@extends('layouts.frontend')

@section('title', $story->title . ' - Audio Lara')
@section('description', Str::limit(strip_tags($story->description), 160))

@php
    use App\Models\Chapter;
@endphp

@section('content')
<div class="container py-4">
    
    <!-- Story Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    @if($story->genres->isNotEmpty())
                        <li class="breadcrumb-item">
                            <a href="{{ route('genre.show', $story->genres->first()->slug) }}">
                                {{ $story->genres->first()->name }}
                            </a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active">{{ $story->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <!-- Story Info -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                                <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                     class="img-fluid rounded shadow" alt="{{ $story->title }}"
                                     style="max-height: 300px;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center shadow"
                                     style="height: 300px;">
                                    <i class="far fa-file-alt fa-4x text-muted" style="opacity: 0.5;"></i>
                                </div>
                            @endif
                        </div>
                        
                        <div class="col-md-9">
                            <h1 class="h3 fw-bold mb-3">{{ $story->title }}</h1>
                            
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    @if($story->author_id && $story->authorModel)
                                        <p class="mb-2">
                                            <strong><i class="fas fa-user me-2"></i>Tác giả:</strong>
                                            <a href="{{ route('author.show', $story->authorModel->slug) }}"
                                               class="text-decoration-none text-primary fw-bold">
                                                {{ $story->authorModel->name }}
                                            </a>
                                        </p>
                                    @elseif($story->author)
                                        <p class="mb-2">
                                            <strong><i class="fas fa-user me-2"></i>Tác giả:</strong>
                                            {{ $story->author }}
                                        </p>
                                    @endif
                                    
                                    <p class="mb-2">
                                        <strong><i class="fas fa-list me-2"></i>Số chương:</strong>
                                        {{ $chapters->total() }} chương
                                    </p>

                                    <p class="mb-2">
                                        <strong><i class="fas fa-volume-up me-2"></i>Audio:</strong>
                                        {{ Chapter::where('story_id', $story->id)->whereNotNull('audio_file_path')->count() }} chương
                                    </p>
                                </div>
                                
                                <div class="col-sm-6">
                                    <p class="mb-2">
                                        <strong><i class="fas fa-info-circle me-2"></i>Trạng thái:</strong>
                                        @if($story->status === 'completed')
                                            <span class="badge bg-success">Hoàn thành</span>
                                        @elseif($story->status === 'ongoing')
                                            <span class="badge bg-primary">Đang cập nhật</span>
                                        @else
                                            <span class="badge bg-secondary">Tạm dừng</span>
                                        @endif
                                    </p>
                                    
                                    <p class="mb-2">
                                        <strong><i class="fas fa-clock me-2"></i>Cập nhật:</strong>
                                        {{ $story->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Genres -->
                            <div class="mb-3">
                                <strong><i class="fas fa-tags me-2"></i>Thể loại:</strong>
                                @foreach($story->genres as $genre)
                                    <a href="{{ route('genre.show', $genre->slug) }}" class="badge-genre me-1">
                                        {{ $genre->name }}
                                    </a>
                                @endforeach
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 flex-wrap">
                                @if($chapters->total() > 0)
                                    @php
                                        $firstChapter = Chapter::where('story_id', $story->id)->orderBy('chapter_number', 'asc')->first();
                                        $lastChapter = Chapter::where('story_id', $story->id)->orderBy('chapter_number', 'desc')->first();
                                    @endphp

                                    @if($firstChapter)
                                        <a href="{{ route('chapter.show', [$story->slug, $firstChapter->chapter_number]) }}"
                                           class="btn btn-primary">
                                            <i class="fas fa-book-open me-2"></i>Đọc từ đầu
                                        </a>
                                    @endif

                                    @if($lastChapter && $chapters->total() > 1)
                                        <a href="{{ route('chapter.show', [$story->slug, $lastChapter->chapter_number]) }}"
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-forward me-2"></i>Chương mới nhất
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            @if($story->description)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-align-left me-2"></i>Giới thiệu</h5>
                    </div>
                    <div class="card-body">
                        <div class="story-description">
                            {!! nl2br(e($story->description)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Audio Player -->
            @php
                // Get audio chapters using new storage structure
                $audioChapters = Chapter::where('story_id', $story->id)
                    ->whereNotNull('audio_file_path')
                    ->where('audio_file_path', '!=', '')
                    ->orderBy('chapter_number', 'asc')
                    ->get()
                    ->filter(function($chapter) {
                        return $chapter->hasAudio();
                    });
            @endphp

            @if($audioChapters->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-headphones me-2"></i>Nghe Audio</h5>
                        <span class="badge bg-success">{{ $audioChapters->count() }} chương có audio</span>
                    </div>
                    <div class="card-body">
                        <!-- Current Chapter Info -->
                        <div class="current-chapter-info mb-3 p-3 bg-light rounded">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1" id="current-chapter-title">
                                        Chương {{ $audioChapters->first()->chapter_number }}
                                        @if($audioChapters->first()->title)
                                            : {{ $audioChapters->first()->title }}
                                        @endif
                                    </h6>
                                    <small class="text-muted" id="current-chapter-progress">Chưa phát</small>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="chapter-navigation">
                                        <button class="btn btn-sm btn-outline-secondary me-1" id="prev-chapter" disabled>
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <span class="small text-muted mx-2" id="chapter-counter">
                                            1 / {{ $audioChapters->count() }}
                                        </span>
                                        <button class="btn btn-sm btn-outline-secondary" id="next-chapter">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Audio Element -->
                        <audio id="story-audio-player" class="w-100 mb-3" controls preload="metadata">
                            @if($audioChapters->first())
                                @php
                                    $firstChapter = $audioChapters->first();
                                    $audioUrl = $firstChapter->audio_web_url;

                                    // Check if actual audio file exists
                                    $audioPath = public_path('storage/' . $firstChapter->audio_file_path);
                                    $fileExists = file_exists($audioPath);

                                    echo "<!-- DEBUG: Audio URL: " . $audioUrl . " -->";
                                    echo "<!-- DEBUG: Audio Path: " . $firstChapter->audio_file_path . " -->";
                                    echo "<!-- DEBUG: File exists: " . ($fileExists ? 'YES' : 'NO') . " -->";
                                    echo "<!-- DEBUG: Full path: " . $audioPath . " -->";
                                @endphp

                                @if(file_exists(public_path('storage/' . $firstChapter->audio_file_path)))
                                    <!-- Use real audio file -->
                                    <source src="{{ $audioUrl }}" type="audio/mpeg">
                                @else
                                    <!-- Fallback to demo audio if real file doesn't exist -->
                                    <source src="https://www.learningcontainer.com/wp-content/uploads/2020/02/Kalimba.mp3" type="audio/mpeg">
                                    <p class="text-warning small">Đang sử dụng audio demo vì file gốc không tồn tại</p>
                                @endif
                            @endif
                            Trình duyệt của bạn không hỗ trợ audio player.
                        </audio>

                        <!-- Controls Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tốc độ phát:</label>
                                <select class="form-select form-select-sm" id="playback-speed">
                                    <option value="0.5">0.5x</option>
                                    <option value="0.75">0.75x</option>
                                    <option value="1" selected>1x (Bình thường)</option>
                                    <option value="1.25">1.25x</option>
                                    <option value="1.5">1.5x</option>
                                    <option value="2">2x</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto-next-chapter" checked>
                                    <label class="form-check-label" for="auto-next-chapter">
                                        Tự động chuyển chương
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-sm btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#chapter-playlist">
                                    <i class="fas fa-list me-1"></i>Danh sách phát ({{ $audioChapters->count() }})
                                </button>
                            </div>
                        </div>

                        <!-- Chapter Playlist -->
                        <div class="collapse" id="chapter-playlist">
                            <div class="playlist-container border rounded p-2" style="max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                                @foreach($audioChapters as $index => $chapter)
                                    @php
                                        $hasRealAudio = file_exists(public_path('storage/' . $chapter->audio_file_path));
                                        $primarySrc = $hasRealAudio ? $chapter->audio_web_url : 'https://www.learningcontainer.com/wp-content/uploads/2020/02/Kalimba.mp3';
                                        $fallbackSrc = $hasRealAudio ? 'https://www.learningcontainer.com/wp-content/uploads/2020/02/Kalimba.mp3' : null;
                                    @endphp
                                    <div class="playlist-item p-2 border-bottom cursor-pointer"
                                         data-chapter-index="{{ $index }}"
                                         data-chapter-id="{{ $chapter->id }}"
                                         data-audio-src="{{ $primarySrc }}"
                                         data-fallback-src="{{ $fallbackSrc }}"
                                         data-has-real-audio="{{ $hasRealAudio ? 'true' : 'false' }}"
                                         data-chapter-number="{{ $chapter->chapter_number }}"
                                         data-chapter-title="{{ $chapter->title }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Chương {{ $chapter->chapter_number }}</strong>
                                                @if($chapter->title)
                                                    <span class="text-muted">: {{ Str::limit($chapter->title, 50) }}</span>
                                                @endif
                                                @if(!file_exists(public_path('storage/' . $chapter->audio_file_path)))
                                                    <span class="badge badge-warning badge-sm ms-2">Demo</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                @if(file_exists(public_path('storage/' . $chapter->audio_file_path)))
                                                    <i class="fas fa-play-circle text-success"></i>
                                                @else
                                                    <i class="fas fa-play-circle text-warning"></i>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Chapter List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách chương</h5>
                    <span class="badge bg-primary">{{ $chapters->total() }} chương</span>
                </div>
                <div class="card-body p-0">
                    @if($chapters->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($chapters as $chapter)
                                <a href="{{ route('chapter.show', [$story->slug, $chapter->chapter_number]) }}"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Chương {{ $chapter->chapter_number }}</strong>
                                        @if($chapter->title)
                                            <span class="text-muted">: {{ $chapter->title }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($chapter->audio_file_path)
                                            <span class="badge bg-success">
                                                <i class="fas fa-volume-up"></i> Audio
                                            </span>
                                        @endif
                                        <small class="text-muted">{{ $chapter->updated_at->format('d/m/Y') }}</small>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($chapters->hasPages())
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="text-muted small mb-2 mb-md-0">
                                        Hiển thị {{ $chapters->firstItem() }}-{{ $chapters->lastItem() }}
                                        trong tổng số {{ $chapters->total() }} chương
                                    </div>
                                    <div>
                                        {{ $chapters->appends(request()->query())->links('custom-pagination') }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="far fa-folder-open fa-3x text-muted mb-3" style="opacity: 0.5;"></i>
                            <p class="text-muted">Chưa có chương nào</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Related Stories -->
            @if($relatedStories->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-book-open me-2"></i>Truyện liên quan</h6>
                    </div>
                    <div class="card-body p-0">
                        @foreach($relatedStories as $related)
                            <div class="d-flex p-3 border-bottom">
                                <div class="flex-shrink-0 me-3">
                                    @if($related->cover_image && file_exists(public_path('images/stories/' . $related->cover_image)))
                                        <img src="{{ asset('images/stories/' . $related->cover_image) }}" 
                                             class="rounded" style="width: 50px; height: 70px; object-fit: cover;" 
                                             alt="{{ $related->title }}">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 70px;">
                                            <i class="far fa-file-text text-muted" style="opacity: 0.6;"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="{{ route('story.show', $related->slug) }}" 
                                           class="text-decoration-none story-title">
                                            {{ Str::limit($related->title, 40) }}
                                        </a>
                                    </h6>
                                    @if($related->author)
                                        <small class="text-muted d-block">{{ $related->author }}</small>
                                    @endif
                                    <div class="mt-1">
                                        @foreach($related->genres->take(2) as $genre)
                                            <span class="badge bg-light text-dark me-1" style="font-size: 0.7rem;">
                                                {{ $genre->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Popular Genres -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Thể loại phổ biến</h6>
                </div>
                <div class="card-body">
                    @php
                        $popularGenres = \App\Models\Genre::public()->withCount('stories')
                            ->having('stories_count', '>', 0)
                            ->orderBy('stories_count', 'desc')
                            ->limit(10)
                            ->get();
                    @endphp
                    
                    @foreach($popularGenres as $genre)
                        <a href="{{ route('genre.show', $genre->slug) }}" 
                           class="btn btn-outline-secondary btn-sm me-1 mb-2">
                            {{ $genre->name }} ({{ $genre->stories_count }})
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .story-description {
        line-height: 1.8;
        text-align: justify;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }

    /* Audio Player Styles - Main Content Optimized */
    .current-chapter-info {
        border-left: 4px solid #28a745;
        background: #f8f9fa !important;
    }

    .playlist-item {
        transition: all 0.3s ease;
        cursor: pointer;
        border-radius: 4px;
        margin: 2px 0;
    }

    .playlist-item:hover {
        background-color: #e9ecef;
        transform: translateX(5px);
    }

    .playlist-item.active {
        background-color: #d4edda;
        border-left: 4px solid #28a745;
    }

    .chapter-navigation button {
        transition: all 0.3s ease;
    }

    .chapter-navigation button:hover:not(:disabled) {
        transform: scale(1.1);
    }

    #story-audio-player {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        min-height: 54px;
    }

    #chapter-counter {
        font-weight: 500;
        color: #6c757d;
        white-space: nowrap;
    }

    .playlist-container {
        background: #f8f9fa !important;
    }

    .playlist-item:last-child {
        border-bottom: none !important;
    }

    /* Mobile optimizations for main content audio player */
    @media (max-width: 768px) {
        .current-chapter-info .row {
            text-align: center;
        }

        .current-chapter-info .col-md-4 {
            margin-top: 1rem;
        }

        .chapter-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .row.g-3 {
            text-align: center;
        }

        .row.g-3 .col-md-4 {
            margin-bottom: 1rem;
        }

        .form-check {
            justify-content: center;
            display: flex;
        }

        #story-audio-player {
            min-height: 48px;
        }
    }

    @media (max-width: 576px) {
        .current-chapter-info {
            padding: 1rem !important;
        }

        .current-chapter-info h6 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .chapter-navigation button {
            padding: 0.25rem 0.5rem;
        }

        #chapter-counter {
            font-size: 0.875rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const audioPlayer = document.getElementById('story-audio-player');
    const currentChapterTitle = document.getElementById('current-chapter-title');
    const currentChapterProgress = document.getElementById('current-chapter-progress');
    const chapterCounter = document.getElementById('chapter-counter');
    const prevButton = document.getElementById('prev-chapter');
    const nextButton = document.getElementById('next-chapter');
    const playbackSpeed = document.getElementById('playback-speed');
    const autoNextCheckbox = document.getElementById('auto-next-chapter');
    const playlistItems = document.querySelectorAll('.playlist-item');

    if (!audioPlayer) return; // No audio player on this page

    let currentChapterIndex = 0;
    let chapters = [];

    // Initialize chapters array from playlist
    playlistItems.forEach((item, index) => {
        chapters.push({
            index: index,
            id: item.dataset.chapterId,
            audioSrc: item.dataset.audioSrc,
            fallbackSrc: item.dataset.fallbackSrc,
            number: item.dataset.chapterNumber,
            title: item.dataset.chapterTitle
        });
    });

    // Initialize player
    updateChapterDisplay();
    updateNavigationButtons();

    // Playback speed control
    playbackSpeed.addEventListener('change', function() {
        audioPlayer.playbackRate = parseFloat(this.value);
        localStorage.setItem('audioPlaybackSpeed', this.value);
    });

    // Load saved playback speed
    const savedSpeed = localStorage.getItem('audioPlaybackSpeed');
    if (savedSpeed) {
        playbackSpeed.value = savedSpeed;
        audioPlayer.playbackRate = parseFloat(savedSpeed);
    }

    // Auto-next chapter preference
    autoNextCheckbox.addEventListener('change', function() {
        localStorage.setItem('autoNextChapter', this.checked);
    });

    // Load saved auto-next preference
    const savedAutoNext = localStorage.getItem('autoNextChapter');
    if (savedAutoNext !== null) {
        autoNextCheckbox.checked = savedAutoNext === 'true';
    }

    // Navigation buttons
    prevButton.addEventListener('click', function() {
        if (currentChapterIndex > 0) {
            currentChapterIndex--;
            loadChapter(currentChapterIndex);
        }
    });

    nextButton.addEventListener('click', function() {
        if (currentChapterIndex < chapters.length - 1) {
            currentChapterIndex++;
            loadChapter(currentChapterIndex);
        }
    });

    // Playlist item clicks
    playlistItems.forEach((item, index) => {
        item.addEventListener('click', function() {
            currentChapterIndex = index;
            loadChapter(currentChapterIndex);
        });
    });

    // Debug audio path
    console.log('Audio player initialized');
    console.log('Initial audio source:', audioPlayer.src);

    // Audio events
    audioPlayer.addEventListener('loadstart', function() {
        currentChapterProgress.textContent = 'Đang tải...';
        console.log('Loading audio:', audioPlayer.src);
    });

    audioPlayer.addEventListener('canplay', function() {
        currentChapterProgress.textContent = 'Sẵn sàng phát';
    });

    audioPlayer.addEventListener('play', function() {
        currentChapterProgress.textContent = 'Đang phát';
    });

    audioPlayer.addEventListener('pause', function() {
        currentChapterProgress.textContent = 'Tạm dừng';
    });

    audioPlayer.addEventListener('timeupdate', function() {
        if (audioPlayer.duration) {
            const progress = (audioPlayer.currentTime / audioPlayer.duration * 100).toFixed(1);
            const currentTime = formatTime(audioPlayer.currentTime);
            const duration = formatTime(audioPlayer.duration);
            currentChapterProgress.textContent = `${currentTime} / ${duration} (${progress}%)`;
        }
    });

    audioPlayer.addEventListener('ended', function() {
        currentChapterProgress.textContent = 'Hoàn thành';

        // Auto-next chapter if enabled
        if (autoNextCheckbox.checked && currentChapterIndex < chapters.length - 1) {
            setTimeout(() => {
                currentChapterIndex++;
                loadChapter(currentChapterIndex);
                audioPlayer.play();
            }, 1000); // 1 second delay
        }
    });

    audioPlayer.addEventListener('error', function(e) {
        console.error('Audio error:', audioPlayer.error);
        console.error('Audio source:', audioPlayer.src);
        console.error('Error details:', e);

        // Try to provide helpful error message
        let errorMessage = 'Lỗi phát audio';
        if (audioPlayer.error) {
            switch(audioPlayer.error.code) {
                case audioPlayer.error.MEDIA_ERR_ABORTED:
                    errorMessage = 'Tải audio bị hủy';
                    console.error('Audio loading aborted');
                    break;
                case audioPlayer.error.MEDIA_ERR_NETWORK:
                    errorMessage = 'Lỗi mạng khi tải audio';
                    console.error('Network error while loading audio');
                    break;
                case audioPlayer.error.MEDIA_ERR_DECODE:
                    errorMessage = 'Lỗi giải mã audio';
                    console.error('Audio decoding error');
                    break;
                case audioPlayer.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
                    errorMessage = 'File audio không tồn tại hoặc không được hỗ trợ';
                    console.error('Audio format not supported or file not found');

                    // Try to load fallback audio
                    console.log('Trying fallback audio...');
                    const sources = audioPlayer.querySelectorAll('source');
                    if (sources.length > 1) {
                        // Remove the failed source and try the next one
                        sources[0].remove();
                        audioPlayer.load();
                        return;
                    }
                    break;
            }
        }

        currentChapterProgress.textContent = errorMessage;
    });

    // Functions
    function loadChapter(index) {
        if (index < 0 || index >= chapters.length) return;

        const chapter = chapters[index];
        console.log('Loading chapter:', chapter.number, 'Audio URL:', chapter.audioSrc);

        // Clear existing sources
        audioPlayer.innerHTML = '';

        // Add primary source
        const primarySource = document.createElement('source');
        primarySource.src = chapter.audioSrc;
        primarySource.type = 'audio/mpeg';
        audioPlayer.appendChild(primarySource);

        // Add fallback source if available
        if (chapter.fallbackSrc) {
            const fallbackSource = document.createElement('source');
            fallbackSource.src = chapter.fallbackSrc;
            fallbackSource.type = 'audio/mpeg';
            audioPlayer.appendChild(fallbackSource);
        }

        audioPlayer.load();

        updateChapterDisplay();
        updateNavigationButtons();
        updatePlaylistHighlight();

        // Save current chapter
        localStorage.setItem('currentChapterIndex', index);

        // Test if audio can be loaded
        audioPlayer.addEventListener('loadedmetadata', function() {
            console.log('Audio metadata loaded successfully for chapter', chapter.number);
            currentChapterProgress.textContent = 'Sẵn sàng phát';
        }, { once: true });

        audioPlayer.addEventListener('error', function() {
            console.error('Failed to load audio for chapter', chapter.number);
            currentChapterProgress.textContent = 'Đang thử nguồn audio khác...';
        }, { once: true });
    }

    function updateChapterDisplay() {
        const chapter = chapters[currentChapterIndex];
        if (chapter) {
            let titleText = `Chương ${chapter.number}`;
            if (chapter.title && chapter.title !== 'null') {
                titleText += `<br><small class="text-muted">${chapter.title.substring(0, 30)}${chapter.title.length > 30 ? '...' : ''}</small>`;
            }
            currentChapterTitle.innerHTML = titleText;

            // Update chapter counter
            if (chapterCounter) {
                chapterCounter.textContent = `${currentChapterIndex + 1} / ${chapters.length}`;
            }
        }
    }

    function updateNavigationButtons() {
        prevButton.disabled = currentChapterIndex === 0;
        nextButton.disabled = currentChapterIndex === chapters.length - 1;
    }

    function updatePlaylistHighlight() {
        playlistItems.forEach((item, index) => {
            item.classList.toggle('active', index === currentChapterIndex);
        });
    }

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // Load saved chapter index
    const savedChapterIndex = localStorage.getItem('currentChapterIndex');
    if (savedChapterIndex !== null && savedChapterIndex < chapters.length) {
        currentChapterIndex = parseInt(savedChapterIndex);
        loadChapter(currentChapterIndex);
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        switch(e.code) {
            case 'Space':
                e.preventDefault();
                if (audioPlayer.paused) {
                    audioPlayer.play();
                } else {
                    audioPlayer.pause();
                }
                break;
            case 'ArrowLeft':
                e.preventDefault();
                audioPlayer.currentTime = Math.max(0, audioPlayer.currentTime - 10);
                break;
            case 'ArrowRight':
                e.preventDefault();
                audioPlayer.currentTime = Math.min(audioPlayer.duration, audioPlayer.currentTime + 10);
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (currentChapterIndex > 0) {
                    currentChapterIndex--;
                    loadChapter(currentChapterIndex);
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (currentChapterIndex < chapters.length - 1) {
                    currentChapterIndex++;
                    loadChapter(currentChapterIndex);
                }
                break;
        }
    });
});
</script>
@endpush
@endsection
