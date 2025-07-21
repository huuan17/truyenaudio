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
                    <!-- Video Generation Mode Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-cogs mr-2"></i>Chế độ tạo video</h5>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-primary active">
                                    <input type="radio" name="video_mode" id="single_video" value="single" checked>
                                    <i class="fas fa-video mr-2"></i>Tạo 1 video
                                </label>
                                <label class="btn btn-outline-primary">
                                    <input type="radio" name="video_mode" id="batch_video" value="batch">
                                    <i class="fas fa-layer-group mr-2"></i>Tạo nhiều video
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Main Video Generation Form -->
                    <form id="videoGeneratorForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="mode" id="form_mode" value="single">

                        <!-- Content Sections -->
                        <div class="row">
                            <!-- Left Column: Media Content -->
                            <div class="col-lg-8">
                                @include('admin.video-generator.partials.media-section')
                                @include('admin.video-generator.partials.audio-section')
                                @include('admin.video-generator.partials.subtitle-section')
                                @include('admin.video-generator.partials.logo-section')
                            </div>

                            <!-- Right Column: Platform & Settings -->
                            <div class="col-lg-4">
                                @include('admin.video-generator.partials.platform-section')
                                @include('admin.video-generator.partials.video-info-section')
                                @include('admin.video-generator.partials.batch-section')
                                @include('admin.video-generator.partials.existing-videos')
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success btn-lg" id="generateBtn">
                                        <i class="fas fa-play mr-2"></i>Tạo Video
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg ml-2">
                                        <i class="fas fa-undo mr-2"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

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

// New Video Generator JavaScript
// Global variables for new interface
let batchVideoCount = 3;

// Initialize new video generator
function initializeNewVideoGenerator() {
    setupNewEventListeners();
    generateBatchVideos(3); // Default 3 videos
}

function setupNewEventListeners() {
    // Video mode change
    $('input[name="video_mode"]').change(function() {
        const mode = $(this).val();
        $('#form_mode').val(mode);

        if (mode === 'batch') {
            $('#batch-section').show();
        } else {
            $('#batch-section').hide();
        }

        updateNewFormAction();
    });

    // Platform change
    $('input[name="platform"]').change(function() {
        const platform = $(this).val();
        showPlatformSettings(platform);
        updateNewFormAction();
    });

    // Media type change
    $('input[name="media_type"]').change(function() {
        const type = $(this).val();
        showMediaSection(type);
    });

    // Mixed mode change
    $('input[name="mixed_mode"]').change(function() {
        const mode = $(this).val();
        showMixedModeSettings(mode);
    });

    // Duration based on change
    $('input[name="duration_based_on"]').change(function() {
        const basis = $(this).val();
        showDurationSettings(basis);
        calculateTotalDuration();
    });

    // Sequence video duration change
    $('#sequence_video_duration').change(function() {
        const duration = $(this).val();
        if (duration === 'custom') {
            $('#custom-video-duration').show();
        } else {
            $('#custom-video-duration').hide();
        }
    });

    // Overlay size change
    $('#overlay_size').change(function() {
        const size = $(this).val();
        if (size === 'custom') {
            $('#custom-overlay-size').show();
        } else {
            $('#custom-overlay-size').hide();
        }
    });

    // Overlay timing change
    $('#overlay_timing').change(function() {
        const timing = $(this).val();
        if (timing === 'custom') {
            $('#custom-overlay-timing').show();
        } else {
            $('#custom-overlay-timing').hide();
        }
    });

    // Custom duration change
    $('#custom_duration').on('input', function() {
        updateCustomDurationDisplay();
    });

    // Image duration changes
    $('#default_image_duration, #transition_duration').on('input', function() {
        calculateTotalDuration();
    });

    // Video info character counters
    $('#video_title').on('input', function() {
        updateCharacterCounter('video_title', 'title-counter', 100);
    });

    $('#video_description').on('input', function() {
        updateCharacterCounter('video_description', 'description-counter');
    });

    $('#tiktok_hashtags').on('input', function() {
        updateCharacterCounter('tiktok_hashtags', null, 100);
    });

    $('#youtube_tags').on('input', function() {
        updateCharacterCounter('youtube_tags', null, 500);
    });

    // Audio source change
    $('input[name="audio_source"]').change(function() {
        const source = $(this).val();
        showAudioSection(source);
    });

    // Subtitle enable/disable
    $('#enable_subtitle').change(function() {
        if ($(this).is(':checked')) {
            $('#subtitle-settings').show();
        } else {
            $('#subtitle-settings').hide();
        }
    });

    // Subtitle source change
    $('input[name="subtitle_source"]').change(function() {
        const source = $(this).val();
        showSubtitleSection(source);
    });

    // Logo enable/disable
    $('#enable_logo').change(function() {
        if ($(this).is(':checked')) {
            $('#logo-settings').show();
        } else {
            $('#logo-settings').hide();
        }
    });

    // Logo source change
    $('input[name="logo_source"]').change(function() {
        const source = $(this).val();
        showLogoSection(source);
    });

    // Logo size change
    $('#logo_size').change(function() {
        const size = $(this).val();
        if (size === 'custom') {
            $('#custom-size-settings').show();
        } else {
            $('#custom-size-settings').hide();
        }
    });

    // Logo duration change
    $('#logo_duration').change(function() {
        const duration = $(this).val();
        if (duration === 'custom') {
            $('#custom-duration-settings').show();
        } else {
            $('#custom-duration-settings').hide();
        }
    });

    // Logo selection from library
    $('.logo-item').click(function() {
        $('.logo-item').removeClass('selected');
        $(this).addClass('selected');
        const logoFile = $(this).data('logo');
        $('#selected_logo').val(logoFile);
    });

    // Batch count change
    $('#batch_count').change(function() {
        const count = parseInt($(this).val());
        generateBatchVideos(count);
    });
}

