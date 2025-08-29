@extends('layouts.app')

@section('title', 'Chỉnh sửa thông tin đăng video')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Chỉnh sửa thông tin đăng video</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Quản lý Video</li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.video-publishing.index') }}">Đăng video</a></li>
                    <li class="breadcrumb-item active">Chỉnh sửa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit mr-2"></i>Chỉnh sửa thông tin đăng video
        </h1>
        <a href="{{ route('admin.video-publishing.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
    </div>

    <!-- Video Info -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Thông tin video</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    @if($videoPublishing->generatedVideo && file_exists(storage_path('app/' . $videoPublishing->generatedVideo->file_path)))
                        <video width="100%" height="200" controls class="rounded">
                            <source src="{{ asset('storage/' . $videoPublishing->generatedVideo->file_path) }}" type="video/mp4">
                            Trình duyệt không hỗ trợ video.
                        </video>
                    @else
                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-video fa-3x text-white"></i>
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Tên file:</strong></td>
                            <td>{{ $videoPublishing->generatedVideo->file_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Nền tảng:</strong></td>
                            <td>
                                <i class="{{ $videoPublishing->platform_icon }} mr-1"></i>
                                {{ ucfirst($videoPublishing->platform) }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Trạng thái hiện tại:</strong></td>
                            <td>
                                <span class="badge badge-{{ $videoPublishing->status_badge }}">
                                    {{ $videoPublishing->status_text }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Ngày tạo:</strong></td>
                            <td>{{ $videoPublishing->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Chỉnh sửa thông tin đăng</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.video-publishing.update', $videoPublishing) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="post_title">Tiêu đề bài đăng <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('post_title') is-invalid @enderror" 
                                   id="post_title" 
                                   name="post_title" 
                                   value="{{ old('post_title', $videoPublishing->post_title) }}" 
                                   required>
                            @error('post_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="channel_id">Kênh đăng</label>
                            <select name="channel_id" id="channel_id" class="form-control @error('channel_id') is-invalid @enderror">
                                <option value="">Chọn kênh</option>
                                @foreach($channels as $channel)
                                    <option value="{{ $channel->id }}" 
                                            {{ old('channel_id', $videoPublishing->channel_id) == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('channel_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="post_description">Mô tả bài đăng</label>
                    <textarea class="form-control @error('post_description') is-invalid @enderror" 
                              id="post_description" 
                              name="post_description" 
                              rows="4">{{ old('post_description', $videoPublishing->post_description) }}</textarea>
                    @error('post_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="post_tags">Tags (phân cách bằng dấu phẩy)</label>
                            <input type="text" 
                                   class="form-control @error('post_tags') is-invalid @enderror" 
                                   id="post_tags" 
                                   name="post_tags" 
                                   value="{{ old('post_tags', is_array($videoPublishing->post_tags) ? implode(', ', $videoPublishing->post_tags) : '') }}"
                                   placeholder="tag1, tag2, tag3">
                            @error('post_tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="post_privacy">Quyền riêng tư <span class="text-danger">*</span></label>
                            <select name="post_privacy" id="post_privacy" class="form-control @error('post_privacy') is-invalid @enderror" required>
                                <option value="private" {{ old('post_privacy', $videoPublishing->post_privacy) == 'private' ? 'selected' : '' }}>
                                    Riêng tư
                                </option>
                                <option value="unlisted" {{ old('post_privacy', $videoPublishing->post_privacy) == 'unlisted' ? 'selected' : '' }}>
                                    Không công khai
                                </option>
                                <option value="public" {{ old('post_privacy', $videoPublishing->post_privacy) == 'public' ? 'selected' : '' }}>
                                    Công khai
                                </option>
                            </select>
                            @error('post_privacy')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="scheduled_at">Lên lịch đăng (tùy chọn)</label>
                    <input type="datetime-local" 
                           class="form-control @error('scheduled_at') is-invalid @enderror" 
                           id="scheduled_at" 
                           name="scheduled_at" 
                           value="{{ old('scheduled_at', $videoPublishing->scheduled_at ? $videoPublishing->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                           min="{{ now()->format('Y-m-d\TH:i') }}">
                    <small class="form-text text-muted">
                        Để trống nếu muốn đăng thủ công. Nếu chọn thời gian, video sẽ được lên lịch đăng tự động.
                    </small>
                    @error('scheduled_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Lưu thay đổi
                        </button>
                        
                        <div>
                            @if($videoPublishing->status === 'draft')
                                <a href="{{ route('admin.video-publishing.publish', $videoPublishing) }}" 
                                   class="btn btn-success mr-2"
                                   onclick="return confirm('Đăng video ngay sau khi lưu?')">
                                    <i class="fas fa-upload mr-1"></i>Lưu và đăng ngay
                                </a>
                            @endif
                            
                            <a href="{{ route('admin.video-publishing.show', $videoPublishing) }}" class="btn btn-info">
                                <i class="fas fa-eye mr-1"></i>Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($videoPublishing->error_message)
        <div class="card shadow mb-4 border-left-danger">
            <div class="card-header bg-danger text-white py-3">
                <h6 class="m-0 font-weight-bold">Lỗi gần nhất</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $videoPublishing->error_message }}</p>
                @if($videoPublishing->last_retry_at)
                    <small class="text-muted">
                        Lần thử lại cuối: {{ $videoPublishing->last_retry_at->format('d/m/Y H:i') }}
                        (Đã thử {{ $videoPublishing->retry_count }} lần)
                    </small>
                @endif
            </div>
        </div>
    @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-generate title from video name if empty
    if (!$('#post_title').val()) {
        const videoTitle = '{{ $videoPublishing->generatedVideo->title ?? "" }}';
        if (videoTitle) {
            $('#post_title').val(videoTitle);
        }
    }

    // Platform-specific validation
    const platform = '{{ $videoPublishing->platform }}';

    if (platform === 'youtube') {
        $('#post_title').attr('maxlength', 100);
        $('#post_description').attr('maxlength', 5000);
    } else if (platform === 'tiktok') {
        $('#post_title').attr('maxlength', 150);
        $('#post_description').attr('maxlength', 2200);
    }
});
</script>
@endsection
