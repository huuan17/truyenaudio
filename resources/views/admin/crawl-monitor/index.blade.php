@extends('layouts.app')

@section('title', 'Giám sát Crawl')

@push('styles')
<style>
    /* Queue Table Styling */
    #queue-table .btn-group .btn {
        padding: 2px 6px;
        font-size: 11px;
    }

    #queue-table code {
        font-size: 11px;
        padding: 2px 4px;
    }

    .delay-info {
        font-weight: bold;
    }

    .scheduled-time {
        font-family: monospace;
        font-size: 12px;
    }

    /* Status indicators */
    .badge-success {
        animation: pulse-success 2s infinite;
    }

    @keyframes pulse-success {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }

    /* Priority button highlight */
    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }

    /* Delay button highlight */
    .btn-warning:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
    }

    /* Delete button highlight */
    .btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    }

    /* Auto-refresh indicator */
    .auto-refresh-active {
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Giám sát Crawl',
            'badge' => 'Thời gian thực'
        ]
    ]" />



    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="crawling-count">{{ $crawlingStories->count() }}</h3>
                    <p>Đang Crawl</p>
                </div>
                <div class="icon">
                    <i class="fas fa-spider"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="stuck-count">{{ $stuckStories->count() }}</h3>
                    <p>Stuck Jobs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $recentCompleted->count() }}</h3>
                    <p>Hoàn thành hôm nay</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3 id="queue-count">{{ $queueStats['total_jobs'] ?? 0 }}</h3>
                    <p>Jobs trong Queue</p>
                    <small>
                        Ready: <span id="ready-count">{{ $queueStats['ready_jobs'] ?? 0 }}</span> |
                        Pending: <span id="pending-count">{{ $queueStats['pending_jobs'] ?? 0 }}</span>
                        <br>
                        <span class="text-info">{{ $queueStats['total_chapters'] ?? 0 }} chapters</span> |
                        <span class="text-warning">{{ $queueStats['total_estimated_time_formatted'] ?? '0s' }}</span>
                    </small>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs mr-2"></i>Control Panel
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-info" id="last-update">
                            Last update: {{ now()->format('H:i:s') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <a href="{{ route('admin.crawl-monitor.add-story') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus mr-2"></i>Thêm Truyện
                            </a>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary btn-block" onclick="refreshStatus()">
                                <i class="fas fa-sync mr-2"></i>Refresh Status
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-warning btn-block" onclick="recoverAll()">
                                <i class="fas fa-wrench mr-2"></i>Recover All Stuck
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-block" onclick="clearQueue()">
                                <i class="fas fa-trash mr-2"></i>Clear Queue
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-info btn-block" onclick="showQueueDetails()">
                                <i class="fas fa-list mr-2"></i>Queue Details
                            </button>
                        </div>
                        <div class="col-md-2">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto-refresh" checked>
                                <label class="custom-control-label" for="auto-refresh">Auto Refresh (30s)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Currently Crawling -->
    @if($crawlingStories->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-spider mr-2"></i>Currently Crawling ({{ $crawlingStories->count() }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0" id="crawling-table">
                            <thead>
                                <tr>
                                    <th>Story</th>
                                    <th>Progress</th>
                                    <th>Files</th>
                                    <th>Last Update</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($crawlingStories as $story)
                                <tr data-story-id="{{ $story->id }}">
                                    <td>
                                        <strong>{{ $story->title }}</strong>
                                        <br><small class="text-muted">ID: {{ $story->id }}</small>
                                    </td>
                                    <td>
                                        <div class="progress mb-1">
                                            <div class="progress-bar" style="width: {{ $story->progress['progress_percentage'] }}%"></div>
                                        </div>
                                        <small>{{ $story->progress['chapters_in_db'] }}/{{ $story->progress['expected_total'] }} chapters</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $story->progress['files_complete'] ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $story->progress['files_in_storage'] }}/{{ $story->progress['expected_total'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="update-time">{{ $story->updated_at->format('H:i:s') }}</span>
                                        <br><small class="text-muted minutes-ago">{{ $story->updated_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($story->progress['is_stuck'])
                                            <span class="badge badge-danger">Stuck</span>
                                        @else
                                            <span class="badge badge-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="recoverStory({{ $story->id }})" title="Recover">
                                                <i class="fas fa-wrench"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="stopStory({{ $story->id }})" title="Stop">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                        </div>
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
    @endif

    <!-- Stuck Jobs -->
    @if($stuckStories->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Stuck Jobs ({{ $stuckStories->count() }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Story</th>
                                    <th>Progress</th>
                                    <th>Stuck Time</th>
                                    <th>Recommended Action</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stuckStories as $story)
                                <tr>
                                    <td>
                                        <strong>{{ $story->title }}</strong>
                                        <br><small class="text-muted">ID: {{ $story->id }}</small>
                                    </td>
                                    <td>
                                        {{ $story->progress['chapters_in_db'] }}/{{ $story->progress['expected_total'] }} chapters
                                        <br>{{ $story->progress['files_in_storage'] }} files
                                    </td>
                                    <td>
                                        <span class="text-danger">
                                            {{ $story->updated_at->diffInMinutes(now()) }} minutes
                                        </span>
                                    </td>
                                    <td>
                                        @if($story->progress['db_complete'])
                                            <span class="badge badge-success">Mark Complete</span>
                                        @elseif($story->progress['files_complete'])
                                            <span class="badge badge-info">Import & Complete</span>
                                        @elseif($story->progress['chapters_in_db'] > 0)
                                            <span class="badge badge-warning">Re-crawl</span>
                                        @else
                                            <span class="badge badge-danger">Reset</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="recoverStory({{ $story->id }})">
                                            <i class="fas fa-wrench mr-1"></i>Recover
                                        </button>
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
    @endif

    <!-- Queue Management -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>Queue Management ({{ $crawlJobs->count() }} jobs)
                    </h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary" onclick="refreshQueue()">
                                <i class="fas fa-sync mr-1"></i>Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-success" onclick="prioritizeAllReady()">
                                <i class="fas fa-arrow-up mr-1"></i>Priority All Ready
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" onclick="delayAllPending()">
                                <i class="fas fa-clock mr-1"></i>Delay All +30m
                            </button>
                            <button type="button" class="btn btn-sm btn-info" onclick="rebalanceQueue()">
                                <i class="fas fa-balance-scale mr-1"></i>Rebalance
                            </button>
                            <button type="button" class="btn btn-sm btn-purple" onclick="updateStoryStatus()">
                                <i class="fas fa-sync-alt mr-1"></i>Update Status
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($crawlJobs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped mb-0" id="queue-table">
                                <thead>
                                    <tr>
                                        <th>Job ID</th>
                                        <th>Story</th>
                                        <th>Chapters</th>
                                        <th>Status</th>
                                        <th>Scheduled Time</th>
                                        <th>Delay</th>
                                        <th>Est. Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($crawlJobs as $job)
                                    <tr data-job-id="{{ $job->id }}">
                                        <td>
                                            <code>{{ $job->id }}</code>
                                        </td>
                                        <td>
                                            <strong>{{ $job->story_title }}</strong>
                                            @if($job->story)
                                                <br><small class="text-muted">ID: {{ $job->story->id }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($job->story)
                                                <span class="badge badge-info">
                                                    {{ $job->story->end_chapter - $job->story->start_chapter + 1 }}
                                                </span>
                                                <br><small class="text-muted">
                                                    {{ $job->story->start_chapter }}-{{ $job->story->end_chapter }}
                                                </small>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($job->delay_seconds <= 0)
                                                <span class="badge badge-success">Ready</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                            @if($job->attempts > 0)
                                                <br><small class="text-danger">{{ $job->attempts }} attempts</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="scheduled-time">{{ date('H:i:s', $job->available_at) }}</span>
                                            <br><small class="text-muted">{{ date('Y-m-d', $job->available_at) }}</small>
                                        </td>
                                        <td>
                                            <span class="delay-info">
                                                @if($job->delay_seconds <= 0)
                                                    <span class="text-success">Now</span>
                                                @else
                                                    <span class="text-warning">{{ ceil($job->delay_seconds / 60) }}m</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if($job->story)
                                                @php
                                                    $chapterCount = $job->story->end_chapter - $job->story->start_chapter + 1;
                                                    $estimatedSeconds = $chapterCount * 2; // 2 seconds per chapter
                                                    $hours = floor($estimatedSeconds / 3600);
                                                    $minutes = floor(($estimatedSeconds % 3600) / 60);
                                                @endphp
                                                <span class="text-info">
                                                    @if($hours > 0)
                                                        {{ $hours }}h {{ $minutes }}m
                                                    @elseif($minutes > 0)
                                                        {{ $minutes }}m
                                                    @else
                                                        {{ $estimatedSeconds }}s
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($job->delay_seconds > 0)
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            onclick="prioritizeJob({{ $job->id }})"
                                                            title="Ưu tiên (chạy ngay)">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                @endif

                                                <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="delayJob({{ $job->id }})"
                                                        title="Delay 30 phút">
                                                    <i class="fas fa-clock"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="deleteJob({{ $job->id }})"
                                                        title="Xóa job">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Không có jobs trong queue</h5>
                            <p class="text-muted">Tất cả crawl jobs đã được xử lý hoặc chưa có job nào được tạo.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Completed -->
    @if($recentCompleted->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-check-circle mr-2"></i>Recently Completed
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Story</th>
                                    <th>Chapters</th>
                                    <th>Completed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentCompleted as $story)
                                <tr>
                                    <td>
                                        <strong>{{ $story->title }}</strong>
                                        <br><small class="text-muted">ID: {{ $story->id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            {{ $story->chapters()->count() }} chapters
                                        </span>
                                    </td>
                                    <td>{{ $story->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Queue Worker Status Alert (Bottom) -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible" id="queue-worker-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-info-circle"></i> Hướng dẫn: Queue Worker</h5>
                <p><strong>Để Smart Auto Crawl hoạt động, bạn cần chạy Queue Worker:</strong></p>
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
                <div class="alert alert-success alert-sm mb-0 mt-2">
                    <i class="fas fa-brain"></i>
                    <strong>Smart Crawl:</strong> Hệ thống tự động quét chapters hiện có và chỉ crawl những chương còn thiếu, tiết kiệm thời gian và resources.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let autoRefreshInterval;

$(document).ready(function() {
    // Start auto refresh if enabled
    if ($('#auto-refresh').is(':checked')) {
        startAutoRefresh();
    }
    
    // Toggle auto refresh
    $('#auto-refresh').change(function() {
        if ($(this).is(':checked')) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
});

function startAutoRefresh() {
    autoRefreshInterval = setInterval(refreshStatus, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

function refreshStatus() {
    $.ajax({
        url: '{{ route("admin.crawl-monitor.status") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateStatusDisplay(response.data);
                $('#last-update').text('Last update: ' + response.timestamp);
            }
        },
        error: function() {
            showToast.error('Failed to refresh status');
        }
    });
}

function updateStatusDisplay(data) {
    // Update counts
    $('#crawling-count').text(data.length);
    
    // Update table rows
    data.forEach(function(story) {
        const row = $(`tr[data-story-id="${story.id}"]`);
        if (row.length) {
            // Update progress bar
            row.find('.progress-bar').css('width', story.progress.progress_percentage + '%');
            row.find('small').first().text(`${story.progress.chapters_in_db}/${story.progress.expected_total} chapters`);
            
            // Update time
            row.find('.update-time').text(story.last_update.split(' ')[1]);
            row.find('.minutes-ago').text(story.minutes_since_update + ' minutes ago');
            
            // Update status
            const statusBadge = row.find('.badge').last();
            if (story.progress.is_stuck) {
                statusBadge.removeClass('badge-success').addClass('badge-danger').text('Stuck');
            } else {
                statusBadge.removeClass('badge-danger').addClass('badge-success').text('Active');
            }
        }
    });
}

function recoverStory(storyId) {
    if (!confirm('Recover this stuck crawl job?')) return;
    
    $.ajax({
        url: '{{ route("admin.crawl-monitor.recover") }}',
        method: 'POST',
        data: {
            story_id: storyId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast.error(response.message);
            }
        }
    });
}

function stopStory(storyId) {
    if (!confirm('Stop this crawl job? This will reset the story status.')) return;
    
    $.ajax({
        url: '{{ route("admin.crawl-monitor.stop") }}',
        method: 'POST',
        data: {
            story_id: storyId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast.error(response.message);
            }
        }
    });
}

function recoverAll() {
    if (!confirm('Recover all stuck crawl jobs?')) return;
    
    $.ajax({
        url: '{{ route("admin.crawl-monitor.recover") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast.error(response.message);
            }
        }
    });
}

function clearQueue() {
    if (!confirm('Clear all jobs in crawl queue? This will stop all pending crawl jobs.')) return;

    $.ajax({
        url: '{{ route("admin.crawl-monitor.clear-queue") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast.error(response.message);
            }
        }
    });
}

// Queue Management Functions
function refreshQueue() {
    $.ajax({
        url: '{{ route("admin.crawl-monitor.queue-details") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateQueueDisplay(response.jobs, response.stats);
                showToast.success('Queue refreshed');
            } else {
                showToast.error('Failed to refresh queue');
            }
        },
        error: function() {
            showToast.error('Failed to refresh queue');
        }
    });
}

function updateQueueDisplay(jobs, stats) {
    // Update stats
    $('#queue-count').text(stats.total);
    $('#ready-count').text(stats.ready);
    $('#pending-count').text(stats.pending);

    // Update table
    const tbody = $('#queue-table tbody');
    tbody.empty();

    if (jobs.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="fas fa-list fa-2x text-muted mb-2"></i>
                    <div class="text-muted">Không có jobs trong queue</div>
                </td>
            </tr>
        `);
        return;
    }

    jobs.forEach(function(job) {
        const statusBadge = job.is_ready ?
            '<span class="badge badge-success">Ready</span>' :
            '<span class="badge badge-warning">Pending</span>';

        const delayInfo = job.is_ready ?
            '<span class="text-success">Now</span>' :
            `<span class="text-warning">${job.delay_minutes}m</span>`;

        const prioritizeBtn = job.is_ready ? '' :
            `<button type="button" class="btn btn-sm btn-success" onclick="prioritizeJob(${job.id})" title="Ưu tiên">
                <i class="fas fa-arrow-up"></i>
            </button>`;

        const attemptsBadge = job.attempts > 0 ?
            `<br><small class="text-danger">${job.attempts} attempts</small>` : '';

        tbody.append(`
            <tr data-job-id="${job.id}">
                <td><code>${job.id}</code></td>
                <td><strong>${job.story_title}</strong></td>
                <td>${statusBadge}${attemptsBadge}</td>
                <td>
                    <span class="scheduled-time">${job.available_at.split(' ')[1]}</span>
                    <br><small class="text-muted">${job.available_at.split(' ')[0]}</small>
                </td>
                <td><span class="delay-info">${delayInfo}</span></td>
                <td><span class="badge ${job.attempts > 0 ? 'badge-warning' : 'badge-secondary'}">${job.attempts}</span></td>
                <td>
                    <div class="btn-group" role="group">
                        ${prioritizeBtn}
                        <button type="button" class="btn btn-sm btn-warning" onclick="delayJob(${job.id})" title="Delay 30 phút">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteJob(${job.id})" title="Xóa job">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function prioritizeJob(jobId) {
    if (!confirm('Ưu tiên job này để chạy ngay lập tức?')) return;

    $.ajax({
        url: '{{ route("admin.crawl-monitor.prioritize-job") }}',
        method: 'POST',
        data: {
            job_id: jobId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                refreshQueue();
            } else {
                showToast.error(response.message);
            }
        }
    });
}

function delayJob(jobId) {
    const delayMinutes = prompt('Delay job bao nhiêu phút?', '30');
    if (!delayMinutes || isNaN(delayMinutes)) return;

    $.ajax({
        url: '{{ route("admin.crawl-monitor.delay-job") }}',
        method: 'POST',
        data: {
            job_id: jobId,
            delay_minutes: parseInt(delayMinutes),
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                refreshQueue();
            } else {
                showToast.error(response.message);
            }
        }
    });
}

function deleteJob(jobId) {
    if (!confirm('Xóa job này khỏi queue? Job sẽ không được thực hiện.')) return;

    $.ajax({
        url: '{{ route("admin.crawl-monitor.delete-job") }}',
        method: 'POST',
        data: {
            job_id: jobId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                refreshQueue();
            } else {
                showToast.error(response.message);
            }
        }
    });
}

// Batch Operations
function prioritizeAllReady() {
    if (!confirm('Ưu tiên tất cả jobs sẵn sàng?')) return;

    const readyJobs = [];
    $('#queue-table tbody tr').each(function() {
        const row = $(this);
        if (row.find('.badge-success').length > 0) { // Ready jobs
            const jobId = row.data('job-id');
            if (jobId) readyJobs.push(jobId);
        }
    });

    if (readyJobs.length === 0) {
        showToast.info('Không có jobs nào cần ưu tiên');
        return;
    }

    let completed = 0;
    readyJobs.forEach(function(jobId) {
        $.ajax({
            url: '{{ route("admin.crawl-monitor.prioritize-job") }}',
            method: 'POST',
            data: {
                job_id: jobId,
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                completed++;
                if (completed === readyJobs.length) {
                    showToast.success(`Đã ưu tiên ${completed} jobs`);
                    refreshQueue();
                }
            }
        });
    });
}

function delayAllPending() {
    if (!confirm('Delay tất cả pending jobs thêm 30 phút?')) return;

    const pendingJobs = [];
    $('#queue-table tbody tr').each(function() {
        const row = $(this);
        if (row.find('.badge-warning').length > 0) { // Pending jobs
            const jobId = row.data('job-id');
            if (jobId) pendingJobs.push(jobId);
        }
    });

    if (pendingJobs.length === 0) {
        showToast.info('Không có pending jobs nào');
        return;
    }

    let completed = 0;
    pendingJobs.forEach(function(jobId) {
        $.ajax({
            url: '{{ route("admin.crawl-monitor.delay-job") }}',
            method: 'POST',
            data: {
                job_id: jobId,
                delay_minutes: 30,
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                completed++;
                if (completed === pendingJobs.length) {
                    showToast.success(`Đã delay ${completed} jobs thêm 30 phút`);
                    refreshQueue();
                }
            }
        });
    });
}

function rebalanceQueue() {
    if (!confirm('Rebalance queue để tránh overlap jobs? Điều này sẽ điều chỉnh thời gian schedule của các jobs.')) return;

    $.ajax({
        url: '{{ route("admin.crawl-monitor.rebalance-queue") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                refreshQueue();
            } else {
                showToast.error(response.message);
            }
        }
    });
}

function updateStoryStatus() {
    if (!confirm('Kiểm tra và cập nhật trạng thái crawl cho tất cả truyện dựa trên số chapter thực tế?')) return;

    // Show loading toast
    showToast.info('🔍 Đang kiểm tra trạng thái truyện...');

    $.ajax({
        url: '{{ route("admin.crawl-monitor.update-story-status") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast.success(response.message);
                // Refresh page to show updated status
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast.error(response.message);
            }
        },
        error: function() {
            showToast.error('Có lỗi xảy ra khi cập nhật trạng thái');
        }
    });
}

function showQueueDetails() {
    // Open queue details in a new tab/window
    window.open('{{ route("admin.crawl-monitor.queue-details") }}', '_blank');
}

// Auto-refresh queue every 30 seconds if enabled
setInterval(function() {
    if ($('#auto-refresh').is(':checked')) {
        refreshQueue();
    }
}, 30000);
</script>
@endsection
