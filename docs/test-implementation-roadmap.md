# Laravel 後端測試實施路線圖

> **目標**: 從目前的 59 個測試擴展到 80+ 個全面測試覆蓋  
> **當前狀態**: EventController Feature Tests 已完成 ✅  
> **剩余工作**: 5 個主要測試模組需要實施

## 📋 剩余測試任務清單

### Phase 2: Controller Feature Tests (高優先級)
- [x] ~~EventController Feature Tests~~ ✅ 已完成
- [ ] **ParticipantController Feature Tests** - 下一個目標
- [ ] **Request 驗證測試** (StoreEventRequest, UpdateAvailabilityRequest)

### Phase 3: 整合與錯誤測試 (中優先級)  
- [ ] **完整使用者流程整合測試**
- [ ] **錯誤情境測試** (無效 hash、權限等)

### Phase 4: 基礎建設測試 (低優先級)
- [ ] **Route 測試**

---

## 🎯 Phase 2.1: ParticipantController Feature Tests

### 測試範圍
基於 `app/Http/Controllers/ParticipantController.php` 的三個端點：

```php
POST   /{event:hash}/participants           → setName()
GET    /{event:hash}/participants/{participant}/availability/edit → show() 
PUT    /{event:hash}/participants/{participant}/availability      → update()
```

### 實施步驟

**1. 生成測試檔案**
```bash
php artisan make:test ParticipantControllerFeatureTest --pest
```

**2. 測試案例結構**
```php
describe('ParticipantController Feature Tests', function () {
    describe('Setting Participant Name (POST)', function () {
        // 正常流程測試
        it('creates new participant and redirects to availability edit')
        it('finds existing participant and redirects to availability edit')
        
        // 驗證測試  
        it('fails validation with missing participant name')
        it('fails validation with too long participant name')
        
        // 邊界測試
        it('handles special characters in participant names')
        it('prevents duplicate participants in same event')
    });
    
    describe('Showing Availability Edit (GET)', function () {
        it('displays availability edit form for valid participant')
        it('returns 404 for invalid participant ID')
        it('redirects when participant does not belong to event')
        it('loads existing availability data correctly')
        it('generates correct time options for dropdowns')
    });
    
    describe('Updating Availability (PUT)', function () {
        it('updates participant availability successfully')
        it('handles empty availability data')
        it('validates time range formats')
        it('deletes old availability before creating new ones')
        it('returns success message after update')
    });
});
```

**3. 關鍵測試實作要點**

- **Factory Dependencies**: 確保 Event, EventParticipant, EventTimeSlot 工廠正確設定
- **Route Model Binding**: 測試 `{event:hash}` 和 `{participant}` 參數綁定
- **Business Logic**: 驗證 `firstOrCreate` 邏輯和權限檢查
- **View Data**: 確認 `existingAvailability` 和 `timeOptions` 資料結構

### 預期挑戰與解決方案

**挑戰 1**: UpdateAvailabilityRequest 驗證複雜
```php
// 解決方案：模擬複雜的可用性資料結構
$availabilityData = [
    '2024-02-15' => [
        ['start_time' => '09:00', 'end_time' => '12:00'],
        ['start_time' => '14:00', 'end_time' => '17:00'],
    ]
];
```

**挑戰 2**: 權限檢查測試
```php
// 解決方案：測試跨事件存取
$event1 = Event::factory()->create();
$event2 = Event::factory()->create();
$participant = EventParticipant::factory()->forEvent($event2)->create();

// 應該重導向並顯示錯誤
$response = $this->get("/{$event1->hash}/participants/{$participant->id}/availability/edit");
```

---

## 🎯 Phase 2.2: Request 驗證測試

### StoreEventRequest 測試

**目標檔案**: `tests/Unit/StoreEventRequestTest.php`

**測試重點**:
```php
describe('StoreEventRequest Validation', function () {
    it('validates required fields correctly')
    it('validates date format and future date requirement')  
    it('validates time format and end_time after start_time')
    it('validates timezone against allowed list')
    it('provides correct error messages in Traditional Chinese')
    
    // 邊界測試
    it('accepts minimum valid event name length')
    it('rejects event names exceeding 255 characters')
    it('handles timezone edge cases correctly')
});
```

