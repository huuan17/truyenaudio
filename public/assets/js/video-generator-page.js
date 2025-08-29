/**
 * Video Generator Page JavaScript
 * Contains all the JavaScript functions needed for the video generator page
 */

// Global variables
let currentVideoFilter = 'all';

/**
 * Initialize the video generator page
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Video Generator Page JS loaded');
    
    // Initialize page functionality
    initializeVideoGenerator();
    
    // Initialize existing videos section
    initializeExistingVideos();
    
    // Initialize media content toggles
    initializeMediaContentToggles();
});

/**
 * Initialize video generator functionality
 */
function initializeVideoGenerator() {
    // Initialize preview functionality if available
    if (typeof initVideoGeneratorPreview === 'function') {
        initVideoGeneratorPreview();
    }
    
    // Initialize platform switching
    initializePlatformSwitching();
}

/**
 * Initialize platform switching functionality
 */
function initializePlatformSwitching() {
    const platformRadios = document.querySelectorAll('input[name="platform"]');
    platformRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            switchPlatform(this.value);
        });
    });
}

/**
 * Switch between platforms (TikTok/YouTube)
 */
function switchPlatform(platform) {
    console.log('🔄 Switching to platform:', platform);

    // Sync hidden input so Blade toggle script can decide which panels to show
    const input = document.getElementById('platform_input');
    if (input) input.value = platform;

    // Delegate showing/hiding panels to the Blade page script only
    if (typeof togglePlatformSettings === 'function') {
        try { togglePlatformSettings(); } catch (e) { console.warn('togglePlatformSettings error', e); }
    }
}

/**
 * Initialize existing videos section
 */
function initializeExistingVideos() {
    // Load existing videos
    loadExistingVideos();
}

/**
 * Filter videos by platform
 */
function filterVideos(platform) {
    console.log('🔍 Filtering videos by platform:', platform);
    
    currentVideoFilter = platform;
    
    // Update active button
    const filterButtons = document.querySelectorAll('.btn-group button');
    filterButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    const activeButton = document.querySelector(`button[onclick="filterVideos('${platform}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Filter video items
    const videoItems = document.querySelectorAll('.video-item');
    videoItems.forEach(item => {
        const itemPlatform = item.dataset.platform;
        
        if (platform === 'all' || itemPlatform === platform) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Load existing videos from server
 */
function loadExistingVideos() {
    // This would typically make an AJAX call to load videos
    // For now, we'll just ensure the filter works with existing DOM elements
    console.log('📁 Loading existing videos...');
}

/**
 * Preview video file
 */
function previewVideo(input) {
    console.log('🎬 Previewing video file');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById('video-preview');
        
        if (!preview) {
            console.warn('Video preview container not found');
            return;
        }
        
        // Validate file type
        if (!file.type.startsWith('video/')) {
            preview.innerHTML = '<div class="alert alert-danger">Vui lòng chọn file video hợp lệ.</div>';
            return;
        }
        
        // Validate file size (500MB limit)
        const maxSize = 500 * 1024 * 1024; // 500MB in bytes
        if (file.size > maxSize) {
            preview.innerHTML = '<div class="alert alert-danger">File quá lớn. Vui lòng chọn file nhỏ hơn 500MB.</div>';
            return;
        }
        
        // Create video preview
        const videoUrl = URL.createObjectURL(file);
        preview.innerHTML = `
            <div class="alert alert-success">
                <strong>File đã chọn:</strong> ${file.name}<br>
                <strong>Kích thước:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                <video controls class="mt-2" style="width: 100%; max-height: 200px;">
                    <source src="${videoUrl}" type="${file.type}">
                    Trình duyệt không hỗ trợ video preview.
                </video>
            </div>
        `;
    }
}

/**
 * Preview file (generic function for various file types)
 */
function previewFile(input, previewId) {
    console.log('📄 Previewing file for:', previewId);
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        
        if (!preview) {
            console.warn('Preview container not found:', previewId);
            return;
        }
        
        if (file.type.startsWith('image/')) {
            previewImage(file, preview);
        } else if (file.type.startsWith('video/')) {
            previewVideoFile(file, preview);
        } else if (file.type.startsWith('audio/')) {
            previewAudioFile(file, preview);
        } else {
            preview.innerHTML = `
                <div class="alert alert-info">
                    <strong>File đã chọn:</strong> ${file.name}<br>
                    <strong>Kích thước:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB
                </div>
            `;
        }
    }
}

/**
 * Preview image file
 */
function previewImage(file, container) {
    const imageUrl = URL.createObjectURL(file);
    container.innerHTML = `
        <div class="alert alert-success">
            <strong>File đã chọn:</strong> ${file.name}<br>
            <strong>Kích thước:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
            <img src="${imageUrl}" class="mt-2 img-thumbnail" style="max-width: 200px; max-height: 150px;">
        </div>
    `;
}

/**
 * Preview video file
 */
function previewVideoFile(file, container) {
    const videoUrl = URL.createObjectURL(file);
    container.innerHTML = `
        <div class="alert alert-success">
            <strong>File đã chọn:</strong> ${file.name}<br>
            <strong>Kích thước:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
            <video controls class="mt-2" style="width: 100%; max-height: 200px;">
                <source src="${videoUrl}" type="${file.type}">
                Trình duyệt không hỗ trợ video preview.
            </video>
        </div>
    `;
}

/**
 * Preview audio file
 */
function previewAudioFile(file, container) {
    const audioUrl = URL.createObjectURL(file);
    container.innerHTML = `
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

/**
 * Preview images with advanced functionality
 */
function previewImagesAdvanced(input) {
    console.log('🖼️ Previewing images (advanced)');

    if (input.files && input.files.length > 0) {
        const preview = document.getElementById('images-preview');
        if (!preview) return;

        let html = '<div class="row">';

        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const imageUrl = URL.createObjectURL(file);
                html += `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="${imageUrl}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            </div>
                        </div>
                    </div>
                `;
            }
        });

        html += '</div>';
        preview.innerHTML = html;

        // Show image settings sections
        showImageSettings(input.files);

        // Generate individual image settings
        generateIndividualImageSettings(input.files);
    } else {
        // Hide image settings if no files
        hideImageSettings();
    }
}

