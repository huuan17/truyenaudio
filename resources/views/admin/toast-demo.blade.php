@extends('layouts.app')

@section('title', 'Toast Demo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell mr-2"></i>Toast Messages Demo
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>JavaScript Toast Functions</h5>
                            <p class="text-muted">Click các nút để test toast messages</p>
                            
                            <div class="btn-group-vertical w-100 mb-3">
                                <button type="button" class="btn btn-success mb-2" onclick="showToast.success('Thao tác thành công!', 'Thành công!')">
                                    <i class="fas fa-check mr-2"></i>Success Toast
                                </button>
                                
                                <button type="button" class="btn btn-danger mb-2" onclick="showToast.error('Có lỗi xảy ra!', 'Lỗi!')">
                                    <i class="fas fa-times mr-2"></i>Error Toast
                                </button>
                                
                                <button type="button" class="btn btn-warning mb-2" onclick="showToast.warning('Cảnh báo quan trọng!', 'Cảnh báo!')">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Warning Toast
                                </button>
                                
                                <button type="button" class="btn btn-info mb-2" onclick="showToast.info('Thông tin hữu ích!', 'Thông tin!')">
                                    <i class="fas fa-info-circle mr-2"></i>Info Toast
                                </button>
                                
                                <button type="button" class="btn btn-secondary mb-2" onclick="showToast.clear()">
                                    <i class="fas fa-broom mr-2"></i>Clear All Toasts
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Advanced Toast Options</h5>
                            <p class="text-muted">Toast với tùy chọn nâng cao</p>
                            
                            <div class="btn-group-vertical w-100 mb-3">
                                <button type="button" class="btn btn-primary mb-2" onclick="testLongMessage()">
                                    <i class="fas fa-align-left mr-2"></i>Long Message Toast
                                </button>
                                
                                <button type="button" class="btn btn-purple mb-2" onclick="testPersistentToast()">
                                    <i class="fas fa-clock mr-2"></i>Persistent Toast (10s)
                                </button>
                                
                                <button type="button" class="btn btn-dark mb-2" onclick="testMultipleToasts()">
                                    <i class="fas fa-layer-group mr-2"></i>Multiple Toasts
                                </button>
                                
                                <button type="button" class="btn btn-outline-success mb-2" onclick="testEmojiToast()">
                                    <i class="fas fa-smile mr-2"></i>Emoji Toast
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h5>Session Flash Messages</h5>
                            <p class="text-muted">Test toast từ session flash messages</p>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="{{ route('admin.toast-demo') }}?flash=success" class="btn btn-success btn-block">
                                        <i class="fas fa-check mr-2"></i>Flash Success
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.toast-demo') }}?flash=error" class="btn btn-danger btn-block">
                                        <i class="fas fa-times mr-2"></i>Flash Error
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.toast-demo') }}?flash=warning" class="btn btn-warning btn-block">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Flash Warning
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.toast-demo') }}?flash=info" class="btn btn-info btn-block">
                                        <i class="fas fa-info-circle mr-2"></i>Flash Info
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb mr-2"></i>Cách sử dụng Toast Messages:</h6>
                        <ul class="mb-0">
                            <li><strong>JavaScript:</strong> <code>showToast.success('message', 'title')</code></li>
                            <li><strong>Controller:</strong> <code>$this->toastSuccess('message', 'route')</code></li>
                            <li><strong>Blade:</strong> <code>@toastSuccess('message')</code></li>
                            <li><strong>Session:</strong> <code>session()->flash('success', 'message')</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testLongMessage() {
    showToast.info(
        'Đây là một thông báo rất dài để test xem toast message có hiển thị đúng không khi nội dung quá dài. Toast sẽ tự động wrap text và hiển thị đầy đủ nội dung.',
        'Thông báo dài'
    );
}

function testPersistentToast() {
    showToast.warning(
        'Toast này sẽ hiển thị trong 10 giây!',
        'Persistent Toast',
        { timeOut: 10000 }
    );
}

function testMultipleToasts() {
    showToast.success('Toast thứ nhất', 'Success 1');
    setTimeout(() => showToast.info('Toast thứ hai', 'Info 2'), 500);
    setTimeout(() => showToast.warning('Toast thứ ba', 'Warning 3'), 1000);
    setTimeout(() => showToast.error('Toast thứ tư', 'Error 4'), 1500);
}

function testEmojiToast() {
    showToast.success('🎉 Chúc mừng! Bạn đã test thành công emoji toast! 🚀', '✨ Thành công!');
}
</script>
@endsection
