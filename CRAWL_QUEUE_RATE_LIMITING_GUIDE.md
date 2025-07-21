# 🚦 Crawl Queue Rate Limiting System

## 📋 Tổng quan

Hệ thống Rate Limiting cho Auto Crawl được thiết kế để giải quyết vấn đề overload khi thêm nhiều truyện cùng lúc. Thay vì crawl tất cả truyện ngay lập tức, hệ thống sẽ phân bổ đều các jobs trong thời gian dài.

## ✨ Tính năng chính

### 🎯 **Smart Scheduling**
- **Giới hạn số lượng**: Tối đa 10 truyện mỗi lần auto crawl
- **Phân bổ thời gian**: Jobs được schedule đều trong 50 phút
- **Delay tối ưu**: 2-10 phút giữa các jobs tùy theo số lượng
- **Queue riêng**: Sử dụng queue `crawl` riêng biệt

### ⏰ **Scheduling Logic**
```
Nếu có 5 truyện → Delay 10 phút/job → Hoàn thành trong 50 phút
Nếu có 10 truyện → Delay 5 phút/job → Hoàn thành trong 50 phút  
Nếu có 25 truyện → Chỉ xử lý 10 truyện đầu tiên
```

### 🔄 **Auto Crawl Frequency**
- **Trước**: Mỗi 1 giờ
- **Sau**: Mỗi 2 giờ (giảm tần suất)
- **Cleanup**: Mỗi 6 giờ tự động dọn dẹp failed jobs

## 🛠️ Cài đặt và Sử dụng

### 1. **Queue Configuration**

File `config/queue.php` đã được cập nhật:
```php
'database_crawl' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'crawl',
    'retry_after' => 14400, // 4 hours
    'after_commit' => false,
],
```

### 2. **Start Queue Workers**

#### **Crawl Queue Worker:**
```bash
# Sử dụng batch file
start-crawl-queue-worker.bat

# Hoặc command trực tiếp
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
```

#### **Regular Queue Worker:**
```bash
# Video và TTS processing
start-queue-worker.bat
```

### 3. **Management Commands**

#### **Kiểm tra trạng thái:**
```bash
php artisan crawl:manage status
```

#### **Xem thống kê:**
```bash
php artisan crawl:manage stats
```

#### **Dọn dẹp queue:**
```bash
php artisan crawl:manage clear
```

#### **Quản lý limits:**
```bash
php artisan crawl:manage limit
```

#### **Test auto crawl:**
```bash
php artisan auto:crawl-stories --dry-run
```

## 📊 Monitoring và Debugging

### 1. **Queue Status Dashboard**

```bash
php artisan crawl:manage status
```

Hiển thị:
- Jobs trong crawl queue
- Stories đang crawl
- Rate limiting status
- Thời gian request cuối cùng

### 2. **Crawl Statistics**

```bash
php artisan crawl:manage stats
```

Hiển thị:
- Tổng số truyện
- Số truyện auto crawl enabled
- Phân bố theo crawl status
- Stories sẵn sàng crawl

### 3. **Log Monitoring**

```bash
# Theo dõi logs real-time
tail -f storage/logs/laravel.log | grep -i crawl

# Kiểm tra auto crawl logs
grep "Auto crawl" storage/logs/laravel.log
```

## ⚙️ Configuration Options

### 1. **Auto Crawl Limits**

File: `app/Console/Commands/AutoCrawlStories.php`
```php
->limit(10) // Tối đa 10 truyện mỗi lần
->orderBy('created_at', 'asc') // Ưu tiên truyện cũ

private function calculateOptimalDelay($storyCount)
{
    $totalMinutes = 50; // Phân bổ trong 50 phút
    $delayBetweenJobs = floor($totalMinutes / $storyCount);
    return max(2, min(10, $delayBetweenJobs)); // 2-10 phút
}
```

### 2. **Job Configuration**

File: `app/Jobs/CrawlStoryJob.php`
```php
public $timeout = 14400; // 4 hours
public $tries = 1;       // Không retry
$this->onQueue('crawl'); // Queue riêng
```

### 3. **Scheduler Configuration**

File: `app/Console/Kernel.php`
```php
$schedule->command('auto:crawl-stories')
    ->everyTwoHours()           // Mỗi 2 giờ
    ->withoutOverlapping()      // Không chồng lấp
    ->runInBackground();        // Chạy background
```

## 🔧 Troubleshooting

### 1. **Jobs bị stuck**
```bash
# Kiểm tra jobs
php artisan crawl:manage status

# Clear queue nếu cần
php artisan crawl:manage clear
```

### 2. **Rate limiting issues**
```bash
# Reset rate limiting cache
php artisan crawl:manage status --reset-limit
```

### 3. **Queue worker không chạy**
```bash
# Restart queue worker
php artisan queue:restart

# Start lại worker
start-crawl-queue-worker.bat
```

### 4. **Stories không được crawl**
```bash
# Kiểm tra điều kiện auto crawl
php artisan auto:crawl-stories --dry-run

# Kiểm tra stats
php artisan crawl:manage stats
```

## 📈 Performance Benefits

### **Trước khi có Rate Limiting:**
- ❌ Tất cả jobs chạy cùng lúc
- ❌ Overload server nguồn
- ❌ Có thể bị block IP
- ❌ Timeout và failed jobs

### **Sau khi có Rate Limiting:**
- ✅ Jobs được phân bổ đều
- ✅ Không overload server
- ✅ Tránh bị block IP  
- ✅ Crawl ổn định và đáng tin cậy

## 🎯 Best Practices

1. **Monitor queue status** thường xuyên
2. **Adjust limits** dựa trên server capacity
3. **Use dry-run** để test trước khi thực hiện
4. **Keep logs** để debug khi có vấn đề
5. **Restart workers** định kỳ để tránh memory leaks

## 📋 Production Deployment

### 1. **Supervisor Configuration**
```ini
[program:laravel-crawl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
directory=/path/to/project
autostart=true
autorestart=true
numprocs=1
user=www-data
```

### 2. **Monitoring Setup**
- Setup log rotation cho Laravel logs
- Monitor queue size và processing time
- Alert khi có quá nhiều failed jobs
- Track crawl success rate

### 3. **Scaling Considerations**
- Tăng số worker processes khi cần
- Sử dụng Redis thay vì database queue
- Implement horizontal scaling với multiple servers
- Load balancing cho crawl requests
