@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/sortable.css') }}">
<style>
    /* Purple button style */
    .btn-purple {
        color: #fff;
        background-color: #6f42c1;
        border-color: #6f42c1;
    }

    .btn-purple:hover {
        color: #fff;
        background-color: #5a32a3;
        border-color: #5a32a3;
    }

    .btn-purple:focus, .btn-purple.focus {
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.5);
    }

    /* Action buttons layout */
    .action-buttons {
        min-width: 200px;
    }

    .action-buttons .btn-group {
        display: flex;
        width: 100%;
        margin-bottom: 2px;
        border-radius: 4px;
        overflow: hidden;
    }

    .action-buttons .btn-group:last-child {
        margin-bottom: 0;
    }

    .action-buttons .btn {
        flex: 1;
        font-size: 11px;
        padding: 4px 6px;
        border-radius: 0;
        border-right: 1px solid rgba(255,255,255,0.2);
        transition: all 0.2s ease;
    }

    .action-buttons .btn:last-child {
        border-right: none;
    }

    .action-buttons .btn:hover {
        transform: translateY(-1px);
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Button group specific styling */
    .action-buttons .btn-group:first-child .btn {
        background-color: rgba(0,123,255,0.1);
    }

    .action-buttons .btn-group:nth-child(2) .btn {
        background-color: rgba(255,193,7,0.1);
    }

    .action-buttons .btn-group:last-child .btn {
        background-color: rgba(220,53,69,0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-buttons .btn-group {
            flex-direction: column;
        }

        .action-buttons .btn {
            margin-bottom: 2px;
            border-radius: 4px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Truyện',
            'badge' => ($stories->total() ?? 0) . ' truyện'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book mr-2"></i>Quản lý Truyện
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.stories.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i>Thêm truyện
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Filter Buttons -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.stories.index') }}"
                                   class="btn btn-outline-secondary {{ !request('filter') ? 'active' : '' }}">
                                    <i class="fas fa-list mr-1"></i>Tất cả ({{ $totalCount ?? $stories->total() }})
                                </a>
                                <a href="{{ route('admin.stories.index', ['filter' => 'visible']) }}"
                                   class="btn btn-outline-success {{ request('filter') == 'visible' ? 'active' : '' }}">
                                    <i class="fas fa-eye mr-1"></i>Hiển thị ({{ $visibleCount ?? 0 }})
                                </a>
                                <a href="{{ route('admin.stories.index', ['filter' => 'hidden']) }}"
                                   class="btn btn-outline-warning {{ request('filter') == 'hidden' ? 'active' : '' }}">
                                    <i class="fas fa-eye-slash mr-1"></i>Ẩn ({{ $hiddenCount ?? 0 }})
                                </a>
                                <a href="{{ route('admin.stories.index', ['filter' => 'inactive']) }}"
                                   class="btn btn-outline-danger {{ request('filter') == 'inactive' ? 'active' : '' }}">
                                    <i class="fas fa-pause mr-1"></i>Tạm dừng ({{ $inactiveCount ?? 0 }})
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <form method="GET" class="d-flex">
                                <input type="hidden" name="filter" value="{{ request('filter') }}">
                                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm truyện..."
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-outline-primary ml-2">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <x-sortable-header route="admin.stories.index" column="title" title="Tiêu đề & Tác giả" />
                <th>Chương</th>
                <th>Crawl Management</th>
                <th>TTS Management</th>
                <th>Hiển thị</th>
                <th width="200">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stories as $story)
            <tr>
                <td>
                    <div>
                        <strong>
                            <a href="{{ route('admin.stories.show', $story) }}" class="text-decoration-none text-dark">
                                {{ $story->title }}
                            </a>
                        </strong>
                        <br>
                        @if($story->author_id && $story->authorModel)
                            <small>
                                <i class="fas fa-user text-primary"></i>
                                <a href="{{ route('admin.authors.show', $story->authorModel) }}"
                                class="text-decoration-none">
                                    {{ $story->authorModel->name }}
                                </a>
                            </small>
                        @elseif($story->author)
                            <small>
                                <i class="fas fa-user text-muted"></i>
                                <span class="text-muted">{{ $story->author }}</span>
                                <span class="badge badge-warning">Chưa liên kết</span>
                            </small>
                        @else
                            <small>
                                <i class="fas fa-user text-muted"></i>
                                <span class="text-muted">Chưa có tác giả</span>
                            </small>
                        @endif
                    </div>
                </td>
                <td>
                    <strong>{{ $story->start_chapter }} → {{ $story->end_chapter }}</strong>
                    <br>
                    <small class="text-muted">
                        Tổng: {{ $story->end_chapter - $story->start_chapter + 1 }} chương
                    </small>
                    <br>
                    <small class="text-info">
                        <strong>DB:</strong> <span class="chapter-count" data-story-id="{{ $story->id }}">{{ $story->chapters()->count() }}</span> chương
                    </small>
                </td>
                <td>
                    @php
                        $statusLabels = config('constants.CRAWL_STATUS.LABELS');
                        $statusColors = config('constants.CRAWL_STATUS.COLORS');
                        $statusLabel = $statusLabels[$story->crawl_status] ?? 'Unknown';
                        $statusColor = $statusColors[$story->crawl_status] ?? 'secondary';
                    @endphp

                    <!-- Status Badge -->
                    <div class="mb-2">
                        <span class="badge badge-{{ $statusColor }} crawl-status" data-story-id="{{ $story->id }}">
                            {{ $statusLabel }}
                        </span>

                        <!-- Missing Chapters Warning -->
                        @if($story->hasMissingChaptersAtSource())
                            <div class="mt-1">
                                <span class="badge badge-warning" title="{{ $story->getMissingChaptersDisplayText() }}">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    {{ $story->missing_chapters['count'] }} chương không tồn tại
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Crawl Actions -->
                    <div class="btn-group-vertical crawl-actions" role="group" data-story-id="{{ $story->id }}" style="width: 100%;">
                        @if($story->crawl_status == config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED') || $story->crawl_status == config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'))
                            <!-- Smart Crawl Button -->
                            <form action="{{ route('admin.stories.smart-crawl', $story) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="btn btn-sm btn-success btn-block" title="Crawl chương thiếu">
                                    <i class="fas fa-download"></i> Smart Crawl
                                </button>
                            </form>
                        @elseif($story->crawl_status == config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                            <!-- Cancel & Remove Buttons -->
                            <div class="btn-group mb-1" role="group" style="width: 100%;">
                                <form action="{{ route('admin.stories.cancel-crawl', $story) }}" method="POST" style="display: inline; flex: 1;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning" style="width: 100%;"
                                            onclick="return confirm('Bạn có chắc muốn hủy crawl?')" title="Hủy crawl">
                                        <i class="fas fa-stop"></i> Cancel
                                    </button>
                                </form>
                            </div>
                            <div class="btn-group" role="group" style="width: 100%;">
                                <form action="{{ route('admin.stories.remove-from-queue', $story) }}" method="POST" style="display: inline; flex: 1;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" style="width: 100%;"
                                            onclick="return confirm('Bạn có chắc muốn xóa khỏi queue?')" title="Xóa khỏi queue">
                                        <i class="fas fa-times"></i> Remove Queue
                                    </button>
                                </form>
                            </div>
                        @elseif($story->crawl_status == config('constants.CRAWL_STATUS.VALUES.CRAWLED'))
                            <!-- Crawled Successfully -->
                            <div class="text-center">
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Đã crawl xong
                                </span>
                                <br>
                                <small class="text-muted mt-1 d-block">{{ $story->chapters_count }} chương</small>
                            </div>
                        @elseif($story->crawl_status == config('constants.CRAWL_STATUS.VALUES.FAILED'))
                            <!-- Failed - Allow Re-crawl -->
                            <form action="{{ route('admin.stories.smart-crawl', $story) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="btn btn-sm btn-danger btn-block" title="Crawl lại do thất bại">
                                    <i class="fas fa-exclamation-triangle"></i> Retry Crawl
                                </button>
                            </form>
                        @else
                            <!-- Other statuses - Smart Crawl -->
                            <form action="{{ route('admin.stories.smart-crawl', $story) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-success btn-block" title="Smart crawl">
                                    <i class="fas fa-redo"></i> Smart Crawl
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
                <td>
                    @php
                        $ttsProgress = $story->getTtsProgress();
                        $ttsStatusLabels = config('constants.TTS_STATUS.LABELS');
                        $ttsStatusColors = config('constants.TTS_STATUS.COLORS');
                        $ttsStatusLabel = $ttsStatusLabels[$ttsProgress['status']] ?? 'Unknown';
                        $ttsStatusColor = $ttsStatusColors[$ttsProgress['status']] ?? 'secondary';
                    @endphp

                    <!-- TTS Status Badge -->
                    <div class="mb-2">
                        <span class="badge badge-{{ $ttsStatusColor }}" title="Trạng thái TTS">
                            {{ $ttsStatusLabel }}
                        </span>
                        @if($ttsProgress['total'] > 0)
                            <br>
                            <small class="text-muted">
                                {{ $ttsProgress['completed'] }}/{{ $ttsProgress['total'] }}
                                ({{ $ttsProgress['progress_percentage'] }}%)
                            </small>
                        @endif
                    </div>

                    <!-- TTS Actions -->
                    <div class="btn-group-vertical" role="group" style="width: 100%;">
                        @if($ttsProgress['status'] == config('constants.TTS_STATUS.VALUES.NOT_STARTED') ||
                            $ttsProgress['status'] == config('constants.TTS_STATUS.VALUES.FAILED') ||
                            $ttsProgress['status'] == config('constants.TTS_STATUS.VALUES.PARTIAL'))
                            <!-- TTS Button -->
                            <a href="{{ route('admin.stories.tts.form', $story) }}" class="btn btn-sm btn-primary btn-block" title="Chuyển đổi Text-to-Speech">
                                <i class="fas fa-microphone"></i> TTS
                            </a>
                        @elseif($ttsProgress['status'] == config('constants.TTS_STATUS.VALUES.PROCESSING') ||
                                $ttsProgress['status'] == config('constants.TTS_STATUS.VALUES.PENDING'))
                            <!-- Cancel TTS Button -->
                            <button type="button" class="btn btn-sm btn-warning btn-block" title="Hủy TTS đang chạy" onclick="cancelTts({{ $story->id }})">
                                <i class="fas fa-stop"></i> Cancel TTS
                            </button>
                        @else
                            <!-- Re-TTS Button -->
                            <a href="{{ route('admin.stories.tts.form', $story) }}" class="btn btn-sm btn-outline-primary btn-block" title="TTS lại">
                                <i class="fas fa-redo"></i> Re-TTS
                            </a>
                        @endif
                    </div>
                </td>
                <td>
                    <!-- Visibility Toggle Button -->
                    <div class="mb-2">
                        <form action="{{ route('admin.stories.toggle-public', $story) }}" method="POST" style="display: inline;">
                            @csrf
                            @if($story->is_public)
                                <button type="submit" class="btn btn-sm btn-success" title="Click để ẩn khỏi website">
                                    <i class="fas fa-eye"></i> Public
                                </button>
                            @else
                                <button type="submit" class="btn btn-sm btn-secondary" title="Click để hiển thị trên website">
                                    <i class="fas fa-eye-slash"></i> Private
                                </button>
                            @endif
                        </form>
                    </div>

                    <!-- Active Status -->
                    <div>
                        @if($story->is_active)
                            <small class="text-success"><i class="fas fa-power-off"></i> Active</small>
                        @else
                            <small class="text-danger"><i class="fas fa-pause"></i> Inactive</small>
                        @endif
                    </div>
                </td>
                <td class="action-buttons">
                    <!-- Primary Actions -->
                    <div class="btn-group" role="group" title="Quản lý cơ bản">
                        <a href="{{ route('admin.stories.edit', $story) }}" class="btn btn-sm btn-primary" title="Chỉnh sửa thông tin truyện">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="{{ route('admin.stories.chapters', $story) }}" class="btn btn-sm btn-success" title="Quản lý chapters">
                            <i class="fas fa-list"></i>
                        </a>
                        <form action="{{ route('admin.stories.update-status', $story) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-purple" title="Cập nhật trạng thái">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Processing Actions -->
                    <div class="btn-group" role="group" title="Xử lý nội dung">
                        <a href="{{ route('admin.stories.scan.form', $story) }}" class="btn btn-sm btn-warning" title="Quét chapters từ storage">
                            <i class="fas fa-search"></i>
                        </a>
                        <a href="{{ route('admin.stories.video', $story) }}" class="btn btn-sm btn-purple" title="Tạo video từ audio">
                            <i class="fas fa-video"></i>
                        </a>
                    </div>

                    <!-- Danger Zone -->
                    <div class="btn-group" role="group" title="Thao tác nguy hiểm">
                        <form action="{{ route('admin.stories.destroy', $story) }}" method="POST" style="display: inline; width: 100%;" onsubmit="return confirm('⚠️ Bạn có chắc muốn xóa truyện này?\n\nHành động này sẽ xóa:\n- Tất cả chapters\n- File audio và video\n- Dữ liệu liên quan\n\nKhông thể hoàn tác!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Xóa truyện (không thể hoàn tác)" style="width: 100%;">
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
                    <div class="d-flex justify-content-center">
                        {{ $stories->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Real-time status and chapter count updates
function updateStoryStatus() {
    const storyIds = [];
    document.querySelectorAll('.crawl-status[data-story-id]').forEach(element => {
        storyIds.push(element.getAttribute('data-story-id'));
    });

    if (storyIds.length === 0) return;

    fetch('{{ route("admin.stories.status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ story_ids: storyIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.stories.forEach(story => {
                // Update crawl status
                const statusElement = document.querySelector(`.crawl-status[data-story-id="${story.id}"]`);
                if (statusElement) {
                    statusElement.textContent = story.status_label;
                    statusElement.className = `badge badge-${story.status_color} crawl-status`;
                    statusElement.setAttribute('data-story-id', story.id);

                    // Cancel button logic is now handled in updateCrawlActions
                }

                // Update chapter count
                const countElement = document.querySelector(`.chapter-count[data-story-id="${story.id}"]`);
                if (countElement) {
                    countElement.textContent = story.chapter_count;
                }

                // Update crawl actions based on status
                updateCrawlActions(story);
            });
        }
    })
    .catch(error => {
        console.log('Status update error:', error);
    });
}

// Helper function to update crawl actions
function updateCrawlActions(story) {
    const actionsDiv = document.querySelector(`.crawl-actions[data-story-id="${story.id}"]`);
    if (!actionsDiv) return;

    let actionsHtml = '';

    if (story.crawl_status == 0 || story.crawl_status == 5) { // NOT_CRAWLED or RE_CRAWL
        actionsHtml = `
            <form action="/admin/stories/${story.slug}/smart-crawl" method="POST" style="display: inline;">
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-sm btn-success btn-block" title="Crawl chương thiếu">
                    <i class="fas fa-download"></i> Smart Crawl
                </button>
            </form>
        `;
    } else if (story.crawl_status == 3) { // CRAWLING
        actionsHtml = `
            <div class="btn-group mb-1" role="group" style="width: 100%;">
                <form action="/admin/stories/${story.slug}/cancel-crawl" method="POST" style="display: inline; flex: 1;">
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                    <button type="submit" class="btn btn-sm btn-warning" style="width: 100%;"
                            onclick="return confirm('Bạn có chắc muốn hủy crawl?')" title="Hủy crawl">
                        <i class="fas fa-stop"></i> Cancel
                    </button>
                </form>
            </div>
            <div class="btn-group" role="group" style="width: 100%;">
                <form action="/admin/stories/${story.slug}/remove-from-queue" method="POST" style="display: inline; flex: 1;">
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                    <button type="submit" class="btn btn-sm btn-danger" style="width: 100%;"
                            onclick="return confirm('Bạn có chắc muốn xóa khỏi queue?')" title="Xóa khỏi queue">
                        <i class="fas fa-times"></i> Remove Queue
                    </button>
                </form>
            </div>
        `;
    } else if (story.crawl_status == 2) { // CRAWLED
        actionsHtml = `
            <div class="text-center">
                <span class="badge badge-success">
                    <i class="fas fa-check-circle"></i> Đã crawl xong
                </span>
                <br>
                <small class="text-muted mt-1 d-block">${story.chapter_count || 0} chương</small>
            </div>
        `;
    } else if (story.crawl_status == 4) { // FAILED
        actionsHtml = `
            <form action="/admin/stories/${story.slug}/smart-crawl" method="POST" style="display: inline;">
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-sm btn-danger btn-block" title="Crawl lại do thất bại">
                    <i class="fas fa-exclamation-triangle"></i> Retry Crawl
                </button>
            </form>
        `;
    } else { // Other statuses
        actionsHtml = `
            <form action="/admin/stories/${story.slug}/smart-crawl" method="POST" style="display: inline;">
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-sm btn-outline-success btn-block" title="Smart crawl">
                    <i class="fas fa-redo"></i> Smart Crawl
                </button>
            </form>
        `;
    }

    actionsDiv.innerHTML = actionsHtml;
}

// Helper function removed (pending jobs count)

// Cancel TTS function
function cancelTts(storyId) {
    if (!confirm('Bạn có chắc muốn hủy TTS đang chạy?')) {
        return;
    }

    fetch(`/admin/stories/${storyId}/cancel-tts`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Đã hủy TTS thành công!');
            // Refresh the page to update status
            location.reload();
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể hủy TTS'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi hủy TTS');
    });
}

// Update every 5 seconds
setInterval(updateStoryStatus, 5000);

// Update immediately on page load
document.addEventListener('DOMContentLoaded', updateStoryStatus);

// Update when page becomes visible (user switches back to tab)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        updateStoryStatus();
    }
});
</script>
@endpush
@endsection
