@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Template Video', 'url' => route('admin.video-templates.index')],
        ['title' => 'Tạo Template Mới']
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus mr-2"></i>Tạo Template Video Mới</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.video-templates.store') }}" enctype="multipart/form-data" id="templateForm">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Thông tin cơ bản</h6>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name">Tên template <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control" 
                                               value="{{ old('name') }}" required>
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
                                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
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
                                          placeholder="Mô tả ngắn gọn về template này...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="thumbnail">Ảnh thumbnail</label>
                                <input type="file" name="thumbnail" id="thumbnail" class="form-control-file" 
                                       accept="image/*" onchange="previewThumbnail(this)">
                                <small class="form-text text-muted">JPG, PNG. Tối đa 2MB. Kích thước khuyến nghị: 400x300px</small>
                                <div id="thumbnail-preview" class="mt-2"></div>
                                @error('thumbnail')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" 
                                           {{ old('is_public') ? 'checked' : '' }}>
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
                                <div class="input-item border p-3 mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tên trường</label>
                                                <input type="text" name="required_inputs[0][name]" class="form-control"
                                                       placeholder="vd: script_text">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Loại input</label>
                                                <select name="required_inputs[0][type]" class="form-control">
                                                    @foreach($inputTypes as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Nhãn hiển thị</label>
                                                <input type="text" name="required_inputs[0][label]" class="form-control"
                                                       placeholder="vd: Nội dung kịch bản">
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
                                        <input type="text" name="required_inputs[0][placeholder]" class="form-control"
                                               placeholder="Hướng dẫn cho người dùng...">
                                    </div>
                                    <div class="form-group select-options" style="display: none;">
                                        <label>Options (cho Select) - JSON format</label>
                                        <textarea name="required_inputs[0][options]" class="form-control" rows="3"
                                                  placeholder='{"value1": "Label 1", "value2": "Label 2"}'></textarea>
                                        <small class="text-muted">Chỉ cần điền khi loại input là "Lựa chọn"</small>
                                    </div>
                                </div>
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
                                <!-- Optional inputs will be added here -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-secondary" id="add-optional-input">
                                <i class="fas fa-plus mr-2"></i>Thêm input tùy chọn
                            </button>
                        </div>

                        <!-- Template Settings -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Cài đặt template</h6>
                            <p class="text-muted">Cấu hình mặc định cho video được tạo từ template này</p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Hướng dẫn:</strong> Sử dụng form tạo video bình thường để cấu hình, 
                                sau đó copy cài đặt vào đây. Hoặc nhập JSON trực tiếp.
                            </div>
                            
                            <!-- Settings Builder Tabs -->
                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic-settings" role="tab">
                                        <i class="fas fa-cog mr-1"></i>Cơ bản
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="video-tab" data-toggle="tab" href="#video-settings" role="tab">
                                        <i class="fas fa-video mr-1"></i>Video
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="audio-tab" data-toggle="tab" href="#audio-settings" role="tab">
                                        <i class="fas fa-volume-up mr-1"></i>Âm thanh
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="logo-tab" data-toggle="tab" href="#logo-settings" role="tab">
                                        <i class="fas fa-image mr-1"></i>Logo
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="advanced-tab" data-toggle="tab" href="#advanced-settings" role="tab">
                                        <i class="fas fa-magic mr-1"></i>Nâng cao
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
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="platform">Platform</label>
                                                <select name="platform" id="platform" class="form-control">
                                                    <option value="tiktok">TikTok (9:16)</option>
                                                    <option value="youtube">YouTube (16:9)</option>
                                                    <option value="none">Tùy chỉnh</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="media_type">Loại media</label>
                                                <select name="media_type" id="media_type" class="form-control">
                                                    <option value="images">Chỉ hình ảnh</option>
                                                    <option value="video">Chỉ video</option>
                                                    <option value="mixed">Kết hợp ảnh + video</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="duration_based_on">Độ dài video dựa trên</label>
                                                <select name="duration_based_on" id="duration_based_on" class="form-control">
                                                    <option value="images">Số lượng ảnh</option>
                                                    <option value="audio">Độ dài âm thanh</option>
                                                    <option value="video">Độ dài video nền</option>
                                                    <option value="custom">Tùy chỉnh (giây)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="custom_duration">Độ dài tùy chỉnh (giây)</label>
                                                <input type="number" name="custom_duration" id="custom_duration" class="form-control"
                                                       value="30">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="image_duration">Thời gian hiển thị mỗi ảnh (giây)</label>
                                                <input type="number" name="image_duration" id="image_duration" class="form-control"
                                                       step="0.5" value="3">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="transition_effect">Hiệu ứng chuyển cảnh</label>
                                                <select name="transition_effect" id="transition_effect" class="form-control">
                                                    <option value="fade">Fade in/out</option>
                                                    <option value="slide">Slide</option>
                                                    <option value="zoom">Zoom</option>
                                                    <option value="none">Không có</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="sync_with_audio" id="sync_with_audio" class="form-check-input">
                                            <label class="form-check-label" for="sync_with_audio">
                                                Đồng bộ với âm thanh
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Settings Tab -->
                                <div class="tab-pane fade" id="video-settings" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="resolution">Độ phân giải</label>
                                                <select name="resolution" id="resolution" class="form-control">
                                                    <option value="1920x1080">Full HD (1920x1080)</option>
                                                    <option value="1280x720">HD (1280x720)</option>
                                                    <option value="1080x1920">TikTok (1080x1920)</option>
                                                    <option value="1080x1080">Square (1080x1080)</option>
                                                    <option value="custom">Tùy chỉnh</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fps">FPS (Frames per second)</label>
                                                <select name="fps" id="fps" class="form-control">
                                                    <option value="24">24 FPS (Cinematic)</option>
                                                    <option value="30" selected>30 FPS (Standard)</option>
                                                    <option value="60">60 FPS (Smooth)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" id="custom-resolution" style="display: none;">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="custom_width">Chiều rộng (px)</label>
                                                <input type="number" name="custom_width" id="custom_width" class="form-control"
                                                       value="1920">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="custom_height">Chiều cao (px)</label>
                                                <input type="number" name="custom_height" id="custom_height" class="form-control"
                                                       value="1080">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="quality">Chất lượng video</label>
                                                <select name="quality" id="quality" class="form-control">
                                                    <option value="medium">Trung bình (Nhanh)</option>
                                                    <option value="high" selected>Cao (Cân bằng)</option>
                                                    <option value="very_high">Rất cao (Chậm)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="video_sections">Chia video thành quãng</label>
                                                <select name="video_sections" id="video_sections" class="form-control">
                                                    <option value="none">Không chia quãng</option>
                                                    <option value="auto">Tự động theo nội dung</option>
                                                    <option value="time">Theo thời gian</option>
                                                    <option value="manual">Tùy chỉnh thủ công</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="section-settings" style="display: none;">
                                        <div class="form-group">
                                            <label for="section_duration">Thời gian mỗi quãng (giây)</label>
                                            <input type="number" name="section_duration" id="section_duration" class="form-control"
                                                   value="10">
                                        </div>

                                        <div class="form-group">
                                            <label for="section_transition">Hiệu ứng chuyển quãng</label>
                                            <select name="section_transition" id="section_transition" class="form-control">
                                                <option value="fade">Fade</option>
                                                <option value="slide">Slide</option>
                                                <option value="zoom">Zoom</option>
                                                <option value="wipe">Wipe</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Image Display Settings -->
                                    <h6 class="mt-4 mb-3">Cài đặt hiển thị ảnh</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="image_position">Vị trí ảnh</label>
                                                <select name="image_position" id="image_position" class="form-control">
                                                    <option value="center">Giữa</option>
                                                    <option value="top">Trên</option>
                                                    <option value="bottom">Dưới</option>
                                                    <option value="left">Trái</option>
                                                    <option value="right">Phải</option>
                                                    <option value="fill">Phủ toàn màn hình</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="image_scale">Kích thước ảnh (%)</label>
                                                <input type="range" name="image_scale" id="image_scale" class="form-control-range"
                                                       min="50" max="150" value="100" oninput="updateScaleValue(this.value)">
                                                <small class="form-text text-muted">Kích thước: <span id="scale-value">100</span>%</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="image_opacity">Độ trong suốt (%)</label>
                                                <input type="range" name="image_opacity" id="image_opacity" class="form-control-range"
                                                       min="10" max="100" value="100" oninput="updateOpacityValue(this.value)">
                                                <small class="form-text text-muted">Độ trong suốt: <span id="opacity-value">100</span>%</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="image_effect">Hiệu ứng xuất hiện</label>
                                                <select name="image_effect" id="image_effect" class="form-control">
                                                    <option value="none">Không có</option>
                                                    <option value="fade_in">Fade in</option>
                                                    <option value="slide_in">Slide in</option>
                                                    <option value="zoom_in">Zoom in</option>
                                                    <option value="rotate_in">Rotate in</option>
                                                    <option value="bounce_in">Bounce in</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audio Settings Tab -->
                                <div class="tab-pane fade" id="audio-settings" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="audio_source">Nguồn âm thanh mặc định</label>
                                                <select name="audio_source" id="audio_source" class="form-control">
                                                    <option value="none">Không có âm thanh</option>
                                                    <option value="tts">Text-to-Speech</option>
                                                    <option value="upload">Upload file</option>
                                                    <option value="library">Từ thư viện</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="audio_volume">Âm lượng mặc định (dB)</label>
                                                <input type="number" name="audio_volume" id="audio_volume" class="form-control"
                                                       value="18" step="1">
                                                <small class="form-text text-muted">Khuyến nghị: 18dB</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TTS Settings -->
                                    <div id="tts-settings" style="display: none;">
                                        <h6 class="mt-3 mb-3">Cài đặt Text-to-Speech</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tts_voice">Giọng đọc mặc định</label>
                                                    <select name="tts_voice" id="tts_voice" class="form-control">
                                                        <option value="vi-VN-HoaiMyNeural">Hoài My (Nữ)</option>
                                                        <option value="vi-VN-NamMinhNeural">Nam Minh (Nam)</option>
                                                        <option value="hn_female_ngochuyen_full_48k-fhg">Ngọc Huyền (Nữ)</option>
                                                        <option value="hn_male_xuantin_full_48k-fhg">Xuân Tín (Nam)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tts_speed">Tốc độ đọc</label>
                                                    <select name="tts_speed" id="tts_speed" class="form-control">
                                                        <option value="0.5">0.5x (Chậm)</option>
                                                        <option value="0.75">0.75x</option>
                                                        <option value="1.0" selected>1.0x (Bình thường)</option>
                                                        <option value="1.25">1.25x</option>
                                                        <option value="1.5">1.5x (Nhanh)</option>
                                                        <option value="2.0">2.0x (Rất nhanh)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tts_volume">Âm lượng TTS (dB)</label>
                                                    <input type="number" name="tts_volume" id="tts_volume" class="form-control"
                                                           value="18" step="1">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tts_bitrate">Bitrate (kbps)</label>
                                                    <select name="tts_bitrate" id="tts_bitrate" class="form-control">
                                                        <option value="64">64 kbps</option>
                                                        <option value="128" selected>128 kbps</option>
                                                        <option value="192">192 kbps</option>
                                                        <option value="256">256 kbps</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Background Audio Settings -->
                                    <h6 class="mt-4 mb-3">Nhạc nền</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="background_audio_volume">Âm lượng nhạc nền (%)</label>
                                                <input type="range" name="background_audio_volume" id="background_audio_volume"
                                                       class="form-control-range" min="0" max="100" value="30"
                                                       oninput="updateBgVolumeValue(this.value)">
                                                <small class="form-text text-muted">Âm lượng: <span id="bg-volume-value">30</span>%</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="audio_mixing">Cách trộn âm thanh</label>
                                                <select name="audio_mixing" id="audio_mixing" class="form-control">
                                                    <option value="overlay">Phủ lên (Overlay)</option>
                                                    <option value="ducking">Giảm nhạc nền khi có giọng nói</option>
                                                    <option value="separate">Tách riêng từng đoạn</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="normalize_audio" id="normalize_audio" class="form-check-input" checked>
                                            <label class="form-check-label" for="normalize_audio">
                                                Chuẩn hóa âm thanh (Normalize)
                                            </label>
                                            <small class="form-text text-muted">Tự động điều chỉnh âm lượng để đồng đều</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Logo Settings Tab -->
                                <div class="tab-pane fade" id="logo-settings" role="tabpanel">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="enable_logo" id="enable_logo" class="form-check-input">
                                            <label class="form-check-label" for="enable_logo">
                                                <strong>Sử dụng logo trong video</strong>
                                            </label>
                                        </div>
                                    </div>

                                    <div id="logo-config" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo_position">Vị trí logo</label>
                                                    <select name="logo_position" id="logo_position" class="form-control">
                                                        <option value="top-left">Góc trên trái</option>
                                                        <option value="top-center">Giữa trên</option>
                                                        <option value="top-right">Góc trên phải</option>
                                                        <option value="center-left">Giữa trái</option>
                                                        <option value="center">Chính giữa</option>
                                                        <option value="center-right">Giữa phải</option>
                                                        <option value="bottom-left">Góc dưới trái</option>
                                                        <option value="bottom-center">Giữa dưới</option>
                                                        <option value="bottom-right" selected>Góc dưới phải</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo_size">Kích thước logo (px)</label>
                                                    <input type="number" name="logo_size" id="logo_size" class="form-control"
                                                           value="100" step="10">
                                                    <small class="form-text text-muted">Khuyến nghị: 80-120px</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo_opacity">Độ trong suốt logo (%)</label>
                                                    <input type="range" name="logo_opacity" id="logo_opacity" class="form-control-range"
                                                           min="10" max="100" value="90" oninput="updateLogoOpacityValue(this.value)">
                                                    <small class="form-text text-muted">Độ trong suốt: <span id="logo-opacity-value">90</span>%</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo_margin">Khoảng cách từ viền (px)</label>
                                                    <input type="number" name="logo_margin" id="logo_margin" class="form-control"
                                                           value="20" step="5">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo_timing">Thời gian hiển thị</label>
                                                    <select name="logo_timing" id="logo_timing" class="form-control">
                                                        <option value="full">Toàn bộ video</option>
                                                        <option value="start">Chỉ đầu video (5s)</option>
                                                        <option value="end">Chỉ cuối video (5s)</option>
                                                        <option value="custom">Tùy chỉnh</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6" id="logo-custom-timing" style="display: none;">
                                                <div class="form-group">
                                                    <label for="logo_duration">Thời gian hiển thị (giây)</label>
                                                    <input type="number" name="logo_duration" id="logo_duration" class="form-control"
                                                           value="10">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="logo_effect">Hiệu ứng logo</label>
                                            <select name="logo_effect" id="logo_effect" class="form-control">
                                                <option value="none">Không có</option>
                                                <option value="fade_in">Fade in</option>
                                                <option value="slide_in">Slide in</option>
                                                <option value="zoom_in">Zoom in</option>
                                                <option value="bounce_in">Bounce in</option>
                                            </select>
                                        </div>

                                        <!-- Logo Preview -->
                                        <div class="form-group">
                                            <label>Xem trước vị trí logo</label>
                                            <div class="logo-preview-container" style="position: relative; width: 100%; height: 200px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                                                <div id="logo-preview" style="position: absolute; width: 40px; height: 40px; background: #007bff; border-radius: 4px; bottom: 10px; right: 10px;">
                                                    <i class="fas fa-image text-white" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>
                                                </div>
                                                <div class="text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #6c757d;">
                                                    <i class="fas fa-video fa-2x mb-2"></i><br>
                                                    <small>Khung video</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Settings Tab -->
                                <div class="tab-pane fade" id="advanced-settings" role="tabpanel">
                                    <h6 class="mb-3">Cài đặt nâng cao</h6>

                                    <div class="form-group">
                                        <label for="content_behavior">Xử lý khi nội dung ngắn hơn</label>
                                        <select name="content_behavior" id="content_behavior" class="form-control">
                                            <option value="loop">Lặp lại nội dung</option>
                                            <option value="extend">Kéo dài frame cuối</option>
                                            <option value="fit">Điều chỉnh độ dài</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="max_duration">Độ dài tối đa (giây)</label>
                                                <input type="number" name="max_duration" id="max_duration" class="form-control"
                                                       value="300">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="min_duration">Độ dài tối thiểu (giây)</label>
                                                <input type="number" name="min_duration" id="min_duration" class="form-control"
                                                       value="10">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="auto_adjust_images" id="auto_adjust_images" class="form-check-input">
                                            <label class="form-check-label" for="auto_adjust_images">
                                                Tự động điều chỉnh số lượng ảnh theo độ dài
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Subtitle Advanced Settings -->
                                    <h6 class="mt-4 mb-3">Phụ đề nâng cao</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="subtitle_position">Vị trí phụ đề</label>
                                                <select name="subtitle_position" id="subtitle_position" class="form-control">
                                                    <option value="top">Trên</option>
                                                    <option value="center">Giữa</option>
                                                    <option value="bottom" selected>Dưới</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="subtitle_size">Kích thước chữ (px)</label>
                                                <input type="number" name="subtitle_size" id="subtitle_size" class="form-control"
                                                       value="24">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="subtitle_color">Màu chữ</label>
                                                <input type="color" name="subtitle_color" id="subtitle_color" class="form-control" value="#FFFFFF">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="subtitle_background">Màu nền</label>
                                                <input type="color" name="subtitle_background" id="subtitle_background" class="form-control" value="#000000">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="subtitle_font">Font chữ</label>
                                        <select name="subtitle_font" id="subtitle_font" class="form-control">
                                            <option value="Arial">Arial</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Helvetica">Helvetica</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Verdana">Verdana</option>
                                        </select>
                                    </div>

                                    <!-- Channel Integration -->
                                    <h6 class="mt-4 mb-3">Tích hợp kênh</h6>
                                    <div class="form-group">
                                        <label for="default_channel_id">Kênh mặc định</label>
                                        <select name="default_channel_id" id="default_channel_id" class="form-control">
                                            <option value="">-- Không có kênh mặc định --</option>
                                            @if(isset($channels))
                                                @foreach($channels as $channel)
                                                    <option value="{{ $channel->id }}">{{ $channel->name }} ({{ ucfirst($channel->platform) }})</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <!-- Background Music Settings -->
                                    <h6 class="mt-4 mb-3">Nhạc nền mặc định</h6>
                                    <div class="form-group">
                                        <label for="background_music_type">Loại nhạc nền</label>
                                        <select name="background_music_type" id="background_music_type" class="form-control">
                                            <option value="none">Không sử dụng nhạc nền</option>
                                            <option value="upload">Upload file nhạc</option>
                                            <option value="library">Chọn từ thư viện</option>
                                            <option value="random">Random theo nhãn</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            Nhạc nền sẽ được sử dụng mặc định khi tạo video từ template này (có thể thay đổi khi tạo video)
                                        </small>
                                    </div>

                                    <!-- Upload Music File -->
                                    <div id="upload-music-section" style="display: none;">
                                        <div class="form-group">
                                            <label for="background_music_file">Upload file nhạc</label>
                                            <input type="file" name="background_music_file" id="background_music_file"
                                                   class="form-control-file" accept="audio/*">
                                            <small class="form-text text-muted">
                                                Hỗ trợ: MP3, WAV, AAC, OGG. Kích thước tối đa: 50MB
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Library Music Selection -->
                                    <div id="library-music-section" style="display: none;">
                                        <div class="form-group">
                                            <label for="background_music_library_id">Chọn từ thư viện</label>
                                            <select name="background_music_library_id" id="background_music_library_id" class="form-control">
                                                <option value="">-- Chọn nhạc từ thư viện --</option>
                                                @if(isset($audioLibrary))
                                                    @foreach($audioLibrary as $audio)
                                                        <option value="{{ $audio->id }}" data-duration="{{ $audio->duration }}" data-category="{{ $audio->category }}">
                                                            {{ $audio->title }} ({{ $audio->category }} - {{ $audio->duration }}s)
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Random Music Tag -->
                                    <div id="random-music-section" style="display: none;">
                                        <div class="form-group">
                                            <label for="background_music_random_tag">Nhãn nhạc random</label>
                                            <select name="background_music_random_tag" id="background_music_random_tag" class="form-control">
                                                <option value="">-- Chọn nhãn nhạc --</option>
                                                <option value="music">Nhạc nền chung</option>
                                                <option value="relaxing">Nhạc thư giãn</option>
                                                <option value="story">Nhạc cho truyện</option>
                                                <option value="upbeat">Nhạc sôi động</option>
                                                <option value="cinematic">Nhạc điện ảnh</option>
                                                <option value="nature">Âm thanh tự nhiên</option>
                                                <option value="corporate">Nhạc doanh nghiệp</option>
                                                <option value="emotional">Nhạc cảm xúc</option>
                                                <option value="action">Nhạc hành động</option>
                                                <option value="ambient">Nhạc không gian</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Hệ thống sẽ tự động chọn 1 bản nhạc ngẫu nhiên từ nhãn được chọn mỗi khi tạo video
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Music Volume -->
                                    <div id="music-volume-section" style="display: none;">
                                        <div class="form-group">
                                            <label for="background_music_volume">Âm lượng nhạc nền (%)</label>
                                            <input type="range" name="background_music_volume" id="background_music_volume"
                                                   class="form-control-range" min="0" max="100" value="30"
                                                   oninput="updateMusicVolumeValue(this.value)">
                                            <small class="form-text text-muted">Âm lượng: <span id="music-volume-value">30</span>%</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- JSON Settings Tab -->
                                <div class="tab-pane fade" id="json-settings" role="tabpanel">
                                    <div class="form-group">
                                        <label for="settings">Cài đặt JSON <span class="text-danger">*</span></label>
                                        <textarea name="settings" id="settings" class="form-control" rows="15"
                                                  placeholder='{"platform": "tiktok", "media_type": "images", ...}'>{{ old('settings') }}</textarea>
                                        <small class="form-text text-muted">
                                            Cài đặt sẽ được tự động tạo từ các tab trên. Bạn cũng có thể chỉnh sửa trực tiếp JSON ở đây.
                                        </small>
                                        @error('settings')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="btn-group mb-3">
                                        <button type="button" class="btn btn-outline-primary" onclick="generateJSONFromForm()">
                                            <i class="fas fa-magic mr-2"></i>Tạo JSON từ form
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="formatJSON()">
                                            <i class="fas fa-code mr-2"></i>Format JSON
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="validateJSON()">
                                            <i class="fas fa-check mr-2"></i>Kiểm tra JSON
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="createMinimalJSON()">
                                            <i class="fas fa-file mr-2"></i>JSON tối thiểu
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="button" class="btn btn-info mr-2" onclick="debugJSON()">
                                <i class="fas fa-search mr-2"></i>Debug JSON
                            </button>
                            <button type="button" class="btn btn-success mr-2" onclick="testBackgroundMusic()">
                                <i class="fas fa-music mr-2"></i>Test Music
                            </button>
                            <button type="button" class="btn btn-warning mr-2" onclick="testFetch()">
                                <i class="fas fa-bug mr-2"></i>Test Fetch
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Tạo Template
                            </button>
                            <a href="{{ route('admin.video-templates.index') }}" class="btn btn-secondary ml-2">
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
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Hướng dẫn</h6>
                </div>
                <div class="card-body">
                    <h6>Template là gì?</h6>
                    <p class="text-muted">Template là một bộ cài đặt có sẵn để tạo video với quy trình cố định. 
                    Người dùng chỉ cần nhập các thông tin cần thiết mà không phải cấu hình lại từ đầu.</p>
                    
                    <h6>Cách tạo template:</h6>
                    <ol class="text-muted">
                        <li>Điền thông tin cơ bản</li>
                        <li>Định nghĩa các input cần thiết</li>
                        <li>Cấu hình cài đặt mặc định</li>
                        <li>Lưu và test template</li>
                    </ol>
                    
                    <h6>Lưu ý:</h6>
                    <ul class="text-muted">
                        <li>Tên trường input phải unique</li>
                        <li>Cài đặt JSON phải hợp lệ</li>
                        <li>Test kỹ trước khi công khai</li>
                    </ul>
                </div>
            </div>

            <!-- JSON Examples -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-code mr-2"></i>Mẫu JSON Settings</h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="jsonExamples">
                        <!-- TikTok Template -->
                        <div class="card">
                            <div class="card-header p-2" id="tiktokExample">
                                <button class="btn btn-link btn-sm" type="button" data-toggle="collapse" data-target="#collapseTiktok">
                                    <i class="fas fa-video mr-1"></i>TikTok Template
                                </button>
                            </div>
                            <div id="collapseTiktok" class="collapse" data-parent="#jsonExamples">
                                <div class="card-body p-2">
                                    <pre class="bg-light p-2 small json-example" data-type="tiktok"><code>{
  "platform": "tiktok",
  "media_type": "images",
  "duration": 30,
  "transition_effect": "fade",
  "transition_duration": 0.5,
  "background_music": "random:relaxing",
  "subtitle": {
    "enabled": true,
    "position": "bottom",
    "font_size": 24,
    "color": "#ffffff"
  },
  "output_quality": "high"
}</code></pre>
                                    <button class="btn btn-sm btn-outline-primary copy-json" data-target="tiktok">
                                        <i class="fas fa-copy mr-1"></i>Copy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- YouTube Template -->
                        <div class="card">
                            <div class="card-header p-2" id="youtubeExample">
                                <button class="btn btn-link btn-sm" type="button" data-toggle="collapse" data-target="#collapseYoutube">
                                    <i class="fab fa-youtube mr-1"></i>YouTube Template
                                </button>
                            </div>
                            <div id="collapseYoutube" class="collapse" data-parent="#jsonExamples">
                                <div class="card-body p-2">
                                    <pre class="bg-light p-2 small json-example" data-type="youtube"><code>{
  "platform": "youtube",
  "media_type": "mixed",
  "duration": 60,
  "transition_effect": "slide",
  "transition_duration": 1.0,
  "background_music": "random:music",
  "subtitle": {
    "enabled": true,
    "position": "center",
    "font_size": 32,
    "color": "#ffff00"
  },
  "intro": {
    "enabled": true,
    "duration": 3
  },
  "outro": {
    "enabled": true,
    "duration": 3
  },
  "output_quality": "ultra"
}</code></pre>
                                    <button class="btn btn-sm btn-outline-primary copy-json" data-target="youtube">
                                        <i class="fas fa-copy mr-1"></i>Copy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Story Audio Template -->
                        <div class="card">
                            <div class="card-header p-2" id="storyExample">
                                <button class="btn btn-link btn-sm" type="button" data-toggle="collapse" data-target="#collapseStory">
                                    <i class="fas fa-book mr-1"></i>Story Audio Template
                                </button>
                            </div>
                            <div id="collapseStory" class="collapse" data-parent="#jsonExamples">
                                <div class="card-body p-2">
                                    <pre class="bg-light p-2 small json-example" data-type="story"><code>{
  "platform": "youtube",
  "media_type": "images",
  "duration": 120,
  "transition_effect": "fade",
  "transition_duration": 2.0,
  "background_music": "random:story",
  "audio_source": "library",
  "subtitle": {
    "enabled": false
  },
  "watermark": {
    "enabled": true,
    "position": "bottom-right",
    "opacity": 0.7
  },
  "output_quality": "high"
}</code></pre>
                                    <button class="btn btn-sm btn-outline-primary copy-json" data-target="story">
                                        <i class="fas fa-copy mr-1"></i>Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6 class="small">Random Background Music:</h6>
                        <ul class="small text-muted">
                            <li><code>"random:music"</code> - Nhạc nền ngẫu nhiên</li>
                            <li><code>"random:relaxing"</code> - Nhạc thư giãn</li>
                            <li><code>"random:story"</code> - Nhạc cho truyện</li>
                            <li><code>"random:upbeat"</code> - Nhạc sôi động</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Global functions for onclick handlers
window.testFetch = function() {
    console.log('Testing fetch API...');

    const form = document.getElementById('templateForm');
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('name', 'Test Template');
    formData.append('category', 'general');
    formData.append('settings', '{"test": true}');
    formData.append('required_inputs[0][name]', 'test_input');
    formData.append('required_inputs[0][type]', 'text');
    formData.append('required_inputs[0][label]', 'Test Input');

    console.log('Test form data prepared');

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        console.log('Test fetch response:', response.status, response.statusText);
        return response.text();
    })
    .then(text => {
        console.log('Test response body:', text);
        alert('Test fetch completed - check console');
    })
    .catch(error => {
        console.error('Test fetch error:', error);
        alert('Test fetch failed: ' + error.message);
    });
};

