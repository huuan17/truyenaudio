@extends('layouts.frontend')

@section('title', 'Danh sách tác giả - Audio Lara')
@section('meta_description', 'Khám phá danh sách các tác giả nổi tiếng với những tác phẩm văn học hay nhất. Đọc và nghe truyện audio của các tác giả yêu thích.')
@section('meta_keywords', 'tác giả, danh sách tác giả, văn học, truyện audio, sách nói')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">👨‍💼 Danh sách tác giả</h1>
                    <p class="text-muted">Khám phá các tác giả và tác phẩm của họ</p>
                </div>
                <div class="text-muted">
                    {{ $authors->total() }} tác giả
                </div>
            </div>
        </div>
    </div>

    @if($authors->count() > 0)
        <!-- Authors Grid -->
        <div class="row">
            @foreach($authors as $author)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <!-- Avatar -->
                            <div class="mb-3">
                                <img src="{{ $author->avatar_url }}" 
                                     alt="{{ $author->name }}" 
                                     class="rounded-circle" 
                                     width="80" height="80"
                                     style="object-fit: cover;">
                            </div>

                            <!-- Name -->
                            <h5 class="card-title mb-2">
                                <a href="{{ route('author.show', $author->slug) }}" 
                                   class="text-decoration-none text-dark">
                                    {{ $author->name }}
                                </a>
                            </h5>

                            <!-- Nationality -->
                            @if($author->nationality)
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-flag mr-1"></i>{{ $author->nationality }}
                                </p>
                            @endif

                            <!-- Stories Count -->
                            <div class="mb-3">
                                <span class="badge badge-primary">
                                    {{ $author->published_stories_count }} truyện
                                </span>
                            </div>

                            <!-- Bio Preview -->
                            @if($author->bio)
                                <p class="card-text text-muted small">
                                    {{ Str::limit($author->bio, 100) }}
                                </p>
                            @endif

                            <!-- View Button -->
                            <a href="{{ route('author.show', $author->slug) }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye mr-1"></i>Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($authors->hasPages())
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        {{ $authors->links() }}
                    </div>
                </div>
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-user-tie fa-4x text-muted mb-4"></i>
                    <h3 class="text-muted">Chưa có tác giả nào</h3>
                    <p class="text-muted">Hệ thống chưa có thông tin về tác giả.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-home mr-1"></i>Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "Danh sách tác giả",
    "description": "Khám phá danh sách các tác giả nổi tiếng với những tác phẩm văn học hay nhất",
    "url": "{{ route('authors.index') }}",
    "mainEntity": {
        "@type": "ItemList",
        "numberOfItems": {{ $authors->total() }},
        "itemListElement": [
            @foreach($authors as $index => $author)
            {
                "@type": "ListItem",
                "position": {{ $index + 1 }},
                "item": {
                    "@type": "Person",
                    "name": "{{ $author->name }}",
                    "url": "{{ route('author.show', $author->slug) }}",
                    @if($author->bio)
                    "description": "{{ Str::limit($author->bio, 200) }}",
                    @endif
                    @if($author->nationality)
                    "nationality": "{{ $author->nationality }}",
                    @endif
                    "image": "{{ $author->avatar_url }}"
                }
            }@if(!$loop->last),@endif
            @endforeach
        ]
    }
}
</script>
@endpush
