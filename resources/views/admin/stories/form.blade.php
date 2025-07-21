@php
    $isEdit = isset($story);
    $storyGenres = $isEdit ? $story->genres->pluck('id')->toArray() : [];
    $textBasePath = config('constants.STORAGE_PATHS.TEXT');
@endphp

<form action="{{ $isEdit ? route('admin.stories.update', $story) : route('admin.stories.store') }}" method="POST" enctype="multipart/form-data">
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
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Tác giả</label>
                <select name="author_id" class="form-control">
                    <option value="">-- Chọn tác giả --</option>
                    @foreach(\App\Models\Author::orderBy('name')->get() as $author)
                        <option value="{{ $author->id }}"
                                {{ old('author_id', $isEdit ? $story->author_id : '') == $author->id ? 'selected' : '' }}>
                            {{ $author->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    <a href="{{ route('admin.authors.create') }}" target="_blank" class="text-primary">
                        <i class="fas fa-plus"></i> Thêm tác giả mới
                    </a>
                </small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Tác giả (text cũ)</label>
                <input type="text" name="author" class="form-control"
                       value="{{ old('author', $isEdit ? $story->author : '') }}"
                       placeholder="Để trống nếu đã chọn tác giả ở trên">
                <small class="form-text text-muted">Chỉ dùng khi chưa có tác giả trong hệ thống</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>Mô tả</label>
        <textarea name="description" class="form-control">{{ old('description', $story->description ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label>URL nguồn <small class="text-muted">(không bắt buộc)</small></label>
        <input type="url" name="source_url" class="form-control" value="{{ old('source_url', $isEdit ? $story->source_url : '') }}" placeholder="https://example.com/story-url">
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

    <!-- Visibility Settings -->
    <div class="form-group">
        <label>Cài đặt hiển thị</label>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="is_public" id="is_public" class="form-check-input"
                           value="1" {{ old('is_public', $isEdit ? $story->is_public : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_public">
                        <i class="fas fa-eye mr-1"></i>Công khai
                    </label>
                    <small class="form-text text-muted">Truyện có hiển thị ở trang chủ và tìm kiếm không</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                           value="1" {{ old('is_active', $isEdit ? $story->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        <i class="fas fa-power-off mr-1"></i>Hoạt động
                    </label>
                    <small class="form-text text-muted">Truyện có đang hoạt động không (có thể tạm dừng)</small>
                </div>
            </div>
        </div>
        <div class="mt-2">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Lưu ý:</strong> Chỉ những truyện vừa <strong>Công khai</strong> vừa <strong>Hoạt động</strong> mới hiển thị ở frontend.
            </div>
        </div>
    </div>

    <!-- Auto Processing Settings -->
    <div class="form-group">
        <label>Cài đặt tự động hóa</label>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="auto_crawl" id="auto_crawl" class="form-check-input"
                           value="1" {{ old('auto_crawl', $isEdit ? $story->auto_crawl : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_crawl">
                        <i class="fas fa-download mr-1 text-success"></i>Tự động crawl
                    </label>
                    <small class="form-text text-muted">Tự động đưa vào queue job để crawl, tránh request liên tục tới website nguồn</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="auto_tts" id="auto_tts" class="form-check-input"
                           value="1" {{ old('auto_tts', $isEdit ? $story->auto_tts : false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_tts">
                        <i class="fas fa-volume-up mr-1 text-primary"></i>Tự động TTS
                    </label>
                    <small class="form-text text-muted">Tự động convert audio qua VBee TTS, đưa vào hàng đợi xử lý tránh overload API</small>
                </div>
            </div>
        </div>
        <div class="mt-2">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Lưu ý:</strong>
                <ul class="mb-0 mt-1">
                    <li><strong>Tự động crawl:</strong> Sẽ đưa truyện vào queue để crawl tuần tự, tránh spam website nguồn</li>
                    <li><strong>Tự động TTS:</strong> Sau khi crawl xong sẽ tự động convert sang audio, tốn credit VBee API</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- TTS Default Settings -->
    <div class="form-group">
        <label>Cài đặt TTS mặc định</label>
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Thông tin:</strong> Các cài đặt này sẽ được áp dụng mặc định cho tất cả chapter của truyện khi chuyển đổi TTS.
            <div class="mt-2">
                <strong>Cài đặt mặc định hệ thống:</strong>
                <ul class="mb-0">
                    <li>Giọng đọc: Ngọc Huyền (Nữ - Hà Nội)</li>
                    <li>Bitrate: 128 kbps</li>
                    <li>Tốc độ đọc: 1.0x (Bình thường)</li>
                    <li>Âm lượng: 100% (Bình thường)</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="default_tts_voice">Giọng đọc mặc định</label>
                    <select name="default_tts_voice" id="default_tts_voice" class="form-control">
                        @php
                            $voices = [
                                'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
                                'hn_male_phuthang_stor80dt_48k-fhg' => 'Anh Khôi (Nam - Hà Nội)',
                                'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
                                'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)',
                                'sg_female_tuongvy_call_44k-fhg' => 'Tường Vy (Nữ - Sài Gòn)'
                            ];
                        @endphp
                        @foreach($voices as $code => $name)
                            <option value="{{ $code }}"
                                {{ old('default_tts_voice', $isEdit ? $story->default_tts_voice : 'hn_female_ngochuyen_full_48k-fhg') == $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Giọng đọc sẽ được sử dụng cho tất cả chapter</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="default_tts_bitrate">Bitrate mặc định (kbps)</label>
                    <select name="default_tts_bitrate" id="default_tts_bitrate" class="form-control">
                        @php
                            $bitrates = [64 => '64 kbps', 128 => '128 kbps', 192 => '192 kbps', 256 => '256 kbps', 320 => '320 kbps'];
                        @endphp
                        @foreach($bitrates as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('default_tts_bitrate', $isEdit ? $story->default_tts_bitrate : 128) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Chất lượng audio (cao hơn = file lớn hơn)</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="default_tts_speed">Tốc độ đọc mặc định</label>

                    <select name="default_tts_speed" id="default_tts_speed" class="form-control">
                        <!-- Hardcoded options instead of using array loop -->
                        <option value="0.5" {{ old('default_tts_speed', $isEdit ? $story->default_tts_speed : '') == '0.5' ? 'selected' : '' }}>0.5x (Chậm)</option>
                        <option value="1.0" {{ old('default_tts_speed', $isEdit ? $story->default_tts_speed : '1.0') == '1.0' || old('default_tts_speed', $isEdit ? $story->default_tts_speed : '1.0') == '1' ? 'selected' : '' }}>1.0x (Bình thường) - Mặc định</option>
                        <option value="1.5" {{ old('default_tts_speed', $isEdit ? $story->default_tts_speed : '') == '1.5' ? 'selected' : '' }}>1.5x (Nhanh)</option>
                        <option value="2.0" {{ old('default_tts_speed', $isEdit ? $story->default_tts_speed : '') == '2.0' || old('default_tts_speed', $isEdit ? $story->default_tts_speed : '') == '2' ? 'selected' : '' }}>2.0x (Rất nhanh)</option>
                    </select>
                    <small class="form-text text-muted">Tốc độ đọc của giọng nói</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="default_tts_volume">Âm lượng mặc định</label>
                    <select name="default_tts_volume" id="default_tts_volume" class="form-control">
                        <!-- Hardcoded options instead of using array loop -->
                        <option value="1.0" {{ old('default_tts_volume', $isEdit ? $story->default_tts_volume : '1.0') == '1.0' || old('default_tts_volume', $isEdit ? $story->default_tts_volume : '1.0') == '1' ? 'selected' : '' }}>100% (Bình thường) - Mặc định</option>
                        <option value="1.5" {{ old('default_tts_volume', $isEdit ? $story->default_tts_volume : '') == '1.5' ? 'selected' : '' }}>150% (To)</option>
                        <option value="2.0" {{ old('default_tts_volume', $isEdit ? $story->default_tts_volume : '') == '2.0' || old('default_tts_volume', $isEdit ? $story->default_tts_volume : '') == '2' ? 'selected' : '' }}>200% (Rất to)</option>
                    </select>
                    <small class="form-text text-muted">Âm lượng của audio được tạo</small>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Lưu ý:</strong>
            <ul class="mb-0 mt-1">
                <li><strong>Giọng đọc:</strong> Chọn giọng phù hợp với nội dung truyện (nam/nữ, miền Bắc/Nam)</li>
                <li><strong>Bitrate:</strong> 128kbps phù hợp cho web, 256kbps+ cho chất lượng cao</li>
                <li><strong>Tốc độ:</strong> 1.0x là tốc độ bình thường, có thể điều chỉnh theo sở thích</li>
                <li><strong>Âm lượng:</strong> 100% là mức chuẩn, tránh để quá cao gây méo tiếng</li>
                <li class="mt-2 text-danger"><strong>Quan trọng:</strong> Hệ thống sử dụng <strong>slug</strong> thay vì ID trong URL. Đảm bảo slug không bị trùng và không thay đổi sau khi đã tạo truyện để tránh lỗi khi truy cập.</li>
            </ul>
        </div>
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
        const slug = createVietnameseSlug(title);

        document.getElementById('slug').value = slug;
        updateFolderName(slug);
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
