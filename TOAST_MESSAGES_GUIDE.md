# 🍞 Toast Messages System Guide

## 📋 Tổng quan

Hệ thống Toast Messages được tích hợp vào ứng dụng để hiển thị thông báo người dùng một cách đẹp mắt và chuyên nghiệp. Sử dụng Toastr.js với custom styling và tích hợp Laravel session flash messages.

## ✨ Tính năng

### 🎨 **Visual Features:**
- **4 loại toast**: Success, Error, Warning, Info
- **Custom styling**: Phù hợp với AdminLTE theme
- **Progress bar**: Hiển thị thời gian còn lại
- **Close button**: Đóng thủ công
- **Responsive**: Hoạt động tốt trên mobile
- **Emoji support**: Hỗ trợ emoji trong messages

### 🔧 **Technical Features:**
- **Auto-display**: Tự động hiển thị từ session flash
- **AJAX support**: Xử lý lỗi AJAX tự động
- **Validation errors**: Hiển thị lỗi validation
- **Prevent duplicates**: Không hiển thị toast trùng lặp
- **Multiple toasts**: Hỗ trợ nhiều toast cùng lúc

## 🚀 Cách sử dụng

### 1. **JavaScript (Frontend)**

#### **Cơ bản:**
```javascript
// Success toast
showToast.success('Thao tác thành công!', 'Thành công!');

// Error toast
showToast.error('Có lỗi xảy ra!', 'Lỗi!');

// Warning toast
showToast.warning('Cảnh báo quan trọng!', 'Cảnh báo!');

// Info toast
showToast.info('Thông tin hữu ích!', 'Thông tin!');
```

#### **Nâng cao:**
```javascript
// Toast với options tùy chỉnh
showToast.success('Message', 'Title', {
    timeOut: 10000,        // 10 giây
    extendedTimeOut: 2000, // 2 giây khi hover
    progressBar: true,
    closeButton: true
});

// Clear tất cả toasts
showToast.clear();

// Remove tất cả toasts ngay lập tức
showToast.remove();
```

### 2. **Controller (Backend)**

#### **Sử dụng Trait:**
```php
use App\Traits\HasToastMessages;

class YourController extends Controller
{
    use HasToastMessages;
    
    public function store()
    {
        // Success với redirect
        return $this->toastSuccess('Tạo thành công!', 'admin.stories.index');
        
        // Error với redirect về trang trước
        return $this->toastError('Có lỗi xảy ra!');
        
        // Warning
        return $this->toastWarning('Cảnh báo!', 'admin.dashboard');
        
        // Info
        return $this->toastInfo('Thông tin!');
    }
    
    // AJAX responses
    public function ajaxAction()
    {
        return $this->toastJsonSuccess('AJAX thành công!');
        return $this->toastJsonError('AJAX lỗi!', [], 400);
    }
    
    // Auto-detect AJAX/Regular request
    public function smartAction()
    {
        return $this->successResponse('Thành công!', 'admin.stories.index');
    }
}
```

#### **Traditional Laravel:**
```php
// Session flash
return redirect()->route('admin.stories.index')
    ->with('success', 'Thành công!');

return back()->with('error', 'Lỗi!');
```

### 3. **Blade Templates**

#### **Blade Directives:**
```blade
{{-- Session flash --}}
@toastSuccess('Thành công!')
@toastError('Lỗi!')
@toastWarning('Cảnh báo!')
@toastInfo('Thông tin!')

{{-- JavaScript toast --}}
@jsToast('success', 'Message', 'Title')
```

#### **Component:**
```blade
<x-toast-trigger type="success" message="Thành công!" title="Great!" />
<x-toast-trigger type="error" message="Lỗi!" timeout="10000" />
```

## 🎨 Styling & Configuration

### **Toastr Options:**
```javascript
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "6000",
    "extendedTimeOut": "2000",
    "showMethod": "slideDown",
    "hideMethod": "slideUp",
    "preventDuplicates": true
};
```

### **Custom CSS Classes:**
- `.toast-success` - Success toast styling
- `.toast-error` - Error toast styling  
- `.toast-warning` - Warning toast styling
- `.toast-info` - Info toast styling

## 🔧 AJAX Integration

### **Automatic Error Handling:**
```javascript
// Tự động xử lý lỗi AJAX
$(document).ajaxError(function(event, xhr, settings, thrownError) {
    // 422: Validation errors
    // 500: Server errors
    // 404: Not found
    // 403: Forbidden
    // 401: Unauthorized
});
```

