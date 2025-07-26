@extends('layouts.app')

@section('title', 'Chỉnh sửa Video')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.videos.index') }}">Quản lý Video</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.videos.show', $video) }}">{{ Str::limit($video->title, 30) }}</a></li>
                    <li class="breadcrumb-item active">Chỉnh sửa</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Chỉnh sửa Video</h1>
        </div>
        <div>
            <a href="{{ route('admin.videos.show', $video) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Edit Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit"></i> Thông tin Video
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.videos.update', $video) }}">
                        @csrf
                        @method('PUT')

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $video->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4">{{ old('description', $video->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="generated" {{ old('status', $video->status) === 'generated' ? 'selected' : '' }}>Đã tạo</option>
                                <option value="scheduled" {{ old('status', $video->status) === 'scheduled' ? 'selected' : '' }}>Đã lên lịch</option>
                                <option value="published" {{ old('status', $video->status) === 'published' ? 'selected' : '' }}>Đã đăng</option>
                                <option value="failed" {{ old('status', $video->status) === 'failed' ? 'selected' : '' }}>Lỗi</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Scheduled At -->
                        <div class="mb-3" id="scheduled-at-group" style="{{ old('status', $video->status) === 'scheduled' ? '' : 'display: none;' }}">
                            <label for="scheduled_at" class="form-label">Thời gian đăng</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror"
                                   id="scheduled_at" name="scheduled_at"
                                   value="{{ old('scheduled_at', $video->scheduled_at ? $video->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                                   min="{{ now()->format('Y-m-d\TH:i') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Chỉ áp dụng khi trạng thái là "Đã lên lịch"</div>
                        </div>

                        <!-- Channel Publishing Settings -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Cài đặt đăng kênh</h6>
                            </div>
                            <div class="card-body">
                                <!-- Publish to Channel -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="publish_to_channel" name="publish_to_channel" value="1"
                                               {{ old('publish_to_channel', $video->publish_to_channel) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="publish_to_channel">
                                            Đăng video lên kênh
                                        </label>
                                    </div>
                                    <div class="form-text">Bật tính năng này để video được đăng lên kênh đã chọn</div>
                                </div>

                                <!-- Channel Selection -->
                                <div class="mb-3" id="channel-group" style="{{ old('publish_to_channel', $video->publish_to_channel) ? '' : 'display: none;' }}">
                                    <label for="channel_id" class="form-label">Chọn kênh</label>
                                    <select class="form-select @error('channel_id') is-invalid @enderror" id="channel_id" name="channel_id">
                                        <option value="">Chọn kênh</option>
                                        @foreach($channels as $channel)
                                            <option value="{{ $channel->id }}" {{ old('channel_id', $video->channel_id) == $channel->id ? 'selected' : '' }}>
                                                {{ $channel->name }} ({{ ucfirst($channel->platform) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('channel_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Auto Publish -->
                                <div class="mb-3" id="auto-publish-group" style="{{ old('publish_to_channel', $video->publish_to_channel) ? '' : 'display: none;' }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="auto_publish" name="auto_publish" value="1"
                                               {{ old('auto_publish', $video->auto_publish) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_publish">
                                            Tự động đăng kênh
                                        </label>
                                    </div>
                                    <div class="form-text">Video sẽ được tự động đăng lên kênh khi trạng thái chuyển thành "Đã đăng"</div>
                                </div>

                                <!-- Channel Status Info -->
                                @if($video->publish_to_channel)
                                    <div class="alert alert-info">
                                        <strong>Trạng thái đăng kênh:</strong> {{ $video->channel_publish_status }}
                                        @if($video->channel_published_at)
                                            <br><small>Đăng lúc: {{ $video->channel_published_at->format('d/m/Y H:i') }}</small>
                                        @endif
                                        @if($video->channel_publish_error)
                                            <br><small class="text-danger">Lỗi: {{ $video->channel_publish_error }}</small>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu thay đổi
                                </button>
                                <a href="{{ route('admin.videos.show', $video) }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash"></i> Xóa Video
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Video Preview -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-eye"></i> Xem trước
                    </h5>
                </div>
                <div class="card-body">
                    @if($video->fileExists())
                        <div class="video-container text-center mb-3">
                            <video controls style="max-width: 100%; max-height: 300px;" class="rounded">
                                <source src="{{ $video->preview_url }}" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ video HTML5.
                            </video>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-sm btn-primary preview-video-btn"
                                    data-video-url="{{ $video->preview_url }}"
                                    data-video-title="{{ $video->title }}"
                                    data-download-url="{{ $video->download_url }}">
                                <i class="fas fa-play mr-1"></i>Xem trước
                            </button>
                            <a href="{{ $video->download_url }}" class="btn btn-sm btn-success">
                                <i class="fas fa-download"></i> Tải xuống
                            </a>
                            <a href="{{ $video->preview_url }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-external-link-alt"></i> Mở tab mới
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p class="mb-0">File video không tồn tại</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Video Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Thông tin
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
                            <td><strong>Thời lượng:</strong></td>
                            <td>{{ $video->duration_human }}</td>
                        </tr>
                        <tr>
                            <td><strong>Kích thước:</strong></td>
                            <td>{{ $video->file_size_human }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tên file:</strong></td>
                            <td><small><code>{{ $video->file_name }}</code></small></td>
                        </tr>
                        <tr>
                            <td><strong>Ngày tạo:</strong></td>
                            <td><small>{{ $video->created_at->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa video này không?</p>
                <p class="text-muted">Hành động này không thể hoàn tác. File video sẽ bị xóa vĩnh viễn.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="{{ route('admin.videos.destroy', $video) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Xóa Video
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const scheduledAtGroup = document.getElementById('scheduled-at-group');
    const scheduledAtInput = document.getElementById('scheduled_at');

    const publishToChannelCheckbox = document.getElementById('publish_to_channel');
    const channelGroup = document.getElementById('channel-group');
    const autoPublishGroup = document.getElementById('auto-publish-group');

    // Handle status change
    statusSelect.addEventListener('change', function() {
        if (this.value === 'scheduled') {
            scheduledAtGroup.style.display = 'block';
            scheduledAtInput.required = true;
        } else {
            scheduledAtGroup.style.display = 'none';
            scheduledAtInput.required = false;
            scheduledAtInput.value = '';
        }
    });

    // Handle publish to channel checkbox
    publishToChannelCheckbox.addEventListener('change', function() {
        if (this.checked) {
            channelGroup.style.display = 'block';
            autoPublishGroup.style.display = 'block';
        } else {
            channelGroup.style.display = 'none';
            autoPublishGroup.style.display = 'none';
            document.getElementById('channel_id').value = '';
            document.getElementById('auto_publish').checked = false;
        }
    });

    // Video preview modal handler
    $('.preview-video-btn').on('click', function() {
        const videoUrl = $(this).data('video-url');
        const videoTitle = $(this).data('video-title');

        // Update modal content
        $('#videoPreviewModalLabel').html('<i class="fas fa-play-circle mr-2"></i>' + videoTitle);
        $('#previewVideo source').attr('src', videoUrl);
        $('#previewVideo')[0].load(); // Reload video element

        // Show modal
        $('#videoPreviewModal').modal('show');
    });

    // Pause video when modal is closed
    $('#videoPreviewModal').on('hidden.bs.modal', function() {
        const video = $('#previewVideo')[0];
        if (video) {
            video.pause();
            video.currentTime = 0;
        }
    });
});
</script>

<!-- Video Preview Modal -->
<x-video-preview-modal id="videoPreviewModal" />
@endsection