window.generateJSONFromForm = function() {
    try {
        console.log('Generating JSON from form...');

        // Helper function to safely get element value
        function getElementValue(id, defaultValue = '', type = 'value') {
            const element = document.getElementById(id);
            if (!element) {
                console.warn(`Element with id '${id}' not found, using default: ${defaultValue}`);
                return defaultValue;
            }

            if (type === 'checked') {
                return element.checked || false;
            } else if (type === 'int') {
                const value = parseInt(element.value);
                return isNaN(value) ? defaultValue : value;
            } else if (type === 'float') {
                const value = parseFloat(element.value);
                return isNaN(value) ? defaultValue : value;
            } else {
                return element.value || defaultValue;
            }
        }

        const settings = {
            // Basic settings
            platform: getElementValue('platform', 'none'),
            media_type: getElementValue('media_type', 'images'),
            duration_based_on: getElementValue('duration_based_on', 'images'),
            custom_duration: getElementValue('custom_duration', 30, 'int'),
            image_duration: getElementValue('image_duration', 3, 'float'),
            transition_effect: getElementValue('transition_effect', 'fade'),
            sync_with_audio: getElementValue('sync_with_audio', false, 'checked'),

            // Video settings
            resolution: getElementValue('resolution', '1920x1080'),
            fps: getElementValue('fps', 30, 'int'),
            quality: getElementValue('quality', 'high'),
            video_sections: getElementValue('video_sections', 'none'),
            image_position: getElementValue('image_position', 'center'),
            image_scale: getElementValue('image_scale', 100, 'int'),
            image_opacity: getElementValue('image_opacity', 100, 'int'),
            image_effect: getElementValue('image_effect', 'none'),

            // Audio settings
            audio_source: getElementValue('audio_source', 'none'),
            audio_volume: getElementValue('audio_volume', 18, 'int'),
            tts_voice: getElementValue('tts_voice', 'vi-VN-HoaiMyNeural'),
            tts_speed: getElementValue('tts_speed', 1.0, 'float'),
            tts_volume: getElementValue('tts_volume', 18, 'int'),
            tts_bitrate: getElementValue('tts_bitrate', 128, 'int'),
            background_audio_volume: getElementValue('background_audio_volume', 30, 'int'),
            audio_mixing: getElementValue('audio_mixing', 'overlay'),
            normalize_audio: getElementValue('normalize_audio', true, 'checked'),

            // Logo settings
            enable_logo: getElementValue('enable_logo', false, 'checked'),
            logo_position: getElementValue('logo_position', 'bottom-right'),
            logo_size: getElementValue('logo_size', 100, 'int'),
            logo_opacity: getElementValue('logo_opacity', 90, 'int'),
            logo_margin: getElementValue('logo_margin', 20, 'int'),
            logo_timing: getElementValue('logo_timing', 'full'),
            logo_effect: getElementValue('logo_effect', 'none'),

            // Advanced settings
            content_behavior: getElementValue('content_behavior', 'loop'),
            max_duration: getElementValue('max_duration', 300, 'int'),
            min_duration: getElementValue('min_duration', 10, 'int'),
            auto_adjust_images: getElementValue('auto_adjust_images', false, 'checked'),
            subtitle_position: getElementValue('subtitle_position', 'bottom'),
            subtitle_size: getElementValue('subtitle_size', 24, 'int'),
            subtitle_color: getElementValue('subtitle_color', '#FFFFFF'),
            subtitle_background: getElementValue('subtitle_background', '#000000'),
            subtitle_font: getElementValue('subtitle_font', 'Arial')
        };

        // Add custom resolution if selected
        if (settings.resolution === 'custom') {
            settings.custom_width = getElementValue('custom_width', 1920, 'int');
            settings.custom_height = getElementValue('custom_height', 1080, 'int');
        }

        // Add section settings if enabled
        if (settings.video_sections === 'time' || settings.video_sections === 'manual') {
            settings.section_duration = getElementValue('section_duration', 10, 'int');
            settings.section_transition = getElementValue('section_transition', 'fade');
        }

        // Add logo duration if custom timing
        if (settings.logo_timing === 'custom') {
            settings.logo_duration = getElementValue('logo_duration', 10, 'int');
        }

        console.log('Generated settings:', settings);

        // Update JSON textarea
        const settingsTextarea = document.getElementById('settings');
        if (settingsTextarea) {
            settingsTextarea.value = JSON.stringify(settings, null, 2);
            console.log('JSON updated in textarea');
        } else {
            console.error('Settings textarea not found!');
        }

    } catch (error) {
        console.error('Error generating JSON from form:', error);
        alert('Lỗi khi tạo JSON settings: ' + error.message);

        // Fallback: create minimal valid JSON
        const settingsTextarea = document.getElementById('settings');
        if (settingsTextarea) {
            settingsTextarea.value = JSON.stringify({
                platform: 'tiktok',
                media_type: 'images',
                duration_based_on: 'images',
                custom_duration: 30,
                image_duration: 3,
                transition_effect: 'fade'
            }, null, 2);
        }
    }
};

