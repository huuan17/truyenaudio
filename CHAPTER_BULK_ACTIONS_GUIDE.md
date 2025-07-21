# 📋 Chapter Bulk Actions Guide

## 📋 Tổng quan

Chapter Bulk Actions cho phép admin thực hiện các thao tác hàng loạt trên nhiều chapters cùng lúc, bao gồm TTS conversion và xóa chapters, giúp tiết kiệm thời gian và tăng hiệu quả quản lý.

## ✨ Tính năng chính

### 🎯 **Selection System**
- **Select All Checkbox**: Chọn/bỏ chọn tất cả chapters
- **Individual Checkboxes**: Chọn từng chapter riêng lẻ
- **Indeterminate State**: Hiển thị trạng thái một phần khi chọn một số chapters
- **Visual Feedback**: Highlight rows đã chọn với màu xanh

### 🔧 **Bulk Operations**
- **Bulk TTS**: Chuyển đổi TTS cho nhiều chapters cùng lúc
- **Bulk Delete**: Xóa nhiều chapters và files liên quan
- **Smart Validation**: Kiểm tra điều kiện trước khi thực hiện
- **Progress Feedback**: Hiển thị tiến trình và kết quả

### 🎨 **User Interface**
- **Dynamic Action Bar**: Hiện/ẩn tự động khi có selection
- **Selected Counter**: Hiển thị số lượng chapters đã chọn
- **Responsive Design**: Tối ưu cho mobile và desktop
- **Loading States**: Visual feedback trong quá trình xử lý

## 🎯 User Experience Flow

### 1. **Selection Flow:**
```
Page Load → No Selection → Hidden Action Bar
     ↓
Select Chapters → Show Action Bar → Display Count
     ↓
Select All → All Checked → Update Counter
     ↓
Partial Selection → Indeterminate State → Show Count
```

### 2. **Bulk TTS Flow:**
```
Select Chapters → Click TTS Button → Confirmation Dialog
     ↓                    ↓                    ↓
Validate Selection → Show Loading → Process Chapters
     ↓                    ↓                    ↓
Check Content → Update Status → Show Results
```

### 3. **Bulk Delete Flow:**
```
Select Chapters → Click Delete → Warning Dialog
     ↓                    ↓                    ↓
Double Confirmation → Show Loading → Delete Files
     ↓                    ↓                    ↓
Safety Check → Remove Records → Show Results
```

## 🏗️ Technical Implementation

### 1. **Frontend Structure**

```html
<!-- Bulk Actions Bar (Hidden by default) -->
<div class="card-body border-bottom" id="bulkActionsBar" style="display: none;">
    <div class="row align-items-center">
        <div class="col-md-6">
            <span class="text-muted">
                <i class="fas fa-check-square me-2"></i>
                Đã chọn <strong id="selectedCount">0</strong> chương
            </span>
        </div>
        <div class="col-md-6 text-right">
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-success" onclick="bulkTTS()">
                    <i class="fas fa-volume-up me-1"></i>TTS hàng loạt
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()">
                    <i class="fas fa-trash me-1"></i>Xóa đã chọn
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                    <i class="fas fa-times me-1"></i>Bỏ chọn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table with Checkboxes -->
<table class="table table-striped">
    <thead>
        <tr>
            <th width="40">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label class="form-check-label" for="selectAll"></label>
                </div>
            </th>
            <th>Chương</th>
            <th>Tiêu đề</th>
            <!-- Other columns -->
        </tr>
    </thead>
    <tbody>
        @foreach($chapters as $chapter)
        <tr>
            <td>
                <div class="form-check">
                    <input class="form-check-input chapter-checkbox" type="checkbox" 
                           value="{{ $chapter->id }}" id="chapter_{{ $chapter->id }}" 
                           onchange="updateBulkActions()">
                    <label class="form-check-label" for="chapter_{{ $chapter->id }}"></label>
                </div>
            </td>
            <td>{{ $chapter->chapter_number }}</td>
            <!-- Other columns -->
        </tr>
        @endforeach
    </tbody>
</table>
```

### 2. **JavaScript Core Functions**

