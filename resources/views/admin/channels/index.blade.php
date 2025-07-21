@extends('layouts.app')

@section('title', 'Quản Lý Kênh')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Kênh',
            'badge' => 'TikTok & YouTube'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-broadcast-tower mr-2"></i>Quản Lý Kênh TikTok & YouTube
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.channels.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Thêm Kênh Mới
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Quản lý các kênh TikTok và YouTube để đăng video tự động với API upload và lên lịch đăng.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Cards -->
    <div class="row">
        @forelse($channels as $channel)
            <div class="col-lg-6 col-12 mb-4">
                <div class="card {{ $channel->is_active ? '' : 'bg-light' }}">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                @if($channel->platform === 'tiktok')
                                    <i class="fab fa-tiktok fa-2x text-dark mr-3"></i>
                                @elseif($channel->platform === 'youtube')
                                    <i class="fab fa-youtube fa-2x text-danger mr-3"></i>
                                @endif
                                
                                <div>
                                    <h5 class="mb-0">{{ $channel->name }}</h5>
                                    <small class="text-muted">
                                        @if($channel->username)
                                            @{{ $channel->username }}
                                        @else
                                            {{ ucfirst($channel->platform) }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                @if($channel->is_active)
                                    <span class="badge badge-success mr-2">Hoạt động</span>
                                @else
                                    <span class="badge badge-secondary mr-2">Tạm dừng</span>
                                @endif
                                
                                @if($channel->auto_upload)
                                    <span class="badge badge-info mr-2">Auto Upload</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Channel Info -->
                        @if($channel->description)
                            <p class="text-muted mb-3">{{ Str::limit($channel->description, 100) }}</p>
                        @endif
                        
                        <!-- Logo Preview -->
                        @if($channel->logo_config && isset($channel->logo_config['logo_file']))
                            <div class="mb-3">
                                <small class="text-muted d-block">Logo kênh:</small>
                                <img src="{{ route('admin.logo.serve', $channel->logo_config['logo_file']) }}" 
                                     alt="Logo" style="max-height: 40px; object-fit: contain;">
                                <small class="text-muted ml-2">
                                    {{ $channel->logo_config['position'] ?? 'bottom-right' }} | 
                                    {{ $channel->logo_config['size'] ?? 100 }}px
                                </small>
                            </div>
                        @endif
                        
                        <!-- Stats -->
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border-right">
                                    <h4 class="mb-0 text-primary">{{ $channel->pending_posts_count }}</h4>
                                    <small class="text-muted">Chờ đăng</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-right">
                                    <h4 class="mb-0 text-success">{{ $channel->uploaded_posts_count }}</h4>
                                    <small class="text-muted">Đã đăng</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <h4 class="mb-0 text-info">{{ $channel->getUploadCount('7 days') }}</h4>
                                <small class="text-muted">Tuần này</small>
                            </div>
                        </div>
                        
                        <!-- API Status -->
                        <div class="mt-3">
                            @if($channel->hasValidCredentials())
                                <span class="badge badge-success">
                                    <i class="fas fa-check mr-1"></i>API Configured
                                </span>
                            @else
                                <span class="badge badge-warning">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>API Not Configured
                                </span>
                            @endif
                            
                            @if($channel->last_upload_at)
                                <small class="text-muted ml-2">
                                    Đăng cuối: {{ $channel->last_upload_at->diffForHumans() }}
                                </small>
                            @endif
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="{{ route('admin.channels.show', $channel) }}" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                                <a href="{{ route('admin.channels.edit', $channel) }}" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                            </div>
                            
                            <div>
                                @if($channel->hasValidCredentials())
                                    <button class="btn btn-sm btn-secondary" 
                                            onclick="testConnection({{ $channel->id }})">
                                        <i class="fas fa-plug"></i> Test API
                                    </button>
                                @endif
                                
                                <form action="{{ route('admin.channels.toggle-status', $channel) }}" 
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="btn btn-sm {{ $channel->is_active ? 'btn-secondary' : 'btn-success' }}">
                                        <i class="fas {{ $channel->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                        {{ $channel->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                                    </button>
                                </form>
                                
                                <form action="{{ route('admin.channels.destroy', $channel) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa kênh này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-broadcast-tower fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">Chưa có kênh nào</h4>
                        <p class="text-muted mb-4">
                            Thêm kênh TikTok hoặc YouTube đầu tiên để bắt đầu đăng video tự động
                        </p>
                        <a href="{{ route('admin.channels.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Thêm Kênh Đầu Tiên
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Quick Stats -->
    @if($channels->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar mr-2"></i>Thống Kê Tổng Quan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-6">
                            <div class="border-right">
                                <h3 class="text-primary">{{ $channels->count() }}</h3>
                                <p class="text-muted mb-0">Tổng kênh</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="border-right">
                                <h3 class="text-success">{{ $channels->where('is_active', true)->count() }}</h3>
                                <p class="text-muted mb-0">Kênh hoạt động</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="border-right">
                                <h3 class="text-info">{{ $channels->sum('pending_posts_count') }}</h3>
                                <p class="text-muted mb-0">Video chờ đăng</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <h3 class="text-warning">{{ $channels->sum('uploaded_posts_count') }}</h3>
                            <p class="text-muted mb-0">Video đã đăng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
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
</script>
@endpush
@endsection
