@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🎬 Tạo Video - {{ $story->title }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('stories.show', $story) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
                    @if(!$hasImage)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Cảnh báo:</strong> Truyện chưa có ảnh nền. Vui lòng upload ảnh cho truyện trước khi tạo video.
                        </div>
                    @endif
                    
                    @if(empty($audioFiles))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Cảnh báo:</strong> Không tìm thấy file audio MP3. Vui lòng tạo audio bằng TTS trước.
                        </div>
                    @endif
                    
                    @if(empty($overlayFiles))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Thông tin:</strong> Không tìm thấy file overlay video. Hệ thống sẽ sử dụng file mặc định.
                        </div>
                    @endif
                    
                    <form action="{{ route('stories.video.generate', $story) }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="chapter_number">Chương cần tạo video</label>
                            <select name="chapter_number" id="chapter_number" class="form-control">
                                <option value="">Tạo video tổng hợp (sử dụng audio đầu tiên)</option>
                                @foreach($audioFiles as $audioFile)
                                    @php
                                        preg_match('/chuong[_-](\d+)\.mp3/i', $audioFile, $matches);
                                        $chapterNum = isset($matches[1]) ? $matches[1] : null;
                                    @endphp
                                    @if($chapterNum)
                                        <option value="{{ $chapterNum }}">Chương {{ $chapterNum }} ({{ $audioFile }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Chọn chương cụ thể hoặc để trống để tạo video tổng hợp
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="overlay_file">Video overlay (hiển thị ở góc phải dưới)</label>
                            <select name="overlay_file" id="overlay_file" class="form-control">
                                <option value="">Sử dụng file mặc định</option>
                                @foreach($overlayFiles as $overlayFile)
                                    <option value="{{ $overlayFile }}">{{ $overlayFile }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Video ngắn sẽ được lặp lại để khớp với thời lượng audio
                            </small>

                            <!-- Upload overlay video -->
                            <div class="mt-2">
                                <small class="text-info">
                                    <i class="fas fa-info-circle"></i>
                                    Bạn có thể upload thêm video overlay trong phần "Quản lý Overlay Videos" bên phải
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="output_name">Tên file output (tùy chọn)</label>
                            <input type="text" name="output_name" id="output_name" class="form-control"
                                   placeholder="Ví dụ: video-gioi-thieu.mp4">
                            <small class="form-text text-muted">
                                Để trống để hệ thống tự đặt tên: <span id="preview_filename" class="text-info font-weight-bold">{{ $story->folder_name ?: Str::slug($story->title) }}_video_tong_hop.mp4</span>
                            </small>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" 
                                    {{ (!$hasImage || empty($audioFiles)) ? 'disabled' : '' }}>
                                <i class="fas fa-video"></i> Tạo Video
                            </button>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <h5><i class="fas fa-info-circle"></i> Thông tin tạo video:</h5>
                        <ul class="mb-0">
                            <li><strong>Input:</strong> Ảnh nền từ truyện + File audio MP3 + Video overlay</li>
                            <li><strong>Output:</strong> Video MP4 với độ phân giải 1280x720</li>
                            <li><strong>Overlay:</strong> Video nhỏ ở góc phải dưới với bo góc tròn</li>
                            <li><strong>Audio:</strong> Được tăng âm lượng +20dB</li>
                            <li><strong>Thời gian:</strong> Quá trình có thể mất 2-5 phút tùy độ dài audio</li>
                            <li><strong>Lưu trữ:</strong> File sẽ được lưu trong thư mục <code>storage/app/videos/{{ $story->folder_name }}/</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Thông tin tài nguyên -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Tài nguyên có sẵn</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="info-box">
                                <span class="info-box-icon {{ $hasImage ? 'bg-success' : 'bg-danger' }}">
                                    <i class="fas fa-image"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Ảnh nền</span>
                                    <span class="info-box-number">{{ $hasImage ? 'Có' : 'Chưa có' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="info-box">
                                <span class="info-box-icon {{ !empty($audioFiles) ? 'bg-success' : 'bg-warning' }}">
                                    <i class="fas fa-volume-up"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">File audio</span>
                                    <span class="info-box-number">{{ count($audioFiles) }}</span>
                                    @if(!empty($audioFiles))
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                @foreach(array_slice($audioFiles, 0, 3) as $audioFile)
                                                    {{ basename($audioFile, '.mp3') }}@if(!$loop->last), @endif
                                                @endforeach
                                                @if(count($audioFiles) > 3)
                                                    và {{ count($audioFiles) - 3 }} file khác
                                                @endif
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="info-box">
                                <span class="info-box-icon {{ !empty($overlayFiles) ? 'bg-info' : 'bg-secondary' }}">
                                    <i class="fas fa-film"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Video overlay</span>
                                    <span class="info-box-number">{{ count($overlayFiles) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="info-box">
                                <span class="info-box-icon {{ !empty($existingVideos) ? 'bg-primary' : 'bg-light' }}">
                                    <i class="fas fa-video"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Video đã tạo</span>
                                    <span class="info-box-number">{{ count($existingVideos) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quản lý overlay videos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🎬 Quản lý Overlay Videos</h3>
                </div>
                <div class="card-body">
                    <div id="overlayList">
                        @if(!empty($overlayFiles))
                            @foreach($overlayFiles as $overlayFile)
                                <div class="list-group-item d-flex justify-content-between align-items-center mb-2" data-filename="{{ $overlayFile }}">
                                    <span>{{ $overlayFile }}</span>
                                    <button class="btn btn-sm btn-danger delete-overlay" data-filename="{{ $overlayFile }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted" id="noOverlayMessage">Chưa có file overlay nào</p>
                        @endif
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadOverlayModal">
                            <i class="fas fa-upload"></i> Upload Overlay Video
                        </button>
                    </div>
                </div>
            </div>

            <!-- Danh sách video đã tạo -->
            @if(!empty($existingVideos))
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">🎥 Video đã tạo</h3>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($existingVideos as $video)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $video }}</span>
                                <small class="text-muted">
                                    @php
                                        $videoPath = storage_path('app/videos/' . $story->folder_name . '/' . $video);
                                        $size = file_exists($videoPath) ? filesize($videoPath) : 0;
                                        $sizeFormatted = $size > 0 ? round($size / 1024 / 1024, 1) . ' MB' : 'N/A';
                                    @endphp
                                    {{ $sizeFormatted }}
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Upload Overlay Video -->
<div class="modal fade" id="uploadOverlayModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Video Overlay</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="uploadOverlayForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="overlay_video">Chọn file video</label>
                        <input type="file" name="overlay_video" id="overlay_video" class="form-control-file"
                               accept=".mp4,.avi,.mov,.wmv" required>
                        <small class="form-text text-muted">
                            Định dạng hỗ trợ: MP4, AVI, MOV, WMV. Kích thước tối đa: 50MB
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Lưu ý:</h6>
                        <ul class="mb-0">
                            <li>Video overlay nên có độ dài 5-15 giây</li>
                            <li>Độ phân giải khuyến nghị: 320x180 hoặc tỷ lệ 16:9</li>
                            <li>Video sẽ được lặp lại để khớp với thời lượng audio</li>
                            <li>Video sẽ hiển thị ở góc phải dưới với bo góc tròn</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="uploadBtn">
                    <i class="fas fa-upload"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Cập nhật preview tên file khi thay đổi chương
    $('#chapter_number').change(function() {
        var chapterNumber = $(this).val();
        var storySlug = '{{ $story->folder_name ?: Str::slug($story->title) }}';
        var previewName;

        if (chapterNumber) {
            previewName = storySlug + '_chuong_' + chapterNumber + '.mp4';
        } else {
            previewName = storySlug + '_video_tong_hop.mp4';
        }

        $('#preview_filename').text(previewName);
    });

    // Cập nhật preview khi thay đổi tên file custom
    $('#output_name').on('input', function() {
        var customName = $(this).val().trim();
        if (customName) {
            $('#preview_filename').text(customName);
        } else {
            // Trigger change event để cập nhật lại preview mặc định
            $('#chapter_number').trigger('change');
        }
    });
    // Upload overlay video
    $('#uploadBtn').click(function() {
        console.log('Upload button clicked');

        var form = $('#uploadOverlayForm')[0];
        var fileInput = $('#overlay_video')[0];

        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Vui lòng chọn file video');
            return;
        }

        var formData = new FormData(form);
        var $btn = $(this);
        var originalText = $btn.html();

        console.log('FormData created, starting upload...');
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang upload...').prop('disabled', true);

        $.ajax({
            url: '{{ route("overlay.upload") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Upload response:', response);
                if (response.success) {
                    // Thêm file mới vào select
                    $('#overlay_file').append(
                        '<option value="' + response.filename + '">' + response.filename + '</option>'
                    );

                    // Thêm vào danh sách quản lý
                    var listItem = `
                        <div class="list-group-item d-flex justify-content-between align-items-center mb-2" data-filename="${response.filename}">
                            <span>${response.filename}</span>
                            <button class="btn btn-sm btn-danger delete-overlay" data-filename="${response.filename}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;

                    // Ẩn thông báo "chưa có file" nếu có
                    $('#noOverlayMessage').hide();

                    // Thêm item mới
                    $('#overlayList').append(listItem);

                    // Đóng modal và reset form
                    setTimeout(function() {
                        if (typeof $.fn.modal !== 'undefined') {
                            $('#uploadOverlayModal').modal('hide');
                        } else {
                            // Fallback manual close
                            $('#uploadOverlayModal').removeClass('show').hide();
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                        }
                        $('#uploadOverlayForm')[0].reset();
                    }, 500);

                    // Hiển thị thông báo
                    showAlert('success', response.message);

                    // Cập nhật số lượng overlay trong info box
                    var currentCount = parseInt($('.info-box .info-box-number').eq(2).text()) || 0;
                    $('.info-box .info-box-number').eq(2).text(currentCount + 1);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                console.log('Upload error:', xhr);
                console.log('Response text:', xhr.responseText);

                var message = 'Lỗi khi upload file';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    message = 'Lỗi server: ' + xhr.responseText.substring(0, 100);
                }
                showAlert('danger', message);
            },
            complete: function() {
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Xóa overlay video
    $(document).on('click', '.delete-overlay', function() {
        var filename = $(this).data('filename');
        var $item = $(this).closest('.list-group-item');

        if (confirm('Bạn có chắc muốn xóa file "' + filename + '"?')) {
            $.ajax({
                url: '{{ route("overlay.delete") }}',
                type: 'DELETE',
                data: {
                    filename: filename,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Xóa khỏi select
                        $('#overlay_file option[value="' + filename + '"]').remove();

                        // Xóa khỏi danh sách
                        $item.remove();

                        // Hiển thị thông báo nếu không còn file nào
                        if ($('#overlayList .list-group-item').length === 0) {
                            $('#overlayList').append('<p class="text-muted" id="noOverlayMessage">Chưa có file overlay nào</p>');
                        }

                        showAlert('success', response.message);

                        // Cập nhật số lượng overlay trong info box
                        var currentCount = parseInt($('.info-box .info-box-number').eq(2).text()) || 0;
                        if (currentCount > 0) {
                            $('.info-box .info-box-number').eq(2).text(currentCount - 1);
                        }
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    var message = 'Lỗi khi xóa file';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showAlert('danger', message);
                }
            });
        }
    });

    // Hàm hiển thị thông báo
    function showAlert(type, message) {
        var alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        $('.card-body').first().prepend(alertHtml);

        // Tự động ẩn sau 5 giây
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endsection
