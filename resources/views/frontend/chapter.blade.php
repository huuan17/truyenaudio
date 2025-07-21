@extends('layouts.frontend')

@section('title', 'Chương ' . $chapter->chapter_number . ($chapter->title ? ': ' . $chapter->title : '') . ' - ' . $story->title)
@section('description', 'Nghe chương ' . $chapter->chapter_number . ' của truyện ' . $story->title . ' tại Audio Lara')

@section('content')
<div class="container py-4">
    
    <!-- Breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('story.show', $story->slug) }}">{{ $story->title }}</a>
                    </li>
                    <li class="breadcrumb-item active">Chương {{ $chapter->chapter_number }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Chapter Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="h4 fw-bold mb-2">{{ $story->title }}</h1>
                    <h2 class="h5 text-primary mb-3">
                        Chương {{ $chapter->chapter_number }}
                        @if($chapter->title)
                            : {{ $chapter->title }}
                        @endif
                    </h2>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        @if($prevChapter)
                            <a href="{{ route('chapter.show.vietnamese', [$story->slug, $prevChapter->chapter_number]) }}"
                               class="btn btn-outline-primary">
                                <i class="fas fa-chevron-left me-1"></i>Chương trước
                            </a>
                        @endif

                        <a href="{{ route('story.show.vietnamese', $story->slug) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>Danh sách chương
                        </a>

                        @if($nextChapter)
                            <a href="{{ route('chapter.show.vietnamese', [$story->slug, $nextChapter->chapter_number]) }}"
                               class="btn btn-outline-primary">
                                Chương tiếp <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Audio Player -->
    @if($chapter->audio_file_path && file_exists($chapter->audio_file_path))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                            <i class="fas fa-headphones me-2"></i>Audio Player
                        </h5>
                        <div class="d-flex align-items-center">
                            <small class="me-2">Chất lượng:</small>
                            <span class="badge badge-light">MP3</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="enhanced-audio-player">
                        <!-- Main Audio Element -->
                        <audio id="chapterAudio" preload="metadata" class="d-none">
                            <source src="{{ route('chapter.audio', $chapter->id) }}" type="audio/mpeg">
                            Trình duyệt của bạn không hỗ trợ audio HTML5.
                        </audio>

                        <!-- Custom Player Controls -->
                        <div class="player-controls">
                            <!-- Progress Bar -->
                            <div class="progress-container mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="current-time text-muted">00:00</span>
                                    <span class="total-time text-muted">00:00</span>
                                </div>
                                <div class="progress" style="height: 8px; cursor: pointer;" id="progressBar">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Control Buttons -->
                            <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                                <!-- Previous Chapter -->
                                @if($prevChapter)
                                <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='{{ route('chapter.show.vietnamese', [$story->slug, $prevChapter->chapter_number]) }}'">
                                    <i class="fas fa-step-backward"></i>
                                </button>
                                @endif

                                <!-- Rewind 10s -->
                                <button class="btn btn-outline-info btn-sm" onclick="rewind()">
                                    <i class="fas fa-undo"></i> 10s
                                </button>

                                <!-- Play/Pause -->
                                <button class="btn btn-primary btn-lg" id="playPauseBtn" onclick="togglePlayPause()">
                                    <i class="fas fa-play" id="playPauseIcon"></i>
                                </button>

                                <!-- Forward 10s -->
                                <button class="btn btn-outline-info btn-sm" onclick="forward()">
                                    10s <i class="fas fa-redo"></i>
                                </button>

                                <!-- Next Chapter -->
                                @if($nextChapter)
                                <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='{{ route('chapter.show.vietnamese', [$story->slug, $nextChapter->chapter_number]) }}'">
                                    <i class="fas fa-step-forward"></i>
                                </button>
                                @endif
                            </div>

                            <!-- Speed and Volume Controls -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <label class="form-label me-2 mb-0">
                                            <i class="fas fa-tachometer-alt"></i> Tốc độ:
                                        </label>
                                        <select class="form-select form-select-sm" id="speedControl" onchange="changeSpeed()">
                                            <option value="0.5">0.5x</option>
                                            <option value="0.75">0.75x</option>
                                            <option value="1" selected>1x</option>
                                            <option value="1.25">1.25x</option>
                                            <option value="1.5">1.5x</option>
                                            <option value="1.75">1.75x</option>
                                            <option value="2">2x</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <label class="form-label me-2 mb-0">
                                            <i class="fas fa-volume-up"></i> Âm lượng:
                                        </label>
                                        <input type="range" class="form-range" id="volumeControl"
                                               min="0" max="100" value="100" onchange="changeVolume()">
                                        <span class="ms-2 text-muted" id="volumeDisplay">100%</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="autoNextChapter"
                                                   onchange="toggleAutoNext()"
                                                   @if($nextChapter) checked @else disabled @endif>
                                            <label class="form-check-label" for="autoNextChapter">
                                                <i class="fas fa-forward me-1"></i>
                                                <span class="auto-next-label">Tự động next</span>
                                            </label>
                                        </div>
                                        @if(!$nextChapter)
                                        <small class="text-muted ms-2">(Chương cuối)</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Player Info -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row text-center">
                                <div class="col-3">
                                    <small class="text-muted d-block">Chương</small>
                                    <strong>{{ $chapter->chapter_number }}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Trạng thái</small>
                                    <span class="badge badge-success" id="playerStatus">Sẵn sàng</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Tốc độ hiện tại</small>
                                    <span id="currentSpeedDisplay">1x</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Auto-next</small>
                                    <span id="autoNextStatus" class="badge badge-secondary">
                                        @if($nextChapter) Bật @else Không có @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Next Countdown (Hidden by default) -->
                        <div class="mt-3 p-3 bg-warning rounded text-center" id="autoNextCountdown" style="display: none;">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-clock me-2"></i>
                                <span>Tự động chuyển sang chương tiếp theo sau: </span>
                                <strong class="ms-1" id="countdownTimer">5</strong>
                                <span class="ms-1">giây</span>
                                <button class="btn btn-sm btn-outline-dark ms-3" onclick="cancelAutoNext()">
                                    <i class="fas fa-times me-1"></i>Hủy
                                </button>
                                <button class="btn btn-sm btn-primary ms-2" onclick="goToNextChapter()">
                                    <i class="fas fa-forward me-1"></i>Chuyển ngay
                                </button>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-primary" id="countdownProgress" style="width: 100%"></div>
                            </div>
                        </div>

                        <!-- Tips -->
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-lightbulb me-1"></i>
                                <strong>Mẹo:</strong> Sử dụng phím Space để play/pause, ← → để tua
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            
            <!-- Chapter Content -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Nội dung chương
                    </h5>
                </div>
                <div class="card-body">
                    @if($chapter->content)
                        <div class="chapter-content">
                            {!! nl2br(e($chapter->content)) !!}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nội dung chương chưa có sẵn</p>
                            @if($chapter->audio_file_path)
                                <p class="text-muted">Vui lòng nghe audio ở trên</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Navigation Bottom -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            @if($prevChapter)
                                <a href="{{ route('chapter.show.vietnamese', [$story->slug, $prevChapter->chapter_number]) }}"
                                   class="btn btn-primary">
                                    <i class="fas fa-chevron-left me-1"></i>Chương {{ $prevChapter->chapter_number }}
                                </a>
                            @endif
                        </div>
                        
                        <div class="text-center">
                            <a href="{{ route('story.show', $story->slug) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-1"></i>Danh sách chương
                            </a>
                        </div>
                        
                        <div>
                            @if($nextChapter)
                                <a href="{{ route('chapter.show.vietnamese', [$story->slug, $nextChapter->chapter_number]) }}"
                                   class="btn btn-primary">
                                    Chương {{ $nextChapter->chapter_number }} <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Story Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-book me-2"></i>Thông tin truyện</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                            <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                 class="img-fluid rounded shadow" alt="{{ $story->title }}"
                                 style="max-height: 200px;">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center shadow" 
                                 style="height: 200px;">
                                <i class="fas fa-book fa-3x text-muted"></i>
                            </div>
                        @endif
                    </div>
                    
                    <h6 class="fw-bold text-center mb-3">
                        <a href="{{ route('story.show', $story->slug) }}" class="text-decoration-none">
                            {{ $story->title }}
                        </a>
                    </h6>
                    
                    @if($story->author)
                        <p class="mb-2">
                            <strong>Tác giả:</strong> {{ $story->author }}
                        </p>
                    @endif
                    
                    <p class="mb-2">
                        <strong>Tổng chương:</strong> {{ $story->chapters->count() }}
                    </p>
                    
                    <p class="mb-3">
                        <strong>Có audio:</strong> {{ $story->chapters->whereNotNull('audio_file_path')->count() }} chương
                    </p>
                    
                    <div class="mb-3">
                        @foreach($story->genres as $genre)
                            <a href="{{ route('genre.show', $genre->slug) }}" class="badge-genre me-1">
                                {{ $genre->name }}
                            </a>
                        @endforeach
                    </div>
                    
                    <div class="d-grid">
                        <a href="{{ route('story.show', $story->slug) }}" class="btn btn-primary">
                            <i class="fas fa-book-open me-2"></i>Xem chi tiết truyện
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Chapter List -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách chương</h6>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    @foreach($story->chapters->sortBy('chapter_number') as $chap)
                        <a href="{{ route('chapter.show.vietnamese', [$story->slug, $chap->chapter_number]) }}"
                           class="list-group-item list-group-item-action border-0 d-flex justify-content-between align-items-center {{ $chap->id === $chapter->id ? 'active' : '' }}">
                            <span>
                                Chương {{ $chap->chapter_number }}
                                @if($chap->title)
                                    <small class="d-block text-muted">{{ Str::limit($chap->title, 30) }}</small>
                                @endif
                            </span>
                            @if($chap->audio_file_path)
                                <i class="fas fa-volume-up text-success"></i>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .chapter-content {
        font-size: 1.1rem;
        line-height: 1.8;
        text-align: justify;
        color: #2d3748;
    }
    
    .chapter-content p {
        margin-bottom: 1.5rem;
        text-indent: 2rem;
    }
    
    .audio-player-container audio {
        border-radius: 8px;
        background: #f8f9fa;
    }
    
    .list-group-item.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .list-group-item:hover:not(.active) {
        background-color: #f8f9fa;
    }
    
    @media (max-width: 768px) {
        .chapter-content {
            font-size: 1rem;
        }
    }

    /* Enhanced Audio Player Styles */
    .enhanced-audio-player {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 20px;
    }

    .progress-container .progress {
        border-radius: 10px;
        background-color: #e9ecef;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .progress-container .progress-bar {
        border-radius: 10px;
        background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
        transition: width 0.3s ease;
    }

    .player-controls .btn {
        border-radius: 50px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .player-controls .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .player-controls .btn-lg {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }

    .form-range {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        outline: none;
    }

    .form-range::-webkit-slider-thumb {
        width: 18px;
        height: 18px;
        background: #007bff;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: pointer;
    }

    .form-range::-moz-range-thumb {
        width: 18px;
        height: 18px;
        background: #007bff;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: pointer;
    }

    .form-select-sm {
        border-radius: 8px;
        border: 1px solid #ced4da;
        font-size: 0.9rem;
    }

    .badge {
        font-size: 0.8rem;
        padding: 0.4em 0.8em;
        border-radius: 12px;
    }

    .badge-success {
        background-color: #28a745;
    }

    .badge-light {
        background-color: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    /* Player info section */
    .enhanced-audio-player .bg-light {
        background: rgba(255,255,255,0.8) !important;
        border: 1px solid rgba(0,0,0,0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .player-controls .d-flex {
            flex-wrap: wrap;
            gap: 10px !important;
        }

        .player-controls .btn-lg {
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }

        .enhanced-audio-player {
            padding: 15px;
        }
    }

    /* Animation for status changes */
    #playerStatus {
        transition: all 0.3s ease;
    }

    /* Keyboard shortcut hints */
    .enhanced-audio-player small {
        opacity: 0.8;
        transition: opacity 0.3s ease;
    }

    .enhanced-audio-player:hover small {
        opacity: 1;
    }

    /* Auto-Next Checkbox Styling */
    .form-check-input {
        border-radius: 4px;
        border: 2px solid #ced4da;
        transition: all 0.3s ease;
    }

    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-check-input:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-check-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .auto-next-label {
        font-weight: 500;
        user-select: none;
    }

    /* Auto-Next Countdown Styling */
    #autoNextCountdown {
        border: 2px solid #ffc107;
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        animation: slideInDown 0.5s ease-out;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    #autoNextCountdown .progress {
        height: 4px;
        background-color: rgba(0,0,0,0.1);
        border-radius: 2px;
        overflow: hidden;
    }

    #autoNextCountdown .progress-bar {
        background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
        transition: width 1s linear;
    }

    #countdownTimer {
        font-size: 1.2rem;
        color: #007bff;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        animation: pulse 1s infinite;
    }

    /* Animations */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Notification Styling */
    .alert {
        border-radius: 8px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: fadeInUp 0.3s ease-out;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        color: #0c5460;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
    }

    /* Status Badge Animations */
    #autoNextStatus {
        transition: all 0.3s ease;
    }

    .badge-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        animation: glow 2s infinite alternate;
    }

    .badge-warning {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    }

    .badge-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }

    @keyframes glow {
        from {
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }
        to {
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.8);
        }
    }

    /* Responsive adjustments for auto-next */
    @media (max-width: 768px) {
        #autoNextCountdown {
            margin: 0 -15px;
            border-radius: 0;
        }

        .auto-next-label {
            font-size: 0.9rem;
        }

        #countdownTimer {
            font-size: 1.1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Enhanced Audio Player JavaScript
    let audio = null;
    let isPlaying = false;
    let currentTime = 0;
    let duration = 0;
    let autoNextEnabled = @if($nextChapter) true @else false @endif;
    let countdownTimer = null;
    let countdownSeconds = 5;
    let nextChapterUrl = '@if($nextChapter){{ route('chapter.show.vietnamese', [$story->slug, $nextChapter->chapter_number]) }}@endif';

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize audio player if exists
        audio = document.getElementById('chapterAudio');
        if (audio) {
            initializeAudioPlayer();
        }

        // Auto-scroll to current chapter in sidebar
        const activeChapter = document.querySelector('.list-group-item.active');
        if (activeChapter) {
            activeChapter.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    function initializeAudioPlayer() {
        // Audio event listeners
        audio.addEventListener('loadedmetadata', function() {
            duration = audio.duration;
            updateTotalTime();
            updatePlayerStatus('Sẵn sàng');
        });

        audio.addEventListener('timeupdate', function() {
            currentTime = audio.currentTime;
            updateProgress();
            updateCurrentTime();
        });

        audio.addEventListener('play', function() {
            isPlaying = true;
            updatePlayPauseButton();
            updatePlayerStatus('Đang phát');
        });

        audio.addEventListener('pause', function() {
            isPlaying = false;
            updatePlayPauseButton();
            updatePlayerStatus('Tạm dừng');
        });

        audio.addEventListener('ended', function() {
            isPlaying = false;
            updatePlayPauseButton();
            updatePlayerStatus('Hoàn thành');

            // Handle auto-next chapter
            handleAudioEnded();
        });

        audio.addEventListener('error', function() {
            updatePlayerStatus('Lỗi');
            console.error('Audio loading error');
        });

        // Progress bar click
        document.getElementById('progressBar').addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * duration;
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea') {
                return; // Don't interfere with form inputs
            }

            switch(e.code) {
                case 'Space':
                    e.preventDefault();
                    togglePlayPause();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    rewind();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    forward();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    changeVolumeBy(10);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    changeVolumeBy(-10);
                    break;
            }
        });

        // Load saved settings
        loadPlayerSettings();

        // Initialize auto-next status
        updateAutoNextStatus();
    }

    function togglePlayPause() {
        if (!audio) return;

        if (isPlaying) {
            audio.pause();
        } else {
            audio.play().catch(e => {
                console.error('Play failed:', e);
                updatePlayerStatus('Lỗi phát');
            });
        }
    }

    function rewind() {
        if (!audio) return;
        audio.currentTime = Math.max(0, audio.currentTime - 10);
    }

    function forward() {
        if (!audio) return;
        audio.currentTime = Math.min(duration, audio.currentTime + 10);
    }

    function changeSpeed() {
        if (!audio) return;
        const speed = document.getElementById('speedControl').value;
        audio.playbackRate = parseFloat(speed);
        document.getElementById('currentSpeedDisplay').textContent = speed + 'x';

        // Save setting
        localStorage.setItem('audioPlayerSpeed', speed);
    }

    function changeVolume() {
        if (!audio) return;
        const volume = document.getElementById('volumeControl').value;
        audio.volume = volume / 100;
        document.getElementById('volumeDisplay').textContent = volume + '%';

        // Save setting
        localStorage.setItem('audioPlayerVolume', volume);
    }

    function changeVolumeBy(delta) {
        const volumeControl = document.getElementById('volumeControl');
        const newVolume = Math.max(0, Math.min(100, parseInt(volumeControl.value) + delta));
        volumeControl.value = newVolume;
        changeVolume();
    }

    function updatePlayPauseButton() {
        const icon = document.getElementById('playPauseIcon');
        const btn = document.getElementById('playPauseBtn');

        if (isPlaying) {
            icon.className = 'fas fa-pause';
            btn.title = 'Tạm dừng (Space)';
        } else {
            icon.className = 'fas fa-play';
            btn.title = 'Phát (Space)';
        }
    }

    function updateProgress() {
        if (!audio || !duration) return;

        const percent = (currentTime / duration) * 100;
        document.querySelector('#progressBar .progress-bar').style.width = percent + '%';
    }

    function updateCurrentTime() {
        document.querySelector('.current-time').textContent = formatTime(currentTime);
    }

    function updateTotalTime() {
        document.querySelector('.total-time').textContent = formatTime(duration);
    }

    function updatePlayerStatus(status) {
        document.getElementById('playerStatus').textContent = status;
    }

    function formatTime(seconds) {
        if (isNaN(seconds)) return '00:00';

        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    function loadPlayerSettings() {
        // Load saved speed
        const savedSpeed = localStorage.getItem('audioPlayerSpeed');
        if (savedSpeed) {
            document.getElementById('speedControl').value = savedSpeed;
            changeSpeed();
        }

        // Load saved volume
        const savedVolume = localStorage.getItem('audioPlayerVolume');
        if (savedVolume) {
            document.getElementById('volumeControl').value = savedVolume;
            changeVolume();
        }

        // Load saved auto-next preference
        const savedAutoNext = localStorage.getItem('audioPlayerAutoNext');
        if (savedAutoNext !== null && nextChapterUrl) {
            autoNextEnabled = savedAutoNext === 'true';
            document.getElementById('autoNextChapter').checked = autoNextEnabled;
            updateAutoNextStatus();
        }
    }

    // Auto-Next Functions
    function toggleAutoNext() {
        autoNextEnabled = document.getElementById('autoNextChapter').checked;
        localStorage.setItem('audioPlayerAutoNext', autoNextEnabled);
        updateAutoNextStatus();

        // Show feedback
        if (autoNextEnabled) {
            showNotification('Đã bật tự động chuyển chương', 'success');
        } else {
            showNotification('Đã tắt tự động chuyển chương', 'info');
        }
    }

    function updateAutoNextStatus() {
        const statusElement = document.getElementById('autoNextStatus');
        if (!nextChapterUrl) {
            statusElement.textContent = 'Không có';
            statusElement.className = 'badge badge-secondary';
        } else if (autoNextEnabled) {
            statusElement.textContent = 'Bật';
            statusElement.className = 'badge badge-success';
        } else {
            statusElement.textContent = 'Tắt';
            statusElement.className = 'badge badge-warning';
        }
    }

    function handleAudioEnded() {
        if (!nextChapterUrl) {
            // No next chapter available
            showNotification('Đã hoàn thành chương cuối cùng!', 'info');
            return;
        }

        if (autoNextEnabled) {
            // Start countdown for auto-next
            startAutoNextCountdown();
        } else {
            // Show manual next option
            showManualNextOption();
        }
    }

    function startAutoNextCountdown() {
        countdownSeconds = 5;
        document.getElementById('autoNextCountdown').style.display = 'block';
        document.getElementById('countdownTimer').textContent = countdownSeconds;
        document.getElementById('countdownProgress').style.width = '100%';

        countdownTimer = setInterval(() => {
            countdownSeconds--;
            document.getElementById('countdownTimer').textContent = countdownSeconds;

            // Update progress bar
            const progress = (countdownSeconds / 5) * 100;
            document.getElementById('countdownProgress').style.width = progress + '%';

            if (countdownSeconds <= 0) {
                clearInterval(countdownTimer);
                goToNextChapter();
            }
        }, 1000);

        // Auto-scroll to countdown
        document.getElementById('autoNextCountdown').scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function cancelAutoNext() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        document.getElementById('autoNextCountdown').style.display = 'none';
        showNotification('Đã hủy tự động chuyển chương', 'info');
    }

    function goToNextChapter() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }

        // Show loading state
        showNotification('Đang chuyển sang chương tiếp theo...', 'info');

        // Navigate to next chapter
        window.location.href = nextChapterUrl;
    }

    function showManualNextOption() {
        // Show a subtle notification with next chapter option
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 350px;';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <span>Chương đã kết thúc!</span>
                <button class="btn btn-sm btn-primary ms-auto me-2" onclick="goToNextChapter()">
                    <i class="fas fa-forward me-1"></i>Chương tiếp
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 10 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 10000);
    }

    function showNotification(message, type = 'info') {
        const alertClass = `alert-${type}`;
        const iconClass = type === 'success' ? 'fa-check-circle' :
                         type === 'error' ? 'fa-exclamation-circle' :
                         type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas ${iconClass} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
</script>
@endpush
@endsection