/**
 * Show image settings sections
 */
function showImageSettings(files) {
    const imageSettingsSection = document.getElementById('image-settings-section');
    const individualImageSettings = document.getElementById('individual-image-settings');

    if (imageSettingsSection) {
        imageSettingsSection.style.display = 'block';
        // Initialize global settings button
        setTimeout(() => {
            initializeGlobalSettingsButton();
        }, 100);
    }

    if (individualImageSettings && files.length > 0) {
        individualImageSettings.style.display = 'block';
        // Initialize duration tracking
        setTimeout(() => {
            initializeDurationTracking();
        }, 100);
    }
}

/**
 * Hide image settings sections
 */
function hideImageSettings() {
    const imageSettingsSection = document.getElementById('image-settings-section');
    const individualImageSettings = document.getElementById('individual-image-settings');

    if (imageSettingsSection) {
        imageSettingsSection.style.display = 'none';
    }

    if (individualImageSettings) {
        individualImageSettings.style.display = 'none';
    }
}

/**
 * Generate individual image settings
 */
function generateIndividualImageSettings(files) {
    const container = document.getElementById('image-items-container');
    if (!container) return;

    let html = '';

    Array.from(files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const imageUrl = URL.createObjectURL(file);
            html += `
                <div class="card mb-3" data-image-index="${index}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="${imageUrl}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;">
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">${file.name}</small>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label class="small">Thời gian (giây)</label>
                                    <input type="number" name="image_durations[]"
                                           class="form-control form-control-sm"
                                           min="0.5" max="30" step="0.5" value="3"
                                           onchange="updateTotalDuration()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label class="small">Hiệu ứng</label>
                                    <select name="image_transitions[]" class="form-control form-control-sm">
                                        <option value="fade">Fade</option>
                                        <option value="slide" selected>Slide</option>
                                        <option value="zoom">Zoom</option>
                                        <option value="dissolve">Dissolve</option>
                                        <option value="wipe">Wipe</option>
                                        <option value="none">Không có</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label class="small">Thứ tự</label>
                                    <input type="number" name="image_orders[]"
                                           class="form-control form-control-sm"
                                           min="1" value="${index + 1}"
                                           data-original-index="${index}"
                                           onchange="console.log('🔄 Order input changed:', this.value, 'for image:', this.getAttribute('data-original-index')); updateImageOrder()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    });

    container.innerHTML = html;

    // Update total duration
    updateTotalDuration();
}

/**
 * Preview mixed images only
 */
function previewMixedImages(input) {
    console.log('🖼️ Previewing mixed images');

    if (input.files && input.files.length > 0) {
        const preview = document.getElementById('mixed-images-preview');
        if (!preview) return;

        let html = '<div class="row">';

        Array.from(input.files).forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);

            html += `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <img src="${fileUrl}" class="card-img-top" style="height: 150px; object-fit: cover;">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1">${file.name}</h6>
                            <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            <div class="mt-2">
                                <label class="small">Thời gian hiển thị (giây):</label>
                                <input type="number" name="mixed_image_durations[]"
                                       class="form-control form-control-sm"
                                       min="0.5" max="30" step="0.5" value="3"
                                       onchange="updateMixedTotalDuration()">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        preview.innerHTML = html;

        // Update total duration
        updateMixedTotalDuration();
    }
}

