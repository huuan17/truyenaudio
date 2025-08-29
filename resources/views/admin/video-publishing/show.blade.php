@extends('layouts.app')

@section('title', 'Chi tiết đăng video')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Chi tiết đăng video</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Quản lý Video</li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.video-publishing.index') }}">Đăng video</a></li>
                    <li class="breadcrumb-item active">Chi tiết</li>
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
            <i class="fas fa-info-circle mr-2"></i>Chi tiết đăng video
        </h1>
        <div>
            @if($videoPublishing->canEdit())
                <a href="{{ route('admin.video-publishing.edit', $videoPublishing) }}" class="btn btn-warning mr-2">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
            @endif
            <a href="{{ route('admin.video-publishing.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Video Preview -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Video Preview</h6>
                </div>
                <div class="card-body">
                    @if($videoPublishing->generatedVideo && file_exists(storage_path('app/' . $videoPublishing->generatedVideo->file_path)))
                        <video width="100%" height="300" controls class="rounded">
                            <source src="{{ asset('storage/' . $videoPublishing->generatedVideo->file_path) }}" type="video/mp4">
                            Trình duyệt không hỗ trợ video.
                        </video>

                        <div class="mt-3">
                            <p><strong>Tên file:</strong> {{ $videoPublishing->generatedVideo->file_name }}</p>
                            <p><strong>Kích thước:</strong> {{ number_format($videoPublishing->generatedVideo->file_size / 1024 / 1024, 2) }} MB</p>
                            @if($videoPublishing->generatedVideo->duration)
                                <p><strong>Thời lượng:</strong> {{ $videoPublishing->generatedVideo->duration }} giây</p>
                            @endif
                        </div>
                    @else
                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                            <div class="text-center text-white">
                                <i class="fas fa-video fa-3x mb-2"></i>
                                <p>Video không tồn tại</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Publishing Details -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin đăng</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Nền tảng:</strong></td>
                            <td>
                                <i class="{{ $videoPublishing->platform_icon }} mr-1"></i>
                                {{ ucfirst($videoPublishing->platform) }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Kênh:</strong></td>
                            <td>{{ $videoPublishing->channel->name ?? 'Chưa chọn kênh' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td>
                                <span class="badge badge-{{ $videoPublishing->status_badge }}">
                                    {{ $videoPublishing->status_text }}
                                </span>
                                @if($videoPublishing->isOverdue())
                                    <br><small class="text-danger">Quá hạn đăng</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Chế độ đăng:</strong></td>
                            <td>
                                @switch($videoPublishing->publish_mode)
                                    @case('auto')
                                        <span class="badge badge-success">Tự động</span>
                                        @break
                                    @case('scheduled')
                                        <span class="badge badge-info">Lên lịch</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">Thủ công</span>
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Quyền riêng tư:</strong></td>
                            <td>
                                @switch($videoPublishing->post_privacy)
                                    @case('public')
                                        <span class="badge badge-success">Công khai</span>
                                        @break
                                    @case('unlisted')
                                        <span class="badge badge-warning">Không công khai</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">Riêng tư</span>
                                @endswitch
                            </td>
                        </tr>
                        @if($videoPublishing->scheduled_at)
                            <tr>
                                <td><strong>Thời gian lên lịch:</strong></td>
                                <td>{{ $videoPublishing->scheduled_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endif
                        @if($videoPublishing->published_at)
                            <tr>
                                <td><strong>Đã đăng lúc:</strong></td>
                                <td>{{ $videoPublishing->published_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Ngày tạo:</strong></td>
                            <td>{{ $videoPublishing->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($videoPublishing->creator)
                            <tr>
                                <td><strong>Người tạo:</strong></td>
                                <td>{{ $videoPublishing->creator->name }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thao tác</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($videoPublishing->channel)
                            <button type="button" id="btn-test-connection"
                                    data-url="{{ route('admin.channels.test-connection', $videoPublishing->channel) }}"
                                    class="btn btn-outline-info btn-block mb-2">
                                <i class="fas fa-plug mr-1"></i>Test kết nối kênh
                            </button>
                            <pre id="test-connection-result" class="bg-light p-2 rounded small" style="display:none;"></pre>
                        @endif

                        @if($videoPublishing->status === 'draft')
                            <form method="POST" action="{{ route('admin.video-publishing.publish', $videoPublishing) }}"
                                  onsubmit="return confirm('Đăng video ngay?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-upload mr-1"></i>Đăng ngay
                                </button>
                            </form>
                        @endif

                        @if($videoPublishing->canRetry())
                            <form method="POST" action="{{ route('admin.video-publishing.retry', $videoPublishing) }}"
                                  onsubmit="return confirm('Thử lại đăng video?')">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-redo mr-1"></i>Thử lại
                                </button>
                            </form>
                        @endif

                        @if($videoPublishing->canCancel())
                            <form method="POST" action="{{ route('admin.video-publishing.cancel', $videoPublishing) }}"
                                  onsubmit="return confirm('Hủy đăng video?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-block">
                                    <i class="fas fa-times mr-1"></i>Hủy đăng
                                </button>
                            </form>
                        @endif

                        @if($videoPublishing->platform_url)
                            <a href="{{ $videoPublishing->platform_url }}" target="_blank" class="btn btn-info btn-block">
                                <i class="fas fa-external-link-alt mr-1"></i>Xem trên {{ ucfirst($videoPublishing->platform) }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Content -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Nội dung bài đăng</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h5>{{ $videoPublishing->post_title ?: 'Chưa có tiêu đề' }}</h5>

                    @if($videoPublishing->post_description)
                        <div class="mt-3">
                            <h6>Mô tả:</h6>
                            <p class="text-muted">{{ $videoPublishing->post_description }}</p>
                        </div>
                    @endif

                    @if($videoPublishing->post_tags && count($videoPublishing->post_tags) > 0)
                        <div class="mt-3">
                            <h6>Tags:</h6>
                            @foreach($videoPublishing->post_tags as $tag)
                                <span class="badge badge-light mr-1">#{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if($videoPublishing->post_category)
                        <div class="mt-3">
                            <h6>Danh mục:</h6>
                            <span class="badge badge-primary">{{ $videoPublishing->post_category }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Error Information -->
    @if($videoPublishing->error_message)
        <div class="card shadow mb-4 border-left-danger">
            <div class="card-header bg-danger text-white py-3">
                <h6 class="m-0 font-weight-bold">Thông tin lỗi</h6>
            </div>
            <div class="card-body">
                <p><strong>Lỗi:</strong> {{ $videoPublishing->error_message }}</p>
                <p><strong>Số lần thử lại:</strong> {{ $videoPublishing->retry_count }}</p>
                @if($videoPublishing->last_retry_at)
                    <p><strong>Lần thử lại cuối:</strong> {{ $videoPublishing->last_retry_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>
    @endif

    <!-- Platform Metadata -->
    @if($videoPublishing->platform_metadata)
        <div class="card shadow mb-4">
@section('scripts')
<script>
$(function(){
  var $btn = $('#btn-test-connection');
  if ($btn.length) {
    $btn.on('click', function(e){
      e.preventDefault();
      var url = $(this).data('url');
      if (!url) return;
      var $res = $('#test-connection-result');
      var original = $btn.html();
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang kiểm tra...');
      $res.hide().text('');
      $.ajax({
        url: url,
        method: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json'
      }).done(function(res){
        var ok = (res && res.success !== false);
        toastr[ok ? 'success' : 'error'](res.message || (ok ? 'Kết nối OK' : 'Kết nối thất bại'));
        try {
          $res.text(JSON.stringify(res, null, 2)).show();
        } catch (e) { $res.text(String(res)).show(); }
      }).fail(function(xhr){
        toastr.error('Lỗi khi kiểm tra kết nối');
        $res.text(xhr.responseText || 'HTTP error').show();
      }).always(function(){
        $btn.prop('disabled', false).html(original);
      });
    });
  }
});
</script>
@endsection

            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Metadata nền tảng</h6>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded">{{ json_encode($videoPublishing->platform_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif
    </div>
</div>
@endsection
