@extends('layouts.app')

@section('title', 'Giám sát TTS')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Truyện',
            'url' => route('admin.stories.index')
        ],
        [
            'title' => 'Giám sát TTS',
            'url' => null
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-headphones mr-2"></i>
                        Giám sát TTS
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addStoryModal">
                            <i class="fas fa-plus mr-1"></i>
                            Thêm truyện vào TTS
                        </button>
                    </div>
                </div>
                <div class="card-body">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="stats-total">{{ $stats['total'] }}</h3>
                        <p>Tổng số chapters</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="stats-completed">{{ $stats['completed'] }}</h3>
                        <p>Đã hoàn thành</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="stats-processing">{{ $stats['processing'] }}</h3>
                        <p>Đang xử lý</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3 id="stats-pending">{{ $stats['pending'] }}</h3>
                        <p>Đang chờ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="stats-failed">{{ $stats['failed'] }}</h3>
                        <p>Thất bại</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-light">
                    <div class="inner">
                        <h3 id="stats-none">{{ $stats['none'] }}</h3>
                        <p>Chưa TTS</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-minus-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Queue Status -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tasks mr-1"></i>
                            Trạng thái Queue
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Jobs đang chờ</span>
                                        <span class="info-box-number" id="queue-pending">{{ $queueStats['pending_jobs'] }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Jobs thất bại</span>
                                        <span class="info-box-number" id="queue-failed">{{ $queueStats['failed_jobs'] }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-cogs"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tổng số jobs</span>
                                        <span class="info-box-number" id="queue-total">{{ $queueStats['queue_size'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12 text-center">
                                <button id="btn-clear-failed" class="btn btn-danger">
                                    <i class="fas fa-trash mr-1"></i> Xóa tất cả jobs thất bại
                                </button>
                                <button id="btn-refresh-status" class="btn btn-info ml-2">
                                    <i class="fas fa-sync mr-1"></i> Làm mới trạng thái
                                </button>
                                <span id="last-updated" class="ml-3 text-muted">
                                    Cập nhật lần cuối: {{ now()->format('H:i:s d/m/Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Details -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h4 class="text-primary">
                                <i class="fas fa-list"></i>
                                <span id="total-queue-jobs">{{ $queueStats['queue_size'] }}</span>
                            </h4>
                            <small>Tổng jobs trong queue</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning">
                                <i class="fas fa-clock"></i>
                                <span id="header-pending-count">{{ $queueStats['pending_jobs'] }}</span>
                            </h4>
                            <small>Đang chờ xử lý</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-success">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span id="header-running-count">{{ $queueStats['running_jobs'] ?? 0 }}</span>
                            </h4>
                            <small>Đang xử lý</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-danger">
                                <i class="fas fa-times"></i>
                                <span id="header-failed-count">{{ $queueStats['failed_jobs'] }}</span>
                            </h4>
                            <small>Thất bại</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Jobs -->
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning">
                        <h3 class="card-title text-dark">
                            <i class="fas fa-clock mr-1"></i>
                            Jobs đang chờ
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-dark" id="pending-jobs-count">{{ $queueStats['pending_jobs'] }}</span>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <div id="pending-jobs-list">
                            @if(isset($pendingJobs) && $pendingJobs->count() > 0)
                                @foreach($pendingJobs as $job)
                                    <div class="job-item border-bottom pb-2 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $job->story_name ?? 'N/A' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    Chapter: {{ $job->chapter_title ?? 'N/A' }}
                                                </small>
                                                <br>
                                                <small class="text-info">
                                                    Tạo lúc: {{ $job->created_at ? $job->created_at->format('H:i:s d/m/Y') : 'N/A' }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    Chờ: {{ $job->created_at ? $job->created_at->diffForHumans() : 'N/A' }}
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Đang chờ
                                                </span>
                                                @if($job->attempts > 0)
                                                    <br>
                                                    <small class="text-warning">Thử lại: {{ $job->attempts }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Không có job nào đang chờ</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Running Jobs -->
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success">
                        <h3 class="card-title text-white">
                            <i class="fas fa-spinner fa-spin mr-1"></i>
                            Jobs đang chạy
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-light" id="running-jobs-count">{{ $queueStats['running_jobs'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <div id="running-jobs-list">
                            @if(isset($runningJobs) && $runningJobs->count() > 0)
                                @foreach($runningJobs as $job)
                                    <div class="job-item border-bottom pb-2 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $job->story_name ?? 'N/A' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    Chapter: {{ $job->chapter_title ?? 'N/A' }}
                                                </small>
                                                <br>
                                                <small class="text-success">
                                                    Bắt đầu: {{ $job->reserved_at ? \Carbon\Carbon::parse($job->reserved_at)->format('H:i:s d/m/Y') : 'N/A' }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-stopwatch"></i>
                                                    Chạy: {{ $job->reserved_at ? \Carbon\Carbon::parse($job->reserved_at)->diffForHumans() : 'N/A' }}
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-spinner fa-spin"></i> Đang chạy
                                                </span>
                                                @if($job->attempts > 0)
                                                    <br>
                                                    <small class="text-success">Thử lại: {{ $job->attempts }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-play-circle fa-2x mb-2"></i>
                                    <p>Không có job nào đang chạy</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stories with TTS Progress -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book-open mr-1"></i>
                            Truyện có TTS
                        </h3>
                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" id="story-search" class="form-control float-right" placeholder="Tìm kiếm truyện...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Buttons -->
                        <div class="mb-3">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Filter truyện">
                                <button type="button" class="btn btn-outline-secondary active" data-filter="all">
                                    <i class="fas fa-list"></i> Tất cả
                                </button>
                                <button type="button" class="btn btn-outline-success" data-filter="completed">
                                    <i class="fas fa-check-circle"></i> Hoàn thành
                                </button>
                                <button type="button" class="btn btn-outline-warning" data-filter="processing">
                                    <i class="fas fa-spinner"></i> Đang xử lý
                                </button>
                                <button type="button" class="btn btn-outline-info" data-filter="pending">
                                    <i class="fas fa-clock"></i> Đang chờ
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-filter="failed">
                                    <i class="fas fa-times-circle"></i> Có lỗi
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-filter="none">
                                    <i class="fas fa-minus-circle"></i> Chưa bắt đầu
                                </button>
                            </div>
                            <div class="float-right">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-stories">
                                    <i class="fas fa-sync"></i> Làm mới
                                </button>
                            </div>
                        </div>

                        @if($storiesWithTts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Truyện</th>
                                            <th>Tác giả</th>
                                            <th>Tổng chương</th>
                                            <th>Tiến độ TTS</th>
                                            <th>Trạng thái</th>
                                            <th>Hoạt động cuối</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($storiesWithTts as $story)
                                            @php
                                                $totalChapters = $story->total_chapters;
                                                $completedChapters = $story->completed_chapters;
                                                $processingChapters = $story->processing_chapters;
                                                $pendingChapters = $story->pending_chapters;
                                                $failedChapters = $story->failed_chapters;
                                                $noneChapters = $story->none_chapters;

                                                $progressPercentage = $totalChapters > 0 ? round(($completedChapters / $totalChapters) * 100, 1) : 0;

                                                // Determine overall status
                                                if ($processingChapters > 0) {
                                                    $statusClass = 'warning';
                                                    $statusText = 'Đang xử lý';
                                                    $statusIcon = 'fas fa-spinner fa-spin';
                                                } elseif ($pendingChapters > 0) {
                                                    $statusClass = 'info';
                                                    $statusText = 'Đang chờ';
                                                    $statusIcon = 'fas fa-clock';
                                                } elseif ($completedChapters === $totalChapters) {
                                                    $statusClass = 'success';
                                                    $statusText = 'Hoàn thành';
                                                    $statusIcon = 'fas fa-check-circle';
                                                } elseif ($failedChapters > 0 && $completedChapters > 0) {
                                                    $statusClass = 'warning';
                                                    $statusText = 'Một phần';
                                                    $statusIcon = 'fas fa-exclamation-triangle';
                                                } elseif ($failedChapters > 0) {
                                                    $statusClass = 'danger';
                                                    $statusText = 'Thất bại';
                                                    $statusIcon = 'fas fa-times-circle';
                                                } else {
                                                    $statusClass = 'secondary';
                                                    $statusText = 'Chưa bắt đầu';
                                                    $statusIcon = 'fas fa-minus-circle';
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $story->title }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $story->folder_name }}</small>
                                                    </div>
                                                </td>
                                                <td>{{ $story->author ?: 'Chưa có' }}</td>
                                                <td>
                                                    <div>
                                                        <span class="badge badge-primary">{{ $totalChapters }}</span>
                                                        @if($story->audio_files_count > 0)
                                                            <br>
                                                            <small class="text-success">
                                                                <i class="fas fa-file-audio"></i> {{ $story->audio_files_count }} files
                                                            </small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="progress mb-1" style="height: 20px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                             style="width: {{ $progressPercentage }}%"
                                                             aria-valuenow="{{ $progressPercentage }}"
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            {{ $progressPercentage }}%
                                                        </div>
                                                    </div>
                                                    <div class="row text-center">
                                                        <div class="col">
                                                            <small class="text-success">
                                                                <i class="fas fa-check"></i> {{ $completedChapters }}
                                                            </small>
                                                        </div>
                                                        @if($processingChapters > 0)
                                                        <div class="col">
                                                            <small class="text-warning">
                                                                <i class="fas fa-spinner"></i> {{ $processingChapters }}
                                                            </small>
                                                        </div>
                                                        @endif
                                                        @if($pendingChapters > 0)
                                                        <div class="col">
                                                            <small class="text-info">
                                                                <i class="fas fa-clock"></i> {{ $pendingChapters }}
                                                            </small>
                                                        </div>
                                                        @endif
                                                        @if($failedChapters > 0)
                                                        <div class="col">
                                                            <small class="text-danger">
                                                                <i class="fas fa-times"></i> {{ $failedChapters }}
                                                            </small>
                                                        </div>
                                                        @endif
                                                        @if($noneChapters > 0)
                                                        <div class="col">
                                                            <small class="text-muted">
                                                                <i class="fas fa-minus"></i> {{ $noneChapters }}
                                                            </small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $statusClass }}">
                                                        <i class="{{ $statusIcon }}"></i> {{ $statusText }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($story->last_tts_activity)
                                                        <small>{{ \Carbon\Carbon::parse($story->last_tts_activity)->diffForHumans() }}</small>
                                                    @else
                                                        <small class="text-muted">Chưa có</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('admin.stories.chapters', ['story' => $story->slug]) }}"
                                                           class="btn btn-outline-primary btn-sm"
                                                           title="Xem chapters">
                                                            <i class="fas fa-list"></i>
                                                        </a>
                                                        @if($noneChapters > 0 || $failedChapters > 0)
                                                        <button type="button"
                                                                class="btn btn-outline-success btn-sm btn-convert-tts"
                                                                data-story-id="{{ $story->id }}"
                                                                data-story-title="{{ $story->title }}"
                                                                title="Chuyển đổi TTS">
                                                            <i class="fas fa-microphone"></i>
                                                        </button>
                                                        @endif
                                                        @if($processingChapters > 0 || $pendingChapters > 0)
                                                        <button type="button"
                                                                class="btn btn-outline-warning btn-sm btn-cancel-tts"
                                                                data-story-id="{{ $story->id }}"
                                                                data-story-title="{{ $story->title }}"
                                                                title="Hủy TTS">
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
                            <div class="d-flex justify-content-center">
                                {{ $storiesWithTts->links() }}
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-book fa-3x mb-3"></i>
                                <h5>Chưa có truyện nào có TTS</h5>
                                <p>Hãy thêm truyện vào TTS để bắt đầu chuyển đổi</p>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addStoryModal">
                                    <i class="fas fa-plus mr-1"></i>
                                    Thêm truyện vào TTS
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TTS Conversion Modal -->
<div class="modal fade" id="ttsConversionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chuyển đổi TTS cho truyện: <span id="tts-story-title"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="tts-conversion-form">
                    <input type="hidden" id="tts-story-id" name="story_id">

                    <div class="form-group">
                        <label>Cài đặt TTS</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tts-voice">Giọng đọc</label>
                                    <select name="voice" id="tts-voice" class="form-control">
                                        <option value="">Sử dụng mặc định của truyện</option>
                                        <option value="hn_female_ngochuyen_full_48k-fhg">Ngọc Huyền (Nữ - Hà Nội)</option>
                                        <option value="hn_male_phuthang_stor80dt_48k-fhg">Anh Khôi (Nam - Hà Nội)</option>
                                        <option value="sg_female_thaotrinh_full_48k-fhg">Thảo Trinh (Nữ - Sài Gòn)</option>
                                        <option value="sg_male_minhhoang_full_48k-fhg">Minh Hoàng (Nam - Sài Gòn)</option>
                                        <option value="sg_female_tuongvy_call_44k-fhg">Tường Vy (Nữ - Sài Gòn)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tts-bitrate">Bitrate</label>
                                    <select name="bitrate" id="tts-bitrate" class="form-control">
                                        <option value="">Sử dụng mặc định của truyện</option>
                                        <option value="64">64 kbps</option>
                                        <option value="128">128 kbps</option>
                                        <option value="192">192 kbps</option>
                                        <option value="256">256 kbps</option>
                                        <option value="320">320 kbps</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tts-speed">Tốc độ đọc</label>
                                    <select name="speed" id="tts-speed" class="form-control">
                                        <option value="">Sử dụng mặc định của truyện</option>
                                        <option value="0.5">0.5x (Chậm)</option>
                                        <option value="1.0">1.0x (Bình thường)</option>
                                        <option value="1.5">1.5x (Nhanh)</option>
                                        <option value="2.0">2.0x (Rất nhanh)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tts-volume">Âm lượng</label>
                                    <select name="volume" id="tts-volume" class="form-control">
                                        <option value="">Sử dụng mặc định của truyện</option>
                                        <option value="1.0">100% (Bình thường)</option>
                                        <option value="1.5">150% (To)</option>
                                        <option value="2.0">200% (Rất to)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Giới hạn chapters</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tts-start-from">Bắt đầu từ chapter</label>
                                    <input type="number" name="start_from" id="tts-start-from" class="form-control" min="1" placeholder="Ví dụ: 1">
                                    <small class="form-text text-muted">Để trống để bắt đầu từ chapter đầu tiên</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tts-limit-chapters">Giới hạn số chapter</label>
                                    <input type="number" name="limit_chapters" id="tts-limit-chapters" class="form-control" min="1" placeholder="Ví dụ: 10">
                                    <small class="form-text text-muted">Để trống để chuyển đổi tất cả chapters</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="tts-force" name="force">
                            <label class="custom-control-label" for="tts-force">Ghi đè chapters đã có audio</label>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Hệ thống sẽ thêm chapters vào queue và xử lý tuần tự.
                        Quá trình này có thể mất thời gian tùy thuộc vào số lượng chapters và độ dài nội dung.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btn-submit-tts">
                    <i class="fas fa-microphone"></i> Bắt đầu chuyển đổi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Modal -->
<div class="modal fade" id="recentActivityModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hoạt động gần đây</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="recent-activity-table">
                        <thead>
                            <tr>
                                <th>Truyện</th>
                                <th>Chapter</th>
                                <th>Trạng thái</th>
                                <th>Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();
    
    // Auto refresh status every 30 seconds
    const refreshInterval = 30000; // 30 seconds
    let refreshTimer = setInterval(refreshStatus, refreshInterval);

    // Story search functionality
    $('#story-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterStories();
    });

    // Filter buttons
    $('[data-filter]').on('click', function() {
        $('[data-filter]').removeClass('active');
        $(this).addClass('active');
        filterStories();
    });

    // Refresh stories button
    $('#refresh-stories').on('click', function() {
        location.reload();
    });

    // Filter stories function
    function filterStories() {
        const searchTerm = $('#story-search').val().toLowerCase();
        const activeFilter = $('[data-filter].active').data('filter');

        $('tbody tr').each(function() {
            const $row = $(this);
            const storyTitle = $row.find('td:first strong').text().toLowerCase();
            const author = $row.find('td:nth-child(2)').text().toLowerCase();

            // Check search term
            const matchesSearch = searchTerm === '' ||
                                storyTitle.includes(searchTerm) ||
                                author.includes(searchTerm);

            // Check filter
            let matchesFilter = true;
            if (activeFilter !== 'all') {
                const statusBadge = $row.find('.badge').last();
                const statusText = statusBadge.text().toLowerCase();

                switch(activeFilter) {
                    case 'completed':
                        matchesFilter = statusText.includes('hoàn thành');
                        break;
                    case 'processing':
                        matchesFilter = statusText.includes('đang xử lý');
                        break;
                    case 'pending':
                        matchesFilter = statusText.includes('đang chờ');
                        break;
                    case 'failed':
                        matchesFilter = statusText.includes('thất bại') || statusText.includes('một phần');
                        break;
                    case 'none':
                        matchesFilter = statusText.includes('chưa bắt đầu');
                        break;
                }
            }

            // Show/hide row
            if (matchesSearch && matchesFilter) {
                $row.show();
            } else {
                $row.hide();
            }
        });

        // Update visible count
        const visibleRows = $('tbody tr:visible').length;
        const totalRows = $('tbody tr').length;

        if (visibleRows === 0 && totalRows > 0) {
            if (!$('#no-results-message').length) {
                $('tbody').append(`
                    <tr id="no-results-message">
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Không tìm thấy truyện nào phù hợp</p>
                        </td>
                    </tr>
                `);
            }
        } else {
            $('#no-results-message').remove();
        }
    }
    
    // Manual refresh button
    $('#btn-refresh-status').click(function() {
        refreshStatus();
    });
    
    // Clear failed jobs
    $('#btn-clear-failed').click(function() {
        if (confirm('Bạn có chắc chắn muốn xóa tất cả jobs thất bại?')) {
            $.ajax({
                url: '{{ route("admin.tts-monitor.clear-failed") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    refreshStatus();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra');
                }
            });
        }
    });
    
    // Convert TTS
    $(document).on('click', '.btn-convert-tts', function() {
        const storyId = $(this).data('story-id');
        const storyTitle = $(this).data('story-title');

        $('#tts-story-id').val(storyId);
        $('#tts-story-title').text(storyTitle);
        $('#ttsConversionModal').modal('show');
    });

    // Submit TTS conversion
    $('#btn-submit-tts').click(function() {
        const storyId = $('#tts-story-id').val();
        const formData = $('#tts-conversion-form').serialize();

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');

        $.ajax({
            url: `/admin/stories/${storyId}/convert-tts`,
            type: 'POST',
            data: formData + '&_token=' + '{{ csrf_token() }}',
            success: function(response) {
                toastr.success(response.message);
                $('#ttsConversionModal').modal('hide');
                refreshStatus();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra');
            },
            complete: function() {
                $('#btn-submit-tts').prop('disabled', false).html('<i class="fas fa-microphone"></i> Bắt đầu chuyển đổi');
            }
        });
    });

    // Cancel TTS for story
    $(document).on('click', '.btn-cancel-tts', function() {
        const storyId = $(this).data('story-id');
        const storyTitle = $(this).data('story-title');

        if (confirm(`Bạn có chắc chắn muốn hủy tất cả TTS đang xử lý cho truyện "${storyTitle}"?`)) {
            $.ajax({
                url: `/admin/stories/${storyId}/cancel-tts`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    refreshStatus();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra');
                }
            });
        }
    });
    
    // Cancel TTS for chapter
    $(document).on('click', '.btn-cancel-chapter-tts', function() {
        const chapterId = $(this).data('chapter-id');
        const chapterTitle = $(this).data('chapter-title');

        if (confirm(`Bạn có chắc chắn muốn hủy TTS cho chapter "${chapterTitle}"?`)) {
            $.ajax({
                url: `/admin/tts-monitor/chapters/${chapterId}/cancel`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    refreshStatus();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra');
                }
            });
        }
    });

    // Retry TTS for chapter
    $(document).on('click', '.btn-retry-chapter-tts', function() {
        const chapterId = $(this).data('chapter-id');
        const chapterTitle = $(this).data('chapter-title');

        if (confirm(`Bạn có chắc chắn muốn thử lại TTS cho chapter "${chapterTitle}"?`)) {
            $.ajax({
                url: `/admin/tts-monitor/chapters/${chapterId}/retry`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    refreshStatus();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra');
                }
            });
        }
    });
    
    // Function to refresh status
    function refreshStatus() {
        $.ajax({
            url: '{{ route("admin.tts-monitor.status") }}',
            type: 'GET',
            success: function(data) {
                updateStats(data.stats);
                updateQueueStats(data.queue);
                updatePendingJobs(data.pending_jobs);
                updateRunningJobs(data.running_jobs);
                updateLastUpdated(data.timestamp);
                updateRecentActivity(data.recent_activity);
            },
            error: function(xhr) {
                console.error('Error refreshing status:', xhr);
            }
        });
    }
    
    // Update statistics
    function updateStats(stats) {
        $('#stats-total').text(stats.total);
        $('#stats-completed').text(stats.completed);
        $('#stats-processing').text(stats.processing);
        $('#stats-pending').text(stats.pending);
        $('#stats-failed').text(stats.failed);
        $('#stats-none').text(stats.none);
    }
    
    // Update queue statistics
    function updateQueueStats(queue) {
        $('#queue-pending').text(queue.pending_jobs);
        $('#queue-failed').text(queue.failed_jobs);
        $('#queue-total').text(queue.queue_size);

        // Update job counts in headers
        $('#pending-jobs-count').text(queue.pending_jobs);
        $('#running-jobs-count').text(queue.running_jobs || 0);

        // Update header summary
        $('#total-queue-jobs').text(queue.queue_size);
        $('#header-pending-count').text(queue.pending_jobs);
        $('#header-running-count').text(queue.running_jobs || 0);
        $('#header-failed-count').text(queue.failed_jobs);
    }

    // Update pending jobs list
    function updatePendingJobs(pendingJobs) {
        const $container = $('#pending-jobs-list');
        $container.empty();

        if (pendingJobs && pendingJobs.length > 0) {
            pendingJobs.forEach(job => {
                const createdAt = new Date(job.created_at).toLocaleString('vi-VN');
                const jobHtml = `
                    <div class="job-item border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${job.story_name || 'N/A'}</strong>
                                <br>
                                <small class="text-muted">
                                    Chapter: ${job.chapter_title || 'N/A'}
                                </small>
                                <br>
                                <small class="text-info">
                                    Tạo lúc: ${createdAt}
                                </small>
                            </div>
                            <div>
                                <span class="badge badge-warning">Đang chờ</span>
                            </div>
                        </div>
                    </div>
                `;
                $container.append(jobHtml);
            });
        } else {
            $container.html(`
                <div class="text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Không có job nào đang chờ</p>
                </div>
            `);
        }
    }

    // Update running jobs list
    function updateRunningJobs(runningJobs) {
        const $container = $('#running-jobs-list');
        $container.empty();

        if (runningJobs && runningJobs.length > 0) {
            runningJobs.forEach(job => {
                const reservedAt = new Date(job.reserved_at).toLocaleString('vi-VN');
                const jobHtml = `
                    <div class="job-item border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${job.story_name || 'N/A'}</strong>
                                <br>
                                <small class="text-muted">
                                    Chapter: ${job.chapter_title || 'N/A'}
                                </small>
                                <br>
                                <small class="text-success">
                                    Bắt đầu: ${reservedAt}
                                </small>
                            </div>
                            <div>
                                <span class="badge badge-success">
                                    <i class="fas fa-spinner fa-spin"></i> Đang chạy
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                $container.append(jobHtml);
            });
        } else {
            $container.html(`
                <div class="text-center text-muted">
                    <i class="fas fa-play-circle fa-3x mb-3"></i>
                    <p>Không có job nào đang chạy</p>
                </div>
            `);
        }
    }

    // Update last updated timestamp
    function updateLastUpdated(timestamp) {
        const date = new Date(timestamp);
        const formattedDate = `${padZero(date.getHours())}:${padZero(date.getMinutes())}:${padZero(date.getSeconds())} ${padZero(date.getDate())}/${padZero(date.getMonth() + 1)}/${date.getFullYear()}`;
        $('#last-updated').text(`Cập nhật lần cuối: ${formattedDate}`);
    }
    
    // Update recent activity
    function updateRecentActivity(activities) {
        const $table = $('#recent-activity-table tbody');
        $table.empty();
        
        activities.forEach(activity => {
            let statusBadge = '';
            
            if (activity.status === 'completed') {
                statusBadge = '<span class="badge badge-success">Hoàn thành</span>';
            } else if (activity.status === 'processing') {
                statusBadge = '<span class="badge badge-warning">Đang xử lý</span>';
            } else if (activity.status === 'pending') {
                statusBadge = '<span class="badge badge-secondary">Đang chờ</span>';
            } else if (activity.status === 'failed') {
                statusBadge = '<span class="badge badge-danger">Thất bại</span>';
            } else if (activity.status === 'cancelled') {
                statusBadge = '<span class="badge badge-dark">Đã hủy</span>';
            } else {
                statusBadge = '<span class="badge badge-light">Chưa TTS</span>';
            }
            
            $table.append(`
                <tr>
                    <td>${activity.story_title}</td>
                    <td>${activity.chapter_title} <small class="text-muted">(Chapter ${activity.chapter_number})</small></td>
                    <td>${statusBadge}</td>
                    <td>${activity.updated_at}</td>
                </tr>
            `);
        });
    }
    
    // Helper function to pad zero
    function padZero(num) {
        return num.toString().padStart(2, '0');
    }

    // Story search functionality
    let searchTimeout;
    $('#story_search').on('input', function() {
        const query = $(this).val();

        if (query.length < 2) {
            $('#selected_story').html('<option value="">-- Chọn truyện --</option>');
            $('#story_info').hide();
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchStories(query);
        }, 300);
    });

    function searchStories(query) {
        $.ajax({
            url: '{{ route("admin.stories.search") }}',
            method: 'GET',
            data: { q: query },
            success: function(response) {
                const select = $('#selected_story');
                select.html('<option value="">-- Chọn truyện --</option>');

                response.forEach(story => {
                    select.append(`<option value="${story.id}" data-story='${JSON.stringify(story)}'>${story.title} (${story.chapters_count} chương)</option>`);
                });
            },
            error: function() {
                toastr.error('Lỗi khi tìm kiếm truyện');
            }
        });
    }

    // Handle conversion type change
    $('#conversion_type').on('change', function() {
        const type = $(this).val();

        if (type === 'range') {
            $('#from_chapter_group, #to_chapter_group').show();
            $('#from_chapter, #to_chapter').prop('required', true);
        } else if (type === 'specific') {
            $('#from_chapter_group').show();
            $('#to_chapter_group').hide();
            $('#from_chapter').prop('required', true);
            $('#to_chapter').prop('required', false);
            $('#from_chapter').attr('placeholder', 'Số chương');
        } else {
            $('#from_chapter_group, #to_chapter_group').hide();
            $('#from_chapter, #to_chapter').prop('required', false);
        }

        updateStoryInfo();
    });

    // Show story info when selected
    $('#selected_story').on('change', function() {
        updateStoryInfo();
    });

    function updateStoryInfo() {
        const selectedOption = $('#selected_story').find('option:selected');
        const storyData = selectedOption.data('story');

        if (storyData) {
            const story = storyData;
            const conversionType = $('#conversion_type').val();
            const fromChapter = parseInt($('#from_chapter').val()) || 1;
            const toChapter = parseInt($('#to_chapter').val()) || story.chapters_count;

            let chaptersToProcess = 0;
            let description = '';

            switch (conversionType) {
                case 'pending_only':
                    chaptersToProcess = story.chapters_count - (story.audio_chapters_count || 0);
                    description = 'Chỉ xử lý các chương chưa có audio';
                    break;
                case 'all':
                    chaptersToProcess = story.chapters_count;
                    description = 'Xử lý tất cả chương (bỏ qua chương đã có audio)';
                    break;
                case 'range':
                    if (fromChapter && toChapter) {
                        chaptersToProcess = Math.max(0, toChapter - fromChapter + 1);
                        description = `Xử lý chương ${fromChapter} đến ${toChapter}`;
                    }
                    break;
                case 'specific':
                    if (fromChapter) {
                        chaptersToProcess = 1;
                        description = `Xử lý chương ${fromChapter}`;
                    }
                    break;
            }

            $('#story_details').html(`
                <div class="row">
                    <div class="col-md-6">
                        <strong>Tác giả:</strong> ${story.author || 'Chưa có'}<br>
                        <strong>Tổng chương:</strong> ${story.chapters_count}<br>
                        <strong>Đã có audio:</strong> ${story.audio_chapters_count || 0}
                    </div>
                    <div class="col-md-6">
                        <strong>Chưa có audio:</strong> ${story.chapters_count - (story.audio_chapters_count || 0)}<br>
                        <strong>Sẽ xử lý:</strong> <span class="text-primary">${chaptersToProcess} chương</span><br>
                        <small class="text-muted">${description}</small>
                    </div>
                </div>
            `);

            // Load default TTS settings from story
            if (story.default_tts_voice) {
                $('#tts_voice').val(story.default_tts_voice);
            }
            if (story.default_tts_bitrate) {
                $('#tts_bitrate').val(story.default_tts_bitrate);
            }
            if (story.default_tts_speed) {
                $('#tts_speed').val(story.default_tts_speed);
            }
            if (story.default_tts_volume) {
                $('#tts_volume').val(story.default_tts_volume);
            }

            $('#story_info').show();
        } else {
            $('#story_info').hide();
        }
    }

    // Update info when chapter range changes
    $('#from_chapter, #to_chapter').on('input', function() {
        updateStoryInfo();
    });

    // Add story to TTS
    $('#btn-add-story-tts').on('click', function() {
        const storyId = $('#selected_story').val();
        const voice = $('#tts_voice').val();
        const bitrate = $('#tts_bitrate').val();
        const speed = $('#tts_speed').val();
        const volume = $('#tts_volume').val();
        const conversionType = $('#conversion_type').val();
        const fromChapter = $('#from_chapter').val();
        const toChapter = $('#to_chapter').val();

        if (!storyId) {
            toastr.error('Vui lòng chọn truyện');
            return;
        }

        // Validate chapter range
        if (conversionType === 'range') {
            if (!fromChapter || !toChapter) {
                toastr.error('Vui lòng nhập khoảng chương');
                return;
            }
            if (parseInt(fromChapter) > parseInt(toChapter)) {
                toastr.error('Chương bắt đầu không thể lớn hơn chương kết thúc');
                return;
            }
        } else if (conversionType === 'specific') {
            if (!fromChapter) {
                toastr.error('Vui lòng nhập số chương');
                return;
            }
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...');

        $.ajax({
            url: '{{ route("admin.tts-monitor.add-story") }}',
            method: 'POST',
            data: {
                story_id: storyId,
                voice: voice,
                bitrate: bitrate,
                speed: speed,
                volume: volume,
                conversion_type: conversionType,
                from_chapter: fromChapter,
                to_chapter: toChapter,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success(response.message);
                $('#addStoryModal').modal('hide');

                // Reset form
                $('#addStoryForm')[0].reset();
                $('#selected_story').html('<option value="">-- Chọn truyện --</option>');
                $('#story_info').hide();
                $('#story_search').val('');
                $('#from_chapter_group, #to_chapter_group').hide();
                $('#conversion_type').val('pending_only');

                // Refresh data
                loadData();
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Có lỗi xảy ra');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Thêm vào TTS');
            }
        });
    });
});
</script>

<!-- Add Story to TTS Modal -->
<div class="modal fade" id="addStoryModal" tabindex="-1" role="dialog" aria-labelledby="addStoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStoryModalLabel">
                    <i class="fas fa-plus mr-2"></i>Thêm truyện vào TTS
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addStoryForm">
                    <div class="form-group">
                        <label for="story_search">Tìm kiếm truyện</label>
                        <input type="text" class="form-control" id="story_search" placeholder="Nhập tên truyện để tìm kiếm...">
                        <small class="form-text text-muted">Gõ ít nhất 2 ký tự để tìm kiếm</small>
                    </div>

                    <div class="form-group">
                        <label for="selected_story">Chọn truyện</label>
                        <select class="form-control" id="selected_story" name="story_id" required>
                            <option value="">-- Chọn truyện --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="chapter_range">Số chương cần chuyển đổi</label>
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control" id="conversion_type" name="conversion_type">
                                    <option value="pending_only">Chỉ chương chưa có audio</option>
                                    <option value="all">Tất cả chương</option>
                                    <option value="range">Khoảng chương</option>
                                    <option value="specific">Chương cụ thể</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="from_chapter_group" style="display: none;">
                                <input type="number" class="form-control" id="from_chapter" name="from_chapter" placeholder="Từ chương" min="1">
                            </div>
                            <div class="col-md-4" id="to_chapter_group" style="display: none;">
                                <input type="number" class="form-control" id="to_chapter" name="to_chapter" placeholder="Đến chương" min="1">
                            </div>
                        </div>
                        <small class="form-text text-muted">Hệ thống sẽ tự động bỏ qua các chương đã có audio</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tts_voice">Giọng đọc</label>
                                <select class="form-control" id="tts_voice" name="voice">
                                    <option value="hn_female_ngochuyen_full_48k-fhg">Ngọc Huyền (Nữ - Hà Nội)</option>
                                    <option value="hn_male_phuthang_stor80dt_48k-fhg">Anh Khôi (Nam - Hà Nội)</option>
                                    <option value="sg_female_thaotrinh_full_48k-fhg">Thảo Trinh (Nữ - Sài Gòn)</option>
                                    <option value="sg_male_minhhoang_full_48k-fhg">Minh Hoàng (Nam - Sài Gòn)</option>
                                    <option value="sg_female_tuongvy_call_44k-fhg">Tường Vy (Nữ - Sài Gòn)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tts_bitrate">Bitrate</label>
                                <select class="form-control" id="tts_bitrate" name="bitrate">
                                    <option value="128">128 kbps</option>
                                    <option value="192">192 kbps</option>
                                    <option value="256">256 kbps</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tts_speed">Tốc độ đọc</label>
                                <select class="form-control" id="tts_speed" name="speed">
                                    <option value="0.5">0.5x (Chậm)</option>
                                    <option value="1.0" selected>1.0x (Bình thường)</option>
                                    <option value="1.5">1.5x (Nhanh)</option>
                                    <option value="2.0">2.0x (Rất nhanh)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tts_volume">Âm lượng</label>
                                <select class="form-control" id="tts_volume" name="volume">
                                    <option value="1.0" selected>100%</option>
                                    <option value="1.5">150%</option>
                                    <option value="2.0">200%</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="story_info" class="alert alert-info" style="display: none;">
                        <h6><i class="fas fa-info-circle mr-1"></i>Thông tin truyện</h6>
                        <div id="story_details"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btn-add-story-tts">
                    <i class="fas fa-plus mr-1"></i>Thêm vào TTS
                </button>
            </div>
        </div>
    </div>
</div>

@endpush
