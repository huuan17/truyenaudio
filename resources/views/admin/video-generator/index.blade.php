@extends('layouts.app')

@section('title', 'Tạo Video Đa Nền Tảng')

@push('styles')
<style>
.logo-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.logo-item:hover {
    transform: scale(1.05);
}

.logo-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.logo-item:hover {
    background-color: #f8f9fa !important;
    border-color: #007bff !important;
}

.logo-item.bg-light {
    background-color: #e3f2fd !important;
    border-color: #007bff !important;
}

.logo-results {
    background: #fff;
}

.logo-results .logo-item {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.logo-results .logo-item:hover {
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.selected-logo-info {
    margin-top: 0.5rem;
}

.logo-preview {
    height: 80px;
    object-fit: contain;
    background: #f8f9fa;
}
</style>
<style>
/* Shared small channel row styling */
.channel-row { display: flex; align-items: center; }
.channel-row label { margin-bottom: 0; margin-right: .5rem; }
.channel-row .flex-grow-1 select.form-control-sm { min-height: 31px; }
</style>

@endpush

@push('head-scripts')
<!-- Ensure jQuery is available for this page -->
<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script>
if (typeof jQuery !== 'undefined') {
    window.$ = jQuery;
    console.log('✅ jQuery loaded in head for video generator');
} else {
    console.error('❌ jQuery failed to load in head');
}
</script>
@endpush

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
                        <a href="{{ route('admin.video-templates.index') }}" class="btn btn-warning btn-sm mr-2">
                            <i class="fas fa-layer-group mr-1"></i>Templates
                        </a>
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
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Muốn tạo nhiều video?</strong>
                                Sử dụng <a href="{{ route('admin.video-templates.index') }}" class="alert-link">
                                    <i class="fas fa-layer-group mr-1"></i>Template Video
                                </a> để tạo nhiều video với cùng format một cách hiệu quả hơn.
                            </div>
                        </div>
                    </div>

                    <!-- Main Video Generation Form -->
                    <form id="videoGeneratorForm" method="POST" action="{{ route('admin.video-generator.generate') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="mode" id="form_mode" value="single">

                        <!-- Single Video Sections -->
                        <div id="single-video-sections">
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
                                    @include('admin.video-generator.partials.existing-videos')
                                </div>
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
<!-- Debug script loading -->
<script>
console.log('🔧 Video Generator: Script section loading...');
console.log('🔧 Document ready state:', document.readyState);
</script>

<!-- Video Preview Script -->
<script src="{{ asset('js/video-preview.js') }}"></script>

{{-- Load page-specific JavaScript --}}
<script src="{{ asset('assets/js/video-generator-page.js') }}?v={{ filemtime(public_path('assets/js/video-generator-page.js')) }}"></script>

<script>
// Debug script loading
console.log('🔧 Video Generator inline script loading...');
console.log('🔧 jQuery available:', typeof jQuery !== 'undefined');
console.log('🔧 $ available:', typeof $ !== 'undefined');

// Page-specific configuration
window.routes = {
    videoGeneratorGenerate: '{{ route("admin.video-generator.generate") }}',
    videoGeneratorStatus: '{{ route("admin.video-generator.status") }}',
    videoGeneratorDelete: '{{ route("admin.video-generator.delete") }}',
    audioLibraryForVideoGenerator: '{{ route("admin.audio-library.for-video-generator") }}'
};

// Initialize page when ready - NO JQUERY DEPENDENCY
function initVideoGeneratorPreview() {
    console.log('✅ Video Generator page loaded with routes:', window.routes);

    // Initialize preview for video generator using vanilla JS
    if (typeof VideoPreview !== 'undefined') {
        try {
            window.videoPreview = new VideoPreview({
                containerSelector: '.col-lg-4',
                insertPosition: 'beforeend',
                formType: 'generator',
                platform: 'auto',
                customSelectors: {
                    images: ['input[name="product_images[]"]', 'input[name="background_images[]"]'],
                    audio: ['input[name="background_audio"]', 'input[name="audio_file"]'],
                    subtitle: ['textarea[name="script_text"]', 'textarea[name="content"]'],
                    tts: ['textarea[name="script_text"]', 'textarea[name="content"]']
                }
            });
            console.log('✅ VideoPreview initialized');
        } catch (error) {
            console.log('⚠️ VideoPreview initialization failed:', error);
        }
    } else {
        console.log('⚠️ VideoPreview not available');
    }
}

// Start initialization using vanilla JS
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVideoGeneratorPreview);
} else {
    initVideoGeneratorPreview();
}

