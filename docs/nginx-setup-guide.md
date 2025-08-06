# Nginx 完整設定教學指南：從基礎到生產環境

## 目標

這份指南將教會你如何為 Laravel When2Meet 專案設定一個完整、安全、高效能的 Nginx 虛擬主機，包含 HTTP 和 HTTPS 支援。

**適用環境**：Ubuntu 22.04 + PHP 8.3 + PostgreSQL 16 + Nginx

---

## 📚 第一章：Nginx 基礎概念

### 1.1 什麼是 Nginx 虛擬主機？

**虛擬主機 (Virtual Host)** 讓一台伺服器能夠託管多個網站。每個網站都有自己的設定檔，Nginx 根據請求的網域名稱來決定要使用哪個設定。

```
瀏覽器請求 example.com → Nginx 檢查 server_name → 找到對應設定檔 → 處理請求
```

### 1.2 Nginx 設定檔結構

```
/etc/nginx/
├── nginx.conf          # 主要設定檔
├── sites-available/     # 可用的網站設定檔
│   └── when2meet       # 我們的網站設定檔
└── sites-enabled/       # 已啟用的網站（符號連結）
    └── when2meet → ../sites-available/when2meet
```

**設計理念**：

- `sites-available/` 存放所有網站設定檔
- `sites-enabled/` 只包含已啟用網站的符號連結
- 這樣可以輕鬆啟用/停用網站，無需刪除設定檔

### 1.3 Location 匹配規則

Nginx 使用 `location` 指令來決定如何處理不同的 URL 路徑：

```nginx
location = /exact        # 精確匹配
location ^~ /prefix      # 優先前綴匹配
location ~ \.php$        # 正則表達式匹配（區分大小寫）
location ~* \.(jpg|png)$ # 正則表達式匹配（不區分大小寫）
location /general        # 一般前綴匹配
location /               # 萬用匹配（最低優先級）
```

**優先順序**：精確匹配 > 優先前綴 > 正則表達式 > 一般前綴 > 萬用匹配

---

## 🚀 第二章：完整生產環境設定檔

以下是一個完整的生產級 Nginx 設定檔，同時支援 HTTP 和 HTTPS：

```nginx
# HTTP Server Block - 重導向到 HTTPS
server {
    listen 80;
    listen [::]:80;

    server_name your-domain.com www.your-domain.com;

    # 安全重導向到 HTTPS
    return 301 https://$server_name$request_uri;
}

# HTTPS Server Block - 主要網站
server {
    # HTTPS 監聽設定
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name your-domain.com www.your-domain.com;

    # 網站根目錄
    root /var/www/when2meet/public;
    index index.php index.html;

    # SSL 憑證設定
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # 現代 SSL/TLS 設定
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # 安全標頭
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;

    # 字元編碼
    charset utf-8;

    # 主要路由處理 - Laravel 前端控制器模式
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 靜態資產快取優化
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off;

        # 壓縮設定
        gzip_static on;
        brotli_static on;
    }

    # PHP 檔案處理
    location ~ \.php$ {
        # 安全檢查：只處理存在的 PHP 檔案
        try_files $uri =404;

        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;

        # FastCGI 參數
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;

        # PHP 效能調校
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_busy_buffers_size 32k;
        fastcgi_temp_file_write_size 32k;

        # PHP 超時設定
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    # 安全：隱藏敏感檔案和目錄
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Laravel 特定安全設定
    location ~ ^/(storage|bootstrap/cache) {
        deny all;
    }

    # favicon 和 robots.txt
    location = /favicon.ico {
        access_log off;
        log_not_found off;
        expires 1y;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    # 錯誤頁面處理
    error_page 404 /index.php;

    # 自訂錯誤頁面
    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /var/www/html;
    }

    # 日誌設定
    access_log /var/log/nginx/when2meet.access.log combined;
    error_log /var/log/nginx/when2meet.error.log warn;
}
```

---

## 🔍 第三章：設定檔逐行詳解

### 3.1 HTTP 重導向區塊

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

**詳細解釋**：

- **`listen 80;`** - 監聽 IPv4 的 HTTP 端口
- **`listen [::]:80;`** - 監聽 IPv6 的 HTTP 端口
- **`return 301`** - 永久重導向狀態碼
- **`$server_name`** - 動態獲取伺服器名稱
- **`$request_uri`** - 保持原始請求路徑和參數

**為什麼這樣設計**：

- 強制所有流量使用 HTTPS，提升安全性
- 301 永久重導向告訴搜尋引擎更新索引
- 保持 URL 完整性，用戶體驗無縫

### 3.2 HTTPS 主要設定

```nginx
listen 443 ssl http2;
listen [::]:443 ssl http2;
```

**參數解釋**：

