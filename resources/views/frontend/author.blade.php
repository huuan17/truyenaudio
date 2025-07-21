@extends('layouts.frontend')

@section('title', $author->seo_title)
@section('meta_description', $author->seo_description)
@section('meta_keywords', $author->seo_keywords)

@section('content')
<div class="container py-4">
    <!-- Author Header -->
    <div class="row mb-4">
        <div class="col-md-3 text-center">
            <img src="{{ $author->avatar_url }}" 
                 alt="{{ $author->name }}" 
                 class="img-thumbnail mb-3" 
                 style="max-width: 200px;">
        </div>
        <div class="col-md-9">
            <h1 class="h2 mb-3">{{ $author->name }}</h1>
            
            <div class="row mb-3">
                @if($author->nationality)
                    <div class="col-md-6">
                        <p class="text-muted mb-1">
                            <i class="fas fa-flag mr-2"></i>
                            <strong>Quốc tịch:</strong> {{ $author->nationality }}
                        </p>
                    </div>
                @endif
                
                @if($author->birth_date)
                    <div class="col-md-6">
                        <p class="text-muted mb-1">
                            <i class="fas fa-birthday-cake mr-2"></i>
                            <strong>Ngày sinh:</strong> {{ $author->formatted_birth_date }}
                            @if($author->age)
                                ({{ $author->age }} tuổi)
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Statistics -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-1">{{ $stats['total_stories'] }}</h4>
                            <small>Truyện</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-1">{{ $stats['total_chapters'] }}</h4>
                            <small>Chương</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-1">
                                @if($stats['latest_story'])
                                    {{ $stats['latest_story']->created_at->format('Y') }}
                                @else
                                    -
                                @endif
                            </h4>
                            <small>Tác phẩm mới nhất</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            @if($author->social_links)
                <div class="mb-3">
                    <strong class="mr-3">Liên hệ:</strong>
                    @foreach($author->social_links as $platform => $link)
                        <a href="{{ $link['url'] }}" 
                           class="btn btn-outline-primary btn-sm mr-2" 
                           target="_blank" 
                           title="{{ $link['label'] }}">
                            <i class="{{ $link['icon'] }} mr-1"></i>{{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Biography -->
    @if($author->bio)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">📖 Tiểu sử</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-justify">{{ $author->bio }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Achievements -->
    @if($author->achievements && count($author->achievements) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">🏆 Thành tựu & Giải thưởng</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($author->achievements as $achievement)
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-trophy text-warning mr-2"></i>
                                        <span>{{ $achievement }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Stories -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0">📚 Truyện của {{ $author->name }} ({{ $stats['total_stories'] }})</h3>
                </div>
                <div class="card-body">
                    @if($stories->count() > 0)
                        <div class="row">
                            @foreach($stories as $story)
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        @if($story->cover_image)
                                            <img src="{{ asset('storage/' . $story->cover_image) }}" 
                                                 class="card-img-top" 
                                                 alt="{{ $story->title }}"
                                                 style="height: 200px; object-fit: cover;">
                                        @endif
                                        
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">
                                                <a href="{{ route('story.show', $story->slug) }}" 
                                                   class="text-decoration-none text-dark">
                                                    {{ $story->title }}
                                                </a>
                                            </h5>
                                            
                                            <p class="card-text text-muted flex-grow-1">
                                                {{ Str::limit($story->description, 100) }}
                                            </p>
                                            
                                            <div class="mt-auto">
                                                <!-- Genres -->
                                                @if($story->genres->count() > 0)
                                                    <div class="mb-2">
                                                        @foreach($story->genres->take(2) as $genre)
                                                            <span class="badge badge-secondary badge-sm mr-1">
                                                                {{ $genre->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                
                                                <!-- Stats -->
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-list mr-1"></i>
                                                        {{ $story->chapters_count }} chương
                                                    </small>
                                                    <small class="text-muted">
                                                        {{ $story->created_at->format('d/m/Y') }}
                                                    </small>
                                                </div>
                                                
                                                <!-- Action Button -->
                                                <a href="{{ route('story.show', $story->slug) }}" 
                                                   class="btn btn-primary btn-sm btn-block">
                                                    <i class="fas fa-book-open mr-1"></i>Đọc truyện
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($stories->hasPages())
                            <div class="d-flex justify-content-center">
                                {{ $stories->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có truyện nào</h5>
                            <p class="text-muted">Tác giả này chưa có tác phẩm nào được công bố.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "{{ $author->name }}",
    "url": "{{ route('author.show', $author->slug) }}",
    "image": "{{ $author->avatar_url }}",
    @if($author->bio)
    "description": "{{ $author->bio }}",
    @endif
    @if($author->nationality)
    "nationality": "{{ $author->nationality }}",
    @endif
    @if($author->birth_date)
    "birthDate": "{{ $author->birth_date->format('Y-m-d') }}",
    @endif
    @if($author->website)
    "url": "{{ $author->website }}",
    @endif
    "sameAs": [
        @php $socialUrls = []; @endphp
        @if($author->facebook)
            @php $socialUrls[] = '"' . $author->facebook . '"'; @endphp
        @endif
        @if($author->twitter)
            @php $socialUrls[] = '"' . $author->twitter . '"'; @endphp
        @endif
        @if($author->instagram)
            @php $socialUrls[] = '"' . $author->instagram . '"'; @endphp
        @endif
        {!! implode(',', $socialUrls) !!}
    ],
    "worksFor": {
        "@type": "Organization",
        "name": "Audio Lara"
    },
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ route('author.show', $author->slug) }}"
    }
}
</script>
@endpush
