<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Quản lý truyện | Admin</title>

  <!-- Google Font (keep CDN for fonts) -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600&display=swap" rel="stylesheet">

  <!-- Font Awesome (Local - Complete) -->
  <link rel="stylesheet" href="{{ asset('assets/css/fontawesome-6.4.0-all.min.css') }}">
  <!-- Icon Fallback CSS (for missing webfonts) -->
  <link rel="stylesheet" href="{{ asset('assets/css/icon-fallback.css') }}">

  <!-- AdminLTE (Local) -->
  <link rel="stylesheet" href="{{ asset('assets/css/adminlte.min.css') }}">

  <!-- Select2 (Local) -->
  <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />

  <!-- Sortable CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/sortable.css') }}">

  <!-- Toastr CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/toastr.min.css') }}">

  <!-- Custom Toast Styling -->
  <style>
    /* Custom Toastr Styling */
    .toast-top-right {
        top: 70px !important;
        right: 12px !important;
    }

    .toast-container .toast {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        font-family: 'Source Sans Pro', sans-serif;
    }

    .toast-success {
        background-color: #28a745;
        border-left: 4px solid #1e7e34;
    }

    .toast-error {
        background-color: #dc3545;
        border-left: 4px solid #c82333;
    }

    .toast-warning {
        background-color: #ffc107;
        color: #212529;
        border-left: 4px solid #e0a800;
    }

    .toast-info {
        background-color: #17a2b8;
        border-left: 4px solid #138496;
    }

    .toast-title {
        font-weight: 600;
        font-size: 14px;
    }

    .toast-message {
        font-size: 13px;
        line-height: 1.4;
    }

    .toast-progress {
        height: 3px;
        background-color: rgba(255,255,255,0.3);
    }

    .toast-close-button {
        font-size: 16px;
        font-weight: bold;
        opacity: 0.8;
    }

    .toast-close-button:hover {
        opacity: 1;
    }
  </style>

  <!-- Custom CSS -->
  <style>
    .btn-purple {
      background-color: #6f42c1;
      border-color: #6f42c1;
      color: #fff;
    }
    .btn-purple:hover {
      background-color: #5a32a3;
      border-color: #5a32a3;
      color: #fff;
    }
    .bg-purple {
      background-color: #6f42c1 !important;
    }

    /* Homepage Link in Header */
    .navbar-nav .nav-link[href*="home"] {
      transition: all 0.3s ease;
      border-radius: 6px;
      margin-right: 8px;
    }

    .navbar-nav .nav-link[href*="home"]:hover {
      background-color: rgba(40, 167, 69, 0.1);
      transform: translateY(-1px);
    }

    /* Direct Logout Button Styling */
    .navbar-nav .nav-item form button.nav-link {
      color: #dc3545 !important;
      transition: all 0.3s ease;
      border-radius: 6px;
      padding: 0.5rem 0.75rem !important;
      margin: 0 2px;
    }

    .navbar-nav .nav-item form button.nav-link:hover {
      background-color: rgba(220, 53, 69, 0.1) !important;
      color: #c82333 !important;
      transform: translateY(-1px);
    }

    .navbar-nav .nav-item form button.nav-link:focus {
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
      outline: none;
    }



    /* Dropdown Menu CSS Fixes - Override AdminLTE conflicts */
    .navbar-nav .dropdown-menu {
      position: absolute !important;
      z-index: 9999 !important;
      display: none !important;
      min-width: 200px !important;
      background-color: #fff !important;
      border: 1px solid rgba(0,0,0,.15) !important;
      border-radius: 0.375rem !important;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175) !important;
      margin-top: 0.125rem !important;
      top: 100% !important;
      left: auto !important;
    }

    .navbar-nav .dropdown-menu.show {
      display: block !important;
    }

    .navbar-nav .dropdown-menu-right {
      right: 0 !important;
      left: auto !important;
    }

    .navbar-nav .dropdown-toggle::after {
      display: none !important; /* Hide default Bootstrap caret */
    }

    .navbar-nav .dropdown-item {
      display: block !important;
      width: 100% !important;
      padding: 0.375rem 1rem !important;
      clear: both !important;
      font-weight: 400 !important;
      color: #212529 !important;
      text-align: inherit !important;
      text-decoration: none !important;
      white-space: nowrap !important;
      background-color: transparent !important;
      border: 0 !important;
      cursor: pointer !important;
    }

    .navbar-nav .dropdown-item:hover,
    .navbar-nav .dropdown-item:focus {
      color: #16181b !important;
      background-color: #f8f9fa !important;
      text-decoration: none !important;
    }

    .navbar-nav .dropdown-divider {
      height: 0 !important;
      margin: 0.5rem 0 !important;
      overflow: hidden !important;
      border-top: 1px solid #e9ecef !important;
    }

    .navbar-nav .dropdown-header {
      display: block !important;
      padding: 0.5rem 1rem !important;
      margin-bottom: 0 !important;
      font-size: 0.875rem !important;
      color: #6c757d !important;
      white-space: nowrap !important;
    }

    /* AdminLTE specific overrides */
    .main-header .navbar-nav .dropdown-menu {
      z-index: 10000 !important;
      position: absolute !important;
    }

    .main-header .navbar-nav .nav-item.dropdown {
      position: relative !important;
    }

    /* Force visibility for debugging */
    .dropdown-debug .dropdown-menu {
      display: block !important;
      position: static !important;
      background-color: #fff !important;
      border: 2px solid #007bff !important;
    }

    .navbar-nav .nav-link[href*="home"] .fas {
      transition: all 0.3s ease;
    }

    .navbar-nav .nav-link[href*="home"]:hover .fas {
      transform: scale(1.1);
    }

    /* Responsive text for homepage link */
    @media (max-width: 768px) {
      .navbar-nav .nav-link[href*="home"] span {
        display: none !important;
      }
    }

    /* Floating Help Button */
    .floating-help-btn {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 9999;
    }

    .floating-help-btn .btn {
      width: 60px;
      height: 60px;
      border: none;
      transition: all 0.3s ease;
      animation: pulse 2s infinite;
    }

    .floating-help-btn .btn:hover {
      transform: scale(1.1);
      animation: none;
    }

    .floating-help-btn .dropdown-menu {
      bottom: 70px;
      right: 0;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      border-radius: 10px;
      min-width: 250px;
    }

    .floating-help-btn .dropdown-item {
      padding: 10px 20px;
      transition: all 0.3s ease;
    }

    .floating-help-btn .dropdown-item:hover {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      transform: translateX(5px);
    }

    .floating-help-btn .dropdown-header {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      margin: 0;
      padding: 10px 20px;
      border-radius: 10px 10px 0 0;
    }

    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(23, 162, 184, 0.7);
      }
      70% {
        box-shadow: 0 0 0 10px rgba(23, 162, 184, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(23, 162, 184, 0);
      }
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
      .floating-help-btn {
        bottom: 20px;
        right: 20px;
      }

      .floating-help-btn .btn {
        width: 50px;
        height: 50px;
      }

      .floating-help-btn .dropdown-menu {
        min-width: 200px;
        bottom: 60px;
      }
    }

    /* Help section active states */
    .nav-treeview .nav-link.active {
      background-color: rgba(255,255,255,0.1) !important;
    }

    .nav-item.menu-open > .nav-link {
      background-color: rgba(255,255,255,0.1) !important;
    }

    /* Enhanced Collapsible Menu Styles */
    .main-sidebar {
      background: linear-gradient(180deg, #343a40 0%, #495057 100%);
    }

    .nav-sidebar .nav-link {
      color: rgba(255,255,255,.8);
      transition: all 0.3s ease;
      border-radius: 4px;
      margin: 0 8px 2px 8px;
    }

    .nav-sidebar .nav-link:hover {
      background-color: rgba(255,255,255,.1);
      color: #fff;
      transform: translateX(2px);
    }

    .nav-sidebar .nav-link.active {
      background-color: #007bff;
      color: #fff;
      box-shadow: 0 2px 4px rgba(0,123,255,0.3);
    }

    /* Collapsible Menu Tree Styles */
    .nav-treeview .nav-link {
      padding-left: 2.5rem;
      font-size: 0.9rem;
      margin: 0 8px 1px 8px;
    }

    .nav-treeview .nav-link:hover {
      background-color: rgba(255,255,255,.05);
      border-left: 3px solid #007bff;
    }

    .nav-treeview .nav-link.active {
      background-color: rgba(0,123,255,0.2);
      border-left: 3px solid #007bff;
    }

    /* Badge Styles */
    .badge.right {
      margin-left: auto;
      font-size: 0.7rem;
    }

    /* Icon Optimization - Remove harsh borders and make icons lighter */
    .fas, .far, .fab {
        font-weight: 400 !important; /* Lighter weight for all icons */
        text-shadow: none !important; /* Remove any text shadows */
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Icon Colors for better visual hierarchy - with softer tones */
    .nav-icon.text-primary { color: #4f8ef7 !important; opacity: 0.8; }
    .nav-icon.text-success { color: #48bb78 !important; opacity: 0.8; }
    .nav-icon.text-info { color: #38b2ac !important; opacity: 0.8; }
    .nav-icon.text-warning { color: #ed8936 !important; opacity: 0.8; }
    .nav-icon.text-danger { color: #f56565 !important; opacity: 0.8; }
    .nav-icon.text-dark { color: #4a5568 !important; opacity: 0.8; }

    /* General icon styling for admin interface */
    .nav-icon, .fas, .far, .fab {
        transition: all 0.3s ease;
    }

    /* Navbar icons */
    .navbar .fas {
        color: #6b7280 !important;
        font-weight: 300 !important;
        opacity: 0.8;
    }

    .navbar .fas:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    /* Button icons */
    .btn .fas, .btn .far, .btn .fab {
        margin-right: 0.5rem;
        font-size: 0.9em;
        opacity: 0.9;
    }

    /* Card and content icons */
    .card-header .fas, .card-title .fas {
        color: #4f8ef7 !important;
        opacity: 0.7;
        margin-right: 0.5rem;
    }

    /* Table and list icons */
    .table .fas, .list-group .fas {
        color: #6b7280 !important;
        opacity: 0.7;
        font-size: 0.9em;
    }

    /* Badge icons */
    .badge .fas {
        font-size: 0.75em;
        margin-right: 0.25rem;
        opacity: 0.9;
    }

    /* Enhanced icon styling for submenu items */
    .nav-treeview .nav-icon {
      font-size: 0.9rem;
      width: 20px;
      text-align: center;
      margin-right: 8px;
    }

    /* Parent menu icons */
    .nav-sidebar > .nav-item > .nav-link .nav-icon {
      font-size: 1.1rem;
      width: 24px;
      text-align: center;
    }

    /* Icon hover effects */
    .nav-link:hover .nav-icon {
      transform: scale(1.1);
      transition: transform 0.2s ease;
    }

    /* Parent menu item when expanded */
    .nav-item.menu-open > .nav-link {
      background-color: rgba(255,255,255,.15) !important;
      color: #fff;
    }

    /* Smooth expand/collapse animation */
    .nav-treeview {
      transition: all 0.3s ease;
    }

    /* Compact spacing */
    .nav-sidebar .nav-item {
      margin-bottom: 1px;
    }

    /* Arrow rotation animation */
    .nav-link .fas.fa-angle-left {
      transition: transform 0.3s ease;
    }

    .menu-open > .nav-link .fas.fa-angle-left {
      transform: rotate(-90deg);
    }

    /* Hide submenu by default */
    .nav-treeview {
      display: none;
    }

    /* Show submenu when parent is open */
    .menu-open > .nav-treeview {
      display: block;
    }

    /* Ensure smooth transitions */
    .nav-item {
      overflow: hidden;
    }

    /* Multi-level menu support */
    .nav-sidebar .nav-item.menu-open {
      margin-bottom: 4px;
    }

    /* Visual separator between open menus */
    .nav-sidebar .nav-item.menu-open + .nav-item.menu-open {
      border-top: 1px solid rgba(255,255,255,0.1);
      padding-top: 4px;
    }

    /* Highlight parent menu when submenu is active */
    .nav-sidebar .nav-item.menu-open > .nav-link {
      background-color: rgba(255,255,255,0.15) !important;
      border-left: 3px solid #007bff;
    }

    /* Improved submenu styling for multiple open menus */
    .nav-treeview {
      background-color: rgba(0,0,0,0.1);
      border-radius: 0 0 4px 4px;
      margin: 0 8px 4px 8px;
      padding: 4px 0;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Homepage Link -->
      <li class="nav-item">
        <a class="nav-link" href="{{ route('home') }}" target="_blank" title="Xem trang chủ website">
          <i class="fas fa-external-link-alt text-success"></i>
          <span class="ml-1 d-none d-md-inline">Trang chủ</span>
        </a>
      </li>



      <!-- Direct Logout Link -->
      <li class="nav-item">
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
          @csrf
          <button type="submit" class="nav-link btn btn-link text-danger p-0"
                  style="border: none; background: none; text-decoration: none; color: #dc3545 !important;"
                  onclick="return confirm('Bạn có chắc muốn đăng xuất?')"
                  title="Đăng xuất">
            <i class="fas fa-sign-out-alt"></i>
            <span class="ml-1 d-none d-md-inline">Đăng xuất</span>
          </button>
        </form>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Main Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
      <span class="brand-text font-weight-light ml-2">Audio Lara Admin</span>
    </a>
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
          <!-- 1. Bảng điều khiển -->
          <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Bảng điều khiển</p>
            </a>
          </li>

          <!-- 2. Tạo Video (Collapsible) -->
          <li class="nav-item {{ request()->routeIs('admin.logos.*') || request()->routeIs('admin.video-generator.*') || request()->routeIs('admin.video-templates.*') || request()->routeIs('admin.audio-library.*') || request()->routeIs('admin.video-queue.*') || request()->routeIs('admin.videos.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('admin.logos.*') || request()->routeIs('admin.video-generator.*') || request()->routeIs('admin.video-templates.*') || request()->routeIs('admin.audio-library.*') || request()->routeIs('admin.video-queue.*') || request()->routeIs('admin.videos.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-video text-primary"></i>
              <p>
                Tạo Video
                <i class="fas fa-angle-left right"></i>
                <span class="badge badge-info right">6</span>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.logos.index') }}" class="nav-link {{ request()->routeIs('admin.logos.*') ? 'active' : '' }}">
                  <i class="fas fa-image nav-icon"></i>
                  <p>Quản Lý Logo</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.video-generator.index') }}" class="nav-link {{ request()->routeIs('admin.video-generator.*') ? 'active' : '' }}">
                  <i class="fas fa-video nav-icon text-primary"></i>
                  <p>Tạo Video</p>
                  <span class="badge badge-info right">Universal</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.video-templates.index') }}" class="nav-link {{ request()->routeIs('admin.video-templates.*') ? 'active' : '' }}">
                  <i class="fas fa-layer-group nav-icon text-warning"></i>
                  <p>Template Video</p>
                  <span class="badge badge-warning right">New</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.audio-library.index') }}" class="nav-link {{ request()->routeIs('admin.audio-library.*') ? 'active' : '' }}">
                  <i class="fas fa-music nav-icon text-success"></i>
                  <p>Thư viện Audio</p>
                  <span class="badge badge-success right">New</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.video-queue.index') }}" class="nav-link {{ request()->routeIs('admin.video-queue.*') ? 'active' : '' }}">
                  <i class="fas fa-tasks nav-icon text-info"></i>
                  <p>Trạng thái xử lý</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.videos.index') }}" class="nav-link {{ request()->routeIs('admin.videos.*') ? 'active' : '' }}">
                  <i class="fas fa-folder-open nav-icon text-success"></i>
                  <p>Quản lý Video</p>
                </a>
              </li>
            </ul>
          </li>



          <!-- 3. Mạng Xã Hội (Collapsible) -->
          <li class="nav-item {{ request()->routeIs('admin.channels.*') || request()->routeIs('admin.scheduled-posts.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('admin.channels.*') || request()->routeIs('admin.scheduled-posts.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-share-alt text-info"></i>
              <p>
                Mạng Xã Hội
                <i class="fas fa-angle-left right"></i>
                <span class="badge badge-success right">2</span>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.channels.index') }}" class="nav-link {{ request()->routeIs('admin.channels.*') ? 'active' : '' }}">
                  <i class="fas fa-broadcast-tower nav-icon"></i>
                  <p>Quản Lý Kênh</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.scheduled-posts.index') }}" class="nav-link {{ request()->routeIs('admin.scheduled-posts.*') ? 'active' : '' }}">
                  <i class="fas fa-calendar-alt nav-icon"></i>
                  <p>Lịch Đăng Video</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- 4. Quản lý Truyện (Collapsible) -->
          <li class="nav-item {{ request()->routeIs('admin.stories.*') || request()->routeIs('admin.authors.*') || request()->routeIs('admin.genres.*') || request()->routeIs('admin.crawl-monitor.*') || request()->routeIs('admin.tts-monitor.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('admin.stories.*') || request()->routeIs('admin.authors.*') || request()->routeIs('admin.genres.*') || request()->routeIs('admin.crawl-monitor.*') || request()->routeIs('admin.tts-monitor.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-book text-success"></i>
              <p>
                Quản lý Truyện
                <i class="fas fa-angle-left right"></i>
                <span class="badge badge-primary right">5</span>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.stories.index') }}" class="nav-link {{ request()->routeIs('admin.stories.*') ? 'active' : '' }}">
                  <i class="fas fa-book nav-icon"></i>
                  <p>Truyện</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.authors.index') }}" class="nav-link {{ request()->routeIs('admin.authors.*') ? 'active' : '' }}">
                  <i class="fas fa-user-tie nav-icon"></i>
                  <p>Tác giả</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.genres.index') }}" class="nav-link {{ request()->routeIs('admin.genres.*') ? 'active' : '' }}">
                  <i class="fas fa-tags nav-icon"></i>
                  <p>Thể loại</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.tts-monitor.index') }}" class="nav-link {{ request()->routeIs('admin.tts-monitor.*') ? 'active' : '' }}">
                  <i class="fas fa-headphones nav-icon text-warning"></i>
                  <p>Giám sát TTS</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.crawl-monitor.index') }}" class="nav-link {{ request()->routeIs('admin.crawl-monitor.*') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon text-danger"></i>
                  <p>Giám sát Crawl</p>
                  @php
                    $crawlingCount = \App\Models\Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))->count();
                  @endphp
                  @if($crawlingCount > 0)
                    <span class="badge badge-danger right">{{ $crawlingCount }}</span>
                  @endif
                </a>
              </li>
            </ul>
          </li>

          <!-- User Management -->
          @if(auth()->user()->isAdmin())
          <li class="nav-item {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
                Quản lý Users
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                  <i class="fas fa-users nav-icon"></i>
                  <p>Người dùng</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                  <i class="fas fa-user-shield nav-icon"></i>
                  <p>Vai trò & Quyền</p>
                </a>
              </li>
            </ul>
          </li>
          @endif

          <!-- 5. Quản trị hệ thống (Collapsible) -->
          @if(auth()->user()->isAdmin())
          <li class="nav-item {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.system.*') || request()->routeIs('admin.logs.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.system.*') || request()->routeIs('admin.logs.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-cogs text-danger"></i>
              <p>
                Quản trị hệ thống
                <i class="fas fa-angle-left right"></i>
                <span class="badge badge-danger right">Admin</span>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                  <i class="fas fa-cog nav-icon"></i>
                  <p>Cài đặt hệ thống</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.system.upload-config') }}" class="nav-link {{ request()->routeIs('admin.system.*') ? 'active' : '' }}">
                  <i class="fas fa-upload nav-icon"></i>
                  <p>Cấu hình Upload</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.logs.index') }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                  <i class="fas fa-file-alt nav-icon"></i>
                  <p>System Logs</p>
                </a>
              </li>
            </ul>
          </li>
          @endif

          <!-- 6. Trợ giúp và hướng dẫn (Collapsible) -->
          <li class="nav-item {{ request()->routeIs('admin.help.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('admin.help.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-life-ring text-info"></i>
              <p>
                Trợ giúp & Hướng dẫn
                <i class="fas fa-angle-left right"></i>
                <span class="badge badge-info right">7</span>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.help.index') }}" class="nav-link {{ request()->routeIs('admin.help.index') ? 'active' : '' }}">
                  <i class="fas fa-home nav-icon text-info"></i>
                  <p>Tổng quan</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.show', 'stories') }}" class="nav-link {{ request()->routeIs('admin.help.show') && request()->route('section') === 'stories' ? 'active' : '' }}">
                  <i class="fas fa-book nav-icon text-success"></i>
                  <p>Quản lý Truyện</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.show', 'chapters') }}" class="nav-link {{ request()->routeIs('admin.help.show') && request()->route('section') === 'chapters' ? 'active' : '' }}">
                  <i class="fas fa-file-alt nav-icon text-info"></i>
                  <p>Thao tác hàng loạt</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.show', 'audio') }}" class="nav-link {{ request()->routeIs('admin.help.show') && request()->route('section') === 'audio' ? 'active' : '' }}">
                  <i class="fas fa-volume-up nav-icon text-warning"></i>
                  <p>Trình phát âm thanh</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.show', 'video') }}" class="nav-link {{ request()->routeIs('admin.help.show') && request()->route('section') === 'video' ? 'active' : '' }}">
                  <i class="fas fa-video nav-icon text-danger"></i>
                  <p>Tạo Video</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.show', 'queue') }}" class="nav-link {{ request()->routeIs('admin.help.show') && request()->route('section') === 'queue' ? 'active' : '' }}">
                  <i class="fas fa-tasks nav-icon text-dark"></i>
                  <p>Quản lý hàng đợi</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.queue-workers') }}" class="nav-link {{ request()->routeIs('admin.help.queue-workers') ? 'active' : '' }}">
                  <i class="fas fa-cogs nav-icon text-primary"></i>
                  <p>Queue Workers</p>
                  <span class="badge badge-warning right">New</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.help.show', 'troubleshooting') }}" class="nav-link {{ request()->routeIs('admin.help.show') && request()->route('section') === 'troubleshooting' ? 'active' : '' }}">
                  <i class="fas fa-tools nav-icon text-danger"></i>
                  <p>Xử lý sự cố</p>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- Content Wrapper. -->
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        @yield('content')
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer text-center text-sm">
    <strong>Copyright &copy; {{ date('Y') }}.</strong> All rights reserved.
  </footer>

  <!-- Floating Help Button -->
  <div class="floating-help-btn">
    <div class="btn-group dropup">
      <button type="button" class="btn btn-info btn-lg rounded-circle shadow"
              data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
              title="Trợ giúp nhanh">
        <i class="fas fa-question"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-right">
        <h6 class="dropdown-header">
          <i class="fas fa-question-circle me-2"></i>
          Trợ giúp nhanh
        </h6>
        <a class="dropdown-item" href="{{ route('admin.help.index') }}">
          <i class="fas fa-home me-2 text-primary"></i>
          Tổng quan hệ thống
        </a>
        <a class="dropdown-item" href="{{ route('admin.help.show', 'stories') }}">
          <i class="fas fa-book me-2 text-success"></i>
          Quản lý Truyện
        </a>
        <a class="dropdown-item" href="{{ route('admin.help.show', 'chapters') }}">
          <i class="fas fa-file-alt me-2 text-info"></i>
          Thao tác hàng loạt
        </a>
        <a class="dropdown-item" href="{{ route('admin.help.show', 'audio') }}">
          <i class="fas fa-volume-up me-2 text-warning"></i>
          Trình phát âm thanh
        </a>
        <a class="dropdown-item" href="{{ route('admin.help.show', 'video') }}">
          <i class="fas fa-video me-2 text-danger"></i>
          Tạo Video
        </a>
        <a class="dropdown-item" href="{{ route('admin.help.queue-workers') }}">
          <i class="fas fa-cogs me-2 text-primary"></i>
          Queue Workers
          <span class="badge badge-warning ml-2">New</span>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{{ route('admin.help.quick-reference') }}">
          <i class="fas fa-bolt me-2 text-warning"></i>
          <strong>Tham khảo nhanh</strong>
        </a>
        <a class="dropdown-item" href="{{ route('admin.help.show', 'troubleshooting') }}">
          <i class="fas fa-tools me-2 text-danger"></i>
          Xử lý sự cố
        </a>
      </div>
    </div>
  </div>
</div>

<!-- jQuery (Local) -->
<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<!-- Bootstrap (Local) -->
<script src="{{ asset('assets/js/bootstrap-4.6.2.bundle.min.js') }}"></script>
<!-- AdminLTE (Local) -->
<script src="{{ asset('assets/js/adminlte.min.js') }}"></script>
<!-- TinyMCE (Local) -->
<script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>

<!-- Page specific scripts -->
@yield('scripts')

<!-- Select2 (Local) -->
<script src="{{ asset('assets/js/select2.min.js') }}"></script>

<!-- Toastr (Local) -->
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Chọn thể loại",
            allowClear: true
        });

        // Configure Toastr
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "400",
            "hideDuration": "1000",
            "timeOut": "6000",
            "extendedTimeOut": "2000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "slideDown",
            "hideMethod": "slideUp",
            "tapToDismiss": true,
            "escapeHtml": false
        };

        // Display flash messages
        @if(session('success'))
            toastr.success('{{ session('success') }}', 'Thành công!');
        @endif

        @if(session('error'))
            toastr.error('{{ session('error') }}', 'Lỗi!');
        @endif

        @if(session('warning'))
            toastr.warning('{{ session('warning') }}', 'Cảnh báo!');
        @endif

        @if(session('info'))
            toastr.info('{{ session('info') }}', 'Thông tin!');
        @endif

        @if($errors->any())
            @foreach($errors->all() as $error)
                toastr.error('{{ $error }}', 'Lỗi xác thực!');
            @endforeach
        @endif
    });

    // Global toast functions
    window.showToast = {
        success: function(message, title = 'Thành công!', options = {}) {
            return toastr.success(message, title, options);
        },
        error: function(message, title = 'Lỗi!', options = {}) {
            return toastr.error(message, title, options);
        },
        warning: function(message, title = 'Cảnh báo!', options = {}) {
            return toastr.warning(message, title, options);
        },
        info: function(message, title = 'Thông tin!', options = {}) {
            return toastr.info(message, title, options);
        },
        clear: function() {
            toastr.clear();
        },
        remove: function() {
            toastr.remove();
        }
    };

    // AJAX Error Handler
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 422) {
            // Validation errors
            var errors = xhr.responseJSON.errors;
            if (errors) {
                Object.keys(errors).forEach(function(key) {
                    errors[key].forEach(function(error) {
                        showToast.error(error, 'Lỗi xác thực!');
                    });
                });
            }
        } else if (xhr.status === 500) {
            showToast.error('Có lỗi xảy ra trên server. Vui lòng thử lại sau.', 'Lỗi server!');
        } else if (xhr.status === 404) {
            showToast.error('Không tìm thấy trang hoặc tài nguyên yêu cầu.', 'Không tìm thấy!');
        } else if (xhr.status === 403) {
            showToast.error('Bạn không có quyền thực hiện thao tác này.', 'Không có quyền!');
        } else if (xhr.status === 401) {
            showToast.error('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 'Chưa xác thực!');
        } else if (xhr.status !== 0) { // Ignore aborted requests
            showToast.error('Có lỗi xảy ra: ' + (xhr.responseJSON?.message || thrownError), 'Lỗi!');
        }
    });

    // AJAX Success Handler for common responses
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
            if (xhr.responseJSON.success !== false) {
                showToast.success(xhr.responseJSON.message);
            }
        }
    });

    // Form submission with toast feedback
    window.submitFormWithToast = function(form, successMessage = 'Thao tác thành công!') {
        var $form = $(form);
        var formData = new FormData(form);

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success !== false) {
                    showToast.success(response.message || successMessage);
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                } else {
                    showToast.error(response.message || 'Có lỗi xảy ra!');
                }
            }
        });
    };
