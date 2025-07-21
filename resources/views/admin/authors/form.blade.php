@php
    $isEdit = isset($author) && $author->exists;
    $formAction = $isEdit ? route('admin.authors.update', $author) : route('admin.authors.store');
    $formMethod = $isEdit ? 'PUT' : 'POST';
@endphp

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row">
        <!-- Basic Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin cơ bản</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Tên tác giả <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $author->name ?? '') }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="slug">Slug</label>
                                <input type="text" 
                                       class="form-control @error('slug') is-invalid @enderror" 
                                       id="slug" 
                                       name="slug" 
                                       value="{{ old('slug', $author->slug ?? '') }}"
                                       placeholder="Tự động tạo từ tên">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Để trống để tự động tạo từ tên</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Tiểu sử</label>
                        <textarea class="form-control @error('bio') is-invalid @enderror" 
                                  id="bio" 
                                  name="bio" 
                                  rows="5"
                                  placeholder="Mô tả về tác giả...">{{ old('bio', $author->bio ?? '') }}</textarea>
                        @error('bio')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birth_date">Ngày sinh</label>
                                <input type="date" 
                                       class="form-control @error('birth_date') is-invalid @enderror" 
                                       id="birth_date" 
                                       name="birth_date" 
                                       value="{{ old('birth_date', $author->birth_date?->format('Y-m-d') ?? '') }}">
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nationality">Quốc tịch</label>
                                <input type="text" 
                                       class="form-control @error('nationality') is-invalid @enderror" 
                                       id="nationality" 
                                       name="nationality" 
                                       value="{{ old('nationality', $author->nationality ?? '') }}"
                                       placeholder="Việt Nam">
                                @error('nationality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="achievements">Thành tựu / Giải thưởng</label>
                        <textarea class="form-control @error('achievements') is-invalid @enderror" 
                                  id="achievements" 
                                  name="achievements" 
                                  rows="3"
                                  placeholder="Mỗi thành tựu một dòng...">{{ old('achievements', is_array($author->achievements ?? null) ? implode("\n", $author->achievements) : '') }}</textarea>
                        @error('achievements')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Mỗi thành tựu/giải thưởng trên một dòng</small>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin liên hệ</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $author->email ?? '') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input type="url" 
                                       class="form-control @error('website') is-invalid @enderror" 
                                       id="website" 
                                       name="website" 
                                       value="{{ old('website', $author->website ?? '') }}"
                                       placeholder="https://example.com">
                                @error('website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="facebook">Facebook</label>
                                <input type="url" 
                                       class="form-control @error('facebook') is-invalid @enderror" 
                                       id="facebook" 
                                       name="facebook" 
                                       value="{{ old('facebook', $author->facebook ?? '') }}"
                                       placeholder="https://facebook.com/username">
                                @error('facebook')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="twitter">Twitter</label>
                                <input type="url" 
                                       class="form-control @error('twitter') is-invalid @enderror" 
                                       id="twitter" 
                                       name="twitter" 
                                       value="{{ old('twitter', $author->twitter ?? '') }}"
                                       placeholder="https://twitter.com/username">
                                @error('twitter')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="instagram">Instagram</label>
                                <input type="url" 
                                       class="form-control @error('instagram') is-invalid @enderror" 
                                       id="instagram" 
                                       name="instagram" 
                                       value="{{ old('instagram', $author->instagram ?? '') }}"
                                       placeholder="https://instagram.com/username">
                                @error('instagram')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Avatar -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Avatar</h5>
                </div>
                <div class="card-body text-center">
                    @if($isEdit && $author->avatar)
                        <img src="{{ $author->avatar_url }}" 
                             alt="{{ $author->name }}" 
                             class="img-thumbnail mb-3" 
                             style="max-width: 200px;">
                    @endif
                    
                    <div class="form-group">
                        <input type="file" 
                               class="form-control-file @error('avatar') is-invalid @enderror" 
                               id="avatar" 
                               name="avatar" 
                               accept="image/*">
                        @error('avatar')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">JPG, PNG, GIF. Tối đa 2MB.</small>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Trạng thái</h5>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', $author->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Kích hoạt tác giả
                        </label>
                    </div>
                    <small class="form-text text-muted">Tác giả không kích hoạt sẽ không hiển thị trên frontend</small>
                </div>
            </div>

            <!-- SEO -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">SEO</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" 
                               class="form-control @error('meta_title') is-invalid @enderror" 
                               id="meta_title" 
                               name="meta_title" 
                               value="{{ old('meta_title', $author->meta_title ?? '') }}"
                               maxlength="255">
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                  id="meta_description" 
                                  name="meta_description" 
                                  rows="3"
                                  maxlength="500">{{ old('meta_description', $author->meta_description ?? '') }}</textarea>
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" 
                               class="form-control @error('meta_keywords') is-invalid @enderror" 
                               id="meta_keywords" 
                               name="meta_keywords" 
                               value="{{ old('meta_keywords', $author->meta_keywords ?? '') }}"
                               placeholder="keyword1, keyword2, keyword3">
                        @error('meta_keywords')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-3">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i>
                        {{ $isEdit ? 'Cập nhật' : 'Tạo mới' }}
                    </button>
                    <a href="{{ route('admin.authors.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.value || slugInput.dataset.autoGenerated) {
            const slug = createVietnameseSlug(this.value);
            slugInput.value = slug;
            slugInput.dataset.autoGenerated = 'true';
        }
    });

    // Function to create Vietnamese slug
    function createVietnameseSlug(text) {
        if (!text) return '';

        // Convert to lowercase
        text = text.toLowerCase();

        // Replace Vietnamese characters
        const vietnamese = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ'
        ];

        const latin = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd'
        ];

        // Replace Vietnamese characters with Latin equivalents
        for (let i = 0; i < vietnamese.length; i++) {
            text = text.replace(new RegExp(vietnamese[i], 'g'), latin[i]);
        }

        // Remove special characters except spaces and hyphens
        text = text.replace(/[^a-z0-9\s\-]/g, '');

        // Replace multiple spaces with single space
        text = text.replace(/\s+/g, ' ');

        // Trim spaces
        text = text.trim();

        // Replace spaces with hyphens
        text = text.replace(/\s/g, '-');

        // Remove multiple consecutive hyphens
        text = text.replace(/-+/g, '-');

        // Remove leading and trailing hyphens
        text = text.replace(/^-+|-+$/g, '');

        return text;
    }
    
    slugInput.addEventListener('input', function() {
        this.dataset.autoGenerated = 'false';
    });
});
</script>
@endpush
