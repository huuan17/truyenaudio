# 🔄 Auto-Next Chapter Feature Guide

## 📋 Tổng quan

Auto-Next Chapter là tính năng cho phép người dùng tự động chuyển sang chương tiếp theo khi audio của chương hiện tại kết thúc, tạo trải nghiệm nghe liên tục và mượt mà.

## ✨ Tính năng chính

### 🎛️ **User Controls**
- **Checkbox Toggle**: Bật/tắt auto-next dễ dàng
- **Settings Persistence**: Lưu preference trong localStorage
- **Smart Detection**: Tự động disable khi không có chương tiếp theo
- **Visual Feedback**: Status badge hiển thị trạng thái real-time

### ⏰ **Countdown System**
- **5-second Countdown**: Thời gian để user có thể hủy
- **Visual Progress**: Progress bar countdown animation
- **Cancel Option**: Button hủy bỏ auto-next
- **Instant Go**: Button chuyển ngay lập tức
- **Auto-scroll**: Tự động scroll đến countdown area

### 🔔 **Smart Notifications**
- **Toast Messages**: Feedback khi bật/tắt auto-next
- **Manual Option**: Notification với next button khi auto-next tắt
- **Completion Alert**: Thông báo khi hết chương cuối
- **Loading State**: Feedback khi đang chuyển chapter

## 🎯 User Experience Flow

### 1. **Auto-Next Enabled Flow:**
```
Audio Ends → Show Countdown (5s) → Auto Navigate
     ↓              ↓                    ↓
Status Update   Cancel Option      Next Chapter
```

### 2. **Auto-Next Disabled Flow:**
```
Audio Ends → Show Manual Notification → User Choice
     ↓              ↓                        ↓
Status Update   Next Button            Stay/Go
```

### 3. **Last Chapter Flow:**
```
Audio Ends → Show Completion Message → End
     ↓              ↓
Status Update   No Next Option
```

## 🏗️ Technical Implementation

### 1. **HTML Structure**

```html
<!-- Auto-Next Checkbox -->
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="autoNextChapter" 
           onchange="toggleAutoNext()" 
           @if($nextChapter) checked @else disabled @endif>
    <label class="form-check-label" for="autoNextChapter">
        <i class="fas fa-forward me-1"></i>
        <span class="auto-next-label">Tự động next</span>
    </label>
</div>

<!-- Status Display -->
<div class="col-3">
    <small class="text-muted d-block">Auto-next</small>
    <span id="autoNextStatus" class="badge badge-secondary">
        @if($nextChapter) Bật @else Không có @endif
    </span>
</div>

<!-- Countdown UI -->
<div class="mt-3 p-3 bg-warning rounded text-center" id="autoNextCountdown" style="display: none;">
    <div class="d-flex align-items-center justify-content-center">
        <i class="fas fa-clock me-2"></i>
        <span>Tự động chuyển sang chương tiếp theo sau: </span>
        <strong class="ms-1" id="countdownTimer">5</strong>
        <span class="ms-1">giây</span>
        <button class="btn btn-sm btn-outline-dark ms-3" onclick="cancelAutoNext()">
            <i class="fas fa-times me-1"></i>Hủy
        </button>
        <button class="btn btn-sm btn-primary ms-2" onclick="goToNextChapter()">
            <i class="fas fa-forward me-1"></i>Chuyển ngay
        </button>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar bg-primary" id="countdownProgress" style="width: 100%"></div>
    </div>
</div>
```

### 2. **JavaScript Core Functions**

```javascript
// Variables
let autoNextEnabled = true;
let countdownTimer = null;
let countdownSeconds = 5;
let nextChapterUrl = '/story/slug/chapter/2';

// Toggle Auto-Next
function toggleAutoNext() {
    autoNextEnabled = document.getElementById('autoNextChapter').checked;
    localStorage.setItem('audioPlayerAutoNext', autoNextEnabled);
    updateAutoNextStatus();
    
    if (autoNextEnabled) {
        showNotification('Đã bật tự động chuyển chương', 'success');
    } else {
        showNotification('Đã tắt tự động chuyển chương', 'info');
    }
}

// Update Status Badge
function updateAutoNextStatus() {
    const statusElement = document.getElementById('autoNextStatus');
    if (!nextChapterUrl) {
        statusElement.textContent = 'Không có';
        statusElement.className = 'badge badge-secondary';
    } else if (autoNextEnabled) {
        statusElement.textContent = 'Bật';
        statusElement.className = 'badge badge-success';
    } else {
        statusElement.textContent = 'Tắt';
        statusElement.className = 'badge badge-warning';
    }
}

// Handle Audio End
function handleAudioEnded() {
    if (!nextChapterUrl) {
        showNotification('Đã hoàn thành chương cuối cùng!', 'info');
        return;
    }

    if (autoNextEnabled) {
        startAutoNextCountdown();
    } else {
        showManualNextOption();
    }
}

// Countdown System
function startAutoNextCountdown() {
    countdownSeconds = 5;
    document.getElementById('autoNextCountdown').style.display = 'block';
    document.getElementById('countdownTimer').textContent = countdownSeconds;
    document.getElementById('countdownProgress').style.width = '100%';
    
    countdownTimer = setInterval(() => {
        countdownSeconds--;
        document.getElementById('countdownTimer').textContent = countdownSeconds;
        
        const progress = (countdownSeconds / 5) * 100;
        document.getElementById('countdownProgress').style.width = progress + '%';
        
        if (countdownSeconds <= 0) {
            clearInterval(countdownTimer);
            goToNextChapter();
        }
    }, 1000);
    
    // Auto-scroll to countdown
    document.getElementById('autoNextCountdown').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
    });
}

// Cancel Auto-Next
function cancelAutoNext() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
    document.getElementById('autoNextCountdown').style.display = 'none';
    showNotification('Đã hủy tự động chuyển chương', 'info');
}

// Navigate to Next Chapter
function goToNextChapter() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
    
    showNotification('Đang chuyển sang chương tiếp theo...', 'info');
    window.location.href = nextChapterUrl;
}
```

