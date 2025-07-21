@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Cài đặt hệ thống', 'url' => route('admin.settings.index')],
        ['title' => 'Thêm cài đặt mới']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>➕ Thêm cài đặt mới</h2>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin cài đặt</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="key">Key <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('key') is-invalid @enderror" 
                                           id="key" 
                                           name="key" 
                                           value="{{ old('key') }}" 
                                           required
                                           placeholder="site_name">
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
                                           value="{{ old('label') }}" 
                                           required
                                           placeholder="Tên website">
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
                                            <option value="{{ $typeKey }}" {{ old('type') === $typeKey ? 'selected' : '' }}>
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
                                            <option value="{{ $groupKey }}" {{ old('group', $group) === $groupKey ? 'selected' : '' }}>
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
                                      rows="3"
                                      placeholder="Mô tả về cài đặt này...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="value">Giá trị</label>
                            <div id="value-input">
                                <!-- Dynamic input based on type -->
                                <input type="text" 
                                       class="form-control @error('value') is-invalid @enderror" 
                                       id="value" 
                                       name="value" 
                                       value="{{ old('value') }}"
                                       placeholder="Nhập giá trị...">
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
                                           value="{{ old('sort_order', 0) }}" 
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
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Kích hoạt
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>Tạo cài đặt
                            </button>
                            <a href="{{ route('admin.settings.index', ['group' => $group]) }}" class="btn btn-secondary">
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
                    <h5 class="mb-0">Hướng dẫn</h5>
                </div>
                <div class="card-body">
                    <h6>Loại cài đặt:</h6>
                    <ul class="small">
                        <li><strong>Text:</strong> Văn bản ngắn</li>
                        <li><strong>Textarea:</strong> Văn bản dài</li>
                        <li><strong>Boolean:</strong> Checkbox true/false</li>
                        <li><strong>URL:</strong> Đường dẫn website</li>
                        <li><strong>Email:</strong> Địa chỉ email</li>
                        <li><strong>Code:</strong> Mã HTML/JS/CSS</li>
                    </ul>

                    <h6 class="mt-3">Nhóm cài đặt:</h6>
                    <ul class="small">
                        <li><strong>General:</strong> Thông tin chung</li>
                        <li><strong>SEO:</strong> Tối ưu SEO</li>
                        <li><strong>Tracking:</strong> Theo dõi & phân tích</li>
                        <li><strong>Social:</strong> Mạng xã hội</li>
                        <li><strong>Appearance:</strong> Giao diện</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const valueInput = document.getElementById('value-input');
    
    function updateValueInput() {
        const type = typeSelect.value;
        let html = '';
        
        switch(type) {
            case 'textarea':
            case 'code':
                html = `<textarea class="form-control" id="value" name="value" rows="${type === 'code' ? '8' : '4'}" placeholder="Nhập giá trị...">${document.getElementById('value')?.value || ''}</textarea>`;
                break;
            case 'boolean':
                html = `
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="value" name="value" value="1">
                        <label class="form-check-label" for="value">Kích hoạt</label>
                    </div>
                `;
                break;
            case 'url':
                html = `<input type="url" class="form-control" id="value" name="value" placeholder="https://example.com">`;
                break;
            case 'email':
                html = `<input type="email" class="form-control" id="value" name="value" placeholder="email@example.com">`;
                break;
            default:
                html = `<input type="text" class="form-control" id="value" name="value" placeholder="Nhập giá trị...">`;
        }
        
        valueInput.innerHTML = html;
    }
    
    typeSelect.addEventListener('change', updateValueInput);
    updateValueInput(); // Initial call
});
</script>
@endpush
@endsection
