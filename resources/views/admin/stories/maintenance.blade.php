@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Truyện',
            'url' => route('admin.stories.index')
        ],
        [
            'title' => $story->title,
            'url' => route('admin.stories.show', $story)
        ],
        [
            'title' => 'Maintenance'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i>Story Maintenance - {{ $story->title }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Thống kê tổng quan -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng chương</span>
                                    <span class="info-box-number">{{ $stats['total_chapters'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-file-text"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Có nội dung</span>
                                    <span class="info-box-number">{{ $stats['chapters_with_content'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-volume-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Có audio</span>
                                    <span class="info-box-number">{{ $stats['chapters_with_audio'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">TTS chờ</span>
                                    <span class="info-box-number">{{ $stats['pending_tts'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-cog fa-spin"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">TTS đang xử lý</span>
                                    <span class="info-box-number">{{ $stats['processing_tts'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary"><i class="fas fa-calculator"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Dự kiến</span>
                                    <span class="info-box-number">{{ $stats['expected_chapters'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Actions -->
                    <div class="row">
                        <!-- Chapter Count Issues -->
                        <div class="col-md-6">
                            <div class="card {{ $chapterCountIssue ? 'card-warning' : 'card-success' }}">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calculator mr-2"></i>Kiểm tra số chương
                                    </h3>
                                </div>
                                <div class="card-body">
                                    @if($chapterCountIssue)
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <strong>Phát hiện vấn đề:</strong><br>
                                            Số chương thực tế: <strong>{{ $stats['total_chapters'] }}</strong><br>
                                            Số chương dự kiến: <strong>{{ $stats['expected_chapters'] }}</strong>
                                        </div>
                                        <form action="{{ route('admin.stories.fix-chapter-count', $story) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-wrench mr-1"></i>Sửa số chương
                                            </button>
                                        </form>
                                    @else
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Số chương đã chính xác!
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Crawl Status -->
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-spider mr-2"></i>Cập nhật trạng thái Crawl
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p>Trạng thái hiện tại: 
                                        @if($story->crawl_status == 1)
                                            <span class="badge badge-success">Hoàn thành</span>
                                        @else
                                            <span class="badge badge-warning">Chưa hoàn thành</span>
                                        @endif
                                    </p>
                                    <form action="{{ route('admin.stories.update-crawl-status', $story) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info">
                                            <i class="fas fa-sync mr-1"></i>Cập nhật trạng thái
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TTS Management -->
                    <div class="row mt-3">
                        <!-- Pending TTS -->
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-clock mr-2"></i>TTS đang chờ
                                    </h3>
                                </div>
                                <div class="card-body">
                                    @if($stats['pending_tts'] > 0)
                                        <p>Có <strong>{{ $stats['pending_tts'] }}</strong> TTS requests đang chờ xử lý.</p>
                                        <form action="{{ route('admin.stories.cancel-pending-tts', $story) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary" onclick="return confirm('Bạn có chắc muốn hủy tất cả TTS đang chờ?')">
                                                <i class="fas fa-times mr-1"></i>Hủy TTS chờ
                                            </button>
                                        </form>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Không có TTS requests nào đang chờ.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Stuck TTS -->
                        <div class="col-md-6">
                            <div class="card {{ $stuckTTS->count() > 0 ? 'card-danger' : 'card-success' }}">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>TTS bị stuck
                                    </h3>
                                </div>
                                <div class="card-body">
                                    @if($stuckTTS->count() > 0)
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            Phát hiện <strong>{{ $stuckTTS->count() }}</strong> TTS requests bị stuck (xử lý quá 30 phút).
                                        </div>
                                        <form action="{{ route('admin.stories.reset-stuck-tts', $story) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn reset TTS bị stuck?')">
                                                <i class="fas fa-redo mr-1"></i>Reset TTS stuck
                                            </button>
                                        </form>
                                    @else
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Không có TTS requests nào bị stuck.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($stuckTTS->count() > 0)
                    <!-- Stuck TTS Details -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card card-danger">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-list mr-2"></i>Chi tiết TTS bị stuck
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Chapter</th>
                                                    <th>Bắt đầu TTS</th>
                                                    <th>Thời gian stuck</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($stuckTTS as $chapter)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('admin.chapters.show', $chapter) }}" class="text-decoration-none">
                                                            Chapter {{ $chapter->chapter_number }}: {{ Str::limit($chapter->title, 50) }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $chapter->tts_started_at ? $chapter->tts_started_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                                    <td>
                                                        @if($chapter->tts_started_at)
                                                            <span class="badge badge-danger">
                                                                {{ $chapter->tts_started_at->diffForHumans() }}
                                                            </span>
                                                        @else
                                                            N/A
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
