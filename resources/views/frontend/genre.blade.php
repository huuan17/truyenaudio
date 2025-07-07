@extends('layouts.frontend')

@section('title', 'Thể loại ' . $genre->name . ' - Audio Lara')
@section('description', 'Danh sách truyện audio thể loại ' . $genre->name . ' hay nhất tại Audio Lara')

@section('content')
<div class="container py-4">
    
    <!-- Genre Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item active">{{ $genre->name }}</li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">
                                <i class="fas fa-tag me-2 text-primary"></i>Thể loại: {{ $genre->name }}
                            </h1>
                            <p class="text-muted mb-0">
                                Tổng cộng {{ $stories->total() }} truyện audio thể loại {{ $genre->name }}
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                                <!-- Filter & Sort -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-filter me-1"></i>Lọc & Sắp xếp
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Trạng thái</h6></li>
                                        <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}">Tất cả</a></li>
                                        <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}?status=ongoing">Đang cập nhật</a></li>
                                        <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}?status=completed">Hoàn thành</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">Sắp xếp</h6></li>
                                        <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}?sort=updated_at&order=desc">Mới cập nhật</a></li>
                                        <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}?sort=created_at&order=desc">Mới đăng</a></li>
                                        <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}?sort=title&order=asc">Tên A-Z</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stories List -->
    <div class="row">
        @forelse($stories as $story)
            <div class="col-lg-6 col-12 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                                    <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                         class="rounded shadow-sm" style="width: 80px; height: 100px; object-fit: cover;" 
                                         alt="{{ $story->title }}">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center shadow-sm" 
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
                                    <p class="mb-2 text-muted small">
                                        {{ Str::limit(strip_tags($story->description), 80) }}
                                    </p>
                                @endif
                                
                                <!-- Story Meta -->
                                <div class="mb-2">
                                    <span class="badge bg-light text-dark me-1">
                                        <i class="fas fa-list me-1"></i>{{ $story->chapters->count() }} chương
                                    </span>
                                    
                                    @if($story->chapters->whereNotNull('audio_file_path')->count() > 0)
                                        <span class="badge bg-success me-1">
                                            <i class="fas fa-volume-up me-1"></i>{{ $story->chapters->whereNotNull('audio_file_path')->count() }} audio
                                        </span>
                                    @endif
                                    
                                    @if($story->status === 'completed')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-check me-1"></i>Full
                                        </span>
                                    @elseif($story->status === 'ongoing')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Đang cập nhật
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Latest Chapter -->
                                @if($story->chapters->isNotEmpty())
                                    <p class="mb-2 text-primary small">
                                        <i class="fas fa-headphones me-1"></i>
                                        <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                           class="text-decoration-none">
                                            Chương {{ $story->chapters->first()->chapter_number }}
                                            @if($story->chapters->first()->title)
                                                : {{ Str::limit($story->chapters->first()->title, 25) }}
                                            @endif
                                        </a>
                                    </p>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @if($story->chapters->isNotEmpty())
                                            <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-play me-1"></i>Nghe ngay
                                            </a>
                                        @endif
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
                    <i class="fas fa-book fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">Chưa có truyện nào</h4>
                    <p class="text-muted">Thể loại {{ $genre->name }} chưa có truyện nào.</p>
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
            {{ $stories->appends(request()->query())->links() }}
        </div>
    @endif

    <!-- Related Genres -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Thể loại khác</h5>
                </div>
                <div class="card-body">
                    @php
                        $otherGenres = \App\Models\Genre::withCount('stories')
                            ->where('id', '!=', $genre->id)
                            ->having('stories_count', '>', 0)
                            ->orderBy('stories_count', 'desc')
                            ->limit(15)
                            ->get();
                    @endphp
                    
                    <div class="genre-grid">
                        @foreach($otherGenres as $otherGenre)
                            <a href="{{ route('genre.show', $otherGenre->slug) }}" 
                               class="btn btn-outline-secondary btn-sm mb-2">
                                {{ $otherGenre->name }} 
                                <span class="badge bg-primary ms-1">{{ $otherGenre->stories_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .genre-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .genre-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }
    }
</style>
@endpush
@endsection
