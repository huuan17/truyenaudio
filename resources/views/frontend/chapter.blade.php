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
                            <a href="{{ route('chapter.show', [$story->slug, $prevChapter->chapter_number]) }}" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-chevron-left me-1"></i>Chương trước
                            </a>
                        @endif
                        
                        <a href="{{ route('story.show', $story->slug) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>Danh sách chương
                        </a>
                        
                        @if($nextChapter)
                            <a href="{{ route('chapter.show', [$story->slug, $nextChapter->chapter_number]) }}" 
                               class="btn btn-outline-primary">
                                Chương tiếp <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Audio Player -->
            @if($chapter->audio_file_path && file_exists(base_path(config('constants.STORAGE_PATHS.AUDIO') . $chapter->audio_file_path)))
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-headphones me-2"></i>Nghe Audio
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="audio-player-container">
                            <audio controls class="w-100" preload="metadata">
                                <source src="{{ route('audio.serve', $chapter->audio_file_path) }}" type="audio/mpeg">
                                Trình duyệt của bạn không hỗ trợ audio HTML5.
                            </audio>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Sử dụng tai nghe để có trải nghiệm tốt nhất
                            </small>
                        </div>
                    </div>
                </div>
            @endif
            
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
                                <a href="{{ route('chapter.show', [$story->slug, $prevChapter->chapter_number]) }}" 
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
                                <a href="{{ route('chapter.show', [$story->slug, $nextChapter->chapter_number]) }}" 
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
                        <a href="{{ route('chapter.show', [$story->slug, $chap->chapter_number]) }}" 
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
</style>
@endpush

@push('scripts')
<script>
    // Auto-scroll to current chapter in sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const activeChapter = document.querySelector('.list-group-item.active');
        if (activeChapter) {
            activeChapter.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
@endpush
@endsection
