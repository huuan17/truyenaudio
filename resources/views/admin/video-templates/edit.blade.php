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

            <!-- Video Preview Card -->
            <div class="card mt-3" id="videoPreviewCard">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-play-circle mr-2"></i>Preview Video</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="generatePreviewBtn">
                        <i class="fas fa-eye mr-1"></i>Tạo Preview
                    </button>
                </div>
                <div class="card-body">
                    <div id="videoPreviewContainer">
                        <!-- Mockup Preview Frame - Centered Content -->
                        <div id="mockupPreview" class="mockup-frame" style="display: none;">
                            <!-- Preview Instructions - Moved up -->
                            <div class="preview-instructions">
                                <div class="text-center mb-3">
                                    <small class="text-success">
                                        <i class="fas fa-eye mr-1"></i>Live Preview - Thay đổi settings để xem layout
                                    </small>
                                </div>

                                <!-- Quick Resolution Test -->
                                <div class="text-center mb-3">
                                    <small class="text-muted d-block mb-1">Test nhanh tỉ lệ:</small>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="testResolution('1920x1080')" title="Landscape">16:9</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="testResolution('1080x1920')" title="Portrait">9:16</button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="testResolution('1080x1080')" title="Square">1:1</button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="testResolution('1280x960')" title="Standard">4:3</button>
                                    </div>
                                </div>

                                <!-- Simple Preview Control -->
                                <div class="text-center mb-3">
                                    <button type="button" class="btn btn-primary" onclick="showMockupVideo()">
                                        <i class="fas fa-play mr-2"></i>Xem Mockup Video
                                    </button>
                                </div>
                            </div>

                            <!-- Main Video Preview Content -->
                            <div class="mockup-content-wrapper">
                                <!-- Content Preview Label -->
                                <div class="content-preview-label">
                                    <i class="fas fa-video mr-1"></i>
                                    Video Preview Content
                                </div>

                                <div class="mockup-video">
                                <!-- Video Border Label -->
                                <div class="video-border-label">
                                    <i class="fas fa-play mr-1"></i>
                                    Video Frame
                                </div>

                                <!-- Corner Indicators -->
                                <div class="corner-indicators">
                                    <div class="corner-indicator corner-tl"></div>
                                    <div class="corner-indicator corner-tr"></div>
                                    <div class="corner-indicator corner-bl"></div>
                                    <div class="corner-indicator corner-br"></div>
                                </div>

                                <div class="mockup-content">
                                    <!-- Video Background -->
                                    <div class="video-background"></div>

                                    <!-- Image Placeholder -->
                                    <div class="image-placeholder">
                                        <span>IMAGE</span>
                                    </div>

                                    <!-- Logo Placeholder -->
                                    <div class="logo-placeholder">
                                        <span>LOGO</span>
                                    </div>

                                    <!-- Subtitle Placeholder -->
                                    <div class="subtitle-placeholder">
                                        <span>Subtitle Text</span>
                                    </div>
                                </div>

                                <!-- Resolution Display -->
                                <div class="mockup-resolution">
                                    <span id="resolutionDisplay">1920x1080</span>
                                </div>
                            </div>

                            <!-- Close mockup content wrapper -->
                            </div>

                            <!-- Scale Info - Outside wrapper -->
                            <div class="mockup-scale-info text-center mb-2">
                                <small class="text-muted" id="scaleInfo">Scale: 1:6 (Preview)</small>
                            </div>





                                <!-- Ratio Comparison Table -->
                                <div class="mt-2" id="ratioComparison" style="display: none;">
                                    <small class="text-muted d-block mb-1">So sánh tỉ lệ:</small>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" style="font-size: 10px;">
                                            <thead>
                                                <tr class="bg-light">
                                                    <th>Tỉ lệ</th>
                                                    <th>Resolution</th>
                                                    <th>Preview</th>
                                                    <th>Scale</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ratioTableBody">
                                                <!-- Will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleRatioComparison()">
                                        <i class="fas fa-eye-slash"></i> Ẩn bảng
                                    </button>
                                </div>

                                <!-- Show comparison button -->
                                <div class="text-center mt-1" id="showComparisonBtn">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleRatioComparison()">
                                        <i class="fas fa-table mr-1"></i>So sánh tỉ lệ
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Initial State -->
                        <div id="previewInitial" class="preview-initial-state">
                            <div class="initial-content">
                                <div class="initial-icon">
                                    <i class="fas fa-video"></i>
                                </div>
                                <h6 class="initial-title">Video Preview</h6>
                                <p class="initial-description">Click vào các trường settings để xem preview layout</p>
                                <button type="button" class="btn btn-primary btn-lg" onclick="showMockupPreview()">
                                    <i class="fas fa-eye mr-2"></i>Hiển thị Preview
                                </button>
                            </div>
                        </div>
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

/* Video Preview Container Styles */
#videoPreviewContainer {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 500px;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    border: 3px solid #007bff;
    position: relative;
    transition: all 0.3s ease;
    /* Will be controlled by JavaScript for sticky behavior */
    box-shadow:
        0 6px 20px rgba(0, 123, 255, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

/* Add content preview label for main container */
#videoPreviewContainer::before {
    content: '📺 Video Preview Area';
    position: absolute;
    top: -12px;
    left: 20px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    z-index: 10;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

/* Hover effects for main container */
#videoPreviewContainer:hover {
    border-color: #0056b3;
    transform: translateY(-2px);
    box-shadow:
        0 8px 25px rgba(0, 123, 255, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

#videoPreviewContainer:hover::before {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: scale(1.05);
}

/* Add subtle border animation for main container */
#videoPreviewContainer {
    position: relative;
    overflow: visible;
}

#videoPreviewContainer::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #007bff, #0056b3, #007bff);
    border-radius: 14px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
    background-size: 200% 200%;
    animation: borderFlow 3s linear infinite;
}

#videoPreviewContainer:hover::after {
    opacity: 0.3;
}

@keyframes borderFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mockup Preview Styles - Center the content */
.mockup-frame {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 4px solid #495057;
    border-radius: 16px;
    padding: 30px;
    background: #ffffff;
    position: relative;
    box-shadow:
        0 10px 30px rgba(0, 0, 0, 0.2),
        0 6px 15px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    max-width: 100%;
    width: auto;
}

.mockup-frame:hover {
    transform: translateY(-3px);
    box-shadow:
        0 15px 40px rgba(0, 0, 0, 0.25),
        0 8px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

/* Mockup Content Wrapper - Centers the video with clear border */
.mockup-content-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-bottom: 20px;
    text-align: center; /* Ensure text centering */
    /* Add clear border for content preview */
    border: 4px solid #007bff;
    border-radius: 16px;
    padding: 30px;
    background:
        linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%),
        linear-gradient(45deg, transparent 49%, rgba(0, 123, 255, 0.05) 50%, transparent 51%);
    box-shadow:
        0 8px 25px rgba(0, 123, 255, 0.2),
        inset 0 2px 0 rgba(255, 255, 255, 0.9),
        inset 0 0 0 1px rgba(0, 123, 255, 0.1);
    transition: all 0.3s ease;
}

.mockup-content-wrapper:hover {
    border-color: #0056b3;
    transform: translateY(-2px);
    box-shadow:
        0 12px 35px rgba(0, 123, 255, 0.25),
        inset 0 2px 0 rgba(255, 255, 255, 0.9),
        inset 0 0 0 1px rgba(0, 123, 255, 0.15);
}

/* Add animated border effect */
.mockup-content-wrapper::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #007bff, #0056b3, #007bff);
    border-radius: 18px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mockup-content-wrapper:hover::before {
    opacity: 0.3;
    animation: borderGlow 2s infinite;
}

@keyframes borderGlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Content Preview Label */
.content-preview-label {
    position: absolute;
    top: -12px;
    left: 20px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    z-index: 10;
    letter-spacing: 0.5px;
}

/* Video Border Label */
.video-border-label {
    position: absolute;
    top: -12px;
    right: 20px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    z-index: 15;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.mockup-video:hover .video-border-label {
    background: linear-gradient(135deg, #c82333, #bd2130);
    transform: scale(1.05);
}

/* Preview Instructions Styling */
.preview-instructions {
    background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
    border: 2px solid #007bff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow:
        0 4px 15px rgba(0, 123, 255, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
    position: relative;
    text-align: center; /* Ensure instructions are centered */
}

/* Ensure all mockup elements are centered */
.mockup-scale-info {
    text-align: center !important;
    width: 100%;
    display: block;
}

/* Force centering for all child elements */
.mockup-content-wrapper > * {
    margin-left: auto;
    margin-right: auto;
}

/* Image Size Suggestions Styling */
.image-suggestions {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 3px solid #ffc107;
    border-radius: 16px;
    padding: 20px;
    margin: 20px 0;
    box-shadow:
        0 6px 20px rgba(255, 193, 7, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
    position: relative;
    animation: slideInUp 0.3s ease;
}

.image-suggestions::before {
    content: '💡 Gợi ý thông minh';
    position: absolute;
    top: -12px;
    left: 20px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
    z-index: 10;
    letter-spacing: 0.5px;
}

.suggestions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(255, 193, 7, 0.3);
}

.suggestions-header h6 {
    color: #856404;
    margin: 0;
    font-weight: 600;
}

.suggestions-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
    margin-bottom: 15px;
}

.suggestion-item {
    background: rgba(255, 255, 255, 0.8);
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.suggestion-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
    border-color: #e0a800;
}

.suggestion-item.recommended {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-color: #28a745;
    position: relative;
}

.suggestion-item.recommended::after {
    content: 'Khuyến nghị';
    position: absolute;
    top: -8px;
    right: 8px;
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

.suggestion-size {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
}

.suggestion-desc {
    font-size: 13px;
    color: #6c757d;
    line-height: 1.4;
}

.suggestions-footer {
    text-align: center;
    padding-top: 10px;
    border-top: 2px solid rgba(255, 193, 7, 0.3);
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.preview-instructions::before {
    content: '💡 Hướng dẫn';
    position: absolute;
    top: -12px;
    left: 20px;
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
    z-index: 10;
    letter-spacing: 0.5px;
}



.mockup-video {
    position: relative;
    background: #000;
    border: 8px solid #dc3545;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin: 0 auto; /* Center horizontally */
    /* Default aspect ratio will be set by JavaScript */
    aspect-ratio: 16/9;
    box-shadow:
        inset 0 0 0 4px rgba(255, 255, 255, 0.15),
        0 10px 30px rgba(220, 53, 69, 0.3),
        0 6px 15px rgba(0, 0, 0, 0.25),
        0 0 0 2px rgba(255, 255, 255, 0.1);
    /* Add professional video monitor look */
    display: block; /* Ensure block display for margin auto */
}

.mockup-video::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        linear-gradient(45deg,
            rgba(255, 255, 255, 0.03) 0%,
            transparent 50%,
            rgba(255, 255, 255, 0.03) 100%),
        radial-gradient(circle at center,
            transparent 0%,
            rgba(0, 0, 0, 0.1) 100%);
    pointer-events: none;
    z-index: 1;
}

.mockup-video:hover {
    transform: scale(1.03);
    border-color: #495057;
    box-shadow:
        inset 0 0 0 3px rgba(255, 255, 255, 0.15),
        0 12px 35px rgba(0, 0, 0, 0.4),
        0 6px 18px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.1);
}

.mockup-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 20px;
    height: 100%;
    width: 100%;
    /* Add subtle grid pattern to show proportions */
    background-image:
        linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 20px 20px;
}

/* Simple Static Mockup Elements */
.video-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 8px;
}

.image-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 120px;
    height: 80px;
    background: #3498db;
    border: 2px solid #2980b9;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
}

.image-placeholder span {
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.logo-placeholder {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 50px;
    height: 25px;
    background: #e74c3c;
    border: 1px solid #c0392b;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
}

.logo-placeholder span {
    color: white;
    font-size: 8px;
    font-weight: bold;
}

.subtitle-placeholder {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    z-index: 4;
}

.subtitle-placeholder span {
    color: white;
}





.mockup-logo {
    position: absolute;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    transition: all 0.3s ease;
    z-index: 10;
}

.mockup-subtitle {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    text-align: center;
    transition: all 0.3s ease;
    max-width: 80%;
    word-wrap: break-word;
}

.mockup-resolution {
    position: absolute;
    top: 8px;
    left: 8px;
    background: linear-gradient(135deg, rgba(0, 123, 255, 0.95), rgba(0, 86, 179, 0.95));
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: bold;
    line-height: 1.3;
    text-align: center;
    min-width: 70px;
    box-shadow:
        0 2px 8px rgba(0, 123, 255, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    z-index: 10;
}

/* Responsive mockup sizes with mathematically accurate scaling */
.mockup-16-9 {
    width: 320px;
    height: 180px; /* 320 * 9/16 = 180 */
    border-color: #28a745; /* Green for landscape */
}
.mockup-9-16 {
    width: 160px;  /* Narrower for vertical */
    height: 284px; /* 160 * 16/9 = 284 */
    border-color: #dc3545; /* Red for portrait */
}
.mockup-1-1 {
    width: 240px;
    height: 240px; /* Perfect square */
    border-color: #ffc107; /* Yellow for square */
}
.mockup-4-3 {
    width: 280px;
    height: 210px; /* 280 * 3/4 = 210 */
    border-color: #17a2b8; /* Cyan for 4:3 */
}
.mockup-custom {
    /* Custom dimensions will be set via inline style */
    border-color: #6f42c1; /* Purple for custom */
}

/* Border thickness variations for different ratios */
.mockup-9-16 {
    border-width: 4px; /* Thicker border for portrait to emphasize */
}
.mockup-1-1 {
    border-width: 3px;
}
.mockup-16-9, .mockup-4-3, .mockup-custom {
    border-width: 3px;
}

/* Logo positions */
.logo-top-left { top: 10px; left: 10px; }
.logo-top-right { top: 10px; right: 10px; }
.logo-bottom-left { bottom: 10px; left: 10px; }
.logo-bottom-right { bottom: 10px; right: 10px; }

/* Subtitle positions */
.subtitle-top { top: 20px; }
.subtitle-center { top: 50%; transform: translate(-50%, -50%); }
.subtitle-bottom { bottom: 20px; }

/* Focus indicators */
.setting-focused {
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    border-color: #007bff;
}

/* Animations */
@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-10px); }
    20% { opacity: 1; transform: translateY(0); }
    80% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-10px); }
}

/* Smooth transitions for mockup changes */
.mockup-video {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mockup-video:hover {
    transform: scale(1.02);
}

/* Resolution indicator animations */
.mockup-resolution {
    transition: all 0.2s ease;
}

.mockup-frame:hover .mockup-resolution {
    background: rgba(0, 123, 255, 1);
    transform: scale(1.05);
}

/* Corner Indicators for Professional Look */
.corner-indicators {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 5;
}

.corner-indicator {
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.6);
    transition: all 0.3s ease;
}

.corner-tl {
    top: 8px;
    left: 8px;
    border-right: none;
    border-bottom: none;
    border-top-left-radius: 4px;
}

.corner-tr {
    top: 8px;
    right: 8px;
    border-left: none;
    border-bottom: none;
    border-top-right-radius: 4px;
}

.corner-bl {
    bottom: 8px;
    left: 8px;
    border-right: none;
    border-top: none;
    border-bottom-left-radius: 4px;
}

.corner-br {
    bottom: 8px;
    right: 8px;
    border-left: none;
    border-top: none;
    border-bottom-right-radius: 4px;
}

.mockup-video:hover .corner-indicator {
    border-color: rgba(255, 255, 255, 0.9);
    width: 25px;
    height: 25px;
}

/* Initial State Styling */
.preview-initial-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    width: 100%;
}

.initial-content {
    text-align: center;
    padding: 40px;
    border: 3px solid #007bff;
    border-radius: 16px;
    background:
        linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%),
        linear-gradient(45deg, transparent 49%, rgba(0, 123, 255, 0.05) 50%, transparent 51%);
    transition: all 0.3s ease;
    max-width: 400px;
    box-shadow:
        0 6px 20px rgba(0, 123, 255, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
    position: relative;
}

.initial-content::before {
    content: '🎬 Click để bắt đầu';
    position: absolute;
    top: -12px;
    left: 20px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    z-index: 10;
    letter-spacing: 0.5px;
}

.initial-content:hover {
    border-color: #0056b3;
    background:
        linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%),
        linear-gradient(45deg, transparent 49%, rgba(0, 123, 255, 0.08) 50%, transparent 51%);
    transform: translateY(-3px);
    box-shadow:
        0 8px 25px rgba(0, 123, 255, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.initial-icon {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.initial-content:hover .initial-icon {
    color: #007bff;
    transform: scale(1.1);
}

.initial-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.initial-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

/* Sticky Scroll Behavior for entire Card */
#videoPreviewCard.preview-sticky {
    box-shadow:
        0 20px 50px rgba(0, 123, 255, 0.3),
        0 10px 25px rgba(0, 0, 0, 0.2);
    border: 3px solid #007bff;
    background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
    transform: scale(1.02);
    transition: all 0.3s ease;
}

#videoPreviewCard.preview-sticky .card-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-bottom: 2px solid #0056b3;
}

#videoPreviewCard.preview-sticky .card-header h6 {
    color: white;
}

#videoPreviewCard.preview-sticky .card-header .btn {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

#videoPreviewCard.preview-sticky .card-header .btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Enhanced container styling when card is sticky */
#videoPreviewCard.preview-sticky #videoPreviewContainer {
    border-color: #0056b3;
    border-width: 4px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
}

#videoPreviewCard.preview-sticky #videoPreviewContainer::before {
    content: '📌 Video Preview Area (Sticky)';
    background: linear-gradient(135deg, #28a745, #20c997);
    animation: stickyPulse 2s infinite;
}

#videoPreviewCard.preview-sticky #videoPreviewContainer::after {
    opacity: 0.5;
    animation: borderFlow 2s linear infinite;
}

/* Placeholder for sticky positioning */
.preview-placeholder {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 14px;
    position: relative;
}

.preview-placeholder::before {
    content: '📌 Video Preview Card đang follow scroll ở trên';
    font-weight: 500;
}

/* Sticky Indicator */
.sticky-indicator {
    position: absolute;
    top: 10px;
    right: 15px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    z-index: 1000;
    animation: stickyPulse 2s infinite;
}

@keyframes stickyPulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
    100% { opacity: 1; transform: scale(1); }
}

/* Enhanced sticky container styling */
#videoPreviewContainer.preview-sticky .mockup-frame {
    border-color: #007bff;
    box-shadow:
        0 12px 35px rgba(0, 123, 255, 0.2),
        0 6px 15px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

#videoPreviewContainer.preview-sticky .mockup-video {
    border-color: #007bff;
    box-shadow:
        inset 0 0 0 3px rgba(0, 123, 255, 0.1),
        0 10px 30px rgba(0, 123, 255, 0.2),
        0 6px 15px rgba(0, 0, 0, 0.15);
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
<!-- Interact.js for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
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

    // Setup mockup preview with focus events
    setupMockupPreview();

    // Setup sticky scroll behavior with delay
    setTimeout(() => {
        setupStickyScroll();
    }, 500);

    // Setup Settings Builder listeners with longer delay
    setTimeout(() => {
        setupSettingsBuilderListeners();
        setupDirectMediaListeners();
        console.log('Settings Builder listeners setup completed');

        // No complex setup needed
        console.log('Simple mockup ready');
    }, 500);
});

