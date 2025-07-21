# Laravel Linting 配置指南 (使用 Laravel Pint)

完整的 Laravel 專案 linting 設定指南，使用 Laravel Pint 和 PHPStan 提供實用且 Laravel 友善的程式碼品質工具配置。

## 🎯 設計哲學

### 核心原則
- **開發效率優先**：不讓 linter 阻礙快速開發
- **Laravel 友善**：支援 Laravel 框架慣例和模式
- **漸進式改善**：從能用開始，逐步提升程式碼品質
- **工具統一**：避免多個工具間的衝突

### 工具選擇
- **Laravel Pint**：Laravel 官方程式碼格式化工具（基於 PHP CS Fixer）
- **PHPStan + Larastan**：靜態分析（Laravel 專用擴展）
- **Level 4 設定**：平衡嚴格性和實用性

## 📦 套件安裝

### 1. Composer 依賴

```bash
# 安裝 linting 工具（Laravel Pint 通常已包含在 Laravel 專案中）
composer require --dev laravel/pint
composer require --dev phpstan/phpstan
composer require --dev larastan/larastan

# 如果有其他格式化工具，可選擇移除避免衝突
composer remove --dev friendsofphp/php-cs-fixer
composer remove --dev squizlabs/php_codesniffer
```

### 2. 驗證安裝

```bash
# 檢查工具版本
./vendor/bin/pint --version
./vendor/bin/phpstan --version
```

## ⚙️ 配置文件

### 1. Laravel Pint 配置 (`pint.json`，可選)

Laravel Pint 預設使用 "laravel" preset，通常不需要額外配置。如需自訂，可建立 `pint.json`：

```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": true,
        "braces": true,
        "new_with_braces": true
    },
    "exclude": [
        "bootstrap/cache",
        "storage",
        "vendor",
        "node_modules"
    ]
}
```

> **注意**：大多數情況下，Laravel Pint 的預設配置就足夠了，不需要建立 `pint.json`。

### 2. PHPStan 配置 (`phpstan.neon`)

```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
        - database
        - routes
        - tests
    
    level: 4
    
    ignoreErrors:
        - '#Undefined variable: \$this#'
        - '#Call to an undefined method PHPUnit\\Framework\\TestCase::#'
        # Laravel Model dynamic properties and methods in tests
        - '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::#'
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Model::#'
    
    excludePaths:
        - bootstrap/*
        - storage/*
        - vendor/*
        - node_modules/*
        - '*/cache/*'
    
    bootstrapFiles:
        - vendor/autoload.php
        
    parallel:
        maximumNumberOfProcesses: 32
```

### 3. Composer Scripts (`composer.json`)

在 `composer.json` 的 `scripts` 區塊新增：

```json
{
    "scripts": {
        "fix": "./vendor/bin/pint --quiet",
        "check": "./vendor/bin/pint --test --quiet",
        "check-verbose": "./vendor/bin/pint --test",
        "stan": "phpstan analyse --no-progress --quiet --memory-limit=256M",
        "stan-verbose": "phpstan analyse --memory-limit=256M",
        "quality": [
            "@check",
            "@stan"
        ],
        "quality-strict": [
            "@check-verbose",
            "@stan-verbose"
        ],
        "ci": [
            "@test",
            "@quality"
        ]
    }
}
```

### 4. VS Code 設定 (`.vscode/settings.json`)

```json
{
    "[php]": {
        "editor.formatOnSave": true,
        "editor.defaultFormatter": "open-southeners.laravel-pint"
    }
}
```

## 🚀 設定步驟

### 1. 建立配置文件

```bash
# 建立 PHPStan 配置
touch phpstan.neon

# Laravel Pint 不需要額外配置檔案（除非需要自訂）
# 如果存在其他格式化工具配置，移除以避免衝突
rm .php-cs-fixer.php phpcs.xml 2>/dev/null || true
```

### 2. 更新 composer.json

```bash
# 編輯 composer.json，新增上述 scripts
# 移除其他格式化工具的 scripts（如果有的話）
```

### 3. 初次運行

```bash
# 檢查並修復格式問題
composer run fix

# 執行靜態分析
composer run stan

# 完整品質檢查
composer run quality
```

## 📋 日常使用

### 開發階段指令

```bash
# 自動修復格式問題（靜默模式）
composer run fix

# 檢查格式問題但不自動修復
composer run check

# 檢查格式問題並顯示詳細資訊
composer run check-verbose

# 執行靜態分析（靜默模式）
composer run stan

# 執行靜態分析並顯示詳細資訊
composer run stan-verbose
```

