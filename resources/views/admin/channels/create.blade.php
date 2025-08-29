@extends('layouts.app')

@section('title', 'Thêm Kênh Mới')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>Thêm Kênh Mới
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.channels.store') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Thông tin cơ bản -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>Thông Tin Cơ Bản
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Tên kênh -->
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-tag mr-1"></i>Tên Kênh *
                            </label>
                            <input type="text" name="name" id="name" class="form-control" 
                                   placeholder="Ví dụ: Kênh TikTok Chính" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Platform -->
                        <div class="form-group">
                            <label for="platform">
                                <i class="fas fa-globe mr-1"></i>Nền Tảng *
                            </label>
                            <select name="platform" id="platform" class="form-control" required onchange="togglePlatformFields()">
                                <option value="">-- Chọn nền tảng --</option>
                                <option value="tiktok" {{ old('platform') == 'tiktok' ? 'selected' : '' }}>
                                    <i class="fab fa-tiktok"></i> TikTok
                                </option>
                                <option value="youtube" {{ old('platform') == 'youtube' ? 'selected' : '' }}>
                                    <i class="fab fa-youtube"></i> YouTube
                                </option>
                            </select>
                            @error('platform')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Channel ID -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="channel_id">
                                        <i class="fas fa-id-card mr-1"></i>Channel ID
                                    </label>
                                    <input type="text" name="channel_id" id="channel_id" class="form-control" 
                                           placeholder="ID kênh trên platform" value="{{ old('channel_id') }}">
                                    @error('channel_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">
                                        <i class="fas fa-at mr-1"></i>Username/Handle
                                    </label>
                                    <input type="text" name="username" id="username" class="form-control" 
                                           placeholder="@username" value="{{ old('username') }}">
                                    @error('username')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Mô tả -->
                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-align-left mr-1"></i>Mô Tả Kênh
                            </label>
                            <textarea name="description" id="description" class="form-control" rows="3" 
                                      placeholder="Mô tả ngắn về kênh này...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- Logo Configuration -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-image mr-2"></i>Cấu Hình Logo
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Chọn logo -->
                        <div class="form-group">
                            <label for="logo_file">
                                <i class="fas fa-image mr-1"></i>Chọn Logo
                            </label>
                            <select name="logo_file" id="logo_file" class="form-control" onchange="previewSelectedLogo()">
                                <option value="">-- Không sử dụng logo --</option>
                                @foreach($logos as $logo)
                                    <option value="{{ $logo['name'] }}" data-url="{{ $logo['url'] }}" 
                                            {{ old('logo_file') == $logo['name'] ? 'selected' : '' }}>
                                        {{ $logo['display_name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <a href="{{ route('admin.logos.index') }}" target="_blank">
                                    <i class="fas fa-external-link-alt mr-1"></i>Quản lý logo
                                </a>
                            </small>
                            @error('logo_file')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="logo_config_section" style="display: none;">
                            <div class="row">
                                <!-- Vị trí logo -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="logo_position">
                                            <i class="fas fa-map-marker-alt mr-1"></i>Vị Trí
                                        </label>
                                        <select name="logo_position" id="logo_position" class="form-control">
                                            <option value="top-left" {{ old('logo_position') == 'top-left' ? 'selected' : '' }}>Góc trên trái</option>
                                            <option value="top-right" {{ old('logo_position') == 'top-right' ? 'selected' : '' }}>Góc trên phải</option>
                                            <option value="bottom-left" {{ old('logo_position') == 'bottom-left' ? 'selected' : '' }}>Góc dưới trái</option>
                                            <option value="bottom-right" {{ old('logo_position', 'bottom-right') == 'bottom-right' ? 'selected' : '' }}>Góc dưới phải</option>
                                            <option value="center" {{ old('logo_position') == 'center' ? 'selected' : '' }}>Giữa màn hình</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Kích thước -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="logo_size">
                                            <i class="fas fa-expand-arrows-alt mr-1"></i>Kích Thước (px)
                                        </label>
                                        <input type="range" name="logo_size" id="logo_size" class="form-control-range" 
                                               min="50" max="500" step="10" value="{{ old('logo_size', '100') }}" 
                                               oninput="updateLogoSizeDisplay(this.value)">
                                        <div class="text-center">
                                            <span id="logo_size_display" class="badge badge-primary">{{ old('logo_size', '100') }}px</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Độ trong suốt -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="logo_opacity">
                                            <i class="fas fa-adjust mr-1"></i>Độ Trong Suốt
                                        </label>
                                        <input type="range" name="logo_opacity" id="logo_opacity" class="form-control-range" 
                                               min="0" max="1" step="0.1" value="{{ old('logo_opacity', '1.0') }}" 
                                               oninput="updateOpacityDisplay(this.value)">
                                        <div class="text-center">
                                            <span id="opacity_display" class="badge badge-info">{{ old('logo_opacity', '1.0') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview logo -->
                            <div class="form-group">
                                <label>Preview Logo:</label>
                                <div id="logo_preview_container" class="border rounded p-3 text-center" 
                                     style="min-height: 120px; background: #f8f9fa; position: relative;">
                                    <div id="logo_preview_placeholder">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                        <p class="text-muted mt-2">Chọn logo để xem preview</p>
                                    </div>
                                    <img id="logo_preview_img" style="display: none; max-width: 100px; max-height: 100px; object-fit: contain;">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Default Settings -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-cog mr-2"></i>Cài Đặt Mặc Định
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <!-- Privacy -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="default_privacy">
                                        <i class="fas fa-eye mr-1"></i>Quyền Riêng Tư *
                                    </label>
                                    <select name="default_privacy" id="default_privacy" class="form-control" required>
                                        <option value="private" {{ old('default_privacy', 'private') == 'private' ? 'selected' : '' }}>Riêng tư</option>
                                        <option value="public" {{ old('default_privacy') == 'public' ? 'selected' : '' }}>Công khai</option>
                                        <option value="unlisted" {{ old('default_privacy') == 'unlisted' ? 'selected' : '' }}>Không liệt kê</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="default_category">
                                        <i class="fas fa-folder mr-1"></i>Danh Mục
                                    </label>
                                    <input type="text" name="default_category" id="default_category" class="form-control" 
                                           placeholder="Entertainment, Education..." value="{{ old('default_category') }}">
                                </div>
                            </div>

                            <!-- Auto Upload -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="auto_upload" name="auto_upload" value="1" 
                                               {{ old('auto_upload') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="auto_upload">
                                            <i class="fas fa-robot mr-1"></i>Tự Động Upload
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Tự động đăng video khi đến giờ hẹn</small>
                                </div>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="form-group">
                            <label for="default_tags">
                                <i class="fas fa-tags mr-1"></i>Tags Mặc Định
                            </label>
                            <input type="text" name="default_tags" id="default_tags" class="form-control" 
                                   placeholder="tag1, tag2, tag3..." value="{{ old('default_tags') }}">
                            <small class="form-text text-muted">Phân cách bằng dấu phẩy</small>
                        </div>

                    </div>
                </div>
            </div>

            <!-- API Configuration -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-key mr-2"></i>Cấu Hình API
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- TikTok API -->
                        <div id="tiktok_api_section" style="display: none;">
                            <h6 class="text-primary mb-3">
                                <i class="fab fa-tiktok mr-2"></i>TikTok API
                            </h6>

                            <div class="form-group">
                                <label for="tiktok_client_key">Client Key *</label>
                                <input type="text" name="tiktok_client_key" id="tiktok_client_key" class="form-control"
                                       placeholder="TikTok Client Key" value="{{ old('tiktok_client_key') }}" required>
                                <small class="form-text text-muted">Client Key từ TikTok Developer Portal</small>
                            </div>

                            <div class="form-group">
                                <label for="tiktok_client_secret">Client Secret *</label>
                                <input type="password" name="tiktok_client_secret" id="tiktok_client_secret" class="form-control"
                                       placeholder="TikTok Client Secret" value="{{ old('tiktok_client_secret') }}" required>
                                <small class="form-text text-muted">Client Secret từ TikTok Developer Portal</small>
                            </div>

                            <div class="form-group">
                                <label for="tiktok_access_token">Access Token</label>
                                <input type="password" name="tiktok_access_token" id="tiktok_access_token" class="form-control"
                                       placeholder="TikTok Access Token" value="{{ old('tiktok_access_token') }}" readonly>
                                <small class="form-text text-muted">Sẽ được tự động điền sau khi lấy token</small>
                            </div>

                            <div class="form-group">
                                <label for="tiktok_refresh_token">Refresh Token</label>
                                <input type="password" name="tiktok_refresh_token" id="tiktok_refresh_token" class="form-control"
                                       placeholder="TikTok Refresh Token" value="{{ old('tiktok_refresh_token') }}" readonly>
                                <small class="form-text text-muted">Sẽ được tự động điền sau khi lấy token</small>
                            </div>

                            <div class="form-group">
                                <div class="btn-group d-block" role="group">
                                    <button type="button" id="get_tiktok_token_btn" class="btn btn-primary mr-2" onclick="getTikTokToken()">
                                        <i class="fab fa-tiktok mr-2"></i>Lấy Access Token & Refresh Token
                                    </button>
                                    <button type="button" id="get_tiktok_channel_id_btn" class="btn btn-success" onclick="getTikTokChannelId()" disabled>
                                        <i class="fas fa-id-card mr-2"></i>Lấy Channel ID
                                    </button>
                                </div>
                                <small class="form-text text-muted mt-2">
                                    <strong>Bước 1:</strong> Lấy Access Token & Refresh Token từ TikTok<br>
                                    <strong>Bước 2:</strong> Lấy Channel ID từ thông tin tài khoản
                                </small>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Hướng dẫn:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Nhập Client Key và Client Secret từ TikTok Developer Portal</li>
                                    <li>Nhấn nút "Lấy Access Token & Refresh Token"</li>
                                    <li>Đăng nhập TikTok và cấp quyền cho ứng dụng</li>
                                    <li>Token sẽ được tự động điền vào form</li>
                                </ol>
                            </div>
                        </div>

                        <!-- YouTube API -->
                        <div id="youtube_api_section" style="display: none;">
                            <h6 class="text-danger mb-3">
                                <i class="fab fa-youtube mr-2"></i>YouTube API
                            </h6>
                            
                            <div class="form-group">
                                <label for="youtube_client_id">Client ID</label>
                                <input type="text" name="youtube_client_id" id="youtube_client_id" class="form-control" 
                                       placeholder="YouTube Client ID" value="{{ old('youtube_client_id') }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="youtube_client_secret">Client Secret</label>
                                <input type="password" name="youtube_client_secret" id="youtube_client_secret" class="form-control" 
                                       placeholder="YouTube Client Secret" value="{{ old('youtube_client_secret') }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="youtube_refresh_token">Refresh Token</label>
                                <input type="password" name="youtube_refresh_token" id="youtube_refresh_token" class="form-control" 
                                       placeholder="YouTube Refresh Token" value="{{ old('youtube_refresh_token') }}">
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Hướng dẫn:</strong> Lấy credentials từ Google Cloud Console
                            </div>
                        </div>

                        <div id="no_platform_selected" class="text-center text-muted py-4">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <p>Chọn nền tảng để cấu hình API</p>
                        </div>

                    </div>
                </div>

                <!-- Help -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-question-circle mr-2"></i>Trợ Giúp
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Lưu ý quan trọng:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i>API credentials được mã hóa an toàn</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Logo sẽ được áp dụng cho tất cả video</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Có thể thay đổi cài đặt sau khi tạo</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Test kết nối API sau khi lưu</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Tạo Kênh
                        </button>
                        <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Toggle platform-specific API fields
function togglePlatformFields() {
    const platform = document.getElementById('platform').value;
    
    // Hide all sections
    document.getElementById('tiktok_api_section').style.display = 'none';
    document.getElementById('youtube_api_section').style.display = 'none';
    document.getElementById('no_platform_selected').style.display = 'none';
    
    // Show relevant section
    if (platform === 'tiktok') {
        document.getElementById('tiktok_api_section').style.display = 'block';
    } else if (platform === 'youtube') {
        document.getElementById('youtube_api_section').style.display = 'block';
    } else {
        document.getElementById('no_platform_selected').style.display = 'block';
    }
}

// Logo functions
function previewSelectedLogo() {
    const logoSelect = document.getElementById('logo_file');
    const selectedOption = logoSelect.options[logoSelect.selectedIndex];
    const logoUrl = selectedOption.getAttribute('data-url');
    
    const placeholder = document.getElementById('logo_preview_placeholder');
    const previewImg = document.getElementById('logo_preview_img');
    const configSection = document.getElementById('logo_config_section');
    
    if (logoUrl) {
        placeholder.style.display = 'none';
        previewImg.src = logoUrl;
        previewImg.style.display = 'block';
        configSection.style.display = 'block';
    } else {
        placeholder.style.display = 'block';
        previewImg.style.display = 'none';
        configSection.style.display = 'none';
    }
}

// TikTok OAuth functions
function getTikTokToken() {
    const clientKey = document.getElementById('tiktok_client_key').value;
    const clientSecret = document.getElementById('tiktok_client_secret').value;

    if (!clientKey || !clientSecret) {
        alert('Vui lòng nhập Client Key và Client Secret trước');
        return;
    }

    // Store form data in sessionStorage to restore after OAuth
    const formData = new FormData(document.querySelector('form'));
    const formObject = {};
    for (let [key, value] of formData.entries()) {
        formObject[key] = value;
    }
    sessionStorage.setItem('tiktok_channel_form_data', JSON.stringify(formObject));

    // Create temporary channel data for OAuth
    const tempChannelData = {
        client_key: clientKey,
        client_secret: clientSecret
    };

    // Start OAuth flow
    const btn = document.getElementById('get_tiktok_token_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang chuyển hướng...';

    // Send request to start OAuth
    fetch('{{ route("admin.channels.tiktok.oauth.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(tempChannelData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to TikTok OAuth
            window.location.href = data.auth_url;
        } else {
            alert('Lỗi: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-tiktok mr-2"></i>Lấy Access Token & Refresh Token';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi kết nối với TikTok');
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-tiktok mr-2"></i>Lấy Access Token & Refresh Token';
    });
}

// TikTok Channel ID function
function getTikTokChannelId() {
    const accessToken = document.getElementById('tiktok_access_token').value;
    const clientKey = document.getElementById('tiktok_client_key').value;
    const clientSecret = document.getElementById('tiktok_client_secret').value;

    if (!accessToken) {
        alert('Vui lòng lấy Access Token trước khi lấy Channel ID');
        return;
    }

    if (!clientKey || !clientSecret) {
        alert('Vui lòng nhập Client Key và Client Secret');
        return;
    }

    const btn = document.getElementById('get_tiktok_channel_id_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lấy...';

    // Send request to get channel info
    fetch('{{ route("admin.channels.tiktok.get-channel-id") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            access_token: accessToken,
            client_key: clientKey,
            client_secret: clientSecret
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fill in the channel info
            document.getElementById('channel_id').value = data.channel_id;
            document.getElementById('username').value = data.username;

            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle mr-2"></i>
                <strong>Thành công!</strong> Đã lấy được Channel ID: <strong>${data.channel_id}</strong>
                ${data.username ? ` và Username: <strong>@${data.username}</strong>` : ''}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            `;

            // Insert after the button group
            const buttonGroup = btn.closest('.form-group');
            buttonGroup.parentNode.insertBefore(alertDiv, buttonGroup.nextSibling);

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Đã lấy Channel ID';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
        } else {
            alert('Lỗi khi lấy Channel ID: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-id-card mr-2"></i>Lấy Channel ID';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lấy Channel ID');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-id-card mr-2"></i>Lấy Channel ID';
    });
}

// Restore form data after OAuth redirect
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have OAuth result in URL
    const urlParams = new URLSearchParams(window.location.search);
    const oauthSuccess = urlParams.get('oauth_success');
    const accessToken = urlParams.get('access_token');
    const refreshToken = urlParams.get('refresh_token');

    if (oauthSuccess === '1' && accessToken && refreshToken) {
        // Fill in the tokens
        document.getElementById('tiktok_access_token').value = accessToken;
        document.getElementById('tiktok_refresh_token').value = refreshToken;

        // Enable Channel ID button
        const channelIdBtn = document.getElementById('get_tiktok_channel_id_btn');
        if (channelIdBtn) {
            channelIdBtn.disabled = false;
        }

        // Show success message
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Thành công!</strong> Đã lấy được Access Token và Refresh Token từ TikTok.
            Bây giờ bạn có thể lấy Channel ID.
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.card-body').firstChild);

        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Restore form data if available
    const savedFormData = sessionStorage.getItem('tiktok_channel_form_data');
    if (savedFormData) {
        const formObject = JSON.parse(savedFormData);
        for (let [key, value] of Object.entries(formObject)) {
            const input = document.querySelector(`[name="${key}"]`);
            if (input && !input.readOnly) {
                input.value = value;
                if (input.type === 'radio' || input.type === 'checkbox') {
                    input.checked = true;
                }
            }
        }
        sessionStorage.removeItem('tiktok_channel_form_data');
    }

    // Enable/disable Channel ID button based on access token
    function checkTikTokTokens() {
        const accessToken = document.getElementById('tiktok_access_token').value;
        const channelIdBtn = document.getElementById('get_tiktok_channel_id_btn');

        if (channelIdBtn) {
            channelIdBtn.disabled = !accessToken;
        }
    }

    // Monitor access token field changes
    const accessTokenField = document.getElementById('tiktok_access_token');
    if (accessTokenField) {
        accessTokenField.addEventListener('input', checkTikTokTokens);
        // Check initial state
        checkTikTokTokens();
    }
});

function updateLogoSizeDisplay(value) {
    document.getElementById('logo_size_display').textContent = value + 'px';
    
    // Update preview size
    const previewImg = document.getElementById('logo_preview_img');
    if (previewImg.style.display !== 'none') {
        const scale = Math.min(1, 100 / value);
        previewImg.style.maxWidth = (value * scale) + 'px';
        previewImg.style.maxHeight = (value * scale) + 'px';
    }
}

function updateOpacityDisplay(value) {
    document.getElementById('opacity_display').textContent = value;
    
    // Update preview opacity
    const previewImg = document.getElementById('logo_preview_img');
    if (previewImg.style.display !== 'none') {
        previewImg.style.opacity = value;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePlatformFields();
    previewSelectedLogo();
});
</script>
@endpush
@endsection
