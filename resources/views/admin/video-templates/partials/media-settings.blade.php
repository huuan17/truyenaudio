<!-- Media Settings -->
<div class="card">
    <div class="card-body">
        <!-- Video Sections -->
        <div class="form-group">
            <label for="video_sections">Phân đoạn video</label>
            <select name="video_sections" id="video_sections" class="form-control">
                <option value="none" {{ ($settings['video_sections'] ?? 'none') === 'none' ? 'selected' : '' }}>Không phân đoạn</option>
                <option value="intro_main_outro" {{ ($settings['video_sections'] ?? '') === 'intro_main_outro' ? 'selected' : '' }}>Intro - Nội dung - Outro</option>
                <option value="chapters" {{ ($settings['video_sections'] ?? '') === 'chapters' ? 'selected' : '' }}>Chia theo chương</option>
                <option value="custom" {{ ($settings['video_sections'] ?? '') === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
            </select>
        </div>

        <!-- Image Settings -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="image_position">Vị trí ảnh</label>
                    <select name="image_position" id="image_position" class="form-control">
                        <option value="center" {{ ($settings['image_position'] ?? 'center') === 'center' ? 'selected' : '' }}>Giữa</option>
                        <option value="top" {{ ($settings['image_position'] ?? '') === 'top' ? 'selected' : '' }}>Trên</option>
                        <option value="bottom" {{ ($settings['image_position'] ?? '') === 'bottom' ? 'selected' : '' }}>Dưới</option>
                        <option value="left" {{ ($settings['image_position'] ?? '') === 'left' ? 'selected' : '' }}>Trái</option>
                        <option value="right" {{ ($settings['image_position'] ?? '') === 'right' ? 'selected' : '' }}>Phải</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="image_scale">Tỷ lệ ảnh (%)</label>
                    <input type="number" name="image_scale" id="image_scale" class="form-control"
                           value="{{ $settings['image_scale'] ?? 100 }}" data-min="10" data-max="200">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="image_opacity">Độ trong suốt (%)</label>
                    <input type="number" name="image_opacity" id="image_opacity" class="form-control"
                           value="{{ $settings['image_opacity'] ?? 100 }}" data-min="0" data-max="100">
                </div>
            </div>
        </div>

        <!-- Image Effects -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="image_effect">Hiệu ứng ảnh</label>
                    <select name="image_effect" id="image_effect" class="form-control">
                        <option value="none" {{ ($settings['image_effect'] ?? 'none') === 'none' ? 'selected' : '' }}>Không có</option>
                        <option value="blur" {{ ($settings['image_effect'] ?? '') === 'blur' ? 'selected' : '' }}>Làm mờ</option>
                        <option value="sepia" {{ ($settings['image_effect'] ?? '') === 'sepia' ? 'selected' : '' }}>Sepia</option>
                        <option value="grayscale" {{ ($settings['image_effect'] ?? '') === 'grayscale' ? 'selected' : '' }}>Đen trắng</option>
                        <option value="vintage" {{ ($settings['image_effect'] ?? '') === 'vintage' ? 'selected' : '' }}>Vintage</option>
                        <option value="bright" {{ ($settings['image_effect'] ?? '') === 'bright' ? 'selected' : '' }}>Sáng hơn</option>
                        <option value="dark" {{ ($settings['image_effect'] ?? '') === 'dark' ? 'selected' : '' }}>Tối hơn</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="auto_adjust_images" id="auto_adjust_images" class="form-check-input" 
                               value="1" {{ ($settings['auto_adjust_images'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_adjust_images">
                            Tự động điều chỉnh ảnh
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Behavior -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="content_behavior">Hành vi nội dung</label>
                    <select name="content_behavior" id="content_behavior" class="form-control">
                        <option value="loop" {{ ($settings['content_behavior'] ?? 'loop') === 'loop' ? 'selected' : '' }}>Lặp lại</option>
                        <option value="once" {{ ($settings['content_behavior'] ?? '') === 'once' ? 'selected' : '' }}>Chỉ một lần</option>
                        <option value="reverse" {{ ($settings['content_behavior'] ?? '') === 'reverse' ? 'selected' : '' }}>Đảo ngược</option>
                        <option value="random" {{ ($settings['content_behavior'] ?? '') === 'random' ? 'selected' : '' }}>Ngẫu nhiên</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="max_duration">Thời lượng tối đa (giây)</label>
                    <input type="number" name="max_duration" id="max_duration" class="form-control"
                           value="{{ $settings['max_duration'] ?? 300 }}" data-min="10" data-max="3600">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="min_duration">Thời lượng tối thiểu (giây)</label>
                    <input type="number" name="min_duration" id="min_duration" class="form-control"
                           value="{{ $settings['min_duration'] ?? 10 }}" data-min="5" data-max="300">
                </div>
            </div>
        </div>

        <!-- Advanced Media Options -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Tùy chọn nâng cao</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slide_duration">Thời gian slide (giây)</label>
                            <input type="number" name="slide_duration" id="slide_duration" class="form-control" 
                                   min="0.5" max="30" step="0.5" value="{{ $settings['slide_duration'] ?? 3 }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slide_transition">Hiệu ứng slide</label>
                            <select name="slide_transition" id="slide_transition" class="form-control">
                                <option value="slide" {{ ($settings['slide_transition'] ?? 'slide') === 'slide' ? 'selected' : '' }}>Slide</option>
                                <option value="fade" {{ ($settings['slide_transition'] ?? '') === 'fade' ? 'selected' : '' }}>Fade</option>
                                <option value="zoom" {{ ($settings['slide_transition'] ?? '') === 'zoom' ? 'selected' : '' }}>Zoom</option>
                                <option value="dissolve" {{ ($settings['slide_transition'] ?? '') === 'dissolve' ? 'selected' : '' }}>Dissolve</option>
                                <option value="wipe" {{ ($settings['slide_transition'] ?? '') === 'wipe' ? 'selected' : '' }}>Wipe</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image_overlays">Image Overlays (JSON)</label>
                    <textarea name="image_overlays" id="image_overlays" class="form-control" rows="3"
                              placeholder='[{"image": "overlay.png", "position": "top-right", "duration": 5}]'>{{ json_encode($settings['image_overlays'] ?? []) }}</textarea>
                    <small class="form-text text-muted">Cấu hình overlay ảnh dưới dạng JSON</small>
                </div>

                <div class="form-group">
                    <label for="section_transitions">Section Transitions (JSON)</label>
                    <textarea name="section_transitions" id="section_transitions" class="form-control" rows="3"
                              placeholder='[{"from": "intro", "to": "main", "effect": "fade", "duration": 1}]'>{{ json_encode($settings['section_transitions'] ?? []) }}</textarea>
                    <small class="form-text text-muted">Cấu hình chuyển cảnh giữa các phần</small>
                </div>
            </div>
        </div>
    </div>
</div>
