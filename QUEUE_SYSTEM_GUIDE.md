# 🎬 Queue System Guide - Video & Crawl

## 📋 Tổng quan

Hệ thống Queue được thiết kế để xử lý các tác vụ nặng một cách tuần tự, tránh quá tải máy chủ:
- **🎬 Video Generation**: Xử lý tạo video TikTok/YouTube
- **🕷️ Auto Crawl**: Xử lý crawl truyện tự động

## 🔧 Cài đặt và Khởi động

### 1. Tạo Database Tables
```bash
# Truy cập URL để tạo tables (chỉ cần chạy 1 lần)
http://localhost:8000/admin/create-queue-tables
```

### 2. Cấu hình Queue
File `.env` đã được cập nhật:
```
QUEUE_CONNECTION=database
```

### 3. Khởi động Queue Worker

#### **🎬 Cho Video Generation:**
```bash
# Chạy file batch (Windows)
start-queue-worker.bat
# Chọn option 4: Video queue only

# Hoặc chạy command trực tiếp
php artisan queue:work --queue=video --timeout=1800 --memory=512 --tries=3
```

#### **🕷️ Cho Auto Crawl:**
```bash
# Chạy file batch (Windows)
start-queue-worker.bat
# Chọn option 2: Crawl queue only

# Hoặc chạy command trực tiếp
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
```

#### **⚡ Cho Tất cả Queues:**
```bash
# Chạy file batch (Windows)
start-queue-worker.bat
# Chọn option 1: All queues

# Hoặc chạy command trực tiếp
php artisan queue:work --timeout=3600 --memory=512 --tries=3 --sleep=3
```

## 🎯 Cách sử dụng

### 🕷️ **Auto Crawl System**

#### **1. Khởi động Crawl Queue Worker:**
```bash
start-queue-worker.bat
# Chọn option 2: Crawl queue only
# Giữ cửa sổ mở - ĐỪNG ĐÓNG!
```

#### **2. Sử dụng Auto Crawl:**
1. **Truy cập**: `http://localhost:8000/admin/crawl-monitor`
2. **Click**: "Thêm Truyện"
3. **Chọn**: Story từ dropdown
4. **Click**: "Thêm vào Queue"
5. **Đợi**: Job hoàn thành trước khi add story tiếp

#### **3. Monitoring:**
- **Dashboard**: Auto-refresh mỗi 30 giây
- **Progress**: Real-time progress bars
- **Logs**: `tail -f storage/logs/laravel.log | grep crawl`

#### **⚠️ Lưu ý quan trọng:**
- **Chỉ add một story mỗi lần** để tránh conflict
- **Giữ Queue Worker chạy** liên tục
- **Đợi job hoàn thành** trước khi add job mới

### Single Video Generation
1. Truy cập `/admin/tiktok` hoặc `/admin/youtube`
2. Điền thông tin và submit form
3. Nhận thông báo: "Video đã được thêm vào hàng đợi xử lý!"
4. Theo dõi tiến trình tại `/admin/video-queue`

### Batch Video Generation
1. Chọn "Batch Mode" trong form
2. Thêm nhiều video items
3. Submit form
4. Tất cả video sẽ được xử lý tuần tự

## 📊 Queue Dashboard

### Truy cập: `/admin/video-queue`

#### Thống kê Queue:
- **Đang chờ**: Số task pending
- **Đang xử lý**: Số task đang process
- **Hoàn thành hôm nay**: Số task completed
- **Thất bại hôm nay**: Số task failed

#### Quản lý Task:
- **👁️ Xem chi tiết**: Xem thông tin task
- **⏹️ Hủy**: Cancel task đang chờ
- **🔄 Retry**: Thử lại task thất bại
- **🗑️ Xóa**: Xóa task hoàn thành/thất bại

## 🔄 Task Lifecycle

```
Pending → Processing → Completed/Failed
   ↓         ↓            ↓
 Cancel   Progress    Retry/Delete
```

