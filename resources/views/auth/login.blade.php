<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập - Audio Lara</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h1><b>Audio</b>Lara</h1>
            <p class="login-box-msg">Đăng nhập để quản lý hệ thống</p>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="post" id="loginForm">
                @csrf
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                           placeholder="Email" value="{{ old('email') }}" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                           placeholder="Mật khẩu" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">
                                Ghi nhớ đăng nhập
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>

            <div class="mt-4">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Tài khoản demo:</h6>
                    <p class="mb-1"><strong>Admin:</strong> admin@audiolara.com / admin123</p>
                    <p class="mb-0"><strong>User:</strong> user@audiolara.com / user123</p>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Refresh CSRF token every 10 minutes
    setInterval(function() {
        $.get('{{ route("csrf.token") }}', function(data) {
            if (data.token) {
                $('input[name="_token"]').val(data.token);
                $('meta[name="csrf-token"]').attr('content', data.token);
            }
        }).fail(function() {
            // If CSRF refresh fails, reload the page
            console.log('CSRF token refresh failed, reloading page...');
            window.location.reload();
        });
    }, 10 * 60 * 1000); // 10 minutes

    // Handle form submission
    $('#loginForm').on('submit', function(e) {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...');

        // Get fresh CSRF token before submit
        $.get('{{ route("csrf.token") }}', function(data) {
            if (data.token) {
                $('input[name="_token"]').val(data.token);
                $('meta[name="csrf-token"]').attr('content', data.token);
            }
        });

        // Re-enable button after 10 seconds to prevent permanent disable
        setTimeout(function() {
            submitBtn.prop('disabled', false).html('Đăng nhập');
        }, 10000);
    });

    // Auto-focus on email field
    $('input[name="email"]').focus();
});
</script>
</body>
</html>
