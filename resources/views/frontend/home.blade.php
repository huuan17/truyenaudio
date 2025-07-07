@extends('layouts.frontend')

@section('title', 'Audio Lara - Nghe truyện audio miễn phí')
@section('description', 'Trang nghe truyện audio online miễn phí với nhiều thể loại phong phú: Tiên hiệp, Kiếm hiệp, Ngôn tình, Đô thị...')

@section('content')
<div class="container py-4">
    
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-5">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-headphones me-3"></i>Audio Lara
                    </h1>
                    <p class="lead mb-4">Trang nghe truyện audio online miễn phí với chất lượng cao</p>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <form action="{{ route('search') }}" method="GET" class="d-flex">
                                <input type="text" name="q" class="form-control form-control-lg me-2" 
                                       placeholder="Tìm kiếm truyện..." value="{{ request('q') }}">
                                <button type="submit" class="btn btn-light btn-lg">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-5">
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-book fa-2x text-primary mb-2"></i>
                    <h4 class="fw-bold">{{ \App\Models\Story::count() }}</h4>
                    <p class="text-muted mb-0">Truyện</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                    <h4 class="fw-bold">{{ \App\Models\Chapter::count() }}</h4>
                    <p class="text-muted mb-0">Chương</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-volume-up fa-2x text-warning mb-2"></i>
                    <h4 class="fw-bold">{{ \App\Models\Chapter::whereNotNull('audio_file_path')->count() }}</h4>
                    <p class="text-muted mb-0">Audio</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-tags fa-2x text-info mb-2"></i>
                    <h4 class="fw-bold">{{ \App\Models\Genre::count() }}</h4>
                    <p class="text-muted mb-0">Thể loại</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Truyện Hot -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">🔥 Truyện Hot</h2>
            <a href="{{ route('stories.hot') }}" class="btn btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row">
            @forelse($hotStories->take(8) as $story)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
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
                            
                            <div class="mb-2">
                                @foreach($story->genres->take(2) as $genre)
                                    <a href="{{ route('genre.show', $genre->slug) }}" class="badge-genre me-1">
                                        {{ $genre->name }}
                                    </a>
                                @endforeach
                            </div>
                            
                            <div class="mt-auto">
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
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có truyện nào</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Truyện mới cập nhật -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">📚 Truyện mới cập nhật</h2>
            <a href="{{ route('stories.recent') }}" class="btn btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row">
            @forelse($recentStories->take(12) as $story)
                <div class="col-lg-6 col-12 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                                        <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                             class="rounded" style="width: 60px; height: 80px; object-fit: cover;" 
                                             alt="{{ $story->title }}">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 80px;">
                                            <i class="fas fa-book text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="col">
                                    <h6 class="mb-1">
                                        <a href="{{ route('story.show', $story->slug) }}" class="story-title">
                                            {{ $story->title }}
                                        </a>
                                    </h6>
                                    
                                    @if($story->chapters->isNotEmpty())
                                        <p class="mb-1 text-primary">
                                            <i class="fas fa-headphones me-1"></i>
                                            <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                               class="text-decoration-none">
                                                Chương {{ $story->chapters->first()->chapter_number }}
                                                @if($story->chapters->first()->title)
                                                    : {{ Str::limit($story->chapters->first()->title, 30) }}
                                                @endif
                                            </a>
                                        </p>
                                    @endif
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            @foreach($story->genres->take(2) as $genre)
                                                <span class="badge bg-light text-dark me-1">{{ $genre->name }}</span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">{{ $story->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có truyện nào</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Thể loại phổ biến -->
    <section class="mb-5">
        <h2 class="section-title">🏷️ Thể loại phổ biến</h2>
        <div class="genre-grid">
            @foreach($popularGenres as $genre)
                <a href="{{ route('genre.show', $genre->slug) }}" 
                   class="btn btn-outline-secondary btn-sm mb-2 text-start">
                    {{ $genre->name }} 
                    <span class="badge bg-primary ms-1">{{ $genre->stories_count }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Truyện hoàn thành -->
    @if($completedStories->isNotEmpty())
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">✅ Truyện hoàn thành</h2>
            <a href="{{ route('stories.completed') }}" class="btn btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row">
            @foreach($completedStories->take(4) as $story)
                <div class="col-lg-3 col-md-6 mb-4">
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
                            
                            <div class="mb-2">
                                @foreach($story->genres->take(2) as $genre)
                                    <a href="{{ route('genre.show', $genre->slug) }}" class="badge-genre me-1">
                                        {{ $genre->name }}
                                    </a>
                                @endforeach
                            </div>
                            
                            <div class="mt-auto">
                                <small class="text-muted">
                                    <i class="fas fa-list me-1"></i>{{ $story->chapters->count() }} chương
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

</div>
@endsection
