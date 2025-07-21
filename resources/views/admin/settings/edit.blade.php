@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Cài đặt hệ thống', 'url' => route('admin.settings.index')],
        ['title' => 'Sửa cài đặt']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>✏️ Sửa cài đặt: {{ $setting->label }}</h2>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin cài đặt</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update-single', $setting->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="key">Key <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('key') is-invalid @enderror" 
                                           id="key" 
                                           name="key" 
                                           value="{{ old('key', $setting->key) }}" 
                                           required>
                                    @error('key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Tên key duy nhất (snake_case)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="label">Label <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('label') is-invalid @enderror" 
                                           id="label" 
                                           name="label" 
                                           value="{{ old('label', $setting->label) }}" 
                                           required>
                                    @error('label')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Loại <span class="text-danger">*</span></label>
                                    <select name="type" 
                                            id="type" 
                                            class="form-control @error('type') is-invalid @enderror" 
                                            required>
                                        @foreach($types as $typeKey => $typeName)
                                            <option value="{{ $typeKey }}" {{ old('type', $setting->type) === $typeKey ? 'selected' : '' }}>
                                                {{ $typeName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="group">Nhóm <span class="text-danger">*</span></label>
                                    <select name="group" 
                                            id="group" 
                                            class="form-control @error('group') is-invalid @enderror" 
                                            required>
                                        @foreach($groups as $groupKey => $groupName)
                                            <option value="{{ $groupKey }}" {{ old('group', $setting->group) === $groupKey ? 'selected' : '' }}>
                                                {{ $groupName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description', $setting->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="value">Giá trị</label>
                            <div id="value-input">
                                @switch($setting->type)
                                    @case('textarea')
                                    @case('code')
                                        <textarea class="form-control @error('value') is-invalid @enderror {{ $setting->type === 'code' ? 'font-monospace' : '' }}" 
                                                  id="value" 
                                                  name="value" 
                                                  rows="{{ $setting->type === 'code' ? '8' : '4' }}">{{ old('value', $setting->value) }}</textarea>
                                        @break
                                    @case('boolean')
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="value" 
                                                   name="value" 
                                                   value="1"
                                                   {{ old('value', $setting->value) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="value">Kích hoạt</label>
                                        </div>
                                        @break
                                    @case('url')
                                        <input type="url" 
                                               class="form-control @error('value') is-invalid @enderror" 
                                               id="value" 
                                               name="value" 
                                               value="{{ old('value', $setting->value) }}">
                                        @break
                                    @case('email')
                                        <input type="email" 
                                               class="form-control @error('value') is-invalid @enderror" 
                                               id="value" 
                                               name="value" 
                                               value="{{ old('value', $setting->value) }}">
                                        @break
                                    @default
                                        <input type="text" 
                                               class="form-control @error('value') is-invalid @enderror" 
                                               id="value" 
                                               name="value" 
                                               value="{{ old('value', $setting->value) }}">
                                @endswitch
                            </div>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">Thứ tự sắp xếp</label>
                                    <input type="number" 
                                           class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" 
                                           name="sort_order" 
                                           value="{{ old('sort_order', $setting->sort_order) }}" 
                                           min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1"
                                               {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Kích hoạt
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>Cập nhật cài đặt
                            </button>
                            <a href="{{ route('admin.settings.index', ['group' => $setting->group]) }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin</h5>
                </div>
                <div class="card-body">
                    <p><strong>Key:</strong> {{ $setting->key }}</p>
                    <p><strong>Nhóm:</strong> {{ $groups[$setting->group] ?? $setting->group }}</p>
                    <p><strong>Loại:</strong> {{ $types[$setting->type] ?? $setting->type }}</p>
                    <p><strong>Trạng thái:</strong> 
                        <span class="badge badge-{{ $setting->is_active ? 'success' : 'secondary' }}">
                            {{ $setting->is_active ? 'Kích hoạt' : 'Tắt' }}
                        </span>
                    </p>
                    <p><strong>Tạo lúc:</strong> {{ $setting->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Cập nhật:</strong> {{ $setting->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Initial resize
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });
});
</script>
@endpush
@endsection