### Task Status:
- **Pending**: Đang chờ xử lý
- **Processing**: Đang xử lý
- **Completed**: Hoàn thành thành công
- **Failed**: Thất bại
- **Cancelled**: Đã hủy

## 🛠️ Troubleshooting

### Queue Worker không chạy:
```bash
# Kiểm tra queue status
php artisan queue:monitor

# Restart worker
php artisan queue:restart
```

### Task bị stuck:
```bash
# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

### Database issues:
```bash
# Recreate tables
http://localhost:8000/admin/create-queue-tables
```

## 🧪 Testing

### Test Routes (Development only):
- `/admin/test-video-queue` - Tạo single test task
- `/admin/test-batch-queue` - Tạo batch test tasks
- `/admin/test-failed-task` - Tạo failed task để test retry
- `/admin/test-process-queue` - Simulate processing

### Test Workflow:
1. Tạo test task
2. Kiểm tra trong Queue Dashboard
3. Test các tính năng cancel/retry/delete
4. Monitor real-time updates

## 📈 Performance

### Resource Management:
- **Sequential Processing**: Chỉ 1 video tại 1 thời điểm
- **Memory Limit**: 512MB per job
- **Timeout**: 30 phút per job
- **Retry**: 3 lần với backoff

### Batch Processing:
- **Delay**: 5 giây giữa các job
- **Individual Tracking**: Mỗi video có task riêng
- **Batch Progress**: Track tiến trình tổng thể

## 🔒 Security

### User Permissions:
- User chỉ xem được task của mình
- Admin xem được tất cả task
- Chỉ owner hoặc admin mới cancel/retry được

### Data Protection:
- Temp files được cleanup tự động
- Sensitive data không log
- Task parameters được encrypt trong database

## 📱 Real-time Updates

### Auto Refresh:
- Dashboard tự động refresh mỗi 10 giây
- Progress bars cập nhật real-time
- Status badges thay đổi theo thời gian thực

### AJAX Endpoints:
- `/admin/video-queue/status` - Get queue status
- `/admin/video-queue/{task}` - Get task details
- `/admin/video-queue/{task}/cancel` - Cancel task
- `/admin/video-queue/{task}/retry` - Retry task

## 🚀 Production Deployment

### Queue Worker Service:
```bash
# Tạo systemd service (Linux)
sudo nano /etc/systemd/system/laravel-worker.service

[Unit]
Description=Laravel queue worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/artisan queue:work --queue=video --sleep=3 --tries=3 --timeout=1800

[Install]
WantedBy=multi-user.target
```

### Monitoring:
```bash
# Enable service
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker

# Check status
sudo systemctl status laravel-worker
```

### Scaling:
- Tăng số worker processes khi cần
- Chuyển sang Redis/SQS cho performance cao hơn
- Implement horizontal scaling với multiple servers

## 📋 Best Practices

### Development:
1. Luôn test với small batch trước
2. Monitor memory usage
3. Cleanup test data thường xuyên
4. Backup database trước khi deploy

### Production:
1. Setup monitoring alerts
2. Regular cleanup old tasks
3. Monitor disk space (temp files)
4. Setup log rotation
5. Backup queue data

## 🆘 Support

### Common Issues:
1. **"Table not found"** → Chạy create-queue-tables
2. **"Worker not processing"** → Check worker status
3. **"Memory exceeded"** → Increase memory limit
4. **"Timeout"** → Increase timeout setting

### Debug Commands:
```bash
# Check queue size
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Clear all jobs
php artisan queue:flush

# Restart workers
php artisan queue:restart
```

---

## 🎉 Kết luận

Hệ thống Queue đảm bảo:
- ✅ Không quá tải server
- ✅ Xử lý tuần tự ổn định
- ✅ Tracking chi tiết
- ✅ Recovery khi lỗi
- ✅ User experience tốt
- ✅ Admin management tools
- ✅ Real-time monitoring

**Happy Video Generation! 🎬✨**
