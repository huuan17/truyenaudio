@extends('layouts.frontend')

@section('title', 'Truyện Hot - Audio Lara')
@section('description', 'Danh sách truyện audio hot nhất, được cập nhật thường xuyên tại Audio Lara')

@section('content')
<div class="container py-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Truyện Hot</li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-2">
                        <i class="fas fa-fire me-2 text-danger"></i>Truyện Hot
                    </h1>
                    <p class="text-muted mb-0">
                        Danh sách {{ $stories->total() }} truyện audio hot nhất, được cập nhật thường xuyên
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
                        
                        @if($story->chapters->count() > 0)
                            <span class="badge bg-primary position-absolute top-0 end-0 m-2">
                                {{ $story->chapters->count() }} chương
                            </span>
                        @endif
                        
                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                            <i class="fas fa-fire me-1"></i>Hot
                        </span>
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
                                <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                   class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-play me-1"></i>Bắt đầu nghe
                                </a>
                            @endif
                            
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                {{ $story->updated_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-fire fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">Chưa có truyện hot nào</h4>
                    <p class="text-muted">Hãy quay lại sau để xem các truyện hot mới nhất.</p>
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
