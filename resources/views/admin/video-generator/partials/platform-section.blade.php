<!-- Platform Selection Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-share-alt mr-2"></i>Chọn kênh phát hành</h6>
    </div>
    <div class="card-body">
        <!-- Platform Selection -->
        <div class="form-group">
            <label class="form-label">Nền tảng</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                <label class="btn btn-outline-dark active mr-2 mb-2">
                    <input type="radio" name="platform" value="tiktok" checked> 
                    <i class="fab fa-tiktok mr-1"></i>TikTok
                </label>
                <label class="btn btn-outline-danger mr-2 mb-2">
                    <input type="radio" name="platform" value="youtube"> 
                    <i class="fab fa-youtube mr-1"></i>YouTube
                </label>
                <label class="btn btn-outline-info mb-2">
                    <input type="radio" name="platform" value="both"> 
                    <i class="fas fa-globe mr-1"></i>Cả hai
                </label>
            </div>
        </div>

        <!-- TikTok Settings -->
        <div id="tiktok-settings">
            <h6 class="text-dark mb-3"><i class="fab fa-tiktok mr-2"></i>Cài đặt TikTok</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_resolution">Độ phân giải</label>
                        <select name="tiktok_resolution" id="tiktok_resolution" class="form-control">
                            <option value="1080x1920" selected>1080x1920 (9:16 - Khuyến nghị)</option>
                            <option value="720x1280">720x1280 (9:16)</option>
                            <option value="1080x1080">1080x1080 (1:1)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_fps">FPS</label>
                        <select name="tiktok_fps" id="tiktok_fps" class="form-control">
                            <option value="24">24 FPS</option>
                            <option value="30" selected>30 FPS (Khuyến nghị)</option>
                            <option value="60">60 FPS</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="tiktok_duration">Thời lượng tối đa</label>
                <select name="tiktok_duration" id="tiktok_duration" class="form-control">
                    <option value="15">15 giây</option>
                    <option value="30">30 giây</option>
                    <option value="60" selected>60 giây</option>
                    <option value="180">3 phút</option>
                    <option value="600">10 phút</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tiktok_output_name">Tên file output</label>
                <input type="text" name="tiktok_output_name" id="tiktok_output_name" 
                       class="form-control" placeholder="tiktok_video_[timestamp]">
                <small class="form-text text-muted">
                    Để trống để tự động tạo tên. Hỗ trợ: [timestamp], [date], [time]
                </small>
            </div>
        </div>

        <!-- YouTube Settings -->
        <div id="youtube-settings" style="display: none;">
            <h6 class="text-danger mb-3"><i class="fab fa-youtube mr-2"></i>Cài đặt YouTube</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_resolution">Độ phân giải</label>
                        <select name="youtube_resolution" id="youtube_resolution" class="form-control">
                            <option value="1920x1080" selected>1920x1080 (16:9 - Khuyến nghị)</option>
                            <option value="1280x720">1280x720 (16:9)</option>
                            <option value="1080x1920">1080x1920 (9:16 - Shorts)</option>
                            <option value="1080x1080">1080x1080 (1:1)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_fps">FPS</label>
                        <select name="youtube_fps" id="youtube_fps" class="form-control">
                            <option value="24">24 FPS</option>
                            <option value="30" selected>30 FPS (Khuyến nghị)</option>
                            <option value="60">60 FPS</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="youtube_quality">Chất lượng video</label>
                <select name="youtube_quality" id="youtube_quality" class="form-control">
                    <option value="medium">Medium (Nhanh)</option>
                    <option value="high" selected>High (Khuyến nghị)</option>
                    <option value="very_high">Very High (Chậm)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="youtube_output_name">Tên file output</label>
                <input type="text" name="youtube_output_name" id="youtube_output_name" 
                       class="form-control" placeholder="youtube_video_[timestamp]">
                <small class="form-text text-muted">
                    Để trống để tự động tạo tên. Hỗ trợ: [timestamp], [date], [time]
                </small>
            </div>
        </div>

        <!-- Both Platforms Settings -->
        <div id="both-settings" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Tạo cho cả hai nền tảng:</strong> Hệ thống sẽ tạo 2 video với cài đặt tối ưu cho từng nền tảng.
            </div>
            
            <div class="form-group">
                <label for="both_output_prefix">Tiền tố tên file</label>
                <input type="text" name="both_output_prefix" id="both_output_prefix" 
                       class="form-control" placeholder="video_[timestamp]">
                <small class="form-text text-muted">
                    Sẽ tạo: [prefix]_tiktok.mp4 và [prefix]_youtube.mp4
                </small>
            </div>
        </div>
    </div>
</div>
