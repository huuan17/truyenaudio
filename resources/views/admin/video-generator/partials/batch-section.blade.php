<!-- Batch Generation Section -->
<div class="card mb-4" id="batch-section" style="display: none;">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-layer-group mr-2"></i>Tạo nhiều video</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Chế độ batch:</strong> Tạo nhiều video cùng lúc với nội dung khác nhau. 
            Mỗi video sẽ được xử lý riêng biệt trong queue.
        </div>

        <!-- Batch Mode Selection -->
        <div class="form-group">
            <label class="form-label">Cách tạo batch</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-primary active mr-2 mb-2">
                    <input type="radio" name="batch_mode" value="multiple_content" checked> 
                    <i class="fas fa-list mr-1"></i>Nhiều nội dung khác nhau
                </label>
                <label class="btn btn-outline-primary mb-2">
                    <input type="radio" name="batch_mode" value="template"> 
                    <i class="fas fa-copy mr-1"></i>Dùng template
                </label>
            </div>
        </div>

        <!-- Multiple Content Mode -->
        <div id="multiple-content-mode">
            <div class="form-group">
                <label for="batch_count">Số lượng video</label>
                <select name="batch_count" id="batch_count" class="form-control">
                    <option value="2">2 video</option>
                    <option value="3" selected>3 video</option>
                    <option value="5">5 video</option>
                    <option value="10">10 video</option>
                </select>
            </div>

            <!-- Dynamic Video Items Container -->
            <div id="batch-videos-container">
                <!-- Video items will be generated here by JavaScript -->
            </div>

            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-primary" onclick="addBatchVideo()">
                    <i class="fas fa-plus mr-2"></i>Thêm video
                </button>
                <button type="button" class="btn btn-outline-danger ml-2" onclick="removeBatchVideo()">
                    <i class="fas fa-minus mr-2"></i>Bớt video
                </button>
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
    </div>
</div>
