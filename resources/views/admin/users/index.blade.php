@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/sortable.css') }}">
@endpush

@section('title', 'Quản lý Người dùng')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Quản lý Người dùng</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active">Người dùng</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Danh sách Người dùng</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Thêm Người dùng
                    </a>
                </div>
            </div>
            <!-- Search Section -->
            <div class="card-body border-bottom search-form">
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-center">
                    <div class="form-group mb-0 mr-3">
                        <label for="search" class="mr-2 mb-0">Tìm kiếm:</label>
                        <input type="text"
                               name="search"
                               id="search"
                               class="form-control"
                               placeholder="Nhập tên hoặc email..."
                               value="{{ request('search') }}"
                               style="width: 300px;">
                    </div>

                    <!-- Preserve sort parameters -->
                    <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>

                    @if(request('search'))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </a>
                    @endif
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <x-sortable-header route="admin.users.index" column="name" title="Tên" />
                            <x-sortable-header route="admin.users.index" column="email" title="Email" />
                            <x-sortable-header route="admin.users.index" column="role" title="Vai trò" />
                            <x-sortable-header route="admin.users.index" column="created_at" title="Ngày tạo" />
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if ($user->id === auth()->id())
                                        <span class="badge badge-info badge-sm">Bạn</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{!! $user->role_badge !!}</td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" 
                                                  style="display: inline-block;" 
                                                  onsubmit="return confirm('Bạn có chắc muốn xóa user này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Không có user nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="card-footer clearfix">
                    {{ $users->appends(request()->query())->links('vendor.pagination.adminlte') }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