</script>

<!-- Custom AdminLTE Collapsible Menu Script -->
<!--
Multi-Level Sidebar Menu Behavior:
- Click parent menu to expand/collapse
- Multiple menus can be open simultaneously
- Click same parent menu again to close
- No automatic closing when opening other menus
- Keyboard shortcuts: Ctrl+Shift+C (collapse all), Ctrl+Shift+E (expand all)
-->
<script>
$(document).ready(function() {
    console.log('🔧 Initializing multi-level collapsible menu...');

    // Note: Dropdown functionality removed - using direct logout link instead
    console.log('✅ Using direct logout link - no dropdown needed');

    // Note: Admin dropdown functionality removed - using direct logout link
    console.log('✅ Direct logout link implemented - no dropdown initialization needed');

    // Click outside to close dropdown with proper namespace
    $(document).off('click.dropdown').on('click.dropdown', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show').hide();
            $('.dropdown-toggle').attr('aria-expanded', 'false');
            console.log('🔽 Dropdown closed by outside click');
        }
    });

    // Simple fallback toggle function
    window.simpleDropdownToggle = function() {
        console.log('🔧 Simple dropdown toggle');
        var $menu = $('.dropdown-menu');
        if ($menu.is(':visible')) {
            $menu.removeClass('show').hide();
            $('.dropdown-toggle').attr('aria-expanded', 'false');
            console.log('Dropdown hidden');
        } else {
            $menu.addClass('show').show();
            $('.dropdown-toggle').attr('aria-expanded', 'true');
            console.log('Dropdown shown');
        }
    };

    // Admin-specific fix dropdown function for console use
    window.fixAdminDropdown = function() {
        console.log('🔧 Fixing admin dropdown events...');

        // Remove ALL existing handlers including AdminLTE
        $('.dropdown-toggle').off();
        $('a[data-toggle="dropdown"]').off();
        $('.nav-link[data-toggle="dropdown"]').off();

        // Find dropdown elements
        var $toggles = $('.dropdown-toggle');
        if ($toggles.length === 0) {
            $toggles = $('a[data-toggle="dropdown"]');
        }

        console.log('Found toggles to fix:', $toggles.length);

        // Add simple working handler
        $toggles.on('click.fix', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            console.log('🔽 Fixed admin dropdown clicked!');
            var $menu = $(this).next('.dropdown-menu');

            if ($menu.length === 0) {
                $menu = $(this).siblings('.dropdown-menu');
                if ($menu.length === 0) {
                    $menu = $(this).parent().find('.dropdown-menu');
                }
            }

            if ($menu.length > 0) {
                $('.dropdown-menu').not($menu).hide();
                $menu.toggle();
                console.log('Menu toggled. Visible:', $menu.is(':visible'));
            } else {
                console.error('Menu not found');
            }
        });

        console.log('✅ Admin dropdown fixed! Try clicking now.');
    };

    // Legacy fix function
    window.fixDropdown = window.fixAdminDropdown;

    // Debug function for manual testing
    window.debugDropdown = function() {
        console.log('=== DROPDOWN DEBUG ===');
        $('.dropdown-toggle').each(function(i) {
            var $toggle = $(this);
            var $menu = $toggle.next('.dropdown-menu');
            console.log('Dropdown ' + (i+1) + ':', {
                'Toggle classes': $toggle.attr('class'),
                'Menu classes': $menu.attr('class'),
                'Menu computed styles': {
                    'display': $menu.css('display'),
                    'position': $menu.css('position'),
                    'z-index': $menu.css('z-index'),
                    'top': $menu.css('top'),
                    'right': $menu.css('right'),
                    'visibility': $menu.css('visibility'),
                    'opacity': $menu.css('opacity')
                },
                'Event handlers': $._data($toggle[0], 'events')
            });
        });

        console.log('Available functions:');
        console.log('- debugDropdown() - Show debug info');
        console.log('- fixDropdown() - Fix dropdown events');
        console.log('- simpleDropdownToggle() - Manual toggle');
    };

    // Wait for DOM to be fully ready
    setTimeout(function() {
        initializeCollapsibleMenu();
    }, 100);
});