window.formatJSON = function() {
    const textarea = document.getElementById('settings');
    try {
        const parsed = JSON.parse(textarea.value);
        textarea.value = JSON.stringify(parsed, null, 2);
        showAlert('JSON đã được format thành công!', 'success');
    } catch (e) {
        showAlert('JSON không hợp lệ: ' + e.message, 'danger');
    }
};

window.validateJSON = function() {
    const textarea = document.getElementById('settings');
    try {
        const parsed = JSON.parse(textarea.value);
        console.log('JSON validation successful:', parsed);
        showAlert('JSON hợp lệ!', 'success');
    } catch (e) {
        console.error('JSON validation error:', e);
        console.error('Invalid JSON content:', textarea.value);
        showAlert('JSON không hợp lệ: ' + e.message, 'danger');
    }
};

window.createMinimalJSON = function() {
    const minimalSettings = {
        platform: 'tiktok',
        media_type: 'images',
        duration_based_on: 'images',
        custom_duration: 30,
        image_duration: 3,
        transition_effect: 'fade',
        sync_with_audio: false,
        resolution: '1920x1080',
        fps: 30,
        quality: 'high',
        audio_source: 'none',
        audio_volume: 18
    };

    const textarea = document.getElementById('settings');
    textarea.value = JSON.stringify(minimalSettings, null, 2);
    showAlert('JSON tối thiểu đã được tạo!', 'success');
    console.log('Minimal JSON created:', minimalSettings);
};

