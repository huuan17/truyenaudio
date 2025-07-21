@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Roles', 'url' => route('admin.roles.index')],
        ['title' => 'Chi tiết Role: ' . $role->display_name]
    ]" />

    <div class="row">
        <!-- Role Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-shield mr-2"></i>
                        Thông tin Role
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">ID:</dt>
                        <dd class="col-sm-8">{{ $role->id }}</dd>

                        <dt class="col-sm-4">Tên:</dt>
                        <dd class="col-sm-8">
                            <code>{{ $role->name }}</code>
                        </dd>

                        <dt class="col-sm-4">Hiển thị:</dt>
                        <dd class="col-sm-8">
                            <strong>{{ $role->display_name }}</strong>
                        </dd>

                        <dt class="col-sm-4">Mô tả:</dt>
                        <dd class="col-sm-8">
                            {{ $role->description ?: 'Không có mô tả' }}
                        </dd>

                        <dt class="col-sm-4">Priority:</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $role->priority >= 90 ? 'danger' : ($role->priority >= 70 ? 'warning' : ($role->priority >= 50 ? 'info' : 'secondary')) }}">
                                {{ $role->priority }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Trạng thái:</dt>
                        <dd class="col-sm-8">
                            @if($role->is_active)
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Active
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    <i class="fas fa-times"></i> Inactive
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Tạo lúc:</dt>
                        <dd class="col-sm-8">{{ $role->created_at->format('d/m/Y H:i:s') }}</dd>

                        <dt class="col-sm-4">Cập nhật:</dt>
                        <dd class="col-sm-8">{{ $role->updated_at->format('d/m/Y H:i:s') }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        @if($role->name !== 'super_admin')
                            <form action="{{ route('admin.roles.toggle-status', $role) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="btn btn-{{ $role->is_active ? 'secondary' : 'success' }}"
                                        onclick="return confirm('Bạn có chắc muốn {{ $role->is_active ? 'vô hiệu hóa' : 'kích hoạt' }} role này?')">
                                    <i class="fas fa-{{ $role->is_active ? 'pause' : 'play' }}"></i>
                                    {{ $role->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Thống kê</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="description-block border-right">
                                <span class="description-percentage text-success">
                                    <i class="fas fa-key"></i>
                                </span>
                                <h5 class="description-header">{{ $permissions->count() }}</h5>
                                <span class="description-text">PERMISSIONS</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="description-block">
                                <span class="description-percentage text-info">
                                    <i class="fas fa-users"></i>
                                </span>
                                <h5 class="description-header">{{ $users->count() }}</h5>
                                <span class="description-text">USERS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-key mr-2"></i>
                        Permissions ({{ $permissions->count() }})
                    </h3>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    @if($permissions->count() > 0)
                        @foreach($groupedPermissions as $module => $modulePermissions)
                            <div class="permission-group mb-3">
                                <h6 class="text-primary border-bottom pb-1">
                                    @php
                                        $firstPermission = $modulePermissions->first();
                                        $moduleIcon = match($module) {
                                            'stories' => 'fas fa-book',
                                            'users' => 'fas fa-users',
                                            'roles' => 'fas fa-user-shield',
                                            'settings' => 'fas fa-cogs',
                                            'chapters' => 'fas fa-file-alt',
                                            'authors' => 'fas fa-user-edit',
                                            'genres' => 'fas fa-tags',
                                            'videos' => 'fas fa-video',
                                            'audios' => 'fas fa-volume-up',
                                            default => 'fas fa-circle'
                                        };
                                    @endphp
                                    <i class="{{ $moduleIcon }}"></i>
                                    {{ ucfirst($module) }}
                                    <span class="badge badge-primary float-right">{{ $modulePermissions->count() }}</span>
                                </h6>

                                @foreach($modulePermissions as $permission)
                                    <div class="mb-2">
                                        @php
                                            $actionIcon = match($permission->action) {
                                                'create' => 'fas fa-plus',
                                                'read' => 'fas fa-eye',
                                                'update' => 'fas fa-edit',
                                                'delete' => 'fas fa-trash',
                                                'manage' => 'fas fa-cogs',
                                                default => 'fas fa-circle'
                                            };
                                            $badgeClass = match($permission->action) {
                                                'create' => 'badge-success',
                                                'read' => 'badge-info',
                                                'update' => 'badge-warning',
                                                'delete' => 'badge-danger',
                                                'manage' => 'badge-primary',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            <i class="{{ $actionIcon }}"></i>
                                            {{ $permission->action }}
                                        </span>
                                        <small class="text-muted ml-2">{{ $permission->display_name }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có permission nào</h5>
                            <p class="text-muted">Role này chưa được gán permission nào</p>
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Thêm Permissions
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Users -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-2"></i>
                        Users có Role này ({{ $users->count() }})
                    </h3>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    @if($users->count() > 0)
                        @foreach($users as $user)
                            <div class="user-item d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="user-avatar mr-3">
                                    @php
                                        $initials = collect(explode(' ', $user->name))
                                            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                                            ->take(2)
                                            ->implode('');
                                        $avatarUrl = "https://ui-avatars.com/api/?name={$initials}&size=40&background=007bff&color=ffffff";
                                    @endphp
                                    <img src="{{ $avatarUrl }}"
                                         alt="{{ $user->name }}"
                                         class="img-circle"
                                         width="40"
                                         height="40">
                                </div>
                                <div class="user-info flex-grow-1">
                                    <h6 class="mb-0">{{ $user->name }}</h6>
                                    <small class="text-muted">{{ $user->email }}</small>
                                    <br>
                                    <small class="text-info">
                                        Gán lúc: {{ $user->assigned_at ? \Carbon\Carbon::parse($user->assigned_at)->format('d/m/Y H:i') : 'N/A' }}
                                    </small>
                                </div>
                                <div class="user-actions">
                                    @if($user->is_active ?? true)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                    <br>
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                       class="btn btn-xs btn-info mt-1"
                                       title="Xem chi tiết user">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có user nào</h5>
                            <p class="text-muted">Role này chưa được gán cho user nào</p>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Quản lý Users
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Thao tác</h3>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Chỉnh sửa Role
                        </a>
                        
                        @if($role->name !== 'super_admin')
                            <form action="{{ route('admin.roles.toggle-status', $role) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="btn btn-{{ $role->is_active ? 'secondary' : 'success' }}"
                                        onclick="return confirm('Bạn có chắc muốn {{ $role->is_active ? 'vô hiệu hóa' : 'kích hoạt' }} role này?')">
                                    <i class="fas fa-{{ $role->is_active ? 'pause' : 'play' }}"></i>
                                    {{ $role->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                                </button>
                            </form>

                            @if($users->count() === 0)
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-danger"
                                            onclick="return confirm('Bạn có chắc muốn xóa role này? Hành động này không thể hoàn tác!')">
                                        <i class="fas fa-trash"></i> Xóa Role
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-danger" disabled title="Không thể xóa role đang được sử dụng">
                                    <i class="fas fa-lock"></i> Không thể xóa
                                </button>
                            @endif
                        @else
                            <button class="btn btn-secondary" disabled title="Super Admin được bảo vệ">
                                <i class="fas fa-shield-alt"></i> Role được bảo vệ
                            </button>
                        @endif
                        
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.permission-group {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
}

.user-item {
    transition: all 0.2s;
}

.user-item:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.75em;
}

.description-block {
    text-align: center;
}
</style>
@endsection
