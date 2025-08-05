# When2Meet 故障排除指南

## 目標

本指南提供 When2Meet 應用程式常見問題的診斷和解決方案，幫助快速定位和修復問題。

---

## 1. 常見問題診斷

### 問題 1：網站無法存取（502 Bad Gateway）

**症狀**：瀏覽器顯示 "502 Bad Gateway" 錯誤

**診斷步驟**：
```bash
# 檢查 PHP-FPM 狀態
sudo systemctl status php8.3-fpm

# 檢查 Nginx 錯誤日誌
sudo tail -n 50 /var/log/nginx/error.log

# 檢查 PHP-FPM 日誌
sudo tail -n 50 /var/log/php8.3-fpm.log
```

**解決方案**：
```bash
# 重新啟動服務
sudo systemctl restart php8.3-fpm nginx

# 如果問題持續，檢查配置檔案
sudo nginx -t
sudo php-fpm8.3 -t
```

---

### 問題 2：資料庫連線失敗

**症狀**：Laravel 顯示資料庫連線錯誤

**診斷步驟**：
```bash
# 檢查 PostgreSQL 狀態
sudo systemctl status postgresql

# 測試資料庫連線
sudo -u postgres psql -c "SELECT version();"

# 檢查 Laravel 設定
cd /var/www/when2meet
php artisan tinker
# 在 tinker 中執行：DB::connection()->getPdo();
```

**解決方案**：
```bash
# 重新啟動 PostgreSQL
sudo systemctl restart postgresql

# 檢查 .env 檔案中的資料庫設定
grep -E "^DB_" /var/www/when2meet/.env

# 測試具體的資料庫連線
sudo -u postgres psql -h 127.0.0.1 -U when2meet_user -d when2meet
```

---

### 問題 3：檔案權限問題（500 錯誤）

**症狀**：網站顯示白畫面或 500 伺服器錯誤

**診斷步驟**：
```bash
# 檢查 Laravel 日誌
sudo tail -n 50 /var/www/when2meet/storage/logs/laravel.log

# 檢查關鍵目錄權限
ls -la /var/www/when2meet/storage/
ls -la /var/www/when2meet/bootstrap/cache/

# 檢查 .env 檔案權限
ls -la /var/www/when2meet/.env
```

**解決方案**：
```bash
# 重新設定權限（如果發現權限不正確）
sudo chown -R $USER:www-data /var/www/when2meet
sudo chmod -R 775 /var/www/when2meet/storage
sudo chmod -R 775 /var/www/when2meet/bootstrap/cache
chmod 600 /var/www/when2meet/.env

# 測試寫入權限
sudo -u www-data touch /var/www/when2meet/storage/logs/test.log
# 如果成功建立檔案，表示權限正確
sudo rm /var/www/when2meet/storage/logs/test.log
```

---

### 問題 4：記憶體不足（e2-micro 特有）

**症狀**：網站響應緩慢或服務異常終止

**診斷步驟**：
```bash
# 檢查記憶體使用
free -h

# 檢查 swap 使用
swapon --show

# 檢查系統日誌中的 OOM (Out of Memory) 錯誤
sudo dmesg | grep -i "out of memory"
```

**解決方案**：
```bash
# 重新啟動服務釋放記憶體
sudo systemctl restart php8.3-fpm nginx postgresql

# 如果 swap 未啟用，重新啟用
sudo swapon /swapfile

# 檢查是否有記憶體洩漏的進程
top -o %MEM
```

---

### 問題 5：磁碟空間不足

**症狀**：應用程式無法寫入檔案或日誌

**診斷步驟**：
```bash
# 檢查磁碟使用
df -h

# 檢查哪些目錄佔用空間最大
sudo du -sh /var/* | sort -rh | head -10
```

**解決方案**：
```bash
# 清理日誌檔案
sudo find /var/log -name "*.log" -type f -size +100M -exec truncate -s 0 {} \;

# 清理 Laravel 日誌
sudo truncate -s 0 /var/www/when2meet/storage/logs/laravel.log

# 清理套件快取
sudo apt autoremove && sudo apt autoclean

# 清理舊的備份檔案（如果有）
sudo find /var/backups -name "*.sql" -mtime +30 -delete
```

---

## 2. 除錯工具

### Laravel 除錯模式

⚠️ **警告：只在測試環境使用，生產環境不要開啟除錯模式**

```bash
# 暫時開啟除錯模式
cd /var/www/when2meet
php artisan down
# 編輯 .env：APP_DEBUG=true
php artisan config:clear
php artisan up

# 查看 Laravel 日誌
php artisan log:clear
tail -f storage/logs/laravel.log

# 關閉除錯模式
# 編輯 .env：APP_DEBUG=false
php artisan config:clear
```

### 資料庫查詢除錯

```bash
# 進入 Laravel Tinker
cd /var/www/when2meet
php artisan tinker

# 在 tinker 中開啟查詢日誌
DB::enableQueryLog();
# 執行一些操作後檢視查詢
DB::getQueryLog();
```

### 系統狀態檢查

```bash
# 檢查所有相關服務狀態
sudo systemctl status nginx php8.3-fpm postgresql

# 檢查連接埠使用情況
sudo netstat -tlnp | grep -E ":80|:443|:5432|:9000"

# 檢查防火牆狀態
sudo ufw status

# 檢查系統資源
htop
```

---

## 3. 緊急恢復程序

### 系統無法啟動

