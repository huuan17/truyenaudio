<!-- Logo Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-image mr-2"></i>Logo & Watermark</h6>
    </div>
    <div class="card-body">
        <!-- Logo Enable/Disable -->
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="enable_logo" id="enable_logo" 
                       class="form-check-input" value="1">
                <label class="form-check-label" for="enable_logo">
                    <strong>Thêm logo vào video</strong>
                </label>
            </div>
        </div>

        <!-- Logo Settings -->
        <div id="logo-settings" style="display: none;">
            <!-- Logo Source -->
            <div class="form-group">
                <label class="form-label">Nguồn logo</label>
                <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                    <label class="btn btn-outline-primary active mr-2 mb-2">
                        <input type="radio" name="logo_source" value="library" checked> 
                        <i class="fas fa-folder mr-1"></i>Thư viện có sẵn
                    </label>
                    <label class="btn btn-outline-primary mb-2">
                        <input type="radio" name="logo_source" value="upload"> 
                        <i class="fas fa-upload mr-1"></i>Upload logo
                    </label>
                </div>
            </div>

            <!-- Logo Library -->
            <div id="logo-library-section">
                <div class="form-group">
                    <label for="logo_library">Chọn logo từ thư viện</label>

                    <!-- Search and filter -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="logo-search" class="form-control" placeholder="Tìm kiếm logo...">
                        </div>
                        <div class="col-md-3">
                            <select id="logo-filter" class="form-control">
                                <option value="">Tất cả logo</option>
                                <option value="png">PNG</option>
                                <option value="svg">SVG</option>
                                <option value="jpg">JPG</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary btn-block" onclick="searchLogoLibrary()">
                                <i class="fas fa-search mr-1"></i>Tìm
                            </button>
                        </div>
                    </div>

                    <!-- Logo results -->
                    <div id="logo-results" class="logo-results" style="max-height: 400px; overflow-y: auto; border: 1px solid #e3e6f0; border-radius: 0.35rem; padding: 1rem;">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Nhấn "Tìm" để hiển thị danh sách logo</p>
                        </div>
                    </div>

                    <!-- Selected logo info -->
                    <div id="selected-logo-info" class="selected-logo-info mt-2" style="display: none;">
                        <div class="alert alert-success">
                            <strong>Đã chọn:</strong> <span id="selected-logo-name"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger float-right" onclick="clearSelectedLogo()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Add new logo button -->
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.logos.index') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-plus mr-1"></i>Quản lý logo
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm ml-2" onclick="searchLogoLibrary()">
                            <i class="fas fa-sync mr-1"></i>Tải lại
                        </button>
                    </div>

                    <input type="hidden" name="selected_logo" id="selected_logo" value="">
                </div>
            </div>

            <!-- Upload Logo -->
            <div id="logo-upload-section" style="display: none;">
                <div class="form-group">
                    <label for="logo_file">Upload logo</label>
                    <input type="file" name="logo_file" id="logo_file" 
                           class="form-control-file" accept="image/*" onchange="previewUploadedLogo(this)">
                    <small class="form-text text-muted">
                        PNG, JPG, GIF (khuyến nghị PNG với nền trong suốt). Tối đa 5MB
                    </small>
                    <div id="uploaded-logo-preview" class="mt-3"></div>
                </div>
            </div>

            <!-- Logo Position & Size Settings -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="logo_position">Vị trí logo</label>
                        <select name="logo_position" id="logo_position" class="form-control">
                            <option value="top-left">Góc trên trái</option>
                            <option value="top-right" selected>Góc trên phải</option>
                            <option value="top-center">Trên giữa</option>
                            <option value="bottom-left">Góc dưới trái</option>
                            <option value="bottom-right">Góc dưới phải</option>
                            <option value="bottom-center">Dưới giữa</option>
                            <option value="center">Giữa màn hình</option>
                            <option value="center-left">Giữa trái</option>
                            <option value="center-right">Giữa phải</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="logo_size">Kích thước logo</label>
                        <select name="logo_size" id="logo_size" class="form-control">
                            <option value="small">Nhỏ (5% màn hình)</option>
                            <option value="medium" selected>Vừa (10% màn hình)</option>
                            <option value="large">Lớn (15% màn hình)</option>
                            <option value="xlarge">Rất lớn (20% màn hình)</option>
                            <option value="custom">Tùy chỉnh</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Custom Size Settings -->
            <div id="custom-size-settings" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="logo_width">Chiều rộng (px)</label>
                            <input type="number" name="logo_width" id="logo_width" 
                                   class="form-control" min="50" max="500" value="100">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="logo_height">Chiều cao (px)</label>
                            <input type="number" name="logo_height" id="logo_height" 
                                   class="form-control" min="50" max="500" value="100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Logo Settings -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="logo_opacity">Độ trong suốt</label>
                        <select name="logo_opacity" id="logo_opacity" class="form-control">
                            <option value="0.3">30% (Rất mờ)</option>
                            <option value="0.5">50% (Mờ)</option>
                            <option value="0.7">70% (Hơi mờ)</option>
                            <option value="1.0" selected>100% (Đậm)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="logo_margin">Khoảng cách từ viền (px)</label>
                        <select name="logo_margin" id="logo_margin" class="form-control">
                            <option value="10">10px</option>
                            <option value="20" selected>20px</option>
                            <option value="30">30px</option>
                            <option value="50">50px</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="logo_duration">Hiển thị logo</label>
                        <select name="logo_duration" id="logo_duration" class="form-control">
                            <option value="full" selected>Toàn bộ video</option>
                            <option value="start">5 giây đầu</option>
                            <option value="end">5 giây cuối</option>
                            <option value="custom">Tùy chỉnh thời gian</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Custom Duration Settings -->
            <div id="custom-duration-settings" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="logo_start_time">Thời gian bắt đầu (giây)</label>
                            <input type="number" name="logo_start_time" id="logo_start_time" 
                                   class="form-control" min="0" value="0" step="0.1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="logo_end_time">Thời gian kết thúc (giây)</label>
                            <input type="number" name="logo_end_time" id="logo_end_time" 
                                   class="form-control" min="0" value="10" step="0.1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform-specific Presets -->
            <div class="form-group mt-4">
                <label class="form-label">Preset theo nền tảng</label>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-dark" onclick="applyLogoPreset('tiktok')">
                        <i class="fab fa-tiktok mr-1"></i>TikTok Preset
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="applyLogoPreset('youtube')">
                        <i class="fab fa-youtube mr-1"></i>YouTube Preset
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetLogoSettings()">
                        <i class="fas fa-undo mr-1"></i>Reset
                    </button>
                </div>
                <small class="form-text text-muted">
                    <strong>TikTok:</strong> Góc trên phải, kích thước nhỏ, độ mờ 70%<br>
                    <strong>YouTube:</strong> Góc dưới phải, kích thước vừa, độ mờ 100%
                </small>
            </div>
        </div>
    </div>
</div>
