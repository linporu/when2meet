# JavaScript 重構思維指南

> **作者**: Backend God  
> **目的**: 訓練系統性重構思維，從混亂代碼到清晰架構

## 目錄

1. [重構的核心思維](#重構的核心思維)
2. [模組分離策略](#模組分離策略)
3. [單一職責原則](#單一職責原則)
4. [依賴管理與組合](#依賴管理與組合)
5. [錯誤處理策略](#錯誤處理策略)
6. [實戰案例分析](#實戰案例分析)
7. [重構檢查清單](#重構檢查清單)

---

## 重構的核心思維

### 什麼是好的代碼？

**壞代碼特徵**：
```javascript
// ❌ 528行的大類別，職責混合
class AvailabilityForm {
    constructor() {
        // 同時處理：表單驗證、DOM操作、時區轉換、HTML生成...
    }
    
    initialize() {
        // 巨大的方法，做太多事情
        this.initAddTimeRangeButtons();
        this.initRemoveTimeRangeButtons();
        this.initTimeRangeValidation();
        this.initFormSubmission();
        this.renderInitialTimeRanges();
        // ... 更多責任
    }
    
    // 重複的錯誤處理邏輯散布各處
    showFieldError(input, message) { /* ... */ }
    clearFieldError(input) { /* ... */ }
    showTimeRangeError() { /* ... */ }
    clearTimeRangeError() { /* ... */ }
}
```

**好代碼特徵**：
```javascript
// ✅ 清晰的職責分離
class AvailabilityForm {
    constructor() {
        this.timezoneConverter = new TimezoneConverter();  // 組合而非繼承
        this.availabilityManagers = [];  // 委託專責組件
    }
    
    initialize() {
        this.initializeAvailabilityManagers();  // 單一責任
        this.initFormSubmission();              // 單一責任
    }
}
```

### 重構的三大原則

1. **單一職責原則 (SRP)**: 每個類別/函數只做一件事
2. **依賴倒置原則 (DIP)**: 依賴抽象，不依賴具體實現
3. **組合優於繼承**: 透過組合建立靈活的系統

---

## 模組分離策略

### 分層架構設計

我們的重構採用了三層架構：

```
resources/js/
├── utils/          # 🔧 工具層：純函數，無狀態
├── components/     # 🧩 組件層：可複用的業務邏輯
└── pages/          # 📄 頁面層：協調組件，處理頁面邏輯
```

### 1. Utils 層 - 純工具函數

**設計思維**：
- 純函數，無副作用
- 高複用性
- 易於測試

```javascript
// ✅ FormValidator - 專注表單驗證
export class FormValidator {
    static validateEventName(input) {
        // 純驗證邏輯，不包含 DOM 操作
    }
    
    static showFieldError(input, message) {
        // 統一的錯誤顯示邏輯
    }
}

// ✅ DOMHelpers - 專注 DOM 操作
export class DOMHelpers {
    static querySelector(selector, parent = document) {
        // 安全的 DOM 查詢
    }
    
    static addDelegatedListener(parent, selector, event, handler) {
        // 事件委託模式
    }
}

// ✅ ErrorHandler - 專注錯誤處理
export class ErrorHandler {
    static safeExecute(fn, context, fallback) {
        // 統一的錯誤處理和日誌
    }
}
```

**為什麼這樣設計**：
- **可測試性**: 純函數易於單元測試
- **可複用性**: 多個組件可以共用同一套工具
- **一致性**: 統一的錯誤處理和 DOM 操作方式

### 2. Components 層 - 業務組件

**設計思維**：
- 封裝特定業務邏輯
- 可獨立測試和複用
- 組合其他組件完成複雜功能

```javascript
// ✅ TimeRangeSelector - 單一時間範圍選擇器
export class TimeRangeSelector {
    constructor(date, index, timeOptions, startTime = "", endTime = "") {
        // 依賴注入，接收所需參數
    }
    
    render(container) {
        // 只負責自己的渲染邏輯
    }
    
    validate() {
        // 只負責自己的驗證邏輯
    }
}

// ✅ AvailabilityManager - 管理多個時間範圍
export class AvailabilityManager {
    constructor(container, date) {
        this.timeRangeSelectors = [];  // 組合多個 TimeRangeSelector
        this.timezoneConverter = new TimezoneConverter();  // 組合工具組件
    }
    
    addTimeRange() {
        // 委託給 TimeRangeSelector 處理
        const selector = new TimeRangeSelector(/*...*/);
        this.timeRangeSelectors.push(selector);
    }
}
```

**為什麼這樣設計**：
- **組合模式**: AvailabilityManager 組合多個 TimeRangeSelector
- **依賴注入**: 通過參數注入依賴，而非內部創建
- **封裝性**: 每個組件只暴露必要的公共接口

### 3. Pages 層 - 頁面協調器

**設計思維**：
- 協調組件，不處理具體業務邏輯
- 處理頁面生命週期
- 連接 UI 和業務邏輯

```javascript
// ✅ 重構後的 AvailabilityForm
export class AvailabilityForm {
    constructor() {
        this.availabilityManagers = [];  // 委託給專責組件
    }
    
    initialize() {
        this.initializeAvailabilityManagers();  // 協調組件初始化
        this.initFormSubmission();              // 處理表單提交
    }
    
    validateAvailabilityForm() {
        // 協調各組件的驗證邏輯
        return this.availabilityManagers.every(manager => manager.validateAll());
    }
}
```

---

## 單一職責原則

### 職責識別技巧

**問題識別**：當一個類別有多個修改的理由時，就違反了 SRP。

**重構前的 AvailabilityForm**：
```javascript
class AvailabilityForm {
    // 職責1: 時區轉換
    convertTimeOptionsToLocal() { /* ... */ }
    convertLocalToUtc() { /* ... */ }
    
    // 職責2: HTML 生成
    createTimeRangeSelectorHTML() { /* ... */ }
    
    // 職責3: DOM 操作
    addNewTimeRange() { /* ... */ }
    removeTimeRange() { /* ... */ }
    
    // 職責4: 表單驗證
    validateTimeRangeSelector() { /* ... */ }
    showTimeRangeError() { /* ... */ }
    
    // 職責5: 索引管理
    reindexTimeRangeSelectors() { /* ... */ }
}
```

**重構後的職責分離**：
```javascript
// 職責1: 時區轉換 → TimezoneConverter
class TimezoneConverter {
    convertUtcToLocal(utcTimeString) { /* ... */ }
    convertLocalToUtc(localTimeString) { /* ... */ }
}

// 職責2: 單一時間範圍 → TimeRangeSelector  
class TimeRangeSelector {
    createHTML() { /* ... */ }
    validate() { /* ... */ }
}

// 職責3: 多時間範圍管理 → AvailabilityManager
class AvailabilityManager {
    addTimeRange() { /* ... */ }
    removeTimeRange() { /* ... */ }
    reindexSelectors() { /* ... */ }
}

// 職責4: 表單驗證 → FormValidator (utils)
class FormValidator {
    static validateTimeRangeSelector() { /* ... */ }
    static showTimeRangeError() { /* ... */ }
}
```

### 職責分離的好處

1. **易於理解**: 每個類別的目的明確
2. **易於測試**: 可以獨立測試每個職責
3. **易於維護**: 修改一個功能不會影響其他功能
4. **易於擴展**: 可以輕鬆添加新功能

---

## 依賴管理與組合

### 依賴注入模式

**壞的依賴管理**：
```javascript
class AvailabilityForm {
    constructor() {
        // ❌ 緊耦合：內部創建依賴
        this.timezoneConverter = new TimezoneConverter();
        this.container = document.getElementById('some-id'); // 直接依賴 DOM
    }
}
```

**好的依賴管理**：
```javascript
class TimeRangeSelector {
    constructor(date, index, timeOptions, startTime = "", endTime = "") {
        // ✅ 依賴注入：通過參數接收依賴
        this.date = date;
        this.index = index;
        this.timeOptions = timeOptions;
    }
}

class AvailabilityManager {
    constructor(container, date) {
        // ✅ 依賴注入：接收必要的外部依賴
        this.container = container;
        this.date = date;
        // ✅ 組合：使用其他組件
        this.timezoneConverter = new TimezoneConverter();
    }
}
```

### 組合模式的威力

**層次化組合**：
```
AvailabilityForm
├── AvailabilityManager (多個)
│   ├── TimeRangeSelector (多個)
│   └── TimezoneConverter
├── TimezoneConverter
└── FormValidator (靜態方法)
```

**組合的好處**：
1. **靈活性**: 可以動態組合不同的組件
2. **可測試性**: 可以注入 mock 對象進行測試
3. **可擴展性**: 新增功能不需要修改現有代碼

---

## 錯誤處理策略

### 統一的錯誤處理

**重構前**：錯誤處理散布各處
```javascript
// ❌ 重複的 try-catch 邏輯
convertUtcToLocal(utcTimeString) {
    try {
        // 轉換邏輯
    } catch (error) {
        console.error("Time conversion failed:", error);
        return utcTimeString;
    }
}

initializePageTimezone() {
    try {
        // 初始化邏輯
    } catch (error) {
        console.error("Timezone initialization failed:", error);
        // 降級處理
    }
}
```

**重構後**：統一的錯誤處理
```javascript
// ✅ ErrorHandler 統一處理
class ErrorHandler {
    static safeExecute(fn, context, fallback) {
        try {
            return fn();
        } catch (error) {
            this.logError(error, context);
            return fallback;
        }
    }
    
    static logError(error, context) {
        console.error(`[${new Date().toISOString()}] ${context}: ${error.message}`);
    }
}

// ✅ 使用統一的錯誤處理
convertUtcToLocal(utcTimeString) {
    return ErrorHandler.safeExecute(() => {
        // 轉換邏輯
    }, 'UTC to Local Conversion', utcTimeString);
}
```

### 錯誤處理的層次

1. **工具層**: 提供 safeExecute 等錯誤處理工具
2. **組件層**: 使用工具層進行錯誤處理，提供降級方案
3. **頁面層**: 處理業務層面的錯誤，如表單驗證失敗

---

## 實戰案例分析

### 案例：availability.js 重構

**原始問題**：
- 528 行的巨大類別
- 職責混合：時區轉換 + DOM 操作 + 表單驗證 + HTML 生成
- 代碼重複：events.js 和 availability.js 有重複的驗證邏輯
- 難以測試和維護

**重構步驟**：

#### 步驟 1: 識別職責並提取工具類
```javascript
// 提取共用的表單驗證邏輯
FormValidator.validateEventName()  // events.js 和 availability.js 都會用到
FormValidator.showFieldError()     // 統一的錯誤顯示
```

#### 步驟 2: 提取業務組件
```javascript
// 時區轉換邏輯 → TimezoneConverter
TimezoneConverter.convertUtcToLocal()

// 單一時間範圍邏輯 → TimeRangeSelector  
TimeRangeSelector.createHTML()
TimeRangeSelector.validate()
```

#### 步驟 3: 建立組合組件
```javascript
// AvailabilityManager 組合多個 TimeRangeSelector
class AvailabilityManager {
    constructor(container, date) {
        this.timeRangeSelectors = [];  // 組合模式
    }
    
    addTimeRange() {
        const selector = new TimeRangeSelector(/*...*/);  // 委託
        this.timeRangeSelectors.push(selector);
    }
}
```

#### 步驟 4: 重構頁面協調器
```javascript
// AvailabilityForm 變成純協調器
class AvailabilityForm {
    constructor() {
        this.availabilityManagers = [];  // 委託給專責組件
    }
    
    validateAvailabilityForm() {
        // 協調各組件進行驗證
        return this.availabilityManagers.every(manager => manager.validateAll());
    }
}
```

**重構結果**：
- **代碼行數**: 528 行 → 200 行 (AvailabilityForm)
- **職責清晰**: 每個類別都有明確的單一職責
- **代碼複用**: FormValidator 被 events.js 和 availability.js 共用
- **易於測試**: 每個組件都可以獨立測試

---

## 重構檢查清單

### 🔍 重構前診斷

- [ ] **類別行數**: 是否超過 200 行？
- [ ] **方法行數**: 是否有方法超過 20 行？
- [ ] **職責數量**: 類別是否承擔多個職責？
- [ ] **代碼重複**: 是否有重複的邏輯？
- [ ] **依賴關係**: 是否有緊耦合的依賴？

### 🛠️ 重構步驟

1. **識別職責**
   - [ ] 列出類別的所有職責
   - [ ] 識別可以提取的通用工具
   - [ ] 找出重複的代碼

2. **提取工具層**
   - [ ] 創建 utils/ 目錄
   - [ ] 提取純函數和工具類
   - [ ] 統一錯誤處理邏輯

3. **設計組件層**
   - [ ] 每個組件單一職責
   - [ ] 使用依賴注入
   - [ ] 組合其他組件

4. **重構頁面層**
   - [ ] 頁面類別變成協調器
   - [ ] 委託具體業務給組件
   - [ ] 處理頁面生命週期

### ✅ 重構後驗證

- [ ] **可讀性**: 代碼是否容易理解？
- [ ] **可測試性**: 是否可以輕鬆編寫單元測試？
- [ ] **可維護性**: 修改功能是否不會影響其他部分？
- [ ] **可擴展性**: 是否容易添加新功能？
- [ ] **性能**: 重構是否影響性能？

---

## 重構思維訓練

### 練習 1: 職責識別

看到一個類別時，問自己：
1. 這個類別在做什麼？（應該只有一個答案）
2. 如果要修改某個功能，會有幾個地方需要改？
3. 這個類別有幾個修改的理由？

### 練習 2: 依賴分析

分析類別的依賴：
1. 這個類別依賴什麼？
2. 依賴是如何創建的？
3. 能否通過依賴注入來解耦？

### 練習 3: 組合設計

設計組件時思考：
1. 這個組件可以獨立存在嗎？
2. 它需要什麼外部依賴？
3. 如何通過組合來實現複雜功能？

---

## 結語

重構不只是技術活動，更是**思維方式的轉變**：

1. **從程序性思維轉向物件導向思維**
2. **從單一大類別轉向組合小組件**  
3. **從重複代碼轉向工具複用**
4. **從隱含依賴轉向明確注入**

好的重構應該讓代碼變得：
- **更易讀**: 每個人都能快速理解
- **更易測**: 每個組件都可以獨立測試
- **更易改**: 修改功能不會破壞其他部分
- **更易擴**: 添加新功能變得簡單

記住：**重構是一個漸進的過程，不要試圖一次性完美**。從識別最明顯的問題開始，逐步改善代碼品質。

---

**祝你重構愉快！** 🚀