/**
 * Preview mixed videos only
 */
function previewMixedVideos(input) {
    console.log('🎬 Previewing mixed videos');

    if (input.files && input.files.length > 0) {
        const preview = document.getElementById('mixed-videos-preview');
        if (!preview) return;

        let html = '<div class="row">';

        Array.from(input.files).forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);

            html += `
                <div class="col-md-12 mb-3">
                    <div class="card">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1">${file.name}</h6>
                            <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            <video controls class="mt-2" style="width: 100%; max-height: 150px;">
                                <source src="${fileUrl}" type="${file.type}">
                                Trình duyệt không hỗ trợ video preview.
                            </video>
                            <div class="mt-2">
                                <label class="small">Thời gian sử dụng:</label>
                                <select name="mixed_video_durations[]" class="form-control form-control-sm" onchange="updateMixedTotalDuration()">
                                    <option value="full">Toàn bộ video</option>
                                    <option value="5">5 giây</option>
                                    <option value="10" selected>10 giây</option>
                                    <option value="15">15 giây</option>
                                    <option value="30">30 giây</option>
                                    <option value="custom">Tùy chỉnh</option>
                                </select>
                                <input type="number" name="mixed_video_custom_durations[]"
                                       class="form-control form-control-sm mt-1"
                                       min="1" max="300" value="10" style="display: none;"
                                       placeholder="Nhập thời gian (giây)">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        preview.innerHTML = html;

        // Add event listeners for custom duration inputs
        preview.querySelectorAll('select[name="mixed_video_durations[]"]').forEach(select => {
            select.addEventListener('change', function() {
                const customInput = this.parentNode.querySelector('input[name="mixed_video_custom_durations[]"]');
                if (this.value === 'custom') {
                    customInput.style.display = 'block';
                } else {
                    customInput.style.display = 'none';
                }
                updateMixedTotalDuration();
            });
        });

        // Update total duration
        updateMixedTotalDuration();
    }
}

/**
 * Update total duration for mixed media
 */
function updateMixedTotalDuration() {
    let totalDuration = 0;
    let itemCount = 0;

    // Calculate image durations
    const imageDurations = document.querySelectorAll('input[name="mixed_image_durations[]"]');
    imageDurations.forEach(input => {
        const duration = parseFloat(input.value) || 0;
        totalDuration += duration;
        if (duration > 0) itemCount++;
    });

    // Calculate video durations
    const videoDurations = document.querySelectorAll('select[name="mixed_video_durations[]"]');
    videoDurations.forEach(select => {
        let duration = 0;
        if (select.value === 'custom') {
            const customInput = select.parentNode.querySelector('input[name="mixed_video_custom_durations[]"]');
            duration = parseFloat(customInput.value) || 0;
        } else if (select.value !== 'full') {
            duration = parseFloat(select.value) || 0;
        } else {
            duration = 10; // Estimate for full video
        }
        totalDuration += duration;
        if (duration > 0) itemCount++;
    });

    // Update display
    const durationInfo = document.getElementById('mixed-duration-info');
    if (durationInfo) {
        durationInfo.innerHTML = `
            <i class="fas fa-clock mr-1"></i>
            Tổng thời lượng ước tính: <strong>${totalDuration.toFixed(1)} giây</strong>
            (${itemCount} items)
        `;
    }

    console.log('🕐 Mixed media total duration updated:', totalDuration, 'seconds');
}

/**
 * Preview mixed media (images and videos) - Legacy function
 */
function previewMixedMediaAdvanced(input) {
    console.log('🎭 Previewing mixed media (advanced)');

    if (input.files && input.files.length > 0) {
        const preview = document.getElementById('mixed-preview');
        if (!preview) return;

        let html = '<div class="row">';

        Array.from(input.files).forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);
            
            if (file.type.startsWith('image/')) {
                html += `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="${fileUrl}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="text-muted"><i class="fas fa-image"></i> ${file.name}</small><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            </div>
                        </div>
                    </div>
                `;
            } else if (file.type.startsWith('video/')) {
                html += `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <video class="card-img-top" style="height: 150px; object-fit: cover;" muted>
                                <source src="${fileUrl}" type="${file.type}">
                            </video>
                            <div class="card-body p-2">
                                <small class="text-muted"><i class="fas fa-video"></i> ${file.name}</small><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        
        html += '</div>';
        preview.innerHTML = html;
    }
}