### CI/CD 指令

```bash
# 完整品質檢查（適合 CI）
composer run quality

# 詳細品質檢查（適合開發除錯）
composer run quality-strict

# 包含測試的完整 CI 流程
composer run ci
```

## 🔧 故障排除

### 常見問題

#### 1. PHPStan 報告大量 Laravel 模型錯誤

**症狀**：`Access to undefined property` 或 `Call to undefined method`

**原因**：未安裝 Larastan 或配置錯誤

**解決**：
```bash
# 確保已安裝 Larastan
composer require --dev larastan/larastan

# 檢查 phpstan.neon 是否包含 Larastan extension
grep -n "larastan" phpstan.neon
```

#### 2. 工具衝突問題

**症狀**：不同工具對同一段程式碼有不同要求

**解決**：
```bash
# 移除其他格式化工具以避免與 Laravel Pint 衝突
composer remove --dev friendsofphp/php-cs-fixer squizlabs/php_codesniffer
rm .php-cs-fixer.php phpcs.xml 2>/dev/null || true

# 統一使用 Laravel Pint
composer run fix
```

#### 3. PHPStan Level 過於嚴格

**症狀**：大量 missing type 錯誤

**解決**：調整 `phpstan.neon` 中的 level 設定
```neon
parameters:
    level: 4  # 從 6 調整為 4
```

### Laravel 特有處理

#### Factory 方法中的未使用參數

Laravel Factory 的 state 方法常有未使用的 `$attributes` 參數，這是正常的框架設計。

```php
// 這是正常的 Laravel 慣例，不需要修改
public function withName(string $name): static
{
    return $this->state(fn (array $attributes) => [
        'name' => $name,
    ]);
}
```

#### Eloquent 關係方法類型聲明

Laravel 慣例中，關係方法通常不需要顯式返回類型：

```php
// Laravel 慣例（Level 4 允許）
public function participants()
{
    return $this->hasMany(EventParticipant::class);
}

// 嚴格類型（Level 6 要求，但不必要）
public function participants(): HasMany
{
    return $this->hasMany(EventParticipant::class);
}
```

## 🎯 最佳實踐

### 1. 開發工作流

```bash
# 開始開發前
composer run quality

# 完成功能後
composer run fix
composer run quality

# 提交前
composer run ci
```

### 2. Git Hooks 整合

建立 `.git/hooks/pre-commit`：

```bash
#!/bin/sh
composer run quality
```

### 3. IDE 整合

**VS Code**：安裝 `Laravel Pint` 和 `PHPStan` 擴展

建立 `.vscode/settings.json`：
```json
{
    "[php]": {
        "editor.formatOnSave": true,
        "editor.defaultFormatter": "open-southeners.laravel-pint"
    }
}
```

**PhpStorm**：
- Settings → PHP → Quality Tools → Laravel Pint (使用 External Tool)
- Settings → PHP → Quality Tools → PHPStan

### 4. CI/CD 配置

**GitHub Actions 範例**：

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - run: composer install
      - run: composer run ci
```

## 🌟 Laravel Pint 優勢

### 相比 PHP CS Fixer 的優勢
- **官方工具**：Laravel 官方支援，與生態系統整合更佳
- **零配置**：開箱即用，預設設定就很適合 Laravel 專案
- **簡化命令**：`./vendor/bin/pint` 比 `php-cs-fixer` 更簡潔
- **Laravel 優化**：針對 Laravel 專案的慣例和模式優化

### 常用指令對比

| 功能 | Laravel Pint | PHP CS Fixer |
|------|-------------|-------------|
| 修復格式 | `./vendor/bin/pint` | `./vendor/bin/php-cs-fixer fix` |
| 檢查格式 | `./vendor/bin/pint --test` | `./vendor/bin/php-cs-fixer fix --dry-run` |
| 指定檔案 | `./vendor/bin/pint app/Models` | `./vendor/bin/php-cs-fixer fix app/Models` |

## 📝 版本歷史

- **v2.0**: 改用 Laravel Pint，簡化配置
- **v1.0**: 初始版本，使用 PHP CS Fixer
- 支援 PHP 8.2+ 和 Laravel 12+
- Level 4 PHPStan 設定平衡實用性與嚴格性
- Laravel 友善的錯誤忽略規則

---

💡 **提示**：這個配置使用 Laravel 官方工具 Laravel Pint，已在實際專案中驗證，提供更簡潔的配置和更好的 Laravel 整合體驗。