function updateNewFormAction() {
    const mode = $('#form_mode').val();
    const action = mode === 'batch'
        ? '{{ route("admin.video-generator.generate-batch") }}'
        : '{{ route("admin.video-generator.generate") }}';

    $('#videoGeneratorForm').attr('action', action);
}

function showPlatformSettings(platform) {
    // Hide all platform settings
    $('#tiktok-settings, #youtube-settings, #both-settings').hide();

    // Show selected platform settings
    if (platform === 'tiktok') {
        $('#tiktok-settings').show();
    } else if (platform === 'youtube') {
        $('#youtube-settings').show();
    } else if (platform === 'both') {
        $('#both-settings').show();
    }
}

function showMediaSection(type) {
    // Hide all media sections
    $('#images-section, #video-section, #mixed-section').hide();

    // Show selected section
    if (type === 'images') {
        $('#images-section').show();
    } else if (type === 'video') {
        $('#video-section').show();
    } else if (type === 'mixed') {
        $('#mixed-section').show();
    }

    // Update duration calculation
    calculateTotalDuration();
}

function showMixedModeSettings(mode) {
    // Hide all mixed mode settings
    $('#sequence-mode-settings, #overlay-mode-settings, #split-mode-settings').hide();

    // Show selected mode settings
    if (mode === 'sequence') {
        $('#sequence-mode-settings').show();
    } else if (mode === 'overlay') {
        $('#overlay-mode-settings').show();
    } else if (mode === 'split') {
        $('#split-mode-settings').show();
    }
}

function showDurationSettings(basis) {
    // Hide all duration info
    $('#images-duration-info, #video-duration-info, #audio-duration-info, #custom-duration-info').hide();
    $('#custom-duration-settings').hide();

    // Show selected duration info
    if (basis === 'images') {
        $('#images-duration-info').show();
    } else if (basis === 'video') {
        $('#video-duration-info').show();
    } else if (basis === 'audio') {
        $('#audio-duration-info').show();
    } else if (basis === 'custom') {
        $('#custom-duration-info').show();
        $('#custom-duration-settings').show();
    }
}

function showAudioSection(source) {
    // Hide all audio sections
    $('#tts-section, #upload-audio-section, #no-audio-section').hide();

    // Show selected section
    if (source === 'tts') {
        $('#tts-section').show();
    } else if (source === 'upload') {
        $('#upload-audio-section').show();
    } else if (source === 'none') {
        $('#no-audio-section').show();
    }
}

function showSubtitleSection(source) {
    // Hide all subtitle sections
    $('#auto-subtitle-section, #manual-subtitle-section, #upload-subtitle-section').hide();

    // Show selected section
    if (source === 'auto') {
        $('#auto-subtitle-section').show();
    } else if (source === 'manual') {
        $('#manual-subtitle-section').show();
    } else if (source === 'upload') {
        $('#upload-subtitle-section').show();
    }
}

function showLogoSection(source) {
    // Hide all logo sections
    $('#logo-library-section, #logo-upload-section').hide();

    // Show selected section
    if (source === 'library') {
        $('#logo-library-section').show();
    } else if (source === 'upload') {
        $('#logo-upload-section').show();
    }
}

// Initialize when document ready
$(document).ready(function() {
    // Check if new interface exists
    if ($('#videoGeneratorForm').length > 0) {
        initializeNewVideoGenerator();
    }
});

// Advanced preview functions
function previewImagesAdvanced(input) {
    const files = Array.from(input.files);
    const previewContainer = document.getElementById('images-preview');
    previewContainer.innerHTML = '';

    if (files.length > 0) {
        // Show individual settings
        $('#individual-image-settings').show();
        generateImageItems(files);

        // Create preview grid
        const grid = document.createElement('div');
        grid.className = 'row';

        files.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const col = document.createElement('div');
                col.className = 'col-md-2 col-sm-3 col-4 mb-3';

                const card = document.createElement('div');
                card.className = 'card';

                const img = document.createElement('img');
                img.className = 'card-img-top';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.src = URL.createObjectURL(file);

                const cardBody = document.createElement('div');
                cardBody.className = 'card-body p-2';

                const fileName = document.createElement('small');
                fileName.className = 'text-muted d-block text-truncate';
                fileName.textContent = `${index + 1}. ${file.name}`;

                cardBody.appendChild(fileName);
                card.appendChild(img);
                card.appendChild(cardBody);
                col.appendChild(card);
                grid.appendChild(col);
            }
        });

        previewContainer.appendChild(grid);

        // Calculate total duration
        calculateTotalDuration();
    } else {
        $('#individual-image-settings').hide();
    }
}

function previewMixedMediaAdvanced(input) {
    const files = Array.from(input.files);
    const previewContainer = document.getElementById('mixed-preview');
    previewContainer.innerHTML = '';

    if (files.length > 0) {
        // Create preview with drag & drop reordering
        const container = document.createElement('div');
        container.className = 'mixed-media-container';
        container.innerHTML = '<h6 class="mb-3">Thứ tự hiển thị (kéo thả để sắp xếp):</h6>';

        const itemsContainer = document.createElement('div');
        itemsContainer.className = 'row sortable-media';
        itemsContainer.id = 'sortable-mixed-media';

        files.forEach((file, index) => {
            const col = document.createElement('div');
            col.className = 'col-md-2 col-sm-3 col-4 mb-3';
            col.setAttribute('data-index', index);

            const card = document.createElement('div');
            card.className = 'card media-item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'card-img-top';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.src = URL.createObjectURL(file);
                card.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.className = 'card-img-top';
                video.style.height = '100px';
                video.style.objectFit = 'cover';
                video.src = URL.createObjectURL(file);
                video.muted = true;
                card.appendChild(video);
            }

            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2';

            const fileName = document.createElement('small');
            fileName.className = 'text-muted d-block text-truncate';
            fileName.textContent = `${index + 1}. ${file.name}`;

            const fileType = document.createElement('span');
            fileType.className = `badge badge-${file.type.startsWith('image/') ? 'primary' : 'success'} badge-sm`;
            fileType.textContent = file.type.startsWith('image/') ? 'Ảnh' : 'Video';

            cardBody.appendChild(fileName);
            cardBody.appendChild(fileType);
            card.appendChild(cardBody);
            col.appendChild(card);
            itemsContainer.appendChild(col);
        });

        container.appendChild(itemsContainer);
        previewContainer.appendChild(container);

        // Initialize sortable
        initializeSortable();
    }
}

