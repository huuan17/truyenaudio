<!-- Subtitle Settings Component -->
<div class="form-section">
    <div class="card border-info">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-closed-captioning mr-2"></i>Cài Đặt Subtitle
                <div class="form-check form-check-inline float-right">
                    <input type="checkbox" name="enable_subtitle" id="{{ $prefix }}_enable_subtitle" 
                           class="form-check-input" value="1" 
                           onchange="toggleSubtitleOptions('{{ $prefix }}', '{{ $platform }}')"
                           {{ old('enable_subtitle') ? 'checked' : '' }}>
                    <label class="form-check-label text-white" for="{{ $prefix }}_enable_subtitle">
                        Bật subtitle
                    </label>
                </div>
            </h6>
        </div>
        <div class="card-body" id="{{ $prefix }}_subtitle_options" style="display: none;">
            
            @if(!isset($batch) || !$batch)
            <!-- Single mode: Direct subtitle text input -->
            <div class="form-group">
                <label for="{{ $prefix }}_subtitle_text">Nội dung subtitle</label>
                <x-tinymce-editor
                    name="subtitle_text"
                    id="{{ $prefix }}_subtitle_text"
                    :value="old('subtitle_text')"
                    :height="150"
                    placeholder="Nhập text hiển thị trên video {{ $platform === 'tiktok' ? 'TikTok' : 'YouTube' }}..."
                    toolbar="basic" />
                <small class="form-text text-muted">Tối đa 500 ký tự. Để trống để không hiển thị subtitle.</small>
            </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="{{ $prefix }}_subtitle_position">Vị trí</label>
                        <select name="subtitle_position" id="{{ $prefix }}_subtitle_position" class="form-control">
                            @if($platform === 'tiktok')
                            <option value="bottom" {{ old('subtitle_position', 'bottom') == 'bottom' ? 'selected' : '' }}>Dưới giữa</option>
                            <option value="top" {{ old('subtitle_position') == 'top' ? 'selected' : '' }}>Trên giữa</option>
                            <option value="center" {{ old('subtitle_position') == 'center' ? 'selected' : '' }}>Giữa màn hình</option>
                            <option value="bottom-left" {{ old('subtitle_position') == 'bottom-left' ? 'selected' : '' }}>Dưới trái</option>
                            <option value="bottom-right" {{ old('subtitle_position') == 'bottom-right' ? 'selected' : '' }}>Dưới phải</option>
                            <option value="top-left" {{ old('subtitle_position') == 'top-left' ? 'selected' : '' }}>Trên trái</option>
                            <option value="top-right" {{ old('subtitle_position') == 'top-right' ? 'selected' : '' }}>Trên phải</option>
                            @else
                            <option value="bottom" {{ old('subtitle_position', 'bottom') == 'bottom' ? 'selected' : '' }}>Dưới giữa</option>
                            <option value="top" {{ old('subtitle_position') == 'top' ? 'selected' : '' }}>Trên giữa</option>
                            <option value="center" {{ old('subtitle_position') == 'center' ? 'selected' : '' }}>Giữa màn hình</option>
                            <option value="bottom-left" {{ old('subtitle_position') == 'bottom-left' ? 'selected' : '' }}>Dưới trái</option>
                            <option value="bottom-right" {{ old('subtitle_position') == 'bottom-right' ? 'selected' : '' }}>Dưới phải</option>
                            <option value="top-left" {{ old('subtitle_position') == 'top-left' ? 'selected' : '' }}>Trên trái</option>
                            <option value="top-right" {{ old('subtitle_position') == 'top-right' ? 'selected' : '' }}>Trên phải</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="{{ $prefix }}_subtitle_size">Kích thước font (px)</label>
                        <input type="number" name="subtitle_size" id="{{ $prefix }}_subtitle_size" 
                               class="form-control" min="12" max="72" 
                               value="{{ old('subtitle_size', $platform === 'tiktok' ? '28' : '24') }}">
                        <small class="form-text text-muted">
                            {{ $platform === 'tiktok' ? '12-72px (khuyến nghị: 28-36px cho mobile)' : '12-72px (khuyến nghị: 20-28px)' }}
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="{{ $prefix }}_subtitle_duration">Thời lượng hiển thị (giây)</label>
                        <input type="number" name="subtitle_duration" id="{{ $prefix }}_subtitle_duration" 
                               class="form-control" min="1" max="30" value="{{ old('subtitle_duration', '5') }}">
                        <small class="form-text text-muted">1-30 giây</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="{{ $prefix }}_subtitle_color">Màu chữ</label>
                        <input type="color" name="{{ $prefix }}_subtitle_color" id="{{ $prefix }}_subtitle_color"
                               class="form-control" value="{{ old($prefix.'_subtitle_color', '#FFFFFF') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="{{ $prefix }}_subtitle_background">Màu nền</label>
                        <input type="color" name="{{ $prefix }}_subtitle_background" id="{{ $prefix }}_subtitle_background"
                               class="form-control" value="{{ old($prefix.'_subtitle_background', '#000000') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="{{ $prefix }}_subtitle_font">Font chữ</label>
                        <select name="{{ $prefix }}_subtitle_font" id="{{ $prefix }}_subtitle_font" class="form-control">
                            <option value="Arial" {{ old('subtitle_font', 'Arial') == 'Arial' ? 'selected' : '' }}>Arial</option>
                            <option value="Times" {{ old('subtitle_font') == 'Times' ? 'selected' : '' }}>Times New Roman</option>
                            <option value="Helvetica" {{ old('subtitle_font') == 'Helvetica' ? 'selected' : '' }}>Helvetica</option>
                            <option value="Courier" {{ old('subtitle_font') == 'Courier' ? 'selected' : '' }}>Courier</option>
                            <option value="Verdana" {{ old('subtitle_font') == 'Verdana' ? 'selected' : '' }}>Verdana</option>
                            <option value="Georgia" {{ old('subtitle_font') == 'Georgia' ? 'selected' : '' }}>Georgia</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-outline-info btn-block" 
                                onclick="previewSubtitle('{{ $prefix }}', '{{ $platform }}')">
                            <i class="fas fa-eye mr-1"></i>Xem trước
                        </button>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Tips cho {{ $platform === 'tiktok' ? 'TikTok' : 'YouTube' }}:</strong> 
                {{ $platform === 'tiktok' 
                   ? 'Sử dụng font size lớn (28-36px), màu sáng trên nền tối để dễ đọc trên mobile.' 
                   : 'Sử dụng font size vừa phải (20-28px), màu tương phản để dễ đọc trên desktop.' }}
            </div>

        </div>
    </div>