// Mockup Preview Functions
function setupMockupPreview() {
    // Watch for focus events on settings inputs
    const settingsInputs = [
        // Resolution
        'select[name*="resolution"]',
        'input[name*="resolution"]',

        // Subtitle settings
        'input[name*="subtitle_size"]',
        'input[name*="subtitle_color"]',
        'select[name*="subtitle_position"]',
        'select[name*="subtitle_font"]',

        // Logo settings
        'input[name*="enable_logo"]',
        'select[name*="logo_position"]',
        'input[name*="logo_size"]',

        // Duration settings
        'input[name*="custom_duration"]',
        'input[name*="slide_duration"]'
    ];

    // Add focus/blur events to all settings inputs
    settingsInputs.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.addEventListener('focus', function() {
                showMockupPreview();
                highlightSettingGroup(this);
                updateMockupPreview();
            });

            element.addEventListener('blur', function() {
                removeHighlight(this);
            });

            element.addEventListener('change', function() {
                updateMockupPreview();
            });

            element.addEventListener('input', function() {
                updateMockupPreview();
            });
        });
    });

    // Add event listeners for Settings Builder inputs
    setupSettingsBuilderListeners();
}

// Setup event listeners for Settings Builder tabs
function setupSettingsBuilderListeners() {
    console.log('Setting up Settings Builder listeners...');

    // Media settings inputs with multiple selectors as fallback
    const mediaSelectors = [
        ['#image_position', 'select[name="image_position"]'],
        ['#image_scale', 'input[name="image_scale"]', 'input[type="range"][name*="scale"]'],
        ['#image_opacity', 'input[name="image_opacity"]', 'input[type="range"][name*="opacity"]'],
        ['#image_effect', 'select[name="image_effect"]'],
        ['input[name="image_rotation"]', 'input[type="range"][name*="rotation"]'],
        ['input[name="enable_image_effects"]', 'input[type="checkbox"][name*="effects"]'],
        ['input[name="image_blur"]', 'input[type="range"][name*="blur"]'],
        ['input[name="image_brightness"]', 'input[type="range"][name*="brightness"]'],
        ['input[name="image_contrast"]', 'input[type="range"][name*="contrast"]'],
        ['input[name="image_saturation"]', 'input[type="range"][name*="saturation"]'],
        ['input[name="image_sepia"]', 'input[type="range"][name*="sepia"]'],
        ['input[name="image_grayscale"]', 'input[type="range"][name*="grayscale"]']
    ];

    // Add real-time listeners to media inputs
    mediaSelectors.forEach(selectors => {
        let foundElement = null;

        // Try each selector until we find an element
        for (const selector of selectors) {
            const elements = document.querySelectorAll(selector);
            if (elements.length > 0) {
                foundElement = elements[0];
                console.log(`Found element with selector: ${selector}`, foundElement);
                break;
            }
        }

        if (foundElement) {
            // Show preview when focusing on media settings
            foundElement.addEventListener('focus', function() {
                console.log('Media input focused:', this.name || this.id);
                showMockupPreview();
                updateMockupPreview();
            });

            // Real-time updates
            foundElement.addEventListener('input', function() {
                console.log('Media input changed:', this.name || this.id, this.value);
                updateMockupPreview();
            });

            foundElement.addEventListener('change', function() {
                console.log('Media input change event:', this.name || this.id, this.value);
                updateMockupPreview();
            });

            // Special handling for checkboxes
            if (foundElement.type === 'checkbox') {
                foundElement.addEventListener('click', function() {
                    console.log('Checkbox clicked:', this.name || this.id, this.checked);
                    setTimeout(() => {
                        updateMockupPreview();
                    }, 50);
                });
            }
        } else {
            console.log('No elements found for selectors:', selectors);
        }
    });

    // Watch for changes in the main settings textarea
    const settingsTextarea = document.getElementById('settings');
    if (settingsTextarea) {
        let settingsTimeout;
        settingsTextarea.addEventListener('input', function() {
            clearTimeout(settingsTimeout);
            settingsTimeout = setTimeout(() => {
                updateMockupPreview();
            }, 500); // Debounce for JSON input
        });
    }
}

// Setup direct event listeners for media inputs
function setupDirectMediaListeners() {
    console.log('Setting up direct media listeners...');

    // Wait for DOM to be fully loaded
    setTimeout(() => {
        // Image Position
        const imagePosition = document.getElementById('image_position');
        if (imagePosition) {
            console.log('Found image_position element');
            imagePosition.addEventListener('change', function() {
                console.log('Image position changed to:', this.value);
                applySettingsToMockup();
            });
        } else {
            console.log('image_position element not found');
        }

        // Image Scale
        const imageScale = document.getElementById('image_scale');
        if (imageScale) {
            console.log('Found image_scale element');
            imageScale.addEventListener('input', function() {
                console.log('Image scale changed to:', this.value);
                applySettingsToMockup();
            });
        } else {
            console.log('image_scale element not found');
        }

        // Image Opacity
        const imageOpacity = document.getElementById('image_opacity');
        if (imageOpacity) {
            console.log('Found image_opacity element');
            imageOpacity.addEventListener('input', function() {
                console.log('Image opacity changed to:', this.value);
                applySettingsToMockup();
            });
        } else {
            console.log('image_opacity element not found');
        }

        // Image Effect
        const imageEffect = document.getElementById('image_effect');
        if (imageEffect) {
            console.log('Found image_effect element');
            imageEffect.addEventListener('change', function() {
                console.log('Image effect changed to:', this.value);
                applySettingsToMockup();
            });
        } else {
            console.log('image_effect element not found');
        }

        // Logo settings
        const enableLogo = document.querySelector('input[name*="enable_logo"]');
        if (enableLogo) {
            enableLogo.addEventListener('change', function() {
                console.log('Logo enabled:', this.checked);
                applySettingsToMockup();
            });
        }

        // Subtitle settings
        const subtitleSize = document.querySelector('input[name*="subtitle_size"]');
        if (subtitleSize) {
            subtitleSize.addEventListener('input', function() {
                console.log('Subtitle size changed:', this.value);
                applySettingsToMockup();
            });
        }

        const subtitleColor = document.querySelector('input[name*="subtitle_color"]');
        if (subtitleColor) {
            subtitleColor.addEventListener('input', function() {
                console.log('Subtitle color changed:', this.value);
                applySettingsToMockup();
            });
        }

    }, 1000);
}

// Apply settings to mockup preview
function applySettingsToMockup() {
    console.log('Applying settings to mockup...');

    // Ensure preview is visible
    showMockupPreview();

    // Get current settings
    const settings = getCurrentSettings();
    console.log('Current settings:', settings);

    // Apply image settings
    applyImageSettings(settings);

    // Apply logo settings
    applyLogoSettings(settings);

    // Apply subtitle settings
    applySubtitleSettings(settings);
}

// Apply image settings
function applyImageSettings(settings) {
    const mockupImage = document.getElementById('mockupImage');
    const img = mockupImage?.querySelector('img');

    if (!mockupImage || !img) {
        console.log('Mockup image not found');
        return;
    }

    // Image position
    const position = settings.image_position || 'center';
    console.log('Applying image position:', position);

    // Reset positioning
    mockupImage.style.top = '';
    mockupImage.style.left = '';
    mockupImage.style.right = '';
    mockupImage.style.bottom = '';
    mockupImage.style.transform = '';

    switch (position) {
        case 'top':
            mockupImage.style.top = '10%';
            mockupImage.style.left = '50%';
            mockupImage.style.transform = 'translateX(-50%)';
            break;
        case 'bottom':
            mockupImage.style.bottom = '10%';
            mockupImage.style.left = '50%';
            mockupImage.style.transform = 'translateX(-50%)';
            break;
        case 'left':
            mockupImage.style.top = '50%';
            mockupImage.style.left = '10%';
            mockupImage.style.transform = 'translateY(-50%)';
            break;
        case 'right':
            mockupImage.style.top = '50%';
            mockupImage.style.right = '10%';
            mockupImage.style.transform = 'translateY(-50%)';
            break;
        case 'center':
        default:
            mockupImage.style.top = '50%';
            mockupImage.style.left = '50%';
            mockupImage.style.transform = 'translate(-50%, -50%)';
            break;
    }

    // Image scale
    const scale = (settings.image_scale || 100) / 100;
    const baseWidth = 150;
    img.style.width = (baseWidth * scale) + 'px';
    console.log('Applied image scale:', scale);

    // Image opacity
    const opacity = (settings.image_opacity || 100) / 100;
    mockupImage.style.opacity = opacity;
    console.log('Applied image opacity:', opacity);

    // Image effect
    const effect = settings.image_effect || 'none';
    applyImageEffect(img, effect);
    console.log('Applied image effect:', effect);
}

// Apply image effect
function applyImageEffect(img, effect) {
    let filter = '';

    switch (effect) {
        case 'blur':
            filter = 'blur(3px)';
            break;
        case 'sepia':
            filter = 'sepia(100%)';
            break;
        case 'grayscale':
            filter = 'grayscale(100%)';
            break;
        case 'vintage':
            filter = 'sepia(50%) contrast(1.2) brightness(1.1)';
            break;
        case 'bright':
            filter = 'brightness(1.3)';
            break;
        case 'dark':
            filter = 'brightness(0.7)';
            break;
        case 'none':
        default:
            filter = 'none';
            break;
    }

    img.style.filter = filter;
}