/**
 * Initialize media content toggles
 */
function initializeMediaContentToggles() {
    // Initialize content type toggles
    const contentTypeRadios = document.querySelectorAll('input[name="content_type"]');
    contentTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            toggleMediaContent(this.value);
        });
    });

    // Initialize default state
    const checkedRadio = document.querySelector('input[name="content_type"]:checked');
    if (checkedRadio) {
        toggleMediaContent(checkedRadio.value);
    }

    // Initialize global settings listeners
    initializeGlobalSettingsListeners();
}

/**
 * Initialize global settings event listeners
 */
function initializeGlobalSettingsListeners() {
    // Listen for changes in global duration setting
    const defaultDuration = document.getElementById('default_image_duration');
    if (defaultDuration) {
        defaultDuration.addEventListener('input', function() {
            console.log('🔄 Global duration changed to:', this.value);
        });
    }

    // Listen for changes in global transition setting
    const defaultTransition = document.getElementById('default_transition_effect');
    if (defaultTransition) {
        defaultTransition.addEventListener('change', function() {
            console.log('🔄 Global transition changed to:', this.value);
        });
    }

    // Listen for changes in slide duration
    const slideDuration = document.getElementById('slide_duration');
    if (slideDuration) {
        slideDuration.addEventListener('input', function() {
            console.log('🔄 Slide duration changed to:', this.value);
        });
    }
}

/**
 * Toggle media content sections based on content type
 */
function toggleMediaContent(contentType) {
    console.log('🔄 Toggling media content to:', contentType);
    
    // Hide all sections first
    const sections = ['images-section', 'video-section', 'mixed-section'];
    sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
            section.style.display = 'none';
        }
    });
    
    // Show selected section
    const targetSection = document.getElementById(contentType + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }
}

/**
 * Toggle individual settings visibility
 */
function toggleIndividualSettings() {
    const individualSettings = document.getElementById('individual-image-settings');
    const button = document.querySelector('button[onclick="toggleIndividualSettings()"]');

    if (individualSettings) {
        const isVisible = individualSettings.style.display !== 'none';

        if (isVisible) {
            individualSettings.style.display = 'none';
            if (button) {
                button.innerHTML = '<i class="fas fa-cogs mr-1"></i>Hiển thị tùy chỉnh từng ảnh';
            }
        } else {
            individualSettings.style.display = 'block';
            if (button) {
                button.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Ẩn tùy chỉnh từng ảnh';
            }
        }
    }
}

/**
 * Update output preview (placeholder function)
 */
function updateOutputPreview(platform) {
    console.log('🔄 Updating output preview for platform:', platform);
    // This function can be expanded to show output filename preview
    // based on current form inputs
}

/**
 * Update total duration display
 */
