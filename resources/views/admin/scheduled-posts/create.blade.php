@extends('layouts.app')

@section('title', 'Lên Lịch Đăng Video')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-plus mr-2"></i>Lên Lịch Đăng Video
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.scheduled-posts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.scheduled-posts.store') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Video Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-video mr-2"></i>Thông Tin Video
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Video Path -->
                        <div class="form-group">
                            <label for="video_path">
                                <i class="fas fa-file-video mr-1"></i>Đường Dẫn Video *
                            </label>
                            <input type="text" name="video_path" id="video_path" class="form-control" 
                                   placeholder="/path/to/video.mp4" value="{{ old('video_path', $videoPath) }}" required>
                            <small class="form-text text-muted">
                                Đường dẫn đầy đủ đến file video cần đăng
                            </small>
                            @error('video_path')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Video Type -->
                        <div class="form-group">
                            <label for="video_type">
                                <i class="fas fa-tag mr-1"></i>Loại Video *
                            </label>
                            <select name="video_type" id="video_type" class="form-control" required>
                                <option value="tiktok" {{ old('video_type', $videoType) == 'tiktok' ? 'selected' : '' }}>
                                    TikTok Video
                                </option>
                                <option value="story" {{ old('video_type', $videoType) == 'story' ? 'selected' : '' }}>
                                    Story Video
                                </option>
                                <option value="custom" {{ old('video_type', $videoType) == 'custom' ? 'selected' : '' }}>
                                    Custom Video
                                </option>
                            </select>
                            @error('video_type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Title -->
                        <div class="form-group">
                            <label for="title">
                                <i class="fas fa-heading mr-1"></i>Tiêu Đề *
                            </label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   placeholder="Tiêu đề video..." value="{{ old('title') }}" required>
                            @error('title')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-align-left mr-1"></i>Mô Tả
                            </label>
                            <textarea name="description" id="description" class="form-control" rows="4" 
                                      placeholder="Mô tả video...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tags -->
                        <div class="form-group">
                            <label for="tags">
                                <i class="fas fa-tags mr-1"></i>Tags
                            </label>
                            <input type="text" name="tags" id="tags" class="form-control" 
                                   placeholder="tag1, tag2, tag3..." value="{{ old('tags') }}">
                            <small class="form-text text-muted">
                                Phân cách bằng dấu phẩy. Sẽ sử dụng tags mặc định của kênh nếu để trống.
                            </small>
                            @error('tags')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Category -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category">
                                        <i class="fas fa-folder mr-1"></i>Danh Mục
                                    </label>
                                    <input type="text" name="category" id="category" class="form-control" 
                                           placeholder="Entertainment, Education..." value="{{ old('category') }}">
                                    @error('category')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Privacy -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="privacy">
                                        <i class="fas fa-eye mr-1"></i>Quyền Riêng Tư *
                                    </label>
                                    <select name="privacy" id="privacy" class="form-control" required>
                                        <option value="private" {{ old('privacy', 'private') == 'private' ? 'selected' : '' }}>
                                            Riêng tư
                                        </option>
                                        <option value="public" {{ old('privacy') == 'public' ? 'selected' : '' }}>
                                            Công khai
                                        </option>
                                        <option value="unlisted" {{ old('privacy') == 'unlisted' ? 'selected' : '' }}>
                                            Không liệt kê
                                        </option>
                                    </select>
                                    @error('privacy')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Scheduling -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-clock mr-2"></i>Lên Lịch Đăng
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <!-- Date -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="scheduled_date">
                                        <i class="fas fa-calendar mr-1"></i>Ngày Đăng *
                                    </label>
                                    <input type="date" name="scheduled_date" id="scheduled_date" class="form-control" 
                                           value="{{ old('scheduled_date', now()->format('Y-m-d')) }}" required>
                                    @error('scheduled_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Time -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="scheduled_time">
                                        <i class="fas fa-clock mr-1"></i>Giờ Đăng *
                                    </label>
                                    <input type="time" name="scheduled_time" id="scheduled_time" class="form-control" 
                                           value="{{ old('scheduled_time', now()->addHour()->format('H:i')) }}" required>
                                    @error('scheduled_time')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Timezone -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="timezone">
                                        <i class="fas fa-globe mr-1"></i>Múi Giờ
                                    </label>
                                    <select name="timezone" id="timezone" class="form-control" required>
                                        <option value="Asia/Ho_Chi_Minh" {{ old('timezone', 'Asia/Ho_Chi_Minh') == 'Asia/Ho_Chi_Minh' ? 'selected' : '' }}>
                                            Việt Nam (UTC+7)
                                        </option>
                                        <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>
                                            UTC (UTC+0)
                                        </option>
                                        <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>
                                            New York (UTC-5)
                                        </option>
                                        <option value="Europe/London" {{ old('timezone') == 'Europe/London' ? 'selected' : '' }}>
                                            London (UTC+0)
                                        </option>
                                    </select>
                                    @error('timezone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Preview scheduled time -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Thời gian đăng:</strong> 
                            <span id="scheduled_preview">Chọn ngày và giờ để xem preview</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Channel Selection & Settings -->
            <div class="col-lg-4">
                <!-- Channel Selection -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-broadcast-tower mr-2"></i>Chọn Kênh *
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        @if($channels->count() > 0)
                            <div class="form-group">
                                <label for="channel_id">Kênh Đăng</label>
                                <select name="channel_id" id="channel_id" class="form-control" required onchange="updateChannelInfo()">
                                    <option value="">-- Chọn kênh --</option>
                                    @foreach($channels as $channel)
                                        <option value="{{ $channel->id }}" 
                                                data-platform="{{ $channel->platform }}"
                                                data-username="{{ $channel->username }}"
                                                data-privacy="{{ $channel->default_privacy }}"
                                                data-category="{{ $channel->default_category }}"
                                                data-tags="{{ $channel->default_tags ? implode(', ', $channel->default_tags) : '' }}"
                                                {{ old('channel_id', $channelId) == $channel->id ? 'selected' : '' }}>
                                            {{ $channel->name }} 
                                            ({{ ucfirst($channel->platform) }})
                                            @if($channel->username)
                                                - @{{ $channel->username }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('channel_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Channel Info -->
                            <div id="channel_info" style="display: none;">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle mr-2"></i>Thông Tin Kênh</h6>
                                    <div id="channel_details"></div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Chưa có kênh nào!</strong><br>
                                Bạn cần tạo kênh trước khi lên lịch đăng video.
                                <br><br>
                                <a href="{{ route('admin.channels.create') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-plus mr-1"></i>Tạo Kênh
                                </a>
                            </div>
                        @endif

                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt mr-2"></i>Thao Tác Nhanh
                        </h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-info btn-block mb-2" onclick="fillFromChannel()">
                            <i class="fas fa-magic mr-2"></i>Điền Từ Cài Đặt Kênh
                        </button>
                        <button type="button" class="btn btn-secondary btn-block mb-2" onclick="setScheduleNow()">
                            <i class="fas fa-play mr-2"></i>Đăng Ngay
                        </button>
                        <button type="button" class="btn btn-warning btn-block" onclick="setScheduleTomorrow()">
                            <i class="fas fa-calendar-plus mr-2"></i>Đăng Ngày Mai
                        </button>
                    </div>
                </div>

                <!-- Help -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-question-circle mr-2"></i>Hướng Dẫn
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Lưu ý:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i>Video sẽ được đăng tự động vào thời gian đã hẹn</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Có thể chỉnh sửa lịch trước khi đăng</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Kênh phải có API credentials hợp lệ</li>
                            <li><i class="fas fa-check text-success mr-2"></i>File video phải tồn tại và có thể truy cập</li>
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
                        <button type="submit" class="btn btn-primary" {{ $channels->count() == 0 ? 'disabled' : '' }}>
                            <i class="fas fa-calendar-plus mr-2"></i>Lên Lịch Đăng
                        </button>
                        <a href="{{ route('admin.scheduled-posts.index') }}" class="btn btn-secondary ml-2">
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
// Update channel info when channel is selected
function updateChannelInfo() {
    const channelSelect = document.getElementById('channel_id');
    const selectedOption = channelSelect.options[channelSelect.selectedIndex];
    const channelInfo = document.getElementById('channel_info');
    const channelDetails = document.getElementById('channel_details');
    
    if (selectedOption.value) {
        const platform = selectedOption.getAttribute('data-platform');
        const username = selectedOption.getAttribute('data-username');
        const privacy = selectedOption.getAttribute('data-privacy');
        const category = selectedOption.getAttribute('data-category');
        const tags = selectedOption.getAttribute('data-tags');
        
        let details = `<strong>Platform:</strong> ${platform.toUpperCase()}<br>`;
        if (username) details += `<strong>Username:</strong> @${username}<br>`;
        details += `<strong>Privacy mặc định:</strong> ${privacy}<br>`;
        if (category) details += `<strong>Category mặc định:</strong> ${category}<br>`;
        if (tags) details += `<strong>Tags mặc định:</strong> ${tags}`;
        
        channelDetails.innerHTML = details;
        channelInfo.style.display = 'block';
    } else {
        channelInfo.style.display = 'none';
    }
}

// Fill form from channel defaults
function fillFromChannel() {
    const channelSelect = document.getElementById('channel_id');
    const selectedOption = channelSelect.options[channelSelect.selectedIndex];
    
    if (selectedOption.value) {
        const privacy = selectedOption.getAttribute('data-privacy');
        const category = selectedOption.getAttribute('data-category');
        const tags = selectedOption.getAttribute('data-tags');
        
        if (privacy) document.getElementById('privacy').value = privacy;
        if (category) document.getElementById('category').value = category;
        if (tags) document.getElementById('tags').value = tags;
        
        alert('Đã điền thông tin từ cài đặt kênh!');
    } else {
        alert('Vui lòng chọn kênh trước!');
    }
}

// Set schedule to now
function setScheduleNow() {
    const now = new Date();
    document.getElementById('scheduled_date').value = now.toISOString().split('T')[0];
    document.getElementById('scheduled_time').value = now.toTimeString().slice(0, 5);
    updateSchedulePreview();
}

// Set schedule to tomorrow
function setScheduleTomorrow() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0); // 9 AM
    
    document.getElementById('scheduled_date').value = tomorrow.toISOString().split('T')[0];
    document.getElementById('scheduled_time').value = '09:00';
    updateSchedulePreview();
}

// Update schedule preview
function updateSchedulePreview() {
    const date = document.getElementById('scheduled_date').value;
    const time = document.getElementById('scheduled_time').value;
    const timezone = document.getElementById('timezone').value;
    
    if (date && time) {
        const scheduledDate = new Date(date + 'T' + time);
        const preview = `${scheduledDate.toLocaleDateString('vi-VN')} ${scheduledDate.toLocaleTimeString('vi-VN')} (${timezone})`;
        document.getElementById('scheduled_preview').textContent = preview;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateChannelInfo();
    updateSchedulePreview();
    
    // Add event listeners
    document.getElementById('scheduled_date').addEventListener('change', updateSchedulePreview);
    document.getElementById('scheduled_time').addEventListener('change', updateSchedulePreview);
    document.getElementById('timezone').addEventListener('change', updateSchedulePreview);
});
</script>
@endpush
@endsection
