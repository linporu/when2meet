#!/bin/bash

# ============================================
# Docker Quick Start Script
# Laravel When2Meet 專案快速部署腳本
# ============================================

set -e  # Exit on error

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 函數：顯示訊息
info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 函數：檢查必要工具
check_requirements() {
    info "檢查必要工具..."

    if ! command -v docker &> /dev/null; then
        error "Docker 未安裝。請先安裝 Docker。"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        error "Docker Compose 未安裝。請先安裝 Docker Compose。"
        exit 1
    fi

    success "所有必要工具已安裝"
}

# 函數：設定環境變數
setup_env() {
    info "設定環境變數..."

    if [ ! -f .env ]; then
        if [ -f .env.docker ]; then
            cp .env.docker .env
            success "已從 .env.docker 複製環境變數檔案"
        else
            cp .env.example .env
            success "已從 .env.example 複製環境變數檔案"
        fi

        warning "請編輯 .env 檔案，設定以下必要變數："
        warning "  - DB_PASSWORD (資料庫密碼)"
        warning "  - APP_URL (應用程式 URL)"
        echo ""
        read -p "按下 Enter 繼續編輯 .env 檔案..."
        ${EDITOR:-vim} .env
    else
        info ".env 檔案已存在，跳過..."
    fi
}

# 函數：生成應用程式金鑰
generate_app_key() {
    info "檢查 APP_KEY..."

    if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
        info "生成 Laravel 應用程式金鑰..."

        # 使用 Docker 容器生成金鑰
        APP_KEY=$(docker compose run --rm app php artisan key:generate --show 2>/dev/null | tail -n 1)

        if [ -n "$APP_KEY" ]; then
            # 更新 .env 檔案
            if [[ "$OSTYPE" == "darwin"* ]]; then
                # macOS
                sed -i '' "s|APP_KEY=.*|APP_KEY=$APP_KEY|g" .env
            else
                # Linux
                sed -i "s|APP_KEY=.*|APP_KEY=$APP_KEY|g" .env
            fi
            success "已生成並設定 APP_KEY"
        else
            error "無法生成 APP_KEY"
            exit 1
        fi
    else
        success "APP_KEY 已存在"
    fi
}

# 函數：建置 Docker 映像
build_images() {
    info "建置 Docker 映像..."
    docker compose build app
    success "Docker 映像建置完成"
}

# 函數：啟動容器
start_containers() {
    info "啟動 Docker 容器..."
    docker compose up -d
    success "Docker 容器已啟動"
}

# 函數：等待資料庫就緒
wait_for_database() {
    info "等待 PostgreSQL 資料庫就緒..."

    MAX_RETRIES=30
    RETRY_COUNT=0

    until docker compose exec -T postgres pg_isready -U when2meet_user &>/dev/null; do
        RETRY_COUNT=$((RETRY_COUNT + 1))
        if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
            error "資料庫未在預期時間內就緒"
            exit 1
        fi
        echo -n "."
        sleep 1
    done

    echo ""
    success "PostgreSQL 資料庫已就緒"
}

# 函數：執行資料庫遷移
run_migrations() {
    info "執行資料庫遷移..."

    # 檢查是否需要執行遷移
    if docker compose exec -T app php artisan migrate:status &>/dev/null | grep -q "Ran"; then
        warning "資料庫遷移已執行過，是否要重新執行？(y/N)"
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            info "跳過資料庫遷移"
            return
        fi
    fi

    docker compose exec -T app php artisan migrate --force
    success "資料庫遷移完成"
}

# 函數：優化 Laravel 快取
optimize_laravel() {
    info "優化 Laravel 快取..."

    docker compose exec -T app php artisan config:cache
    docker compose exec -T app php artisan route:cache
    docker compose exec -T app php artisan view:cache

    success "Laravel 快取優化完成"
}

# 函數：顯示部署資訊
show_deployment_info() {
    echo ""
    echo "============================================"
    success "🎉 When2Meet 部署完成！"
    echo "============================================"
    echo ""
    info "訪問網址: http://localhost:8000"
    echo ""
    info "常用命令："
    echo "  - 查看容器狀態: docker compose ps"
    echo "  - 查看日誌:     docker compose logs -f"
    echo "  - 停止容器:     docker compose down"
    echo "  - 進入容器:     docker compose exec app bash"
    echo ""
    info "完整文件: docs/docker-deployment-guide.md"
    echo "============================================"
    echo ""
}

# 主函數
main() {
    echo "============================================"
    echo "  Laravel When2Meet Docker 快速部署"
    echo "============================================"
    echo ""

    check_requirements
    setup_env
    generate_app_key
    build_images
    start_containers
    wait_for_database
    run_migrations
    optimize_laravel
    show_deployment_info
}

# 執行主函數
main
