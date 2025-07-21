@extends('layouts.app')

@section('title', 'Chi tiết truyện')

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
            'badge' => $chapterCount . ' chương'
        ]
    ]" />
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book mr-2"></i>{{ $story->title }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.stories.edit', $story) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                        </a>
                        <a href="{{ route('admin.stories.chapters', $story) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-list mr-1"></i>Quản lý Chapter
                        </a>
                        <a href="{{ route('admin.stories.tts.form', $story) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-microphone mr-1"></i>TTS Management
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            @if($story->cover_image)
                                <img src="{{ asset($story->cover_image) }}" class="img-fluid rounded shadow mb-3" alt="{{ $story->title }}">
                            @else
                                <div class="bg-light text-center p-5 mb-3 rounded">
                                    <i class="fas fa-book fa-3x text-secondary"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 200px"><i class="fas fa-user mr-2"></i>Tác giả</th>
                                        <td>
                                            @if($story->author_id && $story->authorModel)
                                                <a href="{{ route('admin.authors.show', $story->authorModel) }}" class="text-decoration-none">
                                                    {{ $story->authorModel->name }}
                                                </a>
                                            @elseif($story->author)
                                                {{ $story->author }}
                                                <small class="text-warning ml-2">(Chưa liên kết)</small>
                                            @else
                                                <span class="text-muted">Không rõ</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-tags mr-2"></i>Thể loại</th>
                                        <td>
                                            @forelse($story->genres as $genre)
                                                <span class="badge badge-primary mr-1">{{ $genre->name }}</span>
                                            @empty
                                                <span class="text-muted">Chưa phân loại</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-link mr-2"></i>Nguồn</th>
                                        <td>
                                            @if($story->source_url)
                                                <a href="{{ $story->source_url }}" target="_blank" class="text-decoration-none">
                                                    {{ $story->source_url }}
                                                    <i class="fas fa-external-link-alt ml-1"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">Chưa có URL nguồn</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-list-ol mr-2"></i>Chương</th>
                                        <td>
                                            <strong>{{ $story->start_chapter }} - {{ $story->end_chapter }}</strong>
                                            <span class="text-muted ml-2">({{ $chapterCount }}/{{ $story->end_chapter - $story->start_chapter + 1 }} chương)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-download mr-2"></i>Trạng thái crawl</th>
                                        <td>
                                            @php
                                                $statusLabels = config('constants.CRAWL_STATUS.LABELS');
                                                $statusColors = config('constants.CRAWL_STATUS.COLORS');
                                                $statusLabel = $statusLabels[$story->crawl_status] ?? 'Unknown';
                                                $statusColor = $statusColors[$story->crawl_status] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-folder mr-2"></i>Thư mục</th>
                                        <td><code>{{ $story->crawl_path ?? $story->folder_name }}</code></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-eye mr-2"></i>Hiển thị</th>
                                        <td>
                                            <span class="badge {{ $story->visibility_badge_class }}">
                                                {{ $story->visibility_status }}
                                            </span>
                                            <div class="mt-1">
                                                @if($story->is_public)
                                                    <small class="text-success"><i class="fas fa-eye"></i> Public</small>
                                                @else
                                                    <small class="text-muted"><i class="fas fa-eye-slash"></i> Private</small>
                                                @endif
                                                |
                                                @if($story->is_active)
                                                    <small class="text-success"><i class="fas fa-power-off"></i> Active</small>
                                                @else
                                                    <small class="text-danger"><i class="fas fa-pause"></i> Inactive</small>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Generation Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video mr-2"></i>Tạo Video
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.stories.video', $story) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-video mr-1"></i>Tạo Video
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-video"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Video Generation</span>
                                    <span class="info-box-number">Kết hợp ảnh + audio thành video MP4</span>
                                    <div class="mt-2">
                                        <a href="{{ route('admin.stories.video', $story) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-video mr-1"></i>Tạo Video
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-file-video"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Video đã tạo</span>

                                    <span class="info-box-number">{{ $videoCount }} video</span>
                                    <div class="mt-2">
                                        @if($videoCount > 0)
                                            <span class="badge badge-success">Có video</span>
                                        @else
                                            <span class="badge badge-secondary">Chưa có video</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-music"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Audio có sẵn</span>

                                    <span class="info-box-number">{{ $audioCount }} file</span>
                                    <div class="mt-2">
                                        @if($audioCount > 0)
                                            <span class="badge badge-success">Sẵn sàng</span>
                                        @else
                                            <span class="badge badge-warning">Cần TTS</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Hướng dẫn:</strong> Để tạo video, bạn cần có file audio (TTS) và ảnh nền.
                        Sử dụng <strong>TTS Management</strong> để tạo audio và <strong>Chapter Management</strong> để quản lý nội dung.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Chapters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>Chương mới nhất
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.stories.chapters', $story) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-list mr-1"></i>Xem tất cả
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Chương</th>
                                    <th>Tiêu đề</th>
                                    <th>Trạng thái</th>
                                    <th>Audio</th>
                                    <th>Video</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestChapters as $chapter)
                                    <tr>
                                        <td>
                                            <strong>{{ $chapter->chapter_number }}</strong>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 300px;" title="{{ $chapter->title }}">
                                                {{ $chapter->title }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($chapter->is_crawled)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-download"></i> Đã crawl
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-keyboard"></i> Thủ công
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $audioPath = storage_path('app/content/' . $story->folder_name . '/audio/chapter_' . $chapter->chapter_number . '.mp3');
                                                $hasAudio = file_exists($audioPath);
                                            @endphp
                                            @if($hasAudio)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-volume-up"></i> Có
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-volume-mute"></i> Chưa có
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($chapter->video)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-video"></i> Có
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-video-slash"></i> Chưa có
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.chapters.edit', $chapter) }}" class="btn btn-sm btn-info" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($hasAudio)
                                                    <a href="{{ route('admin.stories.video', $story) }}?chapter={{ $chapter->chapter_number }}"
                                                       class="btn btn-sm btn-primary" title="Tạo video">
                                                        <i class="fas fa-video"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-book fa-2x text-muted mb-2"></i>
                                            <div class="text-muted">Chưa có chương nào</div>
                                            <small class="text-muted">Sử dụng Chapter Management để quản lý chương</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection