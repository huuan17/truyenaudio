@extends('layouts.app')

@section('title', 'Hướng dẫn Queue Workers')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>🚀 Hướng dẫn Queue Workers</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Queue Workers</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Queue Status Check -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">📊 Kiểm tra trạng thái Queue Workers</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-cogs"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tất cả Queues</span>
                                        <span class="info-box-number" id="all-queue-status">Đang kiểm tra...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-spider"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Crawl Queue</span>
                                        <span class="info-box-number" id="crawl-queue-status">Đang kiểm tra...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-video"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Video Queue</span>
                                        <span class="info-box-number" id="video-queue-status">Đang kiểm tra...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Nếu hiển thị "Stopped" hoặc "Không hoạt động", bạn cần khởi động queue workers theo hướng dẫn bên dưới.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Start Guide -->
        <div class="row">
            <div class="col-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">⚡ Khởi động nhanh</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-windows"></i> Windows</h5>
                                <div class="bg-dark p-3 rounded">
                                    <code class="text-light">
                                        # Mở Command Prompt tại thư mục dự án<br>
                                        start-queue-worker.bat
                                    </code>
                                </div>
                                <p class="mt-2 text-muted">Chọn option phù hợp khi được hỏi</p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-linux"></i> Linux/Mac</h5>
                                <div class="bg-dark p-3 rounded">
                                    <code class="text-light">
                                        # Chạy tất cả queues<br>
                                        php artisan queue:work --timeout=3600
                                    </code>
                                </div>
                                <p class="mt-2 text-muted">Hoặc chạy queue cụ thể (xem bên dưới)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Commands -->
        <div class="row">
            <div class="col-md-4">
                <div class="card card-info">
                    <div class="card-header">
                        <!-- Info: Default/Publishing Queue (ScheduledPost/YouTube) -->
                        <div class="alert alert-info">
                            <h5 class="mb-1">📤 Publishing Queue (Default)</h5>
                            <p class="mb-2">
                                Các job đăng video lên kênh (ScheduledPost → YouTube/TikTok) chạy trên <strong>default queue</strong>.
                                Hãy chạy worker mặc định song song với các worker khác:
                            </p>
                            <div class="bg-dark p-2 rounded mb-2"><code class="text-light small">php artisan queue:work --tries=3 --timeout=600 --sleep=5</code></div>
                            <p class="mb-2">Hoặc chỉ định rõ queue mặc định:</p>
                            <div class="bg-dark p-2 rounded mb-2"><code class="text-light small">php artisan queue:work --queue=default --tries=3 --timeout=600 --sleep=5</code></div>
                            <p class="mb-0">Xử lý thủ công các bài pending (nếu cần):
                                <code class="text-monospace">php artisan posts:process-scheduled --limit=10</code>
                            </p>
                        </div>
                        <h3 class="card-title">🕷️ Crawl Queue</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Mục đích:</strong> Xử lý crawl truyện từ các website</p>
                        <p><strong>Thời gian:</strong> Có thể mất vài giờ cho truyện dài</p>
                        
                        <h6>Command:</h6>
                        <div class="bg-dark p-2 rounded mb-2">
                            <code class="text-light small">
                                php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
                            </code>
                        </div>
                        
                        <h6>Hoặc sử dụng file batch:</h6>
                        <div class="bg-dark p-2 rounded">
                            <code class="text-light small">
                                start-crawl-queue-worker.bat
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">🎬 Video Queue</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Mục đích:</strong> Tạo video TikTok/YouTube</p>
                        <p><strong>Thời gian:</strong> 5-30 phút mỗi video</p>
                        
                        <h6>Command:</h6>
                        <div class="bg-dark p-2 rounded mb-2">
                            <code class="text-light small">
                                php artisan queue:work --queue=video --timeout=1800 --memory=512 --tries=3
                            </code>
                        </div>
                        
                        <h6>Batch file option:</h6>
                        <div class="bg-dark p-2 rounded">
                            <code class="text-light small">
                                start-queue-worker.bat<br>
                                # Chọn option 4
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">🔊 TTS Queue</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Mục đích:</strong> Chuyển đổi text thành speech</p>
                        <p><strong>Thời gian:</strong> 1-5 phút mỗi chapter</p>
                        
                        <h6>Command:</h6>
                        <div class="bg-dark p-2 rounded mb-2">
                            <code class="text-light small">
                                php artisan queue:work --queue=tts --timeout=300 --memory=256 --tries=3
                            </code>
                        </div>
                        
                        <h6>Hoặc chạy default queue:</h6>
                        <div class="bg-dark p-2 rounded">
                            <code class="text-light small">
                                php artisan queue:work --queue=default
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Queues -->
        <div class="row">
            <div class="col-12">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">⚡ Chạy tất cả Queues cùng lúc</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Command đơn giản:</h6>
                                <div class="bg-dark p-3 rounded">
                                    <code class="text-light">
                                        php artisan queue:work --timeout=3600 --memory=512 --tries=3 --sleep=3
                                    </code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Sử dụng batch file:</h6>
                                <div class="bg-dark p-3 rounded">
                                    <code class="text-light">
                                        start-queue-worker.bat<br>
                                        # Chọn option 1 (All queues)
                                    </code>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Khuyến nghị:</strong> Chạy từng queue riêng biệt để dễ quản lý và monitor.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parameters Explanation -->
        <div class="row">
            <div class="col-12">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">📋 Giải thích các tham số</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Tham số</th>
                                        <th>Ý nghĩa</th>
                                        <th>Crawl</th>
                                        <th>Video</th>
                                        <th>TTS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>--queue</code></td>
                                        <td>Tên queue cần xử lý</td>
                                        <td>crawl</td>
                                        <td>video</td>
                                        <td>tts/default</td>
                                    </tr>
                                    <tr>
                                        <td><code>--timeout</code></td>
                                        <td>Thời gian tối đa cho 1 job (giây)</td>
                                        <td>14400 (4h)</td>
                                        <td>1800 (30m)</td>
                                        <td>300 (5m)</td>
                                    </tr>
                                    <tr>
                                        <td><code>--memory</code></td>
                                        <td>Giới hạn RAM (MB)</td>
                                        <td>1024</td>
                                        <td>512</td>
                                        <td>256</td>
                                    </tr>
                                    <tr>
                                        <td><code>--tries</code></td>
                                        <td>Số lần thử lại khi fail</td>
                                        <td>1</td>
                                        <td>3</td>
                                        <td>3</td>
                                    </tr>
                                    <tr>
                                        <td><code>--sleep</code></td>
                                        <td>Thời gian chờ giữa các job (giây)</td>
                                        <td>30</td>
                                        <td>3</td>
                                        <td>3</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <!-- Troubleshooting -->
        <div class="row">
            <div class="col-12">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">🔧 Troubleshooting - Xử lý sự cố</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>❌ Worker không chạy</h5>
                                <p><strong>Triệu chứng:</strong> Jobs không được xử lý, status "Stopped"</p>
                                <p><strong>Giải pháp:</strong></p>
                                <div class="bg-dark p-2 rounded mb-3">
                                    <code class="text-light small">
                                        # Restart worker<br>
                                        php artisan queue:restart<br>
                                        php artisan queue:work --queue=video
                                    </code>
                                </div>

                                <h5>⏳ Job bị stuck</h5>
                                <p><strong>Triệu chứng:</strong> Task ở trạng thái "processing" quá lâu</p>
                                <p><strong>Giải pháp:</strong></p>
                                <div class="bg-dark p-2 rounded">
                                    <code class="text-light small">
                                        # Clear failed jobs<br>
                                        php artisan queue:flush<br>
                                        # Restart worker<br>
                                        php artisan queue:restart
                                    </code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>💾 Memory issues</h5>
                                <p><strong>Triệu chứng:</strong> Worker bị kill do hết RAM</p>
                                <p><strong>Giải pháp:</strong></p>
                                <div class="bg-dark p-2 rounded mb-3">
                                    <code class="text-light small">
                                        # Tăng memory limit<br>
                                        php artisan queue:work --memory=1024
                                    </code>
                                </div>

                                <h5>🗄️ Database issues</h5>
                                <p><strong>Triệu chứng:</strong> Lỗi "Table not found"</p>
                                <p><strong>Giải pháp:</strong></p>
                                <div class="bg-dark p-2 rounded">
                                    <code class="text-light small">
                                        # Tạo lại tables<br>
                                        php artisan migrate<br>
                                        # Hoặc truy cập:<br>
                                        /admin/create-queue-tables
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitoring Commands -->
        <div class="row">
            <div class="col-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">📊 Commands để Monitor</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Kiểm tra trạng thái:</h6>
                                <div class="bg-dark p-2 rounded mb-2">
                                    <code class="text-light small">php artisan queue:monitor</code>
                                </div>
                                <button class="btn btn-sm btn-info queue-command mb-2" data-command="monitor">
                                    <i class="fas fa-eye"></i> Monitor
                                </button>

                                <h6>Xem failed jobs:</h6>
                                <div class="bg-dark p-2 rounded mb-2">
                                    <code class="text-light small">php artisan queue:failed</code>
                                </div>
                                <button class="btn btn-sm btn-warning queue-command" data-command="failed">
                                    <i class="fas fa-exclamation-triangle"></i> View Failed
                                </button>
                            </div>
                            <div class="col-md-4">
                                <h6>Retry failed jobs:</h6>
                                <div class="bg-dark p-2 rounded mb-2">
                                    <code class="text-light small">php artisan queue:retry all</code>
                                </div>
                                <button class="btn btn-sm btn-success queue-command mb-2" data-command="retry">
                                    <i class="fas fa-redo"></i> Retry All
                                </button>

                                <h6>Clear all jobs:</h6>
                                <div class="bg-dark p-2 rounded mb-2">
                                    <code class="text-light small">php artisan queue:flush</code>
                                </div>
                                <button class="btn btn-sm btn-danger queue-command" data-command="flush">
                                    <i class="fas fa-trash"></i> Clear All
                                </button>
                            </div>
                            <div class="col-md-4">
                                <h6>Restart workers:</h6>
                                <div class="bg-dark p-2 rounded mb-2">
                                    <code class="text-light small">php artisan queue:restart</code>
                                </div>
                                <button class="btn btn-sm btn-primary queue-command mb-2" data-command="restart">
                                    <i class="fas fa-sync"></i> Restart Workers
                                </button>

                                <h6>Clear failed jobs:</h6>
                                <div class="bg-dark p-2 rounded mb-2">
                                    <code class="text-light small">php artisan queue:forget-failed</code>
                                </div>
                                <button class="btn btn-sm btn-secondary queue-command" data-command="forget-failed">
                                    <i class="fas fa-eraser"></i> Forget Failed
                                </button>
                            </div>
                        </div>

                        <!-- Command Output -->
                        <div class="row mt-3" id="command-output-container" style="display: none;">
                            <div class="col-12">
                                <h6>Command Output:</h6>
                                <div class="bg-dark p-3 rounded">
                                    <pre id="command-output" class="text-light mb-0"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">🆘 Emergency Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Queue Tables Missing?</h6>
                                    <p>Nếu gặp lỗi "Table not found", click nút bên dưới để tạo tables:</p>
                                    <button class="btn btn-warning" id="create-queue-tables">
                                        <i class="fas fa-database"></i> Create Queue Tables
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-fire"></i> Emergency Reset</h6>
                                    <p>Khi mọi thứ bị stuck, thực hiện theo thứ tự:</p>
                                    <ol>
                                        <li>Restart Workers</li>
                                        <li>Clear All Jobs</li>
                                        <li>Forget Failed Jobs</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Practices -->
        <div class="row">
            <div class="col-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">✅ Best Practices</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>🔄 Development:</h6>
                                <ul>
                                    <li>Luôn monitor queue worker status</li>
                                    <li>Restart worker khi deploy code mới</li>
                                    <li>Test với small batch trước</li>
                                    <li>Cleanup test data thường xuyên</li>
                                    <li>Monitor memory usage</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>🚀 Production:</h6>
                                <ul>
                                    <li>Setup monitoring alerts</li>
                                    <li>Regular cleanup old tasks</li>
                                    <li>Monitor disk space (temp files)</li>
                                    <li>Setup log rotation</li>
                                    <li>Backup queue data</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-12">
                <div class="card card-light">
                    <div class="card-header">
                        <h3 class="card-title">🔗 Liên kết nhanh</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="{{ route('admin.video-queue.index') }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-video"></i> Video Queue Dashboard
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('admin.crawl-monitor.index') }}" class="btn btn-success btn-block">
                                    <i class="fas fa-spider"></i> Crawl Monitor
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('admin.tts-monitor.index') }}" class="btn btn-warning btn-block">
                                    <i class="fas fa-volume-up"></i> TTS Monitor
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/clear-session" class="btn btn-secondary btn-block">
                                    <i class="fas fa-broom"></i> Clear Session
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Check queue status
    function checkQueueStatus() {
        $.ajax({
            url: '{{ route("admin.help.queue-status") }}',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#all-queue-status').text(data.all_queue);
                $('#crawl-queue-status').text(data.crawl_queue);
                $('#video-queue-status').text(data.video_queue);

                // Show error if any
                if (data.error) {
                    toastr.warning(data.error);
                }

                // Update stats if available
                if (data.stats) {
                    var stats = data.stats;
                    // Could update more detailed stats here
                }
            },
            error: function(xhr, status, error) {
                $('#all-queue-status').text('Error checking');
                $('#crawl-queue-status').text('Error checking');
                $('#video-queue-status').text('Error checking');
                toastr.error('Error checking queue status: ' + error);
            }
        });
    }

    // Execute queue command
    $('.queue-command').on('click', function(e) {
        e.preventDefault();
        var command = $(this).data('command');

        if (!confirm('Bạn có chắc muốn thực hiện lệnh: ' + command + '?')) {
            return;
        }

        $.ajax({
            url: '{{ route("admin.help.queue-command") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                command: command
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    toastr.success(data.message);
                    if (data.output) {
                        // Show command output
                        $('#command-output').text(data.output);
                        $('#command-output-container').show();
                    }
                    // Refresh status
                    checkQueueStatus();
                } else {
                    toastr.error(data.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error executing command: ' + error);
            }
        });
    });

    // Create queue tables
    $('#create-queue-tables').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Bạn có chắc muốn tạo queue tables?')) {
            return;
        }

        $.ajax({
            url: '{{ route("admin.help.create-queue-tables") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    toastr.success(data.message);
                    // Refresh status
                    checkQueueStatus();
                } else {
                    toastr.error(data.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error creating tables: ' + error);
            }
        });
    });

    // Initial check
    checkQueueStatus();

    // Auto refresh status every 30 seconds
    setInterval(checkQueueStatus, 30000);
});
</script>
@endsection
