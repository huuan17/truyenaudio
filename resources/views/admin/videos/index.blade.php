@extends('layouts.app')

@section('title', 'Quản lý Video')

@section('content')
<div class="container-fluid video-management-page">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Quản lý Video</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quản lý Video</h1>
            <p class="text-muted">Quản lý các video đã tạo từ hệ thống</p>
        </div>
        <div>
            <a href="{{ route('admin.video-generator.index') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo Video Mới
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.videos.index') }}" class="filter-form">
                <div class="filter-row">
                    <!-- Search -->
                    <div class="filter-item filter-search">
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Tìm kiếm video..." value="{{ request('search') }}">
                    </div>

                    <!-- Platform -->
                    <div class="filter-item">
                        <select name="platform" class="form-select form-select-sm">
                            <option value="">Tất cả platform</option>
                            <option value="tiktok" {{ request('platform') === 'tiktok' ? 'selected' : '' }}>TikTok</option>
                            <option value="youtube" {{ request('platform') === 'youtube' ? 'selected' : '' }}>YouTube</option>
                            <option value="none" {{ request('platform') === 'none' ? 'selected' : '' }}>Không đăng kênh</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="filter-item">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Tất cả trạng thái</option>
                            <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>Đã tạo</option>
                            <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Đã lên lịch</option>
                            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Đã đăng</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Lỗi</option>
                        </select>
                    </div>

                    <!-- Channel Status -->
                    <div class="filter-item">
                        <select name="channel_status" class="form-select form-select-sm">
                            <option value="">Tất cả đăng kênh</option>
                            <option value="not_publishing" {{ request('channel_status') === 'not_publishing' ? 'selected' : '' }}>Không đăng kênh</option>
                            <option value="waiting_channel" {{ request('channel_status') === 'waiting_channel' ? 'selected' : '' }}>Chờ đăng kênh</option>
                            <option value="published_channel" {{ request('channel_status') === 'published_channel' ? 'selected' : '' }}>Đã đăng kênh</option>
                            <option value="error_channel" {{ request('channel_status') === 'error_channel' ? 'selected' : '' }}>Lỗi đăng kênh</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="filter-item">
                        <select name="sort" class="form-select form-select-sm">
                            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Ngày tạo</option>
                            <option value="title" {{ request('sort') === 'title' ? 'selected' : '' }}>Tiêu đề</option>
                            <option value="platform" {{ request('sort') === 'platform' ? 'selected' : '' }}>Platform</option>
                            <option value="status" {{ request('sort') === 'status' ? 'selected' : '' }}>Trạng thái</option>
                        </select>
                    </div>

                    <!-- Direction -->
                    <div class="filter-item">
                        <select name="direction" class="form-select form-select-sm">
                            <option value="desc" {{ request('direction') === 'desc' ? 'selected' : '' }}>↓ Giảm dần</option>
                            <option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>↑ Tăng dần</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Lọc
                        </button>
                        <a href="{{ route('admin.videos.index') }}" class="btn btn-outline-secondary btn-sm">
                            Xóa
                        </a>
                    </div>
                </div>

                @if(request()->hasAny(['search', 'platform', 'status', 'channel_status']))
                    <div class="filter-status mt-2">
                        <small class="text-muted">
                            <i class="fas fa-filter me-1"></i>
                            Đang lọc {{ collect(request()->only(['search', 'platform', 'status', 'channel_status']))->filter()->count() }} tiêu chí
                        </small>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-4" id="bulk-actions" style="display: none;">
        <div class="card-body">
            <form id="bulk-form" method="POST" action="{{ route('admin.videos.bulk-action') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Hành động</label>
                        <select name="action" class="form-select" required>
                            <option value="">Chọn hành động</option>
                            <option value="schedule">Lên lịch đăng</option>
                            <option value="publish">Đăng ngay</option>
                            <option value="enable_channel">Bật đăng kênh</option>
                            <option value="disable_channel">Tắt đăng kênh</option>
                            <option value="delete">Xóa</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="schedule-time" style="display: none;">
                        <label class="form-label">Thời gian đăng</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" min="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-3" id="channel-select" style="display: none;">
                        <label class="form-label">Chọn kênh</label>
                        <select name="channel_id" class="form-select">
                            <option value="">Chọn kênh</option>
                            @foreach($channels as $channel)
                                <option value="{{ $channel->id }}">{{ $channel->name }} ({{ ucfirst($channel->platform) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Thực hiện
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted">Đã chọn: <span id="selected-count">0</span> video</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Videos List -->
    <div class="card">
        <div class="card-body">
            @if($videos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40" class="text-center">
                                    <div class="form-check">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </div>
                                </th>
                                <th>Video</th>
                                <th>Platform</th>
                                <th>Trạng thái</th>
                                <th>Đăng kênh</th>
                                <th>Thời lượng</th>
                                <th>Kích thước</th>
                                <th>Lịch đăng</th>
                                <th>Ngày tạo</th>
                                <th width="200">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($videos as $video)
                            <tr>
                                <td class="text-center">
                                    <div class="form-check">
                                        <input type="checkbox" name="video_ids[]" value="{{ $video->id }}" class="form-check-input video-checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ Str::limit($video->title, 50) }}</h6>
                                            <small class="text-muted">{{ $video->file_name }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $platformColors = [
                                            'tiktok' => 'dark',
                                            'youtube' => 'danger',
                                            'none' => 'secondary'
                                        ];
                                        $platformLabels = [
                                            'tiktok' => 'TIKTOK',
                                            'youtube' => 'YOUTUBE',
                                            'none' => 'KHÔNG ĐĂNG KÊNH'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $platformColors[$video->platform] ?? 'secondary' }}">
                                        {{ $platformLabels[$video->platform] ?? strtoupper($video->platform) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'generated' => 'success',
                                            'scheduled' => 'warning',
                                            'published' => 'primary',
                                            'failed' => 'danger'
                                        ];
                                        $statusLabels = [
                                            'generated' => 'Đã tạo',
                                            'scheduled' => 'Đã lên lịch',
                                            'published' => 'Đã đăng',
                                            'failed' => 'Lỗi'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$video->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$video->status] ?? $video->status }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $channelStatusColors = [
                                            'Không đăng kênh' => 'secondary',
                                            'Chưa chọn kênh' => 'warning',
                                            'Đã đăng kênh' => 'success',
                                            'Lỗi đăng kênh' => 'danger',
                                            'Chờ xuất bản' => 'info',
                                            'Chờ đăng kênh' => 'primary'
                                        ];
                                        $channelStatus = $video->channel_publish_status;
                                    @endphp
                                    <span class="badge bg-{{ $channelStatusColors[$channelStatus] ?? 'secondary' }}">
                                        {{ $channelStatus }}
                                    </span>
                                    @if($video->channel)
                                        <br><small class="text-muted">{{ $video->channel->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $video->duration_human }}</td>
                                <td>{{ $video->file_size_human }}</td>
                                <td>
                                    @if($video->scheduled_at)
                                        <small>{{ $video->scheduled_at->format('d/m/Y H:i') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $video->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($video->fileExists())
                                            <button type="button" class="btn btn-outline-info preview-video-btn"
                                                    data-video-url="{{ $video->preview_url }}"
                                                    data-video-title="{{ $video->title }}"
                                                    data-download-url="{{ $video->download_url }}"
                                                    title="Xem trước">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="{{ $video->download_url }}" class="btn btn-outline-success" title="Tải xuống">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.videos.show', $video) }}" class="btn btn-outline-primary" title="Chi tiết">
                                            <i class="fas fa-info"></i>
                                        </a>
                                        <a href="{{ route('admin.videos.edit', $video) }}" class="btn btn-outline-warning" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.videos.destroy', $video) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa video này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        <small class="text-muted">
                            Hiển thị {{ $videos->firstItem() }} - {{ $videos->lastItem() }} trong tổng số {{ $videos->total() }} video
                        </small>
                    </div>
                    <div>
                        {{ $videos->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <h5>Chưa có video nào</h5>
                    <p class="text-muted">Bắt đầu tạo video đầu tiên của bạn</p>
                    <a href="{{ route('admin.video-generator.index') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo Video Mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const videoCheckboxes = document.querySelectorAll('.video-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const actionSelect = document.querySelector('select[name="action"]');
    const scheduleTime = document.getElementById('schedule-time');

    // Select all functionality
    selectAll.addEventListener('change', function() {
        videoCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox functionality
    videoCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    // Show/hide schedule time input and channel selector
    const channelSelect = document.getElementById('channel-select');
    actionSelect.addEventListener('change', function() {
        // Hide all conditional inputs first
        scheduleTime.style.display = 'none';
        channelSelect.style.display = 'none';
        scheduleTime.querySelector('input').required = false;
        channelSelect.querySelector('select').required = false;

        // Show relevant inputs based on action
        if (this.value === 'schedule') {
            scheduleTime.style.display = 'block';
            scheduleTime.querySelector('input').required = true;
        } else if (this.value === 'enable_channel') {
            channelSelect.style.display = 'block';
        }
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.video-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCount.textContent = count;
        
        if (count > 0) {
            bulkActions.style.display = 'block';
            // Update hidden inputs for bulk form
            const bulkForm = document.getElementById('bulk-form');
            // Remove existing hidden inputs
            bulkForm.querySelectorAll('input[name="video_ids[]"]').forEach(input => input.remove());
            // Add new hidden inputs
            checkedBoxes.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'video_ids[]';
                hiddenInput.value = checkbox.value;
                bulkForm.appendChild(hiddenInput);
            });
        } else {
            bulkActions.style.display = 'none';
        }
        
        // Update select all checkbox state
        selectAll.indeterminate = count > 0 && count < videoCheckboxes.length;
        selectAll.checked = count === videoCheckboxes.length;
    }
});

function clearSelection() {
    document.querySelectorAll('.video-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select-all').checked = false;
    document.getElementById('bulk-actions').style.display = 'none';
}
</script>

<style>
/* Video Management Page Specific Styles */
.video-management-page {
    /* Custom styles for video management */
}

/* Filter Form Styles */
.video-management-page .filter-form {
    margin: 0;
}

.video-management-page .filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.video-management-page .filter-item {
    flex: 0 0 auto;
    min-width: 140px;
}

.video-management-page .filter-search {
    flex: 1 1 250px;
    min-width: 200px;
}

.video-management-page .filter-actions {
    display: flex;
    gap: 0.5rem;
    flex: 0 0 auto;
}

/* Form Controls */
.video-management-page .form-control-sm,
.video-management-page .form-select-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    border: 1px solid #ced4da;
    background-color: #fff;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.video-management-page .form-control-sm:focus,
.video-management-page .form-select-sm:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    outline: 0;
}

.video-management-page .form-select-sm {
    padding-right: 2rem;
}

/* Checkbox Fixes */
.video-management-page .form-check {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 1.5rem;
    margin-bottom: 0;
}

.video-management-page .form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0;
    margin-right: 0;
    vertical-align: middle;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    cursor: pointer;
}

.video-management-page .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.video-management-page .form-check-input:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Table Improvements */
.video-management-page .table th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    padding: 0.75rem;
    vertical-align: middle;
}

.video-management-page .table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.video-management-page .table th:first-child,
.video-management-page .table td:first-child {
    text-align: center;
    width: 40px;
}

/* Button Improvements */
.video-management-page .btn {
    border-radius: 0.25rem;
    font-weight: 500;
    border: 1px solid transparent;
    transition: all 0.15s ease-in-out;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
}

.video-management-page .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.video-management-page .btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.video-management-page .btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
    color: #fff;
}

.video-management-page .btn-outline-secondary {
    color: #6c757d;
    border-color: #6c757d;
    background-color: transparent;
}

.video-management-page .btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

/* Filter status */
.video-management-page .filter-status {
    padding-top: 0.5rem;
    border-top: 1px solid #dee2e6;
}

/* Input Group Fixes */
.video-management-page .input-group .form-select {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: 0;
}

.video-management-page .input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

/* Card Improvements */
.video-management-page .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.375rem;
}

.video-management-page .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    padding: 0.75rem 1.25rem;
}

.video-management-page .card-body {
    padding: 1.25rem;
}

/* Badge Improvements */
.video-management-page .badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
}

