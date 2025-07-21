<!-- Shared Modals for Universal Video Generator -->

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle mr-2"></i>Hướng dẫn sử dụng Universal Video Generator
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fab fa-tiktok mr-2"></i>TikTok Video</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i>Tỷ lệ 9:16 (Portrait)</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Tối ưu cho mobile</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Product review focused</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Logo overlay support</li>
                        </ul>
                        
                        <h6>Yêu cầu:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-file-alt mr-2"></i>Kịch bản review</li>
                            <li><i class="fas fa-video mr-2"></i>Video sản phẩm (MP4/AVI/MOV)</li>
                            <li><i class="fas fa-image mr-2"></i>Ảnh sản phẩm (tùy chọn)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fab fa-youtube mr-2"></i>YouTube Video</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i>Tỷ lệ 16:9 (Landscape)</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Tối ưu cho desktop</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Slideshow/Video background</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Transition effects</li>
                        </ul>
                        
                        <h6>Yêu cầu:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-microphone mr-2"></i>Audio (Text-to-Speech hoặc file)</li>
                            <li><i class="fas fa-images mr-2"></i>Ảnh cho slideshow</li>
                            <li><i class="fas fa-video mr-2"></i>Video nền (tùy chọn)</li>
                        </ul>
                    </div>
                </div>
                
                <hr>
                
                <h6><i class="fas fa-cogs mr-2"></i>Tính năng chung</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-closed-captioning mr-2"></i>Subtitle/Text overlay</li>
                            <li><i class="fas fa-volume-up mr-2"></i>Audio customization</li>
                            <li><i class="fas fa-layer-group mr-2"></i>Batch processing</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-tasks mr-2"></i>Queue management</li>
                            <li><i class="fas fa-broadcast-tower mr-2"></i>Auto posting</li>
                            <li><i class="fas fa-clock mr-2"></i>Scheduled publishing</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <a href="{{ route('admin.video-queue.index') }}" class="btn btn-info">
                    <i class="fas fa-tasks mr-1"></i>Xem Queue Status
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog mr-2"></i>Cài đặt Universal Video Generator
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="auto_refresh_status" checked>
                        Tự động refresh trạng thái (30s)
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="show_notifications" checked>
                        Hiển thị thông báo khi hoàn thành
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="default_platform">Platform mặc định:</label>
                    <select id="default_platform" class="form-control">
                        <option value="tiktok">TikTok</option>
                        <option value="youtube">YouTube</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="max_concurrent_videos">Số video xử lý đồng thời tối đa:</label>
                    <select id="max_concurrent_videos" class="form-control">
                        <option value="1" selected>1 (Khuyến nghị)</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                    <small class="form-text text-muted">
                        Tăng số này có thể gây quá tải server
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="saveSettings()">
                    <i class="fas fa-save mr-1"></i>Lưu cài đặt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog fa-spin mr-2"></i>Đang xử lý video...
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="progress-bar">
                        0%
                    </div>
                </div>
                <p id="progress-message">Đang khởi tạo...</p>
                <div class="text-center">
                    <small class="text-muted">
                        Quá trình này có thể mất vài phút. Vui lòng không đóng trang.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Help modal
function showHelp() {
    $('#helpModal').modal('show');
}

// Settings modal
function showSettings() {
    // Load current settings
    loadSettings();
    $('#settingsModal').modal('show');
}

function loadSettings() {
    // Load from localStorage or defaults
    $('#auto_refresh_status').prop('checked', localStorage.getItem('auto_refresh_status') !== 'false');
    $('#show_notifications').prop('checked', localStorage.getItem('show_notifications') !== 'false');
    $('#default_platform').val(localStorage.getItem('default_platform') || 'tiktok');
    $('#max_concurrent_videos').val(localStorage.getItem('max_concurrent_videos') || '1');
}

function saveSettings() {
    // Save to localStorage
    localStorage.setItem('auto_refresh_status', $('#auto_refresh_status').is(':checked'));
    localStorage.setItem('show_notifications', $('#show_notifications').is(':checked'));
    localStorage.setItem('default_platform', $('#default_platform').val());
    localStorage.setItem('max_concurrent_videos', $('#max_concurrent_videos').val());
    
    $('#settingsModal').modal('hide');
    
    // Show success message
    showNotification('Đã lưu cài đặt thành công', 'success');
    
    // Apply settings
    applySettings();
}

function applySettings() {
    // Apply auto refresh setting
    const autoRefresh = localStorage.getItem('auto_refresh_status') !== 'false';
    if (autoRefresh) {
        // Restart auto refresh if enabled
        if (window.statusRefreshInterval) {
            clearInterval(window.statusRefreshInterval);
        }
        window.statusRefreshInterval = setInterval(refreshVideoStatus, 30000);
    } else {
        // Stop auto refresh if disabled
        if (window.statusRefreshInterval) {
            clearInterval(window.statusRefreshInterval);
        }
    }
    
    // Switch to default platform
    const defaultPlatform = localStorage.getItem('default_platform') || 'tiktok';
    if (defaultPlatform !== currentPlatform) {
        $(`#${defaultPlatform}-tab`).tab('show');
    }
}

// Progress modal
function showProgress(title = 'Đang xử lý video...') {
    $('#progressModal .modal-title').html(`<i class="fas fa-cog fa-spin mr-2"></i>${title}`);
    $('#progress-bar').css('width', '0%').text('0%');
    $('#progress-message').text('Đang khởi tạo...');
    $('#progressModal').modal('show');
}

function updateProgress(percentage, message) {
    $('#progress-bar').css('width', percentage + '%').text(percentage + '%');
    if (message) {
        $('#progress-message').text(message);
    }
    
    if (percentage >= 100) {
        setTimeout(() => {
            $('#progressModal').modal('hide');
        }, 2000);
    }
}

function hideProgress() {
    $('#progressModal').modal('hide');
}

// Notification system
function showNotification(message, type = 'info', duration = 5000) {
    const showNotifications = localStorage.getItem('show_notifications') !== 'false';
    if (!showNotifications) return;
    
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
    
    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show notification-toast" 
             style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas ${iconClass} mr-2"></i>${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    
    $('body').append(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        notification.fadeOut(() => notification.remove());
    }, duration);
}

// Initialize settings on page load
$(document).ready(function() {
    applySettings();
});
</script>
