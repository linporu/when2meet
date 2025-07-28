### **Laravel 專案部署手冊：從本機到 GCP (with SELinux)**

#### **目標**

將你的 `when2meet` Laravel 專案，安全、高效地部署到 Google Cloud Platform (GCP) 的虛擬機上，並確保在啟用 SELinux 的環境下能正常運作。

---

### **Phase 1: 部署前準備 (在本機上操作)**

在接觸伺服器之前，先確保你的專案已經準備好進入生產環境。

1.  **程式碼版本控制**:
    - 確認所有最新的、穩定的程式碼都已經 commit 到你的 Git Repository。
    - 執行 `git status`，確保工作目錄是乾淨的。

2.  **`.gitignore` 檢查**:
    - 再次確認 `.gitignore` 檔案中有包含以下項目，避免將敏感資訊或不必要的檔案上傳到 Git：
        ```
        /node_modules
        /public/build
        /storage/*.key
        /vendor
        .env
        ```

3.  **設定檔 `.env.example`**:
    - 檢查 `.env.example` 檔案，確保所有未來在正式環境需要的變數都已經被定義。這是一個好習慣，讓未來的設定有跡可循。

---

### **Phase 2: 設定 GCP 伺服器環境**

這部分假設你已經在 GCP 上建立了一台虛擬機（例如 e2-micro 或 e2-small），並可以透過 SSH 連線。建議使用像 **CentOS Stream** 或 **Rocky Linux** 這類預設啟用 SELinux 的作業系統。

1.  **安裝 LEMP Stack (Linux, Nginx, MySQL, PHP)**:
    - **Nginx (網頁伺服器)**:
        ```bash
        sudo dnf install nginx
        sudo systemctl enable --now nginx
        ```
    - **MySQL (資料庫)**: (或 MariaDB)
        ```bash
        sudo dnf install mysql-server
        sudo systemctl enable --now mysqld
        sudo mysql_secure_installation # 執行安全性設定
        ```

        - 登入 MySQL，為你的應用程式建立專用的資料庫和使用者：
            ```sql
            CREATE DATABASE when2meet;
            CREATE USER 'when2meet_user'@'localhost' IDENTIFIED BY '一個非常安全的密碼';
            GRANT ALL PRIVILEGES ON when2meet.* TO 'when2meet_user'@'localhost';
            FLUSH PRIVILEGES;
            EXIT;
            ```
    - **PHP**: 安裝符合你 `composer.json` 版本的 PHP 及必要的擴充套件。
        ```bash
        # 以 PHP 8.2 為例
        sudo dnf install php php-fpm php-mysqlnd php-xml php-mbstring php-json php-bcmath php-zip
        ```

2.  **安裝其他工具**:
    - **Git**: `sudo dnf install git`
    - **Composer**:
        ```bash
        # 從官網下載並安裝 Composer
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
        php -r "unlink('composer-setup.php');"
        ```
    - **Node.js 和 pnpm**:
        ```bash
        # 安裝 Node.js (例如 v20.x)
        curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
        sudo dnf install -y nodejs
        # 安裝 pnpm
        sudo npm install -g pnpm
        ```

---

### **Phase 3: 手動部署流程**

這是將你的程式碼部署到伺服器上的核心步驟。

1.  **建立專案目錄並下載程式碼**:

    ```bash
    # 在 /var/www 建立專案目錄
    sudo mkdir -p /var/www/when2meet
    # 變更目錄擁有者為你的 SSH 使用者，方便操作
    sudo chown -R $USER:$USER /var/www/when2meet
    # 從 Git 下載程式碼
    git clone <你的 Git Repo URL> /var/www/when2meet
    # 進入專案目錄
    cd /var/www/when2meet
    ```

2.  **設定環境變數 (`.env`)**:
    - 複製範例檔，並用文字編輯器 (如 `nano`) 填寫正式環境的設定。
        ```bash
        cp .env.example .env
        nano .env
        ```
    - **務必修改以下關鍵設定**:

        ```ini
        APP_NAME=When2Meet
        APP_ENV=production
        APP_DEBUG=false
        APP_URL=http://<你的網域或 IP>

        # 在伺服器上執行 php artisan key:generate --show 貼上
        APP_KEY=

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=when2meet
        DB_USERNAME=when2meet_user
        DB_PASSWORD=<你設定的資料庫密碼>

        # 為了效能，建議改用 redis
        CACHE_DRIVER=redis
        SESSION_DRIVER=redis
        ```

    - 產生並設定 `APP_KEY`:
        ```bash
        php artisan key:generate
        ```