function updateTotalDuration() {
    const durationInputs = document.querySelectorAll('input[name="image_durations[]"]');
    let totalDuration = 0;
    let imageCount = 0;

    durationInputs.forEach(input => {
        const duration = parseFloat(input.value) || 0;
        totalDuration += duration;
        if (duration > 0) imageCount++;
    });

    // Add transition time if there are multiple images and transitions are enabled
    let transitionTime = 0;
    let transitionType = 'none';

    if (imageCount > 1) {
        // Check if transitions are enabled
        const transitionSelect = document.getElementById('default_transition_effect') || document.getElementById('slide_transition');
        transitionType = transitionSelect ? transitionSelect.value : 'slide';

        if (transitionType !== 'none') {
            const transitionDurationInput = document.getElementById('transition_duration');
            const transitionDuration = transitionDurationInput ? parseFloat(transitionDurationInput.value) || 0.5 : 0.5;
            transitionTime = (imageCount - 1) * transitionDuration;
        }
    }

    // Calculate final duration based on transition type
    let estimatedDuration;
    if (transitionType !== 'none' && imageCount > 1) {
        // With transitions: total image time + transition overlaps
        estimatedDuration = totalDuration + transitionTime;
    } else {
        // Without transitions: just sum of image durations
        estimatedDuration = totalDuration;
    }

    // Update display if exists
    const totalDisplay = document.getElementById('total-duration-display');
    if (totalDisplay) {
        let detailText = `${imageCount} ảnh: ${totalDuration.toFixed(1)}s`;
        if (transitionTime > 0) {
            detailText += ` + hiệu ứng ${transitionType}: ${transitionTime.toFixed(1)}s`;
        }

        totalDisplay.innerHTML = `
            <strong>Ước tính thời gian video:</strong> ${estimatedDuration.toFixed(1)}s
            <small class="text-muted d-block">
                (${detailText})
            </small>
        `;
    }

    console.log('📊 Video duration calculation:', {
        imageCount,
        imageDuration: totalDuration.toFixed(1) + 's',
        transitionType,
        transitionTime: transitionTime.toFixed(1) + 's',
        estimatedTotal: estimatedDuration.toFixed(1) + 's'
    });
}

/**
 * Update image order
 */
