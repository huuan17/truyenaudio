@extends('layouts.app')

@section('title', 'Quản lý Chapter')

@push('styles')
<style>
    /* Bulk Actions Styling */
    #bulkActionsBar {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin: 0 15px;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Checkbox Styling */
    .form-check-input {
        border-radius: 4px;
        border: 2px solid #ced4da;
        transition: all 0.3s ease;
    }

    .form-check-input:checked {
        background-color: #6c757d;
        border-color: #6c757d;
        box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
    }

    .form-check-input:indeterminate {
        background-color: #6c757d;
        border-color: #6c757d;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
    }

    /* Table Row Hover */
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Sortable Header Styling */
    .sortable-header {
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
        padding: 8px 12px;
        border-radius: 4px;
    }

    .sortable-header:hover {
        background-color: rgba(0,0,0,0.05);
        text-decoration: none !important;
    }

    .sortable-header i {
        margin-left: 5px;
        font-size: 0.8em;
    }

    /* Selected Row Styling - Using class instead of :has() for better compatibility */
    .table tbody tr.selected-row {
        background-color: #f8f9fa !important;
        border-left: 4px solid #6c757d;
    }

    /* Bulk Action Buttons */
    .btn-group .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .btn-group .btn:first-child {
        border-top-right-radius: 6px;
        border-bottom-right-radius: 6px;
    }

    .btn-group .btn:last-child {
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
    }

    /* Selected Count Badge */
    #selectedCount {
        color: #495057;
        font-weight: bold;
        font-size: 1.1rem;
    }

    /* Loading State */
    .bulk-loading {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 1px solid #ffc107;
    }

    /* Breadcrumb Styling */
    .breadcrumb {
        background: #ffffff;
        border: 1px solid #dee2e6;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: #6c757d;
        font-weight: bold;
    }

    .breadcrumb-item a {
        transition: all 0.3s ease;
        color: #6c757d;
    }

    .breadcrumb-item a:hover {
        color: #495057 !important;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .breadcrumb-item.active {
        font-weight: 600;
        color: #495057;
    }

    /* Chapter Action Buttons */
    .btn-group .btn {
        transition: all 0.3s ease;
        border: none;
        margin-right: 2px;
    }

    .btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .cancel-chapter-tts-btn {
        animation: pulse-warning 2s infinite;
    }

    @keyframes pulse-warning {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
        }
    }

    .start-chapter-tts-btn:hover {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    /* Responsive */
    @media (max-width: 768px) {
        #bulkActionsBar .btn-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        #bulkActionsBar .btn {
            margin-bottom: 5px;
            border-radius: 6px !important;
        }

        #bulkActionsBar .col-md-6 {
            text-align: center !important;
            margin-bottom: 10px;
        }

        .breadcrumb {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .btn-group .btn {
            margin-bottom: 2px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Truyện',
            'url' => route('admin.stories.index')
        ],
        [
            'title' => Str::limit($story->title, 40),
            'url' => route('admin.stories.show', $story->slug)
        ],
        [
            'title' => 'Chapter Management',
            'badge' => $chapters->total() . ' chapter'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0 text-dark">
                        <i class="fas fa-list mr-2 text-primary"></i>Chapter Management - {{ $story->title }}
                    </h3>
                    <p class="mb-0 mt-2 text-muted">
                        Quản lý chapter, TTS và các thao tác hàng loạt
                    </p>
                </div>
            <div class="card-tools">
                <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Quay lại
                </a>
                <a href="{{ route('admin.stories.tts.form', $story) }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-microphone mr-1"></i>TTS Management
                </a>
                <a href="{{ route('admin.stories.scan.form', $story) }}" class="btn btn-sm btn-info">
                    <i class="fas fa-search mr-1"></i>Quét Chapter
                </a>
                <a href="{{ route('admin.chapters.create', ['story_id' => $story->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i>Thêm chương
                </a>
            </div>
        </div>

        <!-- Search Section -->
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.stories.chapters', $story) }}" class="d-flex align-items-center">
                <div class="form-group mb-0 mr-3">
                    <label for="search" class="mr-2 mb-0">Tìm kiếm:</label>
                    <input type="text"
                           name="search"
                           id="search"
                           class="form-control"
                           placeholder="Nhập tiêu đề chapter..."
                           value="{{ request('search') }}"
                           style="width: 300px;">
                </div>

                <!-- Preserve sort parameters -->
                <input type="hidden" name="sort" value="{{ request('sort', 'chapter_number') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">

                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>

                @if(request('search'))
                    <a href="{{ route('admin.stories.chapters', $story) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                @endif
            </form>
        </div>

        <!-- Bulk Actions Bar -->
        <div class="card-body border-bottom" id="bulkActionsBar" style="display: none;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="text-muted">
                        <i class="fas fa-check-square me-2"></i>
                        Đã chọn <strong id="selectedCount">0</strong> chương
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-success" id="bulkTtsBtn">
                            <i class="fas fa-volume-up me-1"></i>TTS hàng loạt
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" id="cancelAllTtsBtn" style="display: none;">
                            <i class="fas fa-stop me-1"></i>Hủy TTS đang chạy
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn">
                            <i class="fas fa-trash me-1"></i>Xóa đã chọn
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" id="clearSelectionBtn">
                            <i class="fas fa-times me-1"></i>Bỏ chọn
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th width="40">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label" for="selectAll"></label>
                            </div>
                        </th>
                        <th>
                            <a href="{{ route('admin.stories.chapters', array_merge(['story' => $story], request()->all(), ['sort' => 'chapter_number', 'direction' => request('sort') == 'chapter_number' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                               class="sortable-header text-decoration-none text-dark">
                                Chương
                                @if(request('sort') == 'chapter_number')
                                    @if(request('direction') == 'asc')
                                        <i class="fas fa-sort-up text-primary"></i>
                                    @else
                                        <i class="fas fa-sort-down text-primary"></i>
                                    @endif
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.stories.chapters', array_merge(['story' => $story], request()->all(), ['sort' => 'title', 'direction' => request('sort') == 'title' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                               class="sortable-header text-decoration-none text-dark">
                                Tiêu đề
                                @if(request('sort') == 'title')
                                    @if(request('direction') == 'asc')
                                        <i class="fas fa-sort-up text-primary"></i>
                                    @else
                                        <i class="fas fa-sort-down text-primary"></i>
                                    @endif
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </a>
                        </th>
                        <th>Trạng thái</th>
                        <th>TTS Status</th>
                        <th>Video Status</th>
                        <th>Audio Player</th>
                        <th>Content</th>
                        <th>Nguồn dữ liệu</th>
                        <th>Kích thước</th>
                        <th>
                            <a href="{{ route('admin.stories.chapters', array_merge(['story' => $story], request()->all(), ['sort' => 'created_at', 'direction' => request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                               class="sortable-header text-decoration-none text-dark">
                                Ngày tạo
                                @if(request('sort') == 'created_at')
                                    @if(request('direction') == 'asc')
                                        <i class="fas fa-sort-up text-primary"></i>
                                    @else
                                        <i class="fas fa-sort-down text-primary"></i>
                                    @endif
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </a>
                        </th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chapters as $chapter)
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input chapter-checkbox" type="checkbox"
                                           value="{{ $chapter->id }}" id="chapter_{{ $chapter->id }}">
                                    <label class="form-check-label" for="chapter_{{ $chapter->id }}"></label>
                                </div>
                            </td>
                            <td>{{ $chapter->chapter_number }}</td>
                            <td>{{ $chapter->title }}</td>
                            <td>
                                @if($chapter->is_crawled)
                                    <span class="badge badge-success">Đã crawl</span>
                                @else
                                    <span class="badge badge-secondary">Thêm thủ công</span>
                                @endif
                            </td>
                            <td>
                                {!! $chapter->tts_status_badge !!}
                                @if($chapter->hasAudio())
                                    <br><small class="text-success">
                                        <i class="fas fa-file-audio"></i>
                                        {{ basename($chapter->audio_file_path) }}
                                    </small>
                                @endif
                            </td>

                            <!-- Video Status Column -->
                            <td>
                                @if($chapter->video)
                                    @switch($chapter->video->render_status)
                                        @case('pending')
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-clock"></i> Chờ render
                                            </span>
                                            @break
                                        @case('processing')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-spinner fa-spin"></i> Đang render
                                            </span>
                                            @break
                                        @case('done')
                                            <span class="badge badge-success">
                                                <i class="fas fa-video"></i> Hoàn thành
                                            </span>
                                            @if($chapter->video->file_path)
                                                <br><small class="text-success">
                                                    <i class="fas fa-file-video"></i>
                                                    {{ basename($chapter->video->file_path) }}
                                                </small>
                                            @endif
                                            @break
                                        @case('error')
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Lỗi
                                            </span>
                                            @break
                                        @default
                                            <span class="badge badge-light">Không xác định</span>
                                    @endswitch
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-minus"></i> Chưa có video
                                    </span>
                                @endif
                            </td>

                            <!-- Audio Player Column -->
                            <td>
                                @if($chapter->hasAudio())
                                    <div class="audio-player-container">
                                        <audio controls preload="none" style="width: 200px; height: 30px;">
                                            <source src="{{ $chapter->audio_url }}" type="audio/mpeg">
                                            Trình duyệt không hỗ trợ audio.
                                        </audio>
                                        <br><small class="text-muted">{{ $chapter->audio_file_name }}</small>
                                    </div>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-volume-mute"></i> Chưa có audio
                                    </small>
                                @endif
                            </td>

                            <!-- Content Column -->
                            <td>
                                @if($chapter->hasReadableContent())
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            data-toggle="modal"
                                            data-target="#contentModal"
                                            data-chapter-id="{{ $chapter->id }}"
                                            data-chapter-number="{{ $chapter->chapter_number }}"
                                            data-chapter-title="{{ $chapter->title }}">
                                        <i class="fas fa-eye"></i> Xem
                                    </button>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-ban"></i> Không có nội dung
                                    </small>
                                @endif
                            </td>

                            <td>
                                @if($chapter->file_path)
                                    <small class="text-muted">
                                        <i class="fas fa-file-alt"></i>
                                        {{ basename($chapter->file_path) }}
                                        @if($chapter->hasContentInDatabase())
                                            <br><span class="badge badge-info badge-sm">DB + File</span>
                                        @else
                                            <br><span class="badge badge-warning badge-sm">Chỉ File</span>
                                        @endif
                                    </small>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-keyboard"></i>
                                        Nhập thủ công
                                        <br><span class="badge badge-primary badge-sm">Database</span>
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($chapter->formatted_file_size)
                                    <small class="text-muted">{{ $chapter->formatted_file_size }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>{{ $chapter->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.chapters.edit', $chapter) }}"
                                       class="btn btn-sm btn-info"
                                       title="Chỉnh sửa chương">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- Dynamic TTS Action Button -->
                                    @if($chapter->canCancelTts())
                                        <!-- Cancel TTS Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-warning cancel-chapter-tts-btn"
                                                data-chapter-id="{{ $chapter->id }}"
                                                data-chapter-title="{{ $chapter->title }}"
                                                title="Hủy TTS cho chương này">
                                            <i class="fas fa-stop"></i>
                                        </button>
                                    @elseif($chapter->canConvertToTts())
                                        <!-- Start TTS Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-success start-chapter-tts-btn"
                                                data-toggle="modal"
                                                data-target="#ttsModal"
                                                data-chapter-id="{{ $chapter->id }}"
                                                data-chapter-number="{{ $chapter->chapter_number }}"
                                                title="Bắt đầu TTS cho chương này">
                                            <i class="fas fa-volume-up"></i>
                                        </button>
                                    @else
                                        <!-- Disabled TTS Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-secondary"
                                                disabled
                                                title="Không thể TTS - chương không có nội dung">
                                            <i class="fas fa-volume-mute"></i>
                                        </button>
                                    @endif

                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.chapters.destroy', $chapter) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa chương này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Xóa chương">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Chưa có chương nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $chapters->appends(request()->query())->links('vendor.pagination.adminlte') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal TTS cho chapter đơn lẻ -->
<div class="modal fade" id="ttsModal" tabindex="-1" role="dialog" aria-labelledby="ttsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ttsModalLabel">Chuyển đổi Chapter thành Audio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="ttsForm" method="POST" action="#">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="voice">Giọng đọc</label>
                        <select name="voice" id="voice" class="form-control" required>
                            <option value="hn_female_ngochuyen_full_48k-fhg">Ngọc Huyền (Nữ - Hà Nội)</option>
                            <option value="hn_male_manhtung_full_48k-fhg">Mạnh Tùng (Nam - Hà Nội)</option>
                            <option value="sg_female_thaotrinh_full_48k-fhg">Thảo Trinh (Nữ - Sài Gòn)</option>
                            <option value="sg_male_minhhoang_full_48k-fhg">Minh Hoàng (Nam - Sài Gòn)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bitrate">Bitrate (kbps)</label>
                                <select name="bitrate" id="bitrate" class="form-control" required>
                                    <option value="64">64 kbps</option>
                                    <option value="128" selected>128 kbps</option>
                                    <option value="192">192 kbps</option>
                                    <option value="256">256 kbps</option>
                                    <option value="320">320 kbps</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="speed">Tốc độ đọc</label>
                                <select name="speed" id="speed" class="form-control" required>
                                    <option value="0.5">0.5x (Chậm)</option>
                                    <option value="0.75">0.75x</option>
                                    <option value="1.0" selected>1.0x (Bình thường)</option>
                                    <option value="1.25">1.25x</option>
                                    <option value="1.5">1.5x</option>
                                    <option value="2.0">2.0x (Nhanh)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" id="ttsSubmitBtn" class="btn btn-success">
                        <i class="fas fa-volume-up"></i> Bắt đầu chuyển đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal TTS cho tất cả chapters -->
<div class="modal fade" id="ttsAllModal" tabindex="-1" role="dialog" aria-labelledby="ttsAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ttsAllModalLabel">Chuyển đổi tất cả Chapters thành Audio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.chapters.tts.all', $story) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Chức năng này sẽ chuyển đổi tất cả các chapters của truyện "{{ $story->title }}" thành audio.
                    </div>

                    <div class="form-group">
                        <label for="voice_all">Giọng đọc</label>
                        <select name="voice" id="voice_all" class="form-control" required>
                            <option value="hn_female_ngochuyen_full_48k-fhg">Ngọc Huyền (Nữ - Hà Nội)</option>
                            <option value="hn_male_phuthang_stor80dt_48k-fhg">Anh Khôi (Nam - Hà Nội)</option>
                            <option value="sg_female_thaotrinh_full_48k-fhg">Thảo Trinh (Nữ - Sài Gòn)</option>
                            <option value="sg_male_minhhoang_full_48k-fhg">Minh Hoàng (Nam - Sài Gòn)</option>
                            <option value="sg_female_tuongvy_call_44k-fhg">Tường Vy (Nữ - Sài Gòn)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bitrate_all">Bitrate (kbps)</label>
                                <select name="bitrate" id="bitrate_all" class="form-control" required>
                                    <option value="64">64 kbps</option>
                                    <option value="128" selected>128 kbps</option>
                                    <option value="192">192 kbps</option>
                                    <option value="256">256 kbps</option>
                                    <option value="320">320 kbps</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="speed_all">Tốc độ đọc</label>
                                <select name="speed" id="speed_all" class="form-control" required>
                                    <option value="0.5">0.5x (Chậm)</option>
                                    <option value="0.75">0.75x</option>
                                    <option value="1.0" selected>1.0x (Bình thường)</option>
                                    <option value="1.25">1.25x</option>
                                    <option value="1.5">1.5x</option>
                                    <option value="2.0">2.0x (Nhanh)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="only_pending" name="only_pending" value="1" checked>
                            <label class="custom-control-label" for="only_pending">
                                Chỉ xử lý chapters chưa chuyển đổi
                            </label>
                            <small class="form-text text-muted">
                                Nếu bỏ tick, tất cả chapters sẽ được xử lý lại (trừ những chapter đang xử lý)
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-volume-up"></i> Bắt đầu chuyển đổi tất cả
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem content chapter -->
<div class="modal fade" id="contentModal" tabindex="-1" role="dialog" aria-labelledby="contentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contentModalLabel">Nội dung Chapter</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="contentLoading" class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải nội dung...
                </div>
                <div id="contentDisplay" style="display: none;">
                    <div class="content-text" style="max-height: 400px; overflow-y: auto; line-height: 1.6; font-size: 14px;">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Xử lý modal TTS cho chapter đơn lẻ
    $('#ttsModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var chapterId = button.data('chapter-id');
        var chapterNumber = button.data('chapter-number');

        if (!chapterId) {
            alert('Lỗi: Không tìm thấy ID chapter');
            return;
        }

        var modal = $(this);
        var actionUrl = '{{ url("/chapters") }}/' + chapterId + '/tts';

        modal.find('.modal-title').text('Chuyển đổi Chapter ' + chapterNumber + ' thành Audio');
        modal.find('#ttsForm').attr('action', actionUrl);
    });

    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Xử lý click button TTS
    $(document).on('click', '#ttsSubmitBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var form = $('#ttsForm');
        var actionUrl = form.attr('action');
        var formData = form.serialize();

        // Kiểm tra action URL
        if (!actionUrl || actionUrl === '#' || actionUrl.indexOf('/tts') === -1) {
            alert('Lỗi: URL action không hợp lệ. Vui lòng thử lại.');
            return false;
        }

        // Disable button để tránh double click
        $(this).prop('disabled', true).text('Đang xử lý...');

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#ttsModal').modal('hide');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (response.message || 'Có lỗi xảy ra'));
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Có lỗi xảy ra';
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch(e) {
                    errorMessage = error || errorMessage;
                }
                alert('Lỗi: ' + errorMessage);
            },
            complete: function() {
                $('#ttsSubmitBtn').prop('disabled', false).html('<i class="fas fa-volume-up"></i> Bắt đầu chuyển đổi');
            }
        });

        return false;
    });



    // Xử lý modal xem content
    $('#contentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var chapterId = button.data('chapter-id');
        var chapterNumber = button.data('chapter-number');
        var chapterTitle = button.data('chapter-title');

        var modal = $(this);
        modal.find('.modal-title').text('Nội dung Chapter ' + chapterNumber + ': ' + chapterTitle);

        // Show loading
        $('#contentLoading').show();
        $('#contentDisplay').hide();

        // Load content via AJAX
        $.ajax({
            url: '{{ route("admin.chapters.content", ":id") }}'.replace(':id', chapterId),
            type: 'GET',
            success: function(response) {
                $('#contentLoading').hide();
                $('#contentDisplay').show();
                $('.content-text').html('<pre style="white-space: pre-wrap; font-family: inherit;">' + response.content + '</pre>');
            },
            error: function(xhr, status, error) {
                $('#contentLoading').hide();
                $('#contentDisplay').show();
                $('.content-text').html('<div class="alert alert-danger">Lỗi khi tải nội dung: ' + error + '</div>');
            }
        });
    });
});

// Bulk Actions JavaScript (Global scope)
function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const chapterCheckboxes = document.querySelectorAll('.chapter-checkbox');

        chapterCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });

        updateBulkActions();
    }

    function updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.chapter-checkbox:checked');
        const selectedCount = selectedCheckboxes.length;
        const totalCheckboxes = document.querySelectorAll('.chapter-checkbox').length;

        // Update select all checkbox state
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            if (selectedCount === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (selectedCount === totalCheckboxes) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Update row highlighting
        document.querySelectorAll('.chapter-checkbox').forEach(checkbox => {
            const row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
        });

        // Show/hide bulk actions bar
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCountSpan = document.getElementById('selectedCount');

        if (selectedCount > 0) {
            bulkActionsBar.style.display = 'block';
            selectedCountSpan.textContent = selectedCount;

            // Check if any selected chapters have TTS in progress or pending
            checkTtsStatusForSelectedChapters(selectedCount);
        } else {
            bulkActionsBar.style.display = 'none';
        }
    }

    function checkTtsStatusForSelectedChapters(selectedCount) {
        const selectedIds = getSelectedChapterIds();
        const cancelAllTtsBtn = document.getElementById('cancelAllTtsBtn');
        let hasActiveTts = false;

        // Check if any selected chapter has TTS in progress or pending
        selectedIds.forEach(chapterId => {
            const statusContainer = document.querySelector(`[data-chapter-id="${chapterId}"]`);
            if (statusContainer) {
                const statusText = statusContainer.textContent.toLowerCase();
                if (statusText.includes('chờ tts') ||
                    statusText.includes('đang xử lý') ||
                    statusContainer.querySelector('.spinner-border') ||
                    statusContainer.querySelector('.progress-bar')) {
                    hasActiveTts = true;
                }
            }
        });

        // Show/hide cancel all TTS button
        if (cancelAllTtsBtn) {
            if (hasActiveTts) {
                cancelAllTtsBtn.style.display = 'inline-block';
                cancelAllTtsBtn.innerHTML = `<i class="fas fa-stop me-1"></i>Hủy TTS đang chạy (${selectedCount})`;
            } else {
                cancelAllTtsBtn.style.display = 'none';
            }
        }

        // Also check for active bulk tasks
        checkActiveBulkTasksForCancelButton(cancelAllTtsBtn, selectedCount);
    }

    function checkActiveBulkTasksForCancelButton(cancelAllTtsBtn, selectedCount) {
        // Check if there are active bulk tasks that might affect selected chapters
        $.ajax({
            url: '{{ route("admin.chapters.tts-status-summary", $story->id) }}',
            method: 'GET',
            success: function(response) {
                if (response.success && response.summary.has_active_tts && cancelAllTtsBtn) {
                    cancelAllTtsBtn.style.display = 'inline-block';
                    cancelAllTtsBtn.innerHTML = `<i class="fas fa-stop me-1"></i>Hủy TTS đang chạy (${selectedCount})`;
                }
            },
            error: function(xhr, status, error) {
                console.log('Failed to check TTS status summary:', error);
            }
        });
    }

    function clearSelection() {
        document.querySelectorAll('.chapter-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('selectAll').checked = false;
        updateBulkActions();
    }

    function getSelectedChapterIds() {
        const selectedCheckboxes = document.querySelectorAll('.chapter-checkbox:checked');
        return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    }

    function bulkTTS() {
        const selectedIds = getSelectedChapterIds();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một chương để thực hiện TTS.');
            return;
        }

        if (!confirm(`Bạn có chắc chắn muốn thực hiện TTS cho ${selectedIds.length} chương đã chọn?`)) {
            return;
        }

        // Show loading state
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        bulkActionsBar.innerHTML = `
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Đang thực hiện TTS cho ${selectedIds.length} chương...
                </div>
            </div>
        `;

        // Send AJAX request
        $.ajax({
            url: '{{ route("admin.chapters.bulk-tts") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                chapter_ids: selectedIds,
                story_id: {{ $story->id }}
            },
            success: function(response) {
                if (response.success) {
                    alert(`Đã bắt đầu TTS cho ${selectedIds.length} chương. Quá trình sẽ chạy trong background.`);
                    location.reload();
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể thực hiện TTS'));
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Có lỗi xảy ra khi thực hiện TTS';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch(e) {
                    errorMessage = error || errorMessage;
                }
                alert('Lỗi: ' + errorMessage);
                location.reload();
            }
        });
    }

    function cancelAllTTS() {
        const selectedIds = getSelectedChapterIds();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một chương để hủy TTS.');
            return;
        }

        if (!confirm(`Bạn có chắc chắn muốn HỦY TTS cho ${selectedIds.length} chương đã chọn?\n\nThao tác này sẽ:\n- Hủy các bulk TTS task đang chạy\n- Reset trạng thái TTS của các chapter\n- Xóa các job đang chờ trong queue`)) {
            return;
        }

        // Show loading state
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        bulkActionsBar.innerHTML = `
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Đang hủy TTS cho ${selectedIds.length} chương...
                </div>
            </div>
        `;

        // Send AJAX request
        $.ajax({
            url: '{{ route("admin.chapters.cancel-all-tts") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                chapter_ids: selectedIds,
                story_id: {{ $story->id }}
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);

                    // Stop TTS monitoring if active
                    if (typeof stopTtsProgressMonitoring === 'function') {
                        stopTtsProgressMonitoring();
                    }
                    if (typeof activeBulkTaskId !== 'undefined') {
                        activeBulkTaskId = null;
                    }
                    if (typeof hideBulkTaskProgress === 'function') {
                        hideBulkTaskProgress();
                    }

                    // Reload page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể hủy TTS'));
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Có lỗi xảy ra khi hủy TTS';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch(e) {
                    errorMessage = error || errorMessage;
                }
                alert('Lỗi: ' + errorMessage);
                location.reload();
            }
        });
    }

    function bulkDelete() {
        const selectedIds = getSelectedChapterIds();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một chương để xóa.');
            return;
        }

        if (!confirm(`⚠️ CẢNH BÁO: Bạn có chắc chắn muốn xóa ${selectedIds.length} chương đã chọn?\n\nHành động này sẽ xóa:\n- Nội dung chương\n- File audio (nếu có)\n- File video (nếu có)\n- Tất cả dữ liệu liên quan\n\nHành động này KHÔNG THỂ HOÀN TÁC!`)) {
            return;
        }

        // Double confirmation for safety
        if (!confirm(`Xác nhận lần cuối: XÓA ${selectedIds.length} CHƯƠNG?`)) {
            return;
        }

        // Show loading state
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        bulkActionsBar.innerHTML = `
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Đang xóa ${selectedIds.length} chương...
                </div>
            </div>
        `;

        // Send AJAX request
        $.ajax({
            url: '{{ route("admin.chapters.bulk-delete") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                chapter_ids: selectedIds,
                story_id: {{ $story->id }}
            },
            success: function(response) {
                if (response.success) {
                    alert(`Đã xóa thành công ${response.deleted_count || selectedIds.length} chương.`);
                    location.reload();
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể xóa chapters'));
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Có lỗi xảy ra khi xóa chapters';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch(e) {
                    errorMessage = error || errorMessage;
                }
                alert('Lỗi: ' + errorMessage);
                location.reload();
            }
        });
    }