### UpdateAvailabilityRequest 測試

**挑戰**: 複雜的動態驗證邏輯

**測試策略**:
```php
describe('UpdateAvailabilityRequest Validation', function () {
    it('validates getFormattedAvailability method correctly')
    it('handles nested availability array structure')
    it('validates individual time range formats')
    it('rejects invalid time combinations')
    
    // 整合測試
    it('integrates with ParticipantController correctly')
});
```

---

## 🎯 Phase 3: 整合與錯誤測試

### 3.1 完整使用者流程整合測試

**目標**: 驗證端到端業務流程

```php
describe('End-to-End User Journey', function () {
    it('completes full event creation to participation flow', function () {
        // 1. 建立事件
        $eventData = [...];
        $response = $this->post('/', $eventData);
        $event = Event::where('name', $eventData['event_name'])->first();
        
        // 2. 訪問事件頁面
        $response = $this->get('/' . $event->hash);
        $response->assertStatus(200);
        
        // 3. 設定參與者名稱
        $response = $this->post("/{$event->hash}/participants", [
            'participant_name' => 'John Doe'
        ]);
        
        // 4. 編輯可用時間
        $participant = $event->participants->first();
        $response = $this->get("/{$event->hash}/participants/{$participant->id}/availability/edit");
        
        // 5. 提交可用時間
        $availabilityData = [...];
        $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);
        
        // 6. 驗證最終狀態
        $this->assertDatabaseHas('participant_availabilities', [...]);
    });
});
```

### 3.2 錯誤情境測試

```php
describe('Error Scenarios and Edge Cases', function () {
    describe('Invalid Hash Handling', function () {
        it('returns 404 for non-existent event hash')
        it('returns 404 for malformed hash format')
    });
    
    describe('Cross-Event Access Prevention', function () {
        it('prevents participant access across different events')
        it('validates event-participant ownership correctly')
    });
    
    describe('Data Consistency', function () {
        it('handles concurrent participant creation gracefully')
        it('maintains referential integrity during cascading deletes')
    });
});
```

---

## 🎯 Phase 4: Route 測試

### 基礎路由驗證
```php
describe('Route Configuration', function () {
    it('defines all required routes correctly')
    it('uses correct HTTP methods for each endpoint')  
    it('applies route model binding for event hash')
    it('applies route model binding for participant ID')
});
```

---

## 🧠 Test-God 思維應用指南

### 核心原則
1. **業務邏輯優先**: 測試應驗證使用者期望的行為，而非程式碼實作細節
2. **錯誤決策框架**: 遇到測試失敗時，問「這對使用者體驗是否更好？」
3. **漸進式實施**: 每完成一個模組就執行完整測試套件確認無破壞

### 測試失敗分析範本
```
測試失敗: [描述失敗]
目前行為: [程式碼實際做什麼]  
期望行為: [測試期望什麼]
使用者影響: [這對終端使用者有何影響？]
決策: [修改測試/程式碼及原因]
```

### 實施檢查清單

**每個測試模組完成後**:
- [ ] 執行 `composer run test` 確認所有測試通過
- [ ] 執行 `composer run code` 確認程式碼品質
- [ ] 檢查測試覆蓋的業務邏輯是否完整
- [ ] 驗證錯誤訊息是否對使用者友善

**階段完成里程碑**:
- Phase 2 完成: ~70 個測試
- Phase 3 完成: ~78 個測試  
- Phase 4 完成: ~80+ 個測試

---

## 🚀 開始執行

**下一步**: 開始實施 ParticipantController Feature Tests
```bash
# 1. 生成測試檔案
php artisan make:test ParticipantControllerFeatureTest --pest

# 2. 參考 EventControllerFeatureTest.php 的結構
# 3. 實施第一個測試案例: 'creates new participant and redirects to availability edit'
# 4. 逐步完成所有測試案例
```

**成功標準**: 
- 所有測試通過
- 覆蓋 ParticipantController 的三個主要端點
- 包含正常流程、驗證失敗、邊界條件測試
- 遵循 Test-God 的業務邏輯優先原則

---

*這份文件基於已完成的 EventController Feature Tests 經驗，並遵循 Test-God 測試哲學。每個階段都應該以使用者體驗為核心，確保測試驗證的是正確的業務行為。*