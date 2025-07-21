<!-- Media Content Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-images mr-2"></i>Nội dung hình ảnh/video</h6>
    </div>
    <div class="card-body">
        <!-- Media Type Selection -->
        <div class="form-group">
            <label class="form-label">Loại nội dung</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-info active mr-2 mb-2">
                    <input type="radio" name="media_type" value="images" checked> 
                    <i class="fas fa-image mr-1"></i>Hình ảnh
                </label>
                <label class="btn btn-outline-info mr-2 mb-2">
                    <input type="radio" name="media_type" value="video"> 
                    <i class="fas fa-video mr-1"></i>Video
                </label>
                <label class="btn btn-outline-info mb-2">
                    <input type="radio" name="media_type" value="mixed"> 
                    <i class="fas fa-layer-group mr-1"></i>Hỗn hợp
                </label>
            </div>
        </div>

        <!-- Images Section -->
        <div id="images-section">
            <div class="form-group">
                <label for="images">Chọn hình ảnh *</label>
                <input type="file" name="images[]" id="images" class="form-control-file"
                       accept="image/*" multiple onchange="previewImagesAdvanced(this)">
                <small class="form-text text-muted">
                    Chọn nhiều ảnh (JPG, PNG, GIF). Tối đa 20 ảnh, mỗi ảnh tối đa 10MB
                </small>
                <div id="images-preview" class="mt-3"></div>
            </div>

            <!-- Individual Image Settings -->
            <div id="individual-image-settings" style="display: none;">
                <h6 class="mb-3"><i class="fas fa-cog mr-2"></i>Cài đặt từng ảnh</h6>
                <div id="image-items-container">
                    <!-- Individual image items will be populated here -->
                </div>
            </div>

            <!-- Global Image Settings -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Cài đặt chung cho tất cả ảnh</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="default_image_duration">Thời gian hiển thị mặc định (giây)</label>
                                <input type="number" name="default_image_duration" id="default_image_duration"
                                       class="form-control" min="0.5" max="30" step="0.5" value="3">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="default_transition_effect">Hiệu ứng chuyển cảnh mặc định</label>
                                <select name="default_transition_effect" id="default_transition_effect" class="form-control">
                                    <option value="fade">Fade</option>
                                    <option value="slide" selected>Slide</option>
                                    <option value="zoom">Zoom</option>
                                    <option value="dissolve">Dissolve</option>
                                    <option value="wipe">Wipe</option>
                                    <option value="none">Không có</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="transition_duration">Thời gian hiệu ứng (giây)</label>
                                <input type="number" name="transition_duration" id="transition_duration"
                                       class="form-control" min="0.1" max="2" step="0.1" value="0.5">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-primary" onclick="applyGlobalImageSettings()">
                            <i class="fas fa-magic mr-1"></i>Áp dụng cho tất cả ảnh
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ml-2" onclick="toggleIndividualSettings()">
                            <i class="fas fa-cogs mr-1"></i>Tùy chỉnh từng ảnh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Section -->
        <div id="video-section" style="display: none;">
            <div class="form-group">
                <label for="background_video">Chọn video nền *</label>
                <input type="file" name="background_video" id="background_video" 
                       class="form-control-file" accept="video/*" onchange="previewVideo(this)">
                <small class="form-text text-muted">
                    MP4, AVI, MOV. Tối đa 500MB
                </small>
                <div id="video-preview" class="mt-3"></div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" name="remove_video_audio" id="remove_video_audio" 
                       class="form-check-input" value="1" checked>
                <label class="form-check-label" for="remove_video_audio">
                    Xóa âm thanh của video nền
                </label>
            </div>
        </div>

        <!-- Mixed Section -->
        <div id="mixed-section" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Chế độ hỗn hợp:</strong> Kết hợp ảnh và video với nhiều tùy chọn hiển thị.
            </div>

            <!-- Mixed Mode Selection -->
            <div class="form-group">
                <label class="form-label">Chế độ hiển thị</label>
                <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                    <label class="btn btn-outline-info active mr-2 mb-2">
                        <input type="radio" name="mixed_mode" value="sequence" checked>
                        <i class="fas fa-list mr-1"></i>Xen kẽ tuần tự
                    </label>
                    <label class="btn btn-outline-info mr-2 mb-2">
                        <input type="radio" name="mixed_mode" value="overlay">
                        <i class="fas fa-layer-group mr-1"></i>Ảnh đè lên video
                    </label>
                    <label class="btn btn-outline-info mb-2">
                        <input type="radio" name="mixed_mode" value="split">
                        <i class="fas fa-columns mr-1"></i>Chia màn hình
                    </label>
                </div>
            </div>

            <!-- File Upload -->
            <div class="form-group">
                <label>Chọn hình ảnh và video</label>
                <input type="file" name="mixed_media[]" id="mixed_media"
                       class="form-control-file" accept="image/*,video/*" multiple
                       onchange="previewMixedMediaAdvanced(this)">
                <small class="form-text text-muted">
                    Chọn nhiều file (ảnh: JPG, PNG, GIF; video: MP4, AVI, MOV)
                </small>
                <div id="mixed-preview" class="mt-3"></div>
            </div>

            <!-- Sequence Mode Settings -->
            <div id="sequence-mode-settings">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Cài đặt xen kẽ tuần tự</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sequence_image_duration">Thời gian hiển thị ảnh (giây)</label>
                                    <input type="number" name="sequence_image_duration" id="sequence_image_duration"
                                           class="form-control" min="1" max="30" value="4">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sequence_video_duration">Thời gian hiển thị video (giây)</label>
                                    <select name="sequence_video_duration" id="sequence_video_duration" class="form-control">
                                        <option value="full">Toàn bộ video</option>
                                        <option value="5">5 giây</option>
                                        <option value="10" selected>10 giây</option>
                                        <option value="15">15 giây</option>
                                        <option value="custom">Tùy chỉnh</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="custom-video-duration" style="display: none;">
                            <div class="form-group">
                                <label for="custom_video_seconds">Thời gian video tùy chỉnh (giây)</label>
                                <input type="number" name="custom_video_seconds" id="custom_video_seconds"
                                       class="form-control" min="1" max="300" value="8">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overlay Mode Settings -->
            <div id="overlay-mode-settings" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Cài đặt ảnh đè lên video</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="overlay_position">Vị trí ảnh</label>
                                    <select name="overlay_position" id="overlay_position" class="form-control">
                                        <option value="top-left">Góc trên trái</option>
                                        <option value="top-right" selected>Góc trên phải</option>
                                        <option value="top-center">Trên giữa</option>
                                        <option value="bottom-left">Góc dưới trái</option>
                                        <option value="bottom-right">Góc dưới phải</option>
                                        <option value="bottom-center">Dưới giữa</option>
                                        <option value="center">Giữa màn hình</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="overlay_size">Kích thước ảnh</label>
                                    <select name="overlay_size" id="overlay_size" class="form-control">
                                        <option value="small">Nhỏ (20% màn hình)</option>
                                        <option value="medium" selected>Vừa (30% màn hình)</option>
                                        <option value="large">Lớn (40% màn hình)</option>
                                        <option value="custom">Tùy chỉnh</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="overlay_opacity">Độ trong suốt ảnh</label>
                                    <select name="overlay_opacity" id="overlay_opacity" class="form-control">
                                        <option value="0.7">70%</option>
                                        <option value="0.8">80%</option>
                                        <option value="0.9" selected>90%</option>
                                        <option value="1.0">100% (Đậm)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="custom-overlay-size" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="overlay_width">Chiều rộng (%)</label>
                                        <input type="number" name="overlay_width" id="overlay_width"
                                               class="form-control" min="10" max="80" value="30">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="overlay_height">Chiều cao (%)</label>
                                        <input type="number" name="overlay_height" id="overlay_height"
                                               class="form-control" min="10" max="80" value="30">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="overlay_timing">Thời gian hiển thị ảnh</label>
                            <select name="overlay_timing" id="overlay_timing" class="form-control">
                                <option value="full">Toàn bộ video</option>
                                <option value="start">5 giây đầu</option>
                                <option value="end">5 giây cuối</option>
                                <option value="middle" selected>Giữa video</option>
                                <option value="custom">Tùy chỉnh thời gian</option>
                            </select>
                        </div>

                        <div id="custom-overlay-timing" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="overlay_start_time">Thời gian bắt đầu (giây)</label>
                                        <input type="number" name="overlay_start_time" id="overlay_start_time"
                                               class="form-control" min="0" value="0" step="0.1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="overlay_end_time">Thời gian kết thúc (giây)</label>
                                        <input type="number" name="overlay_end_time" id="overlay_end_time"
                                               class="form-control" min="0" value="10" step="0.1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Split Mode Settings -->
            <div id="split-mode-settings" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Cài đặt chia màn hình</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="split_layout">Bố cục chia</label>
                                    <select name="split_layout" id="split_layout" class="form-control">
                                        <option value="horizontal" selected>Chia ngang (trên/dưới)</option>
                                        <option value="vertical">Chia dọc (trái/phải)</option>
                                        <option value="pip">Picture in Picture</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="split_ratio">Tỷ lệ chia</label>
                                    <select name="split_ratio" id="split_ratio" class="form-control">
                                        <option value="50:50" selected>50:50</option>
                                        <option value="60:40">60:40</option>
                                        <option value="70:30">70:30</option>
                                        <option value="80:20">80:20</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Duration Settings -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clock mr-2"></i>Cài đặt thời lượng video</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Thời lượng video dựa trên</label>
                    <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                        <label class="btn btn-outline-primary active mr-2 mb-2">
                            <input type="radio" name="duration_based_on" value="images" checked>
                            <i class="fas fa-images mr-1"></i>Tổng thời gian ảnh
                        </label>
                        <label class="btn btn-outline-primary mr-2 mb-2">
                            <input type="radio" name="duration_based_on" value="video">
                            <i class="fas fa-video mr-1"></i>Thời lượng video nội dung
                        </label>
                        <label class="btn btn-outline-primary mr-2 mb-2">
                            <input type="radio" name="duration_based_on" value="audio">
                            <i class="fas fa-volume-up mr-1"></i>Thời lượng âm thanh
                        </label>
                        <label class="btn btn-outline-primary mb-2">
                            <input type="radio" name="duration_based_on" value="custom">
                            <i class="fas fa-cog mr-1"></i>Tùy chỉnh
                        </label>
                    </div>
                </div>

                <!-- Duration Info Display -->
                <div id="duration-info" class="alert alert-info">
                    <div id="images-duration-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Thời lượng dựa trên ảnh:</strong>
                        <span id="total-images-duration">0 giây</span>
                        <small class="d-block mt-1">Tính toán: Số ảnh × Thời gian hiển thị + Hiệu ứng chuyển cảnh</small>
                    </div>
                    <div id="video-duration-info" style="display: none;">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Thời lượng dựa trên video:</strong>
                        <span id="total-video-duration">Chưa có video</span>
                    </div>
                    <div id="audio-duration-info" style="display: none;">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Thời lượng dựa trên âm thanh:</strong>
                        <span id="total-audio-duration">Chưa có âm thanh</span>
                    </div>
                    <div id="custom-duration-info" style="display: none;">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Thời lượng tùy chỉnh:</strong>
                        <span id="custom-duration-display">30 giây</span>
                    </div>
                </div>

                <!-- Custom Duration Settings -->
                <div id="custom-duration-settings" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="custom_duration">Thời lượng video (giây)</label>
                                <input type="number" name="custom_duration" id="custom_duration"
                                       class="form-control" min="5" max="600" value="30">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="content_behavior">Xử lý nội dung khi thiếu/thừa</label>
                                <select name="content_behavior" id="content_behavior" class="form-control">
                                    <option value="loop" selected>Lặp lại nội dung</option>
                                    <option value="freeze">Dừng ở frame cuối</option>
                                    <option value="fade">Fade to black</option>
                                    <option value="crop">Cắt bớt nội dung</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Duration Sync Options -->
                <div class="form-group mt-3">
                    <div class="form-check">
                        <input type="checkbox" name="sync_with_audio" id="sync_with_audio"
                               class="form-check-input" value="1">
                        <label class="form-check-label" for="sync_with_audio">
                            Đồng bộ với âm thanh (ưu tiên thời lượng âm thanh)
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="auto_adjust_images" id="auto_adjust_images"
                               class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="auto_adjust_images">
                            Tự động điều chỉnh thời gian ảnh để khớp với tổng thời lượng
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
