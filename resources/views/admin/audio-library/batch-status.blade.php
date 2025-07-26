@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Thư viện Audio', 'url' => route('admin.audio-library.index')],
        ['title' => 'Trạng thái Upload Batch']
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-upload mr-2"></i>Trạng thái Upload Batch #{{ $batch->id }}
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshStatus()">
                            <i class="fas fa-sync-alt mr-1"></i>Refresh
                        </button>
                        @if($batch->isCompleted())
                            <a href="{{ route('admin.audio-library.index') }}" class="btn btn-sm btn-success ml-1">
                                <i class="fas fa-check mr-1"></i>Xem thư viện
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Batch Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary" id="total-files">{{ $batch->total_files }}</h4>
                                <small class="text-muted">Tổng files</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success" id="completed-files">{{ $batch->completed_files }}</h4>
                                <small class="text-muted">Hoàn thành</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info" id="processing-files">{{ $batch->processing_files }}</h4>
                                <small class="text-muted">Đang xử lý</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-danger" id="failed-files">{{ $batch->failed_files }}</h4>
                                <small class="text-muted">Thất bại</small>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="font-weight-bold">Tiến trình:</span>
                            <span id="progress-text">{{ $batch->progress_percentage }}%</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 id="progress-bar"
                                 role="progressbar" 
                                 style="width: {{ $batch->progress_percentage }}%"
                                 aria-valuenow="{{ $batch->progress_percentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="mb-3">
                        <strong>Trạng thái:</strong> 
                        <span id="status-badge">{!! $batch->status_badge !!}</span>
                    </div>

                    <!-- Summary -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span id="summary-text">{{ $batch->summary }}</span>
                    </div>

                    @if(!$batch->isCompleted())
                    <!-- Estimated Time -->
                    <div class="alert alert-light">
                        <i class="fas fa-clock mr-2"></i>
                        <strong>Thời gian ước tính:</strong>
                        <span id="estimated-time">Đang tính toán...</span>
                    </div>
                    @endif

                    <!-- Files List -->
                    <div class="mt-4">
                        <h6>Chi tiết files:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="35%">Tên file</th>
                                        <th width="15%">Trạng thái</th>
                                        <th width="35%">Thông báo</th>
                                        <th width="10%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="files-table">
                                    @foreach($batch->files as $index => $file)
                                    <tr data-file-index="{{ $index }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $file['title'] ?? $file['original_name'] }}</strong><br>
                                            <small class="text-muted">{{ $file['original_name'] }}</small>
                                        </td>
                                        <td>
                                            <span class="file-status">
                                                @if($file['status'] === 'pending')
                                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Chờ xử lý</span>
                                                @elseif($file['status'] === 'processing')
                                                    <span class="badge badge-info"><i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý</span>
                                                @elseif($file['status'] === 'completed')
                                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Hoàn thành</span>
                                                @elseif($file['status'] === 'failed')
                                                    <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Thất bại</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="file-message">{{ $file['message'] ?? '' }}</td>
                                        <td>
                                            @if($file['status'] === 'completed' && isset($file['audio_id']))
                                                <a href="{{ route('admin.audio-library.show', $file['audio_id']) }}" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin Batch</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Batch ID:</strong></td>
                            <td>#{{ $batch->id }}</td>
                        </tr>
                        <tr>
                            <td><strong>Người upload:</strong></td>
                            <td>{{ $batch->user->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Bắt đầu:</strong></td>
                            <td>{{ $batch->started_at ? $batch->started_at->format('d/m/Y H:i:s') : 'Chưa bắt đầu' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Hoàn thành:</strong></td>
                            <td id="completed-at">{{ $batch->completed_at ? $batch->completed_at->format('d/m/Y H:i:s') : 'Chưa hoàn thành' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Danh mục:</strong></td>
                            <td>{{ $batch->settings['category'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Ngôn ngữ:</strong></td>
                            <td>{{ $batch->settings['language'] ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($batch->isCompleted())
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-download mr-2"></i>Thao tác</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.audio-library.index') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-list mr-2"></i>Xem thư viện Audio
                    </a>
                    <a href="{{ route('admin.audio-library.batch-list') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-history mr-2"></i>Lịch sử Upload
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let batchId = {{ $batch->id }};
let refreshInterval;

// Auto refresh if batch is not completed
@if(!$batch->isCompleted())
    refreshInterval = setInterval(refreshStatus, 3000); // Refresh every 3 seconds
@endif

function refreshStatus() {
    fetch(`/admin/api/audio-library/batch-status/${batchId}`)
        .then(response => response.json())
        .then(data => {
            updateBatchStatus(data);
            
            // Stop auto refresh if completed
            if (data.is_completed && refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        })
        .catch(error => {
            console.error('Error refreshing status:', error);
        });
}

function updateBatchStatus(data) {
    // Update counters
    document.getElementById('total-files').textContent = data.total_files;
    document.getElementById('completed-files').textContent = data.completed_files;
    document.getElementById('processing-files').textContent = data.processing_files;
    document.getElementById('failed-files').textContent = data.failed_files;
    
    // Update progress bar
    document.getElementById('progress-text').textContent = data.progress + '%';
    const progressBar = document.getElementById('progress-bar');
    progressBar.style.width = data.progress + '%';
    progressBar.setAttribute('aria-valuenow', data.progress);
    
    // Update status badge
    const statusBadges = {
        'pending': '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Chờ xử lý</span>',
        'processing': '<span class="badge badge-info"><i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý</span>',
        'completed': '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Hoàn thành</span>',
        'completed_with_errors': '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Hoàn thành có lỗi</span>',
        'failed': '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Thất bại</span>'
    };
    document.getElementById('status-badge').innerHTML = statusBadges[data.status] || data.status;
    
    // Update summary
    document.getElementById('summary-text').textContent = data.summary;

    // Update estimated time
    updateEstimatedTime(data);

    // Update files table
    updateFilesTable(data.files);

    // Update completed time if completed
    if (data.is_completed) {
        document.getElementById('completed-at').textContent = data.updated_at;
    }
}

function updateEstimatedTime(data) {
    const estimatedTimeElement = document.getElementById('estimated-time');
    if (!estimatedTimeElement || data.is_completed) return;

    const remainingFiles = data.processing_files + (data.total_files - data.completed_files - data.failed_files - data.processing_files);
    if (remainingFiles <= 0) {
        estimatedTimeElement.textContent = 'Sắp hoàn thành...';
        return;
    }

    // Estimate 30 seconds per file (conservative estimate)
    const estimatedSeconds = remainingFiles * 30;
    const minutes = Math.floor(estimatedSeconds / 60);
    const seconds = estimatedSeconds % 60;

    if (minutes > 0) {
        estimatedTimeElement.textContent = `Khoảng ${minutes} phút ${seconds} giây`;
    } else {
        estimatedTimeElement.textContent = `Khoảng ${seconds} giây`;
    }
}

function updateFilesTable(files) {
    const tbody = document.getElementById('files-table');

    files.forEach((file, index) => {
        const row = tbody.querySelector(`tr[data-file-index="${index}"]`);
        if (row) {
            const statusCell = row.querySelector('.file-status');
            const messageCell = row.querySelector('.file-message');

            // Update status
            let statusBadge = '';
            switch(file.status) {
                case 'pending':
                    statusBadge = '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Chờ xử lý</span>';
                    break;
                case 'processing':
                    statusBadge = '<span class="badge badge-info"><i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý</span>';
                    break;
                case 'completed':
                    statusBadge = '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Hoàn thành</span>';
                    break;
                case 'failed':
                    statusBadge = '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Thất bại</span>';
                    break;
            }
            statusCell.innerHTML = statusBadge;

            // Update message
            messageCell.textContent = file.message || '';
        }
    });
}

// Cleanup interval on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
@endpush
