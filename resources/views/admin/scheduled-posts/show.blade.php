@extends('layouts.app')

@section('title', 'Chi Tiết Lịch Đăng - ' . $scheduledPost->title)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            @if($scheduledPost->video_type === 'tiktok')
                                <i class="fab fa-tiktok fa-3x text-dark mr-3"></i>
                            @elseif($scheduledPost->video_type === 'story')
                                <i class="fas fa-book fa-3x text-primary mr-3"></i>
                            @else
                                <i class="fas fa-video fa-3x text-info mr-3"></i>
                            @endif
                            
                            <div>
                                <h3 class="mb-0">{{ $scheduledPost->title }}</h3>
                                <p class="text-muted mb-0">
                                    {{ ucfirst($scheduledPost->video_type) }} Video
                                    • {{ $scheduledPost->channel->name }}
                                    @if($scheduledPost->channel->username)
                                        (@{{ $scheduledPost->channel->username }})
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            @if($scheduledPost->status === 'pending')
                                <span class="badge badge-warning badge-lg">
                                    <i class="fas fa-clock mr-1"></i>Chờ đăng
                                </span>
                            @elseif($scheduledPost->status === 'processing')
                                <span class="badge badge-info badge-lg">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý
                                </span>
                            @elseif($scheduledPost->status === 'uploaded')
                                <span class="badge badge-success badge-lg">
                                    <i class="fas fa-check mr-1"></i>Đã đăng
                                </span>
                            @elseif($scheduledPost->status === 'failed')
                                <span class="badge badge-danger badge-lg">
                                    <i class="fas fa-times mr-1"></i>Thất bại
                                </span>
                            @elseif($scheduledPost->status === 'cancelled')
                                <span class="badge badge-secondary badge-lg">
                                    <i class="fas fa-ban mr-1"></i>Đã hủy
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($scheduledPost->description)
                        <p class="text-muted">{{ $scheduledPost->description }}</p>
                    @endif
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            @if($scheduledPost->isPending())
                                <a href="{{ route('admin.scheduled-posts.edit', $scheduledPost) }}" class="btn btn-warning">
                                    <i class="fas fa-edit mr-2"></i>Chỉnh Sửa
                                </a>
                            @endif
                            <a href="{{ route('admin.scheduled-posts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Quay Lại
                            </a>
                        </div>
                        
                        <div>
                            @if($scheduledPost->isPending())
                                <form action="{{ route('admin.scheduled-posts.post-now', $scheduledPost) }}" 
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-play mr-2"></i>Đăng Ngay
                                    </button>
                                </form>
                                
                                <form action="{{ route('admin.scheduled-posts.cancel', $scheduledPost) }}" 
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-ban mr-2"></i>Hủy Lịch
                                    </button>
                                </form>
                            @endif
                            
                            @if($scheduledPost->canRetry())
                                <form action="{{ route('admin.scheduled-posts.retry', $scheduledPost) }}" 
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-redo mr-2"></i>Thử Lại
                                    </button>
                                </form>
                            @endif
                            
                            @if($scheduledPost->platform_url)
                                <a href="{{ $scheduledPost->platform_url }}" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt mr-2"></i>Xem Trên Platform
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Post Details -->
        <div class="col-lg-8">
            <!-- Video Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-video mr-2"></i>Thông Tin Video
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td width="150"><strong>Tiêu đề:</strong></td>
                            <td>{{ $scheduledPost->title }}</td>
                        </tr>
                        @if($scheduledPost->description)
                        <tr>
                            <td><strong>Mô tả:</strong></td>
                            <td>{{ $scheduledPost->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Loại video:</strong></td>
                            <td>
                                <span class="badge badge-info">{{ ucfirst($scheduledPost->video_type) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Đường dẫn:</strong></td>
                            <td>
                                <code>{{ $scheduledPost->video_path }}</code>
                                @if(file_exists($scheduledPost->video_path))
                                    <span class="badge badge-success ml-2">
                                        <i class="fas fa-check mr-1"></i>File tồn tại
                                    </span>
                                    <small class="text-muted ml-2">
                                        ({{ $scheduledPost->video_size_formatted }})
                                    </small>
                                @else
                                    <span class="badge badge-danger ml-2">
                                        <i class="fas fa-times mr-1"></i>File không tồn tại
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if($scheduledPost->tags)
                        <tr>
                            <td><strong>Tags:</strong></td>
                            <td>
                                @foreach($scheduledPost->tags as $tag)
                                    <span class="badge badge-secondary mr-1">{{ $tag }}</span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if($scheduledPost->category)
                        <tr>
                            <td><strong>Danh mục:</strong></td>
                            <td>{{ $scheduledPost->category }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Quyền riêng tư:</strong></td>
                            <td>
                                <span class="badge badge-{{ $scheduledPost->privacy === 'public' ? 'success' : ($scheduledPost->privacy === 'private' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($scheduledPost->privacy) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Schedule Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock mr-2"></i>Thông Tin Lịch Đăng
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td width="150"><strong>Lịch đăng:</strong></td>
                            <td>
                                <strong>{{ $scheduledPost->scheduled_at->format('d/m/Y H:i:s') }}</strong>
                                <small class="text-muted">({{ $scheduledPost->timezone }})</small>
                                <br>
                                <small class="text-muted">{{ $scheduledPost->scheduled_at->diffForHumans() }}</small>
                                
                                @if($scheduledPost->isReadyToPost())
                                    <br><span class="badge badge-warning">Sẵn sàng đăng</span>
                                @endif
                            </td>
                        </tr>
                        @if($scheduledPost->uploaded_at)
                        <tr>
                            <td><strong>Đã đăng lúc:</strong></td>
                            <td>
                                <strong class="text-success">{{ $scheduledPost->uploaded_at->format('d/m/Y H:i:s') }}</strong>
                                <br>
                                <small class="text-muted">{{ $scheduledPost->uploaded_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td>
                                @if($scheduledPost->status === 'pending')
                                    <span class="badge badge-warning">Chờ đăng</span>
                                @elseif($scheduledPost->status === 'processing')
                                    <span class="badge badge-info">Đang xử lý</span>
                                @elseif($scheduledPost->status === 'uploaded')
                                    <span class="badge badge-success">Đã đăng</span>
                                @elseif($scheduledPost->status === 'failed')
                                    <span class="badge badge-danger">Thất bại</span>
                                @elseif($scheduledPost->status === 'cancelled')
                                    <span class="badge badge-secondary">Đã hủy</span>
                                @endif
                            </td>
                        </tr>
                        @if($scheduledPost->retry_count > 0)
                        <tr>
                            <td><strong>Số lần thử lại:</strong></td>
                            <td>
                                {{ $scheduledPost->retry_count }}/3
                                @if($scheduledPost->last_retry_at)
                                    <br><small class="text-muted">
                                        Thử lại cuối: {{ $scheduledPost->last_retry_at->diffForHumans() }}
                                    </small>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($scheduledPost->error_message)
                        <tr>
                            <td><strong>Lỗi:</strong></td>
                            <td>
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ $scheduledPost->error_message }}
                                </div>
                            </td>
                        </tr>
                        @endif
                        @if($scheduledPost->platform_post_id)
                        <tr>
                            <td><strong>Post ID:</strong></td>
                            <td>
                                <code>{{ $scheduledPost->platform_post_id }}</code>
                                @if($scheduledPost->platform_url)
                                    <a href="{{ $scheduledPost->platform_url }}" target="_blank" class="btn btn-sm btn-primary ml-2">
                                        <i class="fas fa-external-link-alt mr-1"></i>Xem
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history mr-2"></i>Lịch Sử
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-primary">{{ $scheduledPost->created_at->format('d/m/Y') }}</span>
                        </div>
                        
                        <div>
                            <i class="fas fa-plus bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> {{ $scheduledPost->created_at->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Tạo lịch đăng</h3>
                                <div class="timeline-body">
                                    Video được lên lịch đăng vào {{ $scheduledPost->scheduled_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>

                        @if($scheduledPost->status === 'processing')
                        <div>
                            <i class="fas fa-spinner fa-spin bg-yellow"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> {{ now()->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Đang xử lý</h3>
                                <div class="timeline-body">
                                    Video đang được upload lên {{ ucfirst($scheduledPost->channel->platform) }}
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($scheduledPost->uploaded_at)
                        <div>
                            <i class="fas fa-check bg-green"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> {{ $scheduledPost->uploaded_at->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Đăng thành công</h3>
                                <div class="timeline-body">
                                    Video đã được đăng lên {{ ucfirst($scheduledPost->channel->platform) }}
                                    @if($scheduledPost->platform_url)
                                        <br><a href="{{ $scheduledPost->platform_url }}" target="_blank">Xem video</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($scheduledPost->status === 'failed')
                        <div>
                            <i class="fas fa-times bg-red"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> {{ $scheduledPost->updated_at->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Upload thất bại</h3>
                                <div class="timeline-body">
                                    @if($scheduledPost->error_message)
                                        {{ $scheduledPost->error_message }}
                                    @else
                                        Có lỗi xảy ra trong quá trình upload
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($scheduledPost->status === 'cancelled')
                        <div>
                            <i class="fas fa-ban bg-gray"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> {{ $scheduledPost->updated_at->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Đã hủy</h3>
                                <div class="timeline-body">
                                    Lịch đăng đã được hủy
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Channel & Metadata -->
        <div class="col-lg-4">
            <!-- Channel Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-broadcast-tower mr-2"></i>Thông Tin Kênh
                    </h5>
                </div>
                <div class="card-body text-center">
                    @if($scheduledPost->channel->platform === 'tiktok')
                        <i class="fab fa-tiktok fa-3x text-dark mb-3"></i>
                    @elseif($scheduledPost->channel->platform === 'youtube')
                        <i class="fab fa-youtube fa-3x text-danger mb-3"></i>
                    @endif
                    
                    <h6>{{ $scheduledPost->channel->name }}</h6>
                    <p class="text-muted">
                        {{ ucfirst($scheduledPost->channel->platform) }}
                        @if($scheduledPost->channel->username)
                            <br>@{{ $scheduledPost->channel->username }}
                        @endif
                    </p>
                    
                    @if($scheduledPost->channel->is_active)
                        <span class="badge badge-success">Hoạt động</span>
                    @else
                        <span class="badge badge-secondary">Tạm dừng</span>
                    @endif
                    
                    @if($scheduledPost->channel->auto_upload)
                        <span class="badge badge-info ml-1">Auto Upload</span>
                    @endif
                    
                    <hr>
                    <a href="{{ route('admin.channels.show', $scheduledPost->channel) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye mr-1"></i>Xem Kênh
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>Thông Tin Khác
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><code>{{ $scheduledPost->id }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Tạo lúc:</strong></td>
                            <td>{{ $scheduledPost->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Cập nhật:</strong></td>
                            <td>{{ $scheduledPost->updated_at->diffForHumans() }}</td>
                        </tr>
                        @if($scheduledPost->metadata)
                        <tr>
                            <td><strong>Metadata:</strong></td>
                            <td>
                                <pre class="small">{{ json_encode($scheduledPost->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs mr-2"></i>Thao Tác
                    </h5>
                </div>
                <div class="card-body">
                    @if($scheduledPost->isPending())
                        <a href="{{ route('admin.scheduled-posts.edit', $scheduledPost) }}" 
                           class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-edit mr-2"></i>Chỉnh Sửa
                        </a>
                        
                        <form action="{{ route('admin.scheduled-posts.post-now', $scheduledPost) }}" 
                              method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-play mr-2"></i>Đăng Ngay
                            </button>
                        </form>
                        
                        <form action="{{ route('admin.scheduled-posts.cancel', $scheduledPost) }}" 
                              method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-block">
                                <i class="fas fa-ban mr-2"></i>Hủy Lịch
                            </button>
                        </form>
                    @endif
                    
                    @if($scheduledPost->canRetry())
                        <form action="{{ route('admin.scheduled-posts.retry', $scheduledPost) }}" 
                              method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fas fa-redo mr-2"></i>Thử Lại
                            </button>
                        </form>
                    @endif
                    
                    @if(!$scheduledPost->isProcessing())
                        <form action="{{ route('admin.scheduled-posts.destroy', $scheduledPost) }}" 
                              method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa lịch đăng này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-trash mr-2"></i>Xóa
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto refresh for processing posts
@if($scheduledPost->status === 'processing')
setTimeout(function() {
    location.reload();
}, 15000); // Refresh every 15 seconds
@endif
</script>
@endpush
@endsection
