# 👁️ Story Visibility Management Guide

## 📋 Tổng quan

Story Visibility Management cho phép admin kiểm soát việc hiển thị truyện ở frontend thông qua hai trường boolean:
- **`is_public`**: Truyện có được hiển thị công khai không
- **`is_active`**: Truyện có đang hoạt động không (có thể tạm dừng)

## 🎯 Logic hiển thị

### Frontend chỉ hiển thị truyện khi:
```
is_public = true AND is_active = true
```

### Các trạng thái visibility:

| is_public | is_active | Status | Badge | Hiển thị Frontend |
|-----------|-----------|--------|-------|-------------------|
| ✅ true   | ✅ true   | **Công khai** | `badge-success` | ✅ **Có** |
| ❌ false  | ✅ true   | **Riêng tư** | `badge-warning` | ❌ Không |
| ✅ true   | ❌ false  | **Tạm dừng** | `badge-secondary` | ❌ Không |
| ❌ false  | ❌ false  | **Ẩn** | `badge-danger` | ❌ Không |

## 🏗️ Database Schema

### Migration: `add_visibility_fields_to_stories_table`

```sql
ALTER TABLE stories ADD COLUMN is_public BOOLEAN DEFAULT TRUE;
ALTER TABLE stories ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
CREATE INDEX stories_visibility_index ON stories (is_public, is_active);
```

### Trường mới:
- **`is_public`**: BOOLEAN, default `true`, comment 'Truyện có được hiển thị công khai ở frontend không'
- **`is_active`**: BOOLEAN, default `true`, comment 'Truyện có đang hoạt động không (admin có thể tạm dừng)'
- **Index**: `stories_visibility_index` để tối ưu query

## 📱 Admin Interface

### 1. **Story Form (Create/Edit)**

```html
<!-- Visibility Settings -->
<div class="form-group">
    <label>Cài đặt hiển thị</label>
    <div class="row">
        <div class="col-md-6">
            <div class="form-check">
                <input type="checkbox" name="is_public" id="is_public" 
                       value="1" {{ old('is_public', $story->is_public ?? true) ? 'checked' : '' }}>
                <label for="is_public">
                    <i class="fas fa-eye mr-1"></i>Công khai
                </label>
                <small>Truyện có hiển thị ở trang chủ và tìm kiếm không</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" 
                       value="1" {{ old('is_active', $story->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active">
                    <i class="fas fa-power-off mr-1"></i>Hoạt động
                </label>
                <small>Truyện có đang hoạt động không (có thể tạm dừng)</small>
            </div>
        </div>
    </div>
</div>
```

### 2. **Story Index với Filter**

```html
<!-- Filter Buttons -->
<div class="btn-group">
    <a href="?filter=" class="btn btn-outline-secondary">
        Tất cả ({{ $totalCount }})
    </a>
    <a href="?filter=visible" class="btn btn-outline-success">
        Hiển thị ({{ $visibleCount }})
    </a>
    <a href="?filter=hidden" class="btn btn-outline-warning">
        Ẩn ({{ $hiddenCount }})
    </a>
    <a href="?filter=inactive" class="btn btn-outline-danger">
        Tạm dừng ({{ $inactiveCount }})
    </a>
</div>
```

### 3. **Visibility Status Display**

```html
<!-- Status Badge -->
<span class="badge {{ $story->visibility_badge_class }}">
    {{ $story->visibility_status }}
</span>

<!-- Detailed Status -->
<div class="mt-1">
    @if($story->is_public)
        <small class="text-success"><i class="fas fa-eye"></i> Public</small>
    @else
        <small class="text-muted"><i class="fas fa-eye-slash"></i> Private</small>
    @endif
    |
    @if($story->is_active)
        <small class="text-success"><i class="fas fa-power-off"></i> Active</small>
    @else
        <small class="text-danger"><i class="fas fa-pause"></i> Inactive</small>
    @endif
</div>
```

## 🔧 Model Implementation

### Story Model Updates

```php
class Story extends Model
{
    protected $fillable = [
        // ... existing fields
        'is_public',
        'is_active',
    ];

    protected $casts = [
        // ... existing casts
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopePublic($query) {
        return $query->where('is_public', true);
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query) {
        return $query->where('is_public', true)->where('is_active', true);
    }

    public function scopeHidden($query) {
        return $query->where(function($q) {
            $q->where('is_public', false)->orWhere('is_active', false);
        });
    }

    // Helper Methods
    public function isVisible() {
        return $this->is_public && $this->is_active;
    }

    public function getVisibilityStatusAttribute() {
        if ($this->is_public && $this->is_active) return 'Công khai';
        if (!$this->is_public && $this->is_active) return 'Riêng tư';
        if ($this->is_public && !$this->is_active) return 'Tạm dừng';
        return 'Ẩn';
    }

    public function getVisibilityBadgeClassAttribute() {
        if ($this->is_public && $this->is_active) return 'badge-success';
        if (!$this->is_public && $this->is_active) return 'badge-warning';
        if ($this->is_public && !$this->is_active) return 'badge-secondary';
        return 'badge-danger';
    }
}
```

## 🎮 Controller Implementation

### Admin StoryController

