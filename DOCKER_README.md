# 🐳 Laravel When2Meet - Docker 部署

快速使用 Docker 部署 When2Meet 事件排程應用程式。

## 🚀 快速開始（3 步驟）

### 方法一：使用自動化腳本（推薦）

```bash
# 1. 執行快速部署腳本
chmod +x docker-quick-start.sh
./docker-quick-start.sh

# 2. 訪問應用程式
open http://localhost:8000
```

腳本會自動完成：
- ✅ 環境變數設定
- ✅ APP_KEY 生成
- ✅ Docker 映像建置
- ✅ 容器啟動
- ✅ 資料庫遷移
- ✅ Laravel 快取優化

### 方法二：手動部署

```bash
# 1. 設定環境變數
cp .env.docker .env
vim .env  # 設定 DB_PASSWORD

# 2. 生成應用程式金鑰
docker compose run --rm app php artisan key:generate

# 3. 啟動服務
docker compose up -d

# 4. 執行資料庫遷移
docker compose exec app php artisan migrate
```

## 📦 Docker 架構

```
┌────────────────────────┐
│  app (Nginx+PHP-FPM)   │  ← Port 8000
│  Laravel 12 + PHP 8.4  │
└───────────┬────────────┘
            │
┌───────────▼────────────┐
│  postgres              │  ← Port 5432
│  PostgreSQL 16         │
└────────────────────────┘
```

## 🛠️ 常用命令

```bash
# 查看容器狀態
docker compose ps

# 查看日誌
docker compose logs -f app

# 進入容器
docker compose exec app bash

# 停止服務
docker compose down

# 重啟服務
docker compose restart

# 執行 Artisan 命令
docker compose exec app php artisan cache:clear
```

## ⚙️ 環境變數配置

**必須設定的變數**（`.env` 檔案）：

```ini
# 資料庫密碼（強密碼）
DB_PASSWORD=your_secure_password_here

# 應用程式 URL
APP_URL=http://localhost:8000

# 應用程式金鑰（自動生成）
APP_KEY=base64:...
```

**可選變數**：

```ini
# 應用程式埠號（預設 8000）
APP_PORT=8000

# 快取驅動（database 或 redis）
CACHE_STORE=database
SESSION_DRIVER=database
```

## 📊 PHP 版本選擇

專案已配置 **PHP 8.4** 作為生產環境版本：

| PHP 版本 | Laravel 12 支援 | 狀態 | 建議用途 |
|---------|---------------|------|---------|
| PHP 8.2 | ✅ 官方支援 | 穩定 | 相容性優先 |
| PHP 8.3 | ✅ 官方支援 | 穩定 | 平衡選擇 |
| **PHP 8.4** | ✅ 官方支援 | **推薦** | **生產環境** |
| PHP 8.5 | ⚠️ 未正式支援 | 實驗性 | 開發/測試 |

若要使用 PHP 8.5（實驗性）：

```dockerfile
# 編輯 Dockerfile 第 46 行
FROM php:8.5-fpm-alpine AS production
```

> ⚠️ **注意**: PHP 8.5 尚未獲得 Laravel 12 正式支援，建議等待官方公告後再用於生產環境。

## 🔧 故障排除

### 問題 1: 容器無法啟動

```bash
# 查看錯誤日誌
docker compose logs app

# 檢查環境變數
docker compose exec app env | grep DB_
```

### 問題 2: 資料庫連線失敗

```bash
# 測試 PostgreSQL 連線
docker compose exec postgres pg_isready -U when2meet_user

# 手動測試連線
docker compose exec app php artisan tinker --execute='DB::connection()->getPdo(); echo "Connected!";'
```

### 問題 3: 前端資產 404

```bash
# 檢查 /public/build 目錄
docker compose exec app ls -la /var/www/when2meet/public/build

# 重建映像
docker compose build --no-cache app
```

## 📚 完整文件

詳細部署說明、生產環境設定、效能優化請參考：

👉 **[docs/docker-deployment-guide.md](docs/docker-deployment-guide.md)**

包含：
- GCP e2-micro 部署步驟
- SSL/TLS 憑證設定
- 備份與還原
- 監控與日誌
- 效能優化
- 安全性檢查清單

## 🎯 生產環境部署

使用 GCP e2-micro（1GB RAM）部署的特殊注意事項：

```bash
# 1. 建立 Swap（必須！）
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# 2. 設定生產環境變數
cp .env.docker .env
vim .env  # 設定 APP_ENV=production, APP_DEBUG=false

# 3. 部署
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
```

## 📝 技術規格

- **Framework**: Laravel 12
- **PHP**: 8.4 (官方支援)
- **Database**: PostgreSQL 16
- **Web Server**: Nginx + PHP-FPM
- **Frontend**: Vite + Tailwind CSS v4（預編譯）
- **Deployment**: Docker + Docker Compose

## 🆘 需要協助？

- **問題回報**: [GitHub Issues](https://github.com/linporu/when2meet/issues)
- **完整文件**: [docs/docker-deployment-guide.md](docs/docker-deployment-guide.md)
- **專案文件**: [docs/PRD.md](docs/PRD.md)

---

## 🐳 Docker Hub 推送指南

### 前置準備

1. **註冊 Docker Hub 帳號**
   - https://hub.docker.com/signup

2. **登入 Docker Hub**
   ```bash
   docker login
   # 輸入你的使用者名稱和密碼
   ```

### 快速推送（使用自動化腳本）

```bash
# 設定你的 Docker Hub 使用者名稱
export DOCKERHUB_USERNAME="your-dockerhub-username"

# 推送 latest 版本
./docker-push.sh

# 推送特定版本
./docker-push.sh v1.0.0
```

腳本會自動：
- ✅ 建置 production image（無 Xdebug）
- ✅ 標記 image（latest + 版本號）
- ✅ 推送到 Docker Hub

### 手動推送

```bash
# 1. 建置 Image
docker compose build --no-cache app

# 2. 標記 Image
docker tag when2meet-app:latest your-dockerhub-username/when2meet:latest
docker tag when2meet-app:latest your-dockerhub-username/when2meet:v1.0.0

# 3. 推送到 Docker Hub
docker push your-dockerhub-username/when2meet:latest
docker push your-dockerhub-username/when2meet:v1.0.0
```

### 從 Docker Hub 拉取並部署

在生產伺服器上：

```bash
# 1. 拉取 Image
docker pull your-dockerhub-username/when2meet:latest

# 2. 建立 docker-compose.yml
cat > docker-compose.yml << 'EOF'
services:
  app:
    image: your-dockerhub-username/when2meet:latest
    container_name: when2meet-app
    restart: unless-stopped
    ports:
      - "8000:80"
    environment:
      APP_NAME: When2Meet
      APP_ENV: production
      APP_KEY: base64:your-app-key-here
      APP_DEBUG: false
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_DATABASE: when2meet
      DB_USERNAME: when2meet_user
      DB_PASSWORD: your-secure-password
    depends_on:
      - postgres

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: when2meet
      POSTGRES_USER: when2meet_user
      POSTGRES_PASSWORD: your-secure-password
    volumes:
      - postgres-data:/var/lib/postgresql/data

volumes:
  postgres-data:
EOF

# 3. 啟動服務
docker compose up -d

# 4. 執行資料庫遷移
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
```

### 版本管理策略

```bash
# 主要版本（不相容變更）
./docker-push.sh v2.0.0

# 次要版本（新功能）
./docker-push.sh v1.1.0

# 修補版本（錯誤修正）
./docker-push.sh v1.0.1
```

---

**版本**: v1.0.0
**最後更新**: 2025-12-23
