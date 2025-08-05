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

### 1. 環境設定檔檢查

#### 1.1 `.gitignore` 檢查

確認 `.gitignore` 檔案包含以下內容，避免將敏感資訊上傳：

```gitignore
# 敏感檔案
.env
.env.backup
.env.production

# 依賴套件
/node_modules
/vendor

# 編譯檔案
/public/build
/public/hot

# 框架快取與日誌
/storage/*.key
/storage/debugbar
/storage/logs
.phpunit.cache

# 資料庫檔案
database/database.sqlite
*.sqlite
*.sqlite3

# 測試覆蓋率
coverage/
.nyc_output/
```

#### 1.2 環境設定檔準備

檢查 `.env.example` 檔案，確保包含生產環境所需的所有變數：

```bash
# 檢查環境變數模板
cat .env.example | grep -E "^[A-Z_]+=.*$"
```

確保 `.env.example` 包含生產環境的適當設定：

- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_LEVEL=error`
- 資料庫連線設定 (PostgreSQL)

### 2. 程式碼版本控制檢查

```bash
# 確認所有程式碼都已提交到 Git
git status
git log --oneline -5

# 合併最新的功能分支到 main
git checkout main
git merge dev
git push origin main

# 建立部署標籤
git tag v1.0.0 -m "Initial production deployment"
git push origin v1.0.0
```

### 3. **e2-micro 資源限制評估**

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
                    php8.3-intl php8.3-gd

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

### 5. Composer 安裝

```bash
# 下載並安裝 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 檢查版本
composer --version
```

### 6. **e2-micro 記憶體優化設定**

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

### 1. 建立專案目錄並下載程式碼（精簡部署）

```bash
# 建立專案目錄
sudo mkdir -p /var/www/when2meet

# 設定目錄擁有者
sudo chown -R $USER:$USER /var/www/when2meet

# 使用 Sparse Checkout 只下載生產環境需要的檔案
git clone --filter=blob:none --no-checkout https://github.com/linporu/when2meet.git /var/www/when2meet

# 進入專案目錄
cd /var/www/when2meet

# 啟用 Sparse Checkout
git sparse-checkout init --cone

# 設定只包含生產環境必要的檔案和目錄
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
  composer.lock

# 檢出檔案
git checkout
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
```

**⚠️ 前端資產說明**：
- 前端資產（CSS/JS）已在本機預先編譯
- `/public/build` 目錄已包含在 Git 中，無需在伺服器上編譯
- 這樣可以節省伺服器資源，避免安裝 Node.js

### 6. 設定檔案權限（精簡檔案結構優化）

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

# 確認 Sparse Checkout 設定檔案權限
chmod 644 /var/www/when2meet/.git/info/sparse-checkout
```

**✅ Sparse Checkout 權限優勢**：

- **減少攻擊面**：測試檔案、開發工具配置不存在於伺服器，無法被攻擊者利用
- **權限精簡**：只需設定真正需要的檔案權限，降低權限管理複雜度
- **安全性提升**：敏感開發資訊（如文件檔案）不會暴露在生產環境

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

### 5. SSL 憑證設定（Cloudflare + Let's Encrypt 雙重保護）

**推薦架構**：`用戶 ←→ Cloudflare SSL ←→ Let's Encrypt SSL ←→ 你的伺服器`

#### 5.1 第一階段：Let's Encrypt 伺服器端設定

```bash
# 安裝 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 取得 SSL 憑證（替換為你的網域）
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 設定自動更新
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# 驗證憑證狀態
sudo certbot certificates
```

#### 5.2 第二階段：Cloudflare 設定

**步驟**：

1. **註冊 Cloudflare 免費帳號**：https://dash.cloudflare.com/sign-up
2. **新增網站**：點擊 "Add a site"，輸入你的網域
3. **更換 DNS**：將你的網域 NS 記錄指向 Cloudflare 提供的 nameservers
4. **設定 SSL 模式**：

在 Cloudflare Dashboard:
SSL/TLS > Overview > Choose "Full (strict)"

⚠️ 重要：絕對不要選擇 "Flexible" 模式

- Flexible：用戶→Cloudflare 加密，Cloudflare→伺服器 明文 ❌
- Full (strict)：雙重 SSL 加密，真正安全 ✅

**Cloudflare 額外優化設定**：

在 Cloudflare Dashboard 可啟用：

- Always Use HTTPS：自動重導向到 HTTPS
- HTTP Strict Transport Security (HSTS)：強制瀏覽器使用 HTTPS
- TLS 1.3：使用最新的 TLS 版本
- Brotli 壓縮：更好的內容壓縮

#### 5.3 驗證雙重 SSL 設定

```bash
# 測試 SSL 憑證（透過 Cloudflare）
curl -I https://your-domain.com

# 檢查 SSL 鏈
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# 檢查伺服器直接連線（繞過 Cloudflare）
curl -I https://your-server-ip --resolve your-domain.com:443:your-server-ip
```

---

## Phase 5: 部署驗證

🎉 恭喜！你的 When2Meet 應用程式現在已經成功部署到 GCP 的 Ubuntu 22.04 伺服器上。

### 快速驗證清單

```bash
# 檢查網站可以正常存取
curl -I http://your-domain.com

# 檢查 SSL 憑證運作（如果已設定）
curl -I https://your-domain.com

# 測試資料庫連線
cd /var/www/when2meet
php artisan tinker
# 在 tinker 中執行：DB::connection()->getPdo();

# 檢查所有服務狀態
sudo systemctl status nginx php8.3-fpm postgresql
```

**功能測試**：

- [ ] 網站首頁可以正常載入
- [ ] 可以建立新事件
- [ ] 可以參與事件並設定時間
- [ ] 群組可用性視覺化正常顯示

### 部署完成後的下一步

部署完成後，建議你：

1. **設定監控系統** - 監控日誌、備份資料庫、追蹤系統資源（參考 `docs/monitoring-guide.md`）
2. **了解故障排除** - 熟悉常見問題的診斷和解決方法（參考 `docs/troubleshooting-guide.md`）
3. **建立維護流程** - 定期更新和保養系統

---

**🚀 部署成功！**

你的 When2Meet 現在已經成功運行在精簡、安全的生產環境中。