// Apply logo settings
function applyLogoSettings(settings) {
    const mockupLogo = document.getElementById('mockupLogo');

    if (!mockupLogo) {
        console.log('Mockup logo not found');
        return;
    }

    const enableLogo = settings.enable_logo || false;
    console.log('Logo enabled:', enableLogo);

    if (enableLogo) {
        mockupLogo.style.display = 'block';

        // Logo position
        const position = settings.logo_position || 'top-right';
        console.log('Logo position:', position);

        // Reset positioning
        mockupLogo.style.top = '';
        mockupLogo.style.left = '';
        mockupLogo.style.right = '';
        mockupLogo.style.bottom = '';

        switch (position) {
            case 'top-left':
                mockupLogo.style.top = '10px';
                mockupLogo.style.left = '10px';
                break;
            case 'top-right':
            default:
                mockupLogo.style.top = '10px';
                mockupLogo.style.right = '10px';
                break;
            case 'bottom-left':
                mockupLogo.style.bottom = '10px';
                mockupLogo.style.left = '10px';
                break;
            case 'bottom-right':
                mockupLogo.style.bottom = '10px';
                mockupLogo.style.right = '10px';
                break;
        }

        // Logo size
        const size = settings.logo_size || 100;
        const fontSize = Math.max(10, Math.min(20, size * 0.15));
        mockupLogo.style.fontSize = fontSize + 'px';
        console.log('Logo size applied:', fontSize);

    } else {
        mockupLogo.style.display = 'none';
    }
}

// Apply subtitle settings
function applySubtitleSettings(settings) {
    const mockupSubtitle = document.getElementById('mockupSubtitle');
    const subtitleContent = mockupSubtitle?.querySelector('.subtitle-content');

    if (!mockupSubtitle || !subtitleContent) {
        console.log('Mockup subtitle not found');
        return;
    }

    // Subtitle size
    const size = settings.subtitle_size || 16;
    subtitleContent.style.fontSize = size + 'px';
    console.log('Subtitle size applied:', size);

    // Subtitle color
    const color = settings.subtitle_color || '#ffffff';
    subtitleContent.style.color = color;
    console.log('Subtitle color applied:', color);

    // Subtitle position
    const position = settings.subtitle_position || 'bottom';
    console.log('Subtitle position:', position);

    // Reset positioning
    mockupSubtitle.style.top = '';
    mockupSubtitle.style.bottom = '';
    mockupSubtitle.style.left = '50%';
    mockupSubtitle.style.transform = 'translateX(-50%)';

    switch (position) {
        case 'top':
            mockupSubtitle.style.top = '20px';
            break;
        case 'middle':
            mockupSubtitle.style.top = '50%';
            mockupSubtitle.style.transform = 'translate(-50%, -50%)';
            break;
        case 'bottom':
        default:
            mockupSubtitle.style.bottom = '20px';
            break;
    }
}

// Setup sticky scroll behavior for video preview
function setupStickyScroll() {
    // Target the entire Video Preview Card by ID
    const previewCard = document.getElementById('videoPreviewCard');
    if (!previewCard) {
        console.log('Video Preview Card not found');
        return;
    }

    console.log('Setting up sticky scroll for Video Preview Card');

    let isSticky = false;
    let originalTop = null;
    let originalWidth = null;
    let originalLeft = null;
    let placeholder = null;

    function handleScroll() {
        const cardRect = previewCard.getBoundingClientRect();
        const scrollY = window.scrollY;

        // Get original position if not set
        if (originalTop === null) {
            const rect = previewCard.getBoundingClientRect();
            originalTop = rect.top + scrollY;
            originalWidth = rect.width;
            originalLeft = rect.left;
            console.log('Original position set:', { originalTop, originalWidth, originalLeft });
        }

        // Check if we should enable sticky
        const shouldBeSticky = scrollY > originalTop - 20;

        if (shouldBeSticky && !isSticky) {
            console.log('Enabling sticky mode');
            enableStickyMode();
        } else if (!shouldBeSticky && isSticky) {
            console.log('Disabling sticky mode');
            disableStickyMode();
        }
    }

    function enableStickyMode() {
        if (isSticky) return;

        try {
            isSticky = true;
            console.log('Enabling sticky mode for card');

            // Create placeholder to maintain layout
            placeholder = document.createElement('div');
            placeholder.style.height = previewCard.offsetHeight + 'px';
            placeholder.style.width = '100%';
            placeholder.className = 'preview-placeholder';

            // Insert placeholder before card
            previewCard.parentNode.insertBefore(placeholder, previewCard);

            // Make entire card fixed
            previewCard.style.position = 'fixed';
            previewCard.style.top = '20px';
            previewCard.style.left = originalLeft + 'px';
            previewCard.style.width = originalWidth + 'px';
            previewCard.style.zIndex = '1000';

            // Add sticky class for enhanced styling
            previewCard.classList.add('preview-sticky');

            // Add visual indicator
            showStickyIndicator();

            console.log('Sticky mode enabled successfully');
        } catch (error) {
            console.error('Error enabling sticky mode:', error);
            isSticky = false;
        }
    }

    function disableStickyMode() {
        if (!isSticky) return;

        isSticky = false;

        // Remove placeholder
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
            placeholder = null;
        }

        // Reset card positioning
        previewCard.style.position = 'relative';
        previewCard.style.top = 'auto';
        previewCard.style.left = 'auto';
        previewCard.style.width = 'auto';
        previewCard.style.zIndex = 'auto';

        // Remove sticky class
        previewCard.classList.remove('preview-sticky');

        // Remove visual indicator
        hideStickyIndicator();
    }

    // Throttled scroll handler for performance
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(handleScroll, 16); // ~60fps
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (isSticky) {
            // Update width and left position for the entire card
            const parentRect = previewCard.parentNode.getBoundingClientRect();
            previewCard.style.left = parentRect.left + 'px';
            previewCard.style.width = parentRect.width + 'px';
        }
    });

    // Initial check
    setTimeout(handleScroll, 100); // Delay to ensure layout is ready
}

// Show mockup preview
function showMockupPreview() {
    const mockupPreview = document.getElementById('mockupPreview');
    const previewInitial = document.getElementById('previewInitial');

    if (mockupPreview && previewInitial) {
        mockupPreview.style.display = 'block';
        previewInitial.style.display = 'none';

        // Apply current settings to mockup
        setTimeout(() => {
            applySettingsToMockup();
        }, 100);
    }
}

// Update mockup preview based on current settings
function updateMockupPreview() {
    const settings = getCurrentSettings();

    // Update resolution and aspect ratio
    updateMockupResolution(settings);

    // Update subtitle
    updateMockupSubtitle(settings);

    // Update logo
    updateMockupLogo(settings);

    // Update image positioning and effects
    updateMockupImage(settings);
}

// Platform-based image size recommendations
const platformImageSuggestions = {
    // YouTube Landscape (16:9)
    '1920x1080': {
        platform: 'YouTube',
        type: 'Landscape',
        ratio: '16:9',
        suggestions: [
            { size: '1920x1080', desc: 'Full HD - Chất lượng tốt nhất', recommended: true },
            { size: '1280x720', desc: 'HD - Chất lượng tốt, file nhỏ' },
            { size: '1600x900', desc: 'HD+ - Cân bằng chất lượng/dung lượng' }
        ]
    },
    '1280x720': {
        platform: 'YouTube',
        type: 'HD',
        ratio: '16:9',
        suggestions: [
            { size: '1280x720', desc: 'HD - Phù hợp với video', recommended: true },
            { size: '1920x1080', desc: 'Full HD - Chất lượng cao hơn' },
            { size: '960x540', desc: 'SD - File nhỏ, tải nhanh' }
        ]
    },

    // TikTok/Instagram Reels Portrait (9:16)
    '1080x1920': {
        platform: 'TikTok/Instagram',
        type: 'Portrait',
        ratio: '9:16',
        suggestions: [
            { size: '1080x1920', desc: 'Full HD Portrait - Chất lượng tốt nhất', recommended: true },
            { size: '720x1280', desc: 'HD Portrait - Chất lượng tốt' },
            { size: '540x960', desc: 'SD Portrait - File nhỏ' }
        ]
    },
    '720x1280': {
        platform: 'TikTok/Instagram',
        type: 'Portrait HD',
        ratio: '9:16',
        suggestions: [
            { size: '720x1280', desc: 'HD Portrait - Phù hợp với video', recommended: true },
            { size: '1080x1920', desc: 'Full HD Portrait - Chất lượng cao hơn' },
            { size: '540x960', desc: 'SD Portrait - File nhỏ' }
        ]
    },

    // Instagram Square (1:1)
    '1080x1080': {
        platform: 'Instagram',
        type: 'Square',
        ratio: '1:1',
        suggestions: [
            { size: '1080x1080', desc: 'Instagram Square - Chất lượng tốt nhất', recommended: true },
            { size: '720x720', desc: 'Square HD - Chất lượng tốt' },
            { size: '600x600', desc: 'Square Medium - Cân bằng' }
        ]
    },
    '720x720': {
        platform: 'Instagram',
        type: 'Square HD',
        ratio: '1:1',
        suggestions: [
            { size: '720x720', desc: 'Square HD - Phù hợp với video', recommended: true },
            { size: '1080x1080', desc: 'Instagram Square - Chất lượng cao hơn' },
            { size: '600x600', desc: 'Square Medium - File nhỏ' }
        ]
    },

    // Facebook/Traditional (4:3)
    '1280x960': {
        platform: 'Facebook',
        type: 'Traditional',
        ratio: '4:3',
        suggestions: [
            { size: '1280x960', desc: 'Facebook 4:3 - Chất lượng tốt nhất', recommended: true },
            { size: '1024x768', desc: 'Traditional HD - Chất lượng tốt' },
            { size: '800x600', desc: 'Traditional Medium - File nhỏ' }
        ]
    },
    '1024x768': {
        platform: 'Facebook',
        type: 'Traditional HD',
        ratio: '4:3',
        suggestions: [
            { size: '1024x768', desc: 'Traditional HD - Phù hợp với video', recommended: true },
            { size: '1280x960', desc: 'Facebook 4:3 - Chất lượng cao hơn' },
            { size: '800x600', desc: 'Traditional Medium - File nhỏ' }
        ]
    }
};

