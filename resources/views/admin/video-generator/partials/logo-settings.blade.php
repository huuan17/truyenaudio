<!-- Logo Settings Component (TikTok only) -->
<div class="form-section">
    <div class="card border-secondary">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0">
                <i class="fas fa-image mr-2"></i>Cài Đặt Logo
                <div class="form-check form-check-inline float-right">
                    <input type="checkbox" name="use_logo" id="{{ $prefix }}_use_logo" 
                           class="form-check-input" value="1" 
                           onchange="toggleLogoOptions('{{ $prefix }}')"
                           {{ old('use_logo') ? 'checked' : '' }}>
                    <label class="form-check-label text-white" for="{{ $prefix }}_use_logo">
                        Sử dụng logo
                    </label>
                </div>
            </h6>
        </div>
        <div class="card-body" id="{{ $prefix }}_logo_options" style="display: none;">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="{{ $prefix }}_logo_file">Chọn logo</label>
                        <select name="logo_file" id="{{ $prefix }}_logo_file" class="form-control" 
                                onchange="updateLogoPreview('{{ $prefix }}')">
                            <option value="">-- Chọn logo --</option>
                            @foreach($logos as $logo)
                                <option value="{{ $logo['filename'] }}" 
                                        data-path="{{ asset('storage/logos/' . $logo['filename']) }}"
                                        {{ old('logo_file') == $logo['filename'] ? 'selected' : '' }}>
                                    {{ $logo['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Logo sẽ được thêm vào video TikTok
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Preview logo</label>
                        <div id="{{ $prefix }}_logo_preview" class="border rounded p-3 text-center" 
                             style="height: 120px; background: #f8f9fa;">
                            <div id="{{ $prefix }}_logo_preview_placeholder" class="text-muted">
                                <i class="fas fa-image fa-2x mb-2"></i>
                                <br>Chọn logo để xem preview
                            </div>
                            <img id="{{ $prefix }}_logo_preview_img" src="" alt="Logo preview" 
                                 style="max-width: 100%; max-height: 100%; display: none;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="{{ $prefix }}_logo_position">Vị trí logo</label>
                        <select name="logo_position" id="{{ $prefix }}_logo_position" class="form-control">
                            <option value="top-left" {{ old('logo_position') == 'top-left' ? 'selected' : '' }}>Trên trái</option>
                            <option value="top-right" {{ old('logo_position') == 'top-right' ? 'selected' : '' }}>Trên phải</option>
                            <option value="bottom-left" {{ old('logo_position') == 'bottom-left' ? 'selected' : '' }}>Dưới trái</option>
                            <option value="bottom-right" {{ old('logo_position', 'bottom-right') == 'bottom-right' ? 'selected' : '' }}>Dưới phải</option>
                            <option value="center" {{ old('logo_position') == 'center' ? 'selected' : '' }}>Giữa</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="{{ $prefix }}_logo_size">Kích thước logo (px)</label>
                        <input type="range" name="logo_size" id="{{ $prefix }}_logo_size" 
                               class="form-control-range" min="50" max="500" 
                               value="{{ old('logo_size', '100') }}" 
                               oninput="updateLogoSizeDisplay('{{ $prefix }}', this.value)">
                        <div class="d-flex justify-content-between">
                            <small>50px</small>
                            <small id="{{ $prefix }}_logo_size_display" class="font-weight-bold">{{ old('logo_size', '100') }}px</small>
                            <small>500px</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="form-control-plaintext">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Khuyến nghị: 80-120px cho TikTok
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Lưu ý:</strong> Logo sẽ được thêm vào video với độ trong suốt phù hợp. 
                Chọn vị trí không che khuất nội dung chính của video.
            </div>

        </div>
    </div>
</div>

<script>
function toggleLogoOptions(prefix) {
    const enabled = document.getElementById(prefix + '_use_logo').checked;
    const optionsDiv = document.getElementById(prefix + '_logo_options');
    
    if (enabled) {
        optionsDiv.style.display = 'block';
    } else {
        optionsDiv.style.display = 'none';
    }
}

function updateLogoPreview(prefix) {
    const select = document.getElementById(prefix + '_logo_file');
    const selectedOption = select.options[select.selectedIndex];
    const logoUrl = selectedOption.getAttribute('data-path');
    
    const placeholder = document.getElementById(prefix + '_logo_preview_placeholder');
    const previewImg = document.getElementById(prefix + '_logo_preview_img');
    
    if (logoUrl) {
        placeholder.style.display = 'none';
        previewImg.src = logoUrl;
        previewImg.style.display = 'block';
    } else {
        placeholder.style.display = 'block';
        previewImg.style.display = 'none';
    }
}

function updateLogoSizeDisplay(prefix, value) {
    document.getElementById(prefix + '_logo_size_display').textContent = value + 'px';
    
    // Update preview size if logo is selected
    const previewImg = document.getElementById(prefix + '_logo_preview_img');
    if (previewImg.style.display !== 'none') {
        const scale = Math.min(1, 100 / value); // Scale down if too large for preview
        previewImg.style.maxWidth = (value * scale) + 'px';
        previewImg.style.maxHeight = (value * scale) + 'px';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all logo options
    const logoCheckboxes = document.querySelectorAll('input[name="use_logo"]');
    logoCheckboxes.forEach(function(checkbox) {
        const prefix = checkbox.id.replace('_use_logo', '');
        toggleLogoOptions(prefix);
        updateLogoPreview(prefix);
        
        const sizeSlider = document.getElementById(prefix + '_logo_size');
        if (sizeSlider) {
            updateLogoSizeDisplay(prefix, sizeSlider.value);
        }
    });
});
</script>