window.debugJSON = function() {
    console.log('=== DEBUG JSON FUNCTION ===');

    const textarea = document.getElementById('settings');
    console.log('Textarea element:', textarea);
    console.log('Textarea exists:', !!textarea);

    if (!textarea) {
        alert('Settings textarea not found!');
        return;
    }

    console.log('Textarea value:', textarea.value);
    console.log('Textarea value length:', textarea.value.length);
    console.log('Textarea value type:', typeof textarea.value);
    console.log('Textarea value (JSON string):', JSON.stringify(textarea.value));

    if (!textarea.value || textarea.value.trim() === '') {
        alert('Textarea is empty! Generating JSON first...');
        window.generateJSONFromForm();
        return;
    }

    try {
        const parsed = JSON.parse(textarea.value);
        console.log('JSON parse successful:', parsed);
        console.log('Parsed object keys:', Object.keys(parsed));
        alert('JSON is valid! Check console for details.');
    } catch (error) {
        console.error('JSON parse failed:', error);
        console.error('Error at position:', error.message.match(/position (\d+)/)?.[1]);

        // Show problematic part
        const match = error.message.match(/position (\d+)/);
        if (match) {
            const pos = parseInt(match[1]);
            const start = Math.max(0, pos - 20);
            const end = Math.min(textarea.value.length, pos + 20);
            console.error('Problematic area:', textarea.value.substring(start, end));
        }

        alert('JSON is invalid: ' + error.message + '\nCheck console for details.');
    }
};

