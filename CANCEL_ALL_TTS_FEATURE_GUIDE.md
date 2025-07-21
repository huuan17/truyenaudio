# 🛑 Cancel All TTS Feature - Hủy toàn bộ TTS

## 📋 Tổng quan

Tính năng "Cancel All TTS" cho phép admin hủy toàn bộ các yêu cầu TTS đang chờ và đang thực hiện cho các chapters đã chọn. Tính năng này tích hợp với bulk actions và chỉ hiển thị khi có TTS đang hoạt động.

## ✨ Tính năng chính

### 🎯 **Smart Button Display**
- **Conditional Visibility**: Chỉ hiển thị khi có TTS đang chạy
- **Dynamic Counter**: Hiển thị số lượng chapters được chọn
- **Real-time Detection**: Tự động phát hiện TTS status
- **Context Aware**: Biết được chapters nào đang có TTS active

### 🛑 **Comprehensive Cancellation**
- **Bulk Task Cancellation**: Hủy các bulk TTS tasks đang chạy
- **Individual Chapter Reset**: Reset trạng thái TTS của từng chapter
- **Queue Cleanup**: Xóa các jobs đang chờ trong queue
- **Status Synchronization**: Đồng bộ trạng thái across toàn bộ system

### 🔄 **Real-time Integration**
- **Progress Monitoring Stop**: Dừng real-time monitoring
- **UI State Reset**: Reset progress bars và indicators
- **Automatic Refresh**: Tự động refresh page sau khi hoàn thành
- **Notification System**: Thông báo chi tiết kết quả

## 🏗️ Technical Implementation

### 1. **Frontend Detection Logic**

```javascript
function checkTtsStatusForSelectedChapters(selectedCount) {
    const selectedIds = getSelectedChapterIds();
    const cancelAllTtsBtn = document.getElementById('cancelAllTtsBtn');
    let hasActiveTts = false;
    
    // Check individual chapter status
    selectedIds.forEach(chapterId => {
        const statusContainer = document.querySelector(`[data-chapter-id="${chapterId}"]`);
        if (statusContainer) {
            const statusText = statusContainer.textContent.toLowerCase();
            if (statusText.includes('chờ tts') || 
                statusText.includes('đang xử lý') || 
                statusContainer.querySelector('.spinner-border') ||
                statusContainer.querySelector('.progress-bar')) {
                hasActiveTts = true;
            }
        }
    });
    
    // Show/hide button based on status
    if (cancelAllTtsBtn) {
        if (hasActiveTts) {
            cancelAllTtsBtn.style.display = 'inline-block';
            cancelAllTtsBtn.innerHTML = `<i class="fas fa-stop me-1"></i>Hủy TTS đang chạy (${selectedCount})`;
        } else {
            cancelAllTtsBtn.style.display = 'none';
        }
    }
}
```

### 2. **Backend Cancellation Logic**

```php
// app/Http/Controllers/Admin/ChapterController.php
public function cancelAllTts(Request $request)
{
    // 1. Validate input
    $request->validate([
        'chapter_ids' => 'required|array|min:1',
        'chapter_ids.*' => 'exists:chapters,id',
        'story_id' => 'required|exists:stories,id'
    ]);

    // 2. Cancel active bulk TTS tasks
    $activeBulkTasks = BulkTtsTask::where('story_id', $storyId)
                                  ->where('user_id', $userId)
                                  ->active()
                                  ->get();

    foreach ($activeBulkTasks as $task) {
        $taskChapterIds = $task->chapter_ids ?? [];
        $hasSelectedChapters = !empty(array_intersect($taskChapterIds, $chapterIds));

        if ($hasSelectedChapters) {
            $task->cancel();
            $cancelledTasks[] = $task->id;
        }
    }

    // 3. Reset individual chapter statuses
    foreach ($chapters as $chapter) {
        if (in_array($chapter->audio_status, ['pending', 'processing'])) {
            $chapter->update([
                'audio_status' => 'pending',
                'tts_progress' => 0,
                'tts_error' => null,
                'tts_started_at' => null,
                'tts_completed_at' => null
            ]);
            $cancelledCount++;
        }
    }

    // 4. Clear pending TTS jobs from queue
    $this->clearPendingTtsJobs($chapterIds);

    return response()->json([
        'success' => true,
        'message' => "Đã hủy TTS cho {$cancelledCount} chương.",
        'cancelled_count' => $cancelledCount,
        'cancelled_tasks' => $cancelledTasks
    ]);
}
```

