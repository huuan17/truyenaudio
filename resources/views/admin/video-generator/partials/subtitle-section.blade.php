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
