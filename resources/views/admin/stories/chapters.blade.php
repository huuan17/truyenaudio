@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách chương - {{ $story->title }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <a href="{{ route('admin.chapters.create', ['story_id' => $story->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Thêm chương mới
                </a>
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#ttsAllModal">
                    <i class="fas fa-volume-up"></i> TTS tất cả
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.stories.chapters', $story) }}" class="form-inline">
                <!-- Filter theo loại content -->
                <div class="form-group mr-3">
                    <label for="content_type" class="mr-2">Lọc theo loại:</label>
                    <select name="content_type" id="content_type" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ request('content_type', 'all') == 'all' ? 'selected' : '' }}>
                            📚 Tất cả ({{ $contentTypeCounts['all'] ?? 0 }})
                        </option>
                        <option value="text" {{ request('content_type') == 'text' ? 'selected' : '' }}>
                            📝 Text ({{ $contentTypeCounts['text'] ?? 0 }})
                        </option>
                        <option value="audio" {{ request('content_type') == 'audio' ? 'selected' : '' }}>
                            🎵 Audio ({{ $contentTypeCounts['audio'] ?? 0 }})
                        </option>
                        <option value="video" {{ request('content_type') == 'video' ? 'selected' : '' }}>
                            🎬 Video ({{ $contentTypeCounts['video'] ?? 0 }})
                        </option>
                        <option value="no_content" {{ request('content_type') == 'no_content' ? 'selected' : '' }}>
                            ❌ Không có nội dung ({{ $contentTypeCounts['no_content'] ?? 0 }})
                        </option>
                    </select>
                </div>

                <!-- Filter theo audio status (legacy) -->
                <div class="form-group">
                    <label for="audio_status" class="mr-2">Trạng thái TTS:</label>
                    <select name="audio_status" id="audio_status" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ request('audio_status', 'all') == 'all' ? 'selected' : '' }}>
                            Tất cả
                        </option>
                        <option value="pending" {{ request('audio_status') == 'pending' ? 'selected' : '' }}>
                            Chờ xử lý ({{ $statusCounts['pending'] ?? 0 }})
                        </option>
                        <option value="processing" {{ request('audio_status') == 'processing' ? 'selected' : '' }}>
                            Đang xử lý ({{ $statusCounts['processing'] ?? 0 }})
                        </option>
                        <option value="done" {{ request('audio_status') == 'done' ? 'selected' : '' }}>
                            Hoàn thành ({{ $statusCounts['done'] ?? 0 }})
                        </option>
                        <option value="error" {{ request('audio_status') == 'error' ? 'selected' : '' }}>
                            Lỗi ({{ $statusCounts['error'] ?? 0 }})
                        </option>
                    </select>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Chương</th>
                        <th>Tiêu đề</th>
                        <th>Trạng thái</th>
                        <th>TTS Status</th>
                        <th>Video Status</th>
                        <th>Audio Player</th>
                        <th>Content</th>
                        <th>Nguồn dữ liệu</th>
                        <th>Kích thước</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chapters as $chapter)
                        <tr>
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
                                <div class="btn-group">
                                    <a href="{{ route('admin.chapters.edit', $chapter) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($chapter->canConvertToTts())
                                        <button type="button" class="btn btn-sm btn-success"
                                                data-toggle="modal"
                                                data-target="#ttsModal"
                                                data-chapter-id="{{ $chapter->id }}"
                                                data-chapter-number="{{ $chapter->chapter_number }}">
                                            <i class="fas fa-volume-up"></i>
                                        </button>
                                    @endif
                                    <form action="{{ route('admin.chapters.destroy', $chapter) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa chương này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
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
            {{ $chapters->links('vendor.pagination.adminlte') }}
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
                            <option value="hn_male_manhtung_full_48k-fhg">Mạnh Tùng (Nam - Hà Nội)</option>
                            <option value="sg_female_thaotrinh_full_48k-fhg">Thảo Trinh (Nữ - Sài Gòn)</option>
                            <option value="sg_male_minhhoang_full_48k-fhg">Minh Hoàng (Nam - Sài Gòn)</option>
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
            url: '{{ url("/chapters") }}/' + chapterId + '/content',
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
</script>
@endpush