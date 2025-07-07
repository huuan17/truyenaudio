@extends('layouts.frontend')

@section('title', $story->title . ' - Audio Lara')
@section('description', Str::limit(strip_tags($story->description), 160))

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
                                    <i class="fas fa-book fa-4x text-muted"></i>
                                </div>
                            @endif
                        </div>
                        
                        <div class="col-md-9">
                            <h1 class="h3 fw-bold mb-3">{{ $story->title }}</h1>
                            
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    @if($story->author)
                                        <p class="mb-2">
                                            <strong><i class="fas fa-user me-2"></i>Tác giả:</strong> 
                                            {{ $story->author }}
                                        </p>
                                    @endif
                                    
                                    <p class="mb-2">
                                        <strong><i class="fas fa-list me-2"></i>Số chương:</strong> 
                                        {{ $story->chapters->count() }} chương
                                    </p>
                                    
                                    <p class="mb-2">
                                        <strong><i class="fas fa-volume-up me-2"></i>Audio:</strong> 
                                        {{ $story->chapters->whereNotNull('audio_file_path')->count() }} chương
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
                                    
                                    @if($story->source_url)
                                        <p class="mb-2">
                                            <strong><i class="fas fa-link me-2"></i>Nguồn:</strong> 
                                            <a href="{{ $story->source_url }}" target="_blank" class="text-decoration-none">
                                                Xem gốc <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        </p>
                                    @endif
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
                                @if($story->chapters->isNotEmpty())
                                    <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                       class="btn btn-primary">
                                        <i class="fas fa-play me-2"></i>Bắt đầu nghe
                                    </a>
                                    
                                    @if($story->chapters->count() > 1)
                                        <a href="{{ route('chapter.show', [$story->slug, $story->chapters->last()->chapter_number]) }}" 
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
            
            <!-- Chapter List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách chương</h5>
                    <span class="badge bg-primary">{{ $story->chapters->count() }} chương</span>
                </div>
                <div class="card-body p-0">
                    @if($story->chapters->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach($story->chapters->sortBy('chapter_number') as $chapter)
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
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
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
                                            <i class="fas fa-book text-muted"></i>
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
                        $popularGenres = \App\Models\Genre::withCount('stories')
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
</style>
@endpush
@endsection
