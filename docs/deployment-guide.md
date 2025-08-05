# Laravel When2Meet 專案部署手冊：GCP Ubuntu 完整指南

## 目標

將你的 `when2meet` Laravel 專案安全、高效地部署到 Google Cloud Platform (GCP) 的 Ubuntu 22.04 虛擬機上，使用現代化的技術堆疊：**Ubuntu 22.04 + PHP 8.3 + PostgreSQL 16 + Nginx**。

**技術規格**

- **伺服器**: GCP e2-micro (1GB RAM, 20GB HDD)
- **作業系統**: Ubuntu 22.04 LTS
- **資料庫**: PostgreSQL 16
- **網頁伺服器**: Nginx + PHP 8.3-FPM
- **前端建置**: Node.js 22 + PNPM

---

## Phase 1: 部署前準備（本機操作）

在接觸伺服器之前，先確保你的專案已經準備好進入生產環境。

### 1. 程式碼版本控制檢查

```bash
# 確認所有程式碼都已提交到 Git
git status
git log --oneline -5

# 建議建立部署標籤
git tag v1.0.0
git push origin v1.0.0
```

### 2. `.gitignore` 檢查

確認 `.gitignore` 檔案包含以下內容，避免將敏感資訊上傳：

```gitignore
/node_modules
/public/build
/storage/*.key
/vendor
.env
.env.backup
.phpunit.cache
```

### 3. 環境設定檔準備

檢查 `.env.example` 檔案，確保包含生產環境所需的所有變數：

```bash
# 檢查環境變數模板
cat .env.example | grep -E "^[A-Z_]+=.*$"
```

### 4. **e2-micro 資源限制評估**

**⚠️ 重要提醒：e2-micro 資源限制**

- **RAM**: 1GB (需要優化記憶體使用)
- **CPU**: 2 vCPU (共用)
- **儲存**: 20GB (需要監控磁碟使用量)

**建議優化項目**：

- 停用不必要的 Laravel 功能
- 使用資料庫作為快取和 session 儲存
- 定期清理日誌檔案
- 監控記憶體使用量

---

## Phase 2: Ubuntu 22.04 伺服器環境設定

### 1. 系統更新與基礎工具安裝

```bash
# SSH 連線到你的 GCP VM
ssh username@your-vm-external-ip

# 系統更新
sudo apt update && sudo apt upgrade -y

# 安裝基礎工具
sudo apt install -y curl wget git unzip software-properties-common \
                    apt-transport-https ca-certificates gnupg lsb-release
```

### 2. PHP 8.3 安裝

```bash
# 新增 PHP 官方倉庫
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# 安裝 PHP 8.3 及必要擴充套件
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
                    php8.3-pgsql php8.3-xml php8.3-mbstring \
                    php8.3-curl php8.3-zip php8.3-bcmath \
                    php8.3-intl php8.3-gd php8.3-sqlite3

# 檢查 PHP 版本
php -v
```

### 3. PostgreSQL 16 安裝

```bash
# 新增 PostgreSQL 官方倉庫
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt update

# 安裝 PostgreSQL 16
sudo apt install -y postgresql-16 postgresql-contrib-16

# 啟動並設定開機自動啟動
sudo systemctl enable postgresql
sudo systemctl start postgresql

# 檢查狀態
sudo systemctl status postgresql
```

### 4. Nginx 安裝

```bash
# 安裝 Nginx
sudo apt install -y nginx

# 啟動並設定開機自動啟動
sudo systemctl enable nginx
sudo systemctl start nginx

# 檢查狀態
sudo systemctl status nginx
```

### 5. Node.js 22 和 PNPM 安裝

```bash
# 安裝 Node.js 22
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# 安裝 PNPM，使用 Corepack
corepack enable pnpm

# 檢查版本
node -v && npm -v && pnpm -v
```

### 6. Composer 安裝

```bash
# 下載並安裝 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 檢查版本
composer --version
```

### 7. **e2-micro 記憶體優化設定**

```bash
# 建立 swap 檔案以增加虛擬記憶體（重要！）
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# 設定永久掛載
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# 調整 swap 使用優先級
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf

# 檢查記憶體狀態
free -h
```

---

## Phase 3: 手動部署流程

### 1. 建立專案目錄並下載程式碼

```bash
# 建立專案目錄
sudo mkdir -p /var/www/when2meet

# 設定目錄擁有者
sudo chown -R $USER:$USER /var/www/when2meet

# Clone 專案（替換為你的 Git Repository URL）
git clone https://github.com/linporu/when2meet.git /var/www/when2meet

# 進入專案目錄
cd /var/www/when2meet
```

### 2. PostgreSQL 資料庫設定