```bash
# 透過 GCP Console 連線
# 檢查系統日誌
sudo journalctl -b

# 檢查磁碟空間
df -h

# 回復到最後一個正常狀態
sudo systemctl stop nginx php8.3-fpm postgresql
sudo systemctl start postgresql php8.3-fpm nginx

# 檢查服務啟動狀態
sudo systemctl status nginx php8.3-fpm postgresql
```

### 資料庫損壞

```bash
# 停止應用程式
cd /var/www/when2meet
php artisan down

# 從備份恢復資料庫
sudo -u postgres dropdb when2meet
sudo -u postgres createdb when2meet -O when2meet_user
sudo -u postgres psql when2meet < /var/backups/when2meet/db_backup_YYYYMMDD_HHMMSS.sql

# 重新授權
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE when2meet TO when2meet_user;"
sudo -u postgres psql -d when2meet -c "GRANT ALL ON SCHEMA public TO when2meet_user;"

# 重啟應用程式
php artisan up
```

### 應用程式回滾

```bash
cd /var/www/when2meet

# 進入維護模式
php artisan down

# 回滾到上一個版本
git log --oneline -5  # 查看最近的 commits
git reset --hard HEAD~1  # 回滾到上一個 commit

# 重新安裝依賴
composer install --no-dev --optimize-autoloader
pnpm install && pnpm run build

# 清除快取
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 結束維護模式
php artisan up
```

---

## 4. Git Sparse Checkout 故障排除

### 問題：更新後缺少檔案

**症狀**：`git pull` 後發現需要的檔案不存在

**診斷步驟**：
```bash
# 檢查 sparse-checkout 設定
git sparse-checkout list

# 檢查檔案是否在 repository 中
git ls-tree -r HEAD | grep "missing-file"

# 檢查 Git 狀態
git status
```

**解決方案**：
```bash
# 將缺少的檔案加入 sparse-checkout
git sparse-checkout add path/to/missing-file

# 檢出該檔案
git checkout HEAD -- path/to/missing-file

# 或重新應用 sparse-checkout
git sparse-checkout reapply
```

### 問題：Sparse Checkout 設定損壞

**症狀**：無法正常更新程式碼，或檔案狀態異常

**診斷步驟**：
```bash
# 檢查 sparse-checkout 檔案內容
cat .git/info/sparse-checkout

# 檢查 sparse-checkout 模式
git config core.sparseCheckout
```

**解決方案**：
```bash
# 重新初始化 sparse-checkout
git sparse-checkout init --cone

# 重新設定目錄清單
git sparse-checkout set \
  app \
  bootstrap \
  config \
  database/factories \
  database/migrations \
  database/seeders \
  public \
  resources \
  routes \
  storage/app \
  storage/framework \
  artisan \
  composer.json \
  composer.lock \
  package.json \
  pnpm-lock.yaml

# 強制重新檢出
git read-tree -m -u HEAD
```

### 緊急情況：完整檔案復原

如果需要臨時存取完整檔案進行故障排除：

```bash
# 備份目前的 sparse-checkout 設定
cp .git/info/sparse-checkout .git/info/sparse-checkout.backup

# 停用 sparse-checkout（存取所有檔案）
git sparse-checkout disable
git checkout .

# 進行故障排除...

# 完成後恢復精簡模式
git sparse-checkout init --cone
cp .git/info/sparse-checkout.backup .git/info/sparse-checkout
git sparse-checkout reapply
```

### 檢查精簡部署完整性

定期檢查以確保部署正確：

```bash
# 檢查不應該存在的開發檔案
if ls -la | grep -E "(test|spec|README\.md|docs)" > /dev/null; then
    echo "⚠️  發現不應該存在的開發檔案"
    ls -la | grep -E "(test|spec|README\.md|docs)"
else
    echo "✅ 精簡部署檢查通過"
fi

# 檢查必要檔案是否存在
required_files=("app" "config" "public" "artisan" "composer.json")
for file in "${required_files[@]}"; do
    if [[ ! -e "$file" ]]; then
        echo "❌ 缺少必要檔案: $file"
    else
        echo "✅ $file 存在"
    fi
done
```

---

## 5. 預防性檢查清單

定期執行以下檢查以預防問題發生：

### 每日檢查
- [ ] 檢查錯誤日誌是否有異常
- [ ] 確認網站可正常存取
- [ ] 檢查資料庫連線正常
- [ ] 檢查精簡部署完整性

### 每週檢查
- [ ] 檢查磁碟空間使用率 (< 80%)
- [ ] 檢查記憶體使用率 (< 80%)
- [ ] 檢查系統負載是否正常
- [ ] 確認備份任務正常執行
- [ ] 驗證 sparse-checkout 設定正確

### 每月檢查
- [ ] 更新系統套件
- [ ] 檢查 SSL 憑證有效期
- [ ] 測試備份恢復流程
- [ ] 檢查防火牆規則

---

## 5. 常用診斷指令速查

```bash
# 快速服務重啟
sudo systemctl restart nginx php8.3-fpm postgresql

# 檢查所有服務狀態
sudo systemctl status nginx php8.3-fpm postgresql

# 查看即時日誌
sudo tail -f /var/www/when2meet/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log

# 檢查系統資源
free -h && df -h && uptime

# 測試資料庫連線
cd /var/www/when2meet && php artisan tinker
# 執行：DB::connection()->getPdo();

# 清除 Laravel 快取
cd /var/www/when2meet
php artisan config:clear && php artisan cache:clear
```

---

**🔧 問題排除，系統復原！**

記住：大部分問題都可以通過重新啟動相關服務來解決。如果問題持續，請檢查日誌檔案以獲得更多詳細資訊。