### **Form Submission:**
```javascript
// Submit form với toast feedback
submitFormWithToast('#myForm', 'Thành công!');
```

### **Manual AJAX:**
```javascript
$.ajax({
    url: '/api/endpoint',
    method: 'POST',
    data: formData,
    success: function(response) {
        if (response.toast) {
            showToast[response.type](response.message);
        }
    }
});
```

## 📱 Responsive Design

### **Desktop:**
- Position: Top-right
- Width: Auto (max 300px)
- Animation: Slide down/up

### **Mobile:**
- Position: Top-right (adjusted)
- Width: Responsive
- Touch-friendly close button

## 🎯 Best Practices

### **Message Content:**
```php
// ✅ Good - Clear and actionable
$this->toastSuccess('✅ Truyện đã được tạo thành công!');
$this->toastError('❌ Không thể xóa truyện đang có chapter!');

// ❌ Bad - Vague and unclear
$this->toastSuccess('OK');
$this->toastError('Error');
```

### **Emoji Usage:**
```php
// Success
'🎉 Thành công!'
'✅ Hoàn thành!'
'🚀 Đã khởi chạy!'

// Error  
'❌ Lỗi!'
'⚠️ Cảnh báo!'
'🚫 Không được phép!'

// Info
'ℹ️ Thông tin!'
'💡 Gợi ý!'
'📋 Hướng dẫn!'
```

### **Timing:**
```javascript
// Quick actions: 3-5 seconds
{ timeOut: 4000 }

// Important messages: 6-8 seconds  
{ timeOut: 7000 }

// Critical errors: 10+ seconds
{ timeOut: 10000 }
```

## 🧪 Testing

### **Demo Page:**
```
http://localhost:8000/admin/toast-demo
```

### **Test Cases:**
1. **JavaScript toasts** - All 4 types
2. **Session flash** - Laravel flash messages
3. **Long messages** - Text wrapping
4. **Multiple toasts** - Stacking behavior
5. **AJAX errors** - Automatic error handling
6. **Validation errors** - Form validation display

## 🔍 Debugging

### **Console Logs:**
```javascript
// Check if toastr is loaded
console.log(typeof toastr);

// Check toast options
console.log(toastr.options);

// Manual toast test
toastr.success('Test message');
```

### **Common Issues:**

**1. Toast không hiển thị:**
- Kiểm tra jQuery đã load
- Kiểm tra toastr.js đã load
- Kiểm tra CSS đã load

**2. Styling bị lỗi:**
- Kiểm tra CSS conflicts
- Kiểm tra z-index
- Kiểm tra responsive breakpoints

**3. AJAX errors không hiển thị:**
- Kiểm tra ajaxError handler
- Kiểm tra response format
- Kiểm tra network tab

## 📦 Files Structure

```
public/assets/
├── css/toastr.min.css          # Toastr CSS
└── js/toastr.min.js            # Toastr JS

resources/views/
├── layouts/app.blade.php       # Main layout với toast config
├── components/
│   └── toast-trigger.blade.php # Toast component
└── admin/
    └── toast-demo.blade.php    # Demo page

app/
├── Traits/
│   └── HasToastMessages.php    # Controller trait
└── Providers/
    └── AppServiceProvider.php  # Blade directives
```

## 🎉 Examples

### **Story Management:**
```php
// Create story
return $this->toastSuccess('🎉 Truyện đã được tạo thành công!', 'admin.stories.index');

// Update story  
return $this->toastSuccess('✅ Truyện đã được cập nhật!', 'admin.stories.index');

// Delete story
return $this->toastSuccess('🗑️ Truyện đã được xóa!', 'admin.stories.index');

// Crawl started
return $this->toastSuccess('🕷️ Crawl đã được khởi chạy!', 'admin.stories.index');

// TTS completed
return $this->toastSuccess('🎤 TTS đã hoàn thành!', 'admin.stories.chapters', ['story' => $story]);
```

### **Error Handling:**
```php
try {
    // Some operation
    return $this->toastSuccess('Thành công!');
} catch (\Exception $e) {
    return $this->toastError('❌ Có lỗi xảy ra: ' . $e->getMessage());
}
```

Hệ thống Toast Messages giúp cải thiện trải nghiệm người dùng với thông báo rõ ràng, đẹp mắt và dễ sử dụng! 🎊
