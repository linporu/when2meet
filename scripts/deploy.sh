#!/bin/bash

#########################################################################
# 簡化版 Laravel 部署腳本
# 用途：基於手動部署指南的核心步驟自動化部署
# 使用方式：./deploy-simple.sh
# 特點：輕量、可靠、易於維護
#########################################################################

set -e  # 遇到錯誤立即退出

#########################################################################
# 配置區域
#########################################################################

# 專案目錄
PROJECT_DIR="/var/www/when2meet"

# 顏色輸出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

#########################################################################
# 工具函數
#########################################################################

log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")
            echo -e "${BLUE}[${timestamp}] [INFO]${NC} $message"
            ;;
        "SUCCESS")
            echo -e "${GREEN}[${timestamp}] [SUCCESS]${NC} $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}[${timestamp}] [WARNING]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[${timestamp}] [ERROR]${NC} $message"
            ;;
    esac
}

error_exit() {
    log "ERROR" "$1"
    log "WARNING" "嘗試恢復應用程式狀態..."
    
    cd "$PROJECT_DIR" 2>/dev/null || true
    
    # 回滾程式碼到部署前版本
    if [ -n "$CURRENT_COMMIT" ]; then
        log "WARNING" "回滾程式碼到之前版本：$CURRENT_COMMIT"
        git reset --hard "$CURRENT_COMMIT" 2>/dev/null || true
        
        # 重新安裝舊版本套件
        log "WARNING" "重新安裝舊版本依賴..."
        composer install --no-dev --optimize-autoloader 2>/dev/null || true
    fi
    
    # 退出維護模式
    php artisan up 2>/dev/null || true
    
    log "ERROR" "部署失敗，已嘗試回滾到穩定版本"
    exit 1
}

#########################################################################
# 主要部署流程
#########################################################################

main() {
    log "INFO" "=========================================="
    log "INFO" "開始 When2Meet 部署流程"
    log "INFO" "時間：$(date)"
    log "INFO" "=========================================="
    
    # 切換到專案目錄
    cd "$PROJECT_DIR" || error_exit "無法進入專案目錄：$PROJECT_DIR"
    
    # 記錄當前版本以備回滾
    CURRENT_COMMIT=$(git rev-parse HEAD)
    log "INFO" "當前版本：$CURRENT_COMMIT"
    
    # 進入維護模式
    log "INFO" "進入維護模式..."
    php artisan down || error_exit "進入維護模式失敗"
    
    # 從 Git 更新程式碼
    log "INFO" "更新程式碼..."
    git fetch origin main || error_exit "Git fetch 失敗"
    git reset --hard origin/main || error_exit "Git reset 失敗"
    
    # 安裝 Composer 套件 (生產環境)
    log "INFO" "安裝 Composer 依賴..."
    composer install --no-dev --optimize-autoloader || error_exit "Composer install 失敗"
    
    # 執行資料庫遷移
    log "INFO" "檢查待執行的遷移..."
    php artisan migrate:status || error_exit "無法檢查遷移狀態"
    
    log "INFO" "執行資料庫遷移..."
    php artisan migrate --force || error_exit "資料庫遷移失敗"
    
    # 清除舊快取
    log "INFO" "清除快取..."
    php artisan cache:clear || log "WARNING" "Cache clear 失敗"
    php artisan config:clear || log "WARNING" "Config clear 失敗"
    php artisan route:clear || log "WARNING" "Route clear 失敗"
    php artisan view:clear || log "WARNING" "View clear 失敗"
    
    # 建立新快取以優化效能
    log "INFO" "建立快取..."
    php artisan config:cache || log "WARNING" "Config cache 失敗"
    php artisan route:cache || log "WARNING" "Route cache 失敗"
    php artisan view:cache || log "WARNING" "View cache 失敗"
    php artisan event:cache || log "WARNING" "Event cache 失敗"
    
    # 退出維護模式
    log "INFO" "退出維護模式..."
    php artisan up || error_exit "退出維護模式失敗"
    
    log "SUCCESS" "=========================================="
    log "SUCCESS" "部署完成！"
    log "SUCCESS" "時間：$(date)"
    log "SUCCESS" "=========================================="
}

#########################################################################
# 腳本入口
#########################################################################

# 檢查是否以正確的用戶執行
if [ "$(whoami)" != "deploy" ] && [ "$(whoami)" != "root" ]; then
    echo "請以 deploy 用戶或 root 用戶執行此腳本"
    exit 1
fi

# 執行主要流程
main "$@"