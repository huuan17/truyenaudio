/**
 * Video Generator Page Specific JavaScript
 * This file contains page-specific functionality for video generator
 */

// Global variables
let currentPlatform = 'tiktok';
let videoItemCounter = 0;
let youtubeVideoItemCounter = 0;
let batchVideoCount = 3;

// Initialize video generator page
function initializeVideoGeneratorPage() {
    console.log('Initializing Video Generator Page...');
    
    setupEventListeners();
    updateFormAction();
    
    // Set initial display based on default mode (batch)
    const defaultMode = $('input[name="video_mode"]:checked').val();
    if (defaultMode === 'batch') {
        $('#batch-section').show();
        $('#single-video-sections').hide();
    } else {
        $('#batch-section').hide();
        $('#single-video-sections').show();
    }

    // Initialize platform UI
    updatePlatformUI('tiktok');
    
    // Initialize output name previews
    updateOutputPreview('tiktok', 'video');
    updateOutputPreview('youtube');
    
    // Check for URL parameters to pre-select audio
    checkUrlParameters();
    
    // Auto-refresh status every 30 seconds
    setInterval(function() {
        refreshVideoStatus();
    }, 30000);

    // Generate batch videos on load with a small delay
    setTimeout(function() {
        generateBatchVideos(3); // Default 3 videos
    }, 100);
}

function setupEventListeners() {
    // Platform switching
    $('#platformTabs a').on('click', function (e) {
        e.preventDefault();
        const platform = $(this).attr('href').substring(1);
        currentPlatform = platform;
        $(this).tab('show');
        
        // Update any platform-specific UI
        updatePlatformUI(platform);
    });

    // Video mode change
    $('input[name="video_mode"]').change(function() {
        const mode = $(this).val();
        $('#form_mode').val(mode);

        if (mode === 'batch') {
            $('#batch-section').show();
            $('#single-video-sections').hide();
            // Generate batch videos when switching to batch mode
            if ($('#batch-videos-container').children().length === 0) {
                generateBatchVideos(batchVideoCount);
            }
        } else {
            $('#batch-section').hide();
            $('#single-video-sections').show();
        }

        updateFormAction();
    });

    // Platform change
    $('input[name="platform"]').change(function() {
        const platform = $(this).val();
        showPlatformSettings(platform);
        updateFormAction();
    });

    // Media type change
    $('input[name="media_type"]').change(function() {
        const type = $(this).val();
        showMediaSection(type);
    });

    // Audio source change
    $('input[name="audio_source"]').change(function() {
        const source = $(this).val();
        showAudioSection(source);
    });

    // Form submit handler
    $('#videoGeneratorForm').on('submit', function(e) {
        e.preventDefault();

        // Validate required fields
        if (!validateForm()) {
            return false;
        }

        // Show loading state
        const submitBtn = $('#generateBtn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tạo video...');

        // Submit form
        this.submit();

        // Reset button after 10 seconds (in case of error)
        setTimeout(function() {
            submitBtn.prop('disabled', false).html(originalText);
        }, 10000);
    });

    // Batch count change
    $('#batch_count').change(function() {
        const count = parseInt($(this).val());
        generateBatchVideos(count);
    });
}

function updatePlatformUI(platform) {
    // Update form action URLs
    if (platform === 'tiktok') {
        $('#tiktok-single-form').attr('action', window.routes?.videoGeneratorGenerate || '');
        $('#tiktok-batch-form').attr('action', window.routes?.videoGeneratorGenerateBatch || '');
    } else {
        $('#youtube-single-form').attr('action', window.routes?.videoGeneratorGenerate || '');
        $('#youtube-batch-form').attr('action', window.routes?.videoGeneratorGenerateBatch || '');
    }
}

function updateFormAction() {
    const mode = $('#form_mode').val();
    const action = mode === 'batch'
        ? (window.routes?.videoGeneratorGenerateBatch || '')
        : (window.routes?.videoGeneratorGenerate || '');

    console.log('Updating form action, mode:', mode, 'action:', action);
    $('#videoGeneratorForm').attr('action', action);
    console.log('Form action updated to:', $('#videoGeneratorForm').attr('action'));
}

