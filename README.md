# Audio Lara - Hệ thống quản lý truyện audio

Hệ thống quản lý truyện audio với tính năng chuyển đổi text-to-speech và tạo video tự động.

## Tính năng chính

- 📚 **Quản lý truyện và chapter**: Upload, quét và quản lý nội dung truyện
- 🎵 **Text-to-Speech**: Chuyển đổi text thành audio bằng VBee API
- 🎬 **Tạo video tự động**: Kết hợp audio + hình ảnh thành video MP4
- 🔍 **Lọc và tìm kiếm**: Lọc chapter theo trạng thái text, audio, video
- 📱 **Giao diện responsive**: Sử dụng AdminLTE + Bootstrap

## Yêu cầu hệ thống

- PHP >= 8.1
- MySQL >= 5.7
- Composer
- FFmpeg (cho tạo video)
- VBee API Token

## Cài đặt

### 1. Clone repository
```bash
git clone <repository-url>
cd audio-lara
```

### 2. Cài đặt dependencies
```bash
composer install
npm install
```

### 3. Cấu hình môi trường
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Cấu hình database trong .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=audio_lara
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Cấu hình VBee API trong .env
```env
VBEE_API_URL=https://vbee.vn/api/v1/synthesize
VBEE_API_TOKEN=your_vbee_api_token
VBEE_VOICE_CODE=ban_mai
```

### 6. Chạy migration
```bash
php artisan migrate
```

### 7. Tạo symbolic link cho storage
```bash
php artisan storage:link
```

### 8. Build assets
```bash
npm run build
```

## Deployment lên hosting

### 1. Upload code lên hosting
```bash
git add .
git commit -m "Initial commit"
git push origin main
```

### 2. Trên hosting, clone repository
```bash
git clone <repository-url> .
composer install --no-dev --optimize-autoloader
```

### 3. Cấu hình .env cho production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

### 4. Chạy các lệnh deployment
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link
```

### 5. Cấu hình quyền thư mục
```bash
chmod -R 755 storage bootstrap/cache
```

## Sử dụng

### Quét chapter từ storage
```bash
php artisan chapters:scan {story_id} --force --with-content
```

### Chuyển đổi TTS
- Truy cập `/stories/{id}/chapters` để xem danh sách chapter
- Click nút TTS để chuyển đổi từng chapter
- Hoặc sử dụng trang TTS để chuyển đổi hàng loạt

### Tạo video
```bash
php artisan generate:video {story_id} {chapter_number}
```

## Cấu trúc thư mục

```
storage/
├── truyen/
│   ├── {folder_name}/          # Thư mục chứa chapter text
│   ├── mp3-{folder_name}/      # Thư mục chứa file audio
│   └── video-{folder_name}/    # Thư mục chứa file video
```

## API Endpoints

- `GET /stories` - Danh sách truyện
- `GET /stories/{id}/chapters` - Danh sách chapter
- `POST /chapters/{id}/tts` - Chuyển đổi TTS
- `POST /stories/{id}/generate-video` - Tạo video

## Troubleshooting

### Lỗi permission
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Lỗi VBee API
- Kiểm tra API token trong .env
- Kiểm tra kết nối internet
- Xem log tại `storage/logs/laravel.log`

### Lỗi FFmpeg
```bash
# Ubuntu/Debian
sudo apt install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg
```

## License

MIT License