.video-management-page .badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000;
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .video-management-page .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }

    .video-management-page .d-flex.justify-content-between > div:last-child {
        text-align: center;
    }

    /* Filter responsive */
    .video-management-page .filter-row {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }

    .video-management-page .filter-item,
    .video-management-page .filter-search {
        flex: 1 1 auto;
        min-width: auto;
    }

    .video-management-page .filter-actions {
        justify-content: center;
        margin-top: 0.5rem;
    }
}

@media (max-width: 576px) {
    .video-management-page .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .video-management-page .card-body {
        padding: 0.75rem;
    }

    /* Filter mobile improvements */
    .video-management-page .filter-row {
        gap: 0.5rem;
    }

    .video-management-page .filter-actions {
        width: 100%;
        justify-content: space-between;
    }

    .video-management-page .filter-actions .btn {
        flex: 1;
    }

    /* Table mobile improvements */
    .video-management-page .table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.875rem;
    }

    .video-management-page .table .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    /* Hide some columns on mobile */
    .video-management-page .table th:nth-child(6),
    .video-management-page .table td:nth-child(6),
    .video-management-page .table th:nth-child(7),
    .video-management-page .table td:nth-child(7),
    .video-management-page .table th:nth-child(8),
    .video-management-page .table td:nth-child(8) {
        display: none;
    }

    /* Make video column wider on mobile */
    .video-management-page .table th:nth-child(2),
    .video-management-page .table td:nth-child(2) {
        min-width: 200px;
    }
}

/* Fix for very small screens */
@media (max-width: 480px) {
    .video-management-page .table-responsive {
        font-size: 0.8rem;
    }

    .video-management-page .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    .video-management-page .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }
}

/* Utility Classes */
.video-management-page .text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.video-management-page .video-thumbnail {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.75rem;
}

.video-management-page .gap-2 {
    gap: 0.5rem !important;
}

.video-management-page .gap-3 {
    gap: 1rem !important;
}

/* Fix for d-flex gap on older browsers */
.video-management-page .d-flex.gap-2 > * + * {
    margin-left: 0.5rem;
}

.video-management-page .d-flex.gap-3 > * + * {
    margin-left: 1rem;
}

@media (max-width: 768px) {
    .video-management-page .d-flex.gap-2 {
        flex-direction: column;
    }

    .video-management-page .d-flex.gap-2 > * + * {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}
</style>
@endsection

<!-- Video Preview Modal -->
<x-video-preview-modal id="videoPreviewModal" />
