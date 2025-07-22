# Laravel 開發最佳實踐計劃

## 概述

本文檔制定了與 Claude Code 合作進行 Laravel 12 專案開發的標準流程和最佳實踐，基於 2025 年最新的 Laravel 開發標準。

## 當前專案問題分析

### ✅ 已正確設置的部分
- Vite 配置正確（支援 Tailwind CSS v4）
- 標準的 Laravel 12 目錄結構
- 使用 @vite 指令載入資產
- 基本的 TDD 測試結構

### ❌ 需要改進的問題
- `welcome.blade.php` 檔案過大（包含大量內聯 CSS）
- 沒有充分利用 `resources/css/` 和 `resources/js/` 的資產分離
- 缺少頁面特定的資產組織結構

## 改進計劃

### 階段一：重構前端架構

#### 1.1 分離內聯樣式
- **目標**：將 `welcome.blade.php` 中的大量內聯 CSS 移至 `resources/css/` 目錄
- **方法**：
  - 創建 `resources/css/pages/welcome.css` 
  - 提取內聯樣式到專用 CSS 檔案
  - 保持 Tailwind CSS 類別的使用

#### 1.2 建立頁面特定資產結構
```
resources/
├── css/
│   ├── app.css          # 全域樣式
│   ├── components/      # 元件特定樣式
│   └── pages/          # 頁面特定樣式
│       └── welcome.css
├── js/
│   ├── app.js          # 主要入口
│   ├── components/     # 元件 JavaScript
│   └── pages/         # 頁面特定 JavaScript
```
#### 1.3 更新 Vite 配置
- 支援多入口點載入
- 頁面特定資產的條件載入
- 保持熱重載功能

#### 1.4 優化 Blade 模板
- 移除內聯 CSS 和 JavaScript
- 使用條件式 @vite 指令載入頁面特定資產
- 保持語意化的 HTML 結構

### 階段二：建立標準開發工作流程

#### 2.1 Artisan 優先原則
**未來開發新功能時的標準流程：**

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

#### 2.2 TDD 工作流程
**遵循 "Red-Green-Refactor" 循環：**

1. **Red（紅燈）**：先寫失敗的測試
   ```bash
   composer run test  # 確認測試失敗
   ```

2. **Green（綠燈）**：寫最少量的程式碼讓測試通過
   ```bash
   composer run test  # 確認測試通過
   ```

3. **Refactor（重構）**：改善程式碼品質
   ```bash
   composer run code  # 執行程式碼品質檢查
   composer run test  # 確保重構後測試仍然通過
   ```

#### 2.3 前端資產管理標準

**CSS 組織原則：**
- 全域樣式：`resources/css/app.css`
- 頁面特定樣式：`resources/css/pages/{page-name}.css`
- 元件樣式：`resources/css/components/{component-name}.css`

**JavaScript 組織原則：**
- 主要入口：`resources/js/app.js`
- 頁面特定邏輯：`resources/js/pages/{page-name}.js`
- 可重用元件：`resources/js/components/{component-name}.js`

**Blade 模板中的資產載入：**
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- 頁面特定資產 --}}
@if (request()->routeIs('events.*'))
    @vite(['resources/css/pages/events.css', 'resources/js/pages/events.js'])
@endif
```

### 階段三：工作流程文件化與標準化

#### 3.1 更新專案文檔
- **CLAUDE.md**：記錄新的開發約定和 Claude 合作指令
- **README.md**：更新開發環境設置指南
- 創建開發規範文檔

#### 3.2 建立開發範本

**標準 Blade 模板結構：**
```php
{{-- 標準頁面模板結構 --}}
@extends('layouts.app')

@section('title', 'Page Title')

@section('styles')
    @vite(['resources/css/pages/page-name.css'])
@endsection

@section('content')
    {{-- 頁面內容 --}}
@endsection

@section('scripts')
    @vite(['resources/js/pages/page-name.js'])
@endsection
```

**Laravel 12.x Blade 元件標準：**
```php
{{-- 元件使用 (kebab-case 命名) --}}
<x-alert/>
<x-user-profile/>
<x-forms.input name="email" type="email"/>

{{-- 傳遞資料 --}}
<x-alert type="error" :message="$message"/>

{{-- 動態元件 --}}
<x-dynamic-component :component="$componentName" class="mt-4" />
```

**元件檔案結構：**
- **類別型元件**: `app/View/Components/Alert.php`
- **匿名元件**: `resources/views/components/alert.blade.php`
- **巢狀元件**: `resources/views/components/forms/input.blade.php`

**元件屬性定義：**
```php
{{-- 在元件 Blade 檔案中 --}}
@props(['type' => 'info', 'message'])

<div class="alert alert-{{ $type }}">
    {{ $message }}
</div>
```

#### 3.3 開發環境指令確認
確保以下指令正確運行：
```bash
composer run dev      # 啟動所有開發服務
composer run test     # 執行測試套件
composer run code     # 程式碼品質檢查
npm run dev          # Vite 開發服務器
npm run build        # 生產環境建構
```

## 與 Claude Code 的合作約定

### 開發新功能時
1. **Claude 先使用 Artisan 命令**生成檔案骨架
2. **Claude 詢問具體需求**再開始修改生成的檔案
3. **遵循 TDD 流程**：測試 → 實作 → 重構
4. **前端分離**：CSS/JS 寫在 `resources/` 目錄，不內嵌在 Blade 中

### 程式碼審查要點
- ✅ 使用 Laravel 慣例和命名約定
- ✅ 前端資產正確分離
- ✅ 測試覆蓋率充足
- ✅ 通過程式碼品質檢查

### 禁止事項
- ❌ 直接在 Blade 中寫大量內聯 CSS/JS
- ❌ 跳過 Artisan 命令直接創建檔案
- ❌ 沒有測試的程式碼提交
- ❌ 忽略程式碼品質檢查的錯誤

## 總結

這個計劃將幫助我們：
1. **提高程式碼品質**：通過 TDD 和標準化流程
2. **改善開發效率**：利用 Laravel 工具和慣例
3. **增強可維護性**：清楚的檔案組織和資產分離
4. **促進合作**：明確的約定和期望

遵循這些原則將確保專案保持高品質且符合 Laravel 2025 最佳實踐。