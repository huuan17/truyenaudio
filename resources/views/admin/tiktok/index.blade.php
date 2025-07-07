@extends('layouts.app')

@section('title', 'TikTok Video Generator')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-tiktok mr-2"></i>TikTok Video Generator
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Tạo video review sản phẩm cho TikTok từ kịch bản text, video sản phẩm và ảnh sản phẩm.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form tạo video -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-plus mr-2"></i>Tạo Video Mới
                    </h4>
                </div>
                <form action="{{ route('admin.tiktok.generate') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        
                        <!-- Kịch bản -->
                        <div class="form-group">
                            <label for="script_text">
                                <i class="fas fa-script mr-1"></i>Kịch bản Review Sản Phẩm *
                            </label>
                            <textarea name="script_text" id="script_text" class="form-control" rows="6" 
                                      placeholder="Nhập kịch bản review sản phẩm của bạn..." required>{{ old('script_text') }}</textarea>
                            <small class="form-text text-muted">
                                Kịch bản sẽ được chuyển đổi thành giọng nói bằng AI
                            </small>
                            @error('script_text')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Video sản phẩm -->
                        <div class="form-group">
                            <label for="product_video">
                                <i class="fas fa-video mr-1"></i>Video Sản Phẩm *
                            </label>
                            <input type="file" name="product_video" id="product_video" 
                                   class="form-control-file" accept="video/mp4,video/avi,video/mov" required>
                            <small class="form-text text-muted">
                                Định dạng: MP4, AVI, MOV. Tối đa 100MB. Tiếng sẽ được xóa tự động.
                            </small>
                            @error('product_video')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Ảnh sản phẩm -->
                        <div class="form-group">
                            <label for="product_image">
                                <i class="fas fa-image mr-1"></i>Ảnh Sản Phẩm (Tùy chọn)
                            </label>
                            <input type="file" name="product_image" id="product_image" 
                                   class="form-control-file" accept="image/jpeg,image/jpg,image/png">
                            <small class="form-text text-muted">
                                Định dạng: JPG, PNG. Tối đa 10MB. Sẽ được ghép vào cuối video.
                            </small>
                            @error('product_image')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Giọng đọc -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="voice">
                                        <i class="fas fa-microphone mr-1"></i>Giọng Đọc
                                    </label>
                                    <select name="voice" id="voice" class="form-control" required>
                                        @foreach($voices as $code => $name)
                                            <option value="{{ $code }}" {{ old('voice', 'hn_female_ngochuyen_full_48k-fhg') == $code ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Bitrate -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="bitrate">
                                        <i class="fas fa-music mr-1"></i>Chất Lượng Audio
                                    </label>
                                    <select name="bitrate" id="bitrate" class="form-control" required>
                                        <option value="64">64 kbps</option>
                                        <option value="128" {{ old('bitrate', '128') == '128' ? 'selected' : '' }}>128 kbps</option>
                                        <option value="192">192 kbps</option>
                                        <option value="256">256 kbps</option>
                                        <option value="320">320 kbps</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tốc độ -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="speed">
                                        <i class="fas fa-tachometer-alt mr-1"></i>Tốc Độ Đọc
                                    </label>
                                    <select name="speed" id="speed" class="form-control" required>
                                        <option value="0.5">0.5x (Chậm)</option>
                                        <option value="0.75">0.75x</option>
                                        <option value="1.0" {{ old('speed', '1.0') == '1.0' ? 'selected' : '' }}>1.0x (Bình thường)</option>
                                        <option value="1.25">1.25x</option>
                                        <option value="1.5">1.5x</option>
                                        <option value="2.0">2.0x (Nhanh)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Channel Selection -->
                        <div class="form-group">
                            <label for="channel_id">
                                <i class="fas fa-broadcast-tower mr-1"></i>Chọn Kênh Đăng
                            </label>
                            <select name="channel_id" id="channel_id" class="form-control" onchange="updateChannelSettings()">
                                <option value="">-- Không đăng lên kênh --</option>
                                @foreach($channels as $channel)
                                    <option value="{{ $channel->id }}"
                                            data-logo="{{ $channel->logo_config ? json_encode($channel->logo_config) : '' }}"
                                            data-privacy="{{ $channel->default_privacy }}"
                                            data-tags="{{ $channel->default_tags ? implode(', ', $channel->default_tags) : '' }}"
                                            data-category="{{ $channel->default_category }}"
                                            {{ old('channel_id') == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                        @if($channel->username)
                                            (@{{ $channel->username }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Chọn kênh để tự động áp dụng logo và cài đặt của kênh
                                @if($channels->count() == 0)
                                    <br><a href="{{ route('admin.channels.create') }}" target="_blank">
                                        <i class="fas fa-plus mr-1"></i>Tạo kênh mới
                                    </a>
                                @endif
                            </small>
                            @error('channel_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Channel Info -->
                        <div id="channel_info" style="display: none;">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle mr-2"></i>Cài đặt từ kênh sẽ được áp dụng:</h6>
                                <div id="channel_details"></div>
                            </div>
                        </div>

                        <!-- Scheduling Options -->
                        <div id="scheduling_section" style="display: none;">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="schedule_post" name="schedule_post" value="1"
                                           {{ old('schedule_post') ? 'checked' : '' }} onchange="toggleScheduling()">
                                    <label class="custom-control-label" for="schedule_post">
                                        <i class="fas fa-calendar-alt mr-1"></i>Lên lịch đăng video
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Tự động đăng video lên kênh vào thời gian đã hẹn
                                </small>
                            </div>

                            <div id="schedule_options" style="display: none;">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="scheduled_date">Ngày đăng</label>
                                            <input type="date" name="scheduled_date" id="scheduled_date" class="form-control"
                                                   value="{{ old('scheduled_date', now()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="scheduled_time">Giờ đăng</label>
                                            <input type="time" name="scheduled_time" id="scheduled_time" class="form-control"
                                                   value="{{ old('scheduled_time', now()->addHour()->format('H:i')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="post_title">Tiêu đề</label>
                                            <input type="text" name="post_title" id="post_title" class="form-control"
                                                   placeholder="Tiêu đề video..." value="{{ old('post_title') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="post_description">Mô tả</label>
                                    <textarea name="post_description" id="post_description" class="form-control" rows="3"
                                              placeholder="Mô tả video...">{{ old('post_description') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="post_tags">Tags</label>
                                    <input type="text" name="post_tags" id="post_tags" class="form-control"
                                           placeholder="tag1, tag2, tag3..." value="{{ old('post_tags') }}">
                                    <small class="form-text text-muted">
                                        Để trống để sử dụng tags mặc định của kênh
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Logo Options -->
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="use_logo" name="use_logo" value="1"
                                       {{ old('use_logo') ? 'checked' : '' }} onchange="toggleLogoOptions()">
                                <label class="custom-control-label" for="use_logo">
                                    <i class="fas fa-image mr-1"></i>Sử dụng Logo Tùy Chỉnh
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Ghi đè logo của kênh bằng logo tùy chỉnh
                            </small>
                        </div>

                        <!-- Logo Settings (ẩn mặc định) -->
                        <div id="logo_settings" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-cog mr-2"></i>Cài Đặt Logo</h6>
                                </div>
                                <div class="card-body">

                                    <!-- Chọn logo -->
                                    <div class="form-group">
                                        <label for="logo_file">
                                            <i class="fas fa-image mr-1"></i>Chọn Logo
                                        </label>
                                        <select name="logo_file" id="logo_file" class="form-control" onchange="previewSelectedLogo()">
                                            <option value="">-- Chọn logo --</option>
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

                                    <div class="row">
                                        <!-- Vị trí logo -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="logo_position">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>Vị Trí Logo
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

                                        <!-- Kích thước logo -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="logo_size">
                                                    <i class="fas fa-expand-arrows-alt mr-1"></i>Kích Thước (px)
                                                </label>
                                                <input type="range" name="logo_size" id="logo_size" class="form-control-range"
                                                       min="50" max="500" step="10" value="{{ old('logo_size', '100') }}"
                                                       oninput="updateLogoSizeDisplay(this.value)">
                                                <div class="d-flex justify-content-between">
                                                    <small>50px</small>
                                                    <span id="logo_size_display" class="badge badge-primary">{{ old('logo_size', '100') }}px</span>
                                                    <small>500px</small>
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

                        <!-- Volume Control -->
                        <div class="form-group">
                            <label for="volume">
                                <i class="fas fa-volume-up mr-1"></i>Mức Âm Lượng (dB)
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="range" name="volume" id="volume" class="form-control-range"
                                           min="-30" max="30" step="1" value="{{ old('volume', '18') }}"
                                           oninput="updateVolumeDisplay(this.value)">
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="number" id="volume_display" class="form-control"
                                               value="{{ old('volume', '18') }}" min="-30" max="30" step="1"
                                               onchange="updateVolumeSlider(this.value)" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">dB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <strong>Khuyến nghị:</strong> 18dB (mặc định) |
                                <span class="text-info">0dB = âm lượng gốc</span> |
                                <span class="text-warning">Âm: giảm âm lượng</span> |
                                <span class="text-success">Dương: tăng âm lượng</span>
                            </small>
                            @error('volume')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tên file output -->
                        <div class="form-group">
                            <label for="output_name">
                                <i class="fas fa-file-video mr-1"></i>Tên File Output (Tùy chọn)
                            </label>
                            <input type="text" name="output_name" id="output_name" class="form-control" 
                                   placeholder="Ví dụ: review_iphone_15" value="{{ old('output_name') }}">
                            <small class="form-text text-muted">
                                Để trống để tự động tạo tên. Phần mở rộng .mp4 sẽ được thêm tự động.
                            </small>
                            @error('output_name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cogs mr-2"></i>Tạo Video TikTok
                        </button>
                        <button type="reset" class="btn btn-secondary ml-2">
                            <i class="fas fa-undo mr-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar thông tin -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>Quy Trình Xử Lý
                    </h4>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-primary">Các Bước Tự Động</span>
                        </div>
                        
                        <div>
                            <i class="fas fa-microphone bg-blue"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Text to Speech</h3>
                                <div class="timeline-body">
                                    Chuyển đổi kịch bản thành giọng nói bằng VBee AI
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-volume-mute bg-yellow"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Xóa Tiếng Video</h3>
                                <div class="timeline-body">
                                    Loại bỏ âm thanh gốc từ video sản phẩm
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-cut bg-orange"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Cắt Video</h3>
                                <div class="timeline-body">
                                    Điều chỉnh độ dài video khớp với audio
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-music bg-green"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Ghép Audio + Volume</h3>
                                <div class="timeline-body">
                                    Kết hợp giọng nói AI với video sản phẩm và điều chỉnh âm lượng (mặc định: 18dB)
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-mobile-alt bg-purple"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Tối Ưu TikTok</h3>
                                <div class="timeline-body">
                                    Chuyển đổi về tỷ lệ 9:16 cho TikTok
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yêu cầu hệ thống -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-server mr-2"></i>Yêu Cầu Hệ Thống
                    </h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success mr-2"></i>FFmpeg đã cài đặt</li>
                        <li><i class="fas fa-check text-success mr-2"></i>VBee API Token</li>
                        <li><i class="fas fa-check text-success mr-2"></i>PHP >= 8.1</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Dung lượng đĩa đủ</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách video đã tạo -->
    @if(count($existingVideos) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-video mr-2"></i>Video Đã Tạo ({{ count($existingVideos) }})
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tên File</th>
                                    <th>Kích Thước</th>
                                    <th>Ngày Tạo</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existingVideos as $video)
                                <tr>
                                    <td>
                                        <i class="fas fa-file-video text-primary mr-2"></i>
                                        {{ $video['name'] }}
                                    </td>
                                    <td>{{ number_format($video['size'] / 1024 / 1024, 2) }} MB</td>
                                    <td>{{ date('d/m/Y H:i', $video['created']) }}</td>
                                    <td>
                                        <a href="{{ route('admin.tiktok.download', $video['name']) }}" 
                                           class="btn btn-sm btn-success" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger ml-1" 
                                                onclick="deleteVideo('{{ $video['name'] }}')" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
// Volume control functions
function updateVolumeDisplay(value) {
    document.getElementById('volume_display').value = value;
    updateVolumeColor(value);
}

function updateVolumeSlider(value) {
    document.getElementById('volume').value = value;
    updateVolumeColor(value);
}

function updateVolumeColor(value) {
    const display = document.getElementById('volume_display');
    const slider = document.getElementById('volume');

    // Thay đổi màu sắc dựa trên giá trị
    if (value < 0) {
        display.style.color = '#ffc107'; // Warning - giảm âm lượng
        slider.style.background = 'linear-gradient(to right, #ffc107 0%, #ffc107 ' + ((value + 30) / 60 * 100) + '%, #ddd ' + ((value + 30) / 60 * 100) + '%, #ddd 100%)';
    } else if (value == 0) {
        display.style.color = '#17a2b8'; // Info - âm lượng gốc
        slider.style.background = 'linear-gradient(to right, #17a2b8 0%, #17a2b8 50%, #ddd 50%, #ddd 100%)';
    } else {
        display.style.color = '#28a745'; // Success - tăng âm lượng
        slider.style.background = 'linear-gradient(to right, #28a745 0%, #28a745 ' + ((value + 30) / 60 * 100) + '%, #ddd ' + ((value + 30) / 60 * 100) + '%, #ddd 100%)';
    }
}

function deleteVideo(filename) {
    if (confirm('Bạn có chắc muốn xóa video này?')) {
        $.ajax({
            url: '{{ route("admin.tiktok.delete") }}',
            method: 'DELETE',
            data: {
                filename: filename,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Lỗi: ' + response.message);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra khi xóa video');
            }
        });
    }
}

// Channel functions
function updateChannelSettings() {
    const channelSelect = document.getElementById('channel_id');
    const selectedOption = channelSelect.options[channelSelect.selectedIndex];
    const channelInfo = document.getElementById('channel_info');
    const channelDetails = document.getElementById('channel_details');
    const schedulingSection = document.getElementById('scheduling_section');

    if (selectedOption.value) {
        // Show channel info
        const logoConfig = selectedOption.getAttribute('data-logo');
        const privacy = selectedOption.getAttribute('data-privacy');
        const tags = selectedOption.getAttribute('data-tags');
        const category = selectedOption.getAttribute('data-category');

        let details = `<strong>Privacy:</strong> ${privacy}<br>`;
        if (category) details += `<strong>Category:</strong> ${category}<br>`;
        if (tags) details += `<strong>Tags:</strong> ${tags}<br>`;
        if (logoConfig) details += `<strong>Logo:</strong> Sẽ được áp dụng từ kênh`;

        channelDetails.innerHTML = details;
        channelInfo.style.display = 'block';
        schedulingSection.style.display = 'block';

        // Auto-fill tags if available
        if (tags && !document.getElementById('post_tags').value) {
            document.getElementById('post_tags').value = tags;
        }
    } else {
        channelInfo.style.display = 'none';
        schedulingSection.style.display = 'none';
    }
}

function toggleScheduling() {
    const scheduleCheckbox = document.getElementById('schedule_post');
    const scheduleOptions = document.getElementById('schedule_options');

    if (scheduleCheckbox.checked) {
        scheduleOptions.style.display = 'block';
    } else {
        scheduleOptions.style.display = 'none';
    }
}

// Logo functions
function toggleLogoOptions() {
    const useLogoCheckbox = document.getElementById('use_logo');
    const logoSettings = document.getElementById('logo_settings');

    if (useLogoCheckbox.checked) {
        logoSettings.style.display = 'block';
    } else {
        logoSettings.style.display = 'none';
    }
}

function previewSelectedLogo() {
    const logoSelect = document.getElementById('logo_file');
    const selectedOption = logoSelect.options[logoSelect.selectedIndex];
    const logoUrl = selectedOption.getAttribute('data-url');

    const placeholder = document.getElementById('logo_preview_placeholder');
    const previewImg = document.getElementById('logo_preview_img');

    if (logoUrl) {
        placeholder.style.display = 'none';
        previewImg.src = logoUrl;
        previewImg.style.display = 'block';
    } else {
        placeholder.style.display = 'block';
        previewImg.style.display = 'none';
    }
}

function updateLogoSizeDisplay(value) {
    document.getElementById('logo_size_display').textContent = value + 'px';

    // Cập nhật kích thước preview
    const previewImg = document.getElementById('logo_preview_img');
    if (previewImg.style.display !== 'none') {
        const scale = Math.min(1, 100 / value); // Scale down nếu quá lớn
        previewImg.style.maxWidth = (value * scale) + 'px';
        previewImg.style.maxHeight = (value * scale) + 'px';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const initialVolume = document.getElementById('volume').value;
    updateVolumeColor(initialVolume);

    // Initialize channel settings
    updateChannelSettings();

    // Initialize scheduling
    toggleScheduling();

    // Initialize logo settings visibility
    toggleLogoOptions();

    // Initialize logo preview if already selected
    previewSelectedLogo();
});

// Preview file uploads
document.getElementById('product_video').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const size = (file.size / 1024 / 1024).toFixed(2);
        console.log(`Video selected: ${file.name} (${size} MB)`);
    }
});

document.getElementById('product_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const size = (file.size / 1024 / 1024).toFixed(2);
        console.log(`Image selected: ${file.name} (${size} MB)`);
    }
});
</script>
@endpush
@endsection