```bash
# 切換到 postgres 使用者
sudo -u postgres psql

# 在 PostgreSQL 中執行以下 SQL 指令：
CREATE DATABASE when2meet;
CREATE USER when2meet_user WITH PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE when2meet TO when2meet_user;

# 授予 SCHEMA 權限（PostgreSQL 15+ 需要）
\c when2meet
GRANT ALL ON SCHEMA public TO when2meet_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO when2meet_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO when2meet_user;

# 退出 PostgreSQL
\q
```

### 3. 環境變數設定 (`.env`)

```bash
# 複製環境設定檔
cp .env.example .env

# 編輯環境設定（使用 nano 或 vim）
nano .env
```

**重要設定內容**：

```ini
APP_NAME=When2Meet
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain-or-ip

# Laravel 應用程式金鑰（等等會自動產生）
APP_KEY=

# PostgreSQL 資料庫設定
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=when2meet
DB_USERNAME=when2meet_user
DB_PASSWORD=your_secure_password_here

# e2-micro 優化：使用資料庫快取
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# 郵件設定（開發階段可使用 log）
MAIL_MAILER=log

# 時區設定
APP_TIMEZONE=Asia/Taipei
```

### 3.1. 檔案權限安全設定

**⚠️ 重要：檔案權限設定**

正確的檔案權限是 Laravel 部署成功的關鍵。Laravel 需要在特定目錄寫入日誌檔案、Session 檔案、快取檔案等。如果網頁伺服器 (www-data) 沒有權限寫入這些目錄，應用程式會拋出 500 伺服器錯誤。

**權限策略說明**：

- **一般檔案**：644 權限（擁有者可讀寫，群組和其他人只能讀）
- **目錄**：755 權限（擁有者可讀寫執行，群組和其他人可讀執行）
- **可寫目錄**：775 權限（storage, bootstrap/cache）
- **敏感檔案**：600 權限（.env 檔案）

**安全優勢**：

1. **最小權限原則**：網頁伺服器只獲得必需的群組權限
2. **維護便利**：開發者保持檔案擁有權，便於程式碼更新
3. **敏感檔案保護**：.env 等敏感檔案有更嚴格的權限控制

### 4. 產生應用程式金鑰

```bash
# 產生 Laravel 應用程式金鑰
php artisan key:generate

# 檢查 .env 檔案中的 APP_KEY 是否已設定
grep APP_KEY .env
```

### 5. 安裝相依套件

```bash
# 安裝 PHP 後端套件（生產環境優化）
composer install --no-dev --optimize-autoloader

# 安裝前端套件並建置
pnpm install
pnpm run build
```

### 6. 設定檔案權限

```bash
# ⚠️ 重要：採用安全的權限策略
# 設定專案檔案擁有者為當前使用者，群組為 www-data（網頁伺服器）
sudo chown -R $USER:www-data /var/www/when2meet

# 設定基本檔案權限
# 檔案：644 (擁有者可讀寫，群組和其他人只能讀)
# 目錄：755 (擁有者可讀寫執行，群組和其他人可讀執行)
sudo find /var/www/when2meet -type f -exec chmod 644 {} \;
sudo find /var/www/when2meet -type d -exec chmod 755 {} \;

# Laravel 特殊權限：storage 和 bootstrap/cache 需要群組寫入權限
# 775 權限讓 www-data 群組可以寫入日誌檔案和快取檔案
sudo chmod -R 775 /var/www/when2meet/storage
sudo chmod -R 775 /var/www/when2meet/bootstrap/cache

# .env 檔案特殊權限（只有擁有者可讀寫，最高安全性）
chmod 600 /var/www/when2meet/.env
```

### 7. Laravel 框架設定

```bash
# 建立 storage 連結
php artisan storage:link

# 清除所有快取
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 8. **手動執行資料庫遷移（重要步驟）**

```bash
# 測試資料庫連線
php artisan tinker
# 在 tinker 中執行：DB::connection()->getPdo();
# 如果沒有錯誤，表示連線成功，輸入 exit 退出

# 預覽將要執行的遷移
php artisan migrate --pretend

# 確認無誤後，手動執行遷移
php artisan migrate

# 檢查資料表是否建立成功
php artisan tinker
# 在 tinker 中執行：DB::select('SELECT tablename FROM pg_tables WHERE schemaname = \'public\'');
```

### 9. 生產環境效能優化

```bash
# 快取設定檔案（重要！）
php artisan config:cache

# 快取路由
php artisan route:cache

# 快取視圖模板
php artisan view:cache

# 快取事件與監聽器
php artisan event:cache
```

---

## Phase 4: Nginx 設定與 Ubuntu 安全設定

### 1. PHP-FPM 設定優化

```bash
# 編輯 PHP-FPM 配置
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

調整以下設定（e2-micro 記憶體優化）：

```ini
; 減少子程序數量以節省記憶體
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
```