window.testBackgroundMusic = function() {
    console.log('=== TESTING BACKGROUND MUSIC ===');

    const select = document.getElementById('background_music_type');
    console.log('Background music select:', select);
    console.log('Current value:', select ? select.value : 'NOT FOUND');

    const sections = {
        upload: document.getElementById('upload-music-section'),
        library: document.getElementById('library-music-section'),
        random: document.getElementById('random-music-section'),
        volume: document.getElementById('music-volume-section')
    };

    console.log('Sections found:', {
        upload: !!sections.upload,
        library: !!sections.library,
        random: !!sections.random,
        volume: !!sections.volume
    });

    if (select) {
        console.log('Setting to random and triggering change...');
        select.value = 'random';
        select.dispatchEvent(new Event('change'));
    }

    alert('Background music test completed - check console');
};
</script>

<script>
// Error handling
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('JavaScript Error:', msg, 'at', url, ':', lineNo);
    alert('JavaScript Error: ' + msg);
    return false;
};

// Form Builder JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing form...');

    // Initialize form interactions
    initializeFormInteractions();

    // Auto-generate JSON when form changes
    setupAutoJSONGeneration();

    // Setup form validation
    setupFormValidation();

    // Generate initial JSON with default values after a delay
    setTimeout(() => {
        console.log('Generating initial JSON...');
        window.generateJSONFromForm();

        // Also initialize background music state
        const backgroundMusicSelect = document.getElementById('background_music_type');
        if (backgroundMusicSelect) {
            console.log('Initializing background music state...');
            backgroundMusicSelect.dispatchEvent(new Event('change'));
        }
    }, 500);

    console.log('Form initialization complete');
});