// Initialize bulk actions on page load
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listeners
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', toggleSelectAll);
    }

    // Attach event listeners to all chapter checkboxes
    const chapterCheckboxes = document.querySelectorAll('.chapter-checkbox');
    chapterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    // Attach event listeners to bulk action buttons
    const bulkTtsBtn = document.getElementById('bulkTtsBtn');
    if (bulkTtsBtn) {
        bulkTtsBtn.addEventListener('click', bulkTTS);
    }

    const cancelAllTtsBtn = document.getElementById('cancelAllTtsBtn');
    if (cancelAllTtsBtn) {
        cancelAllTtsBtn.addEventListener('click', cancelAllTTS);
    }

    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', bulkDelete);
    }

    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', clearSelection);
    }

    // Initial update
    updateBulkActions();

    // Initialize TTS progress monitoring
    initializeTtsProgressMonitoring();

    // Initialize individual chapter TTS buttons
    initializeChapterTtsButtons();
});

// Individual Chapter TTS Management
function initializeChapterTtsButtons() {
    // Cancel individual chapter TTS buttons
    document.querySelectorAll('.cancel-chapter-tts-btn').forEach(button => {
        button.addEventListener('click', function() {
            const chapterId = this.getAttribute('data-chapter-id');
            const chapterTitle = this.getAttribute('data-chapter-title');
            cancelIndividualChapterTts(chapterId, chapterTitle, this);
        });
    });

    // Start individual chapter TTS buttons (existing modal functionality)
    document.querySelectorAll('.start-chapter-tts-btn').forEach(button => {
        button.addEventListener('click', function() {
            const chapterId = this.getAttribute('data-chapter-id');
            const chapterNumber = this.getAttribute('data-chapter-number');

            // Set form action for TTS modal
            const ttsForm = document.getElementById('ttsForm');
            if (ttsForm) {
                ttsForm.action = `/admin/chapters/${chapterId}/tts`;
            }

            // Update modal title
            const modalTitle = document.getElementById('ttsModalLabel');
            if (modalTitle) {
                modalTitle.textContent = `Chuyển đổi Chương ${chapterNumber} thành Audio`;
            }
        });
    });
}