function initializeCollapsibleMenu() {
    console.log('📋 Setting up menu handlers...');

    // Remove any existing handlers to prevent duplicates
    $('.nav-sidebar .nav-item > .nav-link').off('click.collapsible');

    // Add click handler for parent menu items
    $('.nav-sidebar .nav-item > .nav-link').on('click.collapsible', function(e) {
        var $this = $(this);
        var $parent = $this.parent('.nav-item');
        var $treeview = $parent.find('.nav-treeview');
        var menuText = $this.find('p').first().text().trim();

        console.log('🖱️ Menu clicked:', menuText);

        // Only handle parent menu items (those with treeview)
        if ($treeview.length > 0) {
            e.preventDefault();
            e.stopPropagation();

            console.log('📁 Handling collapsible menu for:', menuText);

            // Toggle current menu only (no accordion behavior)
            if ($parent.hasClass('menu-open')) {
                // Close menu
                console.log('📤 Closing menu:', menuText);
                $parent.removeClass('menu-open');
                $treeview.slideUp(250, function() {
                    console.log('✅ Menu closed:', menuText);
                });
            } else {
                // Open current menu (keep other menus as they are)
                console.log('📥 Opening menu:', menuText);
                $parent.addClass('menu-open');
                $treeview.slideDown(250, function() {
                    console.log('✅ Menu opened:', menuText);
                });
            }

            return false;
        } else {
            console.log('🔗 Regular link clicked:', menuText);
        }
    });

    // Auto-open menu if current page is in submenu
    $('.nav-treeview .nav-link.active').each(function() {
        var activeText = $(this).find('p').text().trim();
        console.log('🎯 Auto-opening menu for active item:', activeText);

        var $parentMenu = $(this).closest('.nav-item');
        $parentMenu.addClass('menu-open');
        $parentMenu.find('.nav-treeview').show();

        console.log('✅ Auto-opened parent menu');
    });

    // Debug info
    var menuCount = $('.nav-sidebar .nav-item').length;
    var treeviewCount = $('.nav-treeview').length;
    var activeCount = $('.nav-treeview .nav-link.active').length;

    console.log('📊 Menu Debug Info:');
    console.log('  - Total menu items:', menuCount);
    console.log('  - Collapsible menus:', treeviewCount);
    console.log('  - Active submenu items:', activeCount);
    console.log('  - jQuery version:', $.fn.jquery);
    console.log('  - AdminLTE available:', typeof AdminLTE !== 'undefined');
    console.log('  - Bootstrap dropdown available:', typeof $.fn.dropdown !== 'undefined');

    // Test click handler
    console.log('🧪 Testing click handlers...');
    var hasHandlers = $('.nav-sidebar .nav-item > .nav-link').length;
    console.log('  - Elements with handlers:', hasHandlers);

    // Add keyboard shortcuts (optional)
    $(document).on('keydown', function(e) {
        // Ctrl + Shift + C = Collapse all menus
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            collapseAllMenus();
            console.log('⌨️ Keyboard shortcut: Collapse all menus');
        }

        // Ctrl + Shift + E = Expand all menus
        if (e.ctrlKey && e.shiftKey && e.keyCode === 69) {
            e.preventDefault();
            expandAllMenus();
            console.log('⌨️ Keyboard shortcut: Expand all menus');
        }
    });

    console.log('⌨️ Keyboard shortcuts enabled:');
    console.log('  - Ctrl+Shift+C: Collapse all menus');
    console.log('  - Ctrl+Shift+E: Expand all menus');
}

