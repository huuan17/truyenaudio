<!-- Channel Settings Component -->
<div class="form-section">
    <h6><i class="fas fa-broadcast-tower mr-2"></i>Cài đặt Kênh và Đăng bài</h6>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}_channel_id">Kênh đăng bài (Tùy chọn)</label>
                <select name="channel_id" id="{{ $prefix }}_channel_id" class="form-control">
                    <option value="">-- Không đăng bài tự động --</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel->id }}" {{ old('channel_id') == $channel->id ? 'selected' : '' }}>
                            {{ $channel->name }} (@{{ $channel->username }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Chọn kênh để tự động đăng video sau khi tạo xong
                </small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <div class="form-check mt-4">
                    <input type="checkbox" name="schedule_post" id="{{ $prefix }}_schedule_post" 
                           class="form-check-input" value="1" 
                           onchange="toggleScheduleOptions('{{ $prefix }}')"
                           {{ old('schedule_post') ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $prefix }}_schedule_post">
                        Lên lịch đăng bài
                    </label>
                </div>
                <small class="form-text text-muted">
                    Đăng bài vào thời gian cụ thể thay vì ngay lập tức
                </small>
            </div>
        </div>
    </div>

    <!-- Schedule Options -->
    <div id="{{ $prefix }}_schedule_options" style="display: none;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="{{ $prefix }}_scheduled_date">Ngày đăng</label>
                    <input type="date" name="scheduled_date" id="{{ $prefix }}_scheduled_date" 
                           class="form-control" value="{{ old('scheduled_date') }}" 
                           min="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="{{ $prefix }}_scheduled_time">Giờ đăng</label>
                    <input type="time" name="scheduled_time" id="{{ $prefix }}_scheduled_time" 
                           class="form-control" value="{{ old('scheduled_time', '09:00') }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Post Content -->
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="{{ $prefix }}_post_title">Tiêu đề bài đăng (Tùy chọn)</label>
                <input type="text" name="post_title" id="{{ $prefix }}_post_title" 
                       class="form-control" placeholder="Nhập tiêu đề cho bài đăng..." 
                       value="{{ old('post_title') }}" maxlength="255">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}_post_description">Mô tả bài đăng (Tùy chọn)</label>
                <textarea name="post_description" id="{{ $prefix }}_post_description" 
                          class="form-control" rows="3" 
                          placeholder="Nhập mô tả cho bài đăng...">{{ old('post_description') }}</textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}_post_tags">Tags (Tùy chọn)</label>
                <textarea name="post_tags" id="{{ $prefix }}_post_tags" 
                          class="form-control" rows="3" 
                          placeholder="Nhập tags, cách nhau bằng dấu phẩy...">{{ old('post_tags') }}</textarea>
                <small class="form-text text-muted">
                    Ví dụ: #review, #product, #viral
                </small>
            </div>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Lưu ý:</strong> Tính năng đăng bài tự động yêu cầu kênh đã được kết nối và có quyền đăng bài. 
        Nếu không chọn kênh, video sẽ chỉ được tạo và lưu trữ.
    </div>
</div>

<script>
function toggleScheduleOptions(prefix) {
    const enabled = document.getElementById(prefix + '_schedule_post').checked;
    const optionsDiv = document.getElementById(prefix + '_schedule_options');
    const dateInput = document.getElementById(prefix + '_scheduled_date');
    const timeInput = document.getElementById(prefix + '_scheduled_time');
    
    if (enabled) {
        optionsDiv.style.display = 'block';
        dateInput.required = true;
        timeInput.required = true;
    } else {
        optionsDiv.style.display = 'none';
        dateInput.required = false;
        timeInput.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all schedule options
    const scheduleCheckboxes = document.querySelectorAll('input[name="schedule_post"]');
    scheduleCheckboxes.forEach(function(checkbox) {
        const prefix = checkbox.id.replace('_schedule_post', '');
        toggleScheduleOptions(prefix);
    });
});
</script>
