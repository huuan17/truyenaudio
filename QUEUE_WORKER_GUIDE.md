# Queue Worker Guide - Hướng dẫn Queue Worker

## 🚀 Tại sao cần Queue Worker?

Queue Worker là tiến trình chạy nền để xử lý các công việc nặng như:
- **Tạo video** từ ảnh và âm thanh
- **Crawl truyện** từ các website
- **Chuyển đổi TTS** (Text-to-Speech)

Nếu không có Queue Worker chạy, các task sẽ **không được xử lý**.

## 📋 Kiểm tra trạng thái

### 1. Kiểm tra trong Admin Panel:
- Truy cập: `http://localhost:8000/admin/video-queue`
- Xem phần "Queue Worker Status"
- Nếu hiển thị "Running" = OK
- Nếu hiển thị "Stopped" = Cần start worker

### 2. Kiểm tra bằng command:
```bash
php artisan queue:monitor
```

## 🔧 Cách start Queue Worker

### Phương pháp 1: Sử dụng file .bat (Windows)
```bash
# Chạy file có sẵn
start-queue-worker.bat

# Chọn option 4: Video queue only
```

### Phương pháp 2: Command thủ công
```bash
# Chỉ xử lý video queue
php artisan queue:work --queue=video --timeout=300 --memory=512 --tries=3

# Xử lý tất cả queue
php artisan queue:work --timeout=300 --memory=512 --tries=3
```

### Phương pháp 3: Background process (Linux/Mac)
```bash
# Chạy nền
nohup php artisan queue:work --queue=video > storage/logs/queue.log 2>&1 &

# Hoặc sử dụng supervisor (production)
```

## ⚙️ Tham số quan trọng

| Tham số | Ý nghĩa | Giá trị khuyến nghị |
|---------|---------|-------------------|
| `--queue=video` | Chỉ xử lý video queue | video |
| `--timeout=300` | Timeout cho mỗi job (giây) | 300 (5 phút) |
| `--memory=512` | Giới hạn RAM (MB) | 512 |
| `--tries=3` | Số lần retry khi fail | 3 |
| `--sleep=3` | Thời gian chờ giữa các job | 3 giây |

## 🔍 Troubleshooting

### Vấn đề 1: Worker bị dừng
**Triệu chứng:** Task không được xử lý, status "Stopped"
**Giải pháp:**
```bash
# Restart worker
php artisan queue:restart
php artisan queue:work --queue=video
```

### Vấn đề 2: Job bị stuck
**Triệu chứng:** Task ở trạng thái "processing" quá lâu
**Giải pháp:**
```bash
# Clear failed jobs
php artisan queue:flush

# Restart worker
php artisan queue:restart
```

### Vấn đề 3: Memory leak
**Triệu chứng:** Worker ngừng hoạt động sau một thời gian
**Giải pháp:**
```bash
# Thêm max-jobs để restart worker định kỳ
php artisan queue:work --queue=video --max-jobs=10
```

### Vấn đề 4: Permission errors
**Triệu chứng:** Lỗi không thể tạo file
**Giải pháp:**
```bash
# Fix permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## 📊 Monitoring

### 1. Real-time monitoring:
```bash
# Xem queue status
php artisan queue:monitor

# Xem failed jobs
php artisan queue:failed
```

### 2. Log files:
- **Laravel log:** `storage/logs/laravel.log`
- **Queue log:** `storage/logs/queue.log` (nếu redirect)

### 3. Database monitoring:
```sql
-- Xem jobs đang chờ
SELECT * FROM jobs WHERE queue = 'video';

-- Xem failed jobs
SELECT * FROM failed_jobs;
```

## 🚀 Production Setup

### 1. Sử dụng Supervisor (Linux):
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=video --sleep=3 --tries=3 --max-time=3600
directory=/path/to/project
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
```

### 2. Sử dụng systemd (Linux):
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/artisan queue:work --queue=video

[Install]
WantedBy=multi-user.target
```

### 3. Windows Service:
Sử dụng NSSM (Non-Sucking Service Manager) để tạo Windows Service.

## 📝 Best Practices

1. **Luôn monitor** queue worker status
2. **Restart worker** khi deploy code mới
3. **Set memory limit** để tránh memory leak
4. **Use max-jobs** để restart worker định kỳ
5. **Monitor failed jobs** và xử lý kịp thời
6. **Backup queue data** trước khi clear
7. **Test queue** trước khi production

## 🆘 Emergency Commands

```bash
# Dừng tất cả workers
php artisan queue:restart

# Clear tất cả jobs
php artisan queue:flush

# Clear failed jobs
php artisan queue:forget-failed

# Retry failed jobs
php artisan queue:retry all

# Xem queue status
php artisan queue:monitor
```

## 📞 Support

Nếu gặp vấn đề:
1. Kiểm tra log files
2. Restart queue worker
3. Clear cache: `php artisan cache:clear`
4. Check permissions
5. Liên hệ admin nếu vẫn không giải quyết được
