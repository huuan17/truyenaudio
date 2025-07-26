@extends('layouts.app')

@section('title', 'Chi tiết Video')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.videos.index') }}">Quản lý Video</a></li>
                    <li class="breadcrumb-item active">Chi tiết Video</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $video->title }}</h1>
        </div>
        <div>
            <a href="{{ route('admin.videos.edit', $video) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Chỉnh sửa
            </a>
            <a href="{{ route('admin.videos.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Video Preview -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-play-circle"></i> Xem trước Video
                    </h5>
                </div>
                <div class="card-body">
                    @if($video->fileExists())
                        <div class="video-container text-center">
                            <video controls style="max-width: 100%; max-height: 500px;" class="rounded">
                                <source src="{{ $video->preview_url }}" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ video HTML5.
                            </video>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <a href="{{ $video->download_url }}" class="btn btn-success">
                                <i class="fas fa-download"></i> Tải xuống Video
                            </a>
                            <button type="button" class="btn btn-info preview-video-btn"
                                    data-video-url="{{ $video->preview_url }}"
                                    data-video-title="{{ $video->title }}"
                                    data-download-url="{{ $video->download_url }}">
                                <i class="fas fa-expand mr-2"></i>Xem toàn màn hình
                            </button>
                        </div>
                    @else
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>File video không tồn tại</h5>
                            <p>File video có thể đã bị xóa hoặc di chuyển.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Video Information -->
        <div class="col-lg-4">
            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Thông tin cơ bản
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Platform:</strong></td>
                            <td>
                                <span class="badge bg-{{ $video->platform === 'tiktok' ? 'dark' : 'danger' }}">
                                    {{ strtoupper($video->platform) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Loại media:</strong></td>
                            <td>{{ ucfirst($video->media_type) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
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
                        </tr>
                        <tr>
                            <td><strong>Thời lượng:</strong></td>
                            <td>{{ $video->duration_human }}</td>
                        </tr>
                        <tr>
                            <td><strong>Kích thước:</strong></td>
                            <td>{{ $video->file_size_human }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tên file:</strong></td>
                            <td><code>{{ $video->file_name }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Ngày tạo:</strong></td>
                            <td>{{ $video->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @if($video->scheduled_at)
                        <tr>
                            <td><strong>Lịch đăng:</strong></td>
                            <td>{{ $video->scheduled_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @endif
                        @if($video->published_at)
                        <tr>
                            <td><strong>Đã đăng:</strong></td>
                            <td>{{ $video->published_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Task Info -->
            @if($video->task)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tasks"></i> Thông tin Task
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Task ID:</strong></td>
                            <td><code>#{{ $video->task->id }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td>
                                @php
                                    $taskStatusColors = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $taskStatusColors[$video->task->status] ?? 'secondary' }}">
                                    {{ ucfirst($video->task->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tiến độ:</strong></td>
                            <td>{{ $video->task->progress }}%</td>
                        </tr>
                        <tr>
                            <td><strong>Thời gian tạo:</strong></td>
                            <td>{{ $video->task->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @if($video->task->completed_at)
                        <tr>
                            <td><strong>Hoàn thành:</strong></td>
                            <td>{{ $video->task->completed_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif

            <!-- Description -->
            @if($video->description)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-align-left"></i> Mô tả
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $video->description }}</p>
                </div>
            </div>
            @endif

            <!-- Generation Metadata -->
            @if($video->metadata)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs"></i> Thông số tạo video
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="metadataAccordion">
                        @if(isset($video->metadata['script']) && $video->metadata['script'])
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#script-content">
                                    Script Content
                                </button>
                            </h2>
                            <div id="script-content" class="accordion-collapse collapse" data-bs-parent="#metadataAccordion">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded">{{ $video->metadata['script'] }}</pre>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($video->metadata['text']) && $video->metadata['text'])
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#text-content">
                                    Text Content
                                </button>
                            </h2>
                            <div id="text-content" class="accordion-collapse collapse" data-bs-parent="#metadataAccordion">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded">{{ $video->metadata['text'] }}</pre>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technical-params">
                                    Thông số kỹ thuật
                                </button>
                            </h2>
                            <div id="technical-params" class="accordion-collapse collapse" data-bs-parent="#metadataAccordion">
                                <div class="accordion-body">
                                    <table class="table table-sm">
                                        @if(isset($video->metadata['voice']))
                                        <tr>
                                            <td><strong>Voice:</strong></td>
                                            <td>{{ $video->metadata['voice'] }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($video->metadata['speed']))
                                        <tr>
                                            <td><strong>Speed:</strong></td>
                                            <td>{{ $video->metadata['speed'] }}x</td>
                                        </tr>
                                        @endif
                                        @if(isset($video->metadata['volume']))
                                        <tr>
                                            <td><strong>Volume:</strong></td>
                                            <td>{{ $video->metadata['volume'] }}dB</td>
                                        </tr>
                                        @endif
                                        @if(isset($video->metadata['bitrate']))
                                        <tr>
                                            <td><strong>Bitrate:</strong></td>
                                            <td>{{ $video->metadata['bitrate'] }}kbps</td>
                                        </tr>
                                        @endif
                                        @if(isset($video->metadata['use_logo']))
                                        <tr>
                                            <td><strong>Logo:</strong></td>
                                            <td>{{ $video->metadata['use_logo'] ? 'Có' : 'Không' }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($video->metadata['subtitle_text']) && $video->metadata['subtitle_text'])
                                        <tr>
                                            <td><strong>Subtitle:</strong></td>
                                            <td>{{ $video->metadata['subtitle_text'] }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if(isset($video->metadata['images']) && $video->metadata['images'])
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#images-used">
                                    Hình ảnh sử dụng
                                </button>
                            </h2>
                            <div id="images-used" class="accordion-collapse collapse" data-bs-parent="#metadataAccordion">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded">{{ $video->metadata['images'] }}</pre>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Video Preview Modal -->
<x-video-preview-modal id="videoPreviewModal" />
@endsection