// Audio source toggle functionality
$(document).ready(function() {
    // Toggle audio source sections
    $('input[name="audio_source"]').change(function() {
        const source = $(this).val();

        // Hide all audio sections first
        $('#tts-section, #upload-audio-section, #library-audio-section, #video-original-audio-section, #no-audio-section').hide();

        // Show selected section
        if (source === 'tts') {
            $('#tts-section').show();
        } else if (source === 'upload') {
            $('#upload-audio-section').show();
        } else if (source === 'library') {
            $('#library-audio-section').show();
        } else if (source === 'video_original') {
            $('#video-original-audio-section').show();
        } else if (source === 'none') {
            $('#no-audio-section').show();
        }
    });

    // Initialize default state - show TTS section
    $('#tts-section').show();
    $('#upload-audio-section, #library-audio-section, #video-original-audio-section, #no-audio-section').hide();

    // Form submission handler
    $('#videoGeneratorForm').on('submit', function(e) {
        // Debug form data before submission
        const formData = new FormData(this);
        const libraryAudioId = formData.get('library_audio_id');

        // Validate logo if enabled
        const enableLogo = document.getElementById('enable_logo');
        if (enableLogo && enableLogo.checked) {
            const logoSource = document.querySelector('input[name="logo_source"]:checked');
            const selectedLogo = document.getElementById('selected_logo');
            const logoFile = document.getElementById('logo_file');

            if (!logoSource) {
                e.preventDefault();
                alert('Vui lòng chọn nguồn logo (thư viện hoặc upload)');
                return false;
            }

            if (logoSource.value === 'library') {
                if (!selectedLogo || !selectedLogo.value) {
                    e.preventDefault();
                    alert('Vui lòng chọn logo từ thư viện');
                    return false;
                }
            } else if (logoSource.value === 'upload') {
                if (!logoFile || !logoFile.files.length) {
                    e.preventDefault();
                    alert('Vui lòng chọn file logo để upload');
                    return false;
                }
            }
        }

        console.log('🚀 Form submitted with data:', {
            library_audio_id: libraryAudioId,
            library_audio_id_type: typeof libraryAudioId,
            library_audio_id_empty: !libraryAudioId,
            audio_source: formData.get('audio_source'),
            form_has_library_input: $('#library_audio_id').length > 0,
            input_value: $('#library_audio_id').val(),
            enable_logo: formData.get('enable_logo'),
            logo_source: formData.get('logo_source'),
            selected_logo: formData.get('selected_logo')
        });
    });
});

// Define refreshVideoStatus function to prevent errors
function refreshVideoStatus() {
    console.log('🔄 refreshVideoStatus called (placeholder)');
    // This function can be implemented later if needed for auto-refresh functionality
}

// Subtitle toggle functionality
$(document).ready(function() {
    // Toggle subtitle settings when checkbox is clicked
    $('#enable_subtitle').change(function() {
        if ($(this).is(':checked')) {
            $('#subtitle-settings').slideDown();
        } else {
            $('#subtitle-settings').slideUp();
        }
    });

    // Toggle subtitle source sections
    $('input[name="subtitle_source"]').change(function() {
        const source = $(this).val();

        // Hide all sections first
        $('#auto-subtitle-section, #manual-subtitle-section, #upload-subtitle-section').hide();

        // Show selected section
        if (source === 'auto') {
            $('#auto-subtitle-section').show();
        } else if (source === 'manual') {
            $('#manual-subtitle-section').show();
        } else if (source === 'upload') {
            $('#upload-subtitle-section').show();
        }
    });

    // Toggle timing settings based on timing mode
    $('input[name="subtitle_timing_mode"]').change(function() {
        const mode = $(this).val();

        // Hide all timing sections first
        $('#image-sync-settings, #custom-timing-settings').hide();

        // Show selected timing section
        if (mode === 'image_sync') {
            $('#image-sync-settings').show();
        } else if (mode === 'custom_timing') {
            $('#custom-timing-settings').show();
        }
    });

    // Initialize default state
    $('#subtitle-settings').hide();
    $('#manual-subtitle-section, #upload-subtitle-section').hide();
    $('#image-sync-settings, #custom-timing-settings').hide();
});

// Preview audio file function
function previewAudio(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById('audio-preview');

        // Create audio element for preview
        const audioUrl = URL.createObjectURL(file);
        preview.innerHTML = `
            <div class="alert alert-success">
                <strong>File đã chọn:</strong> ${file.name}<br>
                <strong>Kích thước:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                <audio controls class="mt-2" style="width: 100%;">
                    <source src="${audioUrl}" type="${file.type}">
                    Trình duyệt không hỗ trợ audio preview.
                </audio>
            </div>
        `;
    }
}

// Search audio library function
function searchAudioLibrary() {
    const search = $('#library-search').val();
    const category = $('#library-category').val();
    const resultsContainer = $('#library-results');

    // Show loading
    resultsContainer.html(`
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
            <p>Đang tìm kiếm...</p>
        </div>
    `);

    // Make AJAX request to search audio library
    $.ajax({
        url: '{{ route("admin.audio-library.for-video-generator") }}',
        method: 'GET',
        data: {
            search: search,
            category: category
        },
        success: function(response) {
            console.log('🎵 Audio library response:', response);
            if (response.success && response.data.length > 0) {
                let html = '';
                response.data.forEach(function(audio) {
                    html += `
                        <div class="audio-item border-bottom py-2" style="cursor: pointer;" onclick="selectAudio(${audio.id}, '${audio.title}', '${audio.url}')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${audio.title}</strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock mr-1"></i>${audio.duration || 'N/A'}
                                        <i class="fas fa-tag ml-2 mr-1"></i>${audio.category || 'N/A'}
                                        ${audio.voice_type ? '<i class="fas fa-microphone ml-2 mr-1"></i>' + audio.voice_type : ''}
                                    </small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); playAudio('${audio.url}')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                resultsContainer.html(html);
            } else {
                resultsContainer.html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p>Không tìm thấy audio nào</p>
                    </div>
                `);
            }
        },
        error: function() {
            resultsContainer.html(`
                <div class="text-center text-danger py-3">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Lỗi khi tìm kiếm</p>
                </div>
            `);
        }
    });
}

// Select audio from library
function selectAudio(id, title, url) {
    $('#library_audio_id').val(id);
    $('#selected-audio-title').text(title);
    $('#selected-audio-info').show();

    // Highlight selected item
    $('.audio-item').removeClass('bg-light');
    event.currentTarget.classList.add('bg-light');

    console.log('🎵 Audio selected:', {
        id: id,
        title: title,
        url: url,
        input_value: $('#library_audio_id').val()
    });
}

// Clear selected audio
function clearSelectedAudio() {
    $('#library_audio_id').val('');
    $('#selected-audio-info').hide();
    $('.audio-item').removeClass('bg-light');
}

// Play audio preview
function playAudio(url) {
    console.log('🎵 Playing audio:', url);

    // Stop any currently playing audio
    if (window.currentAudio) {
        window.currentAudio.pause();
        window.currentAudio.currentTime = 0;
    }

    // Create temporary audio element to play
    window.currentAudio = new Audio(url);

    // Add event listeners for debugging
    window.currentAudio.addEventListener('loadstart', function() {
        console.log('🎵 Audio loading started');
    });

    window.currentAudio.addEventListener('canplay', function() {
        console.log('🎵 Audio can play');
    });

    window.currentAudio.addEventListener('error', function(e) {
        console.error('🎵 Audio error:', e);
        alert('Lỗi khi tải audio: ' + url);
    });

    window.currentAudio.play().catch(function(error) {
        console.error('🎵 Cannot play audio:', error);
        alert('Không thể phát audio preview: ' + error.message);
    });
}

// Preview subtitle file function
function previewSubtitle(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const content = e.target.result;
            const preview = document.getElementById('subtitle-preview');

            // Show first few lines of SRT file
            const lines = content.split('\n').slice(0, 10);
            preview.innerHTML = `
                <div class="alert alert-success">
                    <strong>Preview file SRT:</strong><br>
                    <pre style="font-size: 12px; max-height: 200px; overflow-y: auto;">${lines.join('\n')}</pre>
                </div>
            `;
        };

        reader.readAsText(file);
    }
}

// Logo functionality
function toggleLogoSettings() {
    const enableLogo = document.getElementById('enable_logo');
    const logoSettings = document.getElementById('logo-settings');

    if (enableLogo.checked) {
        logoSettings.style.display = 'block';
    } else {
        logoSettings.style.display = 'none';
    }
}

function toggleLogoSource() {
    const logoSource = document.querySelector('input[name="logo_source"]:checked').value;
    const librarySection = document.getElementById('logo-library-section');
    const uploadSection = document.getElementById('logo-upload-section');

    if (logoSource === 'library') {
        librarySection.style.display = 'block';
        uploadSection.style.display = 'none';
    } else {
        librarySection.style.display = 'none';
        uploadSection.style.display = 'block';
    }
}

// Select logo from library (similar to selectAudio)
function selectLogo(logoName, logoDisplayName, logoUrl) {
    $('#selected_logo').val(logoName);
    $('#selected-logo-name').text(logoDisplayName || logoName);
    $('#selected-logo-info').show();

    // Highlight selected item
    $('.logo-item').removeClass('bg-light');
    event.currentTarget.classList.add('bg-light');

    console.log('🎨 Logo selected:', {
        name: logoName,
        display_name: logoDisplayName,
        url: logoUrl,
        input_value: $('#selected_logo').val()
    });
}

// Clear selected logo (similar to clearSelectedAudio)
function clearSelectedLogo() {
    $('#selected_logo').val('');
    $('#selected-logo-info').hide();
    $('.logo-item').removeClass('bg-light');
}

function toggleCustomSize() {
    const logoSize = document.getElementById('logo_size').value;
    const customSettings = document.getElementById('custom-size-settings');

    if (logoSize === 'custom') {
        customSettings.style.display = 'block';
    } else {
        customSettings.style.display = 'none';
    }
}

function toggleCustomDuration() {
    const logoDuration = document.getElementById('logo_duration').value;
    const customSettings = document.getElementById('custom-duration-settings');

    if (logoDuration === 'custom') {
        customSettings.style.display = 'block';
    } else {
        customSettings.style.display = 'none';
    }
}

