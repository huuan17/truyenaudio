@props([
    'collapsed' => false
])

@php
    use App\Helpers\AdminHelper;
    $menuItems = AdminHelper::getMenuItems();
@endphp

<aside class="sidebar {{ $collapsed ? 'collapsed' : '' }}">
    <!-- Logo -->
    <div class="sidebar-header">
        <div class="logo">
            <img src="{{ AdminHelper::config('logo', '/assets/images/logo.png') }}" alt="Logo" class="logo-img">
            <span class="logo-text">{{ AdminHelper::config('name') }}</span>
        </div>
        <button type="button" class="sidebar-toggle btn btn-link" data-bs-toggle="tooltip" title="Thu gọn sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul class="nav nav-pills nav-sidebar flex-column">
            @foreach($menuItems as $item)
                @if(isset($item['children']))
                    <!-- Menu with children -->
                    <li class="nav-item has-children {{ collect($item['children'])->contains('active', true) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#menu-{{ Str::slug($item['title']) }}">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                            <span class="nav-text">{{ $item['title'] }}</span>
                            <i class="fas fa-angle-left nav-arrow"></i>
                        </a>
                        <div class="collapse {{ collect($item['children'])->contains('active', true) ? 'show' : '' }}" id="menu-{{ Str::slug($item['title']) }}">
                            <ul class="nav nav-treeview">
                                @foreach($item['children'] as $child)
                                    <li class="nav-item">
                                        <a href="{{ $child['url'] }}" class="nav-link {{ $child['active'] ? 'active' : '' }}">
                                            @if(isset($child['icon']))
                                                <i class="{{ $child['icon'] }}"></i>
                                            @else
                                                <i class="far fa-circle nav-icon"></i>
                                            @endif
                                            <span class="nav-text">{{ $child['title'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @else
                    <!-- Single menu item -->
                    <li class="nav-item">
                        <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                            <span class="nav-text">{{ $item['title'] }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ auth()->user()->role ?? 'Admin' }}</div>
            </div>
        </div>
        
        <div class="sidebar-actions">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-link btn-sm" data-bs-toggle="tooltip" title="Cài đặt">
                <i class="fas fa-cog"></i>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link btn-sm" data-bs-toggle="tooltip" title="Đăng xuất">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

@push('styles')
<style>
.sidebar {
    width: 250px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar.collapsed {
    width: 60px;
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    display: flex;
    align-items: center;
    color: white;
    text-decoration: none;
}

.logo-img {
    width: 32px;
    height: 32px;
    margin-right: 10px;
}

.logo-text {
    font-weight: 600;
    font-size: 1.1rem;
}

.sidebar.collapsed .logo-text,
.sidebar.collapsed .nav-text,
.sidebar.collapsed .nav-arrow,
.sidebar.collapsed .user-details {
    display: none;
}

.sidebar-toggle {
    color: white;
    border: none;
    padding: 0.25rem;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
}

.nav-sidebar .nav-item {
    margin-bottom: 2px;
}

.nav-sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 12px 20px;
    border-radius: 8px;
    margin: 0 10px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.nav-sidebar .nav-link:hover,
.nav-sidebar .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,0.1);
    transform: translateX(5px);
}

.nav-sidebar .nav-link i {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}

.nav-arrow {
    margin-left: auto;
    transition: transform 0.3s ease;
}

.has-children.menu-open .nav-arrow {
    transform: rotate(-90deg);
}

.nav-treeview {
    padding-left: 0;
    margin-left: 30px;
}

.nav-treeview .nav-link {
    padding: 8px 20px;
    font-size: 0.9rem;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    color: white;
}

.user-avatar {
    font-size: 2rem;
    margin-right: 10px;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.user-role {
    font-size: 0.8rem;
    opacity: 0.8;
}

.sidebar-actions {
    display: flex;
    justify-content: space-around;
}

.sidebar-actions .btn {
    color: rgba(255,255,255,0.8);
    padding: 0.5rem;
}

.sidebar-actions .btn:hover {
    color: white;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
        
        // Restore sidebar state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }
    
    // Mobile sidebar toggle
    const mobileToggle = document.querySelector('.mobile-sidebar-toggle');
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !e.target.matches('.mobile-sidebar-toggle')) {
                sidebar.classList.remove('show');
            }
        }
    });
});
</script>
@endpush
