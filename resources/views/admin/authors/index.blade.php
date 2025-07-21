@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/sortable.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Tác giả',
            'badge' => ($authors->total() ?? 0) . ' tác giả'
        ]
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>👨‍💼 Danh sách tác giả</h2>
        <a href="{{ route('admin.authors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Thêm tác giả
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <!-- Search Section -->
        <div class="card-body border-bottom search-form">
            <form method="GET" action="{{ route('admin.authors.index') }}" class="d-flex align-items-center">
                <div class="form-group mb-0 mr-3">
                    <label for="search" class="mr-2 mb-0">Tìm kiếm:</label>
                    <input type="text"
                           name="search"
                           id="search"
                           class="form-control"
                           placeholder="Nhập tên tác giả hoặc tiểu sử..."
                           value="{{ request('search') }}"
                           style="width: 300px;">
                </div>

                <!-- Preserve sort parameters -->
                <input type="hidden" name="sort" value="{{ request('sort', 'name') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">

                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>

                @if(request('search'))
                    <a href="{{ route('admin.authors.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body">
            @if($authors->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <x-sortable-header route="admin.authors.index" column="name" title="Tên tác giả" />
                                <th>Slug</th>
                                <th>Số truyện</th>
                                <th>Trạng thái</th>
                                <x-sortable-header route="admin.authors.index" column="created_at" title="Ngày tạo" />
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($authors as $author)
                                <tr>
                                    <td>
                                        <img src="{{ $author->avatar_url }}" 
                                             alt="{{ $author->name }}" 
                                             class="rounded-circle" 
                                             width="40" height="40">
                                    </td>
                                    <td>
                                        <strong>{{ $author->name }}</strong>
                                        @if($author->nationality)
                                            <br><small class="text-muted">{{ $author->nationality }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $author->slug }}</code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $author->stories_count }} truyện</span>
                                    </td>
                                    <td>
                                        @if($author->is_active)
                                            <span class="badge badge-success">Hoạt động</span>
                                        @else
                                            <span class="badge badge-secondary">Vô hiệu</span>
                                        @endif
                                    </td>
                                    <td>{{ $author->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.authors.show', $author) }}" 
                                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.authors.edit', $author) }}" 
                                               class="btn btn-sm btn-warning" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.authors.toggle-status', $author) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn btn-sm {{ $author->is_active ? 'btn-secondary' : 'btn-success' }}" 
                                                        title="{{ $author->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                    <i class="fas {{ $author->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                </button>
                                            </form>
                                            @if($author->stories_count == 0)
                                                <form action="{{ route('admin.authors.destroy', $author) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa tác giả này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($authors->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $authors->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có tác giả nào</h5>
                    <p class="text-muted">Hãy thêm tác giả đầu tiên cho hệ thống.</p>
                    <a href="{{ route('admin.authors.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Thêm tác giả
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
