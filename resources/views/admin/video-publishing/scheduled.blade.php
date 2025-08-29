@extends('layouts.app')

@section('title', 'Lịch đăng video hôm nay')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Lịch đăng video hôm nay</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Quản lý Video</li>
                    <li class="breadcrumb-item active">Lịch đăng hôm nay</li>
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
            <i class="fas fa-calendar-alt mr-2"></i>Lịch đăng video hôm nay
        </h1>
        <a href="{{ route('admin.video-publishing.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
    </div>

    <!-- Overdue Videos -->
    @if($overdue->count() > 0)
        <div class="card shadow mb-4 border-left-danger">
            <div class="card-header bg-danger text-white py-3">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Video quá hạn đăng ({{ $overdue->count() }})
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Video</th>
                                <th>Nền tảng</th>
                                <th>Kênh</th>
                                <th>Thời gian dự kiến</th>
                                <th>Quá hạn</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdue as $publishing)
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
                                    <td>{{ $publishing->channel->name ?? 'Chưa chọn kênh' }}</td>
                                    <td>{{ $publishing->scheduled_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="text-danger font-weight-bold">
                                            {{ $publishing->scheduled_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <form method="POST" action="{{ route('admin.video-publishing.publish', $publishing) }}" 
                                                  style="display: inline;" onsubmit="return confirm('Đăng video ngay?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Đăng ngay">
                                                    <i class="fas fa-upload mr-1"></i>Đăng ngay
                                                </button>
                                            </form>
                                            
                                            <a href="{{ route('admin.video-publishing.edit', $publishing) }}" 
                                               class="btn btn-sm btn-warning" title="Chỉnh sửa lịch">
                                                <i class="fas fa-edit mr-1"></i>Sửa lịch
                                            </a>
                                            
                                            <form method="POST" action="{{ route('admin.video-publishing.cancel', $publishing) }}" 
                                                  style="display: inline;" onsubmit="return confirm('Hủy đăng video?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hủy">
                                                    <i class="fas fa-times mr-1"></i>Hủy
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Today's Schedule -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-clock mr-2"></i>Lịch đăng hôm nay ({{ $scheduledToday->count() }})
            </h6>
        </div>
        <div class="card-body">
            @if($scheduledToday->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Video</th>
                                <th>Nền tảng</th>
                                <th>Kênh</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scheduledToday as $publishing)
                                <tr class="{{ $publishing->scheduled_at->isPast() ? 'table-warning' : '' }}">
                                    <td>
                                        <div class="font-weight-bold">{{ $publishing->scheduled_at->format('H:i') }}</div>
                                        <small class="text-muted">{{ $publishing->scheduled_at->format('d/m/Y') }}</small>
                                        @if($publishing->scheduled_at->isPast())
                                            <br><small class="text-warning">Đã qua giờ</small>
                                        @elseif($publishing->scheduled_at->diffInMinutes() <= 30)
                                            <br><small class="text-info">Sắp tới</small>
                                        @endif
                                    </td>
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
                                    <td>{{ $publishing->channel->name ?? 'Chưa chọn kênh' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $publishing->status_badge }}">
                                            {{ $publishing->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.video-publishing.show', $publishing) }}" 
                                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($publishing->status === 'scheduled')
                                                <form method="POST" action="{{ route('admin.video-publishing.publish', $publishing) }}" 
                                                      style="display: inline;" onsubmit="return confirm('Đăng video ngay?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Đăng ngay">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <a href="{{ route('admin.video-publishing.edit', $publishing) }}" 
                                               class="btn btn-sm btn-warning" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-calendar-check fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">Không có video nào được lên lịch đăng hôm nay.</p>
                    <a href="{{ route('admin.video-generator.index') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Tạo video mới
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.video-publishing.index', ['status' => 'draft']) }}" 
                           class="list-group-item list-group-item-action">
                            <i class="fas fa-save mr-2"></i>Xem video nháp
                        </a>
                        <a href="{{ route('admin.video-publishing.index', ['status' => 'failed']) }}" 
                           class="list-group-item list-group-item-action">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Xem video thất bại
                        </a>
                        <a href="{{ route('admin.video-generator.index') }}" 
                           class="list-group-item list-group-item-action">
                            <i class="fas fa-plus mr-2"></i>Tạo video mới
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thống kê hôm nay</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <div class="h4 font-weight-bold text-primary">{{ $scheduledToday->count() }}</div>
                                <div class="text-muted">Đã lên lịch</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="h4 font-weight-bold text-danger">{{ $overdue->count() }}</div>
                            <div class="text-muted">Quá hạn</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
