@extends('layouts.app')

@section('title', 'Chỉnh Sửa Kênh - ' . $channel->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>Chỉnh Sửa Kênh: {{ $channel->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.channels.show', $channel) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.channels.update', $channel) }}" method="POST">
        @csrf
        @method('PUT')
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
                                   placeholder="Ví dụ: Kênh TikTok Chính" value="{{ old('name', $channel->name) }}" required>
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Platform (readonly) -->
                        <div class="form-group">
                            <label for="platform">
                                <i class="fas fa-globe mr-1"></i>Nền Tảng
                            </label>
                            <input type="text" class="form-control" value="{{ ucfirst($channel->platform) }}" readonly>
                            <input type="hidden" name="platform" value="{{ $channel->platform }}">
                            <small class="form-text text-muted">Không thể thay đổi nền tảng sau khi tạo</small>
                        </div>

                        <div class="row">
                            <!-- Channel ID -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="channel_id">
                                        <i class="fas fa-id-card mr-1"></i>Channel ID
                                    </label>
                                    <input type="text" name="channel_id" id="channel_id" class="form-control" 
                                           placeholder="ID kênh trên platform" value="{{ old('channel_id', $channel->channel_id) }}">
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
                                           placeholder="@username" value="{{ old('username', $channel->username) }}">
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
                                      placeholder="Mô tả ngắn về kênh này...">{{ old('description', $channel->description) }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', $channel->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">
                                    <i class="fas fa-power-off mr-1"></i>Kích Hoạt Kênh
                                </label>
                            </div>
                            <small class="form-text text-muted">Kênh không hoạt động sẽ không thể upload video</small>
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
                        
                        <!-- Current Logo -->
                        @if($channel->logo_config && isset($channel->logo_config['logo_file']))
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <img src="{{ route('admin.logo.serve', $channel->logo_config['logo_file']) }}" 
                                     alt="Current Logo" style="max-height: 40px; object-fit: contain;" class="mr-3">
                                <div>
                                    <strong>Logo hiện tại:</strong> {{ $channel->logo_config['logo_file'] }}<br>
                                    <small>Vị trí: {{ $channel->logo_config['position'] ?? 'bottom-right' }} | 
                                           Kích thước: {{ $channel->logo_config['size'] ?? 100 }}px</small>
                                </div>
                                <div class="ml-auto">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="remove_logo" name="remove_logo" value="1">
                                        <label class="custom-control-label" for="remove_logo">
                                            <i class="fas fa-trash mr-1"></i>Xóa logo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Chọn logo -->
                        <div class="form-group">
                            <label for="logo_file">
                                <i class="fas fa-image mr-1"></i>Chọn Logo Mới
                            </label>
                            <select name="logo_file" id="logo_file" class="form-control" onchange="previewSelectedLogo()">
                                <option value="">-- Không thay đổi --</option>
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
                                            <option value="top-left" {{ old('logo_position', $channel->logo_config['position'] ?? '') == 'top-left' ? 'selected' : '' }}>Góc trên trái</option>
                                            <option value="top-right" {{ old('logo_position', $channel->logo_config['position'] ?? '') == 'top-right' ? 'selected' : '' }}>Góc trên phải</option>
                                            <option value="bottom-left" {{ old('logo_position', $channel->logo_config['position'] ?? '') == 'bottom-left' ? 'selected' : '' }}>Góc dưới trái</option>
                                            <option value="bottom-right" {{ old('logo_position', $channel->logo_config['position'] ?? 'bottom-right') == 'bottom-right' ? 'selected' : '' }}>Góc dưới phải</option>
                                            <option value="center" {{ old('logo_position', $channel->logo_config['position'] ?? '') == 'center' ? 'selected' : '' }}>Giữa màn hình</option>
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
                                               min="50" max="500" step="10" value="{{ old('logo_size', $channel->logo_config['size'] ?? '100') }}" 
                                               oninput="updateLogoSizeDisplay(this.value)">
                                        <div class="text-center">
                                            <span id="logo_size_display" class="badge badge-primary">{{ old('logo_size', $channel->logo_config['size'] ?? '100') }}px</span>
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
                                               min="0" max="1" step="0.1" value="{{ old('logo_opacity', $channel->logo_config['opacity'] ?? '1.0') }}" 
                                               oninput="updateOpacityDisplay(this.value)">
                                        <div class="text-center">
                                            <span id="opacity_display" class="badge badge-info">{{ old('logo_opacity', $channel->logo_config['opacity'] ?? '1.0') }}</span>
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
                                        <option value="private" {{ old('default_privacy', $channel->default_privacy) == 'private' ? 'selected' : '' }}>Riêng tư</option>
                                        <option value="public" {{ old('default_privacy', $channel->default_privacy) == 'public' ? 'selected' : '' }}>Công khai</option>
                                        <option value="unlisted" {{ old('default_privacy', $channel->default_privacy) == 'unlisted' ? 'selected' : '' }}>Không liệt kê</option>
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
                                           placeholder="Entertainment, Education..." value="{{ old('default_category', $channel->default_category) }}">
                                </div>
                            </div>

                            <!-- Auto Upload -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="auto_upload" name="auto_upload" value="1" 
                                               {{ old('auto_upload', $channel->auto_upload) ? 'checked' : '' }}>
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
                                   placeholder="tag1, tag2, tag3..." 
                                   value="{{ old('default_tags', $channel->default_tags ? implode(', ', $channel->default_tags) : '') }}">
                            <small class="form-text text-muted">Phân cách bằng dấu phẩy</small>
                        </div>

                    </div>
                </div>
            </div>

            <!-- API Configuration & Info -->
            <div class="col-lg-4">
                <!-- Current Status -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>Trạng Thái Hiện Tại
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        @if($channel->is_active)
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-success">Kênh Đang Hoạt Động</h6>
                        @else
                            <i class="fas fa-pause-circle fa-3x text-secondary mb-3"></i>
                            <h6 class="text-secondary">Kênh Tạm Dừng</h6>
                        @endif
                        
                        <p class="text-muted">
                            Tạo: {{ $channel->created_at->format('d/m/Y') }}<br>
                            Cập nhật: {{ $channel->updated_at->diffForHumans() }}
                        </p>
                        
                        @if($channel->last_upload_at)
                            <p class="text-info">
                                <i class="fas fa-upload mr-1"></i>
                                Upload cuối: {{ $channel->last_upload_at->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- API Configuration -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-key mr-2"></i>API Configuration
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($channel->platform === 'tiktok')
                            <!-- TikTok OAuth Status -->
                            <div class="mb-4">
                                @if($channel->hasValidCredentials())
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <strong>Đã kết nối TikTok</strong>
                                        <p class="mb-2">Kênh đã được authorize và sẵn sàng upload video.</p>

                                        @if(isset($channel->api_credentials['user_info']))
                                            @php $userInfo = $channel->api_credentials['user_info']; @endphp
                                            <small class="text-muted">
                                                👤 {{ $userInfo['display_name'] ?? 'N/A' }}
                                                @if(isset($userInfo['username']))
                                                    (@{{ $userInfo['username'] }})
                                                @endif
                                            </small>
                                        @endif

                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-info" onclick="testTikTokConnection({{ $channel->id }})">
                                                <i class="fas fa-sync mr-1"></i>Test Connection
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="refreshTikTokToken({{ $channel->id }})">
                                                <i class="fas fa-refresh mr-1"></i>Refresh Token
                                            </button>
                                            <a href="{{ route('admin.channels.tiktok.authorize', ['channel_id' => $channel->id]) }}"
                                               class="btn btn-sm btn-secondary">
                                                <i class="fas fa-redo mr-1"></i>Re-authorize
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <strong>Chưa kết nối TikTok</strong>
                                        <p class="mb-2">Cần authorize với TikTok để có thể upload video tự động.</p>
                                        <a href="{{ route('admin.channels.tiktok.authorize', ['channel_id' => $channel->id]) }}"
                                           class="btn btn-primary">
                                            <i class="fab fa-tiktok mr-2"></i>Kết nối TikTok
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- Manual Token Input (Advanced) -->
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Cập Nhật Token Thủ Công (Nâng Cao)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Lưu ý:</strong> Chỉ sử dụng khi cần thiết. Khuyến nghị sử dụng OAuth flow ở trên.
                                    </div>

                                    <div class="form-group">
                                        <label for="tiktok_access_token">
                                            <i class="fas fa-key mr-1"></i>Access Token
                                        </label>
                                        <input type="password" name="tiktok_access_token" id="tiktok_access_token"
                                               class="form-control" placeholder="Nhập TikTok Access Token mới (để trống nếu không thay đổi)">
                                        <small class="text-muted">
                                            Để trống nếu không muốn thay đổi token hiện tại
                                        </small>
                                        @error('tiktok_access_token')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="tiktok_refresh_token">
                                            <i class="fas fa-sync-alt mr-1"></i>Refresh Token
                                        </label>
                                        <input type="password" name="tiktok_refresh_token" id="tiktok_refresh_token"
                                               class="form-control" placeholder="Nhập TikTok Refresh Token mới (để trống nếu không thay đổi)">
                                        <small class="text-muted">
                                            Để trống nếu không muốn thay đổi refresh token hiện tại
                                        </small>
                                        @error('tiktok_refresh_token')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="tiktok_open_id">
                                            <i class="fas fa-user mr-1"></i>Open ID (User ID)
                                        </label>
                                        <input type="text" name="tiktok_open_id" id="tiktok_open_id"
                                               class="form-control" placeholder="Nhập TikTok Open ID (để trống nếu không thay đổi)"
                                               value="{{ old('tiktok_open_id', isset($channel->api_credentials['open_id']) ? $channel->api_credentials['open_id'] : '') }}">
                                        <small class="text-muted">
                                            ID người dùng TikTok được trả về từ OAuth
                                        </small>
                                        @error('tiktok_open_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="clear_tiktok_credentials" id="clear_tiktok_credentials"
                                                   class="form-check-input" value="1">
                                            <label class="form-check-label text-danger" for="clear_tiktok_credentials">
                                                <i class="fas fa-trash mr-1"></i>
                                                Xóa tất cả TikTok credentials
                                            </label>
                                        </div>
                                        <small class="text-muted">
                                            Chọn để xóa hoàn toàn kết nối TikTok hiện tại
                                        </small>
                                    </div>
                                </div>
                            </div>

                        @elseif($channel->platform === 'youtube')
                            <!-- YouTube API (Future) -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>YouTube API</strong> sẽ được triển khai trong phiên bản tiếp theo.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie mr-2"></i>Thống Kê Nhanh
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-warning">{{ $channel->scheduledPosts()->where('status', 'pending')->count() }}</h4>
                                <small class="text-muted">Chờ đăng</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">{{ $channel->scheduledPosts()->where('status', 'uploaded')->count() }}</h4>
                                <small class="text-muted">Đã đăng</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h5 class="text-info">{{ $channel->getUploadCount('7 days') }}</h5>
                                <small class="text-muted">Tuần này</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-secondary">{{ $channel->getUploadCount('30 days') }}</h5>
                                <small class="text-muted">Tháng này</small>
                            </div>
                        </div>
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
                            <i class="fas fa-save mr-2"></i>Cập Nhật Kênh
                        </button>
                        <a href="{{ route('admin.channels.show', $channel) }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </a>
                        
                        <div class="float-right">
                            <form action="{{ route('admin.channels.destroy', $channel) }}" 
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Bạn có chắc muốn xóa kênh này?\n\nTất cả lịch đăng sẽ bị xóa!')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash mr-2"></i>Xóa Kênh
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
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

function testConnection(channelId) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    btn.disabled = true;
    
    $.ajax({
        url: `/admin/channels/${channelId}/test-connection`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ ' + response.message);
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Có lỗi xảy ra khi test kết nối');
        },
        complete: function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// TikTok functions
function testTikTokConnection(channelId) {
    const btn = event.target;
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    $.ajax({
        url: `/admin/channels/${channelId}/tiktok/test`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                let message = '✅ ' + response.message;
                if (response.data) {
                    message += '\n\nThông tin kênh:';
                    if (response.data.username) message += '\nUsername: ' + response.data.username;
                    if (response.data.display_name) message += '\nDisplay Name: ' + response.data.display_name;
                }
                alert(message);
            } else {
                alert('❌ ' + response.error);
            }
        },
        error: function() {
            alert('❌ Có lỗi xảy ra khi test kết nối TikTok');
        },
        complete: function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

function refreshTikTokToken(channelId) {
    const btn = event.target;
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    $.ajax({
        url: `/admin/channels/${channelId}/tiktok/refresh`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ ' + response.message);
                location.reload(); // Reload để cập nhật thông tin
            } else {
                alert('❌ ' + response.error);
            }
        },
        error: function() {
            alert('❌ Có lỗi xảy ra khi refresh token');
        },
        complete: function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// Clear credentials checkbox handler
document.addEventListener('DOMContentLoaded', function() {
    const clearCheckbox = document.getElementById('clear_tiktok_credentials');
    const tokenInputs = ['tiktok_access_token', 'tiktok_refresh_token', 'tiktok_open_id'];

    if (clearCheckbox) {
        clearCheckbox.addEventListener('change', function() {
            tokenInputs.forEach(function(inputId) {
                const input = document.getElementById(inputId);
                if (input) {
                    input.disabled = this.checked;
                    if (this.checked) {
                        input.value = '';
                        input.placeholder = 'Sẽ được xóa khi lưu';
                    } else {
                        input.placeholder = input.getAttribute('data-original-placeholder') || '';
                    }
                }
            }.bind(this));
        });

        // Store original placeholders
        tokenInputs.forEach(function(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.setAttribute('data-original-placeholder', input.placeholder);
            }
        });
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    previewSelectedLogo();
});
</script>
@endpush
@endsection