function setupFormValidation() {
    const form = document.getElementById('templateForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submit triggered');
            alert('Form submit triggered - check console for details');

            // Auto-generate JSON before submit if empty
            const settingsTextarea = document.getElementById('settings');
            if (!settingsTextarea.value.trim()) {
                console.log('Settings empty, generating JSON...');
                window.generateJSONFromForm();

                // Double check after generation
                if (!settingsTextarea.value.trim()) {
                    console.log('JSON generation failed, using minimal fallback...');
                    window.createMinimalJSON();
                }
            }

            console.log('Settings value:', settingsTextarea.value);

            // Validate JSON
            try {
                console.log('=== JSON VALIDATION START ===');
                console.log('Settings textarea element:', settingsTextarea);
                console.log('Settings textarea value length:', settingsTextarea.value.length);
                console.log('Settings textarea value (first 200 chars):', settingsTextarea.value.substring(0, 200));
                console.log('Settings textarea value (raw):', JSON.stringify(settingsTextarea.value.substring(0, 100)));

                if (!settingsTextarea.value || settingsTextarea.value.trim() === '') {
                    console.error('Settings JSON is empty or whitespace only');
                    throw new Error('Settings JSON is empty');
                }

                console.log('Attempting to parse JSON...');
                const settings = JSON.parse(settingsTextarea.value);
                console.log('JSON.parse successful');

                if (!settings || typeof settings !== 'object') {
                    console.error('Settings is not a valid object:', typeof settings, settings);
                    throw new Error('Settings must be a valid JSON object');
                }

                console.log('JSON validation passed');
                console.log('Parsed settings keys:', Object.keys(settings));
                console.log('Parsed settings (first few):', {
                    platform: settings.platform,
                    media_type: settings.media_type,
                    duration_based_on: settings.duration_based_on
                });
            } catch (error) {
                console.error('=== JSON VALIDATION FAILED ===');
                console.error('Error type:', error.constructor.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                console.error('Raw textarea value (full):', settingsTextarea.value);
                console.error('Textarea value as JSON string:', JSON.stringify(settingsTextarea.value));

                e.preventDefault();
                showAlert('Cài đặt JSON không hợp lệ: ' + error.message, 'danger');

                // Switch to JSON tab to show error
                const jsonTab = document.getElementById('json-tab');
                if (jsonTab) {
                    jsonTab.click();
                }

                // Focus on settings textarea
                settingsTextarea.focus();
                return false;
            }

            // Validate form fields with custom validation
            const validationErrors = validateFormFields();
            if (validationErrors.length > 0) {
                e.preventDefault();
                showAlert('Vui lòng kiểm tra các trường sau: ' + validationErrors.join(', '), 'danger');
                return false;
            }

            console.log('Form validation passed, submitting...');
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);

            // Log form data
            const formData = new FormData(form);
            console.log('Form data entries:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }

            // Submit form using fetch API
            console.log('About to submit form using fetch...');

            // Add a loading indicator
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang tạo template...';
            }

            // Alternative approach: Create FormData manually
            const formData = new FormData();

            // Add all form fields manually
            const formElements = form.querySelectorAll('input, select, textarea');
            formElements.forEach(element => {
                if (element.name && element.type !== 'file') {
                    if (element.type === 'checkbox') {
                        if (element.checked) {
                            formData.append(element.name, element.value || 'on');
                        }
                    } else if (element.type === 'radio') {
                        if (element.checked) {
                            formData.append(element.name, element.value);
                        }
                    } else {
                        formData.append(element.name, element.value);
                    }
                }
            });

            // Handle file inputs separately
            const fileInputs = form.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                if (input.files.length > 0) {
                    formData.append(input.name, input.files[0]);
                }
            });

            // Manually ensure settings is included with correct value
            const settingsValue = settingsTextarea.value;
            formData.set('settings', settingsValue);

            console.log('Manual FormData creation completed');

            console.log('Submitting to:', form.action);
            console.log('Form data prepared');
            console.log('Settings manually set to:', settingsValue.substring(0, 100) + '...');

            // Debug form data
            console.log('=== FORM DATA DEBUG ===');
            let hasSettings = false;
            for (let [key, value] of formData.entries()) {
                if (key === 'settings') {
                    hasSettings = true;
                    console.log('✓ settings key found in FormData');
                    console.log('settings value type:', typeof value);
                    console.log('settings value length:', value.length);
                    console.log('settings value (first 200 chars):', value.substring(0, 200));

                    // Verify it's valid JSON
                    try {
                        const parsed = JSON.parse(value);
                        console.log('✓ settings is valid JSON');
                        console.log('settings parsed keys:', Object.keys(parsed));
                    } catch (e) {
                        console.error('✗ settings is NOT valid JSON:', e.message);
                    }
                } else {
                    console.log(key + ':', typeof value === 'string' ? value.substring(0, 50) : value);
                }
            }

            if (!hasSettings) {
                console.error('✗ settings key NOT found in FormData!');
            }

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                   document.querySelector('input[name="_token"]')?.value
                }
            })
            .then(response => {
                console.log('Fetch response received:', response.status, response.statusText);

                if (response.ok) {
                    console.log('Success! Redirecting...');
                    // Check if response is JSON (validation errors) or redirect
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().then(data => {
                            console.log('JSON response:', data);
                            if (data.errors) {
                                alert('Validation errors: ' + JSON.stringify(data.errors));
                            }
                        });
                    } else {
                        // Successful creation, redirect
                        window.location.href = '/admin/video-templates';
                    }
                } else {
                    console.error('HTTP error:', response.status, response.statusText);
                    return response.text().then(text => {
                        console.error('Error response body:', text);
                        alert('Server error: ' + response.status + ' - ' + response.statusText);
                    });
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Network error: ' + error.message);

                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Tạo Template';
                }
            });

            return false; // Prevent default form submission
        });
    }
}