function cancelIndividualChapterTts(chapterId, chapterTitle, buttonElement) {
    if (!confirm(`Bạn có chắc chắn muốn HỦY TTS cho chương:\n"${chapterTitle}"?\n\nThao tác này sẽ:\n- Gỡ bỏ chapter khỏi hàng đợi TTS\n- Reset trạng thái TTS của chapter\n- Hủy bulk task nếu chỉ có chapter này`)) {
        return;
    }

    // Disable button and show loading
    const originalHtml = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    $.ajax({
        url: `/admin/chapters/${chapterId}/cancel-tts`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');

                // Update button to TTS button
                buttonElement.className = 'btn btn-sm btn-success start-chapter-tts-btn';
                buttonElement.innerHTML = '<i class="fas fa-volume-up"></i>';
                buttonElement.title = 'Bắt đầu TTS cho chương này';
                buttonElement.disabled = false;

                // Update TTS status display
                updateChapterTtsStatusDisplay(chapterId, 'pending');

                // Re-initialize button event listeners
                initializeChapterTtsButtons();

                // Update bulk actions if this chapter was selected
                updateBulkActions();

                // Stop monitoring if this was the last active chapter
                if (response.cancelled_tasks && response.cancelled_tasks.length > 0) {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            } else {
                showNotification('Lỗi: ' + response.message, 'error');
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalHtml;
            }
        },
        error: function(xhr, status, error) {
            let errorMessage = 'Lỗi khi hủy TTS: ' + error;
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showNotification(errorMessage, 'error');
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalHtml;
        }
    });
}

function updateChapterTtsStatusDisplay(chapterId, newStatus) {
    const statusContainer = document.querySelector(`[data-chapter-id="${chapterId}"]`);
    if (statusContainer) {
        let statusHtml = '';

        switch (newStatus) {
            case 'pending':
                statusHtml = '<span class="badge badge-warning"><i class="fas fa-clock me-1"></i>Chờ TTS</span>';
                break;
            case 'processing':
                statusHtml = `
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%">0%</div>
                            </div>
                        </div>
                    </div>`;
                break;
            case 'completed':
                statusHtml = '<span class="badge badge-success"><i class="fas fa-check me-1"></i>Có audio</span>';
                break;
            case 'failed':
                statusHtml = '<span class="badge badge-danger"><i class="fas fa-times me-1"></i>Thất bại</span>';
                break;
        }

        statusContainer.innerHTML = statusHtml;
    }
}

// TTS Progress Monitoring
let ttsProgressInterval = null;
let activeBulkTaskId = null;

function initializeTtsProgressMonitoring() {
    // Check for active bulk TTS tasks
    checkActiveBulkTasks();

    // Start monitoring if there are processing chapters
    const processingChapters = document.querySelectorAll('.tts-status-container[data-chapter-id]');
    if (processingChapters.length > 0) {
        startTtsProgressMonitoring();
    }
}

