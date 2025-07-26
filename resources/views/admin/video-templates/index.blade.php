@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Template Video']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-layer-group mr-2"></i>Quản lý Template Video</h2>
        <a href="{{ route('admin.video-templates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Tạo Template Mới
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label for="category" class="form-label">Danh mục</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           value="{{ $search }}" placeholder="Tìm theo tên hoặc mô tả...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-search mr-1"></i>Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popular Templates -->
    @if($popularTemplates->count() > 0 && !$search && !$category)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-fire mr-2"></i>Template Phổ Biến</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($popularTemplates as $template)
                <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                    <div class="card template-card-small">
                        <div class="card-body text-center p-2">
                            @if($template->thumbnail)
                                <img src="{{ Storage::url($template->thumbnail) }}" 
                                     class="img-fluid mb-2" style="height: 60px; object-fit: cover;">
                            @else
                                <div class="template-icon mb-2">
                                    <i class="fas fa-video fa-2x text-muted"></i>
                                </div>
                            @endif
                            <h6 class="card-title mb-1" style="font-size: 0.875rem;">{{ $template->name }}</h6>
                            <small class="text-muted">{{ $template->usage_count }} lượt dùng</small>
                            <div class="mt-2">
                                <a href="{{ route('admin.video-templates.use', $template) }}" 
                                   class="btn btn-sm btn-primary">Dùng</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Templates Grid -->
    <div class="row">
        @forelse($templates as $template)
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card template-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="badge badge-{{ $template->category === 'tiktok' ? 'info' : ($template->category === 'youtube' ? 'danger' : 'secondary') }}">
                        {{ $categories[$template->category] }}
                    </span>
                    <div class="btn-group btn-group-sm">
                        @if($template->created_by === auth()->id() || auth()->user()->isAdmin())
                        <a href="{{ route('admin.video-templates.edit', $template) }}"
                           class="btn btn-warning btn-sm" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        <a href="{{ route('admin.video-templates.duplicate', $template) }}"
                           class="btn btn-secondary btn-sm" title="Sao chép">
                            <i class="fas fa-copy"></i>
                        </a>
                        @if($template->created_by === auth()->id() || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.video-templates.destroy', $template) }}"
                              onsubmit="return confirm('Bạn có chắc muốn xóa template này?')" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                
                @if($template->thumbnail)
                <img src="{{ Storage::url($template->thumbnail) }}"
                     class="card-img-top template-thumbnail" style="height: 200px; object-fit: cover;">
                @else
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light template-thumbnail"
                     style="height: 200px;">
                    <i class="fas fa-video fa-4x text-muted"></i>
                </div>
                @endif
                
                <div class="card-body">
                    <h5 class="card-title">{{ $template->name }}</h5>
                    <p class="card-text text-muted">{{ Str::limit($template->description, 100) }}</p>
                    
                    <div class="template-stats mb-3">
                        <small class="text-muted">
                            <i class="fas fa-user mr-1"></i>{{ $template->creator->name ?? 'Unknown' }}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-chart-line mr-1"></i>{{ $template->usage_count }} lượt sử dụng
                        </small>
                        @if($template->last_used_at)
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-clock mr-1"></i>{{ $template->last_used_at->diffForHumans() }}
                        </small>
                        @endif
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-6">
                            <a href="{{ route('admin.video-templates.use', $template) }}" 
                               class="btn btn-primary btn-block">
                                <i class="fas fa-play mr-1"></i>Sử dụng
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.video-templates.show', $template) }}" 
                               class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-eye mr-1"></i>Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-layer-group fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Chưa có template nào</h4>
                <p class="text-muted">Tạo template đầu tiên để bắt đầu sử dụng tính năng này.</p>
                <a href="{{ route('admin.video-templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Tạo Template Mới
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($templates->hasPages())
    <div class="d-flex justify-content-center">
        {{ $templates->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.template-card {
    transition: transform 0.2s ease-in-out;
    border: 1px solid #e3e6f0;
}

.template-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.template-card-small {
    transition: transform 0.2s ease-in-out;
}

.template-card-small:hover {
    transform: translateY(-2px);
}

.template-stats small {
    display: block;
    margin-bottom: 0.25rem;
}

.template-icon {
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 4 cards per row layout improvements */
@media (min-width: 992px) {
    .col-lg-3 .template-card {
        min-height: 350px;
    }

    .col-lg-3 .card-title {
        font-size: 0.9rem;
        line-height: 1.3;
        height: 2.6rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .col-lg-3 .card-text {
        font-size: 0.8rem;
        line-height: 1.2;
        height: 3.6rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .col-lg-3 .badge {
        font-size: 0.7rem;
    }

    /* Button group improvements for 4-card layout */
    .col-lg-3 .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Popular templates - 6 per row */
@media (min-width: 992px) {
    .col-lg-2 .template-card-small {
        min-height: 120px;
    }

    .col-lg-2 .template-card-small .card-body {
        padding: 0.5rem;
    }

    .col-lg-2 .template-card-small .card-title {
        font-size: 0.75rem;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .col-md-6 .template-card {
        min-height: 300px;
    }

    .col-md-3 .template-card-small {
        min-height: 100px;
    }
}

@media (max-width: 575px) {
    .col-sm-12 .template-card {
        min-height: auto;
    }

    .col-6 .template-card-small {
        min-height: 80px;
    }

    .col-6 .template-card-small .card-title {
        font-size: 0.7rem;
    }
}

/* Button group enhancements */
.btn-group-sm .btn {
    transition: all 0.2s ease-in-out;
    border-radius: 0.2rem !important;
    margin-right: 1px;
}

.btn-group-sm .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-group-sm .btn:last-child {
    margin-right: 0;
}

/* Color-coded action buttons */
.btn-group-sm .btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-group-sm .btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-group-sm .btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-group-sm .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-group-sm .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

/* Template card hover effects */
.template-card {
    transition: all 0.3s ease;
}

.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.template-thumbnail {
    transition: transform 0.3s ease;
}

.template-card:hover .template-thumbnail {
    transform: scale(1.02);
}
</style>
@endpush
