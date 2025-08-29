@props([
    'id' => 'videoPreviewModal',
    'showDownload' => true,
    'showNewTab' => true
])

<!-- Video Preview Modal -->
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">
                    <i class="fas fa-play-circle mr-2"></i>Xem trước video
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-0">
                <video id="{{ $id }}Video" class="w-100" controls style="max-height: 70vh;">
                    <source src="" type="video/mp4">
                    Trình duyệt của bạn không hỗ trợ video HTML5.
                </video>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Đóng
                </button>
                @if($showDownload)
                    <a href="#" id="{{ $id }}DownloadBtn" class="btn btn-success" target="_blank">
                        <i class="fas fa-download mr-1"></i>Tải xuống
                    </a>
                @endif
                @if($showNewTab)
                    <a href="#" id="{{ $id }}NewTabBtn" class="btn btn-info" target="_blank">
                        <i class="fas fa-external-link-alt mr-1"></i>Mở tab mới
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Video preview modal handler for {{ $id }}
    $('.preview-video-btn').on('click', function() {
        const videoUrl = $(this).data('video-url');
        const videoTitle = $(this).data('video-title');
        const downloadUrl = $(this).data('download-url') || $(this).closest('tr').find('a[title="Tải xuống"]').attr('href');
        
        // Update modal content
        $('#{{ $id }}Label').html('<i class="fas fa-play-circle mr-2"></i>' + videoTitle);
        $('#{{ $id }}Video').attr('src', videoUrl);
        $('#{{ $id }}Video')[0].load(); // Reload video element
        
        @if($showDownload)
            if (downloadUrl) {
                $('#{{ $id }}DownloadBtn').attr('href', downloadUrl);
            }
        @endif
        
        @if($showNewTab)
            $('#{{ $id }}NewTabBtn').attr('href', videoUrl);
        @endif
        
        // Show modal
        $('#{{ $id }}').modal('show');
    });
    
    // Pause video when modal is closed
    $('#{{ $id }}').on('hidden.bs.modal', function() {
        const video = $('#{{ $id }}Video')[0];
        if (video) {
            video.pause();
            video.currentTime = 0;
        }
    });
    
    // Auto-play video when modal is shown (optional)
    $('#{{ $id }}').on('shown.bs.modal', function() {
        const video = $('#{{ $id }}Video')[0];
        if (video) {
            // Uncomment next line if you want auto-play
            // video.play();
        }
    });
});
</script>
@endpush
