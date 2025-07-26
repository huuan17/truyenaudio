@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Thư viện Audio', 'url' => route('admin.audio-library.index')],
        ['title' => 'Thêm Audio Mới']
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus mr-2"></i>Thêm Audio Mới</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.audio-library.store') }}" enctype="multipart/form-data" id="audioForm">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Thông tin cơ bản</h6>
                            
                            <div class="form-group">
                                <label for="title">Tiêu đề</label>
                                <input type="text" name="title" id="title" class="form-control"
                                       value="{{ old('title') }}" placeholder="Để trống sẽ tự động lấy tên file">
                                <small class="form-text text-muted">
                                    Tên hiển thị của audio file trong thư viện. Nếu để trống, sẽ tự động lấy tên từ file upload.
                                </small>
                                @error('title')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea name="description" id="description" class="form-control" rows="3" 
                                          placeholder="Mô tả ngắn gọn về audio này...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Upload Mode Selection -->
                            <div class="form-group">
                                <label class="form-label">Chế độ upload</label>
                                <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                                    <label class="btn btn-outline-primary active mr-2 mb-2">
                                        <input type="radio" name="upload_mode" value="single" checked onchange="toggleUploadMode()">
                                        <i class="fas fa-file mr-1"></i>Upload 1 file
                                    </label>
                                    <label class="btn btn-outline-success mb-2">
                                        <input type="radio" name="upload_mode" value="multiple" onchange="toggleUploadMode()">
                                        <i class="fas fa-files mr-1"></i>Upload nhiều file
                                    </label>
                                </div>
                            </div>

                            <!-- Single File Upload -->
                            <div id="single-upload-section">
                                <div class="form-group">
                                    <label for="audio_file">File Audio <span class="text-danger">*</span></label>
                                    <input type="file" name="audio_file" id="audio_file" class="form-control-file"
                                           accept="audio/*" onchange="previewAudio(this)">
                                    <small class="form-text text-muted">
                                        Hỗ trợ: MP3, WAV, AAC, M4A, OGG. Tối đa 100MB.
                                    </small>
                                    <div id="audio-preview" class="mt-2"></div>
                                    @error('audio_file')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Multiple Files Upload -->
                            <div id="multiple-upload-section" style="display: none;">
                                <div class="form-group">
                                    <label for="audio_files">Chọn nhiều file Audio <span class="text-danger">*</span></label>
                                    <input type="file" name="audio_files[]" id="audio_files" class="form-control-file"
                                           accept="audio/*" multiple onchange="previewMultipleAudios(this)">
                                    <small class="form-text text-muted">
                                        Hỗ trợ: MP3, WAV, AAC, M4A, OGG. Tối đa 100MB mỗi file. Có thể chọn nhiều file cùng lúc.
                                    </small>
                                    <div id="multiple-audio-preview" class="mt-2"></div>
                                    @error('audio_files')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    @error('audio_files.*')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Classification -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Phân loại</h6>

                            <div class="alert alert-info" id="multiple-upload-note" style="display: none;">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Lưu ý:</strong> Khi upload nhiều file:
                                <ul class="mb-0 mt-1">
                                    <li>Tất cả file sẽ sử dụng cùng thông tin phân loại này</li>
                                    <li>Tên audio sẽ tự động tạo từ tên file</li>
                                    <li>Bạn có thể chỉnh sửa từng file riêng lẻ sau khi upload</li>
                                </ul>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category">Danh mục <span class="text-danger">*</span></label>
                                        <select name="category" id="category" class="form-control" required>
                                            <option value="">Chọn danh mục</option>
                                            @foreach($categories as $key => $label)
                                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="source_type">Nguồn <span class="text-danger">*</span></label>
                                        <select name="source_type" id="source_type" class="form-control" required>
                                            <option value="">Chọn nguồn</option>
                                            @foreach($sourceTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('source_type') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('source_type')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="language">Ngôn ngữ <span class="text-danger">*</span></label>
                                        <select name="language" id="language" class="form-control" required>
                                            @foreach($languages as $key => $label)
                                                <option value="{{ $key }}" {{ old('language', 'vi') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('language')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="voice_type">Loại giọng</label>
                                        <select name="voice_type" id="voice_type" class="form-control">
                                            <option value="">Không xác định</option>
                                            @foreach($voiceTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('voice_type') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('voice_type')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="mood">Tâm trạng</label>
                                        <select name="mood" id="mood" class="form-control">
                                            <option value="">Không xác định</option>
                                            @foreach($moodTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('mood') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('mood')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tags and Settings -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Tags và Cài đặt</h6>
                            
                            <div class="form-group">
                                <label for="tags">Tags</label>
                                <input type="text" name="tags" id="tags" class="form-control" 
                                       value="{{ old('tags') }}" placeholder="tag1, tag2, tag3...">
                                <small class="form-text text-muted">
                                    Nhập các tags cách nhau bằng dấu phẩy để dễ tìm kiếm
                                </small>
                                @error('tags')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" 
                                           {{ old('is_public') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">
                                        Công khai (cho phép người khác sử dụng)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Thêm vào Thư viện
                            </button>
                            <a href="{{ route('admin.audio-library.index') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Hướng dẫn</h6>
                </div>
                <div class="card-body">
                    <h6>Định dạng hỗ trợ:</h6>
                    <ul class="text-muted">
                        <li><strong>MP3</strong> - Phổ biến nhất, tương thích tốt</li>
                        <li><strong>WAV</strong> - Chất lượng cao, dung lượng lớn</li>
                        <li><strong>AAC</strong> - Chất lượng tốt, dung lượng nhỏ</li>
                        <li><strong>M4A</strong> - Định dạng Apple</li>
                        <li><strong>OGG</strong> - Mã nguồn mở</li>
                    </ul>
                    
                    <h6>Giới hạn Upload:</h6>
                    <ul class="text-muted">
                        <li><strong>Single file:</strong> Tối đa 100MB mỗi file</li>
                        <li><strong>Multiple files:</strong> <span class="upload-limits-text">Tối đa 50 files, tổng 35MB</span></li>
                        <li><strong>Processing:</strong> Upload lớn qua queue jobs</li>
                        <li><strong>Tracking:</strong> Theo dõi tiến trình real-time</li>
                        <li><strong>Quality:</strong> Khuyến nghị 128-320 kbps</li>
                        <li><strong>Tags:</strong> Sử dụng tags để dễ tìm kiếm</li>
                        <li><strong>Privacy:</strong> Audio công khai có thể dùng chung</li>
                    </ul>

                    <h6>Danh mục:</h6>
                    <ul class="text-muted">
                        <li><strong>Truyện audio:</strong> Audio từ truyện, chương</li>
                        <li><strong>Nhạc nền:</strong> Background music</li>
                        <li><strong>Giọng đọc:</strong> Voice-over, narration</li>
                        <li><strong>Hiệu ứng:</strong> Sound effects</li>
                        <li><strong>Podcast:</strong> Nội dung podcast</li>
                    </ul>
                </div>
            </div>

            <!-- Multiple Upload Tips -->
            <div class="card mt-3" id="multiple-upload-tips" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-files mr-2"></i>Upload nhiều file</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <strong>Lợi ích upload nhiều file:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Upload tối đa 50 files cùng lúc</li>
                            <li>Xử lý qua queue jobs (không bị timeout)</li>
                            <li>Theo dõi tiến trình real-time</li>
                            <li>Tự động tạo title từ tên file</li>
                            <li>Áp dụng cùng phân loại cho tất cả</li>
                            <li>Có thể chỉnh sửa riêng lẻ sau</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Giới hạn Upload:</strong>
                        <ul class="mb-0 mt-2">
                            <li class="upload-limits-text">Tối đa 50 files, tổng 35MB</li>
                            <li>Mỗi file tối đa: 100MB</li>
                            <li>Upload lớn sẽ xử lý qua queue</li>
                            <li>Có thể theo dõi tiến trình upload</li>
                        </ul>
                    </div>
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle mr-1"></i>Queue Worker:</strong>
                        <p class="mb-1">Upload nhiều file cần queue worker để xử lý.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Chạy lệnh: <code>php artisan queue:work</code>
                            </small>
                            <span id="queue-status" class="badge badge-secondary">Checking...</span>
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="loadServerLimits()">
                                <i class="fas fa-sync mr-1"></i>Refresh Limits
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audio Quality Tips -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-lightbulb mr-2"></i>Tips chất lượng</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Để có chất lượng tốt nhất:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Ghi âm trong môi trường yên tĩnh</li>
                            <li>Sử dụng micro chất lượng tốt</li>
                            <li>Tránh tiếng ồn nền</li>
                            <li>Chuẩn hóa âm lượng</li>
                            <li>Kiểm tra trước khi upload</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-section {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 1rem;
}

.section-title {
    color: #5a5c69;
    font-weight: 600;
    margin-bottom: 1rem;
}

#audio-preview audio {
    width: 100%;
    margin-top: 10px;
}

.file-info {
    background: #f8f9fc;
    padding: 0.75rem;
    border-radius: 0.35rem;
    margin-top: 0.5rem;
}

.multiple-files-preview {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    background: #f8f9fc;
}

.files-list {
    max-height: 400px;
    overflow-y: auto;
}

.file-item {
    background: #fff;
    margin: 0.5rem 0;
    padding: 0.75rem;
    border-radius: 0.35rem;
    border: 1px solid #e3e6f0;
}

.file-item:hover {
    background: #f1f3f4;
}
</style>
@endpush

@push('scripts')
<script>
// Generate clean title from filename
function generateTitleFromFilename(filename) {
    // Remove file extension
    let name = filename.replace(/\.[^/.]+$/, '');

    // Replace common separators with spaces
    name = name.replace(/[_\-\.]/g, ' ');

    // Remove multiple spaces
    name = name.replace(/\s+/g, ' ');

    // Remove common prefixes/suffixes
    name = name.replace(/^(audio|track|song|music|sound)\s*/i, '');
    name = name.replace(/\s*(audio|track|song|music|sound)$/i, '');

    // Remove numbers at the beginning if they look like track numbers
    name = name.replace(/^\d{1,3}[\s\-\.]*/, '');

    // Capitalize each word
    name = name.toLowerCase().split(' ').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ').trim();

    // If empty after cleaning, use original filename
    if (!name) {
        name = filename.replace(/\.[^/.]+$/, '').replace(/[_\-]/g, ' ');
        name = name.split(' ').map(word =>
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
    }

    return name;
}

// Toggle upload mode
function toggleUploadMode() {
    const uploadMode = document.querySelector('input[name="upload_mode"]:checked').value;
    const singleSection = document.getElementById('single-upload-section');
    const multipleSection = document.getElementById('multiple-upload-section');
    const multipleNote = document.getElementById('multiple-upload-note');
    const multipleTips = document.getElementById('multiple-upload-tips');
    const titleInput = document.getElementById('title');
    const audioForm = document.getElementById('audioForm');

    if (uploadMode === 'single') {
        singleSection.style.display = 'block';
        multipleSection.style.display = 'none';
        multipleNote.style.display = 'none';
        multipleTips.style.display = 'none';
        titleInput.required = false;
        audioForm.action = '{{ route("admin.audio-library.store") }}';

        // Clear multiple files
        document.getElementById('audio_files').value = '';
        document.getElementById('multiple-audio-preview').innerHTML = '';
    } else {
        singleSection.style.display = 'none';
        multipleSection.style.display = 'block';
        multipleNote.style.display = 'block';
        multipleTips.style.display = 'block';
        titleInput.required = false;
        audioForm.action = '{{ route("admin.audio-library.store-multiple") }}';

        // Clear single file
        document.getElementById('audio_file').value = '';
        document.getElementById('audio-preview').innerHTML = '';
        titleInput.value = '';
    }
}

function previewAudio(input) {
    const preview = document.getElementById('audio-preview');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB

        // Create audio element
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.src = URL.createObjectURL(file);
        audio.style.width = '100%';

        // Create file info
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <strong>File đã chọn:</strong> ${file.name}<br>
            <strong>Kích thước:</strong> ${fileSize} MB<br>
            <strong>Định dạng:</strong> ${file.type}
        `;

        // Clear previous preview
        preview.innerHTML = '';
        preview.appendChild(audio);
        preview.appendChild(fileInfo);

        // Auto-fill title from filename if empty
        const titleInput = document.getElementById('title');
        if (!titleInput.value.trim()) {
            titleInput.value = generateTitleFromFilename(file.name);
        }
    }
}

function previewMultipleAudios(input) {
    const preview = document.getElementById('multiple-audio-preview');

    if (input.files && input.files.length > 0) {
        const files = Array.from(input.files);
        let totalSize = 0;

        // Clear previous preview
        preview.innerHTML = '';

        // Create container
        const container = document.createElement('div');
        container.className = 'multiple-files-preview';

        // Create header
        const header = document.createElement('div');
        header.className = 'alert alert-success';
        header.innerHTML = `
            <strong>Đã chọn ${files.length} file audio:</strong>
        `;
        container.appendChild(header);

        // Create files list
        const filesList = document.createElement('div');
        filesList.className = 'files-list';

        files.forEach((file, index) => {
            totalSize += file.size;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);

            // Generate title from filename
            const generatedTitle = generateTitleFromFilename(file.name);

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item border-bottom py-2';
            fileItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <strong>${index + 1}. ${generatedTitle}</strong><br>
                        <small class="text-muted">
                            File: ${file.name} | Kích thước: ${fileSize} MB | Định dạng: ${file.type}
                        </small>
                    </div>
                    <div class="ml-2">
                        <audio controls style="width: 200px; height: 30px;">
                            <source src="${URL.createObjectURL(file)}" type="${file.type}">
                        </audio>
                    </div>
                </div>
            `;
            filesList.appendChild(fileItem);
        });

        container.appendChild(filesList);

        // Create summary with size warning using server limits
        const summary = document.createElement('div');
        const totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);
        const maxTotalSizeMB = (serverLimits.maxTotalSize / 1024 / 1024).toFixed(0);

        if (totalSize > serverLimits.maxTotalSize) {
            summary.className = 'alert alert-danger mt-2';
            summary.innerHTML = `
                <strong><i class="fas fa-exclamation-triangle mr-2"></i>Cảnh báo:</strong>
                ${files.length} files - ${totalSizeMB} MB
                <br><small>Vượt quá giới hạn ${maxTotalSizeMB}MB. Upload sẽ thất bại!</small>
            `;
        } else {
            summary.className = 'alert alert-info mt-2';
            summary.innerHTML = `
                <strong><i class="fas fa-check mr-2"></i>Tổng kết:</strong>
                ${files.length} files - ${totalSizeMB} MB / ${maxTotalSizeMB} MB
            `;
        }
        container.appendChild(summary);

        preview.appendChild(container);
    }
}

// Form validation
document.getElementById('audioForm').addEventListener('submit', function(e) {
    const uploadMode = document.querySelector('input[name="upload_mode"]:checked').value;
    const maxSize = 100 * 1024 * 1024; // 100MB

    if (uploadMode === 'single') {
        const audioFile = document.getElementById('audio_file');

        if (!audioFile.files || !audioFile.files[0]) {
            e.preventDefault();
            alert('Vui lòng chọn file audio.');
            return;
        }

        const file = audioFile.files[0];
        if (file.size > maxSize) {
            e.preventDefault();
            alert('File quá lớn. Vui lòng chọn file nhỏ hơn 100MB.');
            return;
        }
    } else {
        const audioFiles = document.getElementById('audio_files');

        if (!audioFiles.files || audioFiles.files.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một file audio.');
            return;
        }

        // Check file count limit
        if (audioFiles.files.length > 50) {
            e.preventDefault();
            alert('Tối đa 50 files mỗi lần upload. Vui lòng chọn ít files hơn.');
            return;
        }

        // Calculate total size and check individual files
        let totalSize = 0;
        for (let i = 0; i < audioFiles.files.length; i++) {
            const file = audioFiles.files[i];
            totalSize += file.size;

            if (file.size > maxSize) {
                e.preventDefault();
                alert(`File "${file.name}" quá lớn. Vui lòng chọn file nhỏ hơn 100MB.`);
                return;
            }
        }

        // Check total size using server limits
        if (totalSize > serverLimits.maxTotalSize) {
            e.preventDefault();
            const totalSizeMB = (totalSize / 1024 / 1024).toFixed(1);
            const maxTotalSizeMB = (serverLimits.maxTotalSize / 1024 / 1024).toFixed(1);
            alert(`Tổng kích thước files (${totalSizeMB}MB) vượt quá giới hạn ${maxTotalSizeMB}MB. Vui lòng chọn ít files hơn hoặc files nhỏ hơn.`);
            return;
        }
    }

    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const fileCount = uploadMode === 'single' ? 1 : document.getElementById('audio_files').files.length;
    submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Đang upload ${fileCount} file(s)...`;
    submitBtn.disabled = true;
});

// Global variables for server limits
let serverLimits = {
    maxTotalSize: 35 * 1024 * 1024, // Default 35MB (will be updated from server)
    maxFiles: 50,
    maxFileSize: 100 * 1024 * 1024 // Default 100MB (will be updated from server)
};

// Load server limits
function loadServerLimits() {
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    console.log('Loading server limits...');

    fetch(`/admin/api/audio-library/upload-limits?t=${timestamp}`)
        .then(response => {
            console.log('API Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Server limits data:', data);

            serverLimits = {
                maxTotalSize: data.recommended_limits.max_total_size,
                maxFiles: data.recommended_limits.max_files,
                maxFileSize: data.recommended_limits.max_file_size
            };

            console.log('Updated serverLimits:', serverLimits);

            // Update UI with actual limits
            updateLimitsDisplay(data);
        })
        .catch(error => {
            console.error('Error loading server limits:', error);
            console.warn('Using default limits');
        });
}

// Update limits display in UI
function updateLimitsDisplay(data) {
    console.log('Updating UI with data:', data);

    // Update tips text
    const tipsElements = document.querySelectorAll('.upload-limits-text');
    console.log('Found upload-limits-text elements:', tipsElements.length);

    tipsElements.forEach((element, index) => {
        const newText = `Tối đa ${data.recommended_limits.max_files} files, tổng ${data.recommended_limits.max_total_size_formatted}`;
        console.log(`Updating element ${index} to: ${newText}`);
        element.innerHTML = newText;
    });

    // Update detailed limits
    const limitsElements = document.querySelectorAll('.server-limits');
    limitsElements.forEach(element => {
        element.innerHTML = `
            <small class="text-muted">
                Server limits: ${data.recommended_limits.max_total_size_formatted} total,
                ${data.recommended_limits.max_file_size_formatted} per file
            </small>
        `;
    });

    console.log('UI update completed');
}

// Check queue worker status
function checkQueueStatus() {
    const statusBadge = document.getElementById('queue-status');
    if (statusBadge) {
        statusBadge.className = 'badge badge-success';
        statusBadge.innerHTML = '<i class="fas fa-check mr-1"></i>Ready';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadServerLimits();
    checkQueueStatus();
});
</script>
@endpush
