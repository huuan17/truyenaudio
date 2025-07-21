# 🎯 Hướng Dẫn Cấu Hình TikTok API

Hướng dẫn chi tiết để thiết lập TikTok Developer App và kết nối với hệ thống upload tự động.

## 📋 Yêu Cầu

- TikTok Business Account
- Website/Domain để làm redirect URI
- SSL Certificate (HTTPS required)

## 🚀 Bước 1: Tạo TikTok Developer Account

1. **Truy cập TikTok for Developers**
   - Đi tới: https://developers.tiktok.com/
   - Đăng nhập bằng TikTok Business Account

2. **Đăng ký Developer Account**
   - Click "Get Started" hoặc "Apply"
   - Điền thông tin công ty/cá nhân
   - Chờ phê duyệt (thường 1-3 ngày làm việc)

## 🔧 Bước 2: Tạo TikTok App

1. **Tạo App mới**
   - Vào Developer Dashboard
   - Click "Create an App"
   - Điền thông tin app:
     - **App Name**: Tên ứng dụng của bạn
     - **App Description**: Mô tả ngắn gọn
     - **Category**: "Content and Publishing"

2. **Cấu hình App Settings**
   - **Platform**: Web
   - **Redirect URI**: `https://yourdomain.com/admin/channels/tiktok/callback`
   - **Scopes**: Chọn các quyền cần thiết:
     - `video.upload` - Upload video
     - `video.publish` - Publish video
     - `user.info.basic` - Thông tin user cơ bản

3. **Lấy Credentials**
   - **Client Key** (Client ID)
   - **Client Secret**
   - Lưu lại thông tin này

## ⚙️ Bước 3: Cấu Hình Laravel

1. **Cập nhật file .env**
   ```env
   # TikTok API Configuration
   TIKTOK_CLIENT_ID=your_client_key_here
   TIKTOK_CLIENT_SECRET=your_client_secret_here
   TIKTOK_REDIRECT_URI=https://yourdomain.com/admin/channels/tiktok/callback
   TIKTOK_SANDBOX=false
   TIKTOK_API_VERSION=v2
   ```

2. **Kiểm tra cấu hình**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

## 🔗 Bước 4: Kết Nối TikTok Channel

1. **Tạo Channel mới**
   - Vào Admin Panel → Channels → Create
   - Chọn Platform: TikTok
   - Điền thông tin cơ bản
   - **Không cần** nhập Access Token thủ công

2. **Kết nối OAuth**
   - Sau khi tạo channel, vào trang chi tiết
   - Click nút "Kết nối TikTok"
   - Sẽ redirect đến TikTok để authorize
   - Đăng nhập và cho phép quyền truy cập
   - Hệ thống tự động lưu tokens

3. **Kiểm tra kết nối**
   - Click nút "Test" để kiểm tra API
   - Nếu thành công sẽ hiển thị thông tin user

## 🧪 Bước 5: Test Upload

1. **Tạo video test**
   - Vào TikTok Video Generator
   - Tạo một video ngắn để test
   - Lên lịch đăng ngay hoặc trong vài phút

2. **Kiểm tra upload**
   ```bash
   # Xử lý scheduled posts thủ công
   php artisan posts:process-scheduled --dry-run
   
   # Upload thực tế
   php artisan posts:process-scheduled
   ```

3. **Kiểm tra logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## 🔄 Bước 6: Cấu Hình Tự Động

1. **Setup Cron Jobs**
   ```bash
   # Thêm vào crontab
   * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Hoặc setup Supervisor (khuyến nghị)**
   ```ini
   [program:laravel-scheduler]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/project/artisan schedule:work
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/path/to/your/project/storage/logs/scheduler.log
   ```

## 🛠️ Commands Hữu Ích

```bash
# Kiểm tra TikTok tokens
php artisan tiktok:refresh-tokens --check-only

# Refresh tokens thủ công
php artisan tiktok:refresh-tokens --force

# Xử lý scheduled posts
php artisan posts:process-scheduled --limit=5

# Xem logs realtime
tail -f storage/logs/laravel.log | grep -i tiktok
```

## 🚨 Troubleshooting

### Lỗi "Invalid redirect_uri"
- Kiểm tra TIKTOK_REDIRECT_URI trong .env
- Đảm bảo URL chính xác trong TikTok App settings
- Phải sử dụng HTTPS

### Lỗi "Invalid client credentials"
- Kiểm tra TIKTOK_CLIENT_ID và TIKTOK_CLIENT_SECRET
- Đảm bảo app đã được approve
- Kiểm tra scopes đã được cấp phép

### Lỗi "Token expired"
- Hệ thống tự động refresh token
- Nếu refresh token cũng hết hạn, cần authorize lại
- Kiểm tra logs để xem chi tiết

### Upload thất bại
- Kiểm tra file video có tồn tại không
- Đảm bảo format video được TikTok hỗ trợ (MP4, MOV)
- Kiểm tra kích thước file (< 500MB)
- Kiểm tra độ dài video (15s - 10 phút)

## 📊 Monitoring

1. **Channel Status**
   - Vào Admin → Channels
   - Kiểm tra trạng thái kết nối
   - Xem thống kê upload

2. **Scheduled Posts**
   - Vào Admin → Scheduled Posts
   - Theo dõi trạng thái upload
   - Xem lỗi nếu có

3. **Logs**
   - `storage/logs/laravel.log` - General logs
   - Admin panel có thể xem logs upload

## 🔐 Bảo Mật

- **Không** commit file .env vào git
- Sử dụng HTTPS cho redirect URI
- Định kỳ rotate client secret
- Monitor API usage để tránh rate limit
- Backup tokens quan trọng

## 📈 Giới Hạn API

- **Rate Limits**: 
  - 100 requests/minute per app
  - 10 video uploads/day per user (sandbox)
  - 50 video uploads/day per user (production)
- **File Limits**:
  - Max size: 500MB
  - Duration: 15s - 10 minutes
  - Formats: MP4, MOV, WEBM

## 🆘 Hỗ Trợ

- TikTok Developer Documentation: https://developers.tiktok.com/doc/
- TikTok Developer Community: https://developers.tiktok.com/community/
- GitHub Issues: [Your repo issues page]