```javascript
// Selection Management
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const chapterCheckboxes = document.querySelectorAll('.chapter-checkbox');
    
    chapterCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.chapter-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    const totalCheckboxes = document.querySelectorAll('.chapter-checkbox').length;
    
    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectedCount === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedCount === totalCheckboxes) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
    
    // Show/hide bulk actions bar
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    
    if (selectedCount > 0) {
        bulkActionsBar.style.display = 'block';
        selectedCountSpan.textContent = selectedCount;
    } else {
        bulkActionsBar.style.display = 'none';
    }
}

// Bulk Operations
function bulkTTS() {
    const selectedIds = getSelectedChapterIds();
    
    if (selectedIds.length === 0) {
        alert('Vui lòng chọn ít nhất một chương để thực hiện TTS.');
        return;
    }

    if (!confirm(`Bạn có chắc chắn muốn thực hiện TTS cho ${selectedIds.length} chương đã chọn?`)) {
        return;
    }

    // Show loading state
    showBulkLoading(`Đang thực hiện TTS cho ${selectedIds.length} chương...`);

    // Send AJAX request
    $.ajax({
        url: '/admin/chapters/bulk-tts',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            chapter_ids: selectedIds,
            story_id: storyId
        },
        success: function(response) {
            if (response.success) {
                alert(`Đã bắt đầu TTS cho ${selectedIds.length} chương.`);
                location.reload();
            } else {
                alert('Lỗi: ' + (response.message || 'Không thể thực hiện TTS'));
                location.reload();
            }
        },
        error: function(xhr, status, error) {
            handleAjaxError(xhr, 'TTS');
        }
    });
}

function bulkDelete() {
    const selectedIds = getSelectedChapterIds();
    
    if (selectedIds.length === 0) {
        alert('Vui lòng chọn ít nhất một chương để xóa.');
        return;
    }

    // Double confirmation for safety
    if (!confirm(`⚠️ CẢNH BÁO: Bạn có chắc chắn muốn xóa ${selectedIds.length} chương đã chọn?\n\nHành động này sẽ xóa:\n- Nội dung chương\n- File audio (nếu có)\n- File video (nếu có)\n- Tất cả dữ liệu liên quan\n\nHành động này KHÔNG THỂ HOÀN TÁC!`)) {
        return;
    }

    if (!confirm(`Xác nhận lần cuối: XÓA ${selectedIds.length} CHƯƠNG?`)) {
        return;
    }

    // Show loading state
    showBulkLoading(`Đang xóa ${selectedIds.length} chương...`);

    // Send AJAX request
    $.ajax({
        url: '/admin/chapters/bulk-delete',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            chapter_ids: selectedIds,
            story_id: storyId
        },
        success: function(response) {
            if (response.success) {
                alert(`Đã xóa thành công ${response.deleted_count || selectedIds.length} chương.`);
                location.reload();
            } else {
                alert('Lỗi: ' + (response.message || 'Không thể xóa chapters'));
                location.reload();
            }
        },
        error: function(xhr, status, error) {
            handleAjaxError(xhr, 'xóa chapters');
        }
    });
}

// Helper Functions
function getSelectedChapterIds() {
    const selectedCheckboxes = document.querySelectorAll('.chapter-checkbox:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
}

function showBulkLoading(message) {
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    bulkActionsBar.innerHTML = `
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <i class="fas fa-spinner fa-spin me-2"></i>
                ${message}
            </div>
        </div>
    `;
    bulkActionsBar.className = 'card-body border-bottom bulk-loading';
}

function handleAjaxError(xhr, operation) {
    let errorMessage = `Có lỗi xảy ra khi ${operation}`;
    try {
        const response = JSON.parse(xhr.responseText);
        errorMessage = response.message || errorMessage;
    } catch(e) {
        errorMessage = xhr.statusText || errorMessage;
    }
    alert('Lỗi: ' + errorMessage);
    location.reload();
}
```

### 3. **Backend Implementation**

```php
// ChapterController.php

/**
 * Bulk TTS conversion for selected chapters
 */
public function bulkTts(Request $request)
{
    $request->validate([
        'chapter_ids' => 'required|array|min:1',
        'chapter_ids.*' => 'exists:chapters,id',
        'story_id' => 'required|exists:stories,id'
    ]);

    try {
        $chapterIds = $request->chapter_ids;
        $storyId = $request->story_id;
        
        // Verify all chapters belong to the specified story
        $chapters = Chapter::whereIn('id', $chapterIds)
                          ->where('story_id', $storyId)
                          ->get();

        if ($chapters->count() !== count($chapterIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Một số chapter không thuộc về story này hoặc không tồn tại.'
            ], 400);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($chapters as $chapter) {
            try {
                // Check if chapter has content
                if (empty($chapter->content)) {
                    $errors[] = "Chương {$chapter->chapter_number}: Không có nội dung";
                    $errorCount++;
                    continue;
                }

                // Update status to processing
                $chapter->update([
                    'audio_status' => 'processing',
                    'tts_started_at' => now()
                ]);

                // Dispatch TTS job
                \App\Jobs\ConvertChapterToTts::dispatch($chapter);
                
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Chương {$chapter->chapter_number}: {$e->getMessage()}";
                $errorCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã bắt đầu TTS cho {$successCount} chương.",
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Bulk delete selected chapters
 */
public function bulkDelete(Request $request)
{
    $request->validate([
        'chapter_ids' => 'required|array|min:1',
        'chapter_ids.*' => 'exists:chapters,id',
        'story_id' => 'required|exists:stories,id'
    ]);

    try {
        $chapterIds = $request->chapter_ids;
        $storyId = $request->story_id;
        
        // Verify all chapters belong to the specified story
        $chapters = Chapter::whereIn('id', $chapterIds)
                          ->where('story_id', $storyId)
                          ->get();

        $deletedCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($chapters as $chapter) {
            try {
                // Delete associated files
                $this->deleteChapterFiles($chapter);
                
                // Delete chapter record
                $chapter->delete();
                $deletedCount++;
                
            } catch (\Exception $e) {
                $errors[] = "Chương {$chapter->chapter_number}: {$e->getMessage()}";
                $errorCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã xóa thành công {$deletedCount} chương.",
            'deleted_count' => $deletedCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Delete files associated with a chapter
 */
private function deleteChapterFiles(Chapter $chapter)
{
    // Delete audio file
    if ($chapter->audio_file_path && file_exists($chapter->audio_file_path)) {
        unlink($chapter->audio_file_path);
    }

    // Delete text file
    if ($chapter->file_path && file_exists($chapter->file_path)) {
        unlink($chapter->file_path);
    }

    // Delete video files if any
    if ($chapter->video) {
        $videoPath = $chapter->video->file_path;
        if ($videoPath && file_exists($videoPath)) {
            unlink($videoPath);
        }
        $chapter->video->delete();
    }
}
```