</div>

<script>
function toggleSubtitleOptions(prefix, platform) {
    const enabled = document.getElementById(prefix + '_enable_subtitle').checked;
    const optionsDiv = document.getElementById(prefix + '_subtitle_options');
    const textInput = document.getElementById(prefix + '_subtitle_text');
    
    if (enabled) {
        optionsDiv.style.display = 'block';
        if (textInput) {
            textInput.required = true;
        }
    } else {
        optionsDiv.style.display = 'none';
        if (textInput) {
            textInput.required = false;
        }
    }
}

function previewSubtitle(prefix, platform) {
    const text = document.getElementById(prefix + '_subtitle_text')?.value || 'Sample subtitle text';
    const position = document.getElementById(prefix + '_subtitle_position').value;
    const size = document.getElementById(prefix + '_subtitle_size').value;
    const color = document.getElementById(prefix + '_subtitle_color').value;
    const background = document.getElementById(prefix + '_subtitle_background').value;
    const font = document.getElementById(prefix + '_subtitle_font').value;
    
    if (!text.trim()) {
        alert('Vui lòng nhập nội dung subtitle trước');
        return;
    }
    
    // Create preview modal
    const aspectRatio = platform === 'tiktok' ? '9:16' : '16:9';
    const containerStyle = platform === 'tiktok' 
        ? 'width: 300px; height: 533px;' 
        : 'width: 480px; height: 270px;';
    
    const previewHtml = `
        <div class="modal fade" id="subtitlePreviewModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa${platform === 'tiktok' ? 'b fa-tiktok' : 'b fa-youtube'} mr-2"></i>
                            Xem trước Subtitle ${platform === 'tiktok' ? 'TikTok' : 'YouTube'}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="preview-container" style="
                            position: relative;
                            ${containerStyle}
                            margin: 0 auto;
                            background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%), 
                                        linear-gradient(-45deg, #f0f0f0 25%, transparent 25%), 
                                        linear-gradient(45deg, transparent 75%, #f0f0f0 75%), 
                                        linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
                            background-size: 20px 20px;
                            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
                            border: 2px solid #ddd;
                            border-radius: 12px;
                            overflow: hidden;
                        ">
                            <div class="subtitle-text" style="
                                position: absolute;
                                ${getPreviewPosition(position, platform)}
                                font-family: ${font}, sans-serif;
                                font-size: ${Math.round(size * (platform === 'tiktok' ? 0.6 : 0.8))}px;
                                color: ${color};
                                background-color: ${background};
                                padding: 6px 10px;
                                border-radius: 4px;
                                max-width: 80%;
                                text-align: center;
                                word-wrap: break-word;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                            ">${text}</div>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Preview tỷ lệ ${aspectRatio} cho ${platform === 'tiktok' ? 'TikTok' : 'YouTube'}. 
                                Font size đã được scale để phù hợp với preview.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#subtitlePreviewModal').remove();
    
    // Add and show modal
    $('body').append(previewHtml);
    $('#subtitlePreviewModal').modal('show');
}

function getPreviewPosition(position, platform) {
    const margin = platform === 'tiktok' ? '30px' : '20px';
    
    switch (position) {
        case 'top':
            return `top: ${margin}; left: 50%; transform: translateX(-50%);`;
        case 'center':
            return 'top: 50%; left: 50%; transform: translate(-50%, -50%);';
        case 'bottom':
            return `bottom: ${margin}; left: 50%; transform: translateX(-50%);`;
        case 'top-left':
            return `top: ${margin}; left: 15px;`;
        case 'top-right':
            return `top: ${margin}; right: 15px;`;
        case 'bottom-left':
            return `bottom: ${margin}; left: 15px;`;
        case 'bottom-right':
            return `bottom: ${margin}; right: 15px;`;
        default:
            return `bottom: ${margin}; left: 50%; transform: translateX(-50%);`;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all subtitle options
    const subtitleCheckboxes = document.querySelectorAll('input[name="enable_subtitle"]');
    subtitleCheckboxes.forEach(function(checkbox) {
        const prefix = checkbox.id.replace('_enable_subtitle', '');
        const platform = checkbox.closest('.platform-form') ? 
            (checkbox.closest('#tiktok') ? 'tiktok' : 'youtube') : 'tiktok';
        toggleSubtitleOptions(prefix, platform);
    });
});
</script>