3.  **安裝相依套件**:
    - **後端 (Composer)**:
        ```bash
        # --no-dev: 不安裝開發用套件
        # --optimize-autoloader: 優化自動載入，提升效能
        composer install --no-dev --optimize-autoloader
        ```
    - **前端 (pnpm)**:
        ```bash
        pnpm install
        pnpm run build
        ```

4.  **設定 Laravel 框架**:
    - **建立儲存連結**:
        ```bash
        php artisan storage:link
        ```
    - **設定目錄權限**: 讓網頁伺服器 (Nginx) 有權限寫入 `storage` 和 `bootstrap/cache`。
        ```bash
        # 將目錄擁有者改為 Nginx 的使用者 (通常是 nginx 或 apache)
        sudo chown -R nginx:nginx storage bootstrap/cache
        # 設定正確的目錄權限
        sudo chmod -R 775 storage bootstrap/cache
        ```

5.  **手動執行資料庫遷移 (Migration)**:
    - **這是你要求的關鍵手動步驟。** 在你確認所有設定都正確後，手動執行以下指令來建立資料庫的資料表：
        ```bash
        php artisan migrate
        ```
    - 在執行前，你可以先用 `php artisan migrate --pretend` 來預覽將會執行的 SQL 指令。

6.  **優化應用程式**:
    - 快取設定檔、路由和視圖，這會**大幅提升**你的應用程式在正式環境的效能。
        ```bash
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        ```

---

### **Phase 4: 設定 Nginx 和 SELinux**

1.  **設定 Nginx**:
    - 為你的網站建立一個新的 Nginx 設定檔：
        ```bash
        sudo nano /etc/nginx/conf.d/when2meet.conf
        ```
    - 貼上以下設定，並將 `your_domain_or_ip` 換成你的網域或 IP：

        ```nginx
        server {
            listen 80;
            server_name your_domain_or_ip;
            root /var/www/when2meet/public;

            add_header X-Frame-Options "SAMEORIGIN";
            add_header X-Content-Type-Options "nosniff";

            index index.php;

            charset utf-8;

            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }

            location = /favicon.ico { access_log off; log_not_found off; }
            location = /robots.txt  { access_log off; log_not_found off; }

            error_page 404 /index.php;

            location ~ \.php$ {
                fastcgi_pass unix:/run/php-fpm/www.sock; # 確認這個路徑是否正確
                fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
                include fastcgi_params;
            }

            location ~ /\.(?!well-known).* {
                deny all;
            }
        }
        ```

    - 測試 Nginx 設定並重新啟動：
        ```bash
        sudo nginx -t
        sudo systemctl restart nginx
        ```

2.  **處理 SELinux**:
    - 部署後，SELinux 很可能會阻止 Nginx 寫入 `storage` 和 `bootstrap/cache` 目錄。
    - 使用 `chcon` 來變更這些目錄的 SELinux 安全性脈絡 (context)：
        ```bash
        sudo chcon -R -t httpd_sys_rw_content_t /var/www/when2meet/storage
        sudo chcon -R -t httpd_sys_rw_content_t /var/www/when2meet/bootstrap/cache
        ```
    - 如果還有權限問題，使用 `audit2why` 來分析日誌，找出被 SELinux 阻擋的原因：
        ```bash
        sudo grep nginx /var/log/audit/audit.log | audit2why
        ```

---

### **部署完成！**

現在，你應該可以透過你的 IP 或網域，看到你的 `when2meet` 應用程式了！

**未來的更新流程**:
當你需要更新程式碼時，只需要在伺服器上重複 **Phase 3** 的部分步驟：

1.  `cd /var/www/when2meet`
2.  `git pull`
3.  `composer install --no-dev --optimize-autoloader`
4.  `pnpm install && pnpm run build`
5.  **(手動)** `php artisan migrate` (如果需要)
6.  `php artisan config:cache && php artisan route:cache && php artisan view:cache`