function updateImageOrder() {
    console.log('🔄 Updating image order - FUNCTION CALLED');

    // Get all image items and their order values
    const imageItems = document.querySelectorAll('#image-items-container [data-image-index]');
    const orderInputs = document.querySelectorAll('#image-items-container input[name="image_orders[]"]');

    console.log('📸 Found image items:', imageItems.length);
    console.log('📸 Found order inputs:', orderInputs.length);

    // Debug: Log all possible selectors
    console.log('📸 Debug selectors:');
    console.log('📸 - #image-items-container exists:', !!document.querySelector('#image-items-container'));
    console.log('📸 - [data-image-index] elements:', document.querySelectorAll('[data-image-index]').length);
    console.log('📸 - input[name="image_orders[]"] elements:', document.querySelectorAll('input[name="image_orders[]"]').length);
    console.log('📸 - .image-order-input elements:', document.querySelectorAll('.image-order-input').length);

    // Debug: Log current order values
    orderInputs.forEach((input, index) => {
        console.log(`📸 Order input ${index}: value=${input.value}, originalIndex=${input.getAttribute('data-original-index')}, name=${input.name}`);
    });

    // Force update if no order inputs found but we have image items
    if (orderInputs.length === 0 && imageItems.length > 0) {
        console.log('⚠️ No order inputs found, but image items exist. Trying alternative selector...');
        const alternativeInputs = document.querySelectorAll('input[type="number"][data-original-index]');
        console.log('📸 Alternative inputs found:', alternativeInputs.length);
        alternativeInputs.forEach((input, index) => {
            console.log(`📸 Alt input ${index}: value=${input.value}, originalIndex=${input.getAttribute('data-original-index')}`);
        });
    }

    // If no order inputs found, this means the image order inputs are not created properly
    if (orderInputs.length === 0) {
        console.warn('📸 WARNING: No image order inputs found! Image order functionality may not work.');
        console.log('📸 Available inputs in container:', document.querySelectorAll('#image-items-container input').length);
        console.log('📸 All inputs with name containing "order":', document.querySelectorAll('input[name*="order"]').length);
    }

    console.log('🔍 Image order debug:', {
        imageItems: imageItems.length,
        orderInputs: orderInputs.length,
        container: document.getElementById('image-items-container')
    });

    if (imageItems.length === 0 || orderInputs.length === 0) {
        console.log('⚠️ No image items or order inputs found');
        return;
    }

    // Create array of items with their order values
    const itemsWithOrder = [];
    imageItems.forEach((item, index) => {
        const orderInput = orderInputs[index];
        const order = parseInt(orderInput.value) || (index + 1);

        itemsWithOrder.push({
            element: item,
            order: order,
            originalIndex: index
        });
    });

    // Sort by order value
    itemsWithOrder.sort((a, b) => a.order - b.order);

    // Reorder the DOM elements
    const container = document.getElementById('image-items-container');
    if (container) {
        // Clear container
        container.innerHTML = '';

        // Add items in new order
        itemsWithOrder.forEach((item, newIndex) => {
            // Update the data-image-index to reflect new order
            item.element.setAttribute('data-image-index', newIndex);
            container.appendChild(item.element);
        });

        // Update the hidden input with new order
        updateImageOrderInput(itemsWithOrder);

        console.log('✅ Image order updated', {
            newOrder: itemsWithOrder.map(item => ({
                originalIndex: item.originalIndex,
                newOrder: item.order
            }))
        });

        // Show visual feedback
        container.style.border = '2px solid #28a745';
        setTimeout(() => {
            container.style.border = '';
        }, 1000);

        // Trigger preview update if preview system exists
        if (typeof window.videoPreview !== 'undefined' && window.videoPreview) {
            console.log('🔄 Triggering preview update after image order change');
            setTimeout(() => {
                // Double-check that image_order_mapping is set before triggering preview
                const orderInput = document.getElementById('image_order_mapping');
                console.log('🔄 Before preview trigger - image_order_mapping:', orderInput ? orderInput.value : 'NOT FOUND');
                window.videoPreview.generatePreview();
            }, 800); // Increased delay to ensure DOM is updated
        }
    }
}

/**
 * Update hidden input with image order
 */
function updateImageOrderInput(itemsWithOrder) {
    console.log('🔄 updateImageOrderInput CALLED with:', itemsWithOrder);

    // Create or update hidden input with image order mapping
    let orderInput = document.getElementById('image_order_mapping');
    if (!orderInput) {
        orderInput = document.createElement('input');
        orderInput.type = 'hidden';
        orderInput.id = 'image_order_mapping';
        orderInput.name = 'image_order_mapping';
        document.querySelector('form').appendChild(orderInput);
    }

    // Create mapping: originalIndex => orderValue (NOT position)
    const orderMapping = {};
    itemsWithOrder.forEach(item => {
        orderMapping[item.originalIndex] = parseInt(item.order);
    });

    orderInput.value = JSON.stringify(orderMapping);

    console.log('🔄 updateImageOrderInput COMPLETED:', {
        itemsWithOrder: itemsWithOrder,
        orderMapping: orderMapping,
        orderInputValue: orderInput.value
    });
}

/**
 * Apply global settings to all images
 */
function applyGlobalImageSettings() {
    const defaultDuration = document.getElementById('default_image_duration');
    const defaultTransition = document.getElementById('default_transition_effect');
    const slideDuration = document.getElementById('slide_duration');
    const slideTransition = document.getElementById('slide_transition');

    // Use either the default settings or slide settings
    const durationValue = defaultDuration ? defaultDuration.value : (slideDuration ? slideDuration.value : '3');
    const transitionValue = defaultTransition ? defaultTransition.value : (slideTransition ? slideTransition.value : 'slide');

    // Apply to all individual image settings
    const durationInputs = document.querySelectorAll('input[name="image_durations[]"]');
    const transitionSelects = document.querySelectorAll('select[name="image_transitions[]"]');

    durationInputs.forEach(input => {
        input.value = durationValue;
    });

    transitionSelects.forEach(select => {
        select.value = transitionValue;
    });

    // Update total duration
    updateTotalDuration();

    // Show success message
    showToast('success', 'Đã áp dụng cài đặt cho tất cả ảnh!');

    console.log('✅ Applied global settings to all images');
}

