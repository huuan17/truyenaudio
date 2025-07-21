<!-- YouTube-specific JavaScript -->
<script>
// YouTube mode switching
function switchYoutubeMode(mode) {
    if (mode === 'single') {
        $('#youtube-single-form-container').show();
        $('#youtube-batch-form-container').hide();
        $('#youtube_mode_description').text('Tạo một video YouTube từ audio và visual content');
    } else {
        $('#youtube-single-form-container').hide();
        $('#youtube-batch-form-container').show();
        $('#youtube_mode_description').text('Tạo nhiều video YouTube cùng lúc với các nội dung khác nhau');
    }
}

// YouTube audio source toggle
function toggleYoutubeAudioSource() {
    const audioSource = $('input[name="audio_source"]:checked').val();

    if (audioSource === 'text') {
        $('#youtube-text-content').show();
        $('#youtube-audio-file').hide();
        $('#youtube-audio-settings').show();
        $('#youtube_text_content').prop('required', true);
        $('#youtube_audio_file').prop('required', false);
    } else {
        $('#youtube-text-content').hide();
        $('#youtube-audio-file').show();
        $('#youtube-audio-settings').hide();
        $('#youtube_text_content').prop('required', false);
        $('#youtube_audio_file').prop('required', true);
    }

    // Update output name preview
    updateOutputPreview('youtube');
}

// YouTube video content toggle
function toggleYoutubeVideoContent() {
    const contentType = $('input[name="video_content_type"]:checked').val();

    $('#youtube-images-section').hide();
    $('#youtube-video-section').hide();

    if (contentType === 'images' || contentType === 'mixed') {
        $('#youtube-images-section').show();
        $('#youtube_images').prop('required', true);
    }

    if (contentType === 'video' || contentType === 'mixed') {
        $('#youtube-video-section').show();
        $('#youtube_background_video').prop('required', true);
    }

    // Update output name preview
    updateOutputPreview('youtube');
}
    
    if (contentType === 'images') {
        $('#youtube_background_video').prop('required', false);
    }
    
    if (contentType === 'video') {
        $('#youtube_images').prop('required', false);
    }
}

