@extends('layouts.app')

@section('title', 'Tạo Video Đa Nền Tảng')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Tạo Video',
            'badge' => 'Đa nền tảng'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video mr-2"></i>Tạo Video Đa Nền Tảng
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.video-queue.index') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-tasks mr-1"></i>Trạng thái xử lý
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Platform Selection Tabs -->
                    <ul class="nav nav-tabs" id="platformTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="tiktok-tab" data-toggle="tab" href="#tiktok" role="tab">
                                <i class="fab fa-tiktok mr-2"></i>TikTok Video
                                <span class="badge badge-dark ml-1">{{ count($tiktokVideos) }}</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="youtube-tab" data-toggle="tab" href="#youtube" role="tab">
                                <i class="fab fa-youtube mr-2"></i>YouTube Video
                                <span class="badge badge-danger ml-1">{{ count($youtubeVideos) }}</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content mt-3" id="platformTabContent">
                        
                        <!-- TikTok Tab -->
                        <div class="tab-pane fade show active" id="tiktok" role="tabpanel">
                            @include('admin.video-generator.partials.tiktok-form')
                        </div>

                        <!-- YouTube Tab -->
                        <div class="tab-pane fade" id="youtube" role="tabpanel">
                            @include('admin.video-generator.partials.youtube-form')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shared Modals -->
@include('admin.video-generator.partials.shared-modals')

@endsection

@push('scripts')
<script>
// Global variables
let currentPlatform = 'tiktok';
let videoItemCounter = 0;
let youtubeVideoItemCounter = 0;

// Platform switching
$('#platformTabs a').on('click', function (e) {
    e.preventDefault();
    const platform = $(this).attr('href').substring(1);
    currentPlatform = platform;
    $(this).tab('show');
    
    // Update any platform-specific UI
    updatePlatformUI(platform);
});

function updatePlatformUI(platform) {
    // Update form action URLs
    if (platform === 'tiktok') {
        $('#tiktok-single-form').attr('action', '{{ route("admin.video-generator.generate") }}');
        $('#tiktok-batch-form').attr('action', '{{ route("admin.video-generator.generate-batch") }}');
    } else {
        $('#youtube-single-form').attr('action', '{{ route("admin.video-generator.generate") }}');
        $('#youtube-batch-form').attr('action', '{{ route("admin.video-generator.generate-batch") }}');
    }
}

// Initialize on page load
$(document).ready(function() {
    updatePlatformUI('tiktok');

    // Initialize output name previews
    updateOutputPreview('tiktok', 'video');
    updateOutputPreview('youtube');

    // Auto-refresh status every 30 seconds
    setInterval(function() {
        refreshVideoStatus();
    }, 30000);
});

function refreshVideoStatus() {
    $.get('{{ route("admin.video-generator.status") }}', {
        platform: currentPlatform
    }, function(data) {
        updateStatusDisplay(data);
    });
}

function updateStatusDisplay(data) {
    // Update status indicators if they exist
    if (data.total_pending > 0) {
        showStatusBadge('pending', data.total_pending);
    }
    if (data.total_processing > 0) {
        showStatusBadge('processing', data.total_processing);
    }
}

function showStatusBadge(type, count) {
    const badgeClass = type === 'pending' ? 'badge-warning' : 'badge-info';
    const icon = type === 'pending' ? 'fa-clock' : 'fa-cog fa-spin';
    const text = type === 'pending' ? 'Đang chờ' : 'Đang xử lý';
    
    // Show floating badge
    if ($('#status-badge').length === 0) {
        $('body').append(`
            <div id="status-badge" class="position-fixed" style="top: 20px; right: 20px; z-index: 1050;">
                <span class="badge ${badgeClass} badge-lg">
                    <i class="fas ${icon} mr-1"></i>${text}: ${count}
                </span>
            </div>
        `);
    } else {
        $('#status-badge .badge').removeClass('badge-warning badge-info')
                                 .addClass(badgeClass)
                                 .html(`<i class="fas ${icon} mr-1"></i>${text}: ${count}`);
    }
    
    // Auto hide after 5 seconds
    setTimeout(function() {
        $('#status-badge').fadeOut();
    }, 5000);
}

// Shared utility functions
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    return true;
}

function showLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...';
    }
}

function hideLoading(buttonId, originalText) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