function generateImageItems(files) {
    const container = document.getElementById('image-items-container');
    container.innerHTML = '';

    files.forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const itemHtml = `
                <div class="card mb-3" id="image-item-${index}">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-image mr-2"></i>Ảnh ${index + 1}: ${file.name}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Thời gian hiển thị (giây)</label>
                                    <input type="number" name="images[${index}][duration]"
                                           class="form-control image-duration" min="0.5" max="30"
                                           step="0.5" value="3" onchange="calculateTotalDuration()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hiệu ứng chuyển cảnh</label>
                                    <select name="images[${index}][transition]" class="form-control">
                                        <option value="fade">Fade</option>
                                        <option value="slide" selected>Slide</option>
                                        <option value="zoom">Zoom</option>
                                        <option value="dissolve">Dissolve</option>
                                        <option value="wipe">Wipe</option>
                                        <option value="none">Không có</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Thời gian hiệu ứng (giây)</label>
                                    <input type="number" name="images[${index}][transition_duration]"
                                           class="form-control" min="0.1" max="2" step="0.1" value="0.5">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', itemHtml);
        }
    });
}

function applyGlobalImageSettings() {
    const defaultDuration = $('#default_image_duration').val();
    const defaultTransition = $('#default_transition_effect').val();
    const transitionDuration = $('#transition_duration').val();

    // Apply to all image items
    $('.image-duration').val(defaultDuration);
    $('select[name*="[transition]"]').val(defaultTransition);
    $('input[name*="[transition_duration]"]').val(transitionDuration);

    calculateTotalDuration();
    showToast('Đã áp dụng cài đặt cho tất cả ảnh', 'success');
}

function toggleIndividualSettings() {
    const container = $('#individual-image-settings');
    if (container.is(':visible')) {
        container.hide();
        showToast('Đã ẩn cài đặt từng ảnh', 'info');
    } else {
        container.show();
        showToast('Đã hiện cài đặt từng ảnh', 'info');
    }
}

function calculateTotalDuration() {
    const mediaType = $('input[name="media_type"]:checked').val();
    const durationBasis = $('input[name="duration_based_on"]:checked').val();

    let totalDuration = 0;

    if (mediaType === 'images' && durationBasis === 'images') {
        // Calculate based on individual image durations
        $('.image-duration').each(function() {
            totalDuration += parseFloat($(this).val()) || 0;
        });

        // Add transition durations
        const transitionDuration = parseFloat($('#transition_duration').val()) || 0;
        const imageCount = $('.image-duration').length;
        if (imageCount > 1) {
            totalDuration += (imageCount - 1) * transitionDuration;
        }

        $('#total-images-duration').text(totalDuration.toFixed(1) + ' giây');
    }

    // Update display
    updateDurationDisplay(totalDuration);
}

function updateDurationDisplay(duration) {
    const minutes = Math.floor(duration / 60);
    const seconds = (duration % 60).toFixed(1);
    const display = minutes > 0 ? `${minutes}:${seconds.padStart(4, '0')}` : `${seconds}s`;

    // Update relevant duration display
    const durationBasis = $('input[name="duration_based_on"]:checked').val();
    if (durationBasis === 'images') {
        $('#total-images-duration').text(display);
    }
}

function updateCustomDurationDisplay() {
    const duration = $('#custom_duration').val();
    $('#custom-duration-display').text(duration + ' giây');
}

function initializeSortable() {
    // Simple drag & drop implementation
    // This would need a library like Sortable.js for full functionality
    console.log('Sortable initialized for mixed media');
}

// Preview functions for new interface
function previewImages(input) {
    previewImagesAdvanced(input);
}

function previewVideo(input) {
    previewFile(input, 'video-preview');
}

function previewAudio(input) {
    previewFile(input, 'audio-preview');
}

function previewMixedMedia(input) {
    previewMixedMediaAdvanced(input);
}

function previewSubtitle(input) {
    previewFile(input, 'subtitle-preview');
}

function previewUploadedLogo(input) {
    const file = input.files[0];
    const preview = document.getElementById('uploaded-logo-preview');

    if (file && preview) {
        const size = (file.size / 1024 / 1024).toFixed(2);
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="card" style="max-width: 200px;">
                    <img src="${e.target.result}" class="card-img-top" style="height: 120px; object-fit: contain;">
                    <div class="card-body p-2">
                        <small class="text-muted">${file.name} (${size} MB)</small>
                    </div>
                </div>
            `;
        };

        reader.readAsDataURL(file);
    }
}

