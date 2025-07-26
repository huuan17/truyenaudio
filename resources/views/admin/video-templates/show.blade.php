@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Template Video', 'url' => route('admin.video-templates.index')],
        ['title' => $videoTemplate->name]
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group mr-2"></i>{{ $videoTemplate->name }}
                        </h5>
                        <div>
                            <a href="{{ route('admin.video-templates.use', $videoTemplate) }}" 
                               class="btn btn-light btn-sm">
                                <i class="fas fa-play mr-1"></i>Sử dụng Template
                            </a>
                            @if($videoTemplate->created_by === auth()->id() || auth()->user()->isAdmin())
                            <a href="{{ route('admin.video-templates.edit', $videoTemplate) }}" 
                               class="btn btn-outline-light btn-sm ml-1">
                                <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($videoTemplate->thumbnail)
                    <div class="text-center mb-4">
                        <img src="{{ Storage::url($videoTemplate->thumbnail) }}" 
                             class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                    @endif

                    <div class="template-description mb-4">
                        <h6>Mô tả:</h6>
                        <p class="text-muted">{{ $videoTemplate->description ?: 'Chưa có mô tả' }}</p>
                    </div>

                    <!-- Required Inputs -->
                    @if($videoTemplate->required_inputs && count($videoTemplate->required_inputs) > 0)
                    <div class="section mb-4">
                        <h6 class="text-danger">
                            <i class="fas fa-asterisk mr-2"></i>Input bắt buộc
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tên trường</th>
                                        <th>Loại</th>
                                        <th>Nhãn hiển thị</th>
                                        <th>Hướng dẫn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($videoTemplate->required_inputs as $input)
                                    <tr>
                                        <td><code>{{ $input['name'] }}</code></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                {{ \App\Models\VideoTemplate::getInputTypes()[$input['type']] ?? $input['type'] }}
                                            </span>
                                        </td>
                                        <td>{{ $input['label'] }}</td>
                                        <td class="text-muted">{{ $input['placeholder'] ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Optional Inputs -->
                    @if($videoTemplate->optional_inputs && count($videoTemplate->optional_inputs) > 0)
                    <div class="section mb-4">
                        <h6 class="text-info">
                            <i class="fas fa-plus-circle mr-2"></i>Input tùy chọn
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tên trường</th>
                                        <th>Loại</th>
                                        <th>Nhãn hiển thị</th>
                                        <th>Hướng dẫn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($videoTemplate->optional_inputs as $input)
                                    <tr>
                                        <td><code>{{ $input['name'] }}</code></td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ \App\Models\VideoTemplate::getInputTypes()[$input['type']] ?? $input['type'] }}
                                            </span>
                                        </td>
                                        <td>{{ $input['label'] }}</td>
                                        <td class="text-muted">{{ $input['placeholder'] ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Template Settings -->
                    <div class="section mb-4">
                        <h6><i class="fas fa-cog mr-2"></i>Cài đặt Template</h6>
                        <div class="settings-display">
                            <pre class="bg-light p-3 rounded"><code>{{ json_encode($videoTemplate->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Template Stats -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Thống kê</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-item">
                                <h4 class="text-primary">{{ $videoTemplate->usage_count }}</h4>
                                <small class="text-muted">Lượt sử dụng</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <h4 class="text-info">{{ count($videoTemplate->required_inputs ?? []) }}</h4>
                                <small class="text-muted">Input bắt buộc</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="template-meta">
                        <div class="meta-item mb-2">
                            <strong>Danh mục:</strong>
                            <span class="badge badge-{{ $videoTemplate->category === 'tiktok' ? 'info' : ($videoTemplate->category === 'youtube' ? 'danger' : 'secondary') }}">
                                {{ \App\Models\VideoTemplate::getCategories()[$videoTemplate->category] }}
                            </span>
                        </div>
                        
                        <div class="meta-item mb-2">
                            <strong>Trạng thái:</strong>
                            <span class="badge badge-{{ $videoTemplate->is_active ? 'success' : 'secondary' }}">
                                {{ $videoTemplate->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                            </span>
                        </div>
                        
                        <div class="meta-item mb-2">
                            <strong>Công khai:</strong>
                            <span class="badge badge-{{ $videoTemplate->is_public ? 'success' : 'warning' }}">
                                {{ $videoTemplate->is_public ? 'Có' : 'Không' }}
                            </span>
                        </div>
                        
                        <div class="meta-item mb-2">
                            <strong>Tạo bởi:</strong>
                            <span class="text-muted">{{ $videoTemplate->creator->name ?? 'Unknown' }}</span>
                        </div>
                        
                        <div class="meta-item mb-2">
                            <strong>Ngày tạo:</strong>
                            <span class="text-muted">{{ $videoTemplate->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        
                        @if($videoTemplate->last_used_at)
                        <div class="meta-item mb-2">
                            <strong>Dùng lần cuối:</strong>
                            <span class="text-muted">{{ $videoTemplate->last_used_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tools mr-2"></i>Thao tác</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.video-templates.use', $videoTemplate) }}" 
                           class="btn btn-primary btn-block">
                            <i class="fas fa-play mr-2"></i>Sử dụng Template
                        </a>
                        
                        <a href="{{ route('admin.video-templates.duplicate', $videoTemplate) }}" 
                           class="btn btn-outline-info btn-block">
                            <i class="fas fa-copy mr-2"></i>Sao chép Template
                        </a>
                        
                        @if($videoTemplate->created_by === auth()->id() || auth()->user()->isAdmin())
                        <a href="{{ route('admin.video-templates.edit', $videoTemplate) }}" 
                           class="btn btn-outline-warning btn-block">
                            <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                        </a>
                        
                        <form method="POST" action="{{ route('admin.video-templates.destroy', $videoTemplate) }}" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa template này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-trash mr-2"></i>Xóa Template
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Related Templates -->
            @php
                $relatedTemplates = \App\Models\VideoTemplate::where('category', $videoTemplate->category)
                                                            ->where('id', '!=', $videoTemplate->id)
                                                            ->where('is_active', true)
                                                            ->limit(3)
                                                            ->get();
            @endphp
            
            @if($relatedTemplates->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-layer-group mr-2"></i>Template liên quan</h6>
                </div>
                <div class="card-body">
                    @foreach($relatedTemplates as $related)
                    <div class="related-template mb-3">
                        <div class="d-flex align-items-center">
                            @if($related->thumbnail)
                                <img src="{{ Storage::url($related->thumbnail) }}" 
                                     class="rounded mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded mr-3 d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-video text-muted"></i>
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="{{ route('admin.video-templates.show', $related) }}" 
                                       class="text-decoration-none">{{ $related->name }}</a>
                                </h6>
                                <small class="text-muted">{{ $related->usage_count }} lượt dùng</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.section {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 1rem;
}

.stat-item {
    padding: 1rem 0;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
}

.settings-display pre {
    max-height: 300px;
    overflow-y: auto;
    font-size: 0.875rem;
}

.related-template {
    border-bottom: 1px solid #f1f1f1;
    padding-bottom: 0.75rem;
}

.related-template:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
</style>
@endpush
