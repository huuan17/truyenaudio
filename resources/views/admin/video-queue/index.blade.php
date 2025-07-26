@extends('layouts.app')

@section('title', 'Hàng đợi Tạo Video')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Hàng đợi Video',
            'badge' => 'Thời gian thực'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks mr-2"></i>Trạng Thái Xử Lý Video
                    </h3>
                    <div class="card-tools">
                        <div class="d-flex align-items-center">
                            <!-- Auto-refresh status indicator -->
                            <div id="auto-refresh-status" class="mr-3">
                                <small class="text-success">
                                    <i class="fas fa-circle fa-xs mr-1"></i>Auto-refresh: ON
                                </small>
                            </div>

                            <button class="btn btn-sm btn-info" onclick="manualRefresh()" title="Làm mới thủ công (Ctrl+R)">
                                <i class="fas fa-sync mr-1"></i>Làm mới
                            </button>

                            <button class="btn btn-sm btn-outline-secondary ml-1" onclick="toggleAutoRefresh()"
                                    id="auto-refresh-toggle" title="Bật/tắt auto-refresh">
                                <i class="fas fa-pause mr-1"></i>Tạm dừng
                            </button>

                            @if(auth()->user()->isAdmin())
                            <button class="btn btn-sm btn-warning ml-1" onclick="clearCompletedTasks()">
                                <i class="fas fa-trash mr-1"></i>Xóa task cũ
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Queue Worker Status -->
                    <div class="alert alert-info mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><i class="fas fa-cogs mr-2"></i>Queue Worker Status</h5>
                                <p class="mb-2">
                                    <strong>Video Queue Worker:</strong>
                                    <span id="worker-status" class="badge badge-success">
                                        <i class="fas fa-circle fa-xs mr-1"></i>Running
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <small class="text-muted">
                                        Queue worker đang chạy và sẵn sàng xử lý video generation jobs.
                                        Nếu worker không chạy, video sẽ không được tạo.
                                    </small>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <button class="btn btn-sm btn-primary" onclick="checkWorkerStatus()">
                                    <i class="fas fa-sync mr-1"></i>Kiểm tra Worker
                                </button>
                                <br><br>
                                <small class="text-muted">
                                    Để start worker thủ công:<br>
                                    <code>php artisan queue:work --queue=video</code>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Đang chờ</span>
                                    <span class="info-box-number" id="pending-count">{{ $queueStatus['pending'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-cog fa-spin"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Đang xử lý</span>
                                    <span class="info-box-number" id="processing-count">{{ $queueStatus['processing'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Hoàn thành hôm nay</span>
                                    <span class="info-box-number" id="completed-count">{{ $queueStatus['completed_today'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Thất bại hôm nay</span>
                                    <span class="info-box-number" id="failed-count">{{ $queueStatus['failed_today'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Tasks -->
                    <div class="row">
                        <div class="col-12">
                            <h5><i class="fas fa-user mr-2"></i>Task của bạn</h5>
                            <div class="table-responsive">
                                <table class="table table-striped" id="user-tasks-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Platform</th>
                                            <th>Loại</th>
                                            <th>Trạng thái</th>
                                            <th>Tiến trình</th>
                                            <th>Thời gian tạo</th>
                                            <th>Thời gian hoàn thành dự kiến</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userTasks as $task)
                                        <tr data-task-id="{{ $task->id }}">
                                            <td><strong>#{{ $task->id }}</strong></td>
                                            <td>
                                                <span class="badge badge-{{ $task->platform === 'tiktok' ? 'dark' : 'danger' }}">
                                                    {{ $task->platform_display }}
                                                </span>
                                            </td>
                                            <td>{{ $task->type_display }}</td>
                                            <td>
                                                <span class="badge {{ $task->status_badge_class }}">
                                                    {{ $task->status_display }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: {{ $task->progress_percentage }}%"
                                                         aria-valuenow="{{ $task->progress_percentage }}" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        {{ $task->progress_percentage }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $task->created_at->format('H:i:s d/m/Y') }}</td>
                                            <td>
                                                @if($task->estimated_completion)
                                                    {{ $task->estimated_completion->format('H:i:s d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick="showTaskDetails({{ $task->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($task->canBeCancelled())
                                                    <button class="btn btn-warning" onclick="cancelTask({{ $task->id }})">
                                                        <i class="fas fa-stop"></i>
                                                    </button>
                                                    @endif
                                                    @if($task->canBeRetried())
                                                    <button class="btn btn-success" onclick="retryTask({{ $task->id }})">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                    @endif
                                                    @if(in_array($task->status, ['completed', 'failed', 'cancelled']))
                                                    <button class="btn btn-danger" onclick="deleteTask({{ $task->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            {{ $userTasks->links() }}
                        </div>
                    </div>

                    @if(auth()->user()->isAdmin() && $allTasks)
                    <!-- Admin: All Tasks -->
                    <div class="row mt-5">
                        <div class="col-12">
                            <h5><i class="fas fa-users-cog mr-2"></i>Tất cả Task (Admin)</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Platform</th>
                                            <th>Loại</th>
                                            <th>Trạng thái</th>
                                            <th>Tiến trình</th>
                                            <th>Thời gian tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allTasks as $task)
                                        <tr>
                                            <td><strong>#{{ $task->id }}</strong></td>
                                            <td>{{ $task->user->email }}</td>
                                            <td>
                                                <span class="badge badge-{{ $task->platform === 'tiktok' ? 'dark' : 'danger' }}">
                                                    {{ $task->platform_display }}
                                                </span>
                                            </td>
                                            <td>{{ $task->type_display }}</td>
                                            <td>
                                                <span class="badge {{ $task->status_badge_class }}">
                                                    {{ $task->status_display }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 15px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: {{ $task->progress_percentage }}%">
                                                        {{ $task->progress_percentage }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $task->created_at->format('H:i d/m') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick="showTaskDetails({{ $task->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($task->canBeCancelled())
                                                    <button class="btn btn-warning" onclick="cancelTask({{ $task->id }})">
                                                        <i class="fas fa-stop"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            {{ $allTasks->links() }}
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết Task</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="taskDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto refresh with smart pausing
let autoRefreshInterval;
let isUserInteracting = false;
let lastInteractionTime = Date.now();
let autoRefreshEnabled = true;

// Start auto refresh
startAutoRefresh();

function startAutoRefresh() {
    // Clear existing interval
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }

    // Only start if auto-refresh is enabled
    if (!autoRefreshEnabled) {
        updateAutoRefreshStatus();
        return;
    }

    // Set new interval (10 seconds)
    autoRefreshInterval = setInterval(function() {
        // Only refresh if enabled and user hasn't interacted recently (last 5 seconds)
        if (autoRefreshEnabled && !isUserInteracting && (Date.now() - lastInteractionTime) > 5000) {
            refreshQueueStatus();
        }
    }, 10000);

    updateAutoRefreshStatus();
}

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;

    if (autoRefreshEnabled) {
        startAutoRefresh();
        showNotification('Auto-refresh đã được bật', 'success', 2000);
    } else {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        showNotification('Auto-refresh đã được tắt', 'info', 2000);
    }

    updateAutoRefreshStatus();
}

function updateAutoRefreshStatus() {
    const statusElement = $('#auto-refresh-status');
    const toggleButton = $('#auto-refresh-toggle');

    if (autoRefreshEnabled) {
        statusElement.html('<small class="text-success"><i class="fas fa-circle fa-xs mr-1"></i>Auto-refresh: ON</small>');
        toggleButton.html('<i class="fas fa-pause mr-1"></i>Tạm dừng').removeClass('btn-success').addClass('btn-outline-secondary');
    } else {
        statusElement.html('<small class="text-muted"><i class="fas fa-circle fa-xs mr-1"></i>Auto-refresh: OFF</small>');
        toggleButton.html('<i class="fas fa-play mr-1"></i>Bật lại').removeClass('btn-outline-secondary').addClass('btn-success');
    }
}

function refreshQueueStatus() {
    // Show subtle loading indicator
    const refreshButton = $('button[onclick="refreshQueueStatus()"]');
    const originalIcon = refreshButton.find('i').attr('class');
    refreshButton.find('i').attr('class', 'fas fa-spinner fa-spin');

    $.get('{{ route("admin.video-queue.status") }}', function(data) {
        // Update statistics with animation
        updateStatisticsWithAnimation(data.queue_status);

        // Update user tasks table
        updateUserTasksTable(data.user_tasks);

        // Restore refresh button
        refreshButton.find('i').attr('class', originalIcon);

        // Show last updated time
        updateLastRefreshTime();

    }).fail(function() {
        // Restore refresh button on error
        refreshButton.find('i').attr('class', originalIcon);
        showNotification('Không thể cập nhật trạng thái queue', 'warning', 3000);
    });
}

function updateStatisticsWithAnimation(queueStatus) {
    // Update each counter with animation if value changed
    updateCounterWithAnimation('#pending-count', queueStatus.pending);
    updateCounterWithAnimation('#processing-count', queueStatus.processing);
    updateCounterWithAnimation('#completed-count', queueStatus.completed_today);
    updateCounterWithAnimation('#failed-count', queueStatus.failed_today);
}

function updateCounterWithAnimation(selector, newValue) {
    const element = $(selector);
    const currentValue = parseInt(element.text()) || 0;

    if (currentValue !== newValue) {
        // Add pulse animation
        element.parent().addClass('pulse-animation');

        // Update value
        element.text(newValue);

        // Remove animation after completion
        setTimeout(function() {
            element.parent().removeClass('pulse-animation');
        }, 600);
    }
}

function updateLastRefreshTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('vi-VN');

    // Update or create last refresh indicator
    let indicator = $('#last-refresh-indicator');
    if (indicator.length === 0) {
        $('.card-tools').append(`
            <small id="last-refresh-indicator" class="text-muted ml-2">
                <i class="fas fa-clock mr-1"></i>Cập nhật: ${timeString}
            </small>
        `);
    } else {
        indicator.html(`<i class="fas fa-clock mr-1"></i>Cập nhật: ${timeString}`);
    }
}

// Track user interactions to pause auto-refresh
$(document).on('click', '.btn-group button, .form-check-input, .btn', function() {
    isUserInteracting = true;
    lastInteractionTime = Date.now();

    // Resume after 3 seconds
    setTimeout(function() {
        isUserInteracting = false;
    }, 3000);
});

// Pause auto-refresh when modal is open
$(document).on('shown.bs.modal', function() {
    isUserInteracting = true;
});

$(document).on('hidden.bs.modal', function() {
    isUserInteracting = false;
    lastInteractionTime = Date.now();
});

// Manual refresh button
function manualRefresh() {
    isUserInteracting = false;
    lastInteractionTime = 0; // Force refresh
    refreshQueueStatus();
}

// Check queue worker status
function checkWorkerStatus() {
    const statusElement = $('#worker-status');
    const originalContent = statusElement.html();

    // Show loading
    statusElement.html('<i class="fas fa-spinner fa-spin mr-1"></i>Checking...')
                 .removeClass('badge-success badge-danger')
                 .addClass('badge-secondary');

    $.get('{{ route("admin.video-queue.worker-status") }}', function(data) {
        if (data.worker_running) {
            statusElement.html('<i class="fas fa-circle fa-xs mr-1"></i>Running')
                         .removeClass('badge-secondary badge-danger')
                         .addClass('badge-success');
            showNotification('Queue worker đang chạy bình thường', 'success', 3000);
        } else {
            statusElement.html('<i class="fas fa-times-circle fa-xs mr-1"></i>Stopped')
                         .removeClass('badge-secondary badge-success')
                         .addClass('badge-danger');
            showNotification('Queue worker không chạy! Video sẽ không được xử lý.', 'error', 5000);
        }
    }).fail(function() {
        statusElement.html('<i class="fas fa-question-circle fa-xs mr-1"></i>Unknown')
                     .removeClass('badge-secondary badge-success')
                     .addClass('badge-warning');
        showNotification('Không thể kiểm tra trạng thái worker', 'warning', 3000);
    });
}

function updateUserTasksTable(tasks) {
    const tbody = $('#user-tasks-table tbody');

    tasks.forEach(function(task) {
        const row = $(`tr[data-task-id="${task.id}"]`);
        if (row.length) {
            // Update status badge with animation if changed
            const currentBadge = row.find('.badge').first();
            const currentStatus = currentBadge.text().trim();

            if (currentStatus !== task.status_display) {
                currentBadge.fadeOut(200, function() {
                    $(this).removeClass().addClass(`badge ${task.status_badge_class}`)
                           .text(task.status_display)
                           .fadeIn(200);
                });
            }

            // Update progress bar with smooth animation
            const progressBar = row.find('.progress-bar');
            const currentProgress = parseInt(progressBar.attr('aria-valuenow')) || 0;

            if (currentProgress !== task.progress) {
                progressBar.css('width', task.progress + '%')
                          .attr('aria-valuenow', task.progress)
                          .text(task.progress + '%');

                // Add progress animation for processing tasks
                if (task.status === 'processing' && task.progress > currentProgress) {
                    progressBar.addClass('progress-bar-animated');
                } else {
                    progressBar.removeClass('progress-bar-animated');
                }
            }

            // Update estimated completion time
            const estimatedCell = row.find('td').eq(6);
            if (task.estimated_completion) {
                estimatedCell.text(task.estimated_completion);
            }

            // Update action buttons based on status
            updateTaskActionButtonsFromStatus(row, task);

            // Add visual indicator for recently updated tasks
            if (task.status === 'processing' && task.progress > currentProgress) {
                row.addClass('table-info');
                setTimeout(function() {
                    row.removeClass('table-info');
                }, 1000);
            }
        } else {
            // New task appeared, might need to reload to show it properly
            if (task.status === 'pending' || task.status === 'processing') {
                // Show notification about new task
                showNotification(`Task mới #${task.id} đã được thêm vào queue`, 'info', 3000);
            }
        }
    });
}

function updateTaskActionButtonsFromStatus(row, task) {
    const actionButtons = row.find('.btn-group');
    const taskId = task.id;

    let buttonsHtml = `
        <button class="btn btn-info btn-sm" onclick="showTaskDetails(${taskId})" title="Xem chi tiết">
            <i class="fas fa-eye"></i>
        </button>
    `;

    if (task.can_cancel) {
        buttonsHtml += `
            <button class="btn btn-warning btn-sm" onclick="cancelTask(${taskId})" title="Hủy task">
                <i class="fas fa-stop"></i>
            </button>
        `;
    }

    if (task.can_retry) {
        buttonsHtml += `
            <button class="btn btn-success btn-sm" onclick="retryTask(${taskId})" title="Thử lại">
                <i class="fas fa-redo"></i>
            </button>
        `;
    }

    if (task.status === 'completed' || task.status === 'failed' || task.status === 'cancelled') {
        buttonsHtml += `
            <button class="btn btn-danger btn-sm" onclick="deleteTask(${taskId})" title="Xóa task">
                <i class="fas fa-trash"></i>
            </button>
        `;
    }

    // Only update if buttons changed
    if (actionButtons.html().trim() !== buttonsHtml.trim()) {
        actionButtons.html(buttonsHtml);
    }
}

function showTaskDetails(taskId) {
    $.get(`{{ route("admin.video-queue.index") }}/${taskId}`, function(data) {
        let content = `
            <div class="row">
                <div class="col-md-6">
                    <strong>ID:</strong> #${data.id}<br>
                    <strong>Platform:</strong> ${data.platform}<br>
                    <strong>Loại:</strong> ${data.type}<br>
                    <strong>Trạng thái:</strong> <span class="badge ${data.status_badge_class}">${data.status}</span><br>
                    <strong>Tiến trình:</strong> ${data.progress}%<br>
                </div>
                <div class="col-md-6">
                    <strong>Thời gian tạo:</strong> ${data.created_at}<br>
                    <strong>Bắt đầu:</strong> ${data.started_at || 'Chưa bắt đầu'}<br>
                    <strong>Hoàn thành:</strong> ${data.completed_at || 'Chưa hoàn thành'}<br>
                    <strong>Thời lượng:</strong> ${data.duration || 'N/A'}<br>
                </div>
            </div>
        `;
        
        if (data.batch_id) {
            content += `
                <hr>
                <h6>Batch Information</h6>
                <strong>Batch ID:</strong> ${data.batch_id}<br>
                <strong>Batch Progress:</strong> ${data.batch_progress ? data.batch_progress.completed + '/' + data.batch_progress.total : 'N/A'}
            `;
        }
        
        if (data.result && data.result.error) {
            content += `
                <hr>
                <h6 class="text-danger">Lỗi:</h6>
                <div class="alert alert-danger">${data.result.error}</div>
            `;
        }
        
        $('#taskDetailsContent').html(content);
        $('#taskDetailsModal').modal('show');
    });
}

function cancelTask(taskId) {
    if (confirm('Bạn có chắc muốn hủy task này?')) {
        // Show loading state
        const button = $(`.btn-group button[onclick="cancelTask(${taskId})"]`);
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(`{{ route("admin.video-queue.index") }}/${taskId}/cancel`, {
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                // Show success notification
                showNotification(data.message, 'success');

                // Update task row immediately
                updateTaskRowStatus(taskId, 'cancelled', 'Đã hủy', 'badge-secondary');

                // Refresh queue status
                refreshQueueStatus();

                // Update statistics immediately
                updateStatisticsAfterCancel();

            } else {
                showNotification('Lỗi: ' + data.message, 'error');
                // Restore button
                button.prop('disabled', false).html(originalHtml);
            }
        }).fail(function() {
            showNotification('Có lỗi xảy ra khi hủy task', 'error');
            // Restore button
            button.prop('disabled', false).html(originalHtml);
        });
    }
}

function retryTask(taskId) {
    if (confirm('Bạn có chắc muốn retry task này?')) {
        // Show loading state
        const button = $(`.btn-group button[onclick="retryTask(${taskId})"]`);
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(`{{ route("admin.video-queue.index") }}/${taskId}/retry`, {
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                // Show success notification
                showNotification(data.message + ` (Task mới: #${data.new_task_id})`, 'success');

                // Hide current task row (it's failed, new task created)
                $(`tr[data-task-id="${taskId}"]`).fadeOut();

                // Refresh queue status to show new task
                refreshQueueStatus();

                // Update statistics
                updateStatisticsAfterRetry();

                // Auto-refresh after 2 seconds to show new task
                setTimeout(function() {
                    location.reload();
                }, 2000);

            } else {
                showNotification('Lỗi: ' + data.message, 'error');
                // Restore button
                button.prop('disabled', false).html(originalHtml);
            }
        }).fail(function() {
            showNotification('Có lỗi xảy ra khi retry task', 'error');
            // Restore button
            button.prop('disabled', false).html(originalHtml);
        });
    }
}

function deleteTask(taskId) {
    if (confirm('Bạn có chắc muốn xóa task này?')) {
        // Show loading state
        const button = $(`.btn-group button[onclick="deleteTask(${taskId})"]`);
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: `{{ route("admin.video-queue.index") }}/${taskId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(data) {
                if (data.success) {
                    // Show success notification
                    showNotification(data.message, 'success');

                    // Remove task row with animation
                    const taskRow = $(`tr[data-task-id="${taskId}"]`);
                    taskRow.fadeOut(300, function() {
                        taskRow.remove();

                        // Check if table is empty
                        if ($('#user-tasks-table tbody tr').length === 0) {
                            $('#user-tasks-table tbody').html(`
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Không có task nào.</p>
                                    </td>
                                </tr>
                            `);
                        }
                    });

                    // Update statistics
                    updateStatisticsAfterDelete();

                } else {
                    showNotification('Lỗi: ' + data.message, 'error');
                    // Restore button
                    button.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                showNotification('Có lỗi xảy ra khi xóa task', 'error');
                // Restore button
                button.prop('disabled', false).html(originalHtml);
            }
        });
    }
}

function clearCompletedTasks() {
    if (confirm('Bạn có chắc muốn xóa tất cả task cũ đã hoàn thành?')) {
        // Show loading state
        const button = $('button[onclick="clearCompletedTasks()"]');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang xóa...');

        $.post('{{ route("admin.video-queue.clear-completed") }}', {
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                showNotification(data.message, 'success');

                // Reload page to reflect changes
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showNotification('Lỗi: ' + data.message, 'error');
                // Restore button
                button.prop('disabled', false).html(originalHtml);
            }
        }).fail(function() {
            showNotification('Có lỗi xảy ra khi xóa task', 'error');
            // Restore button
            button.prop('disabled', false).html(originalHtml);
        });
    }
}

// Helper functions for real-time UI updates
function updateTaskRowStatus(taskId, status, statusDisplay, badgeClass) {
    const row = $(`tr[data-task-id="${taskId}"]`);
    if (row.length) {
        // Update status badge
        const statusBadge = row.find('.badge').first();
        statusBadge.removeClass().addClass(`badge ${badgeClass}`).text(statusDisplay);

        // Update progress bar (set to 0 for cancelled)
        if (status === 'cancelled') {
            const progressBar = row.find('.progress-bar');
            progressBar.css('width', '0%').attr('aria-valuenow', 0).text('0%');
        }

        // Update action buttons
        updateTaskActionButtons(row, status);

        // Add visual feedback
        row.addClass('table-warning');
        setTimeout(function() {
            row.removeClass('table-warning');
        }, 2000);
    }
}

function updateTaskActionButtons(row, status) {
    const actionButtons = row.find('.btn-group');

    if (status === 'cancelled') {
        // Remove cancel button, add retry and delete buttons
        actionButtons.html(`
            <button class="btn btn-info btn-sm" onclick="showTaskDetails(${row.data('task-id')})">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-success btn-sm" onclick="retryTask(${row.data('task-id')})">
                <i class="fas fa-redo"></i>
            </button>
            <button class="btn btn-danger btn-sm" onclick="deleteTask(${row.data('task-id')})">
                <i class="fas fa-trash"></i>
            </button>
        `);
    }
}

function updateStatisticsAfterCancel() {
    // Decrease pending count, increase cancelled count
    const pendingCount = parseInt($('#pending-count').text());
    if (pendingCount > 0) {
        $('#pending-count').text(pendingCount - 1);

        // Add visual feedback to pending counter
        $('#pending-count').parent().addClass('pulse-animation');
        setTimeout(function() {
            $('#pending-count').parent().removeClass('pulse-animation');
        }, 1000);
    }
}

function updateStatisticsAfterRetry() {
    // Increase pending count (new task created)
    const pendingCount = parseInt($('#pending-count').text());
    $('#pending-count').text(pendingCount + 1);

    // Add visual feedback
    $('#pending-count').parent().addClass('pulse-animation');
    setTimeout(function() {
        $('#pending-count').parent().removeClass('pulse-animation');
    }, 1000);
}

function updateStatisticsAfterDelete() {
    // Just visual feedback, actual counts will be updated on next refresh
    // Add pulse animation to indicate change
    $('.info-box').addClass('pulse-animation');
    setTimeout(function() {
        $('.info-box').removeClass('pulse-animation');
    }, 1000);
}

// Enhanced notification system
function showNotification(message, type = 'info', duration = 5000) {
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle' :
                     type === 'error' ? 'fa-exclamation-circle' :
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show notification-toast"
             style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 350px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <i class="fas ${iconClass} mr-2"></i>${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);

    $('body').append(notification);

    // Auto remove after duration
    setTimeout(() => {
        notification.fadeOut(() => notification.remove());
    }, duration);

    // Play notification sound (optional)
    if (type === 'success') {
        playNotificationSound();
    }
}

function playNotificationSound() {
    // Create a subtle notification sound
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (e) {
        // Ignore audio errors
    }
}

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl/Cmd + R: Manual refresh
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
        e.preventDefault();
        manualRefresh();
        showNotification('Đã làm mới thủ công', 'info', 2000);
    }

    // F5: Manual refresh (alternative)
    if (e.keyCode === 116) {
        e.preventDefault();
        manualRefresh();
    }

    // Escape: Close any open modals
    if (e.keyCode === 27) {
        $('.modal').modal('hide');
    }
});

// Page visibility API to pause/resume auto-refresh
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, pause auto-refresh
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    } else {
        // Page is visible, resume auto-refresh
        startAutoRefresh();
        // Refresh immediately when page becomes visible
        setTimeout(manualRefresh, 500);
    }
});

// Connection status monitoring
let isOnline = navigator.onLine;

window.addEventListener('online', function() {
    if (!isOnline) {
        isOnline = true;
        showNotification('Kết nối internet đã được khôi phục', 'success', 3000);
        // Refresh immediately when back online
        setTimeout(manualRefresh, 1000);
    }
});

window.addEventListener('offline', function() {
    isOnline = false;
    showNotification('Mất kết nối internet. Auto-refresh sẽ tạm dừng.', 'warning', 5000);
    // Clear auto-refresh when offline
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

// Enhanced error handling for AJAX requests
$(document).ajaxError(function(event, xhr, settings, thrownError) {
    if (xhr.status === 0) {
        // Network error
        showNotification('Lỗi kết nối mạng. Vui lòng kiểm tra internet.', 'error', 5000);
    } else if (xhr.status === 500) {
        // Server error
        showNotification('Lỗi server. Vui lòng thử lại sau.', 'error', 5000);
    } else if (xhr.status === 403) {
        // Permission error
        showNotification('Không có quyền thực hiện thao tác này.', 'error', 5000);
    }
});

// Initialize tooltips for better accessibility
$(document).ready(function() {
    $('[title]').tooltip();

    // Add ARIA labels for screen readers
    $('#pending-count').attr('aria-label', 'Số task đang chờ');
    $('#processing-count').attr('aria-label', 'Số task đang xử lý');
    $('#completed-count').attr('aria-label', 'Số task hoàn thành hôm nay');
    $('#failed-count').attr('aria-label', 'Số task thất bại hôm nay');

    // Add keyboard navigation for table
    $('#user-tasks-table tbody tr').attr('tabindex', '0');

    // Focus management for modals
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('[autofocus]').focus();
    });
});
</script>
@endpush

@push('styles')
<style>
.info-box {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table th {
    border-top: none;
    font-weight: 600;
}

.progress {
    background-color: #e9ecef;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.badge {
    font-size: 0.875rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.fa-spin {
    animation: spin 2s linear infinite;
}

/* Real-time update animations */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.pulse-animation {
    animation: pulse 0.6s ease-in-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-toast {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes highlight {
    0% { background-color: transparent; }
    50% { background-color: rgba(255, 193, 7, 0.3); }
    100% { background-color: transparent; }
}

.table-warning {
    animation: highlight 2s ease-in-out;
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn .fa-spinner {
    margin-right: 5px;
}

/* Enhanced info boxes */
.info-box {
    transition: all 0.3s ease;
}

.info-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Progress bar animations */
.progress-bar {
    transition: width 0.6s ease;
}

/* Status badge transitions */
.badge {
    transition: all 0.3s ease;
}

/* Button hover effects */
.btn-group .btn {
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Table row hover effects */
.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
}

/* Notification positioning for mobile */
@media (max-width: 768px) {
    .notification-toast {
        left: 10px;
        right: 10px;
        min-width: auto;
        max-width: calc(100% - 20px);
    }
}
</style>
@endpush
@endsection
