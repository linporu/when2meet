# Docker 容器化部署指南

本文件提供 Laravel When2Meet 專案的 Docker 容器化部署完整指南。

## 📋 目錄

1. [系統需求](#系統需求)
2. [快速開始](#快速開始)
3. [詳細配置說明](#詳細配置說明)
4. [部署到生產環境](#部署到生產環境)
5. [常見問題排除](#常見問題排除)
6. [維護與監控](#維護與監控)

---

## 系統需求

### 本機開發環境

- **Docker**: 20.10+ 或更新版本
- **Docker Compose**: 2.0+ 或更新版本
- **系統記憶體**: 最少 2GB（建議 4GB+）
- **磁碟空間**: 最少 5GB 可用空間

### 生產環境（GCP e2-micro）

- **RAM**: 1GB（已透過 swap 優化）
- **CPU**: 2 vCPU（共用）
- **儲存**: 20GB HDD
- **作業系統**: Ubuntu 22.04 LTS 或相容系統

---

## 快速開始

### 1. 克隆專案並進入目錄

```bash
git clone https://github.com/linporu/when2meet.git
cd when2meet
```

### 2. 設定環境變數

```bash
# 複製 Docker 環境範本
cp .env.docker .env

# 編輯環境變數（必須設定 DB_PASSWORD 和 APP_KEY）
vim .env  # 或使用 nano .env
```

**必須修改的重要變數**：

```ini
# 資料庫密碼（必須設定強密碼）
DB_PASSWORD=your_secure_password_here

# Laravel 應用程式金鑰（稍後會生成）
APP_KEY=

# 應用程式 URL（根據部署環境調整）
APP_URL=http://localhost:8000
```

### 3. 生成應用程式金鑰

```bash
# 使用 Docker 容器生成金鑰
docker compose run --rm app php artisan key:generate --show

# 將生成的金鑰複製到 .env 檔案的 APP_KEY 變數
```

### 4. 啟動 Docker 容器

```bash
# 啟動所有服務（在背景執行）
docker compose up -d

# 查看容器狀態
docker compose ps

# 查看日誌
docker compose logs -f
```

### 5. 執行資料庫遷移

```bash
# 進入應用程式容器
docker compose exec app bash

# 執行遷移
php artisan migrate

# 檢查遷移狀態
php artisan migrate:status

# 退出容器
exit
```

### 6. 驗證部署

開啟瀏覽器訪問：`http://localhost:8000`

應該可以看到 When2Meet 首頁。

---

## 詳細配置說明

### Docker 架構概覽

專案使用多容器架構：

```
┌─────────────────────────────────────┐
│  app (Nginx + PHP-FPM)              │
│  - Laravel 應用程式                  │
│  - 靜態檔案服務                      │
│  - Port: 8000 → 80                  │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  postgres (PostgreSQL 16)           │
│  - 主資料庫                          │
│  - Port: 5432                       │
└─────────────────────────────────────┘
              ↓ (可選)
┌─────────────────────────────────────┐
│  redis (Redis 7) [可選]             │
│  - 快取層                            │
│  - Port: 6379                       │
└─────────────────────────────────────┘
```

### Dockerfile 多階段構建

專案使用多階段構建優化映像大小：

1. **Stage 1: Composer Builder** - 安裝 PHP 依賴
2. **Stage 2: Production Runtime** - 生產環境（PHP 8.4 + Nginx + Supervisor）
3. **Stage 3: Development Runtime** - 開發環境（包含 Xdebug 和 Node.js）

### 環境變數配置

#### 資料庫配置

```ini
# PostgreSQL（推薦用於生產）
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=when2meet
DB_USERNAME=when2meet_user
DB_PASSWORD=your_secure_password

# SQLite（僅開發環境，不建議容器化使用）
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/when2meet/database/database.sqlite
```

#### 快取配置策略

**方案一：資料庫快取（預設，e2-micro 最佳選擇）**

```ini
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

**方案二：Redis 快取（適合高流量場景）**

```ini
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=redis_password
REDIS_PORT=6379
```

啟動 Redis 服務：

```bash
docker compose --profile full up -d
```

### 持久化資料卷

Docker Compose 自動管理以下持久化資料：

| 卷名稱 | 容器路徑 | 說明 |
|-------|----------|------|
| `./storage` | `/var/www/when2meet/storage` | Laravel 日誌與快取 |
| `./bootstrap/cache` | `/var/www/when2meet/bootstrap/cache` | Laravel 框架快取 |
| `./database` | `/var/www/when2meet/database` | SQLite 資料庫檔案（如使用） |
| `postgres-data` | `/var/lib/postgresql/data` | PostgreSQL 資料 |
| `redis-data` | `/data` | Redis 資料（如啟用） |

---

## 部署到生產環境

### 部署前檢查清單

- [ ] 已設定強密碼（`DB_PASSWORD`、`REDIS_PASSWORD`）
- [ ] 已生成唯一的 `APP_KEY`
- [ ] `.env` 中 `APP_ENV=production`、`APP_DEBUG=false`
- [ ] 已設定正確的 `APP_URL`（含網域）
- [ ] 已執行 `composer run test` 確保測試通過
- [ ] 已執行 `composer run code` 確保程式碼品質

### 建置生產映像

```bash
# 建置生產環境映像
docker compose build --no-cache app

# 標記版本（例如 v1.0.0）
docker tag when2meet-app:latest when2meet-app:v1.0.0
```

### GCP e2-micro 部署步驟

#### 1. 準備伺服器環境

```bash
# SSH 連線到 GCP VM
ssh username@your-vm-ip

# 安裝 Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# 安裝 Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# 驗證安裝
docker --version
docker-compose --version
```

#### 2. 設定 Swap（e2-micro 記憶體優化，重要！）

```bash
# 建立 2GB swap 檔案
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# 永久啟用 swap
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# 調整 swap 優先級
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf

# 驗證 swap
free -h
```

#### 3. 部署應用程式

```bash
# 克隆專案
git clone https://github.com/linporu/when2meet.git
cd when2meet

# 設定環境變數
cp .env.docker .env
vim .env  # 設定生產環境變數

# 生成應用程式金鑰
docker compose run --rm app php artisan key:generate

# 啟動服務
docker compose up -d

# 執行資料庫遷移
docker compose exec app php artisan migrate --force

# 優化 Laravel 快取
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

#### 4. 設定防火牆

```bash
# 安裝 UFW
sudo apt install -y ufw

# 允許 SSH、HTTP、HTTPS
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 啟用防火牆
sudo ufw enable

# 檢查狀態
sudo ufw status
```

### SSL/TLS 憑證設定

**使用 Nginx Proxy Manager（推薦）**

1. 安裝 Nginx Proxy Manager（另一個 Docker Compose 專案）
2. 設定反向代理指向 `http://localhost:8000`
3. 使用內建的 Let's Encrypt 自動申請憑證

**使用 Cloudflare（雙重 SSL）**

1. 將網域 DNS 指向 Cloudflare
2. 在 Cloudflare 設定 SSL 模式為 "Full (strict)"
3. 在伺服器上使用 Certbot 申請 Let's Encrypt 憑證

```bash
# 安裝 Certbot
sudo apt install -y certbot

# 暫停 Docker 容器（釋放 80 port）
docker compose down

# 申請憑證
sudo certbot certonly --standalone -d your-domain.com

# 重新啟動容器
docker compose up -d
```

---

## 常見問題排除

### 問題 1: 容器啟動失敗

**症狀**：`docker compose up` 後容器立即退出

**診斷**：

```bash
# 查看容器日誌
docker compose logs app
docker compose logs postgres

# 查看容器狀態
docker compose ps
```

**常見原因與解決方法**：

1. **資料庫密碼未設定**
   ```bash
   # 確認 .env 檔案中有設定 DB_PASSWORD
   grep DB_PASSWORD .env
   ```

2. **PostgreSQL 未就緒**
   ```bash
   # 檢查 PostgreSQL 健康狀態
   docker compose exec postgres pg_isready -U when2meet_user
   ```

3. **檔案權限問題**
   ```bash
   # 修復 storage 權限
   docker compose exec app chown -R www-data:www-data /var/www/when2meet/storage
   docker compose exec app chmod -R 775 /var/www/when2meet/storage
   ```

### 問題 2: 無法存取網站（502 Bad Gateway）

**診斷步驟**：

```bash
# 1. 檢查 PHP-FPM 是否運行
docker compose exec app ps aux | grep php-fpm

# 2. 檢查 Nginx 錯誤日誌
docker compose exec app tail -f /var/log/nginx/error.log

# 3. 測試 PHP-FPM 連線
docker compose exec app netstat -tuln | grep 9000
```

**解決方法**：

```bash
# 重啟容器
docker compose restart app

# 如果問題持續，重建容器
docker compose down
docker compose up -d --build
```

### 問題 3: 資料庫連線失敗

**症狀**：Laravel 顯示 "SQLSTATE[HY000] [2002] Connection refused"

**診斷**：

```bash
# 檢查資料庫容器狀態
docker compose ps postgres

# 測試資料庫連線
docker compose exec app php artisan tinker --execute='DB::connection()->getPdo(); echo "Connected!";'
```

**解決方法**：

1. **確認環境變數正確**
   ```bash
   docker compose exec app env | grep DB_
   ```

2. **檢查 PostgreSQL 日誌**
   ```bash
   docker compose logs postgres
   ```

3. **手動測試連線**
   ```bash
   docker compose exec app psql -h postgres -U when2meet_user -d when2meet
   ```

### 問題 4: 記憶體不足（e2-micro）

**症狀**：容器隨機重啟或應用程式變慢

**診斷**：

```bash
# 檢查記憶體使用量
docker stats

# 在宿主機檢查
free -h
```

**解決方法**：

1. **確認 swap 已啟用**
   ```bash
   swapon --show
   ```

2. **減少 PHP-FPM 工作進程**

   編輯 `docker/php/php-fpm.conf`：
   ```ini
   pm.max_children = 3  ; 從 5 減少到 3
   pm.start_servers = 1
   pm.min_spare_servers = 1
   pm.max_spare_servers = 2
   ```

3. **停用 Redis（使用資料庫快取）**
   ```bash
   # .env 設定
   CACHE_STORE=database
   SESSION_DRIVER=database

   # 停止 Redis 容器
   docker compose stop redis
   ```

### 問題 5: 前端資產無法載入（404）

**症狀**：CSS/JS 檔案 404 錯誤

**診斷**：

```bash
# 檢查 public/build 目錄是否存在
docker compose exec app ls -la /var/www/when2meet/public/build

# 檢查 Nginx 靜態檔案配置
docker compose exec app cat /etc/nginx/http.d/default.conf | grep build
```

**解決方法**：

```bash
# 確認前端資產已預編譯並提交到 Git
ls -la public/build

# 如果缺少，重新編譯（需要 Node.js）
pnpm install
pnpm run build

# 重建 Docker 映像
docker compose build --no-cache app
docker compose up -d
```

---

## 維護與監控

### 日常維護命令

```bash
# 查看容器狀態
docker compose ps

# 查看資源使用量
docker stats

# 查看應用程式日誌
docker compose logs -f app

# 查看資料庫日誌
docker compose logs -f postgres

# 進入容器 shell
docker compose exec app bash

# 執行 Laravel Artisan 命令
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache
```

### 備份與還原

#### 備份資料庫

```bash
# PostgreSQL 備份
docker compose exec postgres pg_dump -U when2meet_user when2meet > backup_$(date +%Y%m%d).sql

# 或使用 Docker volume 備份
docker run --rm \
  -v when2meet_postgres-data:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/postgres_backup_$(date +%Y%m%d).tar.gz /data
```

#### 還原資料庫

```bash
# PostgreSQL 還原
docker compose exec -T postgres psql -U when2meet_user when2meet < backup_20250101.sql

# 或還原 volume
docker run --rm \
  -v when2meet_postgres-data:/data \
  -v $(pwd):/backup \
  alpine tar xzf /backup/postgres_backup_20250101.tar.gz -C /
```

### 更新部署

```bash
# 1. 拉取最新程式碼
git pull origin main

# 2. 重建映像
docker compose build --no-cache app

# 3. 停止並更新容器（最小停機時間）
docker compose up -d

# 4. 執行新的遷移（如有）
docker compose exec app php artisan migrate --force

# 5. 清除並重建快取
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### 監控與日誌

#### 使用 Docker Compose 內建日誌

```bash
# 即時查看所有日誌
docker compose logs -f

# 查看特定服務日誌
docker compose logs -f app
docker compose logs -f postgres

# 查看最近 100 行日誌
docker compose logs --tail=100 app
```

#### Laravel 日誌位置

```bash
# 查看 Laravel 應用程式日誌
docker compose exec app tail -f /var/www/when2meet/storage/logs/laravel.log

# 查看 PHP-FPM 錯誤日誌
docker compose exec app tail -f /var/log/php-fpm/error.log

# 查看 Nginx 日誌
docker compose exec app tail -f /var/log/nginx/access.log
docker compose exec app tail -f /var/log/nginx/error.log
```

### 效能優化建議

1. **Laravel 快取預熱**
   ```bash
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   docker compose exec app php artisan event:cache
   ```

2. **啟用 OPcache**（已在 `php.ini` 中設定）

   驗證 OPcache 狀態：
   ```bash
   docker compose exec app php -i | grep opcache
   ```

3. **使用 Redis 快取**（適合高流量）
   ```bash
   # 啟動 Redis
   docker compose --profile full up -d

   # 更新 .env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   ```

4. **定期清理日誌**
   ```bash
   # 每週清理舊日誌
   docker compose exec app find /var/www/when2meet/storage/logs -name "*.log" -mtime +7 -delete
   ```

### 安全性檢查清單

- [ ] `.env` 檔案權限設定為 `600`
- [ ] 資料庫密碼使用強密碼（至少 16 字元）
- [ ] `APP_DEBUG=false` 在生產環境
- [ ] 定期更新 Docker 映像（安全性修補）
- [ ] 啟用防火牆（UFW）限制開放埠
- [ ] 使用 HTTPS（SSL/TLS 憑證）
- [ ] 定期備份資料庫
- [ ] 監控容器日誌以偵測異常活動

---

## 附錄

### Docker Compose 命令速查表

| 命令 | 說明 |
|------|------|
| `docker compose up -d` | 啟動所有服務（背景執行） |
| `docker compose down` | 停止並移除所有容器 |
| `docker compose ps` | 查看容器狀態 |
| `docker compose logs -f` | 即時查看日誌 |
| `docker compose exec app bash` | 進入 app 容器 |
| `docker compose restart app` | 重啟 app 容器 |
| `docker compose build --no-cache` | 重新建置映像 |
| `docker compose pull` | 拉取最新映像 |

### Laravel Artisan 常用命令

| 命令 | 說明 |
|------|------|
| `php artisan migrate` | 執行資料庫遷移 |
| `php artisan migrate:status` | 查看遷移狀態 |
| `php artisan migrate:rollback` | 回滾上一批遷移 |
| `php artisan cache:clear` | 清除應用程式快取 |
| `php artisan config:cache` | 快取配置檔 |
| `php artisan route:cache` | 快取路由 |
| `php artisan view:cache` | 快取視圖 |
| `php artisan tinker` | Laravel REPL 環境 |

---

## 參考資源

- [Laravel 12 官方文件](https://laravel.com/docs/12.x)
- [Docker 官方文件](https://docs.docker.com/)
- [Docker Compose 文件](https://docs.docker.com/compose/)
- [PostgreSQL 文件](https://www.postgresql.org/docs/16/)
- [Nginx 文件](https://nginx.org/en/docs/)
- [PHP-FPM 文件](https://www.php.net/manual/en/install.fpm.php)

---

**最後更新**：2025-12-23
**版本**：v1.0.0
