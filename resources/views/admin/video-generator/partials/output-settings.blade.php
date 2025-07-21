<!-- Output Settings Component -->
<div class="form-section">
    <h6><i class="fas fa-file-video mr-2"></i>Cài đặt Output</h6>
    
    <div class="form-group">
        <label for="{{ $prefix }}_output_name">
            <i class="fas fa-file-video mr-1"></i>Tên File Output (Tùy chọn)
        </label>
        <input type="text" name="output_name" id="{{ $prefix }}_output_name" class="form-control"
               placeholder="Ví dụ: {{ $prefix === 'tiktok' ? 'review_iphone_15' : 'youtube_slideshow_demo' }}"
               value="{{ old('output_name') }}"
               onchange="updateOutputPreview('{{ $prefix }}')">
        <small class="form-text text-muted">
            <div id="{{ $prefix }}_output_preview" class="mb-2">
                <strong>Tên mặc định:</strong>
                <span class="text-info" id="{{ $prefix }}_default_name">
                    {{ $prefix }}_video_{{ date('Y-m-d_H-i') }}_001.mp4
                </span>
            </div>
            Để trống để tự động tạo tên theo format: [loại]_[ngày]_[giờ]_[số thứ tự].mp4
            <br>Phần mở rộng .mp4 sẽ được thêm tự động nếu chưa có.
        </small>
    </div>
</div>