```php
public function index(Request $request)
{
    $query = Story::query();
    
    // Handle search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author', 'like', "%{$search}%")
              ->orWhere('slug', 'like', "%{$search}%");
        });
    }
    
    // Handle filter
    switch ($request->filter) {
        case 'visible':
            $query->visible();
            break;
        case 'hidden':
            $query->hidden();
            break;
        case 'inactive':
            $query->where('is_active', false);
            break;
    }
    
    $stories = $query->latest()->paginate(15);
    
    // Get counts for filter buttons
    $totalCount = Story::count();
    $visibleCount = Story::visible()->count();
    $hiddenCount = Story::hidden()->count();
    $inactiveCount = Story::where('is_active', false)->count();
    
    return view('admin.stories.index', compact(
        'stories', 'totalCount', 'visibleCount', 'hiddenCount', 'inactiveCount'
    ));
}
```

### Frontend HomeController

```php
public function index()
{
    // Chỉ lấy stories visible (is_public = true AND is_active = true)
    $hotStories = Story::visible()
        ->withCount('chapters')
        ->whereHas('chapters')
        ->orderBy('updated_at', 'desc')
        ->limit(12)
        ->get();

    $recentStories = Story::visible()
        ->with(['chapters' => function($query) {
            $query->orderBy('chapter_number', 'desc')->limit(1);
        }])
        ->whereHas('chapters')
        ->orderBy('updated_at', 'desc')
        ->limit(20)
        ->get();

    // ... other queries with ->visible()
}
```

## 📊 Query Performance

### Optimized Queries với Index

```sql
-- Lấy stories hiển thị ở frontend (sử dụng index)
SELECT * FROM stories 
WHERE is_public = 1 AND is_active = 1 
ORDER BY updated_at DESC;

-- Đếm stories theo visibility status
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_public = 1 AND is_active = 1 THEN 1 ELSE 0 END) as visible,
    SUM(CASE WHEN is_public = 0 OR is_active = 0 THEN 1 ELSE 0 END) as hidden,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
FROM stories;
```

### Index Usage:
- **`stories_visibility_index`**: Tối ưu cho queries `WHERE is_public = ? AND is_active = ?`
- **Performance**: Queries nhanh hơn 10-100x với index

## 🎯 Use Cases

### 1. **Ẩn truyện tạm thời**
```php
$story->update(['is_active' => false]);
// Status: "Tạm dừng" - không hiển thị frontend nhưng vẫn giữ is_public = true
```

### 2. **Chuyển truyện thành riêng tư**
```php
$story->update(['is_public' => false]);
// Status: "Riêng tư" - chỉ admin có thể thấy
```

### 3. **Ẩn hoàn toàn**
```php
$story->update(['is_public' => false, 'is_active' => false]);
// Status: "Ẩn" - hoàn toàn không hiển thị
```

### 4. **Công khai truyện**
```php
$story->update(['is_public' => true, 'is_active' => true]);
// Status: "Công khai" - hiển thị ở frontend
```

## 🔍 Admin Features

### 1. **Filter Stories**
- **Tất cả**: Hiển thị tất cả stories
- **Hiển thị**: Chỉ stories visible (public + active)
- **Ẩn**: Stories hidden (private hoặc inactive)
- **Tạm dừng**: Stories inactive

### 2. **Search + Filter**
- Tìm kiếm theo title, author, slug
- Kết hợp với filter visibility
- Pagination với filter preserved

### 3. **Bulk Actions** (Future Enhancement)
- Bulk hide/show stories
- Bulk activate/deactivate
- Bulk change visibility

## 🚀 Benefits

### 1. **Content Management**
- **Flexible Control**: Admin có thể ẩn/hiện truyện dễ dàng
- **Gradual Release**: Có thể chuẩn bị content trước khi public
- **Quality Control**: Ẩn truyện có vấn đề mà không cần xóa

### 2. **Performance**
- **Optimized Queries**: Index tối ưu cho visibility queries
- **Reduced Load**: Frontend chỉ query stories cần thiết
- **Better UX**: Users chỉ thấy content chất lượng

### 3. **SEO & User Experience**
- **Clean Frontend**: Chỉ hiển thị content hoàn chỉnh
- **Better Navigation**: Không có broken links
- **Professional Look**: Site luôn trông professional

## 📈 Future Enhancements

### 1. **Scheduled Visibility**
```php
// Tự động public truyện vào thời gian nhất định
$story->update([
    'is_public' => true,
    'published_at' => '2025-07-10 00:00:00'
]);
```

### 2. **User-specific Visibility**
```php
// Chỉ hiển thị cho user premium
$story->update([
    'is_premium' => true,
    'required_level' => 'premium'
]);
```

### 3. **Category-based Visibility**
```php
// Ẩn/hiện theo thể loại
Genre::where('slug', 'adult')->update(['is_public' => false]);
```

---

## 🎉 Conclusion

Story Visibility Management cung cấp:

- ✅ **Flexible Control** cho admin
- ✅ **Clean Frontend** cho users  
- ✅ **Optimized Performance** với index
- ✅ **Professional Content Management**
- ✅ **Easy to Use** interface
- ✅ **Scalable Architecture** cho future enhancements

**Story Visibility is ready for production! 👁️✨**
