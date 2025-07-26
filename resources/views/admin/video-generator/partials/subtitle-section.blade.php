<!-- Subtitle Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-closed-captioning mr-2"></i>Phụ đề (Subtitle)</h6>
    </div>
    <div class="card-body">
        <!-- Subtitle Enable/Disable -->
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="enable_subtitle" id="enable_subtitle" 
                       class="form-check-input" value="1">
                <label class="form-check-label" for="enable_subtitle">
                    <strong>Bật phụ đề cho video</strong>
                </label>
            </div>
        </div>

        <!-- Subtitle Settings -->
        <div id="subtitle-settings" style="display: none;">
            <!-- Subtitle Source -->
            <div class="form-group">
                <label class="form-label">Nguồn phụ đề</label>
                <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                    <label class="btn btn-outline-warning active mr-2 mb-2">
                        <input type="radio" name="subtitle_source" value="auto" checked> 
                        <i class="fas fa-magic mr-1"></i>Tự động từ TTS
                    </label>
                    <label class="btn btn-outline-warning mr-2 mb-2">
                        <input type="radio" name="subtitle_source" value="manual"> 
                        <i class="fas fa-keyboard mr-1"></i>Nhập thủ công
                    </label>
                    <label class="btn btn-outline-warning mb-2">
                        <input type="radio" name="subtitle_source" value="upload"> 
                        <i class="fas fa-upload mr-1"></i>Upload file SRT
                    </label>
                </div>
            </div>

            <!-- Auto Subtitle (from TTS) -->
            <div id="auto-subtitle-section">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Phụ đề tự động:</strong> Hệ thống sẽ tự động tạo phụ đề từ nội dung TTS.
                    Phụ đề sẽ được đồng bộ với âm thanh.
                </div>

                <!-- Subtitle Timing Mode -->
                <div class="form-group">
                    <label class="form-label">Chế độ hiển thị phụ đề</label>
                    <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                        <label class="btn btn-outline-primary active mr-2 mb-2">
                            <input type="radio" name="subtitle_timing_mode" value="auto" checked>
                            <i class="fas fa-magic mr-1"></i>Tự động theo nội dung
                        </label>
                        <label class="btn btn-outline-primary mr-2 mb-2">
                            <input type="radio" name="subtitle_timing_mode" value="image_sync">
                            <i class="fas fa-images mr-1"></i>Gắn theo ảnh
                        </label>
                        <label class="btn btn-outline-primary mb-2">
                            <input type="radio" name="subtitle_timing_mode" value="custom_timing">
                            <i class="fas fa-clock mr-1"></i>Tùy chỉnh thời gian
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        <strong>Tự động:</strong> Phụ đề hiển thị theo thời lượng audio/video<br>
                        <strong>Gắn theo ảnh:</strong> Mỗi ảnh có phụ đề riêng (cho video từ ảnh)<br>
                        <strong>Tùy chỉnh:</strong> Bạn tự định thời gian hiển thị
                    </small>
                </div>
            </div>

            <!-- Manual Subtitle -->
            <div id="manual-subtitle-section" style="display: none;">
                <div class="form-group">
                    <label for="subtitle_text">Nội dung phụ đề</label>
                    <textarea name="subtitle_text" id="subtitle_text" class="form-control" rows="6" 
                              placeholder="Nhập nội dung phụ đề. Mỗi dòng sẽ hiển thị khoảng 3-4 giây..."></textarea>
                    <small class="form-text text-muted">
                        Mỗi dòng sẽ được hiển thị như một đoạn phụ đề riêng biệt.
                    </small>
                </div>
            </div>

            <!-- Upload Subtitle -->
            <div id="upload-subtitle-section" style="display: none;">
                <div class="form-group">
                    <label for="subtitle_file">Upload file SRT</label>
                    <input type="file" name="subtitle_file" id="subtitle_file" 
                           class="form-control-file" accept=".srt" onchange="previewSubtitle(this)">
                    <small class="form-text text-muted">
                        Chỉ hỗ trợ file .srt với encoding UTF-8
                    </small>
                    <div id="subtitle-preview" class="mt-3"></div>
                </div>
            </div>

            <!-- Subtitle Styling -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="subtitle_position">Vị trí</label>
                        <select name="subtitle_position" id="subtitle_position" class="form-control">
                            <option value="bottom" selected>Dưới cùng</option>
                            <option value="top">Trên cùng</option>
                            <option value="center">Giữa màn hình</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="subtitle_size">Kích thước chữ</label>
                        <select name="subtitle_size" id="subtitle_size" class="form-control">
                            <option value="small">Nhỏ</option>
                            <option value="medium" selected>Vừa</option>
                            <option value="large">Lớn</option>
                            <option value="xlarge">Rất lớn</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="subtitle_color">Màu chữ</label>
                        <select name="subtitle_color" id="subtitle_color" class="form-control">
                            <option value="white" selected>Trắng</option>
                            <option value="black">Đen</option>
                            <option value="yellow">Vàng</option>
                            <option value="red">Đỏ</option>
                            <option value="blue">Xanh dương</option>
                            <option value="green">Xanh lá</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="subtitle_background">Nền chữ</label>
                        <select name="subtitle_background" id="subtitle_background" class="form-control">
                            <option value="none">Không có</option>
                            <option value="black" selected>Đen trong suốt</option>
                            <option value="white">Trắng trong suốt</option>
                            <option value="solid_black">Đen đặc</option>
                            <option value="solid_white">Trắng đặc</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Timing Settings -->
            <div id="timing-settings">
                <!-- Image Sync Settings -->
                <div id="image-sync-settings" style="display: none;">
                    <div class="alert alert-warning">
                        <i class="fas fa-images mr-2"></i>
                        <strong>Chế độ gắn theo ảnh:</strong> Phụ đề sẽ được chia đều cho từng ảnh trong slideshow.
                        Mỗi ảnh sẽ có một phần phụ đề tương ứng.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subtitle_per_image">Số từ/câu mỗi ảnh</label>
                                <select name="subtitle_per_image" id="subtitle_per_image" class="form-control">
                                    <option value="auto" selected>Tự động chia đều</option>
                                    <option value="sentence">Theo câu</option>
                                    <option value="word_count">Theo số từ</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="words_per_image">Số từ mỗi ảnh (nếu chọn theo từ)</label>
                                <input type="number" name="words_per_image" id="words_per_image"
                                       class="form-control" value="10" min="5" max="50">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Timing Settings -->
                <div id="custom-timing-settings" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-clock mr-2"></i>
                        <strong>Tùy chỉnh thời gian:</strong> Bạn có thể điều chỉnh thời gian hiển thị cho từng đoạn phụ đề.
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="subtitle_duration">Thời gian hiển thị mỗi đoạn (giây)</label>
                                <input type="number" name="subtitle_duration" id="subtitle_duration"
                                       class="form-control" value="3" min="1" max="10" step="0.5">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="subtitle_delay">Delay giữa các đoạn (giây)</label>
                                <input type="number" name="subtitle_delay" id="subtitle_delay"
                                       class="form-control" value="0.5" min="0" max="3" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="subtitle_fade">Hiệu ứng fade</label>
                                <select name="subtitle_fade" id="subtitle_fade" class="form-control">
                                    <option value="none">Không có</option>
                                    <option value="in" selected>Fade in</option>
                                    <option value="out">Fade out</option>
                                    <option value="both">Cả hai</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Subtitle Settings -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="subtitle_outline" id="subtitle_outline"
                           class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="subtitle_outline">
                        Viền chữ (giúp chữ rõ hơn)
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
