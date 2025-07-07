@extends('layouts.frontend')

@section('title', 'Truyện Mới Cập Nhật - Audio Lara')
@section('description', 'Danh sách truyện audio mới cập nhật gần đây nhất tại Audio Lara')

@section('content')
<div class="container py-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Mới cập nhật</li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-2">
                        <i class="fas fa-clock me-2 text-primary"></i>Truyện Mới Cập Nhật
                    </h1>
                    <p class="text-muted mb-0">
                        Danh sách {{ $stories->total() }} truyện audio được cập nhật gần đây nhất
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stories List -->
    <div class="row">
        @forelse($stories as $story)
            <div class="col-lg-6 col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                @if($story->cover_image && file_exists(public_path('images/stories/' . $story->cover_image)))
                                    <img src="{{ asset('images/stories/' . $story->cover_image) }}" 
                                         class="rounded shadow-sm" style="width: 70px; height: 90px; object-fit: cover;" 
                                         alt="{{ $story->title }}">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center shadow-sm" 
                                         style="width: 70px; height: 90px;">
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
                                
                                @if($story->author)
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user me-1"></i>{{ $story->author }}
                                    </p>
                                @endif
                                
                                @if($story->chapters->isNotEmpty())
                                    <p class="mb-1 text-primary">
                                        <i class="fas fa-headphones me-1"></i>
                                        <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                           class="text-decoration-none">
                                            <strong>Chương {{ $story->chapters->first()->chapter_number }}</strong>
                                            @if($story->chapters->first()->title)
                                                : {{ Str::limit($story->chapters->first()->title, 35) }}
                                            @endif
                                        </a>
                                    </p>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @foreach($story->genres->take(2) as $genre)
                                            <span class="badge bg-light text-dark me-1">{{ $genre->name }}</span>
                                        @endforeach
                                        
                                        @if($story->status === 'completed')
                                            <span class="badge bg-success">Full</span>
                                        @elseif($story->status === 'ongoing')
                                            <span class="badge bg-warning">Đang cập nhật</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $story->updated_at->diffForHumans() }}</small>
                                </div>
                                
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-list me-1"></i>{{ $story->chapters->count() }} chương
                                        @if($story->chapters->whereNotNull('audio_file_path')->count() > 0)
                                            • <i class="fas fa-volume-up me-1 text-success"></i>{{ $story->chapters->whereNotNull('audio_file_path')->count() }} audio
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="col-auto">
                                @if($story->chapters->isNotEmpty())
                                    <a href="{{ route('chapter.show', [$story->slug, $story->chapters->first()->chapter_number]) }}" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">Chưa có truyện mới cập nhật</h4>
                    <p class="text-muted">Hãy quay lại sau để xem các truyện mới cập nhật.</p>
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