// Get current settings from form
function getCurrentSettings() {
    const settingsInput = document.getElementById('settings');
    let settings = {};

    try {
        if (settingsInput && settingsInput.value) {
            settings = JSON.parse(settingsInput.value);
            console.log('Loaded template settings:', settings);
        }
    } catch (e) {
        console.warn('Could not parse settings JSON, using defaults');
    }

    // Get values directly from form inputs - prioritize form over template settings
    const resolution = getResolutionFromForm() || settings.resolution || '1920x1080';
    const subtitleSize = document.querySelector('input[name*="subtitle_size"]')?.value || settings.subtitle_size || 16;
    const subtitleColor = document.querySelector('input[name*="subtitle_color"]')?.value || settings.subtitle_color || '#ffffff';
    const subtitlePosition = document.querySelector('select[name*="subtitle_position"]')?.value || settings.subtitle_position || 'bottom';
    const enableLogo = document.querySelector('input[name*="enable_logo"]')?.checked ?? settings.enable_logo ?? false;
    const logoPosition = document.querySelector('select[name*="logo_position"]')?.value || settings.logo_position || 'top-right';
    const logoSize = document.querySelector('input[name*="logo_size"]')?.value || settings.logo_size || 100;

    // Get media settings - prioritize form inputs over template settings for real-time editing
    const imagePosition = document.querySelector('#image_position')?.value || settings.image_position || 'center';
    const imageScale = document.querySelector('#image_scale')?.value || settings.image_scale || 100;
    const imageOpacity = document.querySelector('#image_opacity')?.value || settings.image_opacity || 100;
    const imageEffect = document.querySelector('#image_effect')?.value || settings.image_effect || 'none';
    const imageRotation = document.querySelector('input[name="image_rotation"]')?.value || settings.image_rotation || 0;
    const enableImageEffects = document.querySelector('input[name="enable_image_effects"]')?.checked || settings.enable_image_effects || false;
    const imageBlur = document.querySelector('input[name="image_blur"]')?.value || settings.image_blur || 0;
    const imageBrightness = document.querySelector('input[name="image_brightness"]')?.value || settings.image_brightness || 100;
    const imageContrast = document.querySelector('input[name="image_contrast"]')?.value || settings.image_contrast || 100;
    const imageSaturation = document.querySelector('input[name="image_saturation"]')?.value || settings.image_saturation || 100;
    const imageSepia = document.querySelector('input[name="image_sepia"]')?.value || settings.image_sepia || 0;
    const imageGrayscale = document.querySelector('input[name="image_grayscale"]')?.value || settings.image_grayscale || 0;

    return {
        resolution,
        subtitle_size: subtitleSize,
        subtitle_color: subtitleColor,
        subtitle_position: subtitlePosition,
        enable_logo: enableLogo,
        logo_position: logoPosition,
        logo_size: logoSize,
        // Media settings
        image_position: imagePosition,
        image_scale: imageScale,
        image_opacity: imageOpacity,
        image_effect: imageEffect,
        image_rotation: imageRotation,
        enable_image_effects: enableImageEffects,
        image_blur: imageBlur,
        image_brightness: imageBrightness,
        image_contrast: imageContrast,
        image_saturation: imageSaturation,
        image_sepia: imageSepia,
        image_grayscale: imageGrayscale,
        ...settings
    };
}

// Get resolution from various form inputs
function getResolutionFromForm() {
    // Try different possible input names/selectors
    const selectors = [
        'select[name*="resolution"]',
        'input[name*="resolution"]',
        'select[name*="video_resolution"]',
        'input[name*="video_resolution"]',
        '#resolution',
        '#video_resolution'
    ];

    for (const selector of selectors) {
        const element = document.querySelector(selector);
        if (element && element.value) {
            return element.value;
        }
    }

    // Fallback: try to get from settings JSON
    const settingsInput = document.getElementById('settings');
    if (settingsInput && settingsInput.value) {
        try {
            const settings = JSON.parse(settingsInput.value);
            if (settings.resolution) {
                return settings.resolution;
            }
        } catch (e) {
            // Ignore JSON parse errors
        }
    }

    return null;
}

// Update mockup resolution and aspect ratio
function updateMockupResolution(settings) {
    const mockupVideo = document.querySelector('.mockup-video');
    const resolutionDisplay = document.getElementById('resolutionDisplay');

    if (!mockupVideo || !resolutionDisplay) return;

    const resolution = settings.resolution || '1920x1080';
    const [width, height] = resolution.split('x').map(Number);
    const aspectRatio = width / height;

    // Remove existing aspect ratio classes and custom styles
    mockupVideo.classList.remove('mockup-16-9', 'mockup-9-16', 'mockup-1-1', 'mockup-4-3', 'mockup-custom');
    mockupVideo.style.width = '';
    mockupVideo.style.height = '';
    mockupVideo.style.aspectRatio = '';

    // Calculate precise mockup dimensions
    const baseWidth = 280; // Base width for calculations
    let mockupWidth, mockupHeight;
    let ratioClass = '';
    let ratioLabel = '';
    let borderColor = '';

    if (Math.abs(aspectRatio - 16/9) < 0.05) {
        // 16:9 Landscape
        mockupWidth = 320;
        mockupHeight = Math.round(mockupWidth * 9 / 16); // 180
        ratioClass = 'mockup-16-9';
        ratioLabel = 'Landscape (16:9)';
        borderColor = '#28a745';
    } else if (Math.abs(aspectRatio - 9/16) < 0.05) {
        // 9:16 Portrait
        mockupWidth = 160;
        mockupHeight = Math.round(mockupWidth * 16 / 9); // 284
        ratioClass = 'mockup-9-16';
        ratioLabel = 'Portrait (9:16)';
        borderColor = '#dc3545';
    } else if (Math.abs(aspectRatio - 1) < 0.05) {
        // 1:1 Square
        mockupWidth = 240;
        mockupHeight = 240;
        ratioClass = 'mockup-1-1';
        ratioLabel = 'Square (1:1)';
        borderColor = '#ffc107';
    } else if (Math.abs(aspectRatio - 4/3) < 0.05) {
        // 4:3 Standard
        mockupWidth = 280;
        mockupHeight = Math.round(mockupWidth * 3 / 4); // 210
        ratioClass = 'mockup-4-3';
        ratioLabel = 'Standard (4:3)';
        borderColor = '#17a2b8';
    } else {
        // Custom aspect ratio - calculate based on base width
        mockupWidth = baseWidth;
        mockupHeight = Math.round(mockupWidth / aspectRatio);

        // Limit height for very tall ratios
        if (mockupHeight > 400) {
            mockupHeight = 400;
            mockupWidth = Math.round(mockupHeight * aspectRatio);
        }

        ratioClass = 'mockup-custom';
        ratioLabel = `Custom (${aspectRatio.toFixed(2)}:1)`;
        borderColor = '#6f42c1';
    }

    // Apply calculated dimensions and ensure centering
    mockupVideo.style.width = mockupWidth + 'px';
    mockupVideo.style.height = mockupHeight + 'px';
    mockupVideo.style.margin = '0 auto'; // Ensure horizontal centering
    mockupVideo.style.display = 'block'; // Ensure block display
    mockupVideo.classList.add(ratioClass);

    // Update resolution display with both resolution and ratio info
    resolutionDisplay.innerHTML = `
        <div style="font-weight: bold;">${resolution}</div>
        <div style="font-size: 8px; opacity: 0.8;">${ratioLabel}</div>
    `;



    // Add visual feedback for aspect ratio change
    mockupVideo.style.transform = 'scale(0.95)';
    setTimeout(() => {
        mockupVideo.style.transform = 'scale(1)';
    }, 200);

    // Update border color to match ratio type
    mockupVideo.style.borderColor = borderColor;

    // Calculate and update scale info
    updateScaleInfo(width, height, mockupWidth, mockupHeight);
}

// Update scale information
function updateScaleInfo(actualWidth, actualHeight, mockupWidth, mockupHeight) {
    const scaleInfo = document.getElementById('scaleInfo');
    if (!scaleInfo) return;

    // Calculate scale ratios
    const scaleX = Math.round(actualWidth / mockupWidth);
    const scaleY = Math.round(actualHeight / mockupHeight);
    const averageScale = Math.round((scaleX + scaleY) / 2);

    // Calculate percentage
    const percentage = ((mockupWidth * mockupHeight) / (actualWidth * actualHeight) * 100).toFixed(1);

    // Update scale display
    scaleInfo.innerHTML = `
        <i class="fas fa-ruler mr-1"></i>
        Scale: 1:${averageScale}
        <span class="text-muted">(${mockupWidth}×${mockupHeight} = ${percentage}% of actual)</span>
    `;
}

// Update mockup subtitle
function updateMockupSubtitle(settings) {
    const mockupSubtitle = document.getElementById('mockupSubtitle');
    if (!mockupSubtitle) return;

    const size = settings.subtitle_size || 24;
    const color = settings.subtitle_color || '#ffffff';
    const position = settings.subtitle_position || 'bottom';

    // Update subtitle style
    mockupSubtitle.style.color = color;
    mockupSubtitle.style.fontSize = Math.max(10, Math.min(20, size * 0.6)) + 'px';

    // Remove existing position classes
    mockupSubtitle.classList.remove('subtitle-top', 'subtitle-center', 'subtitle-bottom');

    // Add position class
    mockupSubtitle.classList.add('subtitle-' + position);

    // Update text based on position
    const positionText = {
        'top': 'Subtitle ở trên',
        'center': 'Subtitle ở giữa',
        'bottom': 'Subtitle ở dưới'
    };

    mockupSubtitle.textContent = positionText[position] || 'Subtitle tiếng Việt';
}