function previewUploadedLogo(input) {
    const preview = document.getElementById('uploaded-logo-preview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="card" style="max-width: 200px;">
                    <img src="${e.target.result}" class="card-img-top" alt="Uploaded logo preview">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted">Logo đã upload</small>
                    </div>
                </div>
            `;
        };

        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}

function applyLogoPreset(platform) {
    if (platform === 'tiktok') {
        document.getElementById('logo_position').value = 'top-right';
        document.getElementById('logo_size').value = 'small';
        document.getElementById('logo_opacity').value = '0.7';
        document.getElementById('logo_margin').value = '20';
        document.getElementById('logo_duration').value = 'full';
    } else if (platform === 'youtube') {
        document.getElementById('logo_position').value = 'bottom-right';
        document.getElementById('logo_size').value = 'medium';
        document.getElementById('logo_opacity').value = '1.0';
        document.getElementById('logo_margin').value = '30';
        document.getElementById('logo_duration').value = 'full';
    }

    // Hide custom settings
    document.getElementById('custom-size-settings').style.display = 'none';
    document.getElementById('custom-duration-settings').style.display = 'none';
}

function resetLogoSettings() {
    document.getElementById('enable_logo').checked = false;
    document.getElementById('selected_logo').value = '';
    document.getElementById('logo_position').value = 'top-right';
    document.getElementById('logo_size').value = 'medium';
    document.getElementById('logo_opacity').value = '1.0';
    document.getElementById('logo_margin').value = '20';
    document.getElementById('logo_duration').value = 'full';

    // Remove active class from all logos
    document.querySelectorAll('.logo-item').forEach(item => {
        item.classList.remove('active');
    });

    // Hide logo settings
    toggleLogoSettings();
}

// Search logo library function (similar to searchAudioLibrary)
function searchLogoLibrary() {
    const search = $('#logo-search').val();
    const filter = $('#logo-filter').val();
    const resultsContainer = $('#logo-results');

    // Show loading
    resultsContainer.html(`
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
            <p>Đang tìm kiếm logo...</p>
        </div>
    `);

    // Make AJAX request to search logo library
    $.ajax({
        url: '{{ route("admin.logos.api") }}',
        method: 'GET',
        data: {
            search: search,
            filter: filter
        },
        success: function(logos) {
            if (logos.length > 0) {
                let html = '<div class="row">';
                logos.forEach(function(logo) {
                    // Get file extension for filtering
                    const extension = logo.name.split('.').pop().toLowerCase();

                    // Apply filter if set
                    if (filter && extension !== filter) {
                        return;
                    }

                    // Apply search filter if set
                    if (search && !logo.display_name.toLowerCase().includes(search.toLowerCase())) {
                        return;
                    }

                    html += `
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="logo-item border rounded p-2" style="cursor: pointer;" onclick="selectLogo('${logo.name}', '${logo.display_name}', '${logo.url}')">
                                <div class="text-center">
                                    <img src="${logo.url}" alt="${logo.display_name}"
                                         style="max-width: 100%; max-height: 80px; object-fit: contain;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div style="display: none; height: 80px;" class="d-flex align-items-center justify-content-center bg-light">
                                        <small class="text-muted">Error loading</small>
                                    </div>
                                    <div class="mt-2">
                                        <small><strong>${logo.display_name}</strong></small><br>
                                        <small class="text-muted">${extension.toUpperCase()}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultsContainer.html(html);
            } else {
                resultsContainer.html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p>Không tìm thấy logo nào</p>
                    </div>
                `);
            }
        },
        error: function() {
            resultsContainer.html(`
                <div class="text-center text-danger py-3">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Lỗi khi tìm kiếm logo</p>
                </div>
            `);
        }
    });
}

// Initialize logo functionality
document.addEventListener('DOMContentLoaded', function() {
    // Logo enable/disable
    document.getElementById('enable_logo').addEventListener('change', toggleLogoSettings);

    // Logo source radio buttons
    document.querySelectorAll('input[name="logo_source"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleLogoSource();
            // Auto-load logo library when "library" is selected
            if (this.value === 'library') {
                searchLogoLibrary();
            }
        });
    });

    // Logo size dropdown
    document.getElementById('logo_size').addEventListener('change', toggleCustomSize);

    // Logo duration dropdown
    document.getElementById('logo_duration').addEventListener('change', toggleCustomDuration);

    // Initialize states
    toggleLogoSettings();
    toggleLogoSource();
    toggleCustomSize();
    toggleCustomDuration();
});

// Update volume display for video original audio
function updateVolumeDisplay(value) {
    document.getElementById('volume-display').textContent = value + '%';
}

// Media type toggle functionality
$(document).ready(function() {
    // Toggle media type sections
    $('input[name="media_type"]').change(function() {
        const mediaType = $(this).val();

        // Hide all media sections first
        $('#images-section, #video-section, #mixed-section').hide();

        // Show selected section
        if (mediaType === 'images') {
            $('#images-section').show();
        } else if (mediaType === 'video') {
            $('#video-section').show();
        } else if (mediaType === 'mixed') {
            $('#mixed-section').show();
        }

        // Update audio source availability
        updateAudioSourceAvailability(mediaType);
    });

    // Initialize default state - show images section
    $('#images-section').show();
    $('#video-section, #mixed-section').hide();
});

// Update audio source availability based on media type
function updateAudioSourceAvailability(mediaType) {
    const videoOriginalOption = $('input[name="audio_source"][value="video_original"]').closest('label');

    if (mediaType === 'video' || mediaType === 'mixed') {
        // Enable video original audio option
        videoOriginalOption.removeClass('disabled').show();
        videoOriginalOption.find('input').prop('disabled', false);
    } else {
        // Disable video original audio option
        videoOriginalOption.addClass('disabled').hide();
        videoOriginalOption.find('input').prop('disabled', true);

        // If currently selected, switch to TTS
        if ($('input[name="audio_source"][value="video_original"]').is(':checked')) {
            $('input[name="audio_source"][value="tts"]').prop('checked', true).trigger('change');
        }
    }
}
</script>
@endpush
