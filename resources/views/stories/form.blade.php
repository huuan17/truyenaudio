@php
    $isEdit = isset($story);
    $storyGenres = $isEdit ? $story->genres->pluck('id')->toArray() : [];
    $textBasePath = config('constants.STORAGE_PATHS.TEXT');
@endphp

<form action="{{ $isEdit ? route('stories.update', $story->id) : route('stories.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-group">
        <label>Tiêu đề</label>
        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $isEdit ? $story->title : '') }}" required>
    </div>
    
    <div class="form-group">
        <label>Slug</label>
        <input type="text" name="slug" id="slug" class="form-control" value="{{ old('slug', $isEdit ? $story->slug : '') }}" required>
        <small class="form-text text-muted">Slug sẽ được sử dụng để tạo đường dẫn lưu trữ</small>
    </div>
    
    <div class="form-group">
        <label for="genres">Thể loại</label>
        <select name="genres[]" id="genres" class="form-control select2" multiple>
            @foreach($allGenres as $genre)
                <option value="{{ $genre->id }}"
                    {{ in_array($genre->id, old('genres', $storyGenres)) ? 'selected' : '' }}>
                    {{ $genre->name }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">Giữ phím Ctrl (hoặc Command trên Mac) để chọn nhiều thể loại</small>
    </div>
    <div class="form-group">
        <label>Tác giả</label>
        <input type="text" name="author" class="form-control" value="{{ old('author', $isEdit ? $story->author : '') }}">
    </div>

    <div class="form-group">
        <label>Mô tả</label>
        <textarea name="description" class="form-control">{{ old('description', $story->description ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label>URL nguồn</label>
        <input type="url" name="source_url" class="form-control" value="{{ old('source_url', $isEdit ? $story->source_url : '') }}" required>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Chương bắt đầu</label>
            <input type="number" name="start_chapter" class="form-control" value="{{ old('start_chapter', $isEdit ? $story->start_chapter : 1) }}" min="1" required>
        </div>
        <div class="form-group col-md-6">
            <label>Chương kết thúc</label>
            <input type="number" name="end_chapter" class="form-control" value="{{ old('end_chapter', $isEdit ? $story->end_chapter : 10) }}" min="1" required>
        </div>
    </div>

    <div class="form-group">
        <label>Đường dẫn lưu trữ</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">{{ $textBasePath }}</span>
            </div>
            <input type="text" id="folder_name" name="folder_name" class="form-control" value="{{ old('folder_name', $isEdit ? $story->folder_name : '') }}" readonly>
            <input type="hidden" name="crawl_path" id="crawl_path" value="{{ old('crawl_path', $isEdit ? $story->crawl_path : $textBasePath) }}">
        </div>
        <small class="form-text text-muted">Đường dẫn sẽ được tự động tạo từ slug</small>
    </div>

    <div class="form-group">
        <label>Trạng thái crawl</label>
        <select name="crawl_status" class="form-control">
            @foreach(config('constants.CRAWL_STATUS.LABELS') as $value => $label)
                <option value="{{ $value }}" @selected(old('crawl_status', $isEdit ? $story->crawl_status : 0) == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Ảnh bìa</label>
        <input type="file" name="cover" class="form-control-file">
        @if($isEdit && $story->cover_image)
            <div class="mt-2">
                <img src="{{ asset($story->cover_image) }}" width="120">
            </div>
        @endif
    </div>

    <button type="submit" class="btn btn-success">Lưu</button>
</form>

@push('scripts')
<script>
    // Tự động tạo slug từ tiêu đề
    document.getElementById('title').addEventListener('input', function() {
        const title = this.value;
        const slug = title.toLowerCase()
            .replace(/đ/g, 'd')
            .replace(/[áàảãạâấầẩẫậăắằẳẵặ]/g, 'a')
            .replace(/[éèẻẽẹêếềểễệ]/g, 'e')
            .replace(/[íìỉĩị]/g, 'i')
            .replace(/[óòỏõọôốồổỗộơớờởỡợ]/g, 'o')
            .replace(/[úùủũụưứừửữự]/g, 'u')
            .replace(/[ýỳỷỹỵ]/g, 'y')
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
        
        document.getElementById('slug').value = slug;
        updateFolderName(slug);
    });
    
    document.getElementById('slug').addEventListener('input', function() {
        updateFolderName(this.value);
    });
    
    function updateFolderName(slug) {
        const folderName = slug;
        document.getElementById('folder_name').value = folderName;
        
        const basePath = '{{ $textBasePath }}';
        document.getElementById('crawl_path').value = basePath + folderName;
    }
    
    // Khởi tạo khi trang load
    if (document.getElementById('slug').value) {
        updateFolderName(document.getElementById('slug').value);
    }
</script>
@endpush
