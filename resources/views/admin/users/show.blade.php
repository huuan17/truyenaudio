@extends('layouts.app')

@section('title', 'Chi tiết User')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Chi tiết User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Chi tiết</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Thông tin User</h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Chỉnh sửa
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">ID</th>
                                <td>{{ $user->id }}</td>
                            </tr>
                            <tr>
                                <th>Tên</th>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if ($user->id === auth()->id())
                                        <span class="badge badge-info badge-sm ml-2">Bạn</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th>Vai trò</th>
                                <td>{!! $user->role_badge !!}</td>
                            </tr>
                            <tr>
                                <th>Email đã xác thực</th>
                                <td>
                                    @if ($user->email_verified_at)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Đã xác thực
                                        </span>
                                        <small class="text-muted ml-2">{{ $user->email_verified_at->format('d/m/Y H:i') }}</small>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Chưa xác thực
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Ngày tạo</th>
                                <td>{{ $user->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Cập nhật lần cuối</th>
                                <td>{{ $user->updated_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        @if ($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" 
                                  style="display: inline-block;" 
                                  onsubmit="return confirm('Bạn có chắc muốn xóa user này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
