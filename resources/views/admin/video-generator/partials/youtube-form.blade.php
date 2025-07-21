<div class="platform-form">
    
    <!-- Mode Selection -->
    <div class="form-section">
        <h6><i class="fas fa-cog mr-2"></i>Chế độ tạo video YouTube</h6>
        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
            <label class="btn btn-outline-primary active">
                <input type="radio" name="youtube_creation_mode" value="single" checked onchange="switchYoutubeMode(this.value)">
                <i class="fas fa-video mr-1"></i>Đơn lẻ
            </label>
            <label class="btn btn-outline-success">
                <input type="radio" name="youtube_creation_mode" value="batch" onchange="switchYoutubeMode(this.value)">
                <i class="fas fa-layer-group mr-1"></i>Hàng loạt
            </label>
        </div>
        <small class="text-muted mt-2 d-block" id="youtube_mode_description">
            Tạo một video YouTube từ audio và visual content
        </small>
    </div>

    <!-- Single Video Form -->
    <div id="youtube-single-form-container">
        <form id="youtube-single-form" method="POST" enctype="multipart/form-data" onsubmit="return validateYoutubeSingleForm()">
            @csrf
            <input type="hidden" name="platform" value="youtube">
            
            <!-- Audio Source -->
            <div class="form-section">
                <h6><i class="fas fa-microphone mr-2"></i>Nguồn Audio</h6>
                <div class="btn-group btn-group-toggle w-100 mb-3" data-toggle="buttons">
                    <label class="btn btn-outline-info active">
                        <input type="radio" name="audio_source" value="text" checked onchange="toggleYoutubeAudioSource()">
                        <i class="fas fa-keyboard mr-1"></i>Text-to-Speech
                    </label>
                    <label class="btn btn-outline-info">
                        <input type="radio" name="audio_source" value="file" onchange="toggleYoutubeAudioSource()">
                        <i class="fas fa-file-audio mr-1"></i>Upload Audio
                    </label>
                </div>

                <!-- Text Content -->
                <div id="youtube-text-content" class="form-group">
                    <label for="youtube_text_content">Nội dung text *</label>
                    <x-tinymce-editor
                        name="text_content"
                        id="youtube_text_content"
                        :value="old('text_content')"
                        :height="200"
                        placeholder="Nhập nội dung để chuyển thành giọng nói..."
                        toolbar="basic"
                        required />
                    <small class="form-text text-muted">Tối đa 5000 ký tự</small>
                </div>

                <!-- Audio File -->
                <div id="youtube-audio-file" class="form-group" style="display: none;">
                    <label for="youtube_audio_file">File audio *</label>
                    <input type="file" name="audio_file" id="youtube_audio_file" 
                           class="form-control-file" accept="audio/mp3,audio/wav,audio/m4a"
                           onchange="previewFile(this, 'youtube_audio_preview')">
                    <small class="form-text text-muted">MP3, WAV, M4A. Tối đa 50MB</small>
                    <div id="youtube_audio_preview"></div>
                </div>
            </div>

            <!-- Audio Settings (only for TTS) -->
            <div id="youtube-audio-settings">
                @include('admin.video-generator.partials.audio-settings', ['prefix' => 'youtube'])
            </div>

            <!-- Video Content -->
            <div class="form-section">
                <h6><i class="fas fa-film mr-2"></i>Nội dung Video</h6>
                <div class="btn-group btn-group-toggle w-100 mb-3" data-toggle="buttons">
                    <label class="btn btn-outline-success active">
                        <input type="radio" name="video_content_type" value="images" checked onchange="toggleYoutubeVideoContent()">
                        <i class="fas fa-images mr-1"></i>Slideshow ảnh
                    </label>
                    <label class="btn btn-outline-success">
                        <input type="radio" name="video_content_type" value="video" onchange="toggleYoutubeVideoContent()">
                        <i class="fas fa-video mr-1"></i>Video nền
                    </label>
                    <label class="btn btn-outline-success">
                        <input type="radio" name="video_content_type" value="mixed" onchange="toggleYoutubeVideoContent()">
                        <i class="fas fa-layer-group mr-1"></i>Kết hợp
                    </label>
                </div>

                <!-- Images Section -->
                <div id="youtube-images-section">
                    <div class="form-group">
                        <label for="youtube_images">Ảnh cho slideshow *</label>
                        <input type="file" name="images[]" id="youtube_images" 
                               class="form-control-file" accept="image/jpeg,image/jpg,image/png,image/gif" multiple required>
                        <small class="form-text text-muted">JPG, PNG, GIF. Tối đa 10MB mỗi file. Chọn nhiều ảnh.</small>
                    </div>
                </div>

                <!-- Background Video Section -->
                <div id="youtube-video-section" style="display: none;">
                    <div class="form-group">
                        <label for="youtube_background_video">Video nền *</label>
                        <input type="file" name="background_video" id="youtube_background_video" 
                               class="form-control-file" accept="video/mp4,video/avi,video/mov"
                               onchange="previewFile(this, 'youtube_bg_video_preview')">
                        <small class="form-text text-muted">MP4, AVI, MOV. Tối đa 500MB</small>
                        <div id="youtube_bg_video_preview"></div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="remove_video_audio" id="youtube_remove_video_audio" class="form-check-input" value="1">
                        <label class="form-check-label" for="youtube_remove_video_audio">
                            Xóa âm thanh của video nền
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="video_loop" id="youtube_video_loop" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="youtube_video_loop">
                            Lặp video nền nếu ngắn hơn audio
                        </label>
                    </div>
                </div>

                <!-- Video Settings -->
                <div id="youtube-video-settings">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="youtube_image_duration">Thời lượng mỗi ảnh (giây)</label>
                                <input type="number" name="image_duration" id="youtube_image_duration" 
                                       class="form-control" value="3" min="0.5" max="10" step="0.5">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="youtube_transition_effects">Hiệu ứng chuyển cảnh</label>
                                <select name="transition_effects[]" id="youtube_transition_effects" 
                                        class="form-control" multiple>
                                    <option value="fade" selected>Fade</option>
                                    <option value="slide">Slide</option>
                                    <option value="zoom">Zoom</option>
                                    <option value="rotate">Rotate</option>
                                    <option value="blur">Blur</option>
                                    <option value="wipe">Wipe</option>
                                </select>
                                <small class="form-text text-muted">Giữ Ctrl để chọn nhiều hiệu ứng</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subtitle Settings -->
            @include('admin.video-generator.partials.subtitle-settings', ['prefix' => 'youtube', 'platform' => 'youtube'])

            <!-- Output Settings -->
            @include('admin.video-generator.partials.output-settings', ['prefix' => 'youtube'])

            <!-- Channel Settings -->
            @include('admin.video-generator.partials.channel-settings', ['prefix' => 'youtube', 'channels' => $youtubeChannels])

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" id="youtube-single-submit" class="btn btn-danger btn-lg">
                    <i class="fab fa-youtube mr-2"></i>Tạo Video YouTube
                </button>
            </div>
        </form>
    </div>

    <!-- Batch Video Form -->
    <div id="youtube-batch-form-container" style="display: none;">
        <form id="youtube-batch-form" method="POST" enctype="multipart/form-data" onsubmit="return validateYoutubeBatchForm()">
            @csrf
            <input type="hidden" name="platform" value="youtube">
            
            <!-- Batch Settings -->
            <div class="form-section">
                <h6><i class="fas fa-layer-group mr-2"></i>Cài đặt chung cho Batch</h6>
                
                <!-- Audio Settings -->
                @include('admin.video-generator.partials.audio-settings', ['prefix' => 'youtube_batch'])

                <!-- Video Settings -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="youtube_batch_image_duration">Thời lượng mỗi ảnh (giây)</label>
                            <input type="number" name="image_duration" id="youtube_batch_image_duration" 
                                   class="form-control" value="3" min="0.5" max="10" step="0.5" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="youtube_batch_transition_effects">Hiệu ứng chuyển cảnh</label>
                            <select name="transition_effects[]" id="youtube_batch_transition_effects" 
                                    class="form-control" multiple required>
                                <option value="fade" selected>Fade</option>
                                <option value="slide">Slide</option>
                                <option value="zoom">Zoom</option>
                                <option value="rotate">Rotate</option>
                                <option value="blur">Blur</option>
                                <option value="wipe">Wipe</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="video_loop" id="youtube_batch_video_loop" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="youtube_batch_video_loop">
                                Lặp video nền
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="remove_video_audio" id="youtube_batch_remove_video_audio" class="form-check-input" value="1">
                            <label class="form-check-label" for="youtube_batch_remove_video_audio">
                                Xóa âm thanh video nền
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Subtitle Settings -->
                @include('admin.video-generator.partials.subtitle-settings', ['prefix' => 'youtube_batch', 'platform' => 'youtube', 'batch' => true])

                <!-- Channel Settings -->
                @include('admin.video-generator.partials.channel-settings', ['prefix' => 'youtube_batch', 'channels' => $youtubeChannels])
            </div>

            <!-- Video Items Container -->
            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6><i class="fas fa-video mr-2"></i>Video Items</h6>
                    <button type="button" class="btn btn-success btn-sm" onclick="addYoutubeVideoItem()">
                        <i class="fas fa-plus mr-1"></i>Thêm Video
                    </button>
                </div>
                
                <div id="youtube-video-items-container">
                    <!-- Video items will be added here -->
                </div>
                
                <div id="youtube-empty-state" class="text-center py-4">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có video nào. Click "Thêm Video" để bắt đầu.</p>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tổng số video: <span id="youtube-video-count">0</span>
                    </small>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" id="youtube-batch-submit" class="btn btn-success btn-lg" disabled>
                    <i class="fas fa-layer-group mr-2"></i>Tạo Batch Video YouTube
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Videos -->
    <div class="form-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6><i class="fas fa-folder mr-2"></i>Video YouTube đã tạo ({{ count($youtubeVideos) }})</h6>
            @if(count($youtubeVideos) > 0)
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllVideos('youtube', true)">
                    <i class="fas fa-check-square mr-1"></i>Chọn tất cả
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ml-1" onclick="selectAllVideos('youtube', false)">
                    <i class="fas fa-square mr-1"></i>Bỏ chọn
                </button>
                <button type="button" class="btn btn-sm btn-danger ml-1" onclick="deleteVideos('youtube')">
                    <i class="fas fa-trash mr-1"></i>Xóa đã chọn
                </button>
            </div>
            @endif
        </div>
        
        @if(count($youtubeVideos) > 0)
        <div class="video-list">
            @foreach($youtubeVideos as $video)
            <div class="video-item">
                <div class="row align-items-center">
                    <div class="col-md-1">
                        <input type="checkbox" name="youtube_videos[]" value="{{ $video['name'] }}" class="form-check-input">
                    </div>
                    <div class="col-md-6">
                        <strong>{{ $video['name'] }}</strong>
                        <br><small class="text-muted">{{ $video['size'] }} • {{ $video['created'] }}</small>
                    </div>
                    <div class="col-md-5 text-right">
                        <a href="{{ route('admin.video-generator.download', ['platform' => 'youtube', 'filename' => $video['name']]) }}" 
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
            <p class="text-muted">Chưa có video YouTube nào được tạo.</p>
        </div>
        @endif
    </div>

</div>
