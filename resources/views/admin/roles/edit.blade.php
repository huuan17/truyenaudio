@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Roles', 'url' => route('admin.roles.index')],
        ['title' => 'Chỉnh sửa Role: ' . $role->display_name]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Chỉnh sửa Role: {{ $role->display_name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> Xem chi tiết
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Có lỗi xảy ra:</h5>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Thông tin cơ bản</h4>
                                    </div>
                                    <div class="card-body">
                                        <!-- Role Name -->
                                        <div class="form-group">
                                            <label for="name">Tên Role <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   name="name" 
                                                   id="name"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   value="{{ old('name', $role->name) }}" 
                                                   {{ $role->name === 'super_admin' ? 'readonly' : '' }}
                                                   required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @if($role->name === 'super_admin')
                                                <small class="form-text text-warning">
                                                    <i class="fas fa-lock"></i> Không thể thay đổi tên Super Admin role
                                                </small>
                                            @else
                                                <small class="form-text text-muted">
                                                    Tên role sẽ được tự động chuyển thành slug (chữ thường, gạch dưới)
                                                </small>
                                            @endif
                                        </div>

                                        <!-- Display Name -->
                                        <div class="form-group">
                                            <label for="display_name">Tên hiển thị <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   name="display_name" 
                                                   id="display_name"
                                                   class="form-control @error('display_name') is-invalid @enderror"
                                                   value="{{ old('display_name', $role->display_name) }}" 
                                                   required>
                                            @error('display_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Description -->
                                        <div class="form-group">
                                            <label for="description">Mô tả</label>
                                            <textarea name="description" 
                                                      id="description"
                                                      class="form-control @error('description') is-invalid @enderror"
                                                      rows="3"
                                                      placeholder="Mô tả vai trò và trách nhiệm của role này">{{ old('description', $role->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Priority -->
                                        <div class="form-group">
                                            <label for="priority">Độ ưu tiên <span class="text-danger">*</span></label>
                                            <input type="number" 
                                                   name="priority" 
                                                   id="priority"
                                                   class="form-control @error('priority') is-invalid @enderror"
                                                   value="{{ old('priority', $role->priority) }}" 
                                                   min="0" 
                                                   max="100"
                                                   {{ $role->name === 'super_admin' ? 'readonly' : '' }}
                                                   required>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @if($role->name === 'super_admin')
                                                <small class="form-text text-warning">
                                                    <i class="fas fa-lock"></i> Không thể thay đổi priority của Super Admin
                                                </small>
                                            @else
                                                <small class="form-text text-muted">
                                                    Số càng cao = quyền hạn càng lớn (0-100)
                                                </small>
                                            @endif
                                        </div>

                                        <!-- Status -->
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       name="is_active" 
                                                       id="is_active"
                                                       class="form-check-input"
                                                       value="1"
                                                       {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                                                       {{ $role->name === 'super_admin' ? 'disabled' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    Kích hoạt role
                                                </label>
                                            </div>
                                            @if($role->name === 'super_admin')
                                                <small class="form-text text-warning">
                                                    <i class="fas fa-lock"></i> Super Admin luôn được kích hoạt
                                                </small>
                                            @else
                                                <small class="form-text text-muted">
                                                    Role không kích hoạt sẽ không thể được gán cho user
                                                </small>
                                            @endif
                                        </div>

                                        <!-- Role Info -->
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Thông tin Role</h6>
                                            <ul class="mb-0">
                                                <li><strong>ID:</strong> {{ $role->id }}</li>
                                                <li><strong>Tạo lúc:</strong> {{ $role->created_at->format('d/m/Y H:i') }}</li>
                                                <li><strong>Cập nhật:</strong> {{ $role->updated_at->format('d/m/Y H:i') }}</li>
                                                <li><strong>Users hiện tại:</strong> {{ $role->users()->count() }} users</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissions -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Phân quyền</h4>
                                        <div class="card-tools">
                                            @if($role->name !== 'super_admin')
                                                <button type="button" class="btn btn-sm btn-primary" onclick="selectAllPermissions()">
                                                    Chọn tất cả
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllPermissions()">
                                                    Bỏ chọn tất cả
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                        @if($role->name === 'super_admin')
                                            <div class="alert alert-warning">
                                                <i class="fas fa-crown"></i>
                                                <strong>Super Admin</strong> có toàn quyền trên hệ thống và không thể thay đổi permissions.
                                            </div>
                                        @endif

                                        @foreach($permissions as $module => $modulePermissions)
                                            <div class="permission-module mb-3">
                                                <h6 class="text-primary border-bottom pb-2">
                                                    <i class="{{ $modulePermissions->first()->module_icon }}"></i>
                                                    {{ ucfirst($module) }}
                                                    @if($role->name !== 'super_admin')
                                                        <button type="button" 
                                                                class="btn btn-xs btn-outline-primary float-right"
                                                                onclick="toggleModulePermissions('{{ $module }}')">
                                                            Toggle Module
                                                        </button>
                                                    @endif
                                                </h6>
                                                
                                                <div class="row">
                                                    @foreach($modulePermissions as $permission)
                                                        <div class="col-md-6 mb-2">
                                                            <div class="form-check">
                                                                <input type="checkbox" 
                                                                       name="permissions[]" 
                                                                       id="permission_{{ $permission->id }}"
                                                                       class="form-check-input permission-checkbox module-{{ $module }}"
                                                                       value="{{ $permission->id }}"
                                                                       {{ in_array($permission->id, $role->current_permission_ids ?? []) ? 'checked' : '' }}
                                                                       {{ $role->name === 'super_admin' ? 'disabled' : '' }}>
                                                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                    <span class="badge {{ $permission->badge_class }}">
                                                                        <i class="{{ $permission->action_icon }}"></i>
                                                                        {{ $permission->action }}
                                                                    </span>
                                                                    <br>
                                                                    <small class="text-muted">{{ $permission->display_name }}</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Cập nhật Role
                                </button>
                                <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-info">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </a>
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Các trường có dấu <span class="text-danger">*</span> là bắt buộc
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox:not([disabled])').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox:not([disabled])').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function toggleModulePermissions(module) {
    const moduleCheckboxes = document.querySelectorAll('.module-' + module + ':not([disabled])');
    const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
    
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}
</script>

<style>
.permission-module {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

.form-check-label {
    cursor: pointer;
}

.badge {
    font-size: 0.75em;
}

input[readonly] {
    background-color: #e9ecef;
}
</style>
@endsection
