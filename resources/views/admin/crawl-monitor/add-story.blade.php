@extends('layouts.admin')

@section('title', 'Thêm Truyện Vào Queue Crawl')

@section('content')
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-plus-circle text-primary"></i>
                        Thêm Truyện Vào Queue Crawl
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.crawl-monitor.index') }}">Crawl Monitor</a></li>
                        <li class="breadcrumb-item active">Thêm Truyện</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">


            <div class="row">
                <!-- Add Story Form -->
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book"></i>
                                Chọn Truyện Để Crawl
                            </h3>
                        </div>
                        
                        <form id="addStoryForm">
                            @csrf
                            <div class="card-body">
                                <!-- Story Selection -->
                                <div class="form-group">
                                    <label for="story_id">
                                        <i class="fas fa-book"></i>
                                        Chọn Truyện <span class="text-danger">*</span>
                                    </label>
                                    <select name="story_id" id="story_id" class="form-control select2" required>
                                        <option value="">-- Chọn truyện --</option>
                                        @foreach($availableStories as $story)
                                            <option value="{{ $story->id }}" 
                                                    data-start="{{ $story->start_chapter }}" 
                                                    data-end="{{ $story->end_chapter }}"
                                                    data-status="{{ $story->crawl_status }}">
                                                {{ $story->title }} 
                                                ({{ config('constants.CRAWL_STATUS.LABELS')[$story->crawl_status] ?? 'Unknown' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Chỉ hiển thị các truyện không đang crawl
                                    </small>
                                </div>

                                <!-- Story Info Display -->
                                <div id="storyInfo" class="alert alert-info" style="display: none;">
                                    <h6><i class="fas fa-info-circle"></i> Thông tin truyện:</h6>
                                    <div id="storyDetails"></div>
                                </div>

                                <!-- Chapter Range -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_chapter">
                                                <i class="fas fa-play"></i>
                                                Chapter Bắt Đầu
                                            </label>
                                            <input type="number" name="start_chapter" id="start_chapter" 
                                                   class="form-control" min="1" placeholder="Để trống = giữ nguyên">
                                            <small class="form-text text-muted">
                                                Để trống để sử dụng giá trị hiện tại
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_chapter">
                                                <i class="fas fa-stop"></i>
                                                Chapter Kết Thúc
                                            </label>
                                            <input type="number" name="end_chapter" id="end_chapter" 
                                                   class="form-control" min="1" placeholder="Để trống = giữ nguyên">
                                            <small class="form-text text-muted">
                                                Để trống để sử dụng giá trị hiện tại
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scheduling Options -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="delay_minutes">
                                                <i class="fas fa-clock"></i>
                                                Delay (phút)
                                            </label>
                                            <input type="number" name="delay_minutes" id="delay_minutes" 
                                                   class="form-control" min="0" max="1440" value="0">
                                            <small class="form-text text-muted">
                                                0 = chạy ngay (hoặc theo optimal delay)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="priority">
                                                <i class="fas fa-flag"></i>
                                                Độ Ưu Tiên
                                            </label>
                                            <select name="priority" id="priority" class="form-control">
                                                <option value="normal">Normal</option>
                                                <option value="high">High Priority</option>
                                                <option value="low">Low Priority</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                High priority sẽ được xử lý trước
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-bolt"></i>
                                        Quick Actions
                                    </label>
                                    <div class="btn-group btn-group-sm d-block">
                                        <button type="button" class="btn btn-outline-primary" onclick="setQuickCrawl()">
                                            <i class="fas fa-rocket"></i> Crawl Ngay
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="setDelayedCrawl()">
                                            <i class="fas fa-clock"></i> Crawl Sau 30 Phút
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="setHighPriority()">
                                            <i class="fas fa-flag"></i> High Priority
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Thêm Vào Queue
                                </button>
                                <a href="{{ route('admin.crawl-monitor.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Quay Lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Queue Status -->
                <div class="col-md-4">
                    <!-- Current Queue -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i>
                                Truyện Trong Queue
                            </h3>
                        </div>
                        <div class="card-body">
                            @if(empty($queuedStories))
                                <div class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Không có truyện nào trong queue</p>
                                </div>
                            @else
                                <div class="list-group list-group-flush">
                                    @foreach($queuedStories as $item)
                                        <div class="list-group-item p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ $item['story']->title }}</h6>
                                                    <small class="text-muted">
                                                        Queue: 
                                                        @if($item['queue'] === 'crawl-high')
                                                            <span class="badge badge-danger">High</span>
                                                        @elseif($item['queue'] === 'crawl-low')
                                                            <span class="badge badge-secondary">Low</span>
                                                        @else
                                                            <span class="badge badge-primary">Normal</span>
                                                        @endif
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        Tạo: {{ \Carbon\Carbon::parse($item['created_at'])->format('H:i d/m') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i>
                                Thống Kê Nhanh
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="info-box bg-primary">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Có Thể Crawl</span>
                                            <span class="info-box-number">{{ $availableStories->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-box bg-warning">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Trong Queue</span>
                                            <span class="info-box-number">{{ count($queuedStories) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Worker Status Alert (Bottom) -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="alert alert-info alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-info-circle"></i> Hướng dẫn: Queue Worker</h5>
                        <p><strong>Trước khi thêm truyện, đảm bảo Queue Worker đang chạy:</strong></p>
                        <div class="row">
                            <div class="col-md-6">
                                <ol class="mb-2">
                                    <li>Mở Command Prompt</li>
                                    <li>Chạy: <code>start-queue-worker.bat</code></li>
                                    <li>Chọn option <strong>2</strong> (Crawl queue only)</li>
                                    <li><strong>Giữ cửa sổ mở</strong> - Đừng đóng!</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>Hoặc chạy command:</strong><br>
                                    <code class="d-block">php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30</code>
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-success alert-sm mb-0">
                                    <i class="fas fa-brain"></i>
                                    <strong>Smart Crawl:</strong> Hệ thống sẽ tự động quét chapters hiện có và chỉ crawl những chương còn thiếu.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning alert-sm mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Lưu ý:</strong> Chỉ thêm một truyện mỗi lần và đợi hoàn thành trước khi thêm truyện tiếp theo.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: '-- Chọn truyện --',
        allowClear: true
    });

    // Story selection change handler
    $('#story_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            const startChapter = selectedOption.data('start');
            const endChapter = selectedOption.data('end');
            const status = selectedOption.data('status');
            
            // Update chapter inputs with current values
            $('#start_chapter').attr('placeholder', `Hiện tại: ${startChapter}`);
            $('#end_chapter').attr('placeholder', `Hiện tại: ${endChapter}`);
            
            // Show story info
            const statusLabels = @json(config('constants.CRAWL_STATUS.LABELS'));
            const statusLabel = statusLabels[status] || 'Unknown';
            
            $('#storyDetails').html(`
                <strong>Trạng thái:</strong> ${statusLabel}<br>
                <strong>Chapter range:</strong> ${startChapter} - ${endChapter} (${endChapter - startChapter + 1} chapters)
            `);
            $('#storyInfo').show();
        } else {
            $('#storyInfo').hide();
            $('#start_chapter, #end_chapter').attr('placeholder', '');
        }
    });

    // Form submission
    $('#addStoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: '{{ route("admin.crawl-monitor.add-story.post") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast.success(response.message);
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.crawl-monitor.index") }}';
                    }, 2000);
                } else {
                    showToast.error(response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.message) {
                    showToast.error(response.message);
                } else {
                    showToast.error('Có lỗi xảy ra khi thêm truyện vào queue');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-plus"></i> Thêm Vào Queue');
            }
        });
    });
});

// Quick action functions
function setQuickCrawl() {
    $('#delay_minutes').val(0);
    $('#priority').val('normal');
    showToast.info('Đã thiết lập crawl ngay lập tức');
}

function setDelayedCrawl() {
    $('#delay_minutes').val(30);
    $('#priority').val('normal');
    showToast.info('Đã thiết lập crawl sau 30 phút');
}

function setHighPriority() {
    $('#delay_minutes').val(0);
    $('#priority').val('high');
    showToast.info('Đã thiết lập high priority');
}
</script>
@endpush
