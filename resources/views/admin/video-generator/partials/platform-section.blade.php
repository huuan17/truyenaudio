<!-- Platform Selection Section -->
<!-- Platform Selection Section -->
<style>
/* Compact, consistent channel row across platforms */
.channel-row { display: flex; align-items: center; }
.channel-row label { margin-bottom: 0; margin-right: .5rem; }
.channel-row .flex-grow-1 select.form-control-sm { min-height: 31px; }
</style>



<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="fas fa-share-alt mr-2"></i></h6>
            <ul class="nav nav-tabs card-header-tabs" id="platformTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="#" class="nav-link active" data-platform="none" role="tab" aria-selected="true">
                        <i class="fas fa-video mr-1"></i> Không đăng
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#" class="nav-link" data-platform="tiktok" role="tab" aria-selected="false">
                        <i class="fab fa-tiktok mr-1"></i> TikTok
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#" class="nav-link" data-platform="youtube" role="tab" aria-selected="false">
<style>
/* Compact, consistent channel row across platforms */
.channel-row { display: flex; align-items: center; }
.channel-row label { margin-bottom: 0; margin-right: .5rem; }
.channel-row .flex-grow-1 select.form-control-sm { min-height: 31px; }
</style>

                        <i class="fab fa-youtube mr-1"></i> YouTube
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#" class="nav-link" data-platform="both" role="tab" aria-selected="false">
                        <i class="fas fa-globe mr-1"></i> Cả hai
                    </a>
                </li>
            </ul>
        </div>
        <input type="hidden" id="platform_input" name="platform" value="none">

    </div>
    <div class="card-body">


        <!-- TikTok Settings -->
        <div id="tiktok-settings" style="display: none;">
            <h6 class="text-dark mb-3"><i class="fab fa-tiktok mr-2"></i>Cài đặt TikTok</h6>

            <!-- TikTok Channel Select (compact row) -->
            <div class="form-group channel-row">
                <label for="tiktok_channel_id">Kênh TikTok:</label>
                <div class="flex-grow-1">
                    <select id="tiktok_channel_id" name="tiktok_channel_id" class="form-control form-control-sm">
                        <option value="">-- Chưa chọn kênh --</option>
                        @foreach($tiktokChannels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }} (@{{ $channel->username }})</option>
                        @endforeach
                    </select>
                </div>
            </div>


            <div id="tiktok-settings-body" style="display:none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tiktok_resolution">Độ phân giải</label>
                            <select name="tiktok_resolution" id="tiktok_resolution" class="form-control">
                                <option value="1080x1920" selected>1080x1920 (9:16 - Khuyến nghị)</option>
                                <option value="720x1280">720x1280 (9:16)</option>
                                <option value="1080x1080">1080x1080 (1:1)</option>
                            </select>
                        </div>
                    </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_fps">FPS</label>
                        <select name="tiktok_fps" id="tiktok_fps" class="form-control">
                            <option value="24">24 FPS</option>
                            <option value="30" selected>30 FPS (Khuyến nghị)</option>
                            <option value="60">60 FPS</option>
                        </select>
                    </div>
                </div>
            </div>

            </div> <!-- /#tiktok-settings-body -->

            <div class="form-group">
                <label for="tiktok_duration">Thời lượng tối đa</label>
                <select name="tiktok_duration" id="tiktok_duration" class="form-control">
                    <option value="15">15 giây</option>
                    <option value="30">30 giây</option>
                    <option value="60" selected>60 giây</option>
                    <option value="180">3 phút</option>
                    <option value="600">10 phút</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tiktok_output_name">Tên file output</label>
                <input type="text" name="tiktok_output_name" id="tiktok_output_name"
                       class="form-control" placeholder="tiktok_video_[timestamp]">
                <small class="form-text text-muted">
                    Để trống để tự động tạo tên. Hỗ trợ: [timestamp], [date], [time]
                </small>
        </div> <!-- /#tiktok-settings -->

            </div>
        </div>

        <!-- YouTube Settings -->
        <div id="youtube-settings">
            <h6 class="text-danger mb-3"><i class="fab fa-youtube mr-2"></i>Cài đặt YouTube</h6>

            <!-- YouTube Channel Select (compact row) -->
            <div class="form-group channel-row" id="youtube-channel-group">
                <label for="youtube_channel_id">Kênh YouTube:</label>
                <div class="flex-grow-1">
                    <select id="youtube_channel_id" name="youtube_channel_id" class="form-control form-control-sm">
                        <option value="">-- Chưa chọn kênh --</option>
                        @forelse($youtubeChannels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }} (@{{ $channel->username }})</option>
                        @empty
                            <option value="" disabled>Chưa có kênh YouTube hoạt động</option>
                        @endforelse
                    </select>
                </div>
            </div>

            <div id="youtube-settings-body" style="display:none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="youtube_resolution">Độ phân giải</label>
                            <select name="youtube_resolution" id="youtube_resolution" class="form-control">
                                <option value="1920x1080" selected>1920x1080 (16:9 - Khuyến nghị)</option>
                                <option value="1280x720">1280x720 (16:9)</option>
                                <option value="1080x1920">1080x1920 (9:16 - Shorts)</option>
                                <option value="1080x1080">1080x1080 (1:1)</option>
                            </select>
                        </div>
                    </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_fps">FPS</label>
                        <select name="youtube_fps" id="youtube_fps" class="form-control">
                            <option value="24">24 FPS</option>
                            <option value="30" selected>30 FPS (Khuyến nghị)</option>
                            <option value="60">60 FPS</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="youtube_quality">Chất lượng video</label>
                <select name="youtube_quality" id="youtube_quality" class="form-control">
                    <option value="medium">Medium (Nhanh)</option>
                    <option value="high" selected">High (Khuyến nghị)</option>
                    <option value="very_high">Very High (Chậm)</option>
                </select>
            </div>

            <!-- Publishing Options -->
            <div class="form-group">
                <label class="form-label">Tùy chọn đăng video</label>
                <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                    <label class="btn btn-outline-secondary active mr-2 mb-2">
                        <input type="radio" name="youtube_publish_mode" value="draft" checked>
                        <i class="fas fa-save mr-1"></i>Lưu nháp (không đăng)
                    </label>
                    <label class="btn btn-outline-success mr-2 mb-2">
                        <input type="radio" name="youtube_publish_mode" value="auto">
                        <i class="fas fa-upload mr-1"></i>Đăng ngay lập tức
                    </label>
                    <label class="btn btn-outline-info mb-2">
                        <input type="radio" name="youtube_publish_mode" value="schedule">
                        <i class="fas fa-clock mr-1"></i>Xếp lịch đăng
                    </label>
                </div>
                <small class="form-text text-muted">
                    Chọn cách xử lý video sau khi tạo xong
                </small>
            </div>

            <!-- Schedule Settings -->
            <div id="youtube-schedule-settings" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="youtube_scheduled_date">Ngày đăng</label>
                            <input type="date" name="youtube_scheduled_date" id="youtube_scheduled_date"
                                   class="form-control" min="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="youtube_scheduled_time">Giờ đăng</label>
                            <input type="time" name="youtube_scheduled_time" id="youtube_scheduled_time"
                                   class="form-control" value="12:00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="youtube_output_name">Tên file output</label>
                <input type="text" name="youtube_output_name" id="youtube_output_name"
                       class="form-control" placeholder="youtube_video_[timestamp]">
                <small class="form-text text-muted">
                    Để trống để tự động tạo tên. Hỗ trợ: [timestamp], [date], [time]
                </small>
            </div>
        </div>

        <!-- Both Platforms Settings -->
        <div id="both-settings" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Tạo cho cả hai nền tảng:</strong> Hệ thống sẽ tạo 2 video với cài đặt tối ưu cho từng nền tảng.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tiktok_channel_id_both">Kênh TikTok</label>
                        <select id="tiktok_channel_id_both" name="tiktok_channel_id" class="form-control">
                            <option value="">-- Chưa chọn kênh --</option>
                            @foreach($tiktokChannels as $channel)
                                <option value="{{ $channel->id }}">{{ $channel->name }} (@{{ $channel->username }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="youtube_channel_id_both">Kênh YouTube</label>
                        @if($youtubeChannels->isEmpty())
                            <div class="alert alert-warning mb-0">
                                Chưa có kênh YouTube hoạt động. <a href="{{ route('admin.channels.create') }}" class="alert-link">Thêm kênh</a>
                            </div>
                        @else
                            <select id="youtube_channel_id_both" name="youtube_channel_id" class="form-control">
                                <option value="">-- Chưa chọn kênh --</option>
                                @foreach($youtubeChannels as $channel)
                                    <option value="{{ $channel->id }}">{{ $channel->name }} (@{{ $channel->username }})</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="both_output_prefix">Tiền tố tên file</label>
                <input type="text" name="both_output_prefix" id="both_output_prefix"
                       class="form-control" placeholder="video_[timestamp]">
                <small class="form-text text-muted">
                    Sẽ tạo: [prefix]_tiktok.mp4 và [prefix]_youtube.mp4
                </small>
            </div>
        </div>

        <!-- No Channel Settings -->
        <div id="none-settings">
            <div class="alert alert-secondary">
                <i class="fas fa-video mr-2"></i>
                <strong>Chỉ tạo video:</strong> Video sẽ được tạo và lưu trữ mà không đăng lên kênh nào.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="none_resolution">Độ phân giải</label>
                        <select name="none_resolution" id="none_resolution" class="form-control">
                            <option value="1920x1080" selected>1920x1080 (16:9 - Khuyến nghị)</option>

                            <option value="1280x720">1280x720 (16:9)</option>
                            <option value="1080x1080">1080x1080 (1:1)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="none_fps">FPS</label>
                        <select name="none_fps" id="none_fps" class="form-control">
                            <option value="24">24 FPS</option>
                            <option value="30" selected>30 FPS (Khuyến nghị)</option>
                            <option value="60">60 FPS</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="none_quality">Chất lượng video</label>
                <select name="none_quality" id="none_quality" class="form-control">
                    <option value="medium">Medium (Nhanh)</option>
                    <option value="high" selected>High (Khuyến nghị)</option>
                    <option value="very_high">Very High (Chậm)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="none_output_name">Tên file output</label>
                <input type="text" name="none_output_name" id="none_output_name"
                       class="form-control" placeholder="video_[timestamp]">
                <small class="form-text text-muted">
                    Để trống để tự động tạo tên. Hỗ trợ: [timestamp], [date], [time]
                </small>
            </div>
        </div>
    </div>
</div>



<script>
function getSelectedPlatformSafe() {
  const input = document.getElementById('platform_input');
  return (input && input.value) ? input.value : 'none';
}

function togglePlatformSettings() {
  const selectedPlatform = getSelectedPlatformSafe();

  const tiktokSettings = document.getElementById('tiktok-settings');
  const youtubeSettings = document.getElementById('youtube-settings');
  const bothSettings = document.getElementById('both-settings');
  const noneSettings = document.getElementById('none-settings');

  if (tiktokSettings) tiktokSettings.style.display = 'none';
  if (youtubeSettings) youtubeSettings.style.display = 'none';
  if (bothSettings) bothSettings.style.display = 'none';
  if (noneSettings) noneSettings.style.display = 'none';

  if (selectedPlatform === 'tiktok') {
    if (tiktokSettings) tiktokSettings.style.display = 'block';
  } else if (selectedPlatform === 'youtube') {
    if (youtubeSettings) youtubeSettings.style.display = 'block';
    const ytGroup = document.getElementById('youtube-channel-group');
    if (ytGroup) { ytGroup.style.display = 'block'; ytGroup.classList.remove('d-none'); ytGroup.hidden = false; }
  } else if (selectedPlatform === 'both') {
    if (tiktokSettings) tiktokSettings.style.display = 'block';
    if (youtubeSettings) youtubeSettings.style.display = 'block';
    if (bothSettings) bothSettings.style.display = 'block';
    const ytGroup = document.getElementById('youtube-channel-group');
    if (ytGroup) { ytGroup.style.display = 'block'; ytGroup.classList.remove('d-none'); ytGroup.hidden = false; }
  } else {
    if (noneSettings) noneSettings.style.display = 'block';
  }

  // Show settings body only when a channel is chosen
  const tiktokBody = document.getElementById('tiktok-settings-body');
  const youtubeBody = document.getElementById('youtube-settings-body');
  const ttSelect = document.getElementById('tiktok_channel_id');
  const ytSelect = document.getElementById('youtube_channel_id');

  if (tiktokBody) {
    const showTT = (selectedPlatform === 'tiktok' || selectedPlatform === 'both') && ttSelect && ttSelect.value !== '';
    tiktokBody.style.display = showTT ? 'block' : 'none';
  }
  if (youtubeBody) {
    const showYT = (selectedPlatform === 'youtube' || selectedPlatform === 'both') && ytSelect && ytSelect.value !== '';
    youtubeBody.style.display = showYT ? 'block' : 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Hook header tabs
  document.querySelectorAll('#platformTabs .nav-link').forEach(a => {

    a.addEventListener('click', function(e){
      e.preventDefault();
      const platform = this.getAttribute('data-platform');
      const input = document.getElementById('platform_input');
      if (input) input.value = platform;
      document.querySelectorAll('#platformTabs .nav-link').forEach(n => n.classList.remove('active'));
      this.classList.add('active');
      togglePlatformSettings();
      if (typeof switchPlatform === 'function') { try { switchPlatform(platform); } catch(e){} }
    });
  });

  // Channel select change
  ['tiktok_channel_id','youtube_channel_id'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', togglePlatformSettings);
  });

  // Handle YouTube publishing mode changes
  const publishModeRadios = document.querySelectorAll('input[name="youtube_publish_mode"]');
  publishModeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      const scheduleSettings = document.getElementById('youtube-schedule-settings');
      if (this.value === 'schedule') {
        scheduleSettings.style.display = 'block';
      } else {
        scheduleSettings.style.display = 'none';
      }
    });
  });

  // Initial
  togglePlatformSettings();
});
</script>
