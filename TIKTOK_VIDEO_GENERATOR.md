# TikTok Video Generator

Hệ thống tạo video review sản phẩm tự động cho TikTok từ kịch bản text, video sản phẩm và ảnh sản phẩm.

## 🎯 Tính năng chính

- **Text-to-Speech**: Chuyển đổi kịch bản review thành giọng nói AI bằng VBee API
- **Video Processing**: Xóa tiếng video gốc và cắt video theo thời lượng audio
- **Audio Merging**: Ghép giọng nói AI vào video sản phẩm
- **TikTok Optimization**: Tối ưu hóa video về tỷ lệ 9:16 cho TikTok
- **Product Image**: Tùy chọn ghép ảnh sản phẩm vào cuối video

## 🔄 Quy trình xử lý

1. **Input**: 
   - Kịch bản review (text)
   - Video quay sản phẩm (MP4/AVI/MOV)
   - Ảnh sản phẩm (JPG/PNG) - tùy chọn

2. **Processing**:
   - Chuyển đổi text → audio bằng VBee API
   - Xóa tiếng video gốc
   - Cắt/lặp video để khớp thời lượng audio
   - Ghép audio vào video
   - Tối ưu hóa cho TikTok (9:16 ratio)

3. **Output**: Video review hoàn chỉnh định dạng MP4

## 🛠️ Yêu cầu hệ thống

- **FFmpeg**: Cài đặt và có thể truy cập từ command line
- **VBee API**: App ID và Access Token
- **PHP**: >= 8.1 với extension fileinfo
- **Storage**: Đủ dung lượng cho file tạm thời

## ⚙️ Cấu hình

### 1. Cài đặt FFmpeg

**Windows:**
```bash
# Download từ https://ffmpeg.org/download.html
# Thêm vào PATH environment variable
```

**Linux/Ubuntu:**
```bash
sudo apt update
sudo apt install ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

### 2. Cấu hình VBee API

Thêm vào file `.env`:
```env
VBEE_APP_ID=your_app_id
VBEE_ACCESS_TOKEN=your_access_token
```

## 🚀 Sử dụng

### 1. Qua Web Interface

1. Truy cập `/admin/tiktok`
2. Nhập kịch bản review
3. Upload video sản phẩm
4. Upload ảnh sản phẩm (tùy chọn)
5. Chọn giọng đọc và cài đặt
6. Click "Tạo Video TikTok"

### 2. Qua Command Line

```bash
php artisan tiktok:generate \
  --script="Đây là sản phẩm tuyệt vời..." \
  --product-video="/path/to/product.mp4" \
  --product-image="/path/to/product.jpg" \
  --voice="hn_female_ngochuyen_full_48k-fhg" \
  --bitrate=128 \
  --speed=1.0 \
  --volume=18 \
  --output="review_product.mp4"
```

## 📁 Cấu trúc thư mục

```
storage/
├── app/
│   ├── tiktok_videos/          # Video output cuối cùng
│   └── temp/                   # Thư mục tạm thời
│       └── tiktok_{id}/        # Thư mục xử lý từng video
│           ├── product_video.mp4
│           ├── product_image.jpg
│           ├── script_audio.mp3
│           ├── muted_video.mp4
│           ├── trimmed_video.mp4
│           └── temp_optimized.mp4
```

## 🎛️ Tùy chọn cấu hình

### Giọng đọc VBee
- `hn_female_ngochuyen_full_48k-fhg`: Ngọc Huyền (Nữ - Hà Nội)
- `hn_male_manhtung_full_48k-fhg`: Mạnh Tùng (Nam - Hà Nội)
- `sg_female_thaotrinh_full_48k-fhg`: Thảo Trinh (Nữ - Sài Gòn)
- `sg_male_minhhoang_full_48k-fhg`: Minh Hoàng (Nam - Sài Gòn)

### Chất lượng Audio
- 64 kbps: Chất lượng thấp, file nhỏ
- 128 kbps: Chất lượng trung bình (khuyến nghị)
- 192 kbps: Chất lượng cao
- 256 kbps: Chất lượng rất cao
- 320 kbps: Chất lượng tối đa

### Tốc độ đọc
- 0.5x: Rất chậm
- 1.0x: Bình thường (khuyến nghị)
- 1.5x: Nhanh
- 2.0x: Rất nhanh

### Mức âm lượng
- **18dB**: Mặc định (khuyến nghị cho TikTok)
- **0dB**: Âm lượng gốc từ VBee API
- **Âm (-30 đến 0dB)**: Giảm âm lượng
- **Dương (0 đến 30dB)**: Tăng âm lượng
- **Lưu ý**: TikTok thường cần âm lượng cao để cạnh tranh với nội dung khác

## 🎨 Tối ưu hóa TikTok

- **Tỷ lệ khung hình**: 9:16 (1080x1920)
- **Codec video**: H.264
- **Codec audio**: AAC
- **Frame rate**: 30 FPS
- **Bitrate**: Tự động tối ưu

## 🔧 Troubleshooting

### Lỗi FFmpeg không tìm thấy
```bash
# Kiểm tra FFmpeg
ffmpeg -version

# Nếu không có, cài đặt lại FFmpeg
```

### Lỗi VBee API
- Kiểm tra App ID và Access Token
- Kiểm tra kết nối internet
- Kiểm tra quota API

### Lỗi dung lượng
- Kiểm tra dung lượng đĩa còn trống
- Xóa các file tạm thời cũ

### Video output bị lỗi
- Kiểm tra định dạng video input
- Kiểm tra codec video gốc
- Thử với video khác

## 📊 Giới hạn

- **Video input**: Tối đa 100MB
- **Ảnh input**: Tối đa 10MB
- **Text script**: Tối đa 5000 ký tự
- **Thời lượng**: Tối đa 3 phút (khuyến nghị cho TikTok)

## 🔄 API Endpoints

- `GET /admin/tiktok` - Giao diện tạo video
- `POST /admin/tiktok/generate` - Tạo video mới
- `DELETE /admin/tiktok/delete` - Xóa video
- `GET /admin/tiktok/download/{filename}` - Download video
- `GET /admin/tiktok/status` - Kiểm tra trạng thái xử lý

## 📝 Ví dụ kịch bản

```text
Chào mọi người! Hôm nay mình sẽ review chiếc iPhone 15 Pro Max mới nhất.

Thiết kế của máy rất đẹp với khung titan cao cấp, cảm giác cầm rất chắc chắn.

Camera 48MP chụp ảnh cực kỳ sắc nét, đặc biệt là chế độ chụp đêm.

Hiệu năng với chip A17 Pro mạnh mẽ, chơi game mượt mà không lag.

Pin sử dụng cả ngày không lo hết, sạc nhanh 20W rất tiện lợi.

Tổng kết: Đây là chiếc điện thoại đáng mua nhất năm 2024!
```

## 🎯 Tips tối ưu

1. **Kịch bản ngắn gọn**: 30-60 giây cho TikTok
2. **Video sản phẩm**: Quay ổn định, ánh sáng tốt
3. **Ảnh sản phẩm**: Độ phân giải cao, nền sạch
4. **Giọng đọc**: Chọn phù hợp với nội dung
5. **Tốc độ**: 1.0x hoặc 1.25x cho dễ nghe
