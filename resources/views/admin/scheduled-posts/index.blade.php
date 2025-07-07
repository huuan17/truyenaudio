@extends('layouts.app')

@section('title', 'Lịch Đăng Video')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt mr-2"></i>Lịch Đăng Video
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.scheduled-posts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Lên Lịch Mới
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Quản lý lịch đăng video tự động lên các kênh TikTok và YouTube.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter mr-2"></i>Bộ Lọc
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.scheduled-posts.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Trạng Thái</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">Tất cả</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="channel_id">Kênh</label>
                                    <select name="channel_id" id="channel_id" class="form-control">
                                        <option value="">Tất cả kênh</option>
                                        @foreach($channels as $channel)
                                            <option value="{{ $channel->id }}" {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                                {{ $channel->name }} ({{ ucfirst($channel->platform) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_from">Từ Ngày</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" 
                                           value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_to">Đến Ngày</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" 
                                           value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-info btn-block">
                                            <i class="fas fa-search mr-1"></i>Lọc
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Posts List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list mr-2"></i>Danh Sách Video ({{ $posts->total() }})
                        </h5>
                        
                        @if($posts->count() > 0)
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-cogs mr-1"></i>Thao Tác Hàng Loạt
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" onclick="bulkAction('cancel')">
                                    <i class="fas fa-ban mr-2"></i>Hủy Lịch
                                </a>
                                <a class="dropdown-item" href="#" onclick="bulkAction('retry')">
                                    <i class="fas fa-redo mr-2"></i>Thử Lại
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" onclick="bulkAction('delete')">
                                    <i class="fas fa-trash mr-2"></i>Xóa
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($posts->count() > 0)
                        <form id="bulk-action-form">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="select-all">
                                                    <label class="custom-control-label" for="select-all"></label>
                                                </div>
                                            </th>
                                            <th>Video</th>
                                            <th>Kênh</th>
                                            <th>Trạng Thái</th>
                                            <th>Lịch Đăng</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($posts as $post)
                                        <tr>
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input post-checkbox" 
                                                           id="post-{{ $post->id }}" value="{{ $post->id }}">
                                                    <label class="custom-control-label" for="post-{{ $post->id }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="mr-3">
                                                        @if($post->video_type === 'tiktok')
                                                            <i class="fab fa-tiktok fa-2x text-dark"></i>
                                                        @elseif($post->video_type === 'story')
                                                            <i class="fas fa-book fa-2x text-primary"></i>
                                                        @else
                                                            <i class="fas fa-video fa-2x text-info"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ Str::limit($post->title, 40) }}</h6>
                                                        @if($post->description)
                                                            <small class="text-muted">{{ Str::limit($post->description, 60) }}</small>
                                                        @endif
                                                        <br>
                                                        <span class="badge badge-info">{{ $post->video_type }}</span>
                                                        @if($post->tags)
                                                            @foreach(array_slice($post->tags, 0, 2) as $tag)
                                                                <span class="badge badge-secondary">{{ $tag }}</span>
                                                            @endforeach
                                                            @if(count($post->tags) > 2)
                                                                <span class="badge badge-light">+{{ count($post->tags) - 2 }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($post->channel->platform === 'tiktok')
                                                        <i class="fab fa-tiktok mr-2"></i>
                                                    @elseif($post->channel->platform === 'youtube')
                                                        <i class="fab fa-youtube mr-2 text-danger"></i>
                                                    @endif
                                                    <div>
                                                        <strong>{{ $post->channel->name }}</strong>
                                                        @if($post->channel->username)
                                                            <br><small class="text-muted">@{{ $post->channel->username }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($post->status === 'pending')
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-clock mr-1"></i>Chờ đăng
                                                    </span>
                                                @elseif($post->status === 'processing')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý
                                                    </span>
                                                @elseif($post->status === 'uploaded')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check mr-1"></i>Đã đăng
                                                    </span>
                                                @elseif($post->status === 'failed')
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times mr-1"></i>Thất bại
                                                    </span>
                                                @elseif($post->status === 'cancelled')
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-ban mr-1"></i>Đã hủy
                                                    </span>
                                                @endif
                                                
                                                @if($post->retry_count > 0)
                                                    <br><small class="text-muted">Thử lại: {{ $post->retry_count }}/3</small>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $post->scheduled_at->format('d/m/Y H:i') }}</strong>
                                                <br><small class="text-muted">{{ $post->scheduled_at->diffForHumans() }}</small>
                                                
                                                @if($post->uploaded_at)
                                                    <br><small class="text-success">
                                                        <i class="fas fa-check mr-1"></i>{{ $post->uploaded_at->format('d/m/Y H:i') }}
                                                    </small>
                                                @endif
                                                
                                                @if($post->isReadyToPost())
                                                    <br><span class="badge badge-warning">Sẵn sàng đăng</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <a href="{{ route('admin.scheduled-posts.show', $post) }}" 
                                                       class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    @if($post->isPending())
                                                        <a href="{{ route('admin.scheduled-posts.edit', $post) }}" 
                                                           class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <form action="{{ route('admin.scheduled-posts.post-now', $post) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-success btn-sm" 
                                                                    title="Đăng ngay">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <form action="{{ route('admin.scheduled-posts.cancel', $post) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-secondary btn-sm" 
                                                                    title="Hủy lịch">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    @if($post->canRetry())
                                                        <form action="{{ route('admin.scheduled-posts.retry', $post) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-info btn-sm" 
                                                                    title="Thử lại">
                                                                <i class="fas fa-redo"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    @if($post->platform_url)
                                                        <a href="{{ $post->platform_url }}" target="_blank" 
                                                           class="btn btn-primary btn-sm" title="Xem trên platform">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Hiển thị {{ $posts->firstItem() }} - {{ $posts->lastItem() }} 
                                trong tổng số {{ $posts->total() }} video
                            </div>
                            <div>
                                {{ $posts->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">Chưa có lịch đăng nào</h4>
                            <p class="text-muted mb-4">
                                Tạo video và lên lịch đăng để bắt đầu quản lý nội dung tự động
                            </p>
                            <a href="{{ route('admin.scheduled-posts.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>Lên Lịch Đầu Tiên
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Bulk actions
function bulkAction(action) {
    const selectedPosts = [];
    document.querySelectorAll('.post-checkbox:checked').forEach(checkbox => {
        selectedPosts.push(checkbox.value);
    });
    
    if (selectedPosts.length === 0) {
        alert('Vui lòng chọn ít nhất một video');
        return;
    }
    
    let confirmMessage = '';
    switch(action) {
        case 'cancel':
            confirmMessage = `Bạn có chắc muốn hủy lịch ${selectedPosts.length} video?`;
            break;
        case 'retry':
            confirmMessage = `Bạn có chắc muốn thử lại ${selectedPosts.length} video?`;
            break;
        case 'delete':
            confirmMessage = `Bạn có chắc muốn xóa ${selectedPosts.length} video?\n\nHành động này không thể hoàn tác!`;
            break;
    }
    
    if (confirm(confirmMessage)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.scheduled-posts.bulk-action") }}';
        
        // CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);
        
        // Post IDs
        selectedPosts.forEach(postId => {
            const postInput = document.createElement('input');
            postInput.type = 'hidden';
            postInput.name = 'post_ids[]';
            postInput.value = postId;
            form.appendChild(postInput);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto refresh for processing posts
@if($posts->where('status', 'processing')->count() > 0)
setTimeout(function() {
    location.reload();
}, 30000); // Refresh every 30 seconds
@endif
</script>
@endpush
@endsection
