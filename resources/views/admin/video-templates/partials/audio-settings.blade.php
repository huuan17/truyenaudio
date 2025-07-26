<!-- Audio Settings -->
<div class="card">
    <div class="card-body">
        <!-- Audio Source -->
        <div class="form-group">
            <label class="form-label">Nguồn âm thanh mặc định</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-success mr-2 mb-2 {{ ($settings['audio_source'] ?? 'none') === 'tts' ? 'active' : '' }}">
                    <input type="radio" name="template_audio_source" value="tts" {{ ($settings['audio_source'] ?? 'none') === 'tts' ? 'checked' : '' }}>
                    <i class="fas fa-microphone mr-1"></i>Text-to-Speech
                </label>
                <label class="btn btn-outline-success mr-2 mb-2 {{ ($settings['audio_source'] ?? 'none') === 'upload' ? 'active' : '' }}">
                    <input type="radio" name="template_audio_source" value="upload" {{ ($settings['audio_source'] ?? 'none') === 'upload' ? 'checked' : '' }}>
                    <i class="fas fa-upload mr-1"></i>Upload file
                </label>
                <label class="btn btn-outline-success mr-2 mb-2 {{ ($settings['audio_source'] ?? 'none') === 'library' ? 'active' : '' }}">
                    <input type="radio" name="template_audio_source" value="library" {{ ($settings['audio_source'] ?? 'none') === 'library' ? 'checked' : '' }}>
                    <i class="fas fa-music mr-1"></i>Thư viện
                </label>
                <label class="btn btn-outline-success mb-2 {{ ($settings['audio_source'] ?? 'none') === 'none' ? 'active' : '' }}">
                    <input type="radio" name="template_audio_source" value="none" {{ ($settings['audio_source'] ?? 'none') === 'none' ? 'checked' : '' }}>
                    <i class="fas fa-volume-mute mr-1"></i>Không có
                </label>
            </div>
        </div>

        <!-- Audio Volume Settings -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="audio_volume">Âm lượng chính (dB)</label>
                    <input type="number" name="audio_volume" id="audio_volume" class="form-control"
                           value="{{ $settings['audio_volume'] ?? 18 }}" data-min="-20" data-max="20">
                    <small class="form-text text-muted">Âm lượng của audio chính (TTS hoặc upload)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="background_audio_volume">Âm lượng nhạc nền (dB)</label>
                    <input type="number" name="background_audio_volume" id="background_audio_volume" class="form-control"
                           value="{{ $settings['background_audio_volume'] ?? 30 }}" data-min="-40" data-max="10">
                    <small class="form-text text-muted">Âm lượng của nhạc nền</small>
                </div>
            </div>
        </div>

        <!-- TTS Settings -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt Text-to-Speech</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tts_voice">Giọng đọc mặc định</label>
                            <select name="tts_voice" id="tts_voice" class="form-control">
                                <option value="vi-VN-HoaiMyNeural" {{ ($settings['tts_voice'] ?? 'vi-VN-HoaiMyNeural') === 'vi-VN-HoaiMyNeural' ? 'selected' : '' }}>Hoài My (Nữ)</option>
                                <option value="vi-VN-NamMinhNeural" {{ ($settings['tts_voice'] ?? '') === 'vi-VN-NamMinhNeural' ? 'selected' : '' }}>Nam Minh (Nam)</option>
                                <option value="hn_female_ngochuyen_full_48k-fhg" {{ ($settings['tts_voice'] ?? '') === 'hn_female_ngochuyen_full_48k-fhg' ? 'selected' : '' }}>Ngọc Huyền (Nữ - HN)</option>
                                <option value="hn_male_manhtung_full_48k-fhg" {{ ($settings['tts_voice'] ?? '') === 'hn_male_manhtung_full_48k-fhg' ? 'selected' : '' }}>Mạnh Tùng (Nam - HN)</option>
                                <option value="sg_female_thaotrinh_full_48k-fhg" {{ ($settings['tts_voice'] ?? '') === 'sg_female_thaotrinh_full_48k-fhg' ? 'selected' : '' }}>Thảo Trinh (Nữ - SG)</option>
                                <option value="sg_male_minhhoang_full_48k-fhg" {{ ($settings['tts_voice'] ?? '') === 'sg_male_minhhoang_full_48k-fhg' ? 'selected' : '' }}>Minh Hoàng (Nam - SG)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tts_speed">Tốc độ đọc</label>
                            <select name="tts_speed" id="tts_speed" class="form-control">
                                <option value="0.5" {{ ($settings['tts_speed'] ?? 1) == 0.5 ? 'selected' : '' }}>0.5x (Chậm)</option>
                                <option value="0.75" {{ ($settings['tts_speed'] ?? 1) == 0.75 ? 'selected' : '' }}>0.75x</option>
                                <option value="1" {{ ($settings['tts_speed'] ?? 1) == 1 ? 'selected' : '' }}>1x (Bình thường)</option>
                                <option value="1.25" {{ ($settings['tts_speed'] ?? 1) == 1.25 ? 'selected' : '' }}>1.25x</option>
                                <option value="1.5" {{ ($settings['tts_speed'] ?? 1) == 1.5 ? 'selected' : '' }}>1.5x</option>
                                <option value="2" {{ ($settings['tts_speed'] ?? 1) == 2 ? 'selected' : '' }}>2x (Nhanh)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tts_volume">Âm lượng TTS</label>
                            <select name="tts_volume" id="tts_volume" class="form-control">
                                <option value="1" {{ ($settings['tts_volume'] ?? 18) == 1 ? 'selected' : '' }}>100%</option>
                                <option value="1.5" {{ ($settings['tts_volume'] ?? 18) == 1.5 ? 'selected' : '' }}>150%</option>
                                <option value="2" {{ ($settings['tts_volume'] ?? 18) == 2 ? 'selected' : '' }}>200%</option>
                                <option value="18" {{ ($settings['tts_volume'] ?? 18) == 18 ? 'selected' : '' }}>18dB (Mặc định)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="tts_bitrate">Bitrate TTS (kbps)</label>
                    <select name="tts_bitrate" id="tts_bitrate" class="form-control">
                        <option value="64" {{ ($settings['tts_bitrate'] ?? 128) == 64 ? 'selected' : '' }}>64 kbps</option>
                        <option value="128" {{ ($settings['tts_bitrate'] ?? 128) == 128 ? 'selected' : '' }}>128 kbps</option>
                        <option value="192" {{ ($settings['tts_bitrate'] ?? 128) == 192 ? 'selected' : '' }}>192 kbps</option>
                        <option value="256" {{ ($settings['tts_bitrate'] ?? 128) == 256 ? 'selected' : '' }}>256 kbps</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Audio Mixing Settings -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Cài đặt trộn âm thanh</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="audio_mixing">Chế độ trộn âm</label>
                            <select name="audio_mixing" id="audio_mixing" class="form-control">
                                <option value="overlay" {{ ($settings['audio_mixing'] ?? 'overlay') === 'overlay' ? 'selected' : '' }}>Overlay (Chồng lên)</option>
                                <option value="replace" {{ ($settings['audio_mixing'] ?? '') === 'replace' ? 'selected' : '' }}>Replace (Thay thế)</option>
                                <option value="mix" {{ ($settings['audio_mixing'] ?? '') === 'mix' ? 'selected' : '' }}>Mix (Trộn đều)</option>
                                <option value="ducking" {{ ($settings['audio_mixing'] ?? '') === 'ducking' ? 'selected' : '' }}>Ducking (Giảm nhạc nền khi có giọng)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="normalize_audio" id="normalize_audio" class="form-check-input" 
                                       value="1" {{ ($settings['normalize_audio'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="normalize_audio">
                                    Chuẩn hóa âm thanh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