```bash
# 重新啟動 PHP-FPM
sudo systemctl restart php8.3-fpm
```

### 2. Nginx 虛擬主機設定

```bash
# 建立網站設定檔
sudo nano /etc/nginx/sites-available/when2meet
```

**Nginx 設定檔內容**：

```nginx
server {
    listen 80;
    listen [::]:80;

    # 替換為你的網域或 IP
    server_name your-domain.com www.your-domain.com;

    root /var/www/when2meet/public;
    index index.php index.html;

    # 安全標頭
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # 字元編碼
    charset utf-8;

    # 主要路由處理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 靜態檔案快取
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP 檔案處理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # PHP 設定
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }

    # 隱藏敏感檔案
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    # 錯誤頁面
    error_page 404 /index.php;
}
```

### 3. 啟用網站設定

```bash
# 建立符號連結啟用網站
sudo ln -s /etc/nginx/sites-available/when2meet /etc/nginx/sites-enabled/

# 移除預設網站（可選）
sudo rm -f /etc/nginx/sites-enabled/default

# 測試 Nginx 設定
sudo nginx -t

# 重新載入 Nginx 設定
sudo systemctl reload nginx
```

### 4. Ubuntu 防火牆設定

```bash
# 啟用 UFW 防火牆
sudo ufw enable

# 允許 SSH（重要！避免鎖定自己）
sudo ufw allow ssh

# 允許 HTTP 和 HTTPS
sudo ufw allow 'Nginx Full'

# 檢查防火牆狀態
sudo ufw status
```

### 5. SSL 憑證設定（Let's Encrypt）

```bash
# 安裝 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 取得 SSL 憑證（替換為你的網域）
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 設定自動更新
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### 6. 系統服務設定

建立 Laravel 佇列處理服務：

```bash
# 建立 systemd 服務檔案
sudo nano /etc/systemd/system/when2meet-queue.service
```

服務設定內容：

```ini
[Unit]
Description=When2Meet Laravel Queue Worker
After=network.target database.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/when2meet
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
# 啟用並啟動服務
sudo systemctl enable when2meet-queue
sudo systemctl start when2meet-queue

# 檢查服務狀態
sudo systemctl status when2meet-queue
```

---

## Phase 5: 監控與維護

### 1. 日誌監控

```bash
# Laravel 應用程式日誌
sudo tail -f /var/www/when2meet/storage/logs/laravel.log

# Nginx 存取日誌
sudo tail -f /var/log/nginx/access.log

# Nginx 錯誤日誌
sudo tail -f /var/log/nginx/error.log

# PostgreSQL 日誌
sudo tail -f /var/log/postgresql/postgresql-16-main.log

# 系統日誌
sudo journalctl -f -u nginx -u php8.3-fpm -u postgresql
```

### 2. 資料庫備份

建立自動備份腳本：

```bash
# 建立備份目錄
sudo mkdir -p /var/backups/when2meet

# 建立備份腳本
sudo nano /usr/local/bin/backup-when2meet.sh
```

備份腳本內容：

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/when2meet"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="when2meet"
DB_USER="when2meet_user"

# 建立資料庫備份
pg_dump -U $DB_USER -h localhost $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# 建立應用程式備份（不含 vendor 和 node_modules）
tar --exclude='vendor' --exclude='node_modules' --exclude='storage/logs/*' \
    -czf $BACKUP_DIR/app_backup_$DATE.tar.gz /var/www/when2meet

# 刪除 7 天前的備份
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# 設定執行權限
sudo chmod +x /usr/local/bin/backup-when2meet.sh

# 建立 crontab 任務（每日凌晨 2 點備份）
sudo crontab -e
# 新增以下行：
# 0 2 * * * /usr/local/bin/backup-when2meet.sh >> /var/log/backup.log 2>&1
```

### 3. 系統效能監控

建立資源監控腳本：

```bash
# 建立監控腳本
sudo nano /usr/local/bin/monitor-resources.sh
```

監控腳本內容：

```bash
#!/bin/bash
# e2-micro 資源監控腳本

echo "=== 系統資源監控 $(date) ==="

# 記憶體使用情況
echo "記憶體使用："
free -h

# 磁碟使用情況
echo "磁碟使用："
df -h /

# CPU 負載
echo "CPU 負載："
uptime

# PostgreSQL 連線數
echo "資料庫連線數："
sudo -u postgres psql -c "SELECT count(*) as connections FROM pg_stat_activity;"

# Laravel 佇列狀態
echo "Laravel 佇列狀態："
cd /var/www/when2meet && php artisan queue:work --once --quiet

echo "==========================================\n"
```

### 4. 日誌輪轉設定

```bash
# 設定 Laravel 日誌輪轉
sudo nano /etc/logrotate.d/when2meet
```

日誌輪轉設定：