// Audio Library Functions
function searchAudioLibrary() {
    const search = $('#library-search').val();
    const category = $('#library-category').val();

    $.ajax({
        url: window.routes?.audioLibraryForVideoGenerator || '',
        method: 'GET',
        data: {
            search: search,
            category: category,
            max_duration: 3600 // 1 hour max for video generation
        },
        success: function(response) {
            if (response.success) {
                displayAudioLibraryResults(response.data);
            }
        },
        error: function() {
            $('#library-results').html(`
                <div class="text-center text-danger py-3">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Lỗi khi tải thư viện audio</p>
                </div>
            `);
        }
    });
}

function displayAudioLibraryResults(audioFiles) {
    const resultsContainer = $('#library-results');

    if (audioFiles.length === 0) {
        resultsContainer.html(`
            <div class="text-center text-muted py-3">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p>Không tìm thấy audio nào</p>
            </div>
        `);
        return;
    }

    let html = '';
    audioFiles.forEach(function(audio) {
        html += `
            <div class="audio-item border-bottom py-2" style="cursor: pointer;" onclick="selectAudioFromLibrary(${audio.id}, '${audio.title}', '${audio.url}', this)">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${audio.title}</h6>
                        <small class="text-muted">
                            <i class="fas fa-clock mr-1"></i>${audio.duration}
                            <span class="ml-2">
                                <i class="fas fa-tag mr-1"></i>${audio.category}
                            </span>
                            ${audio.voice_type ? `<span class="ml-2"><i class="fas fa-user mr-1"></i>${audio.voice_type}</span>` : ''}
                        </small>
                    </div>
                    <div class="ml-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); playAudioPreview('${audio.url}')">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    resultsContainer.html(html);
}

function selectAudioFromLibrary(audioId, audioTitle, audioUrl, element = null) {
    $('#library_audio_id').val(audioId);
    $('#selected-audio-title').text(audioTitle);
    $('#selected-audio-info').show();

    // Clear upload file input
    $('#audio_file').val('');

    // Highlight selected item
    $('.audio-item').removeClass('bg-light');
    if (element) {
        element.classList.add('bg-light');
    } else if (event && event.currentTarget) {
        event.currentTarget.classList.add('bg-light');
    }
}

function clearSelectedAudio() {
    $('#library_audio_id').val('');
    $('#selected-audio-info').hide();
    $('.audio-item').removeClass('bg-light');
}

function playAudioPreview(audioUrl) {
    // Stop any currently playing audio
    $('audio').each(function() {
        this.pause();
        this.currentTime = 0;
    });

    // Create and play new audio
    const audio = new Audio(audioUrl);
    audio.play().catch(function(error) {
        console.log('Audio play failed:', error);
    });

    // Stop after 10 seconds
    setTimeout(function() {
        audio.pause();
    }, 10000);
}

// Check URL parameters for pre-selection
function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const audioSource = urlParams.get('audio_source');
    const libraryAudioId = urlParams.get('library_audio_id');

    if (audioSource && libraryAudioId) {
        // Select audio source
        $('input[name="audio_source"][value="' + audioSource + '"]').prop('checked', true).trigger('change');

        // Pre-select library audio
        if (audioSource === 'library') {
            setTimeout(function() {
                preSelectLibraryAudio(libraryAudioId);
            }, 500);
        }
    }
}

// Pre-select audio from library
function preSelectLibraryAudio(audioId) {
    $.ajax({
        url: window.routes?.audioLibraryForVideoGenerator || '',
        method: 'GET',
        data: { search: '', category: '' },
        success: function(response) {
            if (response.success) {
                displayAudioLibraryResults(response.data);

                // Find and select the specific audio
                const targetAudio = response.data.find(audio => audio.id == audioId);
                if (targetAudio) {
                    selectAudioFromLibrary(targetAudio.id, targetAudio.title, targetAudio.url);
                }
            }
        }
    });
}

// Initialize when document ready
$(document).ready(function() {
    console.log('Document ready, checking for video generator form...');
    
    // Check if video generator form exists
    if ($('#videoGeneratorForm').length > 0) {
        console.log('Video generator form found, initializing...');
        initializeVideoGeneratorPage();
    } else {
        console.log('Video generator form not found');
    }
});

// Export functions for global access
window.VideoGeneratorPage = {
    init: initializeVideoGeneratorPage,
    searchAudioLibrary: searchAudioLibrary,
    selectAudioFromLibrary: selectAudioFromLibrary,
    clearSelectedAudio: clearSelectedAudio,
    playAudioPreview: playAudioPreview
};
