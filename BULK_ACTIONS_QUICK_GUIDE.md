# 📋 Chapter Bulk Actions - Quick Guide

## 🚀 Cách sử dụng

### 1. **Truy cập trang Chapters**
```
URL: http://localhost:8000/admin/stories/{story-slug}/chapters
Ví dụ: http://localhost:8000/admin/stories/tien-nghich/chapters
```

### 2. **Chọn Chapters**
- **☑️ Select All**: Click checkbox ở header để chọn/bỏ chọn tất cả
- **☑️ Individual**: Click checkbox từng chapter để chọn riêng lẻ
- **📊 Counter**: Thanh action bar hiện số chapters đã chọn

### 3. **Bulk Operations**

#### **🎵 TTS Hàng loạt**
```
1. Chọn chapters cần TTS
2. Click [TTS hàng loạt]
3. Xác nhận trong dialog
4. Hệ thống sẽ xử lý background
```

#### **🗑️ Xóa Hàng loạt**
```
1. Chọn chapters cần xóa
2. Click [Xóa đã chọn]
3. Xác nhận 2 lần (an toàn)
4. Chapters và files sẽ bị xóa vĩnh viễn
```

#### **❌ Bỏ chọn**
```
Click [Bỏ chọn] để clear tất cả selection
```

## 🎯 Visual Indicators

### **Selection States**
```
☐ Unselected     - Checkbox trống
☑️ Selected       - Checkbox xanh
◼️ Indeterminate  - Select All ở trạng thái một phần
```

### **Row Highlighting**
```
⚪ Normal Row     - Nền trắng
🔵 Selected Row   - Nền xanh + border trái
🔘 Hover Row      - Nền xám nhạt
```

### **Action Bar**
```
Hidden:  [Không có selection]
Active:  [📋 Đã chọn 5 chương] [🎵 TTS] [🗑️ Xóa] [❌ Bỏ chọn]
Loading: [⏳ Đang xử lý 5 chương...]
```

## ⚠️ Lưu ý An toàn

### **TTS Bulk**
- ✅ An toàn - có thể undo
- ⚠️ Chỉ TTS chapters có nội dung
- 📊 Hiển thị kết quả chi tiết

### **Delete Bulk**
- 🚨 **NGUY HIỂM** - không thể undo
- 🔒 Xác nhận 2 lần
- 🗑️ Xóa cả files audio/video
- ⚡ Xóa vĩnh viễn khỏi database

## 🔧 Troubleshooting

### **Checkbox không hoạt động?**
```
1. Refresh trang (F5)
2. Check console errors (F12)
3. Đảm bảo JavaScript enabled
```

### **Action bar không hiện?**
```
1. Chọn ít nhất 1 chapter
2. Check JavaScript console
3. Refresh nếu cần
```

### **Bulk operation thất bại?**
```
1. Check network connection
2. Xem error message
3. Thử lại với ít chapters hơn
4. Check server logs
```

## 📱 Mobile Usage

### **Touch Targets**
- Checkboxes lớn hơn cho touch
- Buttons stack vertically
- Action bar responsive

### **Gestures**
- Tap để select
- Long press cho context (future)
- Swipe friendly interface

## 🎯 Best Practices

### **Selection Strategy**
```
✅ DO: Chọn theo batch nhỏ (10-20 chapters)
✅ DO: Kiểm tra content trước TTS
✅ DO: Backup trước khi bulk delete

❌ DON'T: Select quá nhiều cùng lúc
❌ DON'T: Delete mà không confirm
❌ DON'T: TTS chapters không có content
```

### **Performance Tips**
```
- Bulk TTS: 10-20 chapters/lần
- Bulk Delete: 5-10 chapters/lần  
- Refresh sau mỗi operation
- Monitor server resources
```

## 🔗 Quick Links

### **Navigation**
```
Stories List:     /admin/stories
Story Detail:     /admin/stories/{slug}
Chapters:         /admin/stories/{slug}/chapters
Chapter Create:   /admin/chapters/create/{story_id}
```

### **Related Features**
```
Individual TTS:   Click [TTS] button per chapter
Chapter Edit:     Click [Edit] button per chapter
Story Management: Back to story detail
Video Generation: Individual chapter video
```

## 📊 Status Indicators

### **TTS Status**
```
⏳ Chờ TTS       - Chưa có audio
🔄 Đang xử lý     - TTS đang chạy
✅ Hoàn thành     - Có file audio
❌ Lỗi           - TTS thất bại
```

### **Content Status**
```
📝 Có nội dung    - Ready for TTS
❌ Không có       - Cần thêm content
🔄 Đang crawl     - Content đang load
```

## 🎉 Success Indicators

### **TTS Success**
```
✅ "Đã bắt đầu TTS cho X chương"
📊 Progress tracking per chapter
🔔 Notification khi hoàn thành
```

### **Delete Success**
```
✅ "Đã xóa thành công X chương"
📊 Detailed count report
🔄 Page auto-refresh
```

---

**💡 Tip: Sử dụng keyboard shortcuts trong tương lai:**
- `Ctrl+A`: Select All
- `Delete`: Bulk Delete
- `Escape`: Clear Selection

**🎯 Happy bulk processing! 📋✨**
