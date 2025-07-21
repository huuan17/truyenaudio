@extends('layouts.app')

@section('title', 'Chi Tiết Kênh - ' . $channel->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            @if($channel->platform === 'tiktok')
                                <i class="fab fa-tiktok fa-3x text-dark mr-3"></i>
                            @elseif($channel->platform === 'youtube')
                                <i class="fab fa-youtube fa-3x text-danger mr-3"></i>
                            @endif
                            
                            <div>
                                <h3 class="mb-0">{{ $channel->name }}</h3>
                                <p class="text-muted mb-0">
                                    {{ ucfirst($channel->platform) }}
                                    @if($channel->username)
                                        • @{{ $channel->username }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            @if($channel->is_active)
                                <span class="badge badge-success badge-lg mr-2">Hoạt động</span>
                            @else
                                <span class="badge badge-secondary badge-lg mr-2">Tạm dừng</span>
                            @endif
                            
                            @if($channel->auto_upload)
                                <span class="badge badge-info badge-lg mr-2">Auto Upload</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($channel->description)
                        <p class="text-muted">{{ $channel->description }}</p>
                    @endif
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-warning">
                                <i class="fas fa-edit mr-2"></i>Chỉnh Sửa
                            </a>
                            <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Quay Lại
                            </a>
                        </div>
                        
                        <div>
                            @if($channel->hasValidCredentials())
                                <button class="btn btn-info" onclick="testConnection({{ $channel->id }})">
                                    <i class="fas fa-plug mr-2"></i>Test API
                                </button>
                            @endif
                            
                            <form action="{{ route('admin.channels.toggle-status', $channel) }}" 
                                  method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="btn {{ $channel->is_active ? 'btn-secondary' : 'btn-success' }}">
                                    <i class="fas {{ $channel->is_active ? 'fa-pause' : 'fa-play' }} mr-2"></i>
                                    {{ $channel->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Channel Info -->
        <div class="col-lg-4">
            <!-- Basic Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>Thông Tin Kênh
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Platform:</strong></td>
                            <td>
                                @if($channel->platform === 'tiktok')
                                    <i class="fab fa-tiktok mr-1"></i>TikTok
                                @elseif($channel->platform === 'youtube')
                                    <i class="fab fa-youtube mr-1"></i>YouTube
                                @endif
                            </td>
                        </tr>
                        @if($channel->channel_id)
                        <tr>
                            <td><strong>Channel ID:</strong></td>
                            <td><code>{{ $channel->channel_id }}</code></td>
                        </tr>
                        @endif
                        @if($channel->username)
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td>@{{ $channel->username }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td>
                                @if($channel->is_active)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-secondary">Tạm dừng</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Auto Upload:</strong></td>
                            <td>
                                @if($channel->auto_upload)
                                    <span class="badge badge-info">Bật</span>
                                @else
                                    <span class="badge badge-secondary">Tắt</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tạo lúc:</strong></td>
                            <td>{{ $channel->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($channel->last_upload_at)
                        <tr>
                            <td><strong>Upload cuối:</strong></td>
                            <td>{{ $channel->last_upload_at->diffForHumans() }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- API Connection Status -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plug mr-2"></i>Kết Nối API
                    </h5>
                </div>
                <div class="card-body">
                    @if($channel->platform === 'tiktok')
                        @if($channel->hasValidCredentials())
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-success">
                                    <i class="fas fa-check-circle mr-2"></i>Đã kết nối TikTok
                                </span>
                                <div>
                                    <button class="btn btn-sm btn-info" onclick="testTikTokConnection({{ $channel->id }})">
                                        <i class="fas fa-sync mr-1"></i>Test
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="refreshTikTokToken({{ $channel->id }})">
                                        <i class="fas fa-refresh mr-1"></i>Refresh
                                    </button>
                                </div>
                            </div>

                            @if(isset($channel->api_credentials['user_info']))
                                @php $userInfo = $channel->api_credentials['user_info']; @endphp
                                <div class="text-center mb-3">
                                    @if(isset($userInfo['avatar_url']))
                                        <img src="{{ $userInfo['avatar_url'] }}" alt="Avatar"
                                             class="rounded-circle mb-2" style="width: 50px; height: 50px;">
                                    @endif
                                    <div>
                                        <strong>{{ $userInfo['display_name'] ?? 'N/A' }}</strong>
                                        @if(isset($userInfo['username']))
                                            <br><small class="text-muted">@{{ $userInfo['username'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="text-center">
                                <form action="{{ route('admin.channels.tiktok.disconnect', $channel) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc muốn ngắt kết nối TikTok?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-unlink mr-1"></i>Ngắt kết nối
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="text-center">
                                <p class="text-muted mb-3">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Chưa kết nối với TikTok
                                </p>
                                <a href="{{ route('admin.channels.tiktok.authorize', ['channel_id' => $channel->id]) }}"
                                   class="btn btn-primary">
                                    <i class="fab fa-tiktok mr-2"></i>Kết nối TikTok
                                </a>
                                <p class="text-muted mt-2 small">
                                    Bạn cần kết nối với TikTok để có thể upload video tự động
                                </p>
                            </div>
                        @endif
                    @elseif($channel->platform === 'youtube')
                        <div class="text-center">
                            <p class="text-muted">
                                <i class="fas fa-info-circle mr-2"></i>
                                YouTube API sẽ được triển khai trong phiên bản tiếp theo
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Logo Config -->
            @if($channel->logo_config)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-image mr-2"></i>Cấu Hình Logo
                    </h5>
                </div>
                <div class="card-body text-center">
                    @if(isset($channel->logo_config['logo_file']))
                        <img src="{{ route('admin.logo.serve', $channel->logo_config['logo_file']) }}" 
                             alt="Logo" style="max-height: 80px; object-fit: contain;" class="mb-3">
                        
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Vị trí:</strong></td>
                                <td>{{ $channel->logo_config['position'] ?? 'bottom-right' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Kích thước:</strong></td>
                                <td>{{ $channel->logo_config['size'] ?? 100 }}px</td>
                            </tr>
                            @if(isset($channel->logo_config['opacity']))
                            <tr>
                                <td><strong>Độ trong suốt:</strong></td>
                                <td>{{ $channel->logo_config['opacity'] }}</td>
                            </tr>
                            @endif
                        </table>
                    @else
                        <p class="text-muted">Chưa cấu hình logo</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Default Settings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog mr-2"></i>Cài Đặt Mặc Định
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Quyền riêng tư:</strong></td>
                            <td>
                                <span class="badge badge-{{ $channel->default_privacy === 'public' ? 'success' : ($channel->default_privacy === 'private' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($channel->default_privacy) }}
                                </span>
                            </td>
                        </tr>
                        @if($channel->default_category)
                        <tr>
                            <td><strong>Danh mục:</strong></td>
                            <td>{{ $channel->default_category }}</td>
                        </tr>
                        @endif
                        @if($channel->default_tags)
                        <tr>
                            <td><strong>Tags:</strong></td>
                            <td>
                                @foreach($channel->default_tags as $tag)
                                    <span class="badge badge-secondary mr-1">{{ $tag }}</span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- API Status -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-key mr-2"></i>Trạng Thái API
                    </h5>
                </div>
                <div class="card-body text-center">
                    @if($channel->hasValidCredentials())
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6 class="text-success">API Đã Cấu Hình</h6>
                        <p class="text-muted">Credentials đã được lưu và mã hóa</p>
                        <button class="btn btn-info btn-sm" onclick="testConnection({{ $channel->id }})">
                            <i class="fas fa-plug mr-1"></i>Test Kết Nối
                        </button>
                    @else
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h6 class="text-warning">Chưa Cấu Hình API</h6>
                        <p class="text-muted">Cần cấu hình API để upload video</p>
                        <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-cog mr-1"></i>Cấu Hình
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistics & Posts -->
        <div class="col-lg-8">
            <!-- Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar mr-2"></i>Thống Kê
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 col-6">
                            <div class="border-right">
                                <h3 class="text-primary">{{ $stats['total_posts'] }}</h3>
                                <p class="text-muted mb-0">Tổng video</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border-right">
                                <h3 class="text-warning">{{ $stats['pending_posts'] }}</h3>
                                <p class="text-muted mb-0">Chờ đăng</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border-right">
                                <h3 class="text-success">{{ $stats['uploaded_posts'] }}</h3>
                                <p class="text-muted mb-0">Đã đăng</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border-right">
                                <h3 class="text-danger">{{ $stats['failed_posts'] }}</h3>
                                <p class="text-muted mb-0">Thất bại</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border-right">
                                <h3 class="text-info">{{ $stats['uploads_this_week'] }}</h3>
                                <p class="text-muted mb-0">Tuần này</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <h3 class="text-secondary">{{ $stats['uploads_this_month'] }}</h3>
                            <p class="text-muted mb-0">Tháng này</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt mr-2"></i>Video Gần Đây
                        </h5>
                        <a href="{{ route('admin.scheduled-posts.index', ['channel_id' => $channel->id]) }}" 
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-eye mr-1"></i>Xem Tất Cả
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($channel->scheduledPosts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tiêu đề</th>
                                        <th>Loại</th>
                                        <th>Trạng thái</th>
                                        <th>Lịch đăng</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($channel->scheduledPosts->take(10) as $post)
                                    <tr>
                                        <td>
                                            <strong>{{ Str::limit($post->title, 30) }}</strong>
                                            @if($post->description)
                                                <br><small class="text-muted">{{ Str::limit($post->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $post->video_type }}</span>
                                        </td>
                                        <td>
                                            @if($post->status === 'pending')
                                                <span class="badge badge-warning">Chờ đăng</span>
                                            @elseif($post->status === 'processing')
                                                <span class="badge badge-info">Đang xử lý</span>
                                            @elseif($post->status === 'uploaded')
                                                <span class="badge badge-success">Đã đăng</span>
                                            @elseif($post->status === 'failed')
                                                <span class="badge badge-danger">Thất bại</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $post->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $post->scheduled_at->format('d/m/Y H:i') }}</small>
                                            @if($post->uploaded_at)
                                                <br><small class="text-success">Đăng: {{ $post->uploaded_at->format('d/m/Y H:i') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.scheduled-posts.show', $post) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Chưa có video nào</h6>
                            <p class="text-muted">Tạo video và lên lịch đăng để bắt đầu</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
</script>
@endpush
@endsection
