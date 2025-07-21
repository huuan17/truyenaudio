# 🧭 Breadcrumb Navigation & Individual TTS Cancel

## 📋 Tổng quan

Tôi đã thêm hai tính năng quan trọng vào giao diện danh sách chapters:
1. **Breadcrumb Navigation** - Điều hướng tiện lợi giữa các trang
2. **Individual Chapter TTS Cancel** - Hủy TTS cho từng chapter riêng lẻ

## ✨ Tính năng mới

### 🧭 **Breadcrumb Navigation**
- **Hierarchical Navigation**: Dashboard → Quản lý Truyện → Story Detail → Chapters
- **Interactive Links**: Click để điều hướng nhanh
- **Visual Feedback**: Hover effects và active states
- **Chapter Count**: Hiển thị tổng số chapters
- **Responsive Design**: Tối ưu cho mobile

### 🛑 **Individual Chapter TTS Cancel**
- **Smart Button States**: TTS button tự động chuyển thành Cancel button
- **Queue Management**: Gỡ bỏ chapter khỏi hàng đợi TTS
- **Bulk Task Integration**: Tự động cập nhật bulk tasks
- **Real-time Updates**: Cập nhật UI ngay lập tức
- **Safety Confirmation**: Xác nhận trước khi hủy

## 🏗️ Technical Implementation

### 1. **Breadcrumb Structure**

```html
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-light p-3 rounded shadow-sm">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.dashboard') }}">
                <i class="fas fa-home me-1"></i>Dashboard
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('admin.stories.index') }}">
                <i class="fas fa-book me-1"></i>Quản lý Truyện
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('admin.stories.show', $story->slug) }}">
                <i class="fas fa-info-circle me-1"></i>{{ Str::limit($story->title, 40) }}
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            <i class="fas fa-list me-1"></i>Danh sách chương
            <span class="badge badge-secondary ms-2">{{ $chapters->total() }} chương</span>
        </li>
    </ol>
</nav>
```

### 2. **Dynamic TTS Action Buttons**

```php
// app/Models/Chapter.php
public function canCancelTts()
{
    return in_array($this->audio_status, ['pending', 'processing']);
}

public function getTtsActionButtonAttribute()
{
    if ($this->canCancelTts()) {
        return '
            <button class="btn btn-sm btn-warning cancel-chapter-tts-btn" 
                    data-chapter-id="' . $this->id . '">
                <i class="fas fa-stop"></i>
            </button>';
    } else {
        return '
            <button class="btn btn-sm btn-success start-chapter-tts-btn">
                <i class="fas fa-volume-up"></i>
            </button>';
    }
}
```

### 3. **Individual Cancel Logic**

```php
// app/Http/Controllers/Admin/ChapterController.php
public function cancelChapterTts(Request $request, $chapterId)
{
    $chapter = Chapter::findOrFail($chapterId);
    
    // 1. Find and update bulk tasks containing this chapter
    $bulkTasks = BulkTtsTask::where('story_id', $chapter->story_id)->active()->get();
    
    foreach ($bulkTasks as $task) {
        $taskChapterIds = $task->chapter_ids ?? [];
        if (in_array($chapter->id, $taskChapterIds)) {
            $remainingChapterIds = array_diff($taskChapterIds, [$chapter->id]);
            
            if (empty($remainingChapterIds)) {
                // Cancel entire task if this was the only chapter
                $task->cancel();
            } else {
                // Remove this chapter from task
                $task->update([
                    'chapter_ids' => $remainingChapterIds,
                    'total_chapters' => count($remainingChapterIds)
                ]);
            }
        }
    }
    
    // 2. Reset chapter status
    $chapter->update([
        'audio_status' => 'pending',
        'tts_progress' => 0,
        'tts_error' => null
    ]);
    
    // 3. Clear specific jobs for this chapter
    $this->clearChapterTtsJobs($chapter->id);
    
    return response()->json([
        'success' => true,
        'message' => "Đã hủy TTS cho chương {$chapter->chapter_number}."
    ]);
}
```

### 4. **Frontend Button Management**

```javascript
function initializeChapterTtsButtons() {
    // Cancel individual chapter TTS buttons
    document.querySelectorAll('.cancel-chapter-tts-btn').forEach(button => {
        button.addEventListener('click', function() {
            const chapterId = this.getAttribute('data-chapter-id');
            const chapterTitle = this.getAttribute('data-chapter-title');
            cancelIndividualChapterTts(chapterId, chapterTitle, this);
        });
    });
}

function cancelIndividualChapterTts(chapterId, chapterTitle, buttonElement) {
    if (!confirm(`Bạn có chắc chắn muốn HỦY TTS cho chương: "${chapterTitle}"?`)) {
        return;
    }

    // Show loading state
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    $.ajax({
        url: `/admin/chapters/${chapterId}/cancel-tts`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                // Update button to TTS button
                buttonElement.className = 'btn btn-sm btn-success start-chapter-tts-btn';
                buttonElement.innerHTML = '<i class="fas fa-volume-up"></i>';
                buttonElement.disabled = false;
                
                // Update status display
                updateChapterTtsStatusDisplay(chapterId, 'pending');
                
                // Re-initialize event listeners
                initializeChapterTtsButtons();
            }
        }
    });
}
```

## 🎨 UI/UX Design

### 1. **Breadcrumb Visual Design**

```css
.breadcrumb {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
    font-weight: bold;
}

.breadcrumb-item a:hover {
    color: #0056b3 !important;
    transform: translateY(-1px);
}
```

### 2. **Button State Transitions**

```css
.cancel-chapter-tts-btn {
    animation: pulse-warning 2s infinite;
}

@keyframes pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

.btn-group .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
```

### 3. **Button State Matrix**

