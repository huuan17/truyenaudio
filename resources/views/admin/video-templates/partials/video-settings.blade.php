<!-- Video Settings -->
<div class="card">
    <div class="card-body">
        <!-- Logo Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt Logo</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="enable_logo" id="enable_logo" class="form-check-input" 
                               value="1" {{ ($settings['enable_logo'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_logo">
                            Hiển thị logo
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="logo_position">Vị trí logo</label>
                            <select name="logo_position" id="logo_position" class="form-control">
                                <option value="top-left" {{ ($settings['logo_position'] ?? 'bottom-right') === 'top-left' ? 'selected' : '' }}>Trên trái</option>
                                <option value="top-right" {{ ($settings['logo_position'] ?? 'bottom-right') === 'top-right' ? 'selected' : '' }}>Trên phải</option>
                                <option value="bottom-left" {{ ($settings['logo_position'] ?? 'bottom-right') === 'bottom-left' ? 'selected' : '' }}>Dưới trái</option>
                                <option value="bottom-right" {{ ($settings['logo_position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Dưới phải</option>
                                <option value="center" {{ ($settings['logo_position'] ?? 'bottom-right') === 'center' ? 'selected' : '' }}>Giữa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="logo_size">Kích thước logo (px)</label>
                            <input type="number" name="logo_size" id="logo_size" class="form-control"
                                   value="{{ $settings['logo_size'] ?? 100 }}" data-min="20" data-max="500">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="logo_opacity">Độ trong suốt logo (%)</label>
                            <input type="number" name="logo_opacity" id="logo_opacity" class="form-control"
                                   value="{{ $settings['logo_opacity'] ?? 90 }}" data-min="0" data-max="100">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="logo_margin">Khoảng cách từ viền (px)</label>
                            <input type="number" name="logo_margin" id="logo_margin" class="form-control"
                                   value="{{ $settings['logo_margin'] ?? 20 }}" data-min="0" data-max="100">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="logo_timing">Thời gian hiển thị</label>
                            <select name="logo_timing" id="logo_timing" class="form-control">
                                <option value="full" {{ ($settings['logo_timing'] ?? 'full') === 'full' ? 'selected' : '' }}>Toàn bộ video</option>
                                <option value="start" {{ ($settings['logo_timing'] ?? '') === 'start' ? 'selected' : '' }}>Chỉ đầu video</option>
                                <option value="end" {{ ($settings['logo_timing'] ?? '') === 'end' ? 'selected' : '' }}>Chỉ cuối video</option>
                                <option value="custom" {{ ($settings['logo_timing'] ?? '') === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="logo_effect">Hiệu ứng logo</label>
                            <select name="logo_effect" id="logo_effect" class="form-control">
                                <option value="none" {{ ($settings['logo_effect'] ?? 'none') === 'none' ? 'selected' : '' }}>Không có</option>
                                <option value="fade_in" {{ ($settings['logo_effect'] ?? '') === 'fade_in' ? 'selected' : '' }}>Fade in</option>
                                <option value="fade_out" {{ ($settings['logo_effect'] ?? '') === 'fade_out' ? 'selected' : '' }}>Fade out</option>
                                <option value="slide_in" {{ ($settings['logo_effect'] ?? '') === 'slide_in' ? 'selected' : '' }}>Slide in</option>
                                <option value="zoom_in" {{ ($settings['logo_effect'] ?? '') === 'zoom_in' ? 'selected' : '' }}>Zoom in</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subtitle Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt Subtitle</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="subtitle_position">Vị trí subtitle</label>
                            <select name="subtitle_position" id="subtitle_position" class="form-control">
                                <option value="bottom" {{ ($settings['subtitle_position'] ?? 'bottom') === 'bottom' ? 'selected' : '' }}>Dưới</option>
                                <option value="top" {{ ($settings['subtitle_position'] ?? '') === 'top' ? 'selected' : '' }}>Trên</option>
                                <option value="center" {{ ($settings['subtitle_position'] ?? '') === 'center' ? 'selected' : '' }}>Giữa</option>
                                <option value="custom" {{ ($settings['subtitle_position'] ?? '') === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="subtitle_size">Kích thước font (px)</label>
                            <input type="number" name="subtitle_size" id="subtitle_size" class="form-control"
                                   value="{{ $settings['subtitle_size'] ?? 24 }}" data-min="12" data-max="72">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="subtitle_font">Font chữ</label>
                            <select name="subtitle_font" id="subtitle_font" class="form-control">
                                <option value="Arial" {{ ($settings['subtitle_font'] ?? 'Arial') === 'Arial' ? 'selected' : '' }}>Arial</option>
                                <option value="Times New Roman" {{ ($settings['subtitle_font'] ?? '') === 'Times New Roman' ? 'selected' : '' }}>Times New Roman</option>
                                <option value="Helvetica" {{ ($settings['subtitle_font'] ?? '') === 'Helvetica' ? 'selected' : '' }}>Helvetica</option>
                                <option value="Roboto" {{ ($settings['subtitle_font'] ?? '') === 'Roboto' ? 'selected' : '' }}>Roboto</option>
                                <option value="Open Sans" {{ ($settings['subtitle_font'] ?? '') === 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subtitle_color">Màu chữ</label>
                            <input type="color" name="subtitle_color" id="subtitle_color" class="form-control" 
                                   value="{{ $settings['subtitle_color'] ?? '#ffffff' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subtitle_background">Màu nền</label>
                            <input type="color" name="subtitle_background" id="subtitle_background" class="form-control" 
                                   value="{{ $settings['subtitle_background'] ?? '#000000' }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Output Settings -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt đầu ra</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_codec">Codec video</label>
                            <select name="video_codec" id="video_codec" class="form-control">
                                <option value="h264" {{ ($settings['video_codec'] ?? 'h264') === 'h264' ? 'selected' : '' }}>H.264 (Khuyến nghị)</option>
                                <option value="h265" {{ ($settings['video_codec'] ?? '') === 'h265' ? 'selected' : '' }}>H.265 (HEVC)</option>
                                <option value="vp9" {{ ($settings['video_codec'] ?? '') === 'vp9' ? 'selected' : '' }}>VP9</option>
                                <option value="av1" {{ ($settings['video_codec'] ?? '') === 'av1' ? 'selected' : '' }}>AV1</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="audio_codec">Codec audio</label>
                            <select name="audio_codec" id="audio_codec" class="form-control">
                                <option value="aac" {{ ($settings['audio_codec'] ?? 'aac') === 'aac' ? 'selected' : '' }}>AAC (Khuyến nghị)</option>
                                <option value="mp3" {{ ($settings['audio_codec'] ?? '') === 'mp3' ? 'selected' : '' }}>MP3</option>
                                <option value="opus" {{ ($settings['audio_codec'] ?? '') === 'opus' ? 'selected' : '' }}>Opus</option>
                                <option value="vorbis" {{ ($settings['audio_codec'] ?? '') === 'vorbis' ? 'selected' : '' }}>Vorbis</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_bitrate">Bitrate video (kbps)</label>
                            <input type="number" name="video_bitrate" id="video_bitrate" class="form-control"
                                   value="{{ $settings['video_bitrate'] ?? 2000 }}" data-min="500" data-max="50000">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="audio_bitrate">Bitrate audio (kbps)</label>
                            <input type="number" name="audio_bitrate" id="audio_bitrate" class="form-control"
                                   value="{{ $settings['audio_bitrate'] ?? 128 }}" data-min="64" data-max="320">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
