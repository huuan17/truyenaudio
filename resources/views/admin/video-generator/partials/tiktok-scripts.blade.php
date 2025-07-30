<!-- TikTok-specific JavaScript -->
<script>
// TikTok mode switching
function switchTiktokMode(mode) {
    if (mode === 'single') {
        $('#tiktok-single-form-container').show();
        $('#tiktok-batch-form-container').hide();
        $('#tiktok_mode_description').text('Tạo một video TikTok từ kịch bản và media files');
    } else {
        $('#tiktok-single-form-container').hide();
        $('#tiktok-batch-form-container').show();
        $('#tiktok_mode_description').text('Tạo nhiều video TikTok cùng lúc với các kịch bản khác nhau');
    }
}

// TikTok form validation
function validateTiktokSingleForm() {
    const form = document.getElementById('tiktok-single-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    
    showLoading('tiktok-single-submit');
    return true;
}

function validateTiktokBatchForm() {
    const form = document.getElementById('tiktok-batch-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    
    if (videoItemCounter === 0) {
        alert('Vui lòng thêm ít nhất một video item');
        return false;
    }
    
    showLoading('tiktok-batch-submit');
    return true;
}

// TikTok batch video item management
function addTiktokVideoItem() {
    videoItemCounter++;
    const itemHtml = createTiktokVideoItemHtml(videoItemCounter);
    
    $('#tiktok-video-items-container').append(itemHtml);
    $('#tiktok-empty-state').hide();
    updateTiktokVideoCount();
    updateTiktokSubmitButton();
}

function removeTiktokVideoItem(index) {
    $(`#tiktok-video-item-${index}`).remove();
    updateTiktokVideoCount();
    updateTiktokSubmitButton();
    
    if ($('#tiktok-video-items-container .video-item').length === 0) {
        $('#tiktok-empty-state').show();
    }
}

function createTiktokVideoItemHtml(index) {
    return `
        <div class="video-item border rounded p-3 mb-3" id="tiktok-video-item-${index}" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="fas fa-video mr-2"></i>Video TikTok #${index}
                </h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTiktokVideoItem(${index})">
                    <i class="fas fa-trash mr-1"></i>Xóa
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="tiktok_script_${index}">Kịch bản *</label>
                        <textarea name="scripts[]" id="tiktok_script_${index}" class="form-control" rows="3" 
                                  placeholder="Nhập kịch bản cho video #${index}..." required maxlength="5000"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_product_video_${index}">Video sản phẩm *</label>
                        <input type="file" name="product_videos[]" id="tiktok_product_video_${index}" 
                               class="form-control-file" accept="video/mp4,video/avi,video/mov" required>
                        <small class="form-text text-muted">MP4, AVI, MOV. Tối đa 100MB</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_product_image_${index}">Ảnh sản phẩm (Tùy chọn)</label>
                        <input type="file" name="product_images[]" id="tiktok_product_image_${index}" 
                               class="form-control-file" accept="image/jpeg,image/jpg,image/png">
                        <small class="form-text text-muted">JPG, PNG. Tối đa 10MB</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_output_name_${index}">Tên file output (Tùy chọn)</label>
                        <input type="text" name="output_names[]" id="tiktok_output_name_${index}" 
                               class="form-control" placeholder="tiktok_video_${index}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group" id="tiktok_subtitle_text_section_${index}" style="display: none;">
                        <label for="tiktok_subtitle_text_${index}">Subtitle cho video #${index}</label>
                        <textarea name="subtitle_texts[]" id="tiktok_subtitle_text_${index}" class="form-control" rows="2" 
                                  placeholder="Nhập subtitle cho video #${index}..." maxlength="500"></textarea>
                        <small class="text-muted">Để trống nếu không muốn subtitle cho video này</small>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function updateTiktokVideoCount() {
    const count = $('#tiktok-video-items-container .video-item').length;
    $('#tiktok-video-count').text(count);
}

function updateTiktokSubmitButton() {
    const count = $('#tiktok-video-items-container .video-item').length;
    const submitButton = $('#tiktok-batch-submit');
    
    if (count > 0) {
        submitButton.prop('disabled', false);
        submitButton.html(`<i class="fas fa-layer-group mr-2"></i>Tạo ${count} Video TikTok`);
    } else {
        submitButton.prop('disabled', true);
        submitButton.html('<i class="fas fa-layer-group mr-2"></i>Tạo Batch Video TikTok');
    }
}

// Initialize TikTok
$(document).ready(function() {
    // Initialize TikTok mode
    switchTiktokMode('single');

    // Initialize video count
    updateTiktokVideoCount();
    updateTiktokSubmitButton();

    // Initialize TikTok preview
    if (typeof VideoPreview !== 'undefined') {
        window.tiktokPreview = new VideoPreview({
            containerSelector: '.col-lg-4',
            insertPosition: 'beforeend',
            formType: 'generator',
            platform: 'tiktok',
            customSelectors: {
                images: ['input[name="product_images[]"]', 'input[id*="tiktok_product_image"]'],
                audio: ['input[name="background_audio"]'],
                subtitle: ['textarea[name="scripts[]"]', 'textarea[id*="tiktok_script"]', 'textarea[name="subtitle_texts[]"]'],
                tts: ['textarea[name="scripts[]"]', 'textarea[id*="tiktok_script"]']
            }
        });
    }
});
</script>
