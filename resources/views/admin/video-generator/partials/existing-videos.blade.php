<!-- Existing Videos Section -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-folder mr-2"></i>Video đã tạo</h6>
    </div>
    <div class="card-body">
        <!-- Platform Filter -->
        <div class="form-group">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" onclick="filterVideos('all')">
                    <i class="fas fa-globe mr-1"></i>Tất cả
                </button>
                <button type="button" class="btn btn-outline-dark" onclick="filterVideos('tiktok')">
                    <i class="fab fa-tiktok mr-1"></i>TikTok
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="filterVideos('youtube')">
                    <i class="fab fa-youtube mr-1"></i>YouTube
                </button>
            </div>
        </div>

        <!-- TikTok Videos -->
        <div id="tiktok-videos" class="video-list">
            <h6 class="text-dark mb-3">
                <i class="fab fa-tiktok mr-2"></i>TikTok Videos ({{ count($tiktokVideos ?? []) }})
            </h6>
            
            @if(isset($tiktokVideos) && count($tiktokVideos) > 0)
                <div class="mb-3">
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

                <div class="list-group list-group-flush">
                    @foreach($tiktokVideos as $video)
                    <div class="list-group-item p-2">
                        <div class="d-flex align-items-center">
                            <div class="form-check mr-3">
                                <input type="checkbox" name="tiktok_videos[]" value="{{ $video['name'] }}" 
                                       class="form-check-input">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $video['name'] }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-hdd mr-1"></i>{{ $video['size'] }}
                                            <i class="fas fa-calendar ml-2 mr-1"></i>{{ $video['created'] }}
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.video-generator.download', ['tiktok', $video['name']]) }}" 
                                           class="btn btn-sm btn-outline-success" title="Tải xuống">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-3">
                    <i class="fas fa-video fa-2x mb-2"></i>
                    <p>Chưa có video TikTok nào</p>
                </div>
            @endif
        </div>

        <!-- YouTube Videos -->
        <div id="youtube-videos" class="video-list mt-4">
            <h6 class="text-danger mb-3">
                <i class="fab fa-youtube mr-2"></i>YouTube Videos ({{ count($youtubeVideos ?? []) }})
            </h6>
            
            @if(isset($youtubeVideos) && count($youtubeVideos) > 0)
                <div class="mb-3">
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

                <div class="list-group list-group-flush">
                    @foreach($youtubeVideos as $video)
                    <div class="list-group-item p-2">
                        <div class="d-flex align-items-center">
                            <div class="form-check mr-3">
                                <input type="checkbox" name="youtube_videos[]" value="{{ $video['name'] }}" 
                                       class="form-check-input">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $video['name'] }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-hdd mr-1"></i>{{ $video['size'] }}
                                            <i class="fas fa-calendar ml-2 mr-1"></i>{{ $video['created'] }}
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.video-generator.download', ['youtube', $video['name']]) }}" 
                                           class="btn btn-sm btn-outline-success" title="Tải xuống">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-3">
                    <i class="fas fa-video fa-2x mb-2"></i>
                    <p>Chưa có video YouTube nào</p>
                </div>
            @endif
        </div>
    </div>
</div>
