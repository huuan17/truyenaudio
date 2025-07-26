<!-- Batch Generation Section -->
<div class="card mb-4" id="batch-section">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-layer-group mr-2"></i>Tạo nhiều video</h5>
            <div class="d-flex align-items-center">
                <span class="badge badge-light mr-2">
                    <i class="fas fa-video mr-1"></i>
                    <span class="batch-video-count">3</span> video
                </span>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light" onclick="addBatchVideo()" title="Thêm video">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-light" onclick="removeBatchVideo()" title="Bớt video">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="alert alert-info m-3 mb-0">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Hướng dẫn:</strong> Mỗi video có thể có nội dung hoàn toàn khác nhau (text, audio, ảnh, video nền, subtitle).
            Các video sẽ được xử lý tuần tự trong queue.
        </div>

        <!-- Batch Mode Selection -->
        <div class="bg-light p-3 border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label mb-2"><strong>Chế độ tạo batch:</strong></label>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-primary active">
                            <input type="radio" name="batch_mode" value="multiple_content" checked>
                            <i class="fas fa-list mr-1"></i>Nội dung khác nhau
                        </label>
                        <label class="btn btn-outline-secondary">
                            <input type="radio" name="batch_mode" value="template">
                            <i class="fas fa-copy mr-1"></i>Dùng template
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label mb-2"><strong>Số lượng video:</strong></label>
                    <select name="batch_count" id="batch_count" class="form-control">
                        <option value="2">2 video</option>
                        <option value="3" selected>3 video</option>
                        <option value="5">5 video</option>
                        <option value="10">10 video</option>
                        <option value="15">15 video</option>
                        <option value="20">20 video</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Multiple Content Mode -->
        <div id="multiple-content-mode">
            <!-- Dynamic Video Items Container -->
            <div id="batch-videos-container" class="p-3">
                <!-- Video items will be generated here by JavaScript -->
            </div>
        </div>

        <!-- Template Mode -->
        <div id="template-mode" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Template mode:</strong> Sử dụng cài đặt từ form chính làm template, 
                chỉ thay đổi nội dung text/audio cho từng video.
            </div>

            <div class="form-group">
                <label for="template_texts">Danh sách nội dung (mỗi dòng = 1 video)</label>
                <textarea name="template_texts" id="template_texts" class="form-control" rows="8" 
                          placeholder="Video 1: Nội dung đầu tiên...
Video 2: Nội dung thứ hai...
Video 3: Nội dung thứ ba..."></textarea>
                <small class="form-text text-muted">
                    Mỗi dòng sẽ tạo ra 1 video riêng biệt với cùng cài đặt.
                </small>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="template_auto_name" id="template_auto_name" 
                           class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="template_auto_name">
                        Tự động đặt tên file (video_1, video_2, ...)
                    </label>
                </div>
            </div>
        </div>

        <!-- Batch Processing Settings -->
        <div class="form-group mt-4">
            <h6>Cài đặt xử lý batch</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="batch_priority">Độ ưu tiên</label>
                        <select name="batch_priority" id="batch_priority" class="form-control">
                            <option value="low">Thấp (Xử lý khi rảnh)</option>
                            <option value="normal" selected>Bình thường</option>
                            <option value="high">Cao (Ưu tiên xử lý)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="batch_delay">Delay giữa các video (giây)</label>
                        <select name="batch_delay" id="batch_delay" class="form-control">
                            <option value="0">Không delay</option>
                            <option value="5" selected>5 giây</option>
                            <option value="10">10 giây</option>
                            <option value="30">30 giây</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-check">
                <input type="checkbox" name="batch_notify" id="batch_notify"
                       class="form-check-input" value="1" checked>
                <label class="form-check-label" for="batch_notify">
                    Thông báo khi hoàn thành batch
                </label>
            </div>
        </div>

        <!-- Platform Selection -->
        <div class="bg-light p-3 border-top border-bottom">
            <div class="row">
                <div class="col-md-8">
                    <label class="form-label mb-2"><strong>Chọn platform:</strong></label>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-info">
                            <input type="radio" name="platform" value="tiktok">
                            <i class="fab fa-tiktok mr-1"></i>TikTok
                        </label>
                        <label class="btn btn-outline-danger">
                            <input type="radio" name="platform" value="youtube">
                            <i class="fab fa-youtube mr-1"></i>YouTube
                        </label>
                        <label class="btn btn-outline-success active">
                            <input type="radio" name="platform" value="both" checked>
                            <i class="fas fa-globe mr-1"></i>Cả hai
                        </label>
                        <label class="btn btn-outline-secondary">
                            <input type="radio" name="platform" value="none">
                            <i class="fas fa-save mr-1"></i>Chỉ lưu
                        </label>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <label class="form-label mb-2">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-layer-group mr-2"></i>Tạo Batch Video
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
