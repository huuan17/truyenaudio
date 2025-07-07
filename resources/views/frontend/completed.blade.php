@extends('layouts.frontend')

@section('title', 'Truyện Hoàn Thành - Audio Lara')
@section('description', 'Danh sách truyện audio đã hoàn thành, full chương tại Audio Lara')

@section('content')
<div class="container py-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Truyện Full</li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-2">
                        <i class="fas fa-check-circle me-2 text-success"></i>Truyện Hoàn Thành
                    </h1>
                    <p class="text-muted mb-0">
                        Danh sách {{ $stories->total() }} truyện audio đã hoàn thành, full chương
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stories Grid -->
    <div class="row">
        @forelse($stories as $story)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card story-card h-100">
                    <div class="position-relative">
                        @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                            <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                 class="card-img-top story-cover" alt="{{ $story->title }}">
                        @else
                            <div class="story-cover bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-book fa-3x text-muted"></i>
                            </div>
                        @endif
                        
                        <span class="badge bg-success position-absolute top-0 end-0 m-2">
                            <i class="fas fa-check me-1"></i>Full
                        </span>
                        
                        @if($story->chapters->count() > 0)
                            <span class="badge bg-primary position-absolute bottom-0 end-0 m-2">
                                {{ $story->chapters->count() }} chương
                            </span>
                        @endif
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">
                            <a href="{{ route('story.show', $story->slug) }}" class="story-title">
                                {{ $story->title }}
                            </a>
                        </h6>
                        
                        @if($story->author)
                            <p class="story-meta mb-2">
                                <i class="fas fa-user me-1"></i>{{ $story->author }}
                            </p>
                        @endif
                        
                        @if($story->description)
                            <p class="text-muted small mb-2">
                                {{ Str::limit(strip_tags($story->description), 80) }}
                            </p>
                        @endif
                        
                        <div class="mb-2">
                            @foreach($story->genres->take(2) as $genre)
                                <a href="{{ route('genre.show', $genre->slug) }}" class="badge-genre me-1">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        </div>
                        
                        <div class="mt-auto">
                            @if($story->chapters->isNotEmpty())
                                <div class="d-grid gap-2 mb-2">
                                    <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-play me-1"></i>Bắt đầu nghe
                                    </a>
                                    <a href="{{ route('story.show', $story->slug) }}" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-list me-1"></i>Xem chi tiết
                                    </a>
                                </div>
                            @endif
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-list me-1"></i>{{ $story->chapters->count() }} chương
                                </small>
                                <small class="text-muted">
                                    {{ $story->updated_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">Chưa có truyện hoàn thành nào</h4>
                    <p class="text-muted">Hãy quay lại sau để xem các truyện đã hoàn thành.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Về trang chủ
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($stories->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $stories->links() }}
        </div>
    @endif
</div>
@endsection