- **`443`** - HTTPS 標準端口
- **`ssl`** - 啟用 SSL/TLS 加密
- **`http2`** - 啟用 HTTP/2 協議，提升效能

**HTTP/2 優勢**：

- 多路復用：同時傳送多個請求
- 標頭壓縮：減少傳輸量
- 伺服器推送：主動推送資源

### 3.3 SSL 憑證與安全設定

```nginx
ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
```

**憑證檔案說明**：

- **`fullchain.pem`** - 完整憑證鏈（包含中繼憑證）
- **`privkey.pem`** - 私密金鑰

```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;
```

**加密設定解釋**：

- **`TLSv1.2 TLSv1.3`** - 只允許現代安全協議
- **`ssl_ciphers`** - 指定強力加密套件
- **`ssl_prefer_server_ciphers off`** - 讓客戶端優先選擇（TLS 1.3 建議）

### 3.4 HTTP 安全標頭深度解析

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

**HSTS (HTTP Strict Transport Security)**：

- **`max-age=31536000`** - 一年內強制 HTTPS（秒為單位）
- **`includeSubDomains`** - 套用到所有子網域
- **`preload`** - 允許加入瀏覽器預載清單

```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
```

**CSP (Content Security Policy)**：

- **`default-src 'self'`** - 預設只允許同網域資源
- **`script-src 'self' 'unsafe-inline'`** - JavaScript 來源政策
- **`style-src 'self' 'unsafe-inline'`** - CSS 來源政策

⚠️ **注意**：`'unsafe-inline'` 降低安全性，生產環境應避免使用

### 3.5 Laravel 前端控制器模式

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**執行邏輯**：

1. **`$uri`** - 嘗試直接存取檔案（如 `/favicon.ico`）
2. **`$uri/`** - 嘗試存取目錄（如 `/images/`）
3. **`/index.php?$query_string`** - 交給 Laravel 路由處理

**Laravel 路由範例**：

- 使用者請求：`/events/123`
- 實際檔案不存在
- 轉交給：`/index.php?$query_string`
- Laravel 路由處理：`Route::get('/events/{id}', ...)`

### 3.6 靜態資產優化

```nginx
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    add_header Vary "Accept-Encoding";
    access_log off;

    gzip_static on;
    brotli_static on;
}
```

**快取策略**：

- **`expires 1y`** - 瀏覽器快取一年
- **`Cache-Control "public, immutable"`** - 檔案不會改變，可安心快取
- **`Vary "Accept-Encoding"`** - 根據編碼方式提供不同版本

**壓縮設定**：

- **`gzip_static on`** - 提供預壓縮的 .gz 檔案
- **`brotli_static on`** - 提供預壓縮的 .br 檔案（更好的壓縮率）

### 3.7 PHP 處理與安全

```nginx
location ~ \.php$ {
    try_files $uri =404;
    # ... 其他設定
}
```

**安全要點**：

- **`try_files $uri =404;`** - 防止執行不存在的 PHP 檔案
- 避免 PHP-CGI 漏洞攻擊

```nginx
fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
fastcgi_param DOCUMENT_ROOT $realpath_root;
```

**路徑處理**：

- **`$realpath_root`** - 解析符號連結，獲取真實路徑
- 避免路徑操作攻擊

---

## 🛠️ 第四章：SSL/HTTPS 完整設定教學

### 4.1 Let's Encrypt 憑證申請

```bash
# 安裝 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 申請憑證（互動式）
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 或者使用非互動式（適合自動化）
sudo certbot --nginx -d your-domain.com -d www.your-domain.com \
    --non-interactive --agree-tos --email your-email@example.com
```

### 4.2 憑證自動更新

```bash
# 測試更新
sudo certbot renew --dry-run

# 設定自動更新 (Certbot 已自動設定)
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# 檢查更新狀態
sudo systemctl status certbot.timer
```

### 4.3 SSL 測試與驗證

```bash
# 檢查憑證資訊
sudo certbot certificates

# 測試 SSL 設定
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# 檢查 SSL 評級（線上工具）
# https://www.ssllabs.com/ssltest/
```

---

## 🔧 第五章：實際操作步驟指南

### 5.1 建立設定檔

```bash
# 建立設定檔
sudo vim /etc/nginx/sites-available/when2meet

# 複製上述完整設定檔內容
# 記得替換 your-domain.com 為你的實際網域
```

### 5.2 設定檔語法檢查

```bash
# 測試 Nginx 設定語法
sudo nginx -t

# 如果有錯誤，會顯示詳細位置
# 常見錯誤：缺少分號、大括號不匹配
```

### 5.3 啟用網站

```bash
# 建立符號連結
sudo ln -s /etc/nginx/sites-available/when2meet /etc/nginx/sites-enabled/

# 移除預設網站（可選）
sudo rm -f /etc/nginx/sites-enabled/default

# 重新載入 Nginx 設定
sudo systemctl reload nginx
```

