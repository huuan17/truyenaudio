@extends('layouts.app')

@section('title', 'Quản lý đăng video')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Quản lý đăng video</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Quản lý Video</li>
                    <li class="breadcrumb-item active">Đăng video</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-broadcast-tower mr-2"></i>Quản lý đăng video
        </h1>
        <a href="{{ route('admin.video-publishing.scheduled') }}" class="btn btn-info">
            <i class="fas fa-calendar-alt mr-1"></i>Lịch đăng hôm nay
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng cộng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Nháp</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['draft'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-save fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Đã lên lịch</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['scheduled'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đã đăng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['published'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Thất bại</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['failed'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Quá hạn</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['overdue'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.video-publishing.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="platform">Nền tảng</label>
                            <select name="platform" id="platform" class="form-control">
                                <option value="">Tất cả nền tảng</option>
                                @foreach($platforms as $platform)
                                    <option value="{{ $platform }}" {{ request('platform') == $platform ? 'selected' : '' }}>
                                        {{ ucfirst($platform) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Tất cả trạng thái</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Đã lên lịch</option>
                                <option value="publishing" {{ request('status') == 'publishing' ? 'selected' : '' }}>Đang đăng</option>
                                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Đã đăng</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i>Lọc
                                </button>
                                <a href="{{ route('admin.video-publishing.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i>Xóa bộ lọc
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Publishing List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách video</h6>
        </div>
        <div class="card-body">
            @if($publishings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Video</th>
                                <th>Nền tảng</th>
                                <th>Kênh</th>
                                <th>Trạng thái</th>
                                <th>Lịch đăng</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($publishings as $publishing)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                @if($publishing->generatedVideo && file_exists(storage_path('app/' . $publishing->generatedVideo->file_path)))
                                                    <video width="60" height="40" class="rounded">
                                                        <source src="{{ asset('storage/' . $publishing->generatedVideo->file_path) }}" type="video/mp4">
                                                    </video>
                                                @else
                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;">
                                                        <i class="fas fa-video text-white"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $publishing->post_title ?: 'Chưa có tiêu đề' }}</div>
                                                <small class="text-muted">{{ $publishing->generatedVideo->title ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="{{ $publishing->platform_icon }} mr-1"></i>
                                        {{ ucfirst($publishing->platform) }}
                                    </td>
                                    <td>
                                        {{ $publishing->channel->name ?? 'Chưa chọn kênh' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $publishing->status_badge }}">
                                            {{ $publishing->status_text }}
                                        </span>
                                        @if($publishing->isOverdue())
                                            <br><small class="text-danger">Quá hạn</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($publishing->scheduled_at)
                                            {{ $publishing->scheduled_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">Chưa lên lịch</span>
                                        @endif
                                    </td>
                                    <td>{{ $publishing->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.video-publishing.show', $publishing) }}" 
                                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($publishing->canEdit())
                                                <a href="{{ route('admin.video-publishing.edit', $publishing) }}" 
                                                   class="btn btn-sm btn-warning" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            
                                            @if($publishing->status === 'draft')
                                                <form method="POST" action="{{ route('admin.video-publishing.publish', $publishing) }}" 
                                                      style="display: inline;" onsubmit="return confirm('Đăng video ngay?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Đăng ngay">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            @if($publishing->canRetry())
                                                <form method="POST" action="{{ route('admin.video-publishing.retry', $publishing) }}" 
                                                      style="display: inline;" onsubmit="return confirm('Thử lại đăng video?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Thử lại">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($publishing->canCancel())
                                                <form method="POST" action="{{ route('admin.video-publishing.cancel', $publishing) }}"
                                                      style="display: inline;" onsubmit="return confirm('Hủy đăng video?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Hủy">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('admin.video-publishing.sync-status', $publishing) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Đồng bộ trạng thái">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $publishings->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-video fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">Chưa có video nào được tạo để đăng.</p>
                    <a href="{{ route('admin.video-generator.index') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Tạo video mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
