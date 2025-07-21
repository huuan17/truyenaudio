<div class="platform-form">
    
    <!-- Mode Selection -->
    <div class="form-section">
        <h6><i class="fas fa-cog mr-2"></i>Chế độ tạo video TikTok</h6>
        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
            <label class="btn btn-outline-primary active">
                <input type="radio" name="tiktok_creation_mode" value="single" checked onchange="switchTiktokMode(this.value)">
                <i class="fas fa-video mr-1"></i>Đơn lẻ
            </label>
            <label class="btn btn-outline-success">
                <input type="radio" name="tiktok_creation_mode" value="batch" onchange="switchTiktokMode(this.value)">
                <i class="fas fa-layer-group mr-1"></i>Hàng loạt
            </label>
        </div>
        <small class="text-muted mt-2 d-block" id="tiktok_mode_description">
            Tạo một video TikTok từ kịch bản và media files
        </small>
    </div>

    <!-- Single Video Form -->
    <div id="tiktok-single-form-container">
        <form id="tiktok-single-form" method="POST" enctype="multipart/form-data" onsubmit="return validateTiktokSingleForm()">
            @csrf
            <input type="hidden" name="platform" value="tiktok">
            
            <!-- Script Input -->
            <div class="form-section">
                <h6><i class="fas fa-edit mr-2"></i>Kịch bản Review</h6>
                <div class="form-group">
                    <label for="tiktok_script_text">Nội dung kịch bản *</label>
                    <x-tinymce-editor
                        name="script_text"
                        id="tiktok_script_text"
                        :value="old('script_text')"
                        :height="200"
                        placeholder="Nhập kịch bản review sản phẩm..."
                        toolbar="basic"
                        required />
                    <small class="form-text text-muted">
                        Tối đa 5000 ký tự. Nên viết kịch bản ngắn gọn, súc tích cho TikTok.
                    </small>
                </div>
            </div>

            <!-- Media Files -->
            <div class="form-section">
                <h6><i class="fas fa-file-video mr-2"></i>Media Files</h6>

                <!-- Media Type Selection -->
                <div class="form-group">
                    <label>Loại media *</label>
                    <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                        <label class="btn btn-outline-primary active">
                            <input type="radio" name="media_type" value="video" checked onchange="switchTiktokMediaType(this.value)">
                            <i class="fas fa-video mr-1"></i>Video
                        </label>
                        <label class="btn btn-outline-info">
                            <input type="radio" name="media_type" value="images" onchange="switchTiktokMediaType(this.value)">
                            <i class="fas fa-images mr-1"></i>Slide ảnh
                        </label>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        Chọn video để sử dụng video có sẵn, hoặc slide ảnh để tạo video từ nhiều ảnh
                    </small>
                </div>

                <!-- Video Upload -->
                <div id="tiktok-video-upload" class="media-upload-section">
                    <div class="form-group">
                        <label for="tiktok_product_video">Video sản phẩm *</label>
                        <input type="file" name="product_video" id="tiktok_product_video"
                               class="form-control-file" accept="video/mp4,video/avi,video/mov"
                               onchange="previewFile(this, 'tiktok_video_preview')">
                        <small class="form-text text-muted">MP4, AVI, MOV. Tối đa 100MB</small>
                        <div id="tiktok_video_preview"></div>
                    </div>
                </div>

                <!-- Multiple Images Upload -->
                <div id="tiktok-images-upload" class="media-upload-section" style="display: none;">
                    <div class="form-group">
                        <label for="tiktok_product_images">Ảnh sản phẩm (Nhiều ảnh) *</label>
                        <input type="file" name="product_images[]" id="tiktok_product_images"
                               class="form-control-file" accept="image/jpeg,image/jpg,image/png" multiple
                               onchange="previewMultipleImages(this, 'tiktok_images_preview')">
                        <small class="form-text text-muted">
                            JPG, PNG. Tối đa 10MB mỗi ảnh. Chọn nhiều ảnh để tạo slide show.
                            <br>Thứ tự ảnh sẽ theo thứ tự chọn file.
                        </small>
                        <div id="tiktok_images_preview" class="images-preview-container mt-3"></div>
                    </div>

                    <!-- Slide Settings -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="slide_duration">Thời gian mỗi ảnh (giây)</label>
                                <select name="slide_duration" id="slide_duration" class="form-control">
                                    <option value="2">2 giây</option>
                                    <option value="3" selected>3 giây</option>
                                    <option value="4">4 giây</option>
                                    <option value="5">5 giây</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="slide_transition">Hiệu ứng chuyển</label>
                                <select name="slide_transition" id="slide_transition" class="form-control">
                                    <option value="fade">Fade</option>
                                    <option value="slide" selected>Slide</option>
                                    <option value="zoom">Zoom</option>
                                    <option value="none">Không có</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audio Settings -->
            @include('admin.video-generator.partials.audio-settings', ['prefix' => 'tiktok'])

            <!-- Logo Settings -->
            @include('admin.video-generator.partials.logo-settings', ['prefix' => 'tiktok'])

            <!-- Subtitle Settings -->
            @include('admin.video-generator.partials.subtitle-settings', ['prefix' => 'tiktok', 'platform' => 'tiktok'])

            <!-- Output Settings -->
            @include('admin.video-generator.partials.output-settings', ['prefix' => 'tiktok'])

            <!-- Channel Settings -->
            @include('admin.video-generator.partials.channel-settings', ['prefix' => 'tiktok', 'channels' => $tiktokChannels])

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" id="tiktok-single-submit" class="btn btn-primary btn-lg">
                    <i class="fab fa-tiktok mr-2"></i>Tạo Video TikTok
                </button>
            </div>
        </form>
    </div>

    <!-- Batch Video Form -->
    <div id="tiktok-batch-form-container" style="display: none;">
        <form id="tiktok-batch-form" method="POST" enctype="multipart/form-data" onsubmit="return validateTiktokBatchForm()">
            @csrf
            <input type="hidden" name="platform" value="tiktok">
            
            <!-- Batch Settings -->
            <div class="form-section">
                <h6><i class="fas fa-layer-group mr-2"></i>Cài đặt chung cho Batch</h6>
                
                <!-- Audio Settings -->
                @include('admin.video-generator.partials.audio-settings', ['prefix' => 'tiktok_batch'])

                <!-- Subtitle Settings -->
                @include('admin.video-generator.partials.subtitle-settings', ['prefix' => 'tiktok_batch', 'platform' => 'tiktok', 'batch' => true])

                <!-- Channel Settings -->
                @include('admin.video-generator.partials.channel-settings', ['prefix' => 'tiktok_batch', 'channels' => $tiktokChannels])
            </div>

            <!-- Video Items Container -->
            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6><i class="fas fa-video mr-2"></i>Video Items</h6>
                    <button type="button" class="btn btn-success btn-sm" onclick="addTiktokVideoItem()">
                        <i class="fas fa-plus mr-1"></i>Thêm Video
                    </button>
                </div>
                
                <div id="tiktok-video-items-container">
                    <!-- Video items will be added here -->
                </div>
                
                <div id="tiktok-empty-state" class="text-center py-4">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có video nào. Click "Thêm Video" để bắt đầu.</p>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tổng số video: <span id="tiktok-video-count">0</span>
                    </small>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" id="tiktok-batch-submit" class="btn btn-success btn-lg" disabled>
                    <i class="fas fa-layer-group mr-2"></i>Tạo Batch Video TikTok
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Videos -->
    <div class="form-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6><i class="fas fa-folder mr-2"></i>Video TikTok đã tạo ({{ count($tiktokVideos) }})</h6>
            @if(count($tiktokVideos) > 0)
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllVideos('tiktok', true)">
                    <i class="fas fa-check-square mr-1"></i>Chọn tất cả
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ml-1" onclick="selectAllVideos('tiktok', false)">
                    <i class="fas fa-square mr-1"></i>Bỏ chọn
                </button>
                <button type="button" class="btn btn-sm btn-danger ml-1" onclick="deleteVideos('tiktok')">
                    <i class="fas fa-trash mr-1"></i>Xóa đã chọn
                </button>
            </div>
            @endif
        </div>
        
        @if(count($tiktokVideos) > 0)
        <div class="video-list">
            @foreach($tiktokVideos as $video)
            <div class="video-item">
                <div class="row align-items-center">
                    <div class="col-md-1">
                        <input type="checkbox" name="tiktok_videos[]" value="{{ $video['name'] }}" class="form-check-input">
                    </div>
                    <div class="col-md-6">
                        <strong>{{ $video['name'] }}</strong>
                        <br><small class="text-muted">{{ $video['size'] }} • {{ $video['created'] }}</small>
                    </div>
                    <div class="col-md-5 text-right">
                        <a href="{{ route('admin.video-generator.download', ['platform' => 'tiktok', 'filename' => $video['name']]) }}" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download mr-1"></i>Tải về
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-4">
            <i class="fas fa-video fa-3x text-muted mb-3"></i>
            <p class="text-muted">Chưa có video TikTok nào được tạo.</p>
        </div>
        @endif
    </div>

</div>