### 5.4 驗證設定

```bash
# 檢查網站狀態
curl -I http://your-domain.com
curl -I https://your-domain.com

# 檢查重導向
curl -I http://your-domain.com

# 應該看到：
# HTTP/1.1 301 Moved Permanently
# Location: https://your-domain.com/
```

---

## 🚨 第六章：故障排除與最佳實務

### 6.1 常見錯誤與解決方案

#### **錯誤 1：403 Forbidden**

```
# 原因：檔案權限問題
# 解決方案：
sudo chown -R $USER:www-data /var/www/when2meet
sudo find /var/www/when2meet -type d -exec chmod 755 {} \;
sudo find /var/www/when2meet -type f -exec chmod 644 {} \;
```

#### **錯誤 2：502 Bad Gateway**

```
# 原因：PHP-FPM 未運行或 socket 錯誤
# 解決方案：
sudo systemctl restart php8.3-fpm
sudo systemctl status php8.3-fpm

# 檢查 socket 是否存在
ls -la /run/php/php8.3-fpm.sock
```

#### **錯誤 3：SSL 憑證錯誤**

```
# 檢查憑證檔案
sudo certbot certificates

# 重新申請憑證
sudo certbot --nginx --force-renewal -d your-domain.com
```

### 6.2 效能監控

```bash
# 檢查 Nginx 狀態
sudo systemctl status nginx

# 檢查錯誤日誌
sudo tail -f /var/log/nginx/when2meet.error.log

# 檢查存取日誌
sudo tail -f /var/log/nginx/when2meet.access.log

# 檢查 Nginx 進程
ps aux | grep nginx
```

### 6.3 安全檢查清單

- [ ] **SSL 憑證有效** - `sudo certbot certificates`
- [ ] **只允許 TLS 1.2+** - 檢查 `ssl_protocols` 設定
- [ ] **HSTS 標頭啟用** - `curl -I https://your-domain.com | grep Strict`
- [ ] **敏感檔案隱藏** - 嘗試存取 `https://your-domain.com/.env`
- [ ] **HTTP 重導向正常** - `curl -I http://your-domain.com`

### 6.4 效能優化建議

#### **啟用 Gzip 壓縮**

```nginx
# 在 http 區塊中加入
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

#### **調整緩衝區大小**

```nginx
# 根據你的 RAM 調整
client_body_buffer_size 16K;
client_header_buffer_size 1k;
client_max_body_size 8m;
large_client_header_buffers 2 1k;
```

#### **啟用連接復用**

```nginx
keepalive_timeout 15;
keepalive_requests 100;
```

---

## 📋 第七章：完整部署檢查清單

### 7.1 部署前檢查

- [ ] **網域 DNS 設定正確** - `nslookup your-domain.com`
- [ ] **防火牆允許 HTTP/HTTPS** - `sudo ufw status`
- [ ] **PHP-FPM 運行正常** - `sudo systemctl status php8.3-fpm`
- [ ] **PostgreSQL 連線正常** - Laravel 資料庫測試

### 7.2 設定檔檢查

- [ ] **語法正確** - `sudo nginx -t`
- [ ] **路徑正確** - 檢查 `root`、憑證路徑
- [ ] **網域名稱正確** - 檢查 `server_name`
- [ ] **PHP socket 路徑正確** - 檢查 `fastcgi_pass`

### 7.3 SSL 設定檢查

- [ ] **憑證申請成功** - `sudo certbot certificates`
- [ ] **自動更新設定** - `sudo systemctl status certbot.timer`
- [ ] **SSL 評級 A+** - 使用 SSL Labs 測試
- [ ] **HSTS 標頭正確** - 瀏覽器開發者工具檢查

### 7.4 功能測試

- [ ] **HTTP 重導向到 HTTPS**
- [ ] **Laravel 應用正常載入**
- [ ] **靜態檔案正確載入**（CSS、JS、圖片）
- [ ] **PHP 錯誤不暴露**（500 錯誤顯示友善頁面）

---

## 🎯 總結

這份指南提供了一個完整、安全、高效能的 Nginx 設定，適用於 Laravel 應用的生產環境。主要特色：

✅ **安全第一** - 現代 TLS 設定、安全標頭、敏感檔案保護  
✅ **效能優化** - HTTP/2、快取策略、壓縮設定  
✅ **Laravel 支援** - 前端控制器、URL 重寫、PHP 處理  
✅ **維護友善** - 詳細註釋、結構化設定、日誌記錄

**下一步建議**：

1. 定期更新 SSL 憑證
2. 監控效能指標
3. 定期安全審查
4. 備份設定檔案

有了這個設定，你的 When2Meet 應用將擁有企業級的 Web 伺服器配置！