function initializeFormInteractions() {
    // Resolution change handler
    document.getElementById('resolution').addEventListener('change', function() {
        const customResolution = document.getElementById('custom-resolution');
        if (this.value === 'custom') {
            customResolution.style.display = 'block';
        } else {
            customResolution.style.display = 'none';
        }
    });

    // Video sections handler
    document.getElementById('video_sections').addEventListener('change', function() {
        const sectionSettings = document.getElementById('section-settings');
        if (this.value === 'time' || this.value === 'manual') {
            sectionSettings.style.display = 'block';
        } else {
            sectionSettings.style.display = 'none';
        }
    });

    // Audio source handler
    document.getElementById('audio_source').addEventListener('change', function() {
        const ttsSettings = document.getElementById('tts-settings');
        if (this.value === 'tts') {
            ttsSettings.style.display = 'block';
        } else {
            ttsSettings.style.display = 'none';
        }
    });

    // Logo enable handler
    document.getElementById('enable_logo').addEventListener('change', function() {
        const logoConfig = document.getElementById('logo-config');
        if (this.checked) {
            logoConfig.style.display = 'block';
        } else {
            logoConfig.style.display = 'none';
        }
    });

    // Logo timing handler
    document.getElementById('logo_timing').addEventListener('change', function() {
        const customTiming = document.getElementById('logo-custom-timing');
        if (this.value === 'custom') {
            customTiming.style.display = 'block';
        } else {
            customTiming.style.display = 'none';
        }
    });

    // Logo position preview
    document.getElementById('logo_position').addEventListener('change', updateLogoPreview);
    document.getElementById('logo_size').addEventListener('input', updateLogoPreview);
    document.getElementById('logo_opacity').addEventListener('input', updateLogoPreview);

    // Background music type handler with error handling
    const backgroundMusicSelect = document.getElementById('background_music_type');
    if (backgroundMusicSelect) {
        console.log('Setting up background music handler');
        backgroundMusicSelect.addEventListener('change', function() {
            console.log('Background music type changed to:', this.value);

            const uploadSection = document.getElementById('upload-music-section');
            const librarySection = document.getElementById('library-music-section');
            const randomSection = document.getElementById('random-music-section');
            const volumeSection = document.getElementById('music-volume-section');

            console.log('Found sections:', {
                upload: !!uploadSection,
                library: !!librarySection,
                random: !!randomSection,
                volume: !!volumeSection
            });

            // Hide all sections first
            if (uploadSection) uploadSection.style.display = 'none';
            if (librarySection) librarySection.style.display = 'none';
            if (randomSection) randomSection.style.display = 'none';
            if (volumeSection) volumeSection.style.display = 'none';

            // Show relevant section based on selection
            switch(this.value) {
                case 'upload':
                    if (uploadSection) {
                        uploadSection.style.display = 'block';
                        console.log('Showing upload section');
                    }
                    if (volumeSection) {
                        volumeSection.style.display = 'block';
                        console.log('Showing volume section');
                    }
                    break;
                case 'library':
                    if (librarySection) {
                        librarySection.style.display = 'block';
                        console.log('Showing library section');
                    }
                    if (volumeSection) {
                        volumeSection.style.display = 'block';
                        console.log('Showing volume section');
                    }
                    break;
                case 'random':
                    if (randomSection) {
                        randomSection.style.display = 'block';
                        console.log('Showing random section');
                    }
                    if (volumeSection) {
                        volumeSection.style.display = 'block';
                        console.log('Showing volume section');
                    }
                    break;
                default:
                    console.log('No music selected, hiding all sections');
            }
        });

        // Trigger change event to set initial state
        backgroundMusicSelect.dispatchEvent(new Event('change'));
    } else {
        console.error('Background music select element not found!');
    }
}

function setupAutoJSONGeneration() {
    // Auto-generate JSON when switching to JSON tab
    document.getElementById('json-tab').addEventListener('click', function() {
        setTimeout(generateJSONFromForm, 100);
    });

    // Auto-generate JSON when form fields change
    const formFields = [
        'platform', 'media_type', 'duration_based_on', 'custom_duration',
        'image_duration', 'transition_effect', 'sync_with_audio',
        'resolution', 'fps', 'quality', 'video_sections',
        'image_position', 'image_scale', 'image_opacity', 'image_effect',
        'audio_source', 'audio_volume', 'tts_voice', 'tts_speed',
        'enable_logo', 'logo_position', 'logo_size', 'logo_opacity'
    ];

    formFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                // Debounce to avoid too many updates
                clearTimeout(window.jsonUpdateTimeout);
                window.jsonUpdateTimeout = setTimeout(generateJSONFromForm, 500);
            });
        }
    });
}

function updateScaleValue(value) {
    document.getElementById('scale-value').textContent = value;
}

function updateOpacityValue(value) {
    document.getElementById('opacity-value').textContent = value;
}

function updateBgVolumeValue(value) {
    document.getElementById('bg-volume-value').textContent = value;
}

function updateLogoOpacityValue(value) {
    document.getElementById('logo-opacity-value').textContent = value;
}

function updateMusicVolumeValue(value) {
    document.getElementById('music-volume-value').textContent = value;
}

function updateLogoPreview() {
    const preview = document.getElementById('logo-preview');
    const position = document.getElementById('logo_position').value;
    const size = document.getElementById('logo_size').value;
    const opacity = document.getElementById('logo_opacity').value / 100;

    // Update size
    const scaledSize = Math.max(20, size / 3); // Scale down for preview
    preview.style.width = scaledSize + 'px';
    preview.style.height = scaledSize + 'px';
    preview.style.opacity = opacity;

    // Update position
    const container = preview.parentElement;
    const margin = 10;

    // Reset position
    preview.style.top = 'auto';
    preview.style.bottom = 'auto';
    preview.style.left = 'auto';
    preview.style.right = 'auto';

    switch(position) {
        case 'top-left':
            preview.style.top = margin + 'px';
            preview.style.left = margin + 'px';
            break;
        case 'top-center':
            preview.style.top = margin + 'px';
            preview.style.left = '50%';
            preview.style.transform = 'translateX(-50%)';
            break;
        case 'top-right':
            preview.style.top = margin + 'px';
            preview.style.right = margin + 'px';
            break;
        case 'center-left':
            preview.style.top = '50%';
            preview.style.left = margin + 'px';
            preview.style.transform = 'translateY(-50%)';
            break;
        case 'center':
            preview.style.top = '50%';
            preview.style.left = '50%';
            preview.style.transform = 'translate(-50%, -50%)';
            break;
        case 'center-right':
            preview.style.top = '50%';
            preview.style.right = margin + 'px';
            preview.style.transform = 'translateY(-50%)';
            break;
        case 'bottom-left':
            preview.style.bottom = margin + 'px';
            preview.style.left = margin + 'px';
            break;
        case 'bottom-center':
            preview.style.bottom = margin + 'px';
            preview.style.left = '50%';
            preview.style.transform = 'translateX(-50%)';
            break;
        case 'bottom-right':
        default:
            preview.style.bottom = margin + 'px';
            preview.style.right = margin + 'px';
            break;
    }
}

// Duplicate functions removed - using global window functions instead