/**
 * Show toast notification
 */
function showToast(type, message) {
    // Try to use toastr if available
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        // Fallback to alert
        alert(message);
    }
}

/**
 * Add apply button functionality
 */
function initializeGlobalSettingsButton() {
    // Add apply button if it doesn't exist
    const imageSettingsSection = document.getElementById('image-settings-section');
    if (imageSettingsSection) {
        const cardBody = imageSettingsSection.querySelector('.card-body');
        if (cardBody && !cardBody.querySelector('.apply-global-btn')) {
            const applyButton = document.createElement('div');
            applyButton.className = 'row mt-3';
            applyButton.innerHTML = `
                <div class="col-12">
                    <button type="button" class="btn btn-primary apply-global-btn" onclick="applyGlobalImageSettings()">
                        <i class="fas fa-magic mr-2"></i>Áp dụng cho tất cả ảnh
                    </button>
                    <small class="text-muted ml-2">Áp dụng cài đặt này cho tất cả các ảnh đã chọn</small>
                </div>
            `;
            cardBody.appendChild(applyButton);
        }
    }
}

/**
 * Initialize duration tracking
 */
function initializeDurationTracking() {
    // Add total duration display if it doesn't exist
    const individualSettings = document.getElementById('individual-image-settings');
    if (individualSettings && !individualSettings.querySelector('#total-duration-display')) {
        const durationDisplay = document.createElement('div');
        durationDisplay.className = 'alert alert-info mt-3';
        durationDisplay.innerHTML = `
            <strong><i class="fas fa-clock mr-2"></i>Tổng thời gian video:</strong>
            <span id="total-duration-display">0s</span>
        `;
        individualSettings.appendChild(durationDisplay);
    }
}

/**
 * Initialize sequence strategy handlers
 */
function initializeSequenceStrategyHandlers() {
    // Sequence strategy change handler
    const strategyRadios = document.querySelectorAll('input[name="sequence_strategy"]');
    strategyRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const strategy = this.value;

            if (strategy === 'even_distribution') {
                document.getElementById('even-distribution-settings').style.display = 'block';
                document.getElementById('alternating-settings').style.display = 'none';
            } else {
                document.getElementById('even-distribution-settings').style.display = 'none';
                document.getElementById('alternating-settings').style.display = 'block';
            }

            updateMixedTotalDuration();
        });
    });

    // Image distribution mode change handler
    const distributionSelect = document.getElementById('image_distribution_mode');
    if (distributionSelect) {
        distributionSelect.addEventListener('change', function() {
            const mode = this.value;

            if (mode === 'custom_timing') {
                document.getElementById('custom-timing-settings').style.display = 'block';
                generateImageTimingControls();
            } else {
                document.getElementById('custom-timing-settings').style.display = 'none';
            }

            updateMixedTotalDuration();
        });
    }

    // Mixed mode change handler
    const mixedModeRadios = document.querySelectorAll('input[name="mixed_mode"]');
    mixedModeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const selectedMode = this.value;

            // Hide all mode settings
            const modeSettings = ['sequence-mode-settings', 'overlay-mode-settings', 'split-mode-settings'];
            modeSettings.forEach(settingId => {
                const setting = document.getElementById(settingId);
                if (setting) {
                    setting.style.display = 'none';
                }
            });

            // Show selected mode settings
            if (selectedMode === 'sequence') {
                const sequenceSettings = document.getElementById('sequence-mode-settings');
                if (sequenceSettings) {
                    sequenceSettings.style.display = 'block';
                }
            } else if (selectedMode === 'overlay') {
                const overlaySettings = document.getElementById('overlay-mode-settings');
                if (overlaySettings) {
                    overlaySettings.style.display = 'block';
                }
            } else if (selectedMode === 'split') {
                const splitSettings = document.getElementById('split-mode-settings');
                if (splitSettings) {
                    splitSettings.style.display = 'block';
                }
            }

            updateMixedTotalDuration();
        });
    });
}

