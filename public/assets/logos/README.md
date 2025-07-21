# Logo Library

Thư mục này chứa các logo có sẵn cho video generator.

## Cấu trúc file:

### Logo chính:
- `logo1.png` - Logo công ty/thương hiệu chính
- `logo2.png` - Logo phiên bản đơn giản
- `logo3.png` - Logo icon only
- `logo4.png` - Logo text only

### Watermark:
- `watermark1.png` - Watermark trong suốt
- `watermark2.png` - Watermark có nền

## Yêu cầu kỹ thuật:

### Định dạng:
- **Khuyến nghị**: PNG với nền trong suốt
- **Hỗ trợ**: JPG, GIF
- **Kích thước**: Tối đa 5MB

### Kích thước khuyến nghị:
- **Logo nhỏ**: 100x100px
- **Logo vừa**: 200x200px  
- **Logo lớn**: 400x400px
- **Watermark**: 150x150px

### Chất lượng:
- **DPI**: 300 DPI cho chất lượng cao
- **Màu sắc**: RGB color space
- **Nền**: Trong suốt (alpha channel)

## Cách thêm logo mới:

1. **Upload file** vào thư mục này
2. **Đặt tên** theo quy tắc: `logo[số].png` hoặc `watermark[số].png`
3. **Cập nhật** file `logo-section.blade.php` để thêm vào gallery
4. **Test** hiển thị trong video generator

## Preset mặc định:

### TikTok:
- **Vị trí**: Góc trên phải
- **Kích thước**: Nhỏ (5% màn hình)
- **Độ mờ**: 70%
- **Margin**: 20px

### YouTube:
- **Vị trí**: Góc dưới phải  
- **Kích thước**: Vừa (10% màn hình)
- **Độ mờ**: 100%
- **Margin**: 30px

## Lưu ý:

- Logo sẽ được resize tự động theo tỷ lệ
- Nên sử dụng logo có tỷ lệ vuông (1:1) để tránh méo
- Logo trong suốt sẽ hiển thị đẹp hơn trên video
- Test logo trên nhiều loại background khác nhau
