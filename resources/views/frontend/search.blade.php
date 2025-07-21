@extends('layouts.frontend')

@section('title', 'Tìm kiếm: ' . ($keyword ?? '') . ' - Audio Lara')
@section('description', 'Kết quả tìm kiếm cho từ khóa: ' . ($keyword ?? ''))

@section('content')
<div class="container py-4">
    
    <!-- Search Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1 class="h4 mb-3">
                        <i class="fas fa-search me-2"></i>Tìm kiếm truyện
                    </h1>
                    
                    <form action="{{ route('search') }}" method="GET">
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" name="q" class="form-control form-control-lg" 
                                       placeholder="Nhập tên truyện, tác giả..." 
                                       value="{{ $keyword }}" autofocus>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    @if($keyword)
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">
                        Kết quả tìm kiếm cho: "{{ $keyword }}"
                    </h2>
                    @if($stories->total() > 0)
                        <span class="badge bg-primary fs-6">{{ $stories->total() }} kết quả</span>
                    @endif
                </div>
                
                @if($stories->count() > 0)
                    <div class="row">
                        @foreach($stories as $story)
                            <div class="col-lg-6 col-12 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                                                    <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                                         class="rounded" style="width: 80px; height: 100px; object-fit: cover;" 
                                                         alt="{{ $story->title }}">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 80px; height: 100px;">
                                                        <i class="fas fa-book fa-2x text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col">
                                                <h5 class="mb-2">
                                                    <a href="{{ route('story.show', $story->slug) }}" class="story-title">
                                                        {{ $story->title }}
                                                    </a>
                                                </h5>
                                                
                                                @if($story->author)
                                                    <p class="mb-2 text-muted">
                                                        <i class="fas fa-user me-1"></i>{{ $story->author }}
                                                    </p>
                                                @endif
                                                
                                                @if($story->description)
                                                    <p class="mb-2 text-muted">
                                                        {{ Str::limit(strip_tags($story->description), 100) }}
                                                    </p>
                                                @endif
                                                
                                                <div class="mb-2">
                                                    @foreach($story->genres->take(3) as $genre)
                                                        <a href="{{ route('genre.show', $genre->slug) }}" class="badge-genre me-1">
                                                            {{ $genre->name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-list me-1"></i>{{ $story->chapters->count() }} chương
                                                        </small>
                                                        @if($story->chapters->whereNotNull('audio_file_path')->count() > 0)
                                                            <small class="text-success ms-2">
                                                                <i class="fas fa-volume-up me-1"></i>{{ $story->chapters->whereNotNull('audio_file_path')->count() }} audio
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">{{ $story->updated_at->diffForHumans() }}</small>
                                                </div>
                                                
                                                @if($story->chapters->isNotEmpty())
                                                    <div class="mt-2">
                                                        <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-play me-1"></i>Bắt đầu nghe
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    @if($stories->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $stories->appends(request()->query())->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">Không tìm thấy kết quả</h4>
                        <p class="text-muted mb-4">
                            Không tìm thấy truyện nào với từ khóa "<strong>{{ $keyword }}</strong>"
                        </p>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="mb-3">Gợi ý tìm kiếm:</h6>
                                        <ul class="list-unstyled text-start">
                                            <li><i class="fas fa-check text-success me-2"></i>Kiểm tra chính tả từ khóa</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Thử tìm kiếm với từ khóa ngắn hơn</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Tìm kiếm theo tên tác giả</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Duyệt theo thể loại</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Popular Searches -->
        <div class="row">
            <div class="col-12">
                <h2 class="section-title">🔥 Từ khóa phổ biến</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Thể loại hot</h6>
                            </div>
                            <div class="card-body">
                                @php
                                    $popularGenres = \App\Models\Genre::public()->withCount('stories')
                                        ->having('stories_count', '>', 0)
                                        ->orderBy('stories_count', 'desc')
                                        ->limit(8)
                                        ->get();
                                @endphp
                                
                                @foreach($popularGenres as $genre)
                                    <a href="{{ route('genre.show', $genre->slug) }}" 
                                       class="btn btn-outline-primary btn-sm me-2 mb-2">
                                        {{ $genre->name }} ({{ $genre->stories_count }})
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-fire me-2"></i>Truyện hot</h6>
                            </div>
                            <div class="card-body">
                                @php
                                    $hotStories = \App\Models\Story::withCount('chapters')
                                        ->where('chapters_count', '>', 0)
                                        ->orderBy('updated_at', 'desc')
                                        ->limit(5)
                                        ->get();
                                @endphp
                                
                                @foreach($hotStories as $story)
                                    <div class="mb-2">
                                        <a href="{{ route('story.show', $story->slug) }}" 
                                           class="text-decoration-none d-block">
                                            <strong>{{ Str::limit($story->title, 40) }}</strong>
                                            @if($story->author)
                                                <small class="text-muted">- {{ $story->author }}</small>
                                            @endif
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
