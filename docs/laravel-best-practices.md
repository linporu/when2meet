# Laravel 開發最佳實踐指南

## 概述

本文檔制定了 Laravel 12 專案開發的標準流程和最佳實踐，基於 2025 年最新的 Laravel 開發標準。

## 核心開發原則

### ✅ Artisan 優先原則
**新功能開發的標準流程**：

1. **使用 Artisan 命令生成檔案骨架**
   ```bash
   # 創建控制器
   php artisan make:controller EventController --resource
   
   # 創建模型（含遷移和工廠）
   php artisan make:model Event --migration --factory
   
   # 創建請求驗證類別
   php artisan make:request StoreEventRequest
   
   # 創建 Blade 元件
   php artisan make:component Alert
   php artisan make:component Forms/Input
   
   # 創建測試
   php artisan make:test EventTest --unit
   php artisan make:test EventControllerTest
   ```

2. **從生成的模板開始修改**
   - 利用 Laravel 預設的結構和慣例
   - 確保命名約定的一致性
   - 保持代碼組織的標準化

### ✅ TDD 工作流程
**遵循 "Red-Green-Refactor" 循環**：

1. **Red（紅燈）**：先寫失敗的測試
2. **Green（綠燈）**：寫最少量的程式碼讓測試通過  
3. **Refactor（重構）**：改善程式碼品質

```bash
composer run test  # 執行測試
composer run code  # 程式碼品質檢查和修復
```

### ✅ 前端資產管理標準

**結構組織**：
- 全域樣式：`resources/css/app.css`
- 頁面特定：`resources/css/pages/{page-name}.css`
- 元件樣式：`resources/css/components/{component-name}.css`
- JavaScript：`resources/js/{components,pages,utils}/`

**Blade 模板載入**：
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- 頁面特定資產 --}}
@if (request()->routeIs('events.*'))
    @vite(['resources/css/pages/events.css', 'resources/js/pages/events.js'])
@endif
```

## 開發範本和約定

### 標準 Blade 元件約定

```php
{{-- 元件使用 (kebab-case) --}}
<x-alert type="error" :message="$message"/>
<x-forms.input name="email" type="email"/>
<x-dynamic-component :component="$componentName"/>
```

**元件結構**：
- 類別型：`app/View/Components/Alert.php`
- 匿名型：`resources/views/components/alert.blade.php`
- 巢狀型：`resources/views/components/forms/input.blade.php`

### 開發環境指令

```bash
composer run dev      # 啟動所有開發服務
composer run test     # 執行測試套件
composer run code     # 程式碼品質檢查和修復
```

## 開發約定

### ✅ 核心原則
- 使用 Artisan 命令生成檔案骨架
- 遵循 TDD 流程：測試 → 實作 → 重構
- 前端資產分離：CSS/JS 寫在 `resources/` 目錄
- 遵循 Laravel 慣例和命名約定

### ❌ 禁止事項
- 直接在 Blade 中寫大量內聯 CSS/JS
- 跳過 Artisan 命令直接創建檔案
- 沒有測試的程式碼提交
- 忽略程式碼品質檢查錯誤

---

*本指南基於 Laravel 12 和 2025 年最佳實踐制定*