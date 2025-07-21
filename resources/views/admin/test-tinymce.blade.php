@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Test TinyMCE Editor
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="#">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Basic Toolbar</h5>
                                <x-tinymce-editor 
                                    name="basic_content" 
                                    id="basic_editor" 
                                    value="<p>Đây là nội dung mẫu với <strong>định dạng đậm</strong> và <em>nghiêng</em>.</p>"
                                    :height="300"
                                    placeholder="Nhập nội dung với toolbar cơ bản..."
                                    toolbar="basic" />
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Default Toolbar</h5>
                                <x-tinymce-editor 
                                    name="default_content" 
                                    id="default_editor" 
                                    value="<h2>Tiêu đề mẫu</h2><p>Đây là đoạn văn mẫu với <a href='#'>liên kết</a> và danh sách:</p><ul><li>Mục 1</li><li>Mục 2</li></ul>"
                                    :height="300"
                                    placeholder="Nhập nội dung với toolbar mặc định..."
                                    toolbar="default" />
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Full Toolbar</h5>
                                <x-tinymce-editor 
                                    name="full_content" 
                                    id="full_editor" 
                                    value="<h1>Tiêu đề chính</h1><p>Đây là nội dung mẫu với đầy đủ tính năng của TinyMCE. Bạn có thể:</p><ul><li>Định dạng văn bản với <strong>đậm</strong>, <em>nghiêng</em>, <u>gạch chân</u></li><li>Thêm <a href='https://example.com'>liên kết</a></li><li>Chèn hình ảnh</li><li>Tạo bảng</li><li>Và nhiều tính năng khác</li></ul><blockquote><p>Đây là một trích dẫn mẫu</p></blockquote>"
                                    :height="400"
                                    placeholder="Nhập nội dung với toolbar đầy đủ..."
                                    toolbar="full"
                                    required />
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>
                                    Lưu nội dung
                                </button>
                                <button type="button" class="btn btn-secondary ml-2" onclick="showContent()">
                                    <i class="fas fa-eye mr-1"></i>
                                    Xem nội dung
                                </button>
                                <button type="button" class="btn btn-info ml-2" onclick="debugTinyMCE()">
                                    <i class="fas fa-bug mr-1"></i>
                                    Debug TinyMCE
                                </button>
                                <button type="button" class="btn btn-warning ml-2" onclick="initFallbackEditor()">
                                    <i class="fas fa-tools mr-1"></i>
                                    Fallback Editor
                                </button>
                                <button type="button" class="btn btn-success ml-2" onclick="reinitTinyMCE()">
                                    <i class="fas fa-redo mr-1"></i>
                                    Reinit TinyMCE
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal hiển thị nội dung -->
<div class="modal fade" id="contentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nội dung từ TinyMCE</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="content-display"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showContent() {
    if (typeof tinymce === 'undefined') {
        alert('TinyMCE chưa được tải!');
        return;
    }

    const editors = tinymce.editors;
    let allContent = '<h3>Tất cả nội dung từ TinyMCE:</h3>';

    if (editors.length === 0) {
        allContent += '<div class="alert alert-warning">Không có editor nào được khởi tạo!</div>';
    } else {
        editors.forEach(function(editor, index) {
            allContent += '<div class="border p-3 mb-3">';
            allContent += '<h5>Editor ' + (index + 1) + ' (' + editor.id + '):</h5>';
            allContent += '<div class="bg-light p-2"><code>' + editor.getContent() + '</code></div>';
            allContent += '</div>';
        });
    }

    document.getElementById('content-display').innerHTML = allContent;
    $('#contentModal').modal('show');
}

function debugTinyMCE() {
    let debugInfo = '<h3>TinyMCE Debug Information:</h3>';

    // Check if TinyMCE is loaded
    if (typeof tinymce === 'undefined') {
        debugInfo += '<div class="alert alert-danger">❌ TinyMCE không được tải!</div>';
        debugInfo += '<p>Kiểm tra:</p><ul>';
        debugInfo += '<li>File tinymce.min.js có tồn tại không?</li>';
        debugInfo += '<li>Đường dẫn script có đúng không?</li>';
        debugInfo += '<li>Console có lỗi JavaScript không?</li>';
        debugInfo += '</ul>';
    } else {
        debugInfo += '<div class="alert alert-success">✅ TinyMCE đã được tải</div>';
        debugInfo += '<p><strong>Version:</strong> ' + tinymce.majorVersion + '.' + tinymce.minorVersion + '</p>';
        debugInfo += '<p><strong>Số editors:</strong> ' + tinymce.editors.length + '</p>';

        if (tinymce.editors.length > 0) {
            debugInfo += '<h4>Danh sách Editors:</h4><ul>';
            tinymce.editors.forEach(function(editor, index) {
                debugInfo += '<li><strong>' + editor.id + '</strong> - Trạng thái: ' + (editor.initialized ? 'Đã khởi tạo' : 'Chưa khởi tạo') + '</li>';
            });
            debugInfo += '</ul>';
        }
    }

    // Check script loading
    const scripts = document.querySelectorAll('script[src*="tinymce"]');
    debugInfo += '<h4>Scripts TinyMCE:</h4>';
    if (scripts.length === 0) {
        debugInfo += '<div class="alert alert-warning">⚠️ Không tìm thấy script TinyMCE nào!</div>';
    } else {
        debugInfo += '<ul>';
        scripts.forEach(function(script) {
            debugInfo += '<li>' + script.src + '</li>';
        });
        debugInfo += '</ul>';
    }

    // Check textareas
    const textareas = document.querySelectorAll('textarea.tinymce-editor');
    debugInfo += '<h4>Textareas TinyMCE:</h4>';
    debugInfo += '<p>Tìm thấy ' + textareas.length + ' textarea(s)</p>';

    document.getElementById('content-display').innerHTML = debugInfo;
    $('#contentModal').modal('show');
}

function initFallbackEditor() {
    // Fallback: Initialize TinyMCE manually
    if (typeof tinymce === 'undefined') {
        alert('TinyMCE chưa được tải! Không thể khởi tạo fallback editor.');
        return;
    }

    // Remove existing editors
    tinymce.remove();

    // Initialize with basic config
    tinymce.init({
        selector: 'textarea.tinymce-editor',
        height: 300,
        menubar: false,
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist',
        plugins: 'lists',
        branding: false,
        setup: function(editor) {
            editor.on('init', function() {
                console.log('Fallback editor initialized: ' + editor.id);
            });
        }
    });

    alert('Đã khởi tạo fallback editor!');
}

function reinitTinyMCE() {
    if (typeof tinymce === 'undefined') {
        alert('TinyMCE chưa được tải!');
        return;
    }

    // Remove all editors
    tinymce.remove();

    // Wait a bit then reinitialize
    setTimeout(function() {
        location.reload();
    }, 500);
}
</script>
@endpush