// Logo preset functions
function applyLogoPreset(platform) {
    if (platform === 'tiktok') {
        // TikTok preset: Góc trên phải, kích thước nhỏ, độ mờ 70%
        $('#logo_position').val('top-right');
        $('#logo_size').val('small');
        $('#logo_opacity').val('0.7');
        $('#logo_margin').val('20');
        $('#logo_duration').val('full');

        // Hide custom settings
        $('#custom-size-settings, #custom-duration-settings').hide();

        showToast('Đã áp dụng preset TikTok', 'success');
    } else if (platform === 'youtube') {
        // YouTube preset: Góc dưới phải, kích thước vừa, độ mờ 100%
        $('#logo_position').val('bottom-right');
        $('#logo_size').val('medium');
        $('#logo_opacity').val('1.0');
        $('#logo_margin').val('30');
        $('#logo_duration').val('full');

        // Hide custom settings
        $('#custom-size-settings, #custom-duration-settings').hide();

        showToast('Đã áp dụng preset YouTube', 'success');
    }
}

function resetLogoSettings() {
    // Reset to default values
    $('#logo_position').val('top-right');
    $('#logo_size').val('medium');
    $('#logo_opacity').val('1.0');
    $('#logo_margin').val('20');
    $('#logo_duration').val('full');

    // Clear custom settings
    $('#logo_width').val('100');
    $('#logo_height').val('100');
    $('#logo_start_time').val('0');
    $('#logo_end_time').val('10');

    // Hide custom sections
    $('#custom-size-settings, #custom-duration-settings').hide();

    // Clear logo selection
    $('.logo-item').removeClass('selected');
    $('#selected_logo').val('');

    showToast('Đã reset cài đặt logo', 'info');
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toastClass = type === 'success' ? 'alert-success' :
                      type === 'error' ? 'alert-danger' : 'alert-info';

    const toast = $(`
        <div class="alert ${toastClass} alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);

    $('body').append(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.alert('close');
    }, 3000);
}

// Video info functions
function updateCharacterCounter(inputId, counterId, maxLength = null) {
    const input = document.getElementById(inputId);
    const counter = counterId ? document.getElementById(counterId) : null;

    if (input && counter) {
        const currentLength = input.value.length;
        counter.textContent = currentLength;

        if (maxLength && currentLength > maxLength) {
            counter.style.color = '#dc3545';
            input.style.borderColor = '#dc3545';
        } else {
            counter.style.color = '#6c757d';
            input.style.borderColor = '#ced4da';
        }
    }
}

function previewThumbnail(input) {
    const file = input.files[0];
    const preview = document.getElementById('thumbnail-preview');

    if (file && preview) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="mt-2">
                    <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 120px;">
                    <div class="mt-1">
                        <small class="text-muted">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>
                    </div>
                </div>
            `;
        };

        reader.readAsDataURL(file);
    }
}

