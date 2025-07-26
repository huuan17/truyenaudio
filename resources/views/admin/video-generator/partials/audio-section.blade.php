<!-- Audio Content Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-volume-up mr-2"></i>Nội dung âm thanh</h6>
    </div>
    <div class="card-body">
        <!-- Audio Source Selection -->
        <div class="form-group">
            <label class="form-label">Nguồn âm thanh</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-success active mr-2 mb-2">
                    <input type="radio" name="audio_source" value="tts" checked>
                    <i class="fas fa-microphone mr-1"></i>Text-to-Speech
                </label>
                <label class="btn btn-outline-success mr-2 mb-2">
                    <input type="radio" name="audio_source" value="upload">
                    <i class="fas fa-upload mr-1"></i>Upload file âm thanh
                </label>
                <label class="btn btn-outline-success mr-2 mb-2">
                    <input type="radio" name="audio_source" value="library">
                    <i class="fas fa-music mr-1"></i>Từ thư viện
                </label>
                <label class="btn btn-outline-success mb-2">
                    <input type="radio" name="audio_source" value="none">
                    <i class="fas fa-volume-mute mr-1"></i>Không có âm thanh
                </label>
            </div>
        </div>

        <!-- TTS Section -->
        <div id="tts-section">
            <div class="form-group">
                <label for="tts_text">Nội dung văn bản *</label>
                <textarea name="tts_text" id="tts_text" class="form-control" rows="6" 
                          placeholder="Nhập nội dung văn bản để chuyển đổi thành giọng nói..."></textarea>
                <small class="form-text text-muted">
                    Tối đa 5000 ký tự. Hỗ trợ tiếng Việt và tiếng Anh.
                </small>
            </div>
            
            <!-- TTS Settings -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tts_voice">Giọng đọc</label>
                        <select name="tts_voice" id="tts_voice" class="form-control">
                            <option value="hn_female_ngochuyen_full_48k-fhg">Ngọc Huyền (Nữ - Hà Nội)</option>
                            <option value="hn_male_manhtung_full_48k-fhg">Mạnh Tùng (Nam - Hà Nội)</option>
                            <option value="sg_female_thaotrinh_full_48k-fhg">Thảo Trinh (Nữ - Sài Gòn)</option>
                            <option value="sg_male_minhhoang_full_48k-fhg">Minh Hoàng (Nam - Sài Gòn)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tts_speed">Tốc độ đọc</label>
                        <select name="tts_speed" id="tts_speed" class="form-control">
                            <option value="0.5">0.5x (Chậm)</option>
                            <option value="0.75">0.75x</option>
                            <option value="1.0" selected>1.0x (Bình thường)</option>
                            <option value="1.25">1.25x</option>
                            <option value="1.5">1.5x</option>
                            <option value="2.0">2.0x (Nhanh)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tts_volume">Âm lượng</label>
                        <select name="tts_volume" id="tts_volume" class="form-control">
                            <option value="1.0" selected>100% (Bình thường)</option>
                            <option value="1.5">150% (To hơn)</option>
                            <option value="2.0">200% (Rất to)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Audio Section -->
        <div id="upload-audio-section" style="display: none;">
            <div class="form-group">
                <label for="audio_file">Chọn file âm thanh *</label>
                <input type="file" name="audio_file" id="audio_file" 
                       class="form-control-file" accept="audio/*" onchange="previewAudio(this)">
                <small class="form-text text-muted">
                    MP3, WAV, AAC. Tối đa 100MB
                </small>
                <div id="audio-preview" class="mt-3"></div>
            </div>
            
            <!-- Audio Settings -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="audio_volume">Âm lượng</label>
                        <select name="audio_volume" id="audio_volume" class="form-control">
                            <option value="0.5">50%</option>
                            <option value="0.75">75%</option>
                            <option value="1.0" selected>100% (Gốc)</option>
                            <option value="1.25">125%</option>
                            <option value="1.5">150%</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="audio_fade">Hiệu ứng fade</label>
                        <select name="audio_fade" id="audio_fade" class="form-control">
                            <option value="none">Không có</option>
                            <option value="in" selected>Fade in</option>
                            <option value="out">Fade out</option>
                            <option value="both">Fade in & out</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Audio Section -->
        <div id="no-audio-section" style="display: none;">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Lưu ý:</strong> Video sẽ được tạo không có âm thanh. 
                Thích hợp cho video cần thêm nhạc nền sau này.
            </div>
        </div>

        <!-- Library Audio Section -->
        <div id="library-audio-section" style="display: none;">
            <div class="form-group">
                <label>🎵 Chọn từ Thư viện Audio</label>
                <div class="audio-library-selector">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="library-search" class="form-control"
                                   placeholder="Tìm kiếm audio...">
                        </div>
                        <div class="col-md-3">
                            <select id="library-category" class="form-control">
                                <option value="">Tất cả danh mục</option>
                                <option value="story">Truyện audio</option>
                                <option value="music">Nhạc nền</option>
                                <option value="voice">Giọng đọc</option>
                                <option value="effect">Hiệu ứng</option>
                                <option value="podcast">Podcast</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary btn-block" onclick="searchAudioLibrary()">
                                <i class="fas fa-search mr-1"></i>Tìm
                            </button>
                        </div>
                    </div>
                    <div id="library-results" class="library-results" style="max-height: 300px; overflow-y: auto; border: 1px solid #e3e6f0; border-radius: 0.35rem; padding: 1rem;">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-music fa-2x mb-2"></i>
                            <p>Tìm kiếm audio trong thư viện</p>
                            <a href="{{ route('admin.audio-library.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt mr-1"></i>Mở Thư viện
                            </a>
                        </div>
                    </div>
                    <input type="hidden" name="library_audio_id" id="library_audio_id">
                    <div id="selected-audio-info" class="selected-audio-info mt-2" style="display: none;">
                        <div class="alert alert-success">
                            <strong>Đã chọn:</strong> <span id="selected-audio-title"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger float-right" onclick="clearSelectedAudio()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.audio-library-selector .library-results {
    background: #fff;
}

.audio-item {
    transition: background-color 0.2s ease;
}

.audio-item:hover {
    background-color: #f8f9fc !important;
}

.audio-item.bg-light {
    background-color: #e3f2fd !important;
    border-left: 3px solid #007bff;
}

.selected-audio-info .alert {
    margin-bottom: 0;
}

.border-danger {
    border-color: #dc3545 !important;
}
</style>