// Update mockup logo
function updateMockupLogo(settings) {
    const mockupLogo = document.getElementById('mockupLogo');
    if (!mockupLogo) return;

    const enableLogo = settings.enable_logo || false;
    const position = settings.logo_position || 'bottom-right';
    const size = settings.logo_size || 100;

    if (enableLogo) {
        mockupLogo.style.display = 'block';

        // Remove existing position classes
        mockupLogo.classList.remove('logo-top-left', 'logo-top-right', 'logo-bottom-left', 'logo-bottom-right');

        // Add position class
        mockupLogo.classList.add('logo-' + position.replace('_', '-'));

        // Update size
        const logoSize = Math.max(8, Math.min(16, size * 0.12));
        mockupLogo.style.fontSize = logoSize + 'px';
        mockupLogo.style.padding = (logoSize * 0.4) + 'px ' + (logoSize * 0.6) + 'px';

    } else {
        mockupLogo.style.display = 'none';
    }
}

// Update mockup image based on media settings
function updateMockupImage(settings) {
    console.log('Updating mockup image with settings:', settings);

    const mockupImage = document.getElementById('mockupImage');
    const demoImageElement = document.getElementById('demoImageElement');
    if (!mockupImage || !demoImageElement) {
        console.log('Mockup image elements not found');
        return;
    }

    // Get media settings
    const imagePosition = settings.image_position || 'center';
    const imageScale = settings.image_scale || 100;
    const imageOpacity = settings.image_opacity || 100;
    const imageRotation = settings.image_rotation || 0;
    const enableImageEffects = settings.enable_image_effects || false;

    console.log('Image settings:', {
        imagePosition, imageScale, imageOpacity, imageRotation, enableImageEffects
    });

    // Update image positioning
    updateImagePosition(mockupImage, imagePosition);

    // Update image scale (convert percentage to decimal)
    const scaleValue = imageScale / 100;

    // Update image opacity (convert percentage to decimal)
    const opacityValue = imageOpacity / 100;

    // Apply transformations
    let transform = `scale(${scaleValue})`;

    // Add rotation if specified
    if (imageRotation !== 0) {
        transform += ` rotate(${imageRotation}deg)`;
    }

    // Apply styles
    demoImageElement.style.transform = transform;
    demoImageElement.style.opacity = opacityValue;

    // Apply image effects from dropdown
    const imageEffect = settings.image_effect || 'none';
    applyImageEffectFromDropdown(demoImageElement, imageEffect);

    // Apply additional effects if enabled
    if (enableImageEffects) {
        applyImageEffects(demoImageElement, settings);
    }

    // Update image size based on scale
    const baseWidth = 200;
    const baseHeight = 150;
    demoImageElement.style.maxWidth = (baseWidth * scaleValue) + 'px';
    demoImageElement.style.maxHeight = (baseHeight * scaleValue) + 'px';
}

// Update image position
function updateImagePosition(mockupImage, position) {
    // Reset transform
    mockupImage.style.top = '';
    mockupImage.style.left = '';
    mockupImage.style.right = '';
    mockupImage.style.bottom = '';
    mockupImage.style.transform = '';

    // Map media-settings.blade.php values to positions
    switch (position) {
        case 'top':
            mockupImage.style.top = '10px';
            mockupImage.style.left = '50%';
            mockupImage.style.transform = 'translateX(-50%)';
            mockupImage.style.right = 'auto';
            mockupImage.style.bottom = 'auto';
            break;
        case 'bottom':
            mockupImage.style.bottom = '10px';
            mockupImage.style.left = '50%';
            mockupImage.style.transform = 'translateX(-50%)';
            mockupImage.style.top = 'auto';
            mockupImage.style.right = 'auto';
            break;
        case 'left':
            mockupImage.style.top = '50%';
            mockupImage.style.left = '10px';
            mockupImage.style.transform = 'translateY(-50%)';
            mockupImage.style.right = 'auto';
            mockupImage.style.bottom = 'auto';
            break;
        case 'right':
            mockupImage.style.top = '50%';
            mockupImage.style.right = '10px';
            mockupImage.style.transform = 'translateY(-50%)';
            mockupImage.style.left = 'auto';
            mockupImage.style.bottom = 'auto';
            break;
        case 'center':
        default:
            mockupImage.style.top = '50%';
            mockupImage.style.left = '50%';
            mockupImage.style.transform = 'translate(-50%, -50%)';
            mockupImage.style.right = 'auto';
            mockupImage.style.bottom = 'auto';
            break;
    }

    console.log(`Image positioned to: ${position}`, {
        top: mockupImage.style.top,
        left: mockupImage.style.left,
        right: mockupImage.style.right,
        bottom: mockupImage.style.bottom,
        transform: mockupImage.style.transform
    });
}

// Apply image effects
function applyImageEffects(imageElement, settings) {
    const effects = [];

    // Blur effect
    if (settings.image_blur && settings.image_blur > 0) {
        effects.push(`blur(${settings.image_blur}px)`);
    }

    // Brightness
    if (settings.image_brightness && settings.image_brightness !== 100) {
        effects.push(`brightness(${settings.image_brightness}%)`);
    }

    // Contrast
    if (settings.image_contrast && settings.image_contrast !== 100) {
        effects.push(`contrast(${settings.image_contrast}%)`);
    }

    // Saturation
    if (settings.image_saturation && settings.image_saturation !== 100) {
        effects.push(`saturate(${settings.image_saturation}%)`);
    }

    // Sepia
    if (settings.image_sepia && settings.image_sepia > 0) {
        effects.push(`sepia(${settings.image_sepia}%)`);
    }

    // Grayscale
    if (settings.image_grayscale && settings.image_grayscale > 0) {
        effects.push(`grayscale(${settings.image_grayscale}%)`);
    }

    // Apply all effects
    imageElement.style.filter = effects.length > 0 ? effects.join(' ') : 'none';
}

// Apply image effect from dropdown (media-settings.blade.php)
function applyImageEffectFromDropdown(imageElement, effect) {
    let filterEffect = '';

    switch (effect) {
        case 'blur':
            filterEffect = 'blur(3px)';
            break;
        case 'sepia':
            filterEffect = 'sepia(100%)';
            break;
        case 'grayscale':
            filterEffect = 'grayscale(100%)';
            break;
        case 'vintage':
            filterEffect = 'sepia(50%) contrast(1.2) brightness(1.1)';
            break;
        case 'bright':
            filterEffect = 'brightness(1.3)';
            break;
        case 'dark':
            filterEffect = 'brightness(0.7)';
            break;
        case 'none':
        default:
            filterEffect = 'none';
            break;
    }

    imageElement.style.filter = filterEffect;
    console.log(`Applied image effect: ${effect} -> ${filterEffect}`);
}



// Auto-load preview from existing template settings
function autoLoadPreviewFromTemplate() {
    console.log('Auto-loading preview from template settings...');

    try {
        // Get settings from JSON textarea (template settings)
        const settingsTextarea = document.getElementById('settings');
        if (!settingsTextarea || !settingsTextarea.value) {
            console.log('No template settings found');
            return;
        }

        const templateSettings = JSON.parse(settingsTextarea.value);
        console.log('Template settings loaded:', templateSettings);

        // Auto-show preview if template has meaningful settings
        if (hasValidPreviewSettings(templateSettings)) {
            console.log('Valid preview settings found, showing preview...');
            showMockupPreview();

            // Show loading indicator
            showAutoLoadIndicator();

            // Update preview with template settings
            setTimeout(() => {
                updateMockupPreview();
                hideAutoLoadIndicator();
                showAutoLoadSuccess(templateSettings);
                console.log('Preview updated with template settings');
            }, 500);
        } else {
            console.log('No valid preview settings found in template');
        }

    } catch (error) {
        console.error('Error auto-loading preview:', error);
    }
}

// Check if template has valid settings for preview
function hasValidPreviewSettings(settings) {
    // Check for any meaningful settings that would affect preview
    const meaningfulSettings = [
        'resolution',
        'image_position',
        'image_scale',
        'image_opacity',
        'image_effect',
        'subtitle_position',
        'subtitle_size',
        'subtitle_color',
        'enable_logo',
        'logo_position',
        'logo_size'
    ];

    return meaningfulSettings.some(setting =>
        settings.hasOwnProperty(setting) &&
        settings[setting] !== null &&
        settings[setting] !== undefined &&
        settings[setting] !== ''
    );
}

// Simple function to show mockup video
function showMockupVideo() {
    const mockupPreview = document.getElementById('mockupPreview');
    const previewInitial = document.getElementById('previewInitial');

    if (mockupPreview && previewInitial) {
        mockupPreview.style.display = 'block';
        previewInitial.style.display = 'none';

        console.log('Mockup video displayed');

        // Show simple notification
        showSimpleNotification();
    }
}

// Show simple notification
function showSimpleNotification() {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 14px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    `;
    notification.innerHTML = `
        <i class="fas fa-check mr-2"></i>
        Mockup video đã hiển thị
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}





// Global variables for editor
let selectedElement = null;
let layoutConfig = {};

// Select element for editing
function selectElement(element) {
    // Clear previous selection
    document.querySelectorAll('.draggable-element').forEach(el => {
        el.classList.remove('selected');
    });

    // Select new element
    element.classList.add('selected');
    selectedElement = element;

    // Show editor controls
    const editorControls = document.getElementById('editorControls');
    const elementName = document.getElementById('selectedElementName');

    editorControls.style.display = 'block';
    elementName.textContent = getElementDisplayName(element.dataset.element);

    // Update controls with current values
    updateControlsFromElement(element);

    // Show/hide relevant control groups
    toggleControlGroups(element.dataset.element);
}

