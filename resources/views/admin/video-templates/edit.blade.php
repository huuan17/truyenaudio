@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Template Video', 'url' => route('admin.video-templates.index')],
        ['title' => 'Chỉnh sửa: ' . $videoTemplate->name]
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit mr-2"></i>Chỉnh sửa Template Video</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.video-templates.update', $videoTemplate) }}" enctype="multipart/form-data" id="templateForm" novalidate>
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Thông tin cơ bản</h6>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name">Tên template <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control" 
                                               value="{{ old('name', $videoTemplate->name) }}" required>
                                        @error('name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="category">Danh mục <span class="text-danger">*</span></label>
                                        <select name="category" id="category" class="form-control" required>
                                            <option value="">Chọn danh mục</option>
                                            @foreach($categories as $key => $label)
                                                <option value="{{ $key }}" {{ old('category', $videoTemplate->category) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea name="description" id="description" class="form-control" rows="3" 
                                          placeholder="Mô tả ngắn gọn về template này...">{{ old('description', $videoTemplate->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="thumbnail">Ảnh thumbnail</label>
                                @if($videoTemplate->thumbnail)
                                <div class="current-thumbnail mb-2">
                                    <img src="{{ Storage::url($videoTemplate->thumbnail) }}" 
                                         class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                                    <small class="d-block text-muted">Ảnh hiện tại</small>
                                </div>
                                @endif
                                <input type="file" name="thumbnail" id="thumbnail" class="form-control-file" 
                                       accept="image/*" onchange="previewThumbnail(this)">
                                <small class="form-text text-muted">JPG, PNG. Tối đa 2MB. Để trống nếu không muốn thay đổi.</small>
                                <div id="thumbnail-preview" class="mt-2"></div>
                                @error('thumbnail')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" 
                                           {{ old('is_public', $videoTemplate->is_public) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">
                                        Công khai template (cho phép người khác sử dụng)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Required Inputs -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Input bắt buộc <span class="text-danger">*</span></h6>
                            <p class="text-muted">Các trường thông tin mà người dùng phải nhập khi sử dụng template</p>
                            
                            <div id="required-inputs-container">
                                @foreach($videoTemplate->required_inputs ?? [] as $index => $input)
                                <div class="input-item border p-3 mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tên trường</label>
                                                <input type="text" name="required_inputs[{{ $index }}][name]" class="form-control" 
                                                       value="{{ $input['name'] }}" placeholder="vd: script_text" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Loại input</label>
                                                <select name="required_inputs[{{ $index }}][type]" class="form-control" required>
                                                    @foreach($inputTypes as $key => $label)
                                                        <option value="{{ $key }}" {{ $input['type'] === $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Nhãn hiển thị</label>
                                                <input type="text" name="required_inputs[{{ $index }}][label]" class="form-control" 
                                                       value="{{ $input['label'] }}" placeholder="vd: Nội dung kịch bản" required>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger btn-block remove-input">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Placeholder/Hướng dẫn</label>
                                        <input type="text" name="required_inputs[{{ $index }}][placeholder]" class="form-control"
                                               value="{{ $input['placeholder'] ?? '' }}" placeholder="Hướng dẫn cho người dùng...">
                                    </div>
                                    <div class="form-group select-options" style="display: {{ $input['type'] === 'select' ? 'block' : 'none' }};">
                                        <label>Options (cho Select) - JSON format</label>
                                        <textarea name="required_inputs[{{ $index }}][options]" class="form-control" rows="3"
                                                  placeholder='{"value1": "Label 1", "value2": "Label 2"}'>{{ isset($input['options']) ? json_encode($input['options']) : '' }}</textarea>
                                        <small class="text-muted">Chỉ cần điền khi loại input là "Lựa chọn"</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary" id="add-required-input">
                                <i class="fas fa-plus mr-2"></i>Thêm input bắt buộc
                            </button>
                        </div>

                        <!-- Optional Inputs -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Input tùy chọn</h6>
                            <p class="text-muted">Các trường thông tin tùy chọn (có thể bỏ trống)</p>
                            
                            <div id="optional-inputs-container">
                                @foreach($videoTemplate->optional_inputs ?? [] as $index => $input)
                                <div class="input-item border p-3 mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tên trường</label>
                                                <input type="text" name="optional_inputs[{{ $index }}][name]" class="form-control" 
                                                       value="{{ $input['name'] }}" placeholder="vd: script_text">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Loại input</label>
                                                <select name="optional_inputs[{{ $index }}][type]" class="form-control">
                                                    @foreach($inputTypes as $key => $label)
                                                        <option value="{{ $key }}" {{ $input['type'] === $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Nhãn hiển thị</label>
                                                <input type="text" name="optional_inputs[{{ $index }}][label]" class="form-control" 
                                                       value="{{ $input['label'] }}" placeholder="vd: Nội dung kịch bản">
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger btn-block remove-input">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Placeholder/Hướng dẫn</label>
                                        <input type="text" name="optional_inputs[{{ $index }}][placeholder]" class="form-control"
                                               value="{{ $input['placeholder'] ?? '' }}" placeholder="Hướng dẫn cho người dùng...">
                                    </div>
                                    <div class="form-group select-options" style="display: {{ $input['type'] === 'select' ? 'block' : 'none' }};">
                                        <label>Options (cho Select) - JSON format</label>
                                        <textarea name="optional_inputs[{{ $index }}][options]" class="form-control" rows="3"
                                                  placeholder='{"value1": "Label 1", "value2": "Label 2"}'>{{ isset($input['options']) ? json_encode($input['options']) : '' }}</textarea>
                                        <small class="text-muted">Chỉ cần điền khi loại input là "Lựa chọn"</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <button type="button" class="btn btn-outline-secondary" id="add-optional-input">
                                <i class="fas fa-plus mr-2"></i>Thêm input tùy chọn
                            </button>
                        </div>

                        <!-- Template Settings -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Cài đặt template</h6>
                            <p class="text-muted">Cấu hình mặc định cho video được tạo từ template này</p>

                            <!-- Settings Builder Tabs -->
                            <ul class="nav nav-tabs" id="settingsTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic-settings" role="tab">
                                        <i class="fas fa-cog mr-1"></i>Cơ bản
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="media-tab" data-toggle="tab" href="#media-settings" role="tab">
                                        <i class="fas fa-images mr-1"></i>Media
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="audio-tab" data-toggle="tab" href="#audio-settings" role="tab">
                                        <i class="fas fa-volume-up mr-1"></i>Audio
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="video-tab" data-toggle="tab" href="#video-settings" role="tab">
                                        <i class="fas fa-video mr-1"></i>Video
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="advanced-tab" data-toggle="tab" href="#advanced-settings" role="tab">
                                        <i class="fas fa-sliders-h mr-1"></i>Nâng cao
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="json-tab" data-toggle="tab" href="#json-settings" role="tab">
                                        <i class="fas fa-code mr-1"></i>JSON
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content mt-3" id="settingsTabContent">
                                <!-- Basic Settings Tab -->
                                <div class="tab-pane fade show active" id="basic-settings" role="tabpanel">
                                    @include('admin.video-templates.partials.basic-settings', ['settings' => $videoTemplate->settings])
                                </div>

                                <!-- Media Settings Tab -->
                                <div class="tab-pane fade" id="media-settings" role="tabpanel">
                                    @include('admin.video-templates.partials.media-settings', ['settings' => $videoTemplate->settings])
                                </div>

                                <!-- Audio Settings Tab -->
                                <div class="tab-pane fade" id="audio-settings" role="tabpanel">
                                    @include('admin.video-templates.partials.audio-settings', ['settings' => $videoTemplate->settings])
                                </div>

                                <!-- Video Settings Tab -->
                                <div class="tab-pane fade" id="video-settings" role="tabpanel">
                                    @include('admin.video-templates.partials.video-settings', ['settings' => $videoTemplate->settings])
                                </div>

                                <!-- Advanced Settings Tab -->
                                <div class="tab-pane fade" id="advanced-settings" role="tabpanel">
                                    @include('admin.video-templates.partials.advanced-settings', ['settings' => $videoTemplate->settings])
                                </div>

                                <!-- JSON Settings Tab -->
                                <div class="tab-pane fade" id="json-settings" role="tabpanel">
                                    <div class="form-group">
                                        <label for="settings">Cài đặt JSON <span class="text-danger">*</span></label>
                                        <textarea name="settings" id="settings" class="form-control" rows="15"
                                                  placeholder='{"platform": "tiktok", "media_type": "images", ...}' required>{{ old('settings', json_encode($videoTemplate->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                                        <small class="form-text text-muted">
                                            Cài đặt dưới dạng JSON. Thay đổi ở các tab khác sẽ tự động cập nhật JSON này.
                                        </small>
                                        @error('settings')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <button type="button" class="btn btn-outline-primary" onclick="updateJsonFromFormManual()">
                                            <i class="fas fa-sync mr-2"></i>Cập nhật JSON từ form
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary ml-2" onclick="updateFormFromJsonManual()">
                                            <i class="fas fa-download mr-2"></i>Cập nhật form từ JSON
                                        </button>
                                        <button type="button" class="btn btn-outline-info ml-2" onclick="validateJson()">
                                            <i class="fas fa-check mr-2"></i>Kiểm tra JSON
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Cập nhật Template
                            </button>
                            <a href="{{ route('admin.video-templates.show', $videoTemplate) }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin Template</h6>
                </div>
                <div class="card-body">
                    <div class="template-stats">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="stat-item">
                                    <h5 class="text-primary">{{ $videoTemplate->usage_count }}</h5>
                                    <small class="text-muted">Lượt sử dụng</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <h5 class="text-info">{{ count($videoTemplate->required_inputs ?? []) }}</h5>
                                    <small class="text-muted">Input bắt buộc</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="template-meta">
                        <small class="text-muted d-block">
                            <i class="fas fa-user mr-1"></i>
                            Tạo bởi: {{ $videoTemplate->creator->name ?? 'Unknown' }}
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-calendar mr-1"></i>
                            {{ $videoTemplate->created_at->format('d/m/Y H:i') }}
                        </small>
                        @if($videoTemplate->last_used_at)
                        <small class="text-muted d-block">
                            <i class="fas fa-clock mr-1"></i>
                            Dùng lần cuối: {{ $videoTemplate->last_used_at->diffForHumans() }}
                        </small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-lightbulb mr-2"></i>Lưu ý</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li>Tên trường input phải unique</li>
                        <li>Cài đặt JSON phải hợp lệ</li>
                        <li>Test kỹ sau khi chỉnh sửa</li>
                        <li>Thay đổi có thể ảnh hưởng đến video đã tạo</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-section {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 1rem;
}

.section-title {
    color: #5a5c69;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.input-item {
    background: #f8f9fc;
    border-radius: 0.35rem;
}

.remove-input {
    margin-top: 1.9rem;
}

.stat-item {
    padding: 0.5rem;
}

.template-stats {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
    margin: 1rem 0;
}

/* Settings Builder Styles */
.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 0.25rem 0.25rem;
    padding: 1rem;
}

.btn-group-toggle .btn {
    margin-bottom: 0.25rem;
}

.btn-group-toggle .btn.active {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
}

.card .card-header h6 {
    margin-bottom: 0;
    font-weight: 600;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

/* Color input styling */
input[type="color"] {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

/* JSON textarea styling */
#settings {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

/* Settings validation indicators */
.settings-valid {
    border-color: #28a745;
}

.settings-invalid {
    border-color: #dc3545;
}
</style>
@endpush

@push('scripts')
<script>
let requiredInputIndex = {{ count($videoTemplate->required_inputs ?? []) }};
let optionalInputIndex = {{ count($videoTemplate->optional_inputs ?? []) }};

// Add required input
document.getElementById('add-required-input').addEventListener('click', function() {
    const container = document.getElementById('required-inputs-container');
    const inputHtml = createInputItem('required_inputs', requiredInputIndex, true);
    container.insertAdjacentHTML('beforeend', inputHtml);
    requiredInputIndex++;
});

// Add optional input
document.getElementById('add-optional-input').addEventListener('click', function() {
    const container = document.getElementById('optional-inputs-container');
    const inputHtml = createInputItem('optional_inputs', optionalInputIndex, false);
    container.insertAdjacentHTML('beforeend', inputHtml);
    optionalInputIndex++;
});

// Remove input
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-input') || e.target.closest('.remove-input')) {
        e.target.closest('.input-item').remove();
    }
});

// Show/hide options field for select inputs
document.addEventListener('change', function(e) {
    if (e.target.matches('select[name*="[type]"]')) {
        const inputItem = e.target.closest('.input-item');
        const optionsField = inputItem.querySelector('.select-options');

        if (e.target.value === 'select') {
            optionsField.style.display = 'block';
        } else {
            optionsField.style.display = 'none';
        }
    }
});

function createInputItem(type, index, required) {
    const inputTypes = @json($inputTypes);
    let optionsHtml = '';
    for (const [key, label] of Object.entries(inputTypes)) {
        optionsHtml += `<option value="${key}">${label}</option>`;
    }
    
    return `
        <div class="input-item border p-3 mb-3">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tên trường</label>
                        <input type="text" name="${type}[${index}][name]" class="form-control" 
                               placeholder="vd: script_text" ${required ? 'required' : ''}>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Loại input</label>
                        <select name="${type}[${index}][type]" class="form-control" ${required ? 'required' : ''}>
                            ${optionsHtml}
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nhãn hiển thị</label>
                        <input type="text" name="${type}[${index}][label]" class="form-control" 
                               placeholder="vd: Nội dung kịch bản" ${required ? 'required' : ''}>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-block remove-input">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Placeholder/Hướng dẫn</label>
                <input type="text" name="${type}[${index}][placeholder]" class="form-control"
                       placeholder="Hướng dẫn cho người dùng...">
            </div>
            <div class="form-group select-options" style="display: none;">
                <label>Options (cho Select) - JSON format</label>
                <textarea name="${type}[${index}][options]" class="form-control" rows="3"
                          placeholder='{"value1": "Label 1", "value2": "Label 2"}'></textarea>
                <small class="text-muted">Chỉ cần điền khi loại input là "Lựa chọn"</small>
            </div>
        </div>
    `;
}

function previewThumbnail(input) {
    const preview = document.getElementById('thumbnail-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                <small class="d-block text-muted">Ảnh mới</small>
            `;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Utility Functions
function showSuccessToast(message) {
    // Create a subtle toast notification
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; opacity: 0.9;';
    toast.innerHTML = `
        <i class="fas fa-check-circle mr-2"></i>${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;

    document.body.appendChild(toast);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// Settings Builder Functions
function updateJsonFromForm(showAlert = false) {
    const settings = {};

    // Collect all form inputs from settings tabs
    const formInputs = document.querySelectorAll('#settingsTabContent input, #settingsTabContent select, #settingsTabContent textarea');

    formInputs.forEach(input => {
        if (input.name && input.name !== 'settings') {
            let value = input.value;

            // Handle different input types
            if (input.type === 'checkbox') {
                value = input.checked;
            } else if (input.type === 'radio') {
                if (input.checked) {
                    // Remove template_ prefix for radio buttons
                    const fieldName = input.name.replace('template_', '');
                    settings[fieldName] = value;
                }
                return; // Skip further processing for radio buttons
            } else if (input.type === 'number') {
                value = parseFloat(value) || 0;
            } else if (input.name.includes('[]')) {
                // Handle checkbox arrays
                const fieldName = input.name.replace('[]', '');
                if (!settings[fieldName]) settings[fieldName] = [];
                if (input.checked) {
                    settings[fieldName].push(value);
                }
                return;
            }

            // Handle JSON fields
            if (input.name === 'image_overlays' || input.name === 'section_transitions') {
                try {
                    value = JSON.parse(value || '[]');
                } catch (e) {
                    value = [];
                }
            }

            settings[input.name] = value;
        }
    });

    // Update JSON textarea
    document.getElementById('settings').value = JSON.stringify(settings, null, 2);

    // Show alert only when explicitly requested (manual updates)
    if (showAlert) {
        // Show a subtle success message instead of intrusive alert
        showSuccessToast('JSON đã được cập nhật từ form!');
    }
}

// Manual update function with alert for button clicks
function updateJsonFromFormManual() {
    updateJsonFromForm(true);
}

function updateFormFromJson(showAlert = false) {
    try {
        const settings = JSON.parse(document.getElementById('settings').value);

        // Update form inputs based on JSON
        Object.keys(settings).forEach(key => {
            const value = settings[key];

            // Handle different field types
            const input = document.querySelector(`[name="${key}"]`);
            const templateInput = document.querySelector(`[name="template_${key}"]`);

            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = !!value;
                } else if (input.type === 'radio') {
                    const radioInput = document.querySelector(`[name="${key}"][value="${value}"]`);
                    if (radioInput) radioInput.checked = true;
                } else {
                    if (typeof value === 'object') {
                        input.value = JSON.stringify(value);
                    } else {
                        input.value = value;
                    }
                }
            } else if (templateInput) {
                if (templateInput.type === 'radio') {
                    const radioInput = document.querySelector(`[name="template_${key}"][value="${value}"]`);
                    if (radioInput) {
                        radioInput.checked = true;
                        // Trigger change event to update button states
                        radioInput.dispatchEvent(new Event('change'));
                    }
                }
            }

            // Handle checkbox arrays
            if (Array.isArray(value)) {
                value.forEach(item => {
                    const checkbox = document.querySelector(`[name="${key}[]"][value="${item}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        });

        // Show alert only when explicitly requested (manual updates)
        if (showAlert) {
            showSuccessToast('Form đã được cập nhật từ JSON!');
        }
    } catch (e) {
        // Only show alert for actual errors
        console.error('JSON parsing error:', e.message);
        alert('JSON không hợp lệ: ' + e.message);
    }
}

// Manual update function with alert for button clicks
function updateFormFromJsonManual() {
    updateFormFromJson(true);
}

function validateJson() {
    try {
        const settings = JSON.parse(document.getElementById('settings').value);
        showSuccessToast('JSON hợp lệ! ✓');
        return true;
    } catch (e) {
        // Keep alert for errors as they need immediate attention
        alert('JSON không hợp lệ: ' + e.message);
        return false;
    }
}

// Auto-update JSON when form changes
document.addEventListener('change', function(e) {
    if (e.target.closest('#settingsTabContent') && e.target.name !== 'settings') {
        // Debounce the update
        clearTimeout(window.settingsUpdateTimeout);
        window.settingsUpdateTimeout = setTimeout(updateJsonFromForm, 1000);
    }
});

// Update button states for radio buttons
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio') {
        const parentGroup = e.target.closest('.btn-group-toggle');
        if (parentGroup) {
            // Remove active class from all labels in group
            parentGroup.querySelectorAll('label').forEach(label => {
                label.classList.remove('active');
            });
            // Add active class to current label
            e.target.closest('label').classList.add('active');
        }
    }
});

// Disable validation for hidden tab fields
function toggleFieldValidation() {
    const allTabPanes = document.querySelectorAll('.tab-pane');

    allTabPanes.forEach(pane => {
        const inputs = pane.querySelectorAll('input, select, textarea');
        const isActive = pane.classList.contains('active');

        inputs.forEach(input => {
            if (!isActive) {
                // Disable HTML5 validation for hidden fields
                input.setAttribute('data-original-required', input.required);
                input.setAttribute('data-original-min', input.min || '');
                input.setAttribute('data-original-max', input.max || '');
                input.removeAttribute('required');
                input.removeAttribute('min');
                input.removeAttribute('max');
            } else {
                // Restore validation for visible fields
                if (input.getAttribute('data-original-required') === 'true') {
                    input.setAttribute('required', 'required');
                }
                if (input.getAttribute('data-original-min')) {
                    input.setAttribute('min', input.getAttribute('data-original-min'));
                }
                if (input.getAttribute('data-original-max')) {
                    input.setAttribute('max', input.getAttribute('data-original-max'));
                }
            }
        });
    });
}

// Handle tab changes
document.addEventListener('shown.bs.tab', function(e) {
    toggleFieldValidation();
});

// Validate JSON before submit
document.getElementById('templateForm').addEventListener('submit', function(e) {
    const settingsInput = document.getElementById('settings');

    // Auto-update JSON from form before validation
    updateJsonFromForm();

    // Temporarily enable all fields for validation
    const allInputs = document.querySelectorAll('#settingsTabContent input, #settingsTabContent select, #settingsTabContent textarea');
    allInputs.forEach(input => {
        if (input.getAttribute('data-original-required') === 'true') {
            input.setAttribute('required', 'required');
        }
        if (input.getAttribute('data-original-min')) {
            input.setAttribute('min', input.getAttribute('data-original-min'));
        }
        if (input.getAttribute('data-original-max')) {
            input.setAttribute('max', input.getAttribute('data-original-max'));
        }
    });

    try {
        const settings = JSON.parse(settingsInput.value);

        // Basic validation
        if (!settings.platform) {
            e.preventDefault();
            alert('Vui lòng chọn nền tảng mặc định.');
            document.querySelector('#basic-tab').click();
            return;
        }

        if (!settings.media_type) {
            e.preventDefault();
            alert('Vui lòng chọn loại nội dung mặc định.');
            document.querySelector('#basic-tab').click();
            return;
        }

    } catch (error) {
        e.preventDefault();
        alert('Cài đặt JSON không hợp lệ. Vui lòng kiểm tra lại.');
        document.querySelector('#json-tab').click();
        settingsInput.focus();

        // Restore field validation state
        toggleFieldValidation();
    }
});

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update form from existing JSON
    updateFormFromJson();

    // Setup initial validation state
    toggleFieldValidation();

    // Handle Bootstrap 4 tab events
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        toggleFieldValidation();
    });
});
</script>
@endpush
