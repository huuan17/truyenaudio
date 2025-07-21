<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Crawl - {{ $story->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Smart Crawl: {{ $story->title }}</h1>
                    <a href="{{ route('admin.stories.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <!-- Story Info Card -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Thông tin crawl
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Trạng thái hiện tại:</strong></td>
                                                <td>
                                                    @php
                                                        $statusColors = config('constants.CRAWL_STATUS.COLORS');
                                                        $statusColor = $statusColors[$story->crawl_status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $statusColor }}">{{ $status_label }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phạm vi chương:</strong></td>
                                                <td>{{ $story->start_chapter }} - {{ $story->end_chapter }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tổng số chương:</strong></td>
                                                <td>{{ $total_chapters }} chương</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Đã crawl:</strong></td>
                                                <td>
                                                    <span class="text-success">{{ $existing_count }} chương</span>
                                                    ({{ round(($existing_count / $total_chapters) * 100, 1) }}%)
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Chưa crawl:</strong></td>
                                                <td>
                                                    <span class="text-danger">{{ $missing_count }} chương</span>
                                                    ({{ round(($missing_count / $total_chapters) * 100, 1) }}%)
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Jobs đang chờ:</strong></td>
                                                <td>
                                                    @if($pending_jobs > 0)
                                                        <span class="badge bg-warning">{{ $pending_jobs }} job(s)</span>
                                                    @else
                                                        <span class="badge bg-success">0 job</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Progress Bar -->
                                        <div class="mb-3">
                                            <label class="form-label">Tiến độ crawl:</label>
                                            @php $progress = round(($existing_count / $total_chapters) * 100, 1); @endphp
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $progress }}%" 
                                                     aria-valuenow="{{ $progress }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ $progress }}%
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Missing Chapters Preview -->
                                        @if($missing_count > 0)
                                            <div class="mb-3">
                                                <label class="form-label">Chương thiếu ({{ $missing_count > 10 ? '10 đầu tiên' : 'tất cả' }}):</label>
                                                <div class="border p-2 rounded bg-light" style="max-height: 100px; overflow-y: auto;">
                                                    @foreach(array_slice($missing_chapters, 0, 10) as $chapter)
                                                        <span class="badge bg-outline-danger me-1 mb-1">{{ $chapter }}</span>
                                                    @endforeach
                                                    @if($missing_count > 10)
                                                        <span class="text-muted">... và {{ $missing_count - 10 }} chương khác</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Card -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs"></i> Hành động
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($story->crawl_status == config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                                    <div class="alert alert-info">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        Truyện đang được crawl. Vui lòng đợi hoặc hủy crawl hiện tại.
                                    </div>
                                    
                                    <form action="{{ route('admin.stories.cancel-crawl', $story) }}" method="POST" class="mb-2">
                                        @csrf
                                        <button type="submit" class="btn btn-warning w-100" 
                                                onclick="return confirm('Bạn có chắc muốn hủy crawl?')">
                                            <i class="fas fa-stop"></i> Hủy crawl
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('admin.stories.remove-from-queue', $story) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-danger w-100" 
                                                onclick="return confirm('Bạn có chắc muốn xóa khỏi queue?')">
                                            <i class="fas fa-times"></i> Xóa khỏi queue
                                        </button>
                                    </form>
                                    
                                @elseif($missing_count > 0)
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Có {{ $missing_count }} chương chưa được crawl.
                                    </div>
                                    
                                    <form action="{{ route('test.smart-crawl.post', $story) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100"
                                                onclick="return confirm('Bạn có chắc muốn crawl {{ $missing_count }} chương thiếu?')">
                                            <i class="fas fa-download"></i> Crawl {{ $missing_count }} chương thiếu
                                        </button>
                                    </form>
                                    
                                @else
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i>
                                        Tất cả các chương đã được crawl hoàn tất!
                                    </div>
                                    
                                    <form action="{{ route('test.smart-crawl.post', $story) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success w-100"
                                                onclick="return confirm('Bạn có chắc muốn crawl lại tất cả các chương?')">
                                            <i class="fas fa-redo"></i> Crawl lại tất cả
                                        </button>
                                    </form>
                                @endif

                                <hr>
                                
                                <!-- Quick Actions -->
                                <div class="d-grid gap-2">
                                    <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Xem chi tiết truyện
                                    </a>
                                    <a href="{{ route('admin.stories.edit', $story) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Chỉnh sửa truyện
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auto-refresh notice -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Lưu ý:</strong> Trang này sẽ tự động cập nhật mỗi 10 giây để hiển thị tiến độ crawl mới nhất.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-refresh every 10 seconds
    setInterval(function() {
        location.reload();
    }, 10000);

    // Show loading when form is submitted
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            if (button) {
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                button.disabled = true;
            }
        });
    });
    </script>
</body>
</html>