// Clear selection
function clearSelection() {
    document.querySelectorAll('.draggable-element').forEach(el => {
        el.classList.remove('selected');
    });

    selectedElement = null;
    document.getElementById('editorControls').style.display = 'none';
}

// Get display name for element
function getElementDisplayName(elementType) {
    const names = {
        'image': 'Hình ảnh',
        'text': 'Văn bản',
        'logo': 'Logo',
        'subtitle': 'Phụ đề'
    };
    return names[elementType] || 'Element';
}

// Update controls from element
function updateControlsFromElement(element) {
    const rect = element.getBoundingClientRect();
    const container = element.closest('.mockup-content').getBoundingClientRect();

    // Position
    const x = ((rect.left - container.left) / container.width) * 100;
    const y = ((rect.top - container.top) / container.height) * 100;

    document.getElementById('positionX').value = Math.round(x);
    document.getElementById('positionY').value = Math.round(y);
    document.getElementById('positionXValue').textContent = Math.round(x) + '%';
    document.getElementById('positionYValue').textContent = Math.round(y) + '%';

    // Size
    const width = (rect.width / container.width) * 100;
    const height = (rect.height / container.height) * 100;

    document.getElementById('elementWidth').value = Math.round(width);
    document.getElementById('elementHeight').value = Math.round(height);
    document.getElementById('widthValue').textContent = Math.round(width) + '%';
    document.getElementById('heightValue').textContent = Math.round(height) + '%';

    // Opacity
    const opacity = parseFloat(getComputedStyle(element).opacity) * 100;
    document.getElementById('elementOpacity').value = Math.round(opacity);
    document.getElementById('opacityValue').textContent = Math.round(opacity) + '%';

    // Get transform values
    const transform = getComputedStyle(element).transform;
    let rotation = 0, scale = 100;

    if (transform && transform !== 'none') {
        // Parse transform matrix to get rotation and scale
        const matrix = transform.match(/matrix.*\((.+)\)/);
        if (matrix) {
            const values = matrix[1].split(', ');
            const a = parseFloat(values[0]);
            const b = parseFloat(values[1]);
            rotation = Math.round(Math.atan2(b, a) * (180 / Math.PI));
            scale = Math.round(Math.sqrt(a * a + b * b) * 100);
        }
    }

    document.getElementById('elementRotation').value = rotation;
    document.getElementById('elementScale').value = scale;
    document.getElementById('rotationValue').textContent = rotation + '°';
    document.getElementById('scaleValue').textContent = scale + '%';
}

// Setup control listeners
function setupControlListeners() {
    // Position controls
    ['positionX', 'positionY'].forEach(id => {
        const control = document.getElementById(id);
        control.addEventListener('input', function() {
            if (selectedElement) {
                applyPositionFromControls();
            }
            document.getElementById(id + 'Value').textContent = this.value + '%';
        });
    });

    // Size controls
    ['elementWidth', 'elementHeight'].forEach(id => {
        const control = document.getElementById(id);
        control.addEventListener('input', function() {
            if (selectedElement) {
                applySizeFromControls();
            }
            const valueId = id.replace('element', '').toLowerCase() + 'Value';
            document.getElementById(valueId).textContent = this.value + '%';
        });
    });

    // Style controls
    document.getElementById('elementOpacity').addEventListener('input', function() {
        if (selectedElement) {
            selectedElement.style.opacity = this.value / 100;
            saveElementConfig(selectedElement);
        }
        document.getElementById('opacityValue').textContent = this.value + '%';
    });

    document.getElementById('elementRotation').addEventListener('input', function() {
        if (selectedElement) {
            applyTransformFromControls();
        }
        document.getElementById('rotationValue').textContent = this.value + '°';
    });

    document.getElementById('elementScale').addEventListener('input', function() {
        if (selectedElement) {
            applyTransformFromControls();
        }
        document.getElementById('scaleValue').textContent = this.value + '%';
    });

    // Color controls
    document.getElementById('textColor').addEventListener('input', function() {
        if (selectedElement) {
            applyColorFromControls();
        }
    });

    document.getElementById('backgroundColor').addEventListener('input', function() {
        if (selectedElement) {
            applyColorFromControls();
        }
    });

    // Font controls
    document.getElementById('fontSize').addEventListener('input', function() {
        if (selectedElement) {
            applyFontFromControls();
        }
        document.getElementById('fontSizeValue').textContent = this.value + 'px';
    });

    document.getElementById('fontWeight').addEventListener('change', function() {
        if (selectedElement) {
            applyFontFromControls();
        }
    });
}

// Apply position from controls
function applyPositionFromControls() {
    const x = document.getElementById('positionX').value;
    const y = document.getElementById('positionY').value;

    selectedElement.style.left = x + '%';
    selectedElement.style.top = y + '%';
    selectedElement.style.transform = 'none'; // Reset transform positioning

    saveElementConfig(selectedElement);
}

// Apply size from controls
function applySizeFromControls() {
    const width = document.getElementById('elementWidth').value;
    const height = document.getElementById('elementHeight').value;

    selectedElement.style.width = width + '%';
    selectedElement.style.height = height + '%';

    saveElementConfig(selectedElement);
}

// Apply transform from controls
function applyTransformFromControls() {
    const rotation = document.getElementById('elementRotation').value;
    const scale = document.getElementById('elementScale').value / 100;

    const x = parseFloat(selectedElement.getAttribute('data-x')) || 0;
    const y = parseFloat(selectedElement.getAttribute('data-y')) || 0;

    selectedElement.style.transform = `translate(${x}px, ${y}px) rotate(${rotation}deg) scale(${scale})`;

    saveElementConfig(selectedElement);
}

// Apply color from controls
function applyColorFromControls() {
    const textColor = document.getElementById('textColor').value;
    const backgroundColor = document.getElementById('backgroundColor').value;

    const elementType = selectedElement.dataset.element;

    if (elementType === 'text' || elementType === 'subtitle') {
        const content = selectedElement.querySelector('.text-content, .subtitle-content');
        if (content) {
            content.style.color = textColor;
        }
        selectedElement.style.backgroundColor = backgroundColor;
    } else if (elementType === 'logo') {
        const content = selectedElement.querySelector('.logo-content');
        if (content) {
            content.style.color = textColor;
        }
        selectedElement.style.backgroundColor = backgroundColor;
    }

    saveElementConfig(selectedElement);
}

// Apply font from controls
function applyFontFromControls() {
    const fontSize = document.getElementById('fontSize').value;
    const fontWeight = document.getElementById('fontWeight').value;

    const content = selectedElement.querySelector('.text-content, .subtitle-content, .logo-content');
    if (content) {
        content.style.fontSize = fontSize + 'px';
        content.style.fontWeight = fontWeight;
    }

    saveElementConfig(selectedElement);
}

// Save element configuration
function saveElementConfig(element) {
    const elementType = element.dataset.element;
    const rect = element.getBoundingClientRect();
    const container = element.closest('.mockup-content').getBoundingClientRect();

    // Calculate relative position and size
    const config = {
        type: elementType,
        position: {
            x: ((rect.left - container.left) / container.width) * 100,
            y: ((rect.top - container.top) / container.height) * 100
        },
        size: {
            width: (rect.width / container.width) * 100,
            height: (rect.height / container.height) * 100
        },
        style: {
            opacity: parseFloat(getComputedStyle(element).opacity),
            transform: getComputedStyle(element).transform
        }
    };

    // Add element-specific properties
    if (elementType === 'text' || elementType === 'subtitle') {
        const content = element.querySelector('.text-content, .subtitle-content');
        if (content) {
            config.content = {
                text: content.textContent,
                fontSize: getComputedStyle(content).fontSize,
                fontWeight: getComputedStyle(content).fontWeight,
                color: getComputedStyle(content).color
            };
        }
        config.backgroundColor = getComputedStyle(element).backgroundColor;
    }

    // Save to layout config
    layoutConfig[elementType] = config;

    console.log('Element config saved:', config);
}

// Save entire layout
function saveLayout() {
    // Collect all element configs
    document.querySelectorAll('.draggable-element').forEach(element => {
        saveElementConfig(element);
    });

    // Send to backend
    fetch('/admin/video-templates/save-layout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            template_id: {{ $videoTemplate->id }},
            layout_config: layoutConfig
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast('Layout đã được lưu thành công!');
        } else {
            showErrorToast('Lỗi khi lưu layout: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving layout:', error);
        showErrorToast('Lỗi khi lưu layout');
    });
}