// File upload preview
function previewFile(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file && preview) {
        const size = (file.size / 1024 / 1024).toFixed(2);
        preview.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-file mr-2"></i>
                <strong>${file.name}</strong> (${size} MB)
            </div>
        `;
    }
}

// Delete videos function
function deleteVideos(platform) {
    const checkboxes = document.querySelectorAll(`input[name="${platform}_videos[]"]:checked`);
    
    if (checkboxes.length === 0) {
        alert('Vui lòng chọn ít nhất một video để xóa');
        return;
    }
    
    if (!confirm(`Bạn có chắc muốn xóa ${checkboxes.length} video đã chọn?`)) {
        return;
    }
    
    const files = Array.from(checkboxes).map(cb => cb.value);
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.video-generator.delete") }}';
    
    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // Method
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    // Platform
    const platformInput = document.createElement('input');
    platformInput.type = 'hidden';
    platformInput.name = 'platform';
    platformInput.value = platform;
    form.appendChild(platformInput);
    
    // Files
    files.forEach(file => {
        const fileInput = document.createElement('input');
        fileInput.type = 'hidden';
        fileInput.name = 'files[]';
        fileInput.value = file;
        form.appendChild(fileInput);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// TikTok Media Type Switching
function switchTiktokMediaType(type) {
    const videoUpload = document.getElementById('tiktok-video-upload');
    const imagesUpload = document.getElementById('tiktok-images-upload');
    const videoInput = document.getElementById('tiktok_product_video');
    const imagesInput = document.getElementById('tiktok_product_images');

    if (type === 'video') {
        videoUpload.style.display = 'block';
        imagesUpload.style.display = 'none';
        videoInput.required = true;
        imagesInput.required = false;
    } else {
        videoUpload.style.display = 'none';
        imagesUpload.style.display = 'block';
        videoInput.required = false;
        imagesInput.required = true;
    }

    // Update output name preview
    updateOutputPreview('tiktok', type);
}

// Update output name preview
function updateOutputPreview(prefix, mediaType = null) {
    const outputInput = document.getElementById(prefix + '_output_name');
    const defaultNameSpan = document.getElementById(prefix + '_default_name');

    if (!defaultNameSpan) return;

    // Generate preview name
    let baseName = prefix;
    if (prefix === 'tiktok' && mediaType) {
        baseName = `tiktok_${mediaType === 'images' ? 'slide' : 'video'}`;
    } else if (prefix === 'youtube') {
        const contentType = document.querySelector('input[name="video_content_type"]:checked')?.value || 'images';
        const audioSource = document.querySelector('input[name="audio_source"]:checked')?.value || 'text';
        baseName = `youtube_${contentType}_${audioSource === 'text' ? 'tts' : 'audio'}`;
    }

    const now = new Date();
    const date = now.toISOString().slice(0, 10);
    const time = now.toTimeString().slice(0, 5).replace(':', '-');

    const defaultName = `${baseName}_${date}_${time}_001.mp4`;
    defaultNameSpan.textContent = defaultName;

    // Update placeholder if input is empty
    if (!outputInput.value) {
        outputInput.placeholder = `Ví dụ: ${baseName}_custom_name`;
    }
}

// Preview multiple images
function previewMultipleImages(input, previewId) {
    const previewContainer = document.getElementById(previewId);
    previewContainer.innerHTML = '';

    if (input.files && input.files.length > 0) {
        const files = Array.from(input.files);

        // Create preview grid
        const grid = document.createElement('div');
        grid.className = 'row';

        files.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-sm-4 col-6 mb-3';

                const card = document.createElement('div');
                card.className = 'card';

                const img = document.createElement('img');
                img.className = 'card-img-top';
                img.style.height = '120px';
                img.style.objectFit = 'cover';
                img.src = URL.createObjectURL(file);

                const cardBody = document.createElement('div');
                cardBody.className = 'card-body p-2';

                const fileName = document.createElement('small');
                fileName.className = 'text-muted d-block text-truncate';
                fileName.textContent = `${index + 1}. ${file.name}`;

                const fileSize = document.createElement('small');
                fileSize.className = 'text-muted';
                fileSize.textContent = formatFileSize(file.size);

                cardBody.appendChild(fileName);
                cardBody.appendChild(fileSize);
                card.appendChild(img);
                card.appendChild(cardBody);
                col.appendChild(card);
                grid.appendChild(col);
            }
        });

        previewContainer.appendChild(grid);

        // Add summary
        const summary = document.createElement('div');
        summary.className = 'alert alert-info mt-2';
        summary.innerHTML = `<i class="fas fa-info-circle mr-2"></i>Đã chọn ${files.length} ảnh. Thứ tự hiển thị sẽ theo thứ tự chọn file.`;
        previewContainer.appendChild(summary);
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Select all videos
function selectAllVideos(platform, checked) {
    const checkboxes = document.querySelectorAll(`input[name="${platform}_videos[]"]`);
    checkboxes.forEach(cb => cb.checked = checked);
}
</script>

<!-- Include platform-specific scripts -->
@include('admin.video-generator.partials.tiktok-scripts')
@include('admin.video-generator.partials.youtube-scripts')
@endpush

@push('styles')
<style>
/* Platform tabs styling */
.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
    color: #495057;
    font-weight: 500;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    color: #007bff;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.nav-tabs .nav-link .badge {
    font-size: 0.75rem;
}

/* Platform-specific colors */
#tiktok-tab.active {
    border-bottom-color: #fff;
}

#tiktok-tab .badge-dark {
    background-color: #000;
}

#youtube-tab.active {
    border-bottom-color: #fff;
}

#youtube-tab .badge-danger {
    background-color: #dc3545;
}

/* Status badge */
#status-badge .badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Shared form styling */
.platform-form {
    min-height: 600px;
}

.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}

.form-section h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
}

/* Video list styling */
.video-list {
    max-height: 400px;
    overflow-y: auto;
}

.video-item {
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 10px;
    background: #fff;
}

.video-item:hover {
    background: #f8f9fa;
    border-color: #007bff;
}

/* Images preview styling */
.images-preview-container .card {
    transition: transform 0.2s;
}

.images-preview-container .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.media-upload-section {
    transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }

    .nav-tabs .nav-link .badge {
        font-size: 0.625rem;
    }

    .images-preview-container .col-6 {
        margin-bottom: 1rem;
    }
}
</style>
@endpush