// Manual trigger function for testing
function testMenuToggle(menuText) {
    console.log('🧪 Manual test for menu:', menuText);
    $('.nav-sidebar .nav-item > .nav-link').each(function() {
        if ($(this).find('p').first().text().trim().includes(menuText)) {
            $(this).trigger('click');
            return false;
        }
    });
}

// Collapse all menus
function collapseAllMenus() {
    console.log('📤 Collapsing all menus...');
    $('.nav-sidebar .nav-item.menu-open').each(function() {
        var $parent = $(this);
        var $treeview = $parent.find('.nav-treeview');
        var menuText = $parent.find('> .nav-link p').first().text().trim();

        console.log('📤 Closing menu:', menuText);
        $parent.removeClass('menu-open');
        $treeview.slideUp(200);
    });
}

// Expand all menus
function expandAllMenus() {
    console.log('📥 Expanding all menus...');
    $('.nav-sidebar .nav-item').each(function() {
        var $parent = $(this);
        var $treeview = $parent.find('.nav-treeview');

        if ($treeview.length > 0 && !$parent.hasClass('menu-open')) {
            var menuText = $parent.find('> .nav-link p').first().text().trim();
            console.log('📥 Opening menu:', menuText);
            $parent.addClass('menu-open');
            $treeview.slideDown(200);
        }
    });
}