```
/var/www/when2meet/storage/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    notifempty
    create 664 www-data www-data
    postrotate
        systemctl reload php8.3-fpm
    endscript
}
```

---

## Phase 6: 故障排除指南

### 1. 常見問題診斷

#### 問題 1：網站無法存取（502 Bad Gateway）

```bash
# 檢查 PHP-FPM 狀態
sudo systemctl status php8.3-fpm

# 檢查 Nginx 錯誤日誌
sudo tail -n 50 /var/log/nginx/error.log

# 重新啟動服務
sudo systemctl restart php8.3-fpm nginx
```

#### 問題 2：資料庫連線失敗

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

#### 問題 3：檔案權限問題（500 錯誤）

**症狀**：網站顯示白畫面或 500 伺服器錯誤

```bash
# 檢查 Laravel 日誌
sudo tail -n 50 /var/www/when2meet/storage/logs/laravel.log

# 檢查關鍵目錄權限
ls -la /var/www/when2meet/storage/
ls -la /var/www/when2meet/bootstrap/cache/

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

#### 問題 4：記憶體不足（e2-micro 特有）

```bash
# 檢查記憶體使用
free -h

# 檢查 swap 使用
swapon --show

# 重新啟動服務釋放記憶體
sudo systemctl restart php8.3-fpm nginx postgresql
```

#### 問題 5：磁碟空間不足

```bash
# 檢查磁碟使用
df -h

# 清理日誌檔案
sudo find /var/log -name "*.log" -type f -size +100M -exec truncate -s 0 {} \;

# 清理 Laravel 日誌
sudo truncate -s 0 /var/www/when2meet/storage/logs/laravel.log

# 清理套件快取
sudo apt autoremove && sudo apt autoclean
```

### 2. 除錯工具

```bash
# Laravel 除錯模式（僅限開發）
cd /var/www/when2meet
php artisan down
# 編輯 .env：APP_DEBUG=true
php artisan config:clear
php artisan up

# 查看 Laravel 日誌
php artisan log:clear
tail -f storage/logs/laravel.log

# 資料庫查詢除錯
php artisan tinker
# 開啟查詢日誌：DB::enableQueryLog();
# 執行查詢後檢視：DB::getQueryLog();
```

### 3. 效能最佳化檢查清單

- [ ] PHP OpCache 是否啟用
- [ ] Laravel 設定檔案是否已快取
- [ ] 資料庫索引是否建立完成
- [ ] Nginx gzip 壓縮是否啟用
- [ ] SSL 憑證是否正確設定
- [ ] 防火牆規則是否適當

### 4. 緊急恢復程序

#### 系統無法啟動

```bash
# 透過 GCP Console 連線
# 檢查系統日誌
sudo journalctl -b

# 回復到最後一個正常狀態
sudo systemctl disable when2meet-queue
sudo systemctl stop nginx php8.3-fpm postgresql
sudo systemctl start postgresql php8.3-fpm nginx
```

#### 資料庫損壞

```bash
# 從備份恢復資料庫
sudo -u postgres dropdb when2meet
sudo -u postgres createdb when2meet -O when2meet_user
sudo -u postgres psql when2meet < /var/backups/when2meet/db_backup_YYYYMMDD_HHMMSS.sql
```

---

## 部署完成！

🎉 恭喜！你的 When2Meet 應用程式現在已經成功部署到 GCP 的 Ubuntu 22.04 伺服器上。

### 快速驗證清單

- [ ] 網站可以正常存取：`http://your-domain.com`
- [ ] SSL 憑證正常運作：`https://your-domain.com`
- [ ] 資料庫連線正常
- [ ] 建立事件功能正常
- [ ] 參與事件功能正常

### 日常維護建議

1. **每週檢查**：系統資源使用狀況
2. **每月檢查**：備份檔案完整性
3. **定期更新**：系統套件和 Laravel 框架
4. **監控日誌**：注意異常錯誤訊息

### 未來的更新流程

當你需要更新程式碼時，執行以下步驟：

```bash
cd /var/www/when2meet

# 1. 進入維護模式
php artisan down

# 2. 拉取最新程式碼
git pull origin main

# 3. 更新套件
composer install --no-dev --optimize-autoloader
pnpm install && pnpm run build

# 4. 手動執行資料庫遷移（如果有）
php artisan migrate

# 5. 清除並重建快取
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. 重新啟動服務
sudo systemctl restart php8.3-fpm when2meet-queue

# 7. 結束維護模式
php artisan up
```

---

**📧 需要協助？**
如遇到問題，請檢查：

1. 錯誤日誌：`/var/www/when2meet/storage/logs/laravel.log`
2. Nginx 日誌：`/var/log/nginx/error.log`
3. 系統日誌：`sudo journalctl -xe`

祝部署成功！🚀