function validateFormFields() {
    const errors = [];

    // Validate required basic fields
    const requiredFields = [
        { id: 'name', name: 'Tên template' },
        { id: 'category', name: 'Danh mục' }
    ];

    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            errors.push(`${field.name} là bắt buộc`);
        }
    });

    // Validate required inputs
    const requiredInputsContainer = document.getElementById('required-inputs-container');
    const requiredInputItems = requiredInputsContainer.querySelectorAll('.input-item');

    if (requiredInputItems.length === 0) {
        errors.push('Phải có ít nhất 1 input bắt buộc');
    } else {
        requiredInputItems.forEach((item, index) => {
            const nameInput = item.querySelector('input[name*="[name]"]');
            const typeSelect = item.querySelector('select[name*="[type]"]');
            const labelInput = item.querySelector('input[name*="[label]"]');

            if (!nameInput || !nameInput.value.trim()) {
                errors.push(`Tên trường input bắt buộc ${index + 1} không được để trống`);
            }
            if (!typeSelect || !typeSelect.value.trim()) {
                errors.push(`Loại input bắt buộc ${index + 1} phải được chọn`);
            }
            if (!labelInput || !labelInput.value.trim()) {
                errors.push(`Nhãn hiển thị input bắt buộc ${index + 1} không được để trống`);
            }
        });
    }

    // Validate number fields with ranges
    const numberFields = [
        { id: 'custom_duration', min: 5, max: 300, name: 'Độ dài tùy chỉnh' },
        { id: 'image_duration', min: 1, max: 10, name: 'Thời gian hiển thị ảnh' },
        { id: 'custom_width', min: 480, max: 3840, name: 'Chiều rộng' },
        { id: 'custom_height', min: 480, max: 2160, name: 'Chiều cao' },
        { id: 'section_duration', min: 3, max: 30, name: 'Thời gian mỗi quãng' },
        { id: 'audio_volume', min: -20, max: 20, name: 'Âm lượng audio' },
        { id: 'tts_volume', min: -20, max: 20, name: 'Âm lượng TTS' },
        { id: 'logo_size', min: 50, max: 300, name: 'Kích thước logo' },
        { id: 'logo_margin', min: 5, max: 100, name: 'Khoảng cách logo' },
        { id: 'logo_duration', min: 1, max: 60, name: 'Thời gian hiển thị logo' },
        { id: 'max_duration', min: 10, max: 600, name: 'Độ dài tối đa' },
        { id: 'min_duration', min: 5, max: 60, name: 'Độ dài tối thiểu' },
        { id: 'subtitle_size', min: 12, max: 72, name: 'Kích thước chữ phụ đề' }
    ];

    numberFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (element && element.value) {
            const value = parseFloat(element.value);
            if (value < field.min || value > field.max) {
                errors.push(`${field.name} phải từ ${field.min} đến ${field.max}`);
            }
        }
    });

    // Validate range fields
    const rangeFields = [
        { id: 'image_scale', min: 50, max: 150, name: 'Kích thước ảnh' },
        { id: 'image_opacity', min: 10, max: 100, name: 'Độ trong suốt ảnh' },
        { id: 'background_audio_volume', min: 0, max: 100, name: 'Âm lượng nhạc nền' },
        { id: 'logo_opacity', min: 10, max: 100, name: 'Độ trong suốt logo' },
        { id: 'background_music_volume', min: 0, max: 100, name: 'Âm lượng nhạc nền template' }
    ];

    rangeFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (element && element.value) {
            const value = parseInt(element.value);
            if (value < field.min || value > field.max) {
                errors.push(`${field.name} phải từ ${field.min} đến ${field.max}`);
            }
        }
    });

    return errors;
}

// testFetch moved to global window scope above

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush

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

.json-example {
    font-size: 11px;
    line-height: 1.3;
    max-height: 200px;
    overflow-y: auto;
}

.json-example code {
    white-space: pre-wrap;
    word-break: break-word;
}

.accordion .card {
    border: 1px solid #e3e6f0;
    margin-bottom: 0.5rem;
}

.accordion .card-header {
    background: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.accordion .btn-link {
    color: #5a5c69;
    text-decoration: none;
    font-weight: 500;
}

.accordion .btn-link:hover {
    color: #3a3b45;
    text-decoration: none;
}

.copy-json {
    font-size: 12px;
}
</style>
@endpush

@push('scripts')
<script>
let requiredInputIndex = 1;
let optionalInputIndex = 0;

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
                               placeholder="vd: script_text">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Loại input</label>
                        <select name="${type}[${index}][type]" class="form-control">
                            ${optionsHtml}
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nhãn hiển thị</label>
                        <input type="text" name="${type}[${index}][label]" class="form-control"
                               placeholder="vd: Nội dung kịch bản">
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
            `;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openSettingsBuilder() {
    // Open video generator in new tab for settings building
    window.open('{{ route("admin.video-generator.index") }}', '_blank');
    alert('Sử dụng form tạo video để cấu hình, sau đó copy JSON settings về đây.');
}

// Copy JSON example
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('copy-json') || e.target.closest('.copy-json')) {
        const button = e.target.closest('.copy-json');
        const target = button.getAttribute('data-target');
        const jsonExample = document.querySelector(`.json-example[data-type="${target}"] code`);

        if (jsonExample) {
            const jsonText = jsonExample.textContent;
            navigator.clipboard.writeText(jsonText).then(() => {
                // Update settings textarea
                document.getElementById('settings').value = jsonText;

                // Show success feedback
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-success');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                }, 2000);
            });
        }
    }
});

// Generate random background music
async function generateRandomMusic(tag = 'music') {
    const settingsTextarea = document.getElementById('settings');
    let settings;

    try {
        settings = JSON.parse(settingsTextarea.value || '{}');
    } catch (error) {
        settings = {};
    }

    // Show loading
    const button = event.target.closest('button') || event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang tìm...';
    button.disabled = true;

    try {
        // Fetch random audio from library with tag
        const response = await fetch(`/admin/api/audio-library/random-background-music?tag=${tag}`);
        const data = await response.json();

        if (data.success && data.audio) {
            // Update settings with random audio
            settings.background_music = `library:${data.audio.id}`;
            settings.background_music_info = {
                id: data.audio.id,
                title: data.audio.title,
                file_path: data.audio.file_path,
                duration: data.audio.duration,
                category: data.audio.category,
                tags: data.audio.tags
            };

            settingsTextarea.value = JSON.stringify(settings, null, 2);

            // Show success message with tag info
            const tagNames = {
                'music': 'Nhạc nền chung',
                'relaxing': 'Nhạc thư giãn',
                'story': 'Nhạc cho truyện',
                'upbeat': 'Nhạc sôi động',
                'cinematic': 'Nhạc điện ảnh',
                'nature': 'Âm thanh tự nhiên'
            };

            alert(`Đã chọn ${tagNames[tag] || tag}: "${data.audio.title}"`);
        } else {
            alert(`Không tìm thấy nhạc nền phù hợp với tag "${tag}" trong thư viện.`);
        }
    } catch (error) {
        console.error('Error fetching random music:', error);
        alert('Có lỗi khi tìm nhạc nền. Vui lòng thử lại.');
    } finally {
        // Restore button
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Validate JSON
function validateJSON() {
    const settingsInput = document.getElementById('settings');
    const button = event.target;

    try {
        const parsed = JSON.parse(settingsInput.value);

        // Show success
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Hợp lệ!';
        button.classList.remove('btn-outline-warning');
        button.classList.add('btn-success');

        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-warning');
        }, 2000);

        // Pretty format JSON
        settingsInput.value = JSON.stringify(parsed, null, 2);

    } catch (error) {
        alert('JSON không hợp lệ: ' + error.message);
        settingsInput.focus();
    }
}

// Validate JSON before submit
document.getElementById('templateForm').addEventListener('submit', function(e) {
    const settingsInput = document.getElementById('settings');
    try {
        JSON.parse(settingsInput.value);
    } catch (error) {
        e.preventDefault();
        alert('Cài đặt JSON không hợp lệ. Vui lòng kiểm tra lại.');
        settingsInput.focus();
    }
});
</script>
@endpush
