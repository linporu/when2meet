# When2Meet 進階重構計劃

> 基於 2025-07-25 程式碼分析，提出系統架構改善建議
> 
> **當前狀態**: 基礎功能完整，需要提升程式碼品質與維護性
> **目標**: 建立更穩健、可擴展、易維護的系統架構

## 📋 已完成的基礎重構

✅ **語意化命名** (`enterName` → `setName`)  
✅ **友善錯誤處理** (`abort(404)` → `redirect with message`)  
✅ **RESTful 路由結構** (`events.enterName` → `participants.setName`)  

---

## 🎯 進階重構計劃

### 4. 控制器職責分離 (Controller Responsibility Separation)

#### 問題分析
```php
// 現況：單一方法處理多種操作，違反單一職責原則
public function editAvailability(Event $event, EventParticipant $participant)
{
    // GET: 顯示表單
    if (request()->isMethod('GET')) { /* show logic */ }
    
    // POST: 處理表單提交
    if (request()->isMethod('POST')) { /* update logic */ }
}
```

#### 建議重構
```php
// 分離為專門的方法，每個方法負責單一職責
class ParticipantAvailabilityController extends Controller
{
    public function show(Event $event, EventParticipant $participant)
    {
        // 只負責顯示編輯表單
    }
    
    public function update(UpdateAvailabilityRequest $request, Event $event, EventParticipant $participant)
    {
        // 只負責更新availability資料
    }
}
```

#### 路由結構調整
```php
// routes/web.php
Route::prefix('{event:hash}/participants/{participant}')->group(function () {
    Route::get('/availability/edit', [ParticipantAvailabilityController::class, 'show'])
        ->name('participants.availability.edit');
    Route::put('/availability', [ParticipantAvailabilityController::class, 'update'])
        ->name('participants.availability.update');
});
```

#### 實作優先級: **高** (影響維護性和測試性)
#### 預估工時: **4-6小時**

---

### 5. 資料轉換邏輯抽取 (Data Transformation Extraction)

#### 問題分析
```php
// 現況：資料格式化邏輯散落在 Controller 中
$existingAvailability = $participant->participantAvailabilities()
    ->get()
    ->groupBy(function ($availability) {
        return $availability->date->format('Y-m-d');
    })
    ->map(function ($availabilities) {
        return $availabilities->map(function ($availability) {
            return [
                'start_time' => Carbon::parse($availability->start_time)->format('H:i'),
                'end_time' => Carbon::parse($availability->end_time)->format('H:i'),
            ];
        })->toArray();
    })
    ->toArray();
```

#### 建議重構
```php
// app/Services/AvailabilityTransformer.php
class AvailabilityTransformer
{
    public function transformForEdit(Collection $availabilities): array
    {
        return $availabilities
            ->groupBy(fn($availability) => $availability->date->format('Y-m-d'))
            ->map(fn($group) => $group->map(fn($item) => [
                'start_time' => Carbon::parse($item->start_time)->format('H:i'),
                'end_time' => Carbon::parse($item->end_time)->format('H:i'),
            ])->toArray())
            ->toArray();
    }
    
    public function generateTimeOptions(Collection $timeSlots): array
    {
        $options = [];
        $interval = 1800; // 30 minutes
        
        foreach ($timeSlots as $slot) {
            $dateKey = $slot->date->format('Y-m-d');
            $options[$dateKey] = $this->generateTimeSlots(
                $slot->start_time, 
                $slot->end_time, 
                $interval
            );
        }
        
        return $options;
    }
    
    private function generateTimeSlots(string $start, string $end, int $interval): array
    {
        // 時間選項生成邏輯
    }
}
```

#### Controller 使用方式
```php
class ParticipantAvailabilityController extends Controller
{
    public function __construct(
        private AvailabilityTransformer $transformer
    ) {}
    
    public function show(Event $event, EventParticipant $participant)
    {
        $event->load('timeSlots');
        $availabilities = $participant->participantAvailabilities()->get();
        
        $existingAvailability = $this->transformer->transformForEdit($availabilities);
        $timeOptions = $this->transformer->generateTimeOptions($event->timeSlots);
        
        return view('participant-edit', compact('event', 'participant', 'existingAvailability', 'timeOptions'));
    }
}
```