### 3. **CSS Styling**

```css
/* Auto-Next Checkbox */
.form-check-input {
    border-radius: 4px;
    border: 2px solid #ced4da;
    transition: all 0.3s ease;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.auto-next-label {
    font-weight: 500;
    user-select: none;
}

/* Countdown Styling */
#autoNextCountdown {
    border: 2px solid #ffc107;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    animation: slideInDown 0.5s ease-out;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

#countdownTimer {
    font-size: 1.2rem;
    color: #007bff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    animation: pulse 1s infinite;
}

/* Animations */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Status Badges */
.badge-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    animation: glow 2s infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
    to { box-shadow: 0 0 10px rgba(40, 167, 69, 0.8); }
}
```

## 🎨 Visual Design

### 1. **Checkbox States**
```
☐ Tự động next (Disabled - No next chapter)
☑️ Tự động next (Enabled - Ready)
☐ Tự động next (Disabled by user)
```

### 2. **Status Badges**
```
🟢 Bật     - Auto-next enabled (Green with glow)
🟡 Tắt     - Auto-next disabled (Yellow)
⚫ Không có - No next chapter (Gray)
```

### 3. **Countdown UI**
```
┌─────────────────────────────────────┐
│ 🕐 Tự động chuyển sang chương tiếp  │
│    theo sau: 3 giây                 │
│                                     │
│    [Hủy]  [Chuyển ngay]            │
│    ████████████░░░░░░░░░░░░░░░░░░░  │
└─────────────────────────────────────┘
```

## 📱 Responsive Behavior

### 1. **Desktop Experience**
- Full-width countdown bar
- Side-by-side buttons
- Smooth animations
- Hover effects

### 2. **Mobile Experience**
- Compact countdown layout
- Stacked buttons on small screens
- Touch-friendly targets
- Reduced animation complexity

### 3. **Accessibility**
- Keyboard navigation support
- Screen reader friendly
- High contrast colors
- Clear visual indicators

## 🔧 Configuration Options

### 1. **Timing Settings**
```javascript
const AUTO_NEXT_CONFIG = {
    countdownDuration: 5,        // seconds
    notificationDuration: 3000,  // milliseconds
    scrollBehavior: 'smooth',    // smooth | auto
    autoScrollToCountdown: true  // boolean
};
```

### 2. **User Preferences**
```javascript
// Saved in localStorage
{
    audioPlayerAutoNext: 'true',     // boolean string
    audioPlayerSpeed: '1',           // playback speed
    audioPlayerVolume: '100'         // volume level
}
```

## 📊 Analytics & Tracking

### 1. **User Behavior Metrics**
```javascript
// Track auto-next usage
function trackAutoNextUsage() {
    analytics.track('audio_auto_next_toggled', {
        enabled: autoNextEnabled,
        chapter: currentChapter,
        story: storySlug
    });
}

// Track countdown interactions
function trackCountdownAction(action) {
    analytics.track('auto_next_countdown', {
        action: action, // 'completed', 'cancelled', 'manual'
        timeRemaining: countdownSeconds,
        chapter: currentChapter
    });
}
```

### 2. **Engagement Metrics**
- Auto-next usage rate
- Countdown completion rate
- Manual override frequency
- Chapter progression patterns

## 🚀 Benefits Summary

### ✅ **For Users**
- **Seamless Experience**: Continuous listening without interruption
- **Full Control**: Easy to enable/disable as needed
- **Clear Feedback**: Always know what will happen next
- **Flexible Options**: Can cancel or proceed immediately

### ✅ **For Engagement**
- **Increased Session Time**: Users listen to more chapters
- **Better Retention**: Smooth progression keeps users engaged
- **Reduced Friction**: No manual navigation needed
- **Personalized**: Remembers user preferences

### ✅ **For Developers**
- **Clean Implementation**: Well-organized code structure
- **Extensible**: Easy to add new features
- **Performance**: Efficient countdown system
- **Maintainable**: Clear separation of concerns

**Auto-Next Chapter feature is ready for production! 🔄✨**