| Chapter Status | Button State | Button Color | Icon | Action |
|---------------|--------------|--------------|------|---------|
| **Pending (no audio)** | Start TTS | 🟢 Success | 🔊 volume-up | Open TTS modal |
| **Pending (has audio)** | Start TTS | 🟢 Success | 🔊 volume-up | Open TTS modal |
| **Processing** | Cancel TTS | 🟡 Warning | ⏹️ stop | Cancel individual |
| **Completed** | Start TTS | 🟢 Success | 🔊 volume-up | Re-run TTS |
| **Failed** | Start TTS | 🟢 Success | 🔊 volume-up | Retry TTS |
| **No Content** | Disabled | ⚫ Secondary | 🔇 volume-mute | No action |

## 🔄 Operation Flow

### 1. **Breadcrumb Navigation Flow**
```
User on Chapters page → Click breadcrumb item → 
Navigate to selected page → Maintain context
```

### 2. **Individual Cancel Flow**
```
User sees Cancel button → Click to cancel → 
Confirmation dialog → AJAX request → 
Update bulk tasks → Reset chapter status → 
Clear queue jobs → Update UI → Re-initialize buttons
```

### 3. **Button State Flow**
```
Chapter created (pending) → Start TTS button → 
User starts TTS → Button becomes Cancel → 
User cancels OR TTS completes → Button becomes Start TTS
```

## 🛡️ Safety Features

### 1. **Confirmation Dialog**
```javascript
const confirmMessage = `
Bạn có chắc chắn muốn HỦY TTS cho chương:
"${chapterTitle}"?

Thao tác này sẽ:
- Gỡ bỏ chapter khỏi hàng đợi TTS
- Reset trạng thái TTS của chapter
- Hủy bulk task nếu chỉ có chapter này
`;
```

### 2. **Permission Checking**
```php
// Check permission
if (!auth()->user()->isAdmin() && $chapter->story->user_id !== $userId) {
    return response()->json([
        'success' => false,
        'message' => 'Bạn không có quyền thao tác với chapter này.'
    ], 403);
}
```

### 3. **Status Validation**
```php
// Only allow cancellation for pending or processing chapters
if (!in_array($currentStatus, ['pending', 'processing'])) {
    return response()->json([
        'success' => false,
        'message' => 'Chapter này không thể hủy TTS vì đã hoàn thành hoặc thất bại.'
    ], 400);
}
```

### 4. **Graceful Error Handling**
```javascript
error: function(xhr, status, error) {
    let errorMessage = 'Lỗi khi hủy TTS: ' + error;
    if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
    }
    showNotification(errorMessage, 'error');
    buttonElement.disabled = false;
    buttonElement.innerHTML = originalHtml;
}
```

## 📱 Responsive Design

### 1. **Mobile Breadcrumb**
```css
@media (max-width: 768px) {
    .breadcrumb {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .breadcrumb-item {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
```

### 2. **Mobile Button Groups**
```css
@media (max-width: 768px) {
    .btn-group .btn {
        margin-bottom: 2px;
        min-width: 40px;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
}
```

## 🎯 User Experience

### 1. **Navigation Experience**
```
Dashboard → Stories → Story Detail → Chapters
    ↑         ↑           ↑            ↑
  Home     List view   Overview    Chapter list
```

### 2. **TTS Management Experience**
```
Chapter with no TTS → Green TTS button → Click → Modal opens
Chapter in queue → Yellow Cancel button → Click → Confirmation → Cancelled
Chapter processing → Yellow Cancel button → Click → Stops processing
```

### 3. **Visual Feedback**
```
Button hover → Lift animation
Cancel button → Pulse animation
Loading state → Spinner icon
Success → Notification + button change
Error → Error notification + button restore
```

## 🔗 API Endpoints

```
POST /admin/chapters/{chapter}/cancel-tts    # Cancel individual chapter TTS
GET  /admin/stories/{story}/chapters         # Chapter list with breadcrumb
```

## 📊 Benefits Achieved

### ✅ **Navigation Improvements**
- **Quick Access**: One-click navigation to any level
- **Context Awareness**: Always know current location
- **Visual Hierarchy**: Clear parent-child relationships
- **Mobile Friendly**: Responsive breadcrumb design

### ✅ **TTS Control Enhancements**
- **Granular Control**: Cancel individual chapters
- **Smart UI**: Buttons adapt to chapter status
- **Queue Integration**: Seamless bulk task management
- **Real-time Updates**: Immediate visual feedback

### ✅ **User Experience**
- **Intuitive Interface**: Clear visual cues
- **Safety First**: Confirmation dialogs
- **Error Handling**: Graceful error recovery
- **Performance**: Efficient AJAX operations

### ✅ **System Integration**
- **Bulk Task Sync**: Automatic bulk task updates
- **Queue Management**: Clean job removal
- **Status Consistency**: Synchronized across UI
- **Event Handling**: Proper event listener management

## 🎮 Usage Examples

### 1. **Navigation Scenario**
```
User on Chapters page → Wants to go back to Stories → 
Click "Quản lý Truyện" in breadcrumb → 
Instantly navigate to stories list
```

### 2. **Individual Cancel Scenario**
```
User starts bulk TTS → Realizes one chapter is wrong → 
Click Cancel button on that chapter → 
Confirm cancellation → Chapter removed from queue → 
Bulk task continues with remaining chapters
```

### 3. **Mobile Usage**
```
User on mobile → Breadcrumb adapts to small screen → 
TTS buttons stack vertically → 
Touch-friendly targets → Smooth interactions
```

**Breadcrumb Navigation và Individual TTS Cancel đã sẵn sàng cung cấp trải nghiệm điều hướng và control tốt nhất! 🧭🛑✨**