function applyTemplate(templateType) {
    const templates = {
        viral: {
            title: '🔥 Video Viral Trending',
            description: 'Nội dung hot trend đang được chia sẻ nhiều nhất! Đừng bỏ lỡ xu hướng này! 🚀\n\n#trending #viral #hot',
            tiktok_hashtags: '#viral #trending #fyp #foryou #hot #xuhuong',
            tiktok_category: 'entertainment',
            youtube_tags: 'viral, trending, hot, xu hướng, nội dung hot',
            youtube_category: '24',
            keywords: 'viral, trending, hot trend, xu hướng'
        },
        educational: {
            title: '📚 Kiến Thức Bổ Ích',
            description: 'Chia sẻ kiến thức hữu ích và thú vị. Học hỏi mỗi ngày để phát triển bản thân! 🎓\n\n#education #knowledge #learning',
            tiktok_hashtags: '#education #knowledge #learning #study #tips #xuhuong',
            tiktok_category: 'education',
            youtube_tags: 'giáo dục, kiến thức, học tập, tips, hướng dẫn',
            youtube_category: '27',
            keywords: 'giáo dục, kiến thức, học tập, tips'
        },
        entertainment: {
            title: '🎭 Giải Trí Vui Nhộn',
            description: 'Nội dung giải trí thú vị, mang lại tiếng cười và niềm vui! 😄\n\n#entertainment #fun #comedy',
            tiktok_hashtags: '#entertainment #fun #comedy #funny #vui #giaitri',
            tiktok_category: 'comedy',
            youtube_tags: 'giải trí, vui nhộn, hài hước, comedy, funny',
            youtube_category: '23',
            keywords: 'giải trí, vui nhộn, hài hước, comedy'
        },
        tutorial: {
            title: '🛠️ Hướng Dẫn Chi Tiết',
            description: 'Hướng dẫn từng bước một cách chi tiết và dễ hiểu. Theo dõi để học được kỹ năng mới! 💡\n\n#tutorial #howto #guide',
            tiktok_hashtags: '#tutorial #howto #guide #tips #huongdan #diy',
            tiktok_category: 'education',
            youtube_tags: 'hướng dẫn, tutorial, how to, tips, thủ thuật',
            youtube_category: '26',
            keywords: 'hướng dẫn, tutorial, how to, tips'
        }
    };

    const template = templates[templateType];
    if (template) {
        $('#video_title').val(template.title);
        $('#video_description').val(template.description);
        $('#tiktok_hashtags').val(template.tiktok_hashtags);
        $('#tiktok_category').val(template.tiktok_category);
        $('#youtube_tags').val(template.youtube_tags);
        $('#youtube_category').val(template.youtube_category);
        $('#video_keywords').val(template.keywords);

        // Update character counters
        updateCharacterCounter('video_title', 'title-counter', 100);
        updateCharacterCounter('video_description', 'description-counter');

        showToast(`Đã áp dụng mẫu ${templateType}`, 'success');
    }
}

function clearVideoInfo() {
    // Clear all video info fields
    $('#video_title, #video_description, #tiktok_hashtags, #youtube_tags, #video_keywords, #video_location').val('');
    $('#tiktok_category, #youtube_category, #tiktok_privacy, #youtube_privacy, #youtube_language, #video_license').val('');

    // Reset checkboxes
    $('#tiktok_allow_comments, #tiktok_allow_duet, #tiktok_allow_stitch, #youtube_allow_comments, #youtube_allow_ratings, #youtube_notify_subscribers, #video_made_for_kids').prop('checked', false);

    // Reset some defaults
    $('#tiktok_privacy').val('public');
    $('#youtube_privacy').val('public');
    $('#youtube_language').val('vi');
    $('#video_license').val('standard');
    $('#tiktok_allow_comments, #tiktok_allow_duet, #tiktok_allow_stitch, #youtube_allow_comments, #youtube_allow_ratings').prop('checked', true);

    // Clear thumbnail preview
    $('#thumbnail-preview').html('');
    $('#video_thumbnail').val('');

    // Update character counters
    updateCharacterCounter('video_title', 'title-counter', 100);
    updateCharacterCounter('video_description', 'description-counter');

    showToast('Đã xóa tất cả thông tin video', 'info');
}

// Video list filtering
function filterVideos(type) {
    const buttons = document.querySelectorAll('.btn-group button');
    buttons.forEach(btn => btn.classList.remove('active'));

    event.target.classList.add('active');

    if (type === 'all') {
        $('#tiktok-videos, #youtube-videos').show();
    } else if (type === 'tiktok') {
        $('#tiktok-videos').show();
        $('#youtube-videos').hide();
    } else if (type === 'youtube') {
        $('#youtube-videos').show();
        $('#tiktok-videos').hide();
    }
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

/* Logo gallery styling */
.logo-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.logo-item .card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.logo-item:hover .card {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.logo-item.selected .card {
    border-color: #28a745;
    background-color: #f8fff8;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.logo-item .logo-preview {
    height: 80px;
    object-fit: contain;
    background: #f8f9fa;
    padding: 10px;
}

.logo-item.selected .logo-preview {
    background: #e8f5e8;
}

/* Logo position preview */
.logo-position-preview {
    width: 200px;
    height: 120px;
    border: 2px dashed #dee2e6;
    position: relative;
    background: #f8f9fa;
    margin: 10px 0;
}

.logo-position-preview .logo-dot {
    width: 20px;
    height: 20px;
    background: #007bff;
    border-radius: 50%;
    position: absolute;
    transition: all 0.3s ease;
}

.logo-position-preview .logo-dot.top-left { top: 10px; left: 10px; }
.logo-position-preview .logo-dot.top-right { top: 10px; right: 10px; }
.logo-position-preview .logo-dot.top-center { top: 10px; left: 50%; transform: translateX(-50%); }
.logo-position-preview .logo-dot.bottom-left { bottom: 10px; left: 10px; }
.logo-position-preview .logo-dot.bottom-right { bottom: 10px; right: 10px; }
.logo-position-preview .logo-dot.bottom-center { bottom: 10px; left: 50%; transform: translateX(-50%); }
.logo-position-preview .logo-dot.center { top: 50%; left: 50%; transform: translate(-50%, -50%); }
.logo-position-preview .logo-dot.center-left { top: 50%; left: 10px; transform: translateY(-50%); }
.logo-position-preview .logo-dot.center-right { top: 50%; right: 10px; transform: translateY(-50%); }

/* Advanced media styling */
.mixed-media-container {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    background: #f8f9fa;
}

.media-item {
    cursor: move;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.media-item:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.media-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.sortable-media {
    min-height: 120px;
}

/* Individual image settings */
#individual-image-settings {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    background: #fff;
}

#individual-image-settings .card {
    border-left: 4px solid #007bff;
}

/* Duration info styling */
#duration-info {
    border-left: 4px solid #17a2b8;
    background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
}

