@props([
    'title' => '',
    'breadcrumbs' => []
])

@php
    use App\Helpers\AdminHelper;
@endphp

<header class="main-header">
    <div class="header-content">
        <!-- Mobile sidebar toggle -->
        <button type="button" class="mobile-sidebar-toggle btn btn-link d-md-none">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page title and breadcrumbs -->
        <div class="header-left">
            <h1 class="page-title">{{ $title }}</h1>
            
            @if(count($breadcrumbs) > 0)
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-home"></i>
                                Dashboard
                            </a>
                        </li>
                        @foreach($breadcrumbs as $breadcrumb)
                            @if(isset($breadcrumb['url']) && $breadcrumb['url'])
                                <li class="breadcrumb-item">
                                    <a href="{{ $breadcrumb['url'] }}">
                                        @if(isset($breadcrumb['icon']))
                                            <i class="{{ $breadcrumb['icon'] }}"></i>
                                        @endif
                                        {{ $breadcrumb['title'] }}
                                    </a>
                                </li>
                            @else
                                <li class="breadcrumb-item active" aria-current="page">
                                    @if(isset($breadcrumb['icon']))
                                        <i class="{{ $breadcrumb['icon'] }}"></i>
                                    @endif
                                    {{ $breadcrumb['title'] }}
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            @endif
        </div>
        
        <!-- Header actions -->
        <div class="header-right">
            <!-- Quick actions -->
            <div class="quick-actions">
                <a href="{{ route('admin.video-generator.index') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Tạo video">
                    <i class="fas fa-video"></i>
                    <span class="d-none d-sm-inline">Tạo video</span>
                </a>
                
                <a href="{{ route('admin.stories.create') }}" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="Thêm truyện">
                    <i class="fas fa-plus"></i>
                    <span class="d-none d-sm-inline">Thêm truyện</span>
                </a>
            </div>
            
            <!-- Notifications -->
            <div class="notifications dropdown">
                <button class="btn btn-link notification-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <div class="dropdown-header">
                        <h6 class="mb-0">Thông báo</h6>
                        <small class="text-muted">Bạn có 3 thông báo mới</small>
                    </div>
                    <div class="dropdown-divider"></div>
                    
                    <a href="#" class="dropdown-item notification-item">
                        <div class="notification-icon bg-success">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Video đã tạo thành công</div>
                            <div class="notification-time">2 phút trước</div>
                        </div>
                    </a>
                    
                    <a href="#" class="dropdown-item notification-item">
                        <div class="notification-icon bg-info">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Crawl truyện hoàn thành</div>
                            <div class="notification-time">5 phút trước</div>
                        </div>
                    </a>
                    
                    <a href="#" class="dropdown-item notification-item">
                        <div class="notification-icon bg-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">TTS queue đang đầy</div>
                            <div class="notification-time">10 phút trước</div>
                        </div>
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item text-center">Xem tất cả thông báo</a>
                </div>
            </div>
            
            <!-- User menu -->
            <div class="user-menu dropdown">
                <button class="btn btn-link user-toggle" type="button" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <span class="user-name d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    <i class="fas fa-chevron-down ms-1"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <div class="dropdown-header">
                        <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                        <small class="text-muted">{{ auth()->user()->email }}</small>
                    </div>
                    <div class="dropdown-divider"></div>
                    
                    <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                        <i class="fas fa-user me-2"></i>
                        Hồ sơ cá nhân
                    </a>
                    
                    <a href="{{ route('admin.settings.index') }}" class="dropdown-item">
                        <i class="fas fa-cog me-2"></i>
                        Cài đặt
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

@push('styles')
<style>
.main-header {
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-bottom: 1px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 999;
    margin-left: 250px;
    transition: margin-left 0.3s ease;
}

.sidebar.collapsed ~ .main-content .main-header {
    margin-left: 60px;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
}

.header-left {
    flex: 1;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.breadcrumb {
    background: none;
    padding: 0;
    margin: 0.5rem 0 0 0;
    font-size: 0.875rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

.breadcrumb-item a {
    color: #6c757d;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #495057;
}

.breadcrumb-item.active {
    color: #495057;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quick-actions {
    display: flex;
    gap: 0.5rem;
}

.notification-toggle,
.user-toggle {
    position: relative;
    color: #6c757d;
    border: none;
    padding: 0.5rem;
}

.notification-toggle:hover,
.user-toggle:hover {
    color: #495057;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-dropdown {
    width: 320px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    color: white;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 500;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.notification-time {
    font-size: 0.75rem;
    color: #6c757d;
}

.user-toggle {
    display: flex;
    align-items: center;
}

.user-avatar {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.user-name {
    font-weight: 500;
}

.mobile-sidebar-toggle {
    color: #6c757d;
    border: none;
    padding: 0.5rem;
    margin-right: 1rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .main-header {
        margin-left: 0;
    }
    
    .header-content {
        padding: 0.75rem 1rem;
    }
    
    .page-title {
        font-size: 1.25rem;
    }
    
    .quick-actions {
        display: none;
    }
    
    .notification-dropdown {
        width: 280px;
    }
}
</style>
@endpush