#### 實作優先級: **中** (提升程式碼重用性)
#### 預估工時: **3-4小時**

---

### 6. 驗證邏輯解耦 (Validation Logic Decoupling)

#### 問題分析
```php
// 現況：驗證邏輯直接寫在 Controller 中，難以重用和測試
private function validateAvailabilityData(): array
{
    $rules = [
        'participant_name' => 'required|string|max:255',
        'availability' => 'array',
        // ... 複雜的驗證規則
    ];
    
    $validated = request()->validate($rules, $messages);
    $this->validateTimeRanges($validated['availability'] ?? []);
    
    return $validated;
}
```

#### 建議重構
```php
// app/Http/Requests/UpdateAvailabilityRequest.php
class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 檢查是否有權限編輯此參與者的availability
        return $this->route('participant')->event_id === $this->route('event')->id;
    }
    
    public function rules(): array
    {
        return [
            'participant_name' => 'required|string|max:255',
            'availability' => 'array',
            'availability.*' => 'array',
            'availability.*.*' => 'array',
            'availability.*.*.start_time' => 'nullable|date_format:H:i',
            'availability.*.*.end_time' => 'nullable|date_format:H:i|after:availability.*.*.start_time',
        ];
    }
    
    public function messages(): array
    {
        return [
            'participant_name.required' => 'Please enter your name.',
            'availability.*.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'availability.*.*.end_time.date_format' => 'End time must be in HH:MM format.',
            'availability.*.*.end_time.after' => 'End time must be later than start time.',
        ];
    }
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateTimeRangeLogic($validator);
        });
    }
    
    private function validateTimeRangeLogic($validator)
    {
        // 複雜的時間範圍驗證邏輯
        $availability = $this->input('availability', []);
        
        foreach ($availability as $date => $ranges) {
            foreach ($ranges as $index => $range) {
                if (empty($range['start_time']) !== empty($range['end_time'])) {
                    $validator->errors()->add(
                        "availability.{$date}.{$index}",
                        'Both start time and end time must be selected.'
                    );
                }
            }
        }
    }
    
    public function getFormattedAvailability(): array
    {
        $availability = $this->validated()['availability'] ?? [];
        $result = [];
        
        foreach ($availability as $date => $ranges) {
            foreach ($ranges as $range) {
                if (!empty($range['start_time']) && !empty($range['end_time'])) {
                    $result[] = [
                        'date' => $date,
                        'start_time' => $range['start_time'],
                        'end_time' => $range['end_time'],
                    ];
                }
            }
        }
        
        return $result;
    }
}
```

#### Controller 簡化
```php
public function update(UpdateAvailabilityRequest $request, Event $event, EventParticipant $participant)
{
    $availabilityData = $request->getFormattedAvailability();
    
    // 刪除舊資料，建立新資料
    $participant->participantAvailabilities()->delete();
    
    foreach ($availabilityData as $availability) {
        $participant->participantAvailabilities()->create([
            'event_id' => $event->id,
            'date' => $availability['date'],
            'start_time' => $availability['start_time'],
            'end_time' => $availability['end_time'],
        ]);
    }
    
    return redirect()->route('participants.availability.edit', [$event->hash, $participant->id])
        ->with('success', 'Your availability has been saved successfully!');
}
```

#### 實作優先級: **高** (提升程式碼品質和測試性)
#### 預估工時: **2-3小時**

---

### 7. 前端依賴降低 (Frontend Dependency Reduction)

#### 問題分析
```javascript
// 現況：JavaScript 對後端資料格式有強依賴
const existingRanges = JSON.parse(container.dataset.existingRanges);
// 期望格式: [{"start_time":"09:00","end_time":"12:00"}]

const timeOptions = JSON.parse(container.dataset.timeOptions);
// 期望格式: {"09:00":"9:00 AM", "09:30":"9:30 AM"}
```

#### 建議重構

