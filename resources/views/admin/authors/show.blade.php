@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Tác giả', 'url' => route('admin.authors.index')],
        ['title' => $author->name]
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>👨‍💼 {{ $author->name }}</h2>
        <div>
            <a href="{{ route('admin.authors.edit', $author) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Chỉnh sửa
            </a>
            <a href="{{ route('author.show', $author->slug) }}" class="btn btn-success" target="_blank">
                <i class="fas fa-external-link-alt mr-1"></i>Xem frontend
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Author Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $author->avatar_url }}" 
                         alt="{{ $author->name }}" 
                         class="img-thumbnail mb-3" 
                         style="max-width: 200px;">
                    
                    <h4>{{ $author->name }}</h4>
                    
                    @if($author->nationality)
                        <p class="text-muted">
                            <i class="fas fa-flag mr-1"></i>{{ $author->nationality }}
                        </p>
                    @endif

                    @if($author->birth_date)
                        <p class="text-muted">
                            <i class="fas fa-birthday-cake mr-1"></i>
                            {{ $author->formatted_birth_date }}
                            @if($author->age)
                                ({{ $author->age }} tuổi)
                            @endif
                        </p>
                    @endif

                    <div class="mb-3">
                        @if($author->is_active)
                            <span class="badge badge-success">Hoạt động</span>
                        @else
                            <span class="badge badge-secondary">Vô hiệu</span>
                        @endif
                    </div>

                    <!-- Social Links -->
                    @if($author->social_links)
                        <div class="mb-3">
                            @foreach($author->social_links as $platform => $link)
                                <a href="{{ $link['url'] }}" 
                                   class="btn btn-sm btn-outline-primary mr-1" 
                                   target="_blank" 
                                   title="{{ $link['label'] }}">
                                    <i class="{{ $link['icon'] }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($author->email)
                        <p>
                            <i class="fas fa-envelope mr-1"></i>
                            <a href="mailto:{{ $author->email }}">{{ $author->email }}</a>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Statistics -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Thống kê</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">{{ $author->stories->count() }}</h4>
                            <small class="text-muted">Truyện</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">{{ $author->stories->sum('chapters_count') }}</h4>
                            <small class="text-muted">Chương</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="col-md-8">
            <!-- Biography -->
            @if($author->bio)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tiểu sử</h5>
                    </div>
                    <div class="card-body">
                        <p>{{ $author->bio }}</p>
                    </div>
                </div>
            @endif

            <!-- Achievements -->
            @if($author->achievements && count($author->achievements) > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Thành tựu & Giải thưởng</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            @foreach($author->achievements as $achievement)
                                <li class="mb-2">
                                    <i class="fas fa-trophy text-warning mr-2"></i>
                                    {{ $achievement }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Stories -->
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Truyện của tác giả ({{ $author->stories->count() }})</h5>
                    <a href="{{ route('admin.stories.create') }}?author_id={{ $author->id }}" 
                       class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i>Thêm truyện
                    </a>
                </div>
                <div class="card-body">
                    @if($author->stories->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tên truyện</th>
                                        <th>Số chương</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($author->stories as $story)
                                        <tr>
                                            <td>
                                                <strong>{{ $story->title }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $story->chapters_count }} chương</span>
                                            </td>
                                            <td>
                                                @if($story->is_public)
                                                    <span class="badge badge-success">Public</span>
                                                @else
                                                    <span class="badge badge-secondary">Private</span>
                                                @endif
                                            </td>
                                            <td>{{ $story->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.stories.show', $story) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.stories.edit', $story) }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-book fa-2x text-muted mb-2"></i>
                            <p class="text-muted">Tác giả chưa có truyện nào</p>
                            <a href="{{ route('admin.stories.create') }}?author_id={{ $author->id }}" 
                               class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Thêm truyện đầu tiên
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SEO Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin SEO</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Meta Title:</strong>
                            <p class="text-muted">{{ $author->seo_title }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Meta Keywords:</strong>
                            <p class="text-muted">{{ $author->seo_keywords }}</p>
                        </div>
                    </div>
                    <div>
                        <strong>Meta Description:</strong>
                        <p class="text-muted">{{ $author->seo_description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