#duration-info .alert {
    margin-bottom: 0;
}

/* Mixed mode settings */
.mixed-mode-settings .card {
    border-left: 4px solid #28a745;
}

/* Overlay position preview */
.overlay-position-preview {
    width: 150px;
    height: 90px;
    border: 2px solid #dee2e6;
    position: relative;
    background: #000;
    margin: 10px 0;
    border-radius: 4px;
}

.overlay-position-preview::after {
    content: 'Video';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #fff;
    font-size: 12px;
}

.overlay-position-preview .overlay-demo {
    position: absolute;
    width: 30px;
    height: 20px;
    background: rgba(0, 123, 255, 0.8);
    border: 1px solid #007bff;
    border-radius: 2px;
}

.overlay-position-preview .overlay-demo.top-left { top: 5px; left: 5px; }
.overlay-position-preview .overlay-demo.top-right { top: 5px; right: 5px; }
.overlay-position-preview .overlay-demo.top-center { top: 5px; left: 50%; transform: translateX(-50%); }
.overlay-position-preview .overlay-demo.bottom-left { bottom: 5px; left: 5px; }
.overlay-position-preview .overlay-demo.bottom-right { bottom: 5px; right: 5px; }
.overlay-position-preview .overlay-demo.bottom-center { bottom: 5px; left: 50%; transform: translateX(-50%); }
.overlay-position-preview .overlay-demo.center { top: 50%; left: 50%; transform: translate(-50%, -50%); }

/* Animation for duration updates */
.duration-update {
    animation: highlightDuration 0.5s ease-in-out;
}

@keyframes highlightDuration {
    0% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}

/* Video info section styling */
.card.border-primary .card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.card.border-danger .card-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.character-counter {
    font-weight: 500;
    transition: color 0.3s ease;
}

.character-counter.warning {
    color: #ffc107 !important;
}

.character-counter.danger {
    color: #dc3545 !important;
}

#thumbnail-preview img {
    border: 2px solid #dee2e6;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

#thumbnail-preview img:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

/* Platform-specific styling */
.tiktok-section {
    border-left: 4px solid #000;
}

.youtube-section {
    border-left: 4px solid #ff0000;
}

/* Template buttons */
.template-buttons .btn {
    margin: 2px;
    transition: all 0.3s ease;
}

.template-buttons .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Form validation styling */
.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* SEO section */
.seo-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0.375rem;
    padding: 1rem;
}

/* Quick templates */
.quick-templates {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
}

.quick-templates .btn-group {
    flex-wrap: wrap;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .mixed-media-container .col-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    #individual-image-settings {
        max-height: 300px;
    }

    .overlay-position-preview {
        width: 120px;
        height: 72px;
    }

    .card.border-primary,
    .card.border-danger {
        margin-bottom: 1rem;
    }

    .template-buttons .btn {
        margin-bottom: 0.5rem;
        width: 100%;
    }

    #thumbnail-preview img {
        max-width: 100%;
        height: auto;
    }
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