// YouTube form validation
function validateYoutubeSingleForm() {
    const form = document.getElementById('youtube-single-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    
    showLoading('youtube-single-submit');
    return true;
}

function validateYoutubeBatchForm() {
    const form = document.getElementById('youtube-batch-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    
    if (youtubeVideoItemCounter === 0) {
        alert('Vui lòng thêm ít nhất một video item');
        return false;
    }
    
    showLoading('youtube-batch-submit');
    return true;
}

// YouTube batch video item management
function addYoutubeVideoItem() {
    youtubeVideoItemCounter++;
    const itemHtml = createYoutubeVideoItemHtml(youtubeVideoItemCounter);
    
    $('#youtube-video-items-container').append(itemHtml);
    $('#youtube-empty-state').hide();
    updateYoutubeVideoCount();
    updateYoutubeSubmitButton();
}

function removeYoutubeVideoItem(index) {
    $(`#youtube-video-item-${index}`).remove();
    updateYoutubeVideoCount();
    updateYoutubeSubmitButton();
    
    if ($('#youtube-video-items-container .video-item').length === 0) {
        $('#youtube-empty-state').show();
    }
}

function createYoutubeVideoItemHtml(index) {
    return `
        <div class="video-item border rounded p-3 mb-3" id="youtube-video-item-${index}" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="fas fa-video mr-2"></i>Video YouTube #${index}
                </h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeYoutubeVideoItem(${index})">
                    <i class="fas fa-trash mr-1"></i>Xóa
                </button>
            </div>
            
            <!-- Audio Source -->
            <div class="form-group">
                <label>Nguồn audio cho video #${index}</label>
                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                    <label class="btn btn-outline-info active">
                        <input type="radio" name="audio_sources[]" value="text" checked>
                        <i class="fas fa-keyboard mr-1"></i>Text-to-Speech
                    </label>
                    <label class="btn btn-outline-info">
                        <input type="radio" name="audio_sources[]" value="file">
                        <i class="fas fa-file-audio mr-1"></i>Upload Audio
                    </label>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_text_content_${index}">Nội dung text</label>
                        <textarea name="text_contents[]" id="youtube_text_content_${index}" class="form-control" rows="3" 
                                  placeholder="Nhập nội dung cho video #${index}..." maxlength="5000"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_audio_file_${index}">File audio (nếu chọn upload)</label>
                        <input type="file" name="audio_files[]" id="youtube_audio_file_${index}" 
                               class="form-control-file" accept="audio/mp3,audio/wav,audio/m4a">
                        <small class="form-text text-muted">MP3, WAV, M4A. Tối đa 50MB</small>
                    </div>
                </div>
            </div>
            
            <!-- Video Content Type -->
            <div class="form-group">
                <label>Loại nội dung video #${index}</label>
                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                    <label class="btn btn-outline-success active">
                        <input type="radio" name="video_content_types[]" value="images" checked>
                        <i class="fas fa-images mr-1"></i>Slideshow ảnh
                    </label>
                    <label class="btn btn-outline-success">
                        <input type="radio" name="video_content_types[]" value="video">
                        <i class="fas fa-video mr-1"></i>Video nền
                    </label>
                    <label class="btn btn-outline-success">
                        <input type="radio" name="video_content_types[]" value="mixed">
                        <i class="fas fa-layer-group mr-1"></i>Kết hợp
                    </label>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_images_${index}">Ảnh cho slideshow</label>
                        <input type="file" name="batch_images[${index}][]" id="youtube_images_${index}" 
                               class="form-control-file" accept="image/jpeg,image/jpg,image/png,image/gif" multiple>
                        <small class="form-text text-muted">JPG, PNG, GIF. Chọn nhiều ảnh.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_bg_video_${index}">Video nền (nếu chọn video)</label>
                        <input type="file" name="batch_background_videos[]" id="youtube_bg_video_${index}" 
                               class="form-control-file" accept="video/mp4,video/avi,video/mov">
                        <small class="form-text text-muted">MP4, AVI, MOV. Tối đa 500MB</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_output_name_${index}">Tên file output (Tùy chọn)</label>
                        <input type="text" name="output_names[]" id="youtube_output_name_${index}" 
                               class="form-control" placeholder="youtube_video_${index}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group" id="youtube_subtitle_text_section_${index}" style="display: none;">
                        <label for="youtube_subtitle_text_${index}">Subtitle cho video #${index}</label>
                        <textarea name="subtitle_texts[]" id="youtube_subtitle_text_${index}" class="form-control" rows="2" 
                                  placeholder="Nhập subtitle cho video #${index}..." maxlength="500"></textarea>
                        <small class="text-muted">Để trống nếu không muốn subtitle cho video này</small>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function updateYoutubeVideoCount() {
    const count = $('#youtube-video-items-container .video-item').length;
    $('#youtube-video-count').text(count);
}

function updateYoutubeSubmitButton() {
    const count = $('#youtube-video-items-container .video-item').length;
    const submitButton = $('#youtube-batch-submit');
    
    if (count > 0) {
        submitButton.prop('disabled', false);
        submitButton.html(`<i class="fas fa-layer-group mr-2"></i>Tạo ${count} Video YouTube`);
    } else {
        submitButton.prop('disabled', true);
        submitButton.html('<i class="fas fa-layer-group mr-2"></i>Tạo Batch Video YouTube');
    }
}

// Initialize YouTube
$(document).ready(function() {
    // Initialize YouTube mode
    switchYoutubeMode('single');
    
    // Initialize audio source
    toggleYoutubeAudioSource();
    
    // Initialize video content
    toggleYoutubeVideoContent();
    
    // Initialize video count
    updateYoutubeVideoCount();
    updateYoutubeSubmitButton();
    
    // Event listeners for dynamic changes
    $(document).on('change', 'input[name="audio_source"]', toggleYoutubeAudioSource);
    $(document).on('change', 'input[name="video_content_type"]', toggleYoutubeVideoContent);
});
</script>
