<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="name">Tên thể loại <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $genre->name) }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="title">Tiêu đề SEO</label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $genre->title) }}" placeholder="Để trống sẽ dùng tên thể loại">
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="description">Mô tả ngắn</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
              rows="3" placeholder="Mô tả ngắn về thể loại này">{{ old('description', $genre->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="content">Nội dung chi tiết</label>
    <textarea name="content" class="form-control @error('content') is-invalid @enderror"
              rows="8" placeholder="Nội dung chi tiết về thể loại này">{{ old('content', $genre->content) }}</textarea>
    @error('content')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <div class="form-check">
        <input type="checkbox" name="is_public" value="1" class="form-check-input @error('is_public') is-invalid @enderror"
               id="is_public" {{ old('is_public', $genre->is_public ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_public">
            Hiển thị công khai
        </label>
        @error('is_public')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <small class="form-text text-muted">Bỏ tick để ẩn thể loại này khỏi frontend</small>
</div>

<button type="submit" class="btn btn-success">Lưu</button>
<a href="{{ route('admin.genres.index') }}" class="btn btn-secondary">Quay lại</a>
