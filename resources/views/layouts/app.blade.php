<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Quản lý truyện | Admin</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">

  <!-- AdminLTE -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3/dist/css/adminlte.min.css">

  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
      <!-- User Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="fas fa-user"></i>
          <span class="ml-1">{{ auth()->user()->name }}</span>
          <i class="fas fa-caret-down ml-1"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <div class="dropdown-header">
            <strong>{{ auth()->user()->name }}</strong>
            <br>
            <small class="text-muted">{{ auth()->user()->email }}</small>
            <br>
            {!! auth()->user()->role_badge !!}
          </div>
          <div class="dropdown-divider"></div>
          <a href="{{ route('admin.users.show', auth()->user()) }}" class="dropdown-item">
            <i class="fas fa-user mr-2"></i> Thông tin cá nhân
          </a>
          <div class="dropdown-divider"></div>
          <form action="{{ route('logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="dropdown-item text-danger">
              <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
            </button>
          </form>
        </div>
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
    <a href="#" class="brand-link">
      <span class="brand-text font-weight-light ml-2">Quản lý Truyện</span>
    </a>
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
          <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-header">QUẢN LÝ NỘI DUNG</li>
          <li class="nav-item">
            <a href="{{ route('admin.stories.index') }}" class="nav-link {{ request()->routeIs('admin.stories.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-book"></i>
              <p>Truyện</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('admin.genres.index') }}" class="nav-link {{ request()->routeIs('admin.genres.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tags"></i>
              <p>Thể loại</p>
            </a>
          </li>

          <li class="nav-header">SOCIAL MEDIA</li>
          <li class="nav-item">
            <a href="{{ route('admin.channels.index') }}" class="nav-link {{ request()->routeIs('admin.channels.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-broadcast-tower"></i>
              <p>Quản Lý Kênh</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('admin.scheduled-posts.index') }}" class="nav-link {{ request()->routeIs('admin.scheduled-posts.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Lịch Đăng Video</p>
            </a>
          </li>

          <li class="nav-header">VIDEO GENERATOR</li>
          <li class="nav-item">
            <a href="{{ route('admin.logos.index') }}" class="nav-link {{ request()->routeIs('admin.logos.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-image"></i>
              <p>Quản Lý Logo</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('admin.tiktok.index') }}" class="nav-link {{ request()->routeIs('admin.tiktok.*') ? 'active' : '' }}">
              <i class="nav-icon fab fa-tiktok"></i>
              <p>TikTok Video</p>
            </a>
          </li>

          @if(auth()->user()->isAdmin())
          <li class="nav-header">QUẢN TRỊ HỆ THỐNG</li>
          <li class="nav-item">
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-users"></i>
              <p>Quản lý Users</p>
            </a>
          </li>
          @endif
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
</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3/dist/js/adminlte.min.js"></script>

<!-- Page specific scripts -->
@yield('scripts')

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Chọn thể loại",
            allowClear: true
        });
    });
</script>

@stack('scripts')
</body>
</html>