**後端標準化 API Response Format**
```php
// app/Http/Resources/AvailabilityResource.php
class AvailabilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'ranges' => $this->ranges->map(fn($range) => [
                'start_time' => [
                    'value' => $range->start_time,
                    'display' => Carbon::parse($range->start_time)->format('g:i A')
                ],
                'end_time' => [
                    'value' => $range->end_time,
                    'display' => Carbon::parse($range->end_time)->format('g:i A')
                ]
            ]),
            'time_options' => $this->time_options->map(fn($option) => [
                'value' => $option->value,
                'display' => $option->display
            ])
        ];
    }
}
```

**前端標準化資料處理**
```javascript
// resources/js/services/AvailabilityService.js
class AvailabilityService {
    constructor() {
        this.apiClient = new ApiClient();
    }
    
    async getAvailabilityData(eventHash, participantId) {
        const response = await this.apiClient.get(
            `/api/events/${eventHash}/participants/${participantId}/availability`
        );
        
        return this.normalizeResponse(response.data);
    }
    
    normalizeResponse(data) {
        return {
            date: data.date,
            existingRanges: data.ranges || [],
            timeOptions: data.time_options || []
        };
    }
    
    renderTimeRangeSelectors(data) {
        // 使用標準化後的資料結構
        data.existingRanges.forEach((range, index) => {
            this.createTimeRangeSelector({
                index,
                startTime: range.start_time.value,
                endTime: range.end_time.value,
                timeOptions: data.timeOptions
            });
        });
    }
}
```

**API 路由**
```php
// routes/api.php
Route::prefix('events/{event:hash}/participants/{participant}')->group(function () {
    Route::get('/availability', [ParticipantAvailabilityApiController::class, 'show']);
});
```

#### 實作優先級: **中** (提升前後端分離度)
#### 預估工時: **5-7小時**

---

## 🚀 實作計劃與時程

### Phase 1: 核心架構改善 (第1-2週)
1. **驗證邏輯解耦** (2-3小時) - 立即改善程式碼品質
2. **控制器職責分離** (4-6小時) - 提升維護性

### Phase 2: 資料處理優化 (第3週)
3. **資料轉換邏輯抽取** (3-4小時) - 提升重用性

### Phase 3: 前後端優化 (第4週)
4. **前端依賴降低** (5-7小時) - 提升系統彈性

## 📊 重構效益評估

| 項目 | 現況問題 | 重構後效益 | 影響範圍 |
|------|----------|------------|----------|
| 控制器職責分離 | 單一方法處理多種操作 | 更好的測試性、維護性 | Controller + Routes |
| 驗證邏輯解耦 | 驗證邏輯散落在Controller | 可重用、易測試的驗證 | Requests + Controller |
| 資料轉換抽取 | 格式化邏輯重複 | 統一的資料處理邏輯 | Services + Controller |
| 前端依賴降低 | 強耦合資料格式 | API標準化、前後端分離 | API + JavaScript |

## ⚠️ 風險評估與緩解

### 高風險項目
- **前端依賴降低**: 涉及前後端資料結構變更
  - *緩解*: 採用漸進式重構，保持向後相容性

### 中風險項目  
- **控制器職責分離**: 涉及路由和方法重構
  - *緩解*: 完整的測試覆蓋，分步驟實作

### 低風險項目
- **驗證邏輯解耦**: 相對獨立的重構
- **資料轉換抽取**: 不影響外部介面

## 🧪 測試策略

### 重構前
1. 建立完整的功能測試覆蓋
2. 建立效能基準測試
3. 記錄現有API合約

### 重構中  
1. 採用TDD方式逐步重構
2. 保持測試綠燈狀態
3. 每個重構點都有獨立的測試

### 重構後
1. 驗證功能完整性
2. 確認效能無退化
3. 更新技術文檔

## 📝 結論

這個重構計劃將系統從「功能導向」轉向「架構導向」，提升程式碼的：

- **可維護性**: 單一職責、清晰分層
- **可測試性**: 解耦合、可注入依賴  
- **可擴展性**: 標準化介面、模組化設計
- **團隊協作**: 清晰的程式碼結構和責任分工

建議按照優先級逐步實施，每個階段完成後都能看到明顯的程式碼品質提升。