// Export configuration
function exportConfig() {
    // Collect all element configs
    document.querySelectorAll('.draggable-element').forEach(element => {
        saveElementConfig(element);
    });

    // Create export data
    const exportData = {
        template_id: {{ $videoTemplate->id }},
        template_name: '{{ $videoTemplate->name }}',
        layout_config: layoutConfig,
        export_timestamp: new Date().toISOString(),
        video_resolution: getCurrentSettings().resolution || '1920x1080'
    };

    // Download as JSON file
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `video-template-${exportData.template_id}-layout.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showSuccessToast('Layout config đã được export!');
}

// Reset element to default
function resetElement() {
    if (!selectedElement) return;

    const elementType = selectedElement.dataset.element;

    // Reset position
    selectedElement.style.left = '50%';
    selectedElement.style.top = '50%';
    selectedElement.style.transform = 'translate(-50%, -50%)';
    selectedElement.setAttribute('data-x', 0);
    selectedElement.setAttribute('data-y', 0);

    // Reset size
    selectedElement.style.width = 'auto';
    selectedElement.style.height = 'auto';

    // Reset style
    selectedElement.style.opacity = '1';

    // Update controls
    updateControlsFromElement(selectedElement);

    // Save config
    saveElementConfig(selectedElement);

    showSuccessToast('Element đã được reset!');
}

// Toggle control groups based on element type
function toggleControlGroups(elementType) {
    const colorControls = document.getElementById('colorControls');
    const fontControls = document.getElementById('fontControls');

    if (elementType === 'text' || elementType === 'subtitle' || elementType === 'logo') {
        colorControls.style.display = 'block';
        fontControls.style.display = 'block';
    } else {
        colorControls.style.display = 'none';
        fontControls.style.display = 'none';
    }
}

// Show auto-load indicator
function showAutoLoadIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'autoLoadIndicator';
    indicator.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    indicator.innerHTML = `
        <i class="fas fa-spinner fa-spin mr-2"></i>
        Đang tải preview từ template...
    `;

    document.body.appendChild(indicator);
}

// Hide auto-load indicator
function hideAutoLoadIndicator() {
    const indicator = document.getElementById('autoLoadIndicator');
    if (indicator) {
        indicator.remove();
    }
}

// Show auto-load success message
function showAutoLoadSuccess(settings) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;

    // Count loaded settings
    const loadedSettings = Object.keys(settings).filter(key =>
        settings[key] !== null && settings[key] !== undefined && settings[key] !== ''
    ).length;

    notification.innerHTML = `
        <i class="fas fa-check mr-2"></i>
        Preview đã tải với ${loadedSettings} settings từ template
    `;

    document.body.appendChild(notification);

    // Auto remove after 4 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 4000);
}

// Highlight setting group when focused
function highlightSettingGroup(element) {
    // Remove existing highlights
    document.querySelectorAll('.setting-focused').forEach(el => {
        el.classList.remove('setting-focused');
    });

    // Add highlight to current element
    element.classList.add('setting-focused');

    // Find and highlight related elements in the same group
    const name = element.name || element.id || '';
    if (name.includes('subtitle')) {
        document.querySelectorAll('[name*="subtitle"], [id*="subtitle"]').forEach(el => {
            el.classList.add('setting-focused');
        });
    } else if (name.includes('logo')) {
        document.querySelectorAll('[name*="logo"], [id*="logo"]').forEach(el => {
            el.classList.add('setting-focused');
        });
    } else if (name.includes('resolution')) {
        document.querySelectorAll('[name*="resolution"], [id*="resolution"]').forEach(el => {
            el.classList.add('setting-focused');
        });
    } else if (name.includes('image')) {
        document.querySelectorAll('[name*="image"], [id*="image"]').forEach(el => {
            el.classList.add('setting-focused');
        });
        // Highlight the demo image in preview
        const demoImage = document.getElementById('demoImageElement');
        if (demoImage) {
            demoImage.style.boxShadow = '0 0 20px rgba(255, 193, 7, 0.8)';
            setTimeout(() => {
                demoImage.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.3)';
            }, 2000);
        }
    }
}

// Remove highlight
function removeHighlight(element) {
    setTimeout(() => {
        document.querySelectorAll('.setting-focused').forEach(el => {
            el.classList.remove('setting-focused');
        });
    }, 100);
}

// Test resolution function for quick preview
function testResolution(resolution) {
    // Show mockup if not visible
    showMockupPreview();

    // Create temporary settings with test resolution
    const currentSettings = getCurrentSettings();
    const testSettings = {
        ...currentSettings,
        resolution: resolution
    };

    // Update mockup with test resolution
    updateMockupResolution(testSettings);

    // Show image size suggestions for this resolution (only for test buttons)
    showImageSuggestions(resolution);

    // Highlight the resolution change
    const mockupVideo = document.querySelector('.mockup-video');
    if (mockupVideo) {
        mockupVideo.style.boxShadow = '0 0 20px rgba(0, 123, 255, 0.5)';
        setTimeout(() => {
            mockupVideo.style.boxShadow = '';
        }, 1000);
    }

    // Show notification
    showResolutionChangeNotification(resolution);
}

// Show resolution change notification
function showResolutionChangeNotification(resolution) {
    const mockupFrame = document.querySelector('.mockup-frame');
    if (!mockupFrame) return;

    // Remove existing notification
    const existingNotification = mockupFrame.querySelector('.resolution-notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification
    const notification = document.createElement('div');
    notification.className = 'resolution-notification';
    notification.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(40, 167, 69, 0.9);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        animation: fadeInOut 2s ease-in-out;
    `;
    notification.innerHTML = `<i class="fas fa-check mr-1"></i>Test: ${resolution}`;

    mockupFrame.style.position = 'relative';
    mockupFrame.appendChild(notification);

    // Auto remove
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 2000);
}

// Toggle ratio comparison table
function toggleRatioComparison() {
    const comparison = document.getElementById('ratioComparison');
    const showBtn = document.getElementById('showComparisonBtn');

    if (comparison.style.display === 'none') {
        comparison.style.display = 'block';
        showBtn.style.display = 'none';
        populateRatioTable();
    } else {
        comparison.style.display = 'none';
        showBtn.style.display = 'block';
    }
}

// Populate ratio comparison table
function populateRatioTable() {
    const tableBody = document.getElementById('ratioTableBody');
    if (!tableBody) return;

    const ratios = [
        { name: '16:9', resolution: '1920×1080', width: 320, height: 180, color: '#28a745' },
        { name: '9:16', resolution: '1080×1920', width: 160, height: 284, color: '#dc3545' },
        { name: '1:1', resolution: '1080×1080', width: 240, height: 240, color: '#ffc107' },
        { name: '4:3', resolution: '1280×960', width: 280, height: 210, color: '#17a2b8' }
    ];

    tableBody.innerHTML = '';

    ratios.forEach(ratio => {
        const actualWidth = parseInt(ratio.resolution.split('×')[0]);
        const actualHeight = parseInt(ratio.resolution.split('×')[1]);
        const scale = Math.round(actualWidth / ratio.width);

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <span style="color: ${ratio.color}; font-weight: bold;">●</span>
                ${ratio.name}
            </td>
            <td>${ratio.resolution}</td>
            <td>${ratio.width}×${ratio.height}</td>
            <td>1:${scale}</td>
        `;
        tableBody.appendChild(row);
    });
}

// Show image size suggestions based on video resolution
function showImageSuggestions(resolution) {
    const suggestions = platformImageSuggestions[resolution];
    if (!suggestions) return;

    // Remove existing suggestions
    hideImageSuggestions();

    // Create suggestions container
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'image-suggestions';
    suggestionsContainer.innerHTML = `
        <div class="suggestions-header">
            <h6 class="mb-2">
                <i class="fas fa-images mr-2"></i>
                Gợi ý kích thước ảnh cho ${suggestions.platform}
            </h6>
            <span class="badge badge-info">${suggestions.type} (${suggestions.ratio})</span>
        </div>
        <div class="suggestions-list">
            ${suggestions.suggestions.map(suggestion => `
                <div class="suggestion-item ${suggestion.recommended ? 'recommended' : ''}"
                     onclick="copySuggestionSize('${suggestion.size}')"
                     title="Click để copy kích thước">
                    <div class="suggestion-size">
                        <strong>${suggestion.size}</strong>
                        ${suggestion.recommended ? '<i class="fas fa-star text-warning ml-1"></i>' : ''}
                        <i class="fas fa-copy text-muted ml-2" style="font-size: 12px;"></i>
                    </div>
                    <div class="suggestion-desc">${suggestion.desc}</div>
                </div>
            `).join('')}
        </div>
        <div class="suggestions-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-lightbulb mr-1"></i>
                    Sử dụng ảnh có tỉ lệ ${suggestions.ratio} để tránh bị cắt xén
                </small>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="hideImageSuggestions()">
                    <i class="fas fa-times mr-1"></i>Ẩn gợi ý
                </button>
            </div>
        </div>
    `;

    // Insert suggestions after mockup content wrapper
    const mockupWrapper = document.querySelector('.mockup-content-wrapper');
    if (mockupWrapper && mockupWrapper.parentNode) {
        mockupWrapper.parentNode.insertBefore(suggestionsContainer, mockupWrapper.nextSibling);
    }
}

// Hide image suggestions
function hideImageSuggestions() {
    const existing = document.querySelector('.image-suggestions');
    if (existing) {
        existing.remove();
    }
}

// Copy suggestion size to clipboard
function copySuggestionSize(size) {
    // Copy to clipboard
    navigator.clipboard.writeText(size).then(() => {
        // Show success notification
        showCopyNotification(size);
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = size;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showCopyNotification(size);
    });
}

// Show copy notification
function showCopyNotification(size) {
    const notification = document.createElement('div');
    notification.className = 'copy-notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas fa-check mr-2"></i>
        Đã copy: <strong>${size}</strong>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 3000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);



// Show sticky indicator
function showStickyIndicator() {
    const previewCard = document.getElementById('videoPreviewCard');
    if (!previewCard) return;

    // Remove existing indicator
    hideStickyIndicator();

    // Create sticky indicator
    const indicator = document.createElement('div');
    indicator.className = 'sticky-indicator';
    indicator.innerHTML = `
        <i class="fas fa-thumbtack mr-1"></i>
        <span>Video Preview Card đang follow scroll</span>
    `;

    previewCard.appendChild(indicator);
}

// Hide sticky indicator
function hideStickyIndicator() {
    const indicator = document.querySelector('.sticky-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// Simple function to reset preview (if needed)
function resetPreview() {
    const mockupPreview = document.getElementById('mockupPreview');
    const previewInitial = document.getElementById('previewInitial');

    if (mockupPreview && previewInitial) {
        mockupPreview.style.display = 'none';
        previewInitial.style.display = 'block';
    }

    // Remove all highlights
    document.querySelectorAll('.setting-focused').forEach(el => {
        el.classList.remove('setting-focused');
    });
}

</script>
@endpush
