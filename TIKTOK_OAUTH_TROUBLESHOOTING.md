# TikTok OAuth Troubleshooting Guide

## Vấn đề đã được khắc phục
✅ **PKCE (Proof Key for Code Exchange) đã được implement**

Lỗi ban đầu: "code_challenge - Tham khảo Tài liệu cho nhà phát triển"
- **Nguyên nhân:** TikTok yêu cầu PKCE cho OAuth flow
- **Giải pháp:** Đã thêm `code_challenge` và `code_challenge_method=S256`

## URL mới với PKCE
```
https://www.tiktok.com/v2/auth/authorize?client_key=aw2ya06arqm4bjdd&scope=video.upload%2Cvideo.publish%2Cuser.info.basic&response_type=code&redirect_uri=http%3A%2F%2Flocalhost%3A8000%2Fadmin%2Fchannels%2Ftiktok%2Foauth%2Fcallback&state=xyz&code_challenge=abc123&code_challenge_method=S256
```

## Nguyên nhân có thể (nếu vẫn lỗi)

### 1. Client Key không hợp lệ
- Client Key `aw2ya06arqm4bjdd` có thể không phải là client key thật từ TikTok Developer Portal
- Hoặc app chưa được approve bởi TikTok

### 2. Redirect URI chưa được whitelist
- URL `http://localhost:8000/admin/channels/tiktok/oauth/callback` chưa được thêm vào whitelist trong TikTok Developer Portal
- TikTok yêu cầu redirect URI phải được đăng ký trước

### 3. Scope không được phép
- Scope `video.upload,video.publish,user.info.basic` có thể chưa được approve cho app
- Một số scope cần review từ TikTok

### 4. Environment mismatch
- Đang sử dụng production URL nhưng app có thể chỉ hoạt động trong sandbox

## Giải pháp

### Bước 1: Kiểm tra TikTok Developer Portal
1. Truy cập https://developers.tiktok.com/
2. Đăng nhập và vào app của bạn
3. Kiểm tra:
   - **Client Key** có đúng không
   - **Client Secret** có đúng không
   - **Redirect URIs** đã thêm `http://localhost:8000/admin/channels/tiktok/oauth/callback` chưa
   - **Scopes** đã được approve chưa

### Bước 2: Cập nhật cấu hình
Cập nhật file `.env` với thông tin đúng:
```env
TIKTOK_CLIENT_ID=your_real_client_key
TIKTOK_CLIENT_SECRET=your_real_client_secret
TIKTOK_REDIRECT_URI=http://localhost:8000/admin/channels/tiktok/oauth/callback
TIKTOK_SANDBOX=false
```

### Bước 3: Test với trang debug
1. Truy cập: http://localhost:8000/admin/test-tiktok-oauth
2. Nhập Client Key và Client Secret thật
3. Nhấn "Test OAuth URL" để kiểm tra
4. Nhấn "Start OAuth" để test thực tế

### Bước 4: Kiểm tra logs
Xem logs trong `storage/logs/laravel.log` để tìm thông tin debug:
```bash
tail -f storage/logs/laravel.log
```

### Bước 5: Alternative - Sử dụng Sandbox
Nếu production không hoạt động, thử sandbox:
```env
TIKTOK_SANDBOX=true
```

## Test Commands

### Test cấu hình:
```bash
php debug_tiktok_oauth.php
```

### Test OAuth flow:
1. Mở http://localhost:8000/admin/test-tiktok-oauth
2. Nhập credentials
3. Test URL generation
4. Test OAuth flow

## Lưu ý quan trọng

1. **Localhost limitations**: TikTok có thể không cho phép localhost trong production. Cần sử dụng domain thật hoặc ngrok.

2. **App Review**: Một số scope cần được TikTok review và approve trước khi sử dụng.

3. **Rate Limiting**: TikTok có rate limit cho OAuth requests.

4. **HTTPS Requirement**: Production TikTok OAuth yêu cầu HTTPS.

## Giải pháp tạm thời

Nếu OAuth không hoạt động, có thể:
1. Sử dụng manual token input (như hiện tại)
2. Sử dụng TikTok Business API thay vì Consumer API
3. Sử dụng ngrok để tạo HTTPS tunnel cho localhost

## Next Steps

1. Kiểm tra TikTok Developer Portal settings
2. Cập nhật credentials trong .env
3. Test với trang debug
4. Nếu vẫn lỗi, cân nhắc sử dụng ngrok hoặc deploy lên server có domain thật
