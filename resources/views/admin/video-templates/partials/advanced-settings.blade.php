<!-- Advanced Settings -->
<div class="card">
    <div class="card-body">
        <!-- Performance Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt hiệu năng</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="processing_priority">Độ ưu tiên xử lý</label>
                            <select name="processing_priority" id="processing_priority" class="form-control">
                                <option value="low" {{ ($settings['processing_priority'] ?? 'normal') === 'low' ? 'selected' : '' }}>Thấp</option>
                                <option value="normal" {{ ($settings['processing_priority'] ?? 'normal') === 'normal' ? 'selected' : '' }}>Bình thường</option>
                                <option value="high" {{ ($settings['processing_priority'] ?? 'normal') === 'high' ? 'selected' : '' }}>Cao</option>
                                <option value="urgent" {{ ($settings['processing_priority'] ?? 'normal') === 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="max_processing_time">Thời gian xử lý tối đa (phút)</label>
                            <input type="number" name="max_processing_time" id="max_processing_time" class="form-control"
                                   value="{{ $settings['max_processing_time'] ?? 30 }}" data-min="5" data-max="120">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="retry_attempts">Số lần thử lại</label>
                            <input type="number" name="retry_attempts" id="retry_attempts" class="form-control"
                                   value="{{ $settings['retry_attempts'] ?? 3 }}" data-min="0" data-max="5">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="enable_gpu_acceleration" id="enable_gpu_acceleration" class="form-check-input" 
                               value="1" {{ ($settings['enable_gpu_acceleration'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_gpu_acceleration">
                            Sử dụng GPU acceleration (nếu có)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom FFmpeg Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt FFmpeg tùy chỉnh</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="custom_ffmpeg_params">Tham số FFmpeg tùy chỉnh</label>
                    <textarea name="custom_ffmpeg_params" id="custom_ffmpeg_params" class="form-control" rows="3"
                              placeholder="-preset fast -crf 23 -movflags +faststart">{{ $settings['custom_ffmpeg_params'] ?? '' }}</textarea>
                    <small class="form-text text-muted">Các tham số FFmpeg bổ sung. Chỉ dành cho người dùng có kinh nghiệm.</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_preset">Video preset</label>
                            <select name="video_preset" id="video_preset" class="form-control">
                                <option value="ultrafast" {{ ($settings['video_preset'] ?? 'medium') === 'ultrafast' ? 'selected' : '' }}>Ultra Fast</option>
                                <option value="superfast" {{ ($settings['video_preset'] ?? 'medium') === 'superfast' ? 'selected' : '' }}>Super Fast</option>
                                <option value="veryfast" {{ ($settings['video_preset'] ?? 'medium') === 'veryfast' ? 'selected' : '' }}>Very Fast</option>
                                <option value="faster" {{ ($settings['video_preset'] ?? 'medium') === 'faster' ? 'selected' : '' }}>Faster</option>
                                <option value="fast" {{ ($settings['video_preset'] ?? 'medium') === 'fast' ? 'selected' : '' }}>Fast</option>
                                <option value="medium" {{ ($settings['video_preset'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="slow" {{ ($settings['video_preset'] ?? 'medium') === 'slow' ? 'selected' : '' }}>Slow</option>
                                <option value="slower" {{ ($settings['video_preset'] ?? 'medium') === 'slower' ? 'selected' : '' }}>Slower</option>
                                <option value="veryslow" {{ ($settings['video_preset'] ?? 'medium') === 'veryslow' ? 'selected' : '' }}>Very Slow</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="crf_value">CRF Value (chất lượng)</label>
                            <input type="number" name="crf_value" id="crf_value" class="form-control"
                                   value="{{ $settings['crf_value'] ?? 23 }}" data-min="0" data-max="51">
                            <small class="form-text text-muted">0 = lossless, 23 = mặc định, 51 = chất lượng thấp nhất</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Watermark & Branding -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Watermark & Branding</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="enable_watermark" id="enable_watermark" class="form-check-input" 
                               value="1" {{ ($settings['enable_watermark'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_watermark">
                            Hiển thị watermark
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="watermark_text">Text watermark</label>
                    <input type="text" name="watermark_text" id="watermark_text" class="form-control" 
                           value="{{ $settings['watermark_text'] ?? '' }}" placeholder="© Your Brand Name">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="watermark_position">Vị trí watermark</label>
                            <select name="watermark_position" id="watermark_position" class="form-control">
                                <option value="bottom-right" {{ ($settings['watermark_position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Dưới phải</option>
                                <option value="bottom-left" {{ ($settings['watermark_position'] ?? '') === 'bottom-left' ? 'selected' : '' }}>Dưới trái</option>
                                <option value="top-right" {{ ($settings['watermark_position'] ?? '') === 'top-right' ? 'selected' : '' }}>Trên phải</option>
                                <option value="top-left" {{ ($settings['watermark_position'] ?? '') === 'top-left' ? 'selected' : '' }}>Trên trái</option>
                                <option value="center" {{ ($settings['watermark_position'] ?? '') === 'center' ? 'selected' : '' }}>Giữa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="watermark_opacity">Độ trong suốt watermark (%)</label>
                            <input type="number" name="watermark_opacity" id="watermark_opacity" class="form-control"
                                   value="{{ $settings['watermark_opacity'] ?? 50 }}" data-min="0" data-max="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto Upload Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt tự động upload</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="auto_upload" id="auto_upload" class="form-check-input" 
                               value="1" {{ ($settings['auto_upload'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_upload">
                            Tự động upload sau khi tạo video
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="upload_platforms">Nền tảng upload</label>
                    <div class="form-check">
                        <input type="checkbox" name="upload_platforms[]" value="youtube" id="upload_youtube" class="form-check-input"
                               {{ in_array('youtube', $settings['upload_platforms'] ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="upload_youtube">YouTube</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="upload_platforms[]" value="tiktok" id="upload_tiktok" class="form-check-input"
                               {{ in_array('tiktok', $settings['upload_platforms'] ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="upload_tiktok">TikTok</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="upload_platforms[]" value="facebook" id="upload_facebook" class="form-check-input"
                               {{ in_array('facebook', $settings['upload_platforms'] ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="upload_facebook">Facebook</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt thông báo</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="notify_on_completion" id="notify_on_completion" class="form-check-input" 
                               value="1" {{ ($settings['notify_on_completion'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_on_completion">
                            Thông báo khi hoàn thành
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="notify_on_error" id="notify_on_error" class="form-check-input" 
                               value="1" {{ ($settings['notify_on_error'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_on_error">
                            Thông báo khi có lỗi
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notification_email">Email nhận thông báo</label>
                    <input type="email" name="notification_email" id="notification_email" class="form-control" 
                           value="{{ $settings['notification_email'] ?? '' }}" placeholder="admin@example.com">
                </div>
            </div>
        </div>
    </div>
</div>