/**
 * Generate image timing controls for custom timing mode
 */
function generateImageTimingControls() {
    const container = document.getElementById('image-timing-controls');
    if (!container) return;

    // Get number of mixed images
    const mixedImagesInput = document.querySelector('input[name="mixed_images[]"]');
    const imageCount = mixedImagesInput && mixedImagesInput.files ? mixedImagesInput.files.length : 0;

    if (imageCount === 0) {
        container.innerHTML = '<p class="text-muted">Vui lòng chọn ảnh trước</p>';
        return;
    }

    let html = '<div class="row">';

    for (let i = 0; i < imageCount; i++) {
        html += `
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label for="image_timing_${i}">Ảnh ${i + 1} - Hiển thị tại giây thứ:</label>
                    <div class="input-group">
                        <input type="number" name="image_timings[]" id="image_timing_${i}"
                               class="form-control" min="0" max="300" step="0.5" value="${i * 5}"
                               onchange="updateMixedTotalDuration()">
                        <div class="input-group-append">
                            <span class="input-group-text">giây</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    html += '</div>';
    container.innerHTML = html;
}

/**
 * Initialize duration settings handlers
 */
function initializeDurationSettingsHandlers() {
    const durationRadios = document.querySelectorAll('input[name="duration_based_on"]');
    const customDurationSettings = document.getElementById('custom-duration-settings');
    const customDurationDisplay = document.getElementById('custom-duration-display');
    const customDurationInput = document.getElementById('custom_duration');

    if (!durationRadios.length || !customDurationSettings) {
        console.warn('Duration settings elements not found');
        return;
    }

    // Handle radio button changes
    durationRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateDurationDisplay(this.value);
        });
    });

    // Handle custom duration input changes
    if (customDurationInput) {
        customDurationInput.addEventListener('input', function() {
            if (customDurationDisplay) {
                customDurationDisplay.textContent = this.value + ' giây';
            }
        });
    }

    // Initialize with current selection
    const checkedRadio = document.querySelector('input[name="duration_based_on"]:checked');
    if (checkedRadio) {
        updateDurationDisplay(checkedRadio.value);
    }

    function updateDurationDisplay(selectedValue) {
        // Hide all duration info displays
        const infoElements = ['images-duration-info', 'video-duration-info', 'audio-duration-info', 'custom-duration-info'];
        infoElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
            }
        });

        // Show/hide custom duration settings
        if (selectedValue === 'custom') {
            customDurationSettings.style.display = 'block';
            const customInfo = document.getElementById('custom-duration-info');
            if (customInfo) {
                customInfo.style.display = 'block';
            }
        } else {
            customDurationSettings.style.display = 'none';
            // Show appropriate info for selected type
            const targetInfo = document.getElementById(selectedValue + '-duration-info');
            if (targetInfo) {
                targetInfo.style.display = 'block';
            }
        }

        console.log('🔄 Duration setting changed to:', selectedValue);
    }
}

// Initialize sequence strategy handlers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initializeSequenceStrategyHandlers();
        initializeDurationSettingsHandlers();
    }, 500);
});

// Export functions for global access
window.filterVideos = filterVideos;
window.previewVideo = previewVideo;
window.previewFile = previewFile;
window.previewImagesAdvanced = previewImagesAdvanced;
window.previewMixedMediaAdvanced = previewMixedMediaAdvanced;
window.previewMixedImages = previewMixedImages;
window.previewMixedVideos = previewMixedVideos;
window.updateMixedTotalDuration = updateMixedTotalDuration;
window.updateOutputPreview = updateOutputPreview;
window.toggleMediaContent = toggleMediaContent;
window.updateTotalDuration = updateTotalDuration;
window.updateImageOrder = updateImageOrder;
window.applyGlobalImageSettings = applyGlobalImageSettings;
window.toggleIndividualSettings = toggleIndividualSettings;
window.showToast = showToast;
window.initializeSequenceStrategyHandlers = initializeSequenceStrategyHandlers;
window.generateImageTimingControls = generateImageTimingControls;
window.initializeDurationSettingsHandlers = initializeDurationSettingsHandlers;

console.log('✅ Video Generator Page JS initialized');