### 3. **Queue Cleanup**

```php
private function clearPendingTtsJobs($chapterIds)
{
    try {
        // If using database queue, delete pending jobs
        if (config('queue.default') === 'database') {
            DB::table('jobs')
                ->where('queue', 'tts')
                ->where('payload', 'LIKE', '%ProcessBulkTtsJob%')
                ->delete();
            
            Log::info('Cleared pending TTS jobs from database queue');
        }
    } catch (Exception $e) {
        Log::warning('Failed to clear pending TTS jobs: ' . $e->getMessage());
    }
}
```

### 4. **Status Summary API**

```php
public function getTtsStatusSummary(Request $request, $storyId)
{
    // Get active bulk tasks
    $activeTasks = BulkTtsTask::where('story_id', $storyId)->active()->count();

    // Get chapters by status
    $statusCounts = Chapter::where('story_id', $storyId)
                          ->selectRaw('audio_status, COUNT(*) as count')
                          ->groupBy('audio_status')
                          ->pluck('count', 'audio_status')
                          ->toArray();

    return response()->json([
        'success' => true,
        'summary' => [
            'active_tasks' => $activeTasks,
            'processing_chapters' => $statusCounts['processing'] ?? 0,
            'pending_chapters' => $statusCounts['pending'] ?? 0,
            'has_active_tts' => $activeTasks > 0 || ($statusCounts['processing'] ?? 0) > 0
        ]
    ]);
}
```

## 🎨 UI/UX Design

### 1. **Button States**

```html
<!-- Hidden by default -->
<button type="button" class="btn btn-sm btn-warning" id="cancelAllTtsBtn" style="display: none;">
    <i class="fas fa-stop me-1"></i>Hủy TTS đang chạy
</button>

<!-- Visible when TTS active -->
<button type="button" class="btn btn-sm btn-warning" id="cancelAllTtsBtn">
    <i class="fas fa-stop me-1"></i>Hủy TTS đang chạy (5)
</button>

<!-- Loading state -->
<button type="button" class="btn btn-sm btn-warning" disabled>
    <i class="fas fa-spinner fa-spin me-1"></i>Đang hủy...
</button>
```

### 2. **Detection Indicators**

```javascript
// Visual indicators that trigger button display
const indicators = [
    'chờ tts',                    // Text content
    'đang xử lý',                 // Text content  
    '.spinner-border',            // Loading spinner
    '.progress-bar',              // Progress bar
    '.progress-bar-animated'      // Animated progress
];
```

### 3. **Confirmation Dialog**

```javascript
const confirmMessage = `
Bạn có chắc chắn muốn HỦY TTS cho ${selectedIds.length} chương đã chọn?

Thao tác này sẽ:
- Hủy các bulk TTS task đang chạy
- Reset trạng thái TTS của các chapter
- Xóa các job đang chờ trong queue
`;

if (!confirm(confirmMessage)) {
    return;
}
```

## 🔄 Operation Flow

### 1. **Detection Flow**
```
User selects chapters → updateBulkActions() → 
checkTtsStatusForSelectedChapters() → 
Check individual chapter status → 
Check bulk task status → 
Show/hide cancel button
```

### 2. **Cancellation Flow**
```
User clicks Cancel All → Confirmation dialog → 
Send AJAX request → Cancel bulk tasks → 
Reset chapter statuses → Clear queue jobs → 
Stop monitoring → Refresh page
```

### 3. **Status Check Flow**
```
For each selected chapter:
- Check text content for status keywords
- Check for spinner elements
- Check for progress bars
- Check for animated elements

API call to get bulk task summary:
- Active tasks count
- Processing chapters count
- Overall TTS activity status
```

## 🛡️ Safety Features

### 1. **Confirmation Dialog**
```javascript
// Multi-line confirmation with detailed explanation
const confirmMessage = `
Bạn có chắc chắn muốn HỦY TTS cho ${selectedIds.length} chương đã chọn?

Thao tác này sẽ:
- Hủy các bulk TTS task đang chạy
- Reset trạng thái TTS của các chapter  
- Xóa các job đang chờ trong queue
`;
```

