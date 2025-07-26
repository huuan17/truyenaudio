# 🚀 Queue Workers Setup Guide

## ⚡ Quick Start

### 1. **Windows (Recommended)**
```bash
# Mở Command Prompt tại thư mục dự án
start-queue-worker.bat

# Chọn option phù hợp:
# 1 = All queues (crawl, video, tts)
# 2 = Crawl queue only  
# 3 = Default queue (tts, video)
# 4 = Video queue only
```

### 2. **Linux/Mac**
```bash
# Tất cả queues
php artisan queue:work --timeout=3600 --memory=512 --tries=3

# Hoặc queue cụ thể
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024
```

## 📊 Queue Types

| Queue | Purpose | Timeout | Memory | Tries |
|-------|---------|---------|--------|-------|
| **crawl** | Crawl stories from websites | 4 hours | 1024MB | 1 |
| **video** | Generate TikTok/YouTube videos | 30 min | 512MB | 3 |
| **tts** | Text-to-Speech conversion | 5 min | 256MB | 3 |
| **default** | General tasks | 30 min | 512MB | 3 |

## 🔧 Commands

### **Start Workers**
```bash
# All queues
php artisan queue:work --timeout=3600 --memory=512 --tries=3 --sleep=3

# Crawl only (for heavy crawling)
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30

# Video only (for video generation)
php artisan queue:work --queue=video --timeout=1800 --memory=512 --tries=3

# TTS only (for audio conversion)
php artisan queue:work --queue=tts --timeout=300 --memory=256 --tries=3
```

### **Monitor & Control**
```bash
# Check status
php artisan queue:monitor

# Restart workers
php artisan queue:restart

# Clear all jobs
php artisan queue:flush

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:forget-failed
```

## 🆘 Troubleshooting

### **Worker not running**
```bash
php artisan queue:restart
php artisan queue:work --queue=video
```

### **Jobs stuck**
```bash
php artisan queue:flush
php artisan queue:restart
```

### **Memory issues**
```bash
php artisan queue:work --memory=1024
```

### **Database issues**
```bash
php artisan migrate
# Or visit: http://localhost:8000/admin/create-queue-tables
```

## 📱 Admin Interface

Visit: **http://localhost:8000/admin/help/queue-workers**

Features:
- ✅ Real-time queue status monitoring
- ✅ One-click command execution
- ✅ Interactive troubleshooting guide
- ✅ Emergency actions
- ✅ Best practices recommendations

## 🔗 Quick Links

- **Video Queue Dashboard**: `/admin/video-queue`
- **Crawl Monitor**: `/admin/crawl-monitor`  
- **TTS Monitor**: `/admin/tts-monitor`
- **Queue Workers Guide**: `/admin/help/queue-workers`

## ⚠️ Important Notes

1. **Always run queue workers** when using:
   - Auto crawl features
   - Video generation
   - TTS conversion

2. **Restart workers** after:
   - Code deployment
   - Configuration changes
   - System updates

3. **Monitor regularly**:
   - Check worker status
   - Clear failed jobs
   - Monitor memory usage

4. **For production**:
   - Use supervisor or systemd
   - Setup monitoring alerts
   - Regular cleanup of old jobs

---

**Need help?** Visit the admin interface at `/admin/help/queue-workers` for interactive guidance!
