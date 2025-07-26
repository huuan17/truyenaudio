@props([
    'title' => 'Admin Dashboard',
    'breadcrumbs' => [],
    'showSidebar' => true,
    'sidebarCollapsed' => false,
    'pageClass' => '',
])

@php
    use App\Services\AssetManager;
    use App\Helpers\AdminHelper;
    
    // Add page-specific assets
    AssetManager::addPageAssets();
@endphp

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ AdminHelper::config('name') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts (keep CDN for fonts) -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600&display=swap" rel="stylesheet">
    
    <!-- CSS Assets -->
    {!! AssetManager::renderCSS() !!}
    
    @stack('styles')
</head>
<body class="admin-layout {{ $pageClass }} {{ $sidebarCollapsed ? 'sidebar-collapsed' : '' }}">
    <div class="wrapper">
        @if($showSidebar)
            <!-- Sidebar -->
            <x-admin.sidebar :collapsed="$sidebarCollapsed" />
        @endif
        
        <!-- Main Content -->
        <div class="main-content {{ $showSidebar ? 'with-sidebar' : 'full-width' }}">
            <!-- Header -->
            <x-admin.header :title="$title" :breadcrumbs="$breadcrumbs" />
            
            <!-- Content -->
            <main class="content-wrapper">
                @if(session('success'))
                    <x-ui.alert type="success" :message="session('success')" dismissible />
                @endif
                
                @if(session('error'))
                    <x-ui.alert type="danger" :message="session('error')" dismissible />
                @endif
                
                @if(session('warning'))
                    <x-ui.alert type="warning" :message="session('warning')" dismissible />
                @endif
                
                @if($errors->any())
                    <x-ui.alert type="danger" dismissible>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif
                
                {{ $slot }}
            </main>
            
            <!-- Footer -->
            <x-admin.footer />
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="spinner-overlay" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
    </div>
    
    <!-- JavaScript Assets -->
    {!! AssetManager::renderJS() !!}
    
    <!-- Global JavaScript Configuration -->
    <script>
        // Global admin configuration
        window.AdminConfig = {
            baseUrl: '{{ url('/') }}',
            apiUrl: '{{ url('/api') }}',
            csrfToken: '{{ csrf_token() }}',
            locale: '{{ app()->getLocale() }}',
            user: @json(auth()->user()),
            settings: {
                notifications: @json(AdminHelper::config('notifications')),
                ui: @json(AdminHelper::config('ui')),
                pagination: @json(AdminHelper::getPaginationSettings()),
            },
            routes: {
                dashboard: '{{ route('admin.dashboard') }}',
                logout: '{{ route('logout') }}',
            }
        };
        
        // Initialize admin when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            if (window.Admin) {
                Admin.init();
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
