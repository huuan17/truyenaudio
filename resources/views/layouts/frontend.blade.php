<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        use App\Helpers\SettingHelper;
        $seoTags = SettingHelper::getHomeSeoTags();
        $siteInfo = SettingHelper::getSiteInfo();
    @endphp

    <title>@yield('title', $seoTags['title'])</title>
    <meta name="description" content="@yield('meta_description', $seoTags['description'])">
    <meta name="keywords" content="@yield('meta_keywords', $seoTags['keywords'])">

    <!-- Meta Verification Tags -->
    {!! SettingHelper::getMetaVerificationTags() !!}
    
    <!-- Bootstrap CSS (Local) -->
    <link href="{{ asset('assets/css/bootstrap-5.3.0.min.css') }}" rel="stylesheet">
    <!-- Font Awesome (Local - Complete) -->
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome-6.4.0-all.min.css') }}">
    <!-- Icon Fallback CSS (for missing webfonts) -->
    <link rel="stylesheet" href="{{ asset('assets/css/icon-fallback.css') }}">
    <!-- Google Fonts (keep CDN for fonts) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .nav-link {
            color: var(--dark-color) !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .story-card {
            height: 100%;
        }

        .story-cover {
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .badge-genre {
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-decoration: none;
        }

        .badge-genre:hover {
            background: var(--dark-color);
            color: white;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 1rem;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .search-form {
            max-width: 500px;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
        }

        .genre-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.5rem;
        }

        .story-meta {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }

        .story-title {
            font-weight: 600;
            color: var(--dark-color);
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .story-title:hover {
            color: var(--primary-color);
        }

        .audio-player {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 0.5rem;
        }

        /* Icon Optimization - Remove harsh borders and make icons lighter */
        .fas, .far, .fab {
            font-weight: 400 !important; /* Lighter weight for all icons */
            text-shadow: none !important; /* Remove any text shadows */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Specific icon styling for better visual appeal */
        .fas.fa-book, .fas.fa-user, .fas.fa-list, .fas.fa-volume-up,
        .fas.fa-info-circle, .fas.fa-clock, .fas.fa-tags, .fas.fa-book-open,
        .fas.fa-forward, .fas.fa-align-left, .fas.fa-headphones,
        .fas.fa-step-backward, .fas.fa-step-forward, .fas.fa-play-circle,
        .fas.fa-file-alt, .fas.fa-chevron-right {
            color: #6b7280 !important; /* Softer gray color */
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        /* Icon hover effects */
        .fas:hover, .far:hover, .fab:hover {
            opacity: 1;
            transform: scale(1.05);
        }

        /* Badge icons should be smaller and lighter */
        .badge .fas {
            font-size: 0.75em;
            margin-right: 0.25rem;
        }

        /* Navigation icons */
        .navbar .fas {
            color: #4b5563 !important;
            font-weight: 300 !important;
        }

        /* Button icons */
        .btn .fas {
            margin-right: 0.5rem;
            font-size: 0.9em;
        }

        /* Card header icons */
        .card-header .fas {
            color: var(--primary-color) !important;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .genre-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .story-cover {
                height: 150px;
            }

            /* Make icons smaller on mobile */
            .fas, .far, .fab {
                font-size: 0.9em;
            }
        }
    </style>

    @stack('styles')

    <!-- Tracking Codes (Head) -->
    {!! SettingHelper::getHeadTrackingCodes() !!}
</head>
<body>
    <!-- Tracking Codes (Body) -->
    {!! SettingHelper::getBodyTrackingCodes() !!}
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-headphones me-2"></i>Audio Lara
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('stories.hot') }}">Truyện Hot</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('stories.completed') }}">Truyện Full</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('stories.recent') }}">Mới cập nhật</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('authors.index') }}">Tác giả</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Thể loại
                        </a>
                        <ul class="dropdown-menu">
                            @php
                                $genres = \App\Models\Genre::public()->withCount('stories')->having('stories_count', '>', 0)->orderBy('stories_count', 'desc')->limit(10)->get();
                            @endphp
                            @foreach($genres as $genre)
                                <li><a class="dropdown-item" href="{{ route('genre.show', $genre->slug) }}">{{ $genre->name }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex search-form" action="{{ route('search') }}" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Tìm truyện..." 
                           value="{{ request('q') }}" aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-headphones me-2"></i>Audio Lara</h5>
                    <p class="mb-3">Trang nghe truyện audio online miễn phí với nhiều thể loại phong phú và chất lượng cao.</p>
                </div>
                <div class="col-md-3">
                    <h6>Danh mục</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('stories.hot') }}" class="text-light text-decoration-none">Truyện Hot</a></li>
                        <li><a href="{{ route('stories.completed') }}" class="text-light text-decoration-none">Truyện Full</a></li>
                        <li><a href="{{ route('stories.recent') }}" class="text-light text-decoration-none">Mới cập nhật</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Liên hệ</h6>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>contact@audiolara.com</p>
                    <p><i class="fas fa-globe me-2"></i>www.audiolara.com</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; {{ date('Y') }} Audio Lara. Trang nghe truyện audio miễn phí.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS (Local) -->
    <script src="{{ asset('assets/js/bootstrap-5.3.0.bundle.min.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
