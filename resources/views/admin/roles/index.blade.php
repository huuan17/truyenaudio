@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Vai trò',
            'badge' => ($roles->total() ?? 0) . ' vai trò'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-shield mr-2"></i>
                        Quản lý Vai trò & Phân quyền
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Thêm Vai trò mới
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="20%">Tên Vai trò</th>
                                    <th width="15%">Ưu tiên</th>
                                    <th width="15%">Quyền hạn</th>
                                    <th width="10%">Người dùng</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="25%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr>
                                        <td>{{ $role->id }}</td>
                                        <td>
                                            <div>
                                                <strong class="text-primary">{{ $role->display_name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $role->name }}</small>
                                            </div>
                                            @if($role->description)
                                                <div class="mt-1">
                                                    <small class="text-info">{{ Str::limit($role->description, 50) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $role->priority >= 90 ? 'danger' : ($role->priority >= 70 ? 'warning' : ($role->priority >= 50 ? 'info' : 'secondary')) }}">
                                                {{ $role->priority }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                {{ $role->permissions_count ?? 0 }} permissions
                                            </span>
                                            @if(($role->permissions_count ?? 0) > 0 && isset($role->permission_modules))
                                                <div class="mt-1">
                                                    @foreach($role->permission_modules as $module)
                                                        <small class="badge badge-light">{{ $module }}</small>
                                                    @endforeach
                                                    @if(($role->permissions_count ?? 0) > count($role->permission_modules))
                                                        <small class="text-muted">+{{ ($role->permissions_count ?? 0) - count($role->permission_modules) }} more</small>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($role->users_count > 0)
                                                <span class="badge badge-success">
                                                    {{ $role->users_count }} users
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">0 users</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($role->is_active)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Active
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times"></i> Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <!-- View Button -->
                                                <a href="{{ route('admin.roles.show', $role) }}" 
                                                   class="btn btn-sm btn-info mb-1" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                <!-- Edit Button -->
                                                <a href="{{ route('admin.roles.edit', $role) }}" 
                                                   class="btn btn-sm btn-warning mb-1" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                
                                                <!-- Toggle Status Button -->
                                                <form action="{{ route('admin.roles.toggle-status', $role) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-{{ $role->is_active ? 'secondary' : 'success' }} mb-1"
                                                            title="{{ $role->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}"
                                                            onclick="return confirm('Bạn có chắc muốn {{ $role->is_active ? 'vô hiệu hóa' : 'kích hoạt' }} role này?')">
                                                        <i class="fas fa-{{ $role->is_active ? 'pause' : 'play' }}"></i>
                                                        {{ $role->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>
                                                
                                                <!-- Delete Button -->
                                                @if($role->name !== 'super_admin')
                                                    <form action="{{ route('admin.roles.destroy', $role) }}" 
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-danger"
                                                                title="Xóa role"
                                                                onclick="return confirm('Bạn có chắc muốn xóa role này? Hành động này không thể hoàn tác!')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-sm btn-secondary" disabled title="Không thể xóa Super Admin">
                                                        <i class="fas fa-lock"></i> Protected
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Chưa có role nào</h5>
                                                <p class="text-muted">Hãy tạo role đầu tiên để bắt đầu phân quyền</p>
                                                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Tạo Role đầu tiên
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($roles->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $roles->links() }}
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Hiển thị {{ $roles->firstItem() ?? 0 }} - {{ $roles->lastItem() ?? 0 }} 
                                trong tổng số {{ $roles->total() }} roles
                            </small>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i>
                                Priority cao hơn = quyền hạn cao hơn
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Statistics -->
    <div class="row mt-3">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-user-shield"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Roles</span>
                    <span class="info-box-number">{{ $roles->total() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Roles</span>
                    <span class="info-box-number">{{ $roles->where('is_active', true)->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-key"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Permissions</span>
                    <span class="info-box-number">{{ \App\Models\Permission::count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Users with Roles</span>
                    <span class="info-box-number">{{ \App\Models\User::whereHas('roles')->count() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-group-vertical .btn {
    border-radius: 0.25rem !important;
    margin-bottom: 2px;
}
.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
}
</style>
@endsection
