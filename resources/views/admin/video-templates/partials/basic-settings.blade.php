<!-- Basic Settings -->
<div class="card">
    <div class="card-body">
        <!-- Platform Selection -->
        <div class="form-group">
            <label class="form-label">Nền tảng mặc định</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-dark mr-2 mb-2 {{ ($settings['platform'] ?? '') === 'tiktok' ? 'active' : '' }}">
                    <input type="radio" name="template_platform" value="tiktok" {{ ($settings['platform'] ?? '') === 'tiktok' ? 'checked' : '' }}>
                    <i class="fab fa-tiktok mr-1"></i>TikTok
                </label>
                <label class="btn btn-outline-danger mr-2 mb-2 {{ ($settings['platform'] ?? '') === 'youtube' ? 'active' : '' }}">
                    <input type="radio" name="template_platform" value="youtube" {{ ($settings['platform'] ?? '') === 'youtube' ? 'checked' : '' }}>
                    <i class="fab fa-youtube mr-1"></i>YouTube
                </label>
                <label class="btn btn-outline-info mr-2 mb-2 {{ ($settings['platform'] ?? '') === 'both' ? 'active' : '' }}">
                    <input type="radio" name="template_platform" value="both" {{ ($settings['platform'] ?? '') === 'both' ? 'checked' : '' }}>
                    <i class="fas fa-globe mr-1"></i>Cả hai
                </label>
                <label class="btn btn-outline-secondary mb-2 {{ ($settings['platform'] ?? 'none') === 'none' ? 'active' : '' }}">
                    <input type="radio" name="template_platform" value="none" {{ ($settings['platform'] ?? 'none') === 'none' ? 'checked' : '' }}>
                    <i class="fas fa-video mr-1"></i>Không đăng kênh
                </label>
            </div>
        </div>

        <!-- Media Type Selection -->
        <div class="form-group">
            <label class="form-label">Loại nội dung mặc định</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-info mr-2 mb-2 {{ ($settings['media_type'] ?? 'images') === 'images' ? 'active' : '' }}">
                    <input type="radio" name="template_media_type" value="images" {{ ($settings['media_type'] ?? 'images') === 'images' ? 'checked' : '' }}> 
                    <i class="fas fa-image mr-1"></i>Hình ảnh
                </label>
                <label class="btn btn-outline-info mr-2 mb-2 {{ ($settings['media_type'] ?? '') === 'video' ? 'active' : '' }}">
                    <input type="radio" name="template_media_type" value="video" {{ ($settings['media_type'] ?? '') === 'video' ? 'checked' : '' }}> 
                    <i class="fas fa-video mr-1"></i>Video
                </label>
                <label class="btn btn-outline-info mb-2 {{ ($settings['media_type'] ?? '') === 'mixed' ? 'active' : '' }}">
                    <input type="radio" name="template_media_type" value="mixed" {{ ($settings['media_type'] ?? '') === 'mixed' ? 'checked' : '' }}> 
                    <i class="fas fa-layer-group mr-1"></i>Hỗn hợp
                </label>
            </div>
        </div>

        <!-- Duration Settings -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="duration_based_on">Thời lượng dựa trên</label>
                    <select name="duration_based_on" id="duration_based_on" class="form-control">
                        <option value="images" {{ ($settings['duration_based_on'] ?? 'images') === 'images' ? 'selected' : '' }}>Số lượng ảnh</option>
                        <option value="audio" {{ ($settings['duration_based_on'] ?? '') === 'audio' ? 'selected' : '' }}>Độ dài audio</option>
                        <option value="video" {{ ($settings['duration_based_on'] ?? '') === 'video' ? 'selected' : '' }}>Độ dài video</option>
                        <option value="custom" {{ ($settings['duration_based_on'] ?? '') === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="custom_duration">Thời lượng tùy chỉnh (giây)</label>
                    <input type="number" name="custom_duration" id="custom_duration" class="form-control"
                           value="{{ $settings['custom_duration'] ?? 30 }}" data-min="5" data-max="600">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="image_duration">Thời gian mỗi ảnh (giây)</label>
                    <input type="number" name="image_duration" id="image_duration" class="form-control"
                           step="0.5" value="{{ $settings['image_duration'] ?? 3 }}" data-min="0.5" data-max="30">
                </div>
            </div>
        </div>

        <!-- Transition Settings -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="transition_effect">Hiệu ứng chuyển cảnh</label>
                    <select name="transition_effect" id="transition_effect" class="form-control">
                        <option value="fade" {{ ($settings['transition_effect'] ?? 'fade') === 'fade' ? 'selected' : '' }}>Fade</option>
                        <option value="slide" {{ ($settings['transition_effect'] ?? '') === 'slide' ? 'selected' : '' }}>Slide</option>
                        <option value="zoom" {{ ($settings['transition_effect'] ?? '') === 'zoom' ? 'selected' : '' }}>Zoom</option>
                        <option value="dissolve" {{ ($settings['transition_effect'] ?? '') === 'dissolve' ? 'selected' : '' }}>Dissolve</option>
                        <option value="wipe" {{ ($settings['transition_effect'] ?? '') === 'wipe' ? 'selected' : '' }}>Wipe</option>
                        <option value="none" {{ ($settings['transition_effect'] ?? '') === 'none' ? 'selected' : '' }}>Không có</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="sync_with_audio" id="sync_with_audio" class="form-check-input" 
                               value="1" {{ ($settings['sync_with_audio'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sync_with_audio">
                            Đồng bộ với audio
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolution and Quality -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="resolution">Độ phân giải</label>
                    <select name="resolution" id="resolution" class="form-control">
                        <option value="1920x1080" {{ ($settings['resolution'] ?? '1920x1080') === '1920x1080' ? 'selected' : '' }}>1920x1080 (16:9)</option>
                        <option value="1080x1920" {{ ($settings['resolution'] ?? '') === '1080x1920' ? 'selected' : '' }}>1080x1920 (9:16)</option>
                        <option value="1280x720" {{ ($settings['resolution'] ?? '') === '1280x720' ? 'selected' : '' }}>1280x720 (16:9)</option>
                        <option value="1080x1080" {{ ($settings['resolution'] ?? '') === '1080x1080' ? 'selected' : '' }}>1080x1080 (1:1)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="fps">FPS</label>
                    <select name="fps" id="fps" class="form-control">
                        <option value="24" {{ ($settings['fps'] ?? 30) == 24 ? 'selected' : '' }}>24 FPS</option>
                        <option value="30" {{ ($settings['fps'] ?? 30) == 30 ? 'selected' : '' }}>30 FPS</option>
                        <option value="60" {{ ($settings['fps'] ?? 30) == 60 ? 'selected' : '' }}>60 FPS</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="quality">Chất lượng video</label>
                    <select name="quality" id="quality" class="form-control">
                        <option value="medium" {{ ($settings['quality'] ?? 'high') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ ($settings['quality'] ?? 'high') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="very_high" {{ ($settings['quality'] ?? 'high') === 'very_high' ? 'selected' : '' }}>Very High</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