function checkActiveBulkTasks() {
    $.ajax({
        url: '{{ route("admin.chapters.bulk-tts-tasks", $story->id) }}',
        method: 'GET',
        success: function(response) {
            if (response.success && response.tasks.data.length > 0) {
                const activeTasks = response.tasks.data.filter(task =>
                    task.status === 'pending' || task.status === 'processing'
                );

                if (activeTasks.length > 0) {
                    activeBulkTaskId = activeTasks[0].id;
                    showBulkTaskProgress(activeTasks[0]);
                    startTtsProgressMonitoring();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to check active bulk tasks:', error);
        }
    });
}

function startTtsProgressMonitoring() {
    if (ttsProgressInterval) {
        clearInterval(ttsProgressInterval);
    }

    ttsProgressInterval = setInterval(function() {
        updateTtsProgress();
    }, 3000); // Update every 3 seconds
}

function stopTtsProgressMonitoring() {
    if (ttsProgressInterval) {
        clearInterval(ttsProgressInterval);
        ttsProgressInterval = null;
    }
}

function updateTtsProgress() {
    if (!activeBulkTaskId) {
        stopTtsProgressMonitoring();
        return;
    }

    $.ajax({
        url: `/admin/bulk-tts-tasks/${activeBulkTaskId}/status`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateBulkTaskDisplay(response.task);
                updateChaptersProgress(response.chapters);

                // Stop monitoring if task is completed
                if (response.task.status === 'completed' ||
                    response.task.status === 'failed' ||
                    response.task.status === 'cancelled') {
                    stopTtsProgressMonitoring();
                    activeBulkTaskId = null;
                    hideBulkTaskProgress();

                    // Refresh page after completion
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to update TTS progress:', error);
            if (xhr.status === 404) {
                stopTtsProgressMonitoring();
                activeBulkTaskId = null;
                hideBulkTaskProgress();
            }
        }
    });
}

function updateChaptersProgress(chapters) {
    chapters.forEach(chapter => {
        const container = document.querySelector(`[data-chapter-id="${chapter.id}"]`);
        if (container) {
            updateChapterStatusDisplay(container, chapter);
        }
    });
}

function updateChapterStatusDisplay(container, chapter) {
    const status = chapter.audio_status;
    const progress = chapter.tts_progress || 0;

    let html = '';

    switch (status) {
        case 'processing':
            html = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                 role="progressbar"
                                 style="width: ${progress}%"
                                 data-progress="${progress}">
                                ${progress}%
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 'completed':
            html = '<span class="badge badge-success"><i class="fas fa-check me-1"></i>Có audio</span>';
            break;

        case 'failed':
            html = `
                <div>
                    <span class="badge badge-danger"><i class="fas fa-times me-1"></i>Thất bại</span>
                    ${chapter.tts_error ? `<br><small class="text-danger">${chapter.tts_error}</small>` : ''}
                </div>`;
            break;

        case 'pending':
            html = '<span class="badge badge-warning"><i class="fas fa-clock me-1"></i>Chờ TTS</span>';
            break;
    }

    container.innerHTML = html;
}

function showBulkTaskProgress(task) {
    // Create or update bulk task progress display
    let progressContainer = document.getElementById('bulkTtsProgress');

    if (!progressContainer) {
        progressContainer = document.createElement('div');
        progressContainer.id = 'bulkTtsProgress';
        progressContainer.className = 'alert alert-info mt-3';

        // Insert after the bulk actions bar
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        if (bulkActionsBar) {
            bulkActionsBar.parentNode.insertBefore(progressContainer, bulkActionsBar.nextSibling);
        } else {
            document.querySelector('.card-body').prepend(progressContainer);
        }
    }

    updateBulkTaskDisplay(task);
}

function updateBulkTaskDisplay(task) {
    const progressContainer = document.getElementById('bulkTtsProgress');
    if (!progressContainer) return;

    const statusClass = task.status === 'processing' ? 'alert-info' :
                       task.status === 'completed' ? 'alert-success' :
                       task.status === 'failed' ? 'alert-danger' : 'alert-warning';

    progressContainer.className = `alert ${statusClass} mt-3`;
    progressContainer.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="alert-heading mb-1">
                    <i class="fas fa-tasks me-2"></i>
                    Bulk TTS Progress
                </h6>
                <div class="mb-2">
                    <strong>Trạng thái:</strong> ${task.status_display}
                    ${task.current_chapter_title ? `<br><strong>Đang xử lý:</strong> ${task.current_chapter_title}` : ''}
                </div>
                <div class="progress mb-2" style="height: 25px;">
                    <div class="progress-bar ${task.status === 'processing' ? 'progress-bar-striped progress-bar-animated' : ''}"
                         role="progressbar"
                         style="width: ${task.progress}%">
                        ${task.progress}%
                    </div>
                </div>
                <small class="text-muted">
                    Hoàn thành: ${task.completed_count}/${task.total_chapters} |
                    Thất bại: ${task.failed_count} |
                    ${task.estimated_time_remaining ? `Còn lại: ${task.estimated_time_remaining}` : ''}
                </small>
            </div>
            <div class="btn-group-vertical">
                ${task.status === 'processing' ?
                    `<button class="btn btn-sm btn-warning" onclick="cancelBulkTask(${task.id})">
                        <i class="fas fa-stop me-1"></i>Hủy
                    </button>` : ''}
                ${(task.status === 'failed' || task.status === 'cancelled') ?
                    `<button class="btn btn-sm btn-primary" onclick="restartBulkTask(${task.id})">
                        <i class="fas fa-redo me-1"></i>Thử lại
                    </button>` : ''}
                <button class="btn btn-sm btn-secondary" onclick="hideBulkTaskProgress()">
                    <i class="fas fa-times me-1"></i>Ẩn
                </button>
            </div>
        </div>
    `;
}

function hideBulkTaskProgress() {
    const progressContainer = document.getElementById('bulkTtsProgress');
    if (progressContainer) {
        progressContainer.remove();
    }
}

function cancelBulkTask(taskId) {
    if (!confirm('Bạn có chắc chắn muốn hủy task TTS này?')) {
        return;
    }

    $.ajax({
        url: `/admin/bulk-tts-tasks/${taskId}/cancel`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                stopTtsProgressMonitoring();
                activeBulkTaskId = null;
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Lỗi khi hủy task: ' + error, 'error');
        }
    });
}

function restartBulkTask(taskId) {
    if (!confirm('Bạn có chắc chắn muốn thử lại task TTS này?')) {
        return;
    }

    $.ajax({
        url: `/admin/bulk-tts-tasks/${taskId}/restart`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                activeBulkTaskId = taskId;
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Lỗi khi restart task: ' + error, 'error');
        }
    });
}

function showNotification(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle' :
                     type === 'error' ? 'fa-exclamation-circle' :
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopTtsProgressMonitoring();
});
</script>
@endpush