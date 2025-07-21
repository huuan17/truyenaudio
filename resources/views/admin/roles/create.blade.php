@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Roles', 'url' => route('admin.roles.index')],
        ['title' => 'Tạo Role mới']
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        Tạo Role mới
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    
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
                                                   value="{{ old('name') }}" 
                                                   placeholder="Ví dụ: content_manager"
                                                   required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Tên role sẽ được tự động chuyển thành slug (chữ thường, gạch dưới)
                                            </small>
                                        </div>

                                        <!-- Display Name -->
                                        <div class="form-group">
                                            <label for="display_name">Tên hiển thị <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   name="display_name" 
                                                   id="display_name"
                                                   class="form-control @error('display_name') is-invalid @enderror"
                                                   value="{{ old('display_name') }}" 
                                                   placeholder="Ví dụ: Content Manager"
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
                                                      placeholder="Mô tả vai trò và trách nhiệm của role này">{{ old('description') }}</textarea>
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
                                                   value="{{ old('priority', 50) }}" 
                                                   min="0" 
                                                   max="100"
                                                   required>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Số càng cao = quyền hạn càng lớn (0-100). Super Admin = 100, Admin = 90
                                            </small>
                                        </div>

                                        <!-- Status -->
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       name="is_active" 
                                                       id="is_active"
                                                       class="form-check-input"
                                                       value="1"
                                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    Kích hoạt role
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Role không kích hoạt sẽ không thể được gán cho user
                                            </small>
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
                                            <button type="button" class="btn btn-sm btn-primary" onclick="selectAllPermissions()">
                                                Chọn tất cả
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllPermissions()">
                                                Bỏ chọn tất cả
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                        @foreach($permissions as $module => $modulePermissions)
                                            <div class="permission-module mb-3">
                                                <h6 class="text-primary border-bottom pb-2">
                                                    <i class="{{ $modulePermissions->first()->module_icon }}"></i>
                                                    {{ ucfirst($module) }}
                                                    <button type="button" 
                                                            class="btn btn-xs btn-outline-primary float-right"
                                                            onclick="toggleModulePermissions('{{ $module }}')">
                                                        Toggle Module
                                                    </button>
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
                                                                       {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
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
                                    <i class="fas fa-save"></i> Tạo Role
                                </button>
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
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function toggleModulePermissions(module) {
    const moduleCheckboxes = document.querySelectorAll('.module-' + module);
    const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
    
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    // This is just for preview, actual slug generation happens in controller
    const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    // You could show a preview here if needed
});
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
</style>
@endsection
