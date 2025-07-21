# 📖 Hướng dẫn sử dụng Smart Auto Crawl System

## 🚀 **Bước 1: Khởi động Queue Worker (BẮT BUỘC)**

**⚠️ QUAN TRỌNG: Phải làm bước này trước khi sử dụng auto crawl!**

### **🔧 Cách khởi động:**

#### **Option A: Sử dụng Batch File (Dễ nhất)**
1. **Mở Command Prompt** (cmd)
2. **Navigate** đến thư mục project: `cd C:\xampp\htdocs\audio-lara`
3. **Chạy**: `start-queue-worker.bat`
4. **Chọn option**: `2` (Crawl queue only)
5. **Giữ cửa sổ mở** - Đây là queue worker, đừng đóng!

#### **Option B: Command trực tiếp**
```bash
cd C:\xampp\htdocs\audio-lara
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
```

### **✅ Xác nhận Queue Worker đang chạy:**
- Cửa sổ Command Prompt hiển thị: `Processing jobs from the [crawl] queue`
- Không có lỗi nào xuất hiện
- Cửa sổ vẫn mở và chờ jobs

---

## 🧠 **Smart Crawl Technology**

**🎯 Tính năng mới: Smart Crawl tự động tối ưu hóa quá trình crawl!**

### **🔍 Smart Crawl hoạt động như thế nào:**
1. **Quét chapters hiện có** trong storage và database
2. **Phân tích gaps** - tìm chapters còn thiếu
3. **Import chapters** từ files có sẵn vào database
4. **Chỉ crawl** những chapters thực sự còn thiếu
5. **Tiết kiệm** 70-90% thời gian và requests

### **💡 Lợi ích:**
- **⚡ Nhanh hơn**: Không crawl lại chapters đã có
- **💰 Tiết kiệm**: Ít requests đến server nguồn
- **🎯 Thông minh**: Tự động phát hiện và xử lý gaps
- **🔄 Linh hoạt**: Có thể resume từ bất kỳ điểm nào

---

## 🕷️ **Bước 2: Sử dụng Smart Auto Crawl**

### **📍 Truy cập Crawl Monitor:**
```
http://localhost:8000/admin/crawl-monitor
```

### **➕ Thêm Story vào Queue:**
1. **Click** nút "Thêm Truyện"
2. **Chọn story** từ dropdown
3. **Click** "Thêm vào Queue"
4. **Đợi** job hoàn thành trước khi add story tiếp theo

### **📊 Theo dõi tiến độ:**
- **Dashboard** tự động refresh mỗi 30 giây
- **Progress bars** hiển thị % hoàn thành
- **Logs** hiển thị chi tiết crawl process

---

## ⚠️ **Quy tắc quan trọng**

### **🔄 Workflow đúng:**
```
1. Start Queue Worker (giữ chạy liên tục)
2. Add một story vào queue
3. Đợi job hoàn thành (status = CRAWLED)
4. Add story tiếp theo
5. Lặp lại bước 3-4
```

### **❌ Tránh làm:**
- **Đóng cửa sổ Queue Worker** khi có jobs đang chạy
- **Add nhiều stories cùng lúc** (gây conflict)
- **Interrupt jobs** đang chạy
- **Restart server** khi có jobs trong queue

### **🚨 Nếu có vấn đề:**
1. **Stop Queue Worker**: Ctrl+C trong cửa sổ cmd
2. **Clear queue**: `php artisan queue:clear`
3. **Reset stories**: Vào admin panel reset status
4. **Restart**: Làm lại từ Bước 1

---

## 📈 **Monitoring & Troubleshooting**

### **🔍 Kiểm tra Queue Worker:**
```bash
# Kiểm tra có worker nào đang chạy
tasklist | findstr php

# Test queue worker
php artisan queue:work --once --stop-when-empty
```

### **📋 Kiểm tra Jobs:**
```bash
# Xem jobs trong queue
php artisan queue:monitor

# Xem failed jobs
php artisan queue:failed
```

### **📊 Monitor Logs:**
```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log | grep -i crawl

# Check specific story
php artisan crawl:monitor status --story=7
```

### **🔧 Recovery Commands:**
```bash
# Auto recovery stuck jobs
php artisan crawl:monitor auto

# Manual recovery
php artisan crawl:monitor recover

# Clear all jobs
php artisan queue:clear
```

---

## 🎯 **Tips & Best Practices**

### **✅ Để có trải nghiệm tốt nhất:**
1. **Luôn start Queue Worker trước** khi sử dụng
2. **Chỉ add một story mỗi lần** để tránh conflict
3. **Monitor dashboard** để theo dõi tiến độ
4. **Đợi job hoàn thành** trước khi add job mới
5. **Backup data** định kỳ

### **📱 Interface Tips:**
- **Auto-refresh**: Dashboard tự động cập nhật
- **Progress bars**: Hiển thị % crawl completion
- **Status badges**: Màu sắc cho biết trạng thái
- **Quick actions**: Buttons để recovery/stop jobs

### **⚡ Performance Tips:**
- **Crawl rate**: 2 giây/chapter (tự động throttling)
- **Memory usage**: Monitor qua Task Manager
- **Storage space**: Check disk space định kỳ
- **Network**: Stable internet connection

---

## 🆘 **Troubleshooting Common Issues**

### **❌ "Jobs không chạy"**
**Nguyên nhân**: Không có Queue Worker
**Giải pháp**: Start Queue Worker (Bước 1)

### **❌ "Job bị stuck"**
**Nguyên nhân**: Worker bị interrupt hoặc timeout
**Giải pháp**: 
```bash
php artisan crawl:monitor recover
```

### **❌ "Multiple jobs conflict"**
**Nguyên nhân**: Add nhiều stories cùng lúc
**Giải pháp**:
```bash
php artisan queue:clear
# Reset stories và add từng story một
```

### **❌ "Crawl failed"**
**Nguyên nhân**: Network issues hoặc site blocking
**Giải pháp**: Check logs và retry

---

## 📞 **Support**

### **📋 Khi cần hỗ trợ, cung cấp:**
1. **Screenshot** của dashboard
2. **Logs** từ `storage/logs/laravel.log`
3. **Queue status**: `php artisan queue:monitor`
4. **Story ID** và error message

### **🔍 Debug Commands:**
```bash
# Full system check
php debug_queue_conflict.php

# Monitor specific story
php monitor_crawl.php

# Check system health
php monitor_crawl_logs.php
```

---

## 🎉 **Kết luận**

**Auto Crawl System đã sẵn sàng sử dụng!**

**Nhớ:** 
1. **Start Queue Worker** trước khi sử dụng
2. **Add từng story một** để tránh conflict  
3. **Monitor dashboard** để theo dõi tiến độ
4. **Giữ Queue Worker chạy** liên tục

**Happy Crawling!** 🕷️✨