### 4. **Routes Configuration**

```php
// routes/web.php

// Bulk chapter actions
Route::post('/chapters/bulk-tts', [ChapterController::class, 'bulkTts'])->name('chapters.bulk-tts');
Route::post('/chapters/bulk-delete', [ChapterController::class, 'bulkDelete'])->name('chapters.bulk-delete');
```

## 🎨 Visual Design

### 1. **Selection States**
```
☐ Unselected     (Default state)
☑️ Selected       (Blue checkbox)
◼️ Indeterminate  (Partial selection)
```

### 2. **Action Bar States**
```
Hidden State:     [No selection - Bar hidden]
Active State:     [📋 Đã chọn 3 chương] [TTS] [Delete] [Clear]
Loading State:    [⏳ Đang xử lý 3 chương...]
```

### 3. **Row Highlighting**
```
Normal Row:       [White background]
Selected Row:     [Blue background + left border]
Hover Row:        [Light gray background]
```

## 📱 Responsive Behavior

### 1. **Desktop Experience**
- Horizontal action bar layout
- Side-by-side buttons
- Full table width
- Hover effects

### 2. **Mobile Experience**
- Stacked button layout
- Vertical action bar
- Compact table
- Touch-friendly targets

### 3. **Tablet Experience**
- Hybrid layout
- Responsive button groups
- Optimized spacing
- Touch and mouse support

## 🔧 Configuration Options

### 1. **Validation Rules**
```php
$rules = [
    'chapter_ids' => 'required|array|min:1|max:100',  // Limit bulk size
    'chapter_ids.*' => 'exists:chapters,id',
    'story_id' => 'required|exists:stories,id'
];
```

### 2. **Safety Limits**
```php
const MAX_BULK_OPERATIONS = 100;  // Maximum chapters per operation
const CONFIRMATION_THRESHOLD = 10; // Double confirm for large operations
```

## 📊 Analytics & Tracking

### 1. **Usage Metrics**
```javascript
// Track bulk operations
analytics.track('bulk_operation', {
    operation: 'tts',           // 'tts' | 'delete'
    chapter_count: selectedIds.length,
    story_id: storyId,
    success_count: response.success_count,
    error_count: response.error_count
});
```

### 2. **Performance Metrics**
- Operation completion time
- Success/failure rates
- Error patterns
- User behavior patterns

## 🚀 Benefits Summary

### ✅ **For Admins**
- **Time Saving**: Process multiple chapters at once
- **Efficiency**: Reduce repetitive tasks
- **Safety**: Double confirmation for destructive actions
- **Feedback**: Clear progress and result reporting

### ✅ **For System**
- **Batch Processing**: Efficient resource utilization
- **Error Handling**: Graceful failure management
- **Validation**: Comprehensive input validation
- **Logging**: Detailed operation tracking

### ✅ **For Users**
- **Intuitive Interface**: Easy selection and operation
- **Visual Feedback**: Clear state indicators
- **Responsive Design**: Works on all devices
- **Safety Features**: Prevents accidental operations

## 🔗 URL Patterns & Route Keys

### **Important: Story Route Key**
The system uses **slug-based routing** for stories, not ID-based:

```php
// Story Model
public function getRouteKeyName()
{
    return 'slug';
}
```

### **Correct URL Patterns:**
```
✅ Correct:   /admin/stories/{slug}/chapters
✅ Example:   /admin/stories/tien-nghich/chapters

❌ Incorrect: /admin/stories/{id}/chapters
❌ Example:   /admin/stories/1/chapters
```

### **Route Definitions:**
```php
// Admin routes use model binding with slug
Route::get('/stories/{story}/chapters', [StoryController::class, 'chapters'])
     ->name('stories.chapters');

// Frontend routes use explicit slug parameter
Route::get('/story/{storySlug}/chapter/{chapterNumber}', [HomeController::class, 'chapter'])
     ->name('chapter.show');
```

### **Getting Story Slug:**
```php
// In Tinker or Controller
$story = \App\Models\Story::first();
echo $story->slug;  // e.g., "tien-nghich"

// Correct URL generation
route('admin.stories.chapters', $story);  // Uses slug automatically
```

### **Testing URLs:**
```bash
# Get story slug first
php artisan tinker --execute="echo \App\Models\Story::first()->slug;"

# Then use in URL
http://localhost:8000/admin/stories/{slug}/chapters
```

**Chapter Bulk Actions feature is ready for production! 📋✨**