// Add to window for debugging and manual control
window.testMenuToggle = testMenuToggle;
window.initializeCollapsibleMenu = initializeCollapsibleMenu;
window.collapseAllMenus = collapseAllMenus;
window.expandAllMenus = expandAllMenus;

// Handle CSRF token refresh and 419 errors
$(document).ready(function() {
    // Setup AJAX to include CSRF token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Refresh CSRF token every 10 minutes
    setInterval(function() {
        $.get('/csrf-token', function(data) {
            if (data.token) {
                $('meta[name="csrf-token"]').attr('content', data.token);
                $('input[name="_token"]').val(data.token);
                // Update AJAX setup
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': data.token
                    }
                });
            }
        }).fail(function() {
            console.log('CSRF token refresh failed');
        });
    }, 10 * 60 * 1000); // 10 minutes

    // Handle 419 errors globally
    $(document).ajaxError(function(event, xhr, settings) {
        if (xhr.status === 419) {
            toastr.error('Phiên làm việc đã hết hạn. Trang sẽ được tải lại.');
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        }
    });

    // Handle form submissions with fresh CSRF token
    $('form').on('submit', function(e) {
        var form = $(this);
        var tokenInput = form.find('input[name="_token"]');

        if (tokenInput.length > 0) {
            // Get fresh token before submit
            $.get('/csrf-token', function(data) {
                if (data.token) {
                    tokenInput.val(data.token);
                }
            });
        }
    });
});
</script>

@stack('scripts')
</body>
</html>
