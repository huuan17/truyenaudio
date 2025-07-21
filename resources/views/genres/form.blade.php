<div class="form-group">
    <label for="name">Tên thể loại</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $genre->name) }}" required>

    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-success">Lưu</button>
<a href="{{ route('admin.genres.index') }}" class="btn btn-secondary">Quay lại</a>
