<!-- Video Information Section -->
<script>
// Hide platform info cards until corresponding channel selected
function initPlatformInfoCardsVisibility() {
  const tiktokCard = document.getElementById('tiktok-info-card');
  const youtubeCard = document.getElementById('youtube-info-card');
  const tiktokSelect = document.getElementById('tiktok_channel_id');
  const youtubeSelect = document.getElementById('youtube_channel_id');

  function update() {
    const platform = document.querySelector('input[name="platform"]:checked')?.value || 'none';
    if (tiktokCard) tiktokCard.style.display = (['tiktok','both'].includes(platform) && tiktokSelect && tiktokSelect.value) ? 'block' : 'none';
    if (youtubeCard) youtubeCard.style.display = (['youtube','both'].includes(platform) && youtubeSelect && youtubeSelect.value) ? 'block' : 'none';
  }

  if (tiktokSelect) tiktokSelect.addEventListener('change', update);
  if (youtubeSelect) youtubeSelect.addEventListener('change', update);
  document.querySelectorAll('input[name="platform"]').forEach(r => r.addEventListener('change', update));
  update();
}

document.addEventListener('DOMContentLoaded', initPlatformInfoCardsVisibility);
</script>

<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin video</h6>
    </div>
    <div class="card-body">
        <!-- Video File Name -->
        <div class="form-group">
            <label for="output_name">Tên video (file)</label>
            <input type="text" name="output_name" id="output_name"
                   class="form-control" placeholder="Ví dụ: bo-to-mo-ep-1" maxlength="100">
            <small class="form-text text-muted">
                Không cần nhập .mp4; hệ thống sẽ tự thêm nếu thiếu. Để trống nếu muốn đặt tên tự động.
            </small>
        </div>


        <!-- Video Title -->
        <div class="form-group">
            <label for="video_title">Tiêu đề video *</label>
            <input type="text" name="video_title" id="video_title"
                   class="form-control" placeholder="Nhập tiêu đề video..."
                   maxlength="100" required>
            <small class="form-text text-muted">
                <span id="title-counter">0</span>/100 ký tự. Tiêu đề sẽ được sử dụng cho cả TikTok và YouTube.
            </small>
        </div>

        <!-- Video Description -->
        <div class="form-group">
            <label for="video_description">Mô tả video</label>
            <textarea name="video_description" id="video_description"
                      class="form-control" rows="4"
                      placeholder="Nhập mô tả chi tiết cho video..."></textarea>
            <small class="form-text text-muted">
                <span id="description-counter">0</span> ký tự. Mô tả chi tiết giúp tăng khả năng tìm kiếm.
            </small>
        </div>

        <!-- Platform-specific Settings -->
        <div class="row">
            <div class="col-md-6">
                <!-- TikTok Settings -->
                <div id="tiktok-info-card" class="card border-primary" style="display:none;">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fab fa-tiktok mr-2"></i>TikTok</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tiktok_hashtags">Hashtags TikTok</label>
                            <input type="text" name="tiktok_hashtags" id="tiktok_hashtags"
                                   class="form-control" placeholder="#trending #viral #fyp">
                            <small class="form-text text-muted">
                                Phân cách bằng dấu cách. Tối đa 100 ký tự.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="tiktok_category">Danh mục TikTok</label>
                            <select name="tiktok_category" id="tiktok_category" class="form-control">
                                <option value="">Chọn danh mục...</option>
                                <option value="entertainment">Giải trí</option>
                                <option value="education">Giáo dục</option>
                                <option value="comedy">Hài hước</option>
                                <option value="music">Âm nhạc</option>
                                <option value="dance">Khiêu vũ</option>
                                <option value="food">Ẩm thực</option>
                                <option value="travel">Du lịch</option>
                                <option value="lifestyle">Lối sống</option>
                                <option value="sports">Thể thao</option>
                                <option value="technology">Công nghệ</option>
                                <option value="beauty">Làm đẹp</option>
                                <option value="fashion">Thời trang</option>
                                <option value="gaming">Game</option>
                                <option value="pets">Thú cưng</option>
                                <option value="diy">Tự làm</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tiktok_privacy">Quyền riêng tư</label>
                            <select name="tiktok_privacy" id="tiktok_privacy" class="form-control">
                                <option value="public" selected>Công khai</option>
                                <option value="friends">Bạn bè</option>
                                <option value="private">Riêng tư</option>
                            </select>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="tiktok_allow_comments" id="tiktok_allow_comments"
                                   class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="tiktok_allow_comments">
                                Cho phép bình luận
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="tiktok_allow_duet" id="tiktok_allow_duet"
                                   class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="tiktok_allow_duet">
                                Cho phép Duet
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="tiktok_allow_stitch" id="tiktok_allow_stitch"
                                   class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="tiktok_allow_stitch">
                                Cho phép Stitch
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- YouTube Settings -->
                <div id="youtube-info-card" class="card border-danger" style="display:none;">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="fab fa-youtube mr-2"></i>YouTube</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="youtube_tags">Tags YouTube</label>
                            <input type="text" name="youtube_tags" id="youtube_tags"
                                   class="form-control" placeholder="tag1, tag2, tag3">
                            <small class="form-text text-muted">
                                Phân cách bằng dấu phẩy. Tối đa 500 ký tự.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="youtube_category">Danh mục YouTube</label>
                            <select name="youtube_category" id="youtube_category" class="form-control">
                                <option value="">Chọn danh mục...</option>
                                <option value="1">Film & Animation</option>
                                <option value="2">Autos & Vehicles</option>
                                <option value="10">Music</option>
                                <option value="15">Pets & Animals</option>
                                <option value="17">Sports</option>
                                <option value="19">Travel & Events</option>
                                <option value="20">Gaming</option>
                                <option value="22">People & Blogs</option>
                                <option value="23">Comedy</option>
                                <option value="24">Entertainment</option>
                                <option value="25">News & Politics</option>
                                <option value="26">Howto & Style</option>
                                <option value="27">Education</option>
                                <option value="28">Science & Technology</option>
                                <option value="29">Nonprofits & Activism</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="youtube_privacy">Quyền riêng tư</label>
                            <select name="youtube_privacy" id="youtube_privacy" class="form-control">
                                <option value="public" selected>Công khai</option>
                                <option value="unlisted">Không công khai</option>
                                <option value="private">Riêng tư</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="youtube_language">Ngôn ngữ</label>
                            <select name="youtube_language" id="youtube_language" class="form-control">
                                <option value="vi" selected>Tiếng Việt</option>
                                <option value="en">English</option>
                                <option value="zh">中文</option>
                                <option value="ja">日本語</option>
                                <option value="ko">한국어</option>
                            </select>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="youtube_allow_comments" id="youtube_allow_comments"
                                   class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="youtube_allow_comments">
                                Cho phép bình luận
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="youtube_allow_ratings" id="youtube_allow_ratings"
                                   class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="youtube_allow_ratings">
                                Cho phép đánh giá
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="youtube_notify_subscribers" id="youtube_notify_subscribers"
                                   class="form-check-input" value="1">
                            <label class="form-check-label" for="youtube_notify_subscribers">
                                Thông báo cho người đăng ký
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO & Advanced Settings -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-search mr-2"></i>SEO & Cài đặt nâng cao</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_keywords">Từ khóa SEO</label>
                            <input type="text" name="video_keywords" id="video_keywords"
                                   class="form-control" placeholder="từ khóa 1, từ khóa 2, từ khóa 3">
                            <small class="form-text text-muted">
                                Từ khóa giúp tối ưu tìm kiếm. Phân cách bằng dấu phẩy.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_location">Vị trí địa lý</label>
                            <input type="text" name="video_location" id="video_location"
                                   class="form-control" placeholder="Hà Nội, Việt Nam">
                            <small class="form-text text-muted">
                                Vị trí quay video (tùy chọn).
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_thumbnail">Thumbnail tùy chỉnh</label>
                            <input type="file" name="video_thumbnail" id="video_thumbnail"
                                   class="form-control-file" accept="image/*" onchange="previewThumbnail(this)">
                            <small class="form-text text-muted">
                                JPG, PNG. Khuyến nghị: 1280x720px. Tối đa 2MB.
                            </small>
                            <div id="thumbnail-preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="video_license">Giấy phép</label>
                            <select name="video_license" id="video_license" class="form-control">
                                <option value="standard" selected>Giấy phép YouTube tiêu chuẩn</option>
                                <option value="creative_commons">Creative Commons</option>
                            </select>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="video_made_for_kids" id="video_made_for_kids"
                                   class="form-check-input" value="1">
                            <label class="form-check-label" for="video_made_for_kids">
                                Video dành cho trẻ em
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Templates -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-magic mr-2"></i>Mẫu nhanh</h6>
            </div>
            <div class="card-body">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="applyTemplate('viral')">
                        <i class="fas fa-fire mr-1"></i>Viral Content
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="applyTemplate('educational')">
                        <i class="fas fa-graduation-cap mr-1"></i>Giáo dục
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="applyTemplate('entertainment')">
                        <i class="fas fa-laugh mr-1"></i>Giải trí
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="applyTemplate('tutorial')">
                        <i class="fas fa-tools mr-1"></i>Hướng dẫn
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="clearVideoInfo()">
                        <i class="fas fa-eraser mr-1"></i>Xóa tất cả
                    </button>
                </div>
                <small class="form-text text-muted mt-2">
                    Áp dụng mẫu có sẵn để điền nhanh thông tin video phù hợp với từng loại nội dung.
                </small>
            </div>
        </div>
    </div>
</div>