### 2. **Selective Cancellation**
```php
// Only cancel chapters that are pending or processing
if (in_array($currentStatus, ['pending', 'processing'])) {
    $chapter->update(['audio_status' => 'pending']);
    $cancelledCount++;
} else {
    $skippedCount++;
}
```

### 3. **Task Validation**
```php
// Only cancel tasks that contain selected chapters
$taskChapterIds = $task->chapter_ids ?? [];
$hasSelectedChapters = !empty(array_intersect($taskChapterIds, $chapterIds));

if ($hasSelectedChapters) {
    $task->cancel();
}
```

### 4. **Error Handling**
```php
try {
    // Cancellation logic
} catch (Exception $e) {
    Log::error('Cancel all TTS failed: ' . $e->getMessage());
    return response()->json([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ], 500);
}
```

## 📊 Status Detection Matrix

| Chapter Status | Visual Indicator | Detection Method | Action |
|---------------|------------------|------------------|---------|
| **Chờ TTS** | Yellow badge "Chờ TTS" | Text content check | ✅ Show cancel |
| **Đang xử lý** | Spinner + Progress bar | Element check | ✅ Show cancel |
| **Hoàn thành** | Green badge "Có audio" | Text content check | ❌ Hide cancel |
| **Thất bại** | Red badge "Thất bại" | Text content check | ❌ Hide cancel |
| **Bulk Task Active** | Progress container | API status check | ✅ Show cancel |

## 🎯 User Experience

### 1. **Smart Visibility**
```
No TTS active → Button hidden
TTS detected → Button appears with counter
User clicks → Confirmation dialog
Confirmed → Loading state with progress
Completed → Success notification + refresh
```

### 2. **Visual Feedback**
```javascript
// Loading state
bulkActionsBar.innerHTML = `
    <div class="row align-items-center">
        <div class="col-12 text-center">
            <i class="fas fa-spinner fa-spin me-2"></i>
            Đang hủy TTS cho ${selectedIds.length} chương...
        </div>
    </div>
`;
```

### 3. **Result Notification**
```javascript
// Success message with details
alert(`Đã hủy TTS cho ${response.cancelled_count} chương. 
${response.skipped_count} chương đã hoàn thành nên không thể hủy.
Đã hủy ${response.cancelled_tasks.length} bulk task.`);
```

## 🔗 API Endpoints

```
POST /admin/chapters/cancel-all-tts     # Cancel all TTS for selected chapters
GET  /admin/stories/{story}/tts-status-summary  # Get TTS status summary
```

## 📈 Benefits Achieved

### ✅ **User Control**
- **Immediate Cancellation**: Hủy TTS ngay lập tức
- **Selective Control**: Chỉ hủy chapters được chọn
- **Bulk Operation**: Hủy nhiều chapters cùng lúc
- **Smart Detection**: Tự động phát hiện TTS active

### ✅ **System Safety**
- **Resource Protection**: Giải phóng resources đang sử dụng
- **Queue Management**: Cleanup pending jobs
- **State Consistency**: Đồng bộ trạng thái across system
- **Error Prevention**: Tránh conflicts và inconsistencies

### ✅ **Operational Efficiency**
- **Quick Recovery**: Nhanh chóng recover từ TTS issues
- **Batch Control**: Quản lý hàng loạt operations
- **Real-time Response**: Phản hồi ngay lập tức
- **Clean Shutdown**: Graceful termination of processes

### ✅ **Developer Experience**
- **Comprehensive Logging**: Chi tiết logs cho debugging
- **Error Handling**: Robust error management
- **API Consistency**: Consistent response format
- **Extensible Design**: Dễ extend cho future features

## 🎮 Usage Examples

### 1. **Scenario: TTS bị stuck**
```
User notices TTS stuck → Selects affected chapters → 
Cancel All TTS button appears → Click to cancel → 
All stuck processes terminated → Clean state restored
```

### 2. **Scenario: Wrong chapters selected**
```
User starts TTS for wrong chapters → Realizes mistake → 
Selects chapters → Cancel All TTS → 
Processes stopped → Re-select correct chapters
```

### 3. **Scenario: System maintenance**
```
Admin needs to restart system → Select all processing chapters → 
Cancel All TTS → Wait for clean shutdown → 
Perform maintenance → Restart TTS later
```

**Cancel All TTS Feature đã sẵn sàng cung cấp control toàn diện cho TTS operations! 🛑✨**
