# When2Meet 監控運維指南

## 目標

本指南提供 When2Meet 應用程式上線後的監控、維護和優化方法，確保系統穩定運行。

---

## 1. 日誌監控

### 基本日誌查看

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

### 日誌輪轉設定

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

## 2. 資料庫備份

### 建立自動備份腳本

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

### 設定自動化備份

```bash
# 設定執行權限
sudo chmod +x /usr/local/bin/backup-when2meet.sh

# 建立 crontab 任務（每日凌晨 2 點備份）
sudo crontab -e
# 新增以下行：
# 0 2 * * * /usr/local/bin/backup-when2meet.sh >> /var/log/backup.log 2>&1
```

---

## 3. 系統效能監控

### 建立資源監控腳本

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

echo "==========================================\n"
```

### 設定定期監控

```bash
# 設定執行權限
sudo chmod +x /usr/local/bin/monitor-resources.sh

# 設定每小時執行監控
sudo crontab -e
# 新增以下行：
# 0 * * * * /usr/local/bin/monitor-resources.sh >> /var/log/resource-monitor.log 2>&1
```

---

## 4. 日常維護建議

### 每週檢查

1. **系統資源使用狀況**
   ```bash
   # 檢查記憶體和磁碟使用
   free -h && df -h
   
   # 檢查系統負載
   uptime
   ```

2. **服務狀態檢查**
   ```bash
   sudo systemctl status nginx php8.3-fpm postgresql
   ```

### 每月檢查

1. **備份檔案完整性**
   ```bash
   # 檢查備份目錄
   ls -la /var/backups/when2meet/
   
   # 測試最新的資料庫備份
   sudo -u postgres psql -c "\l" # 列出所有資料庫
   ```

2. **日誌檔案大小**
   ```bash
   # 檢查日誌檔案大小
   du -sh /var/log/nginx/
   du -sh /var/www/when2meet/storage/logs/
   ```

### 定期更新

1. **系統套件更新**
   ```bash
   sudo apt update && sudo apt upgrade
   ```

2. **Laravel 框架更新**
   ```bash
   cd /var/www/when2meet
   composer update
   ```

---

## 5. 未來的更新流程

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
sudo systemctl restart php8.3-fpm

# 7. 結束維護模式
php artisan up
```

---

## 6. 效能最佳化檢查清單

定期檢查以下項目以確保最佳效能：

- [ ] PHP OpCache 是否啟用
- [ ] Laravel 設定檔案是否已快取
- [ ] 資料庫索引是否建立完成
- [ ] Nginx gzip 壓縮是否啟用
- [ ] SSL 憑證是否正確設定
- [ ] 防火牆規則是否適當
- [ ] 磁碟空間是否充足（< 80%）
- [ ] 記憶體使用是否正常（< 80%）
- [ ] 備份任務是否正常執行

---

**📊 持續監控，確保穩定運行！**