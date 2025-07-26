@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Cấu hình hệ thống', 'url' => '#'],
        ['title' => 'Cấu hình Upload']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-cog mr-2"></i>Cấu hình Upload File</h2>
        <button class="btn btn-outline-primary" onclick="refreshConfig()">
            <i class="fas fa-sync-alt mr-2"></i>Refresh
        </button>
    </div>

    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>Trạng thái hỗ trợ Upload lớn
                    </h5>
                </div>
                <div class="card-body">
                    @if($check['supported'])
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Tuyệt vời!</strong> Cấu hình hiện tại hỗ trợ upload file lớn.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Cần cấu hình!</strong> Cấu hình hiện tại chưa hỗ trợ upload file lớn.
                            <button class="btn btn-sm btn-primary ml-2" onclick="showInstructions()">
                                Xem hướng dẫn
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Current vs Recommended -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">So sánh cấu hình</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cài đặt</th>
                                    <th>Hiện tại</th>
                                    <th>Khuyến nghị</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recommended as $setting => $recValue)
                                    @php
                                        $currentValue = $current[$setting] ?? 'Chưa đặt';
                                        $isOk = $currentValue === $recValue;
                                    @endphp
                                    <tr class="{{ $isOk ? 'table-success' : 'table-warning' }}">
                                        <td><code>{{ $setting }}</code></td>
                                        <td>
                                            <span class="badge badge-{{ $isOk ? 'success' : 'warning' }}">
                                                {{ $currentValue }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $recValue }}</span>
                                        </td>
                                        <td>
                                            @if($isOk)
                                                <i class="fas fa-check text-success"></i> OK
                                            @else
                                                <i class="fas fa-times text-warning"></i> Cần cập nhật
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Test Upload Limits -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Test giới hạn Upload</h5>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="test-size">Kích thước test:</label>
                                <select id="test-size" class="form-control">
                                    <option value="50M">50 MB</option>
                                    <option value="100M" selected>100 MB</option>
                                    <option value="200M">200 MB</option>
                                    <option value="500M">500 MB</option>
                                    <option value="1G">1 GB</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-primary btn-block" onclick="testUploadLimits()">
                                    <i class="fas fa-vial mr-2"></i>Test Upload
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="test-results" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin hệ thống</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td>{{ PHP_VERSION }}</td>
                        </tr>
                        <tr>
                            <td><strong>PHP ini:</strong></td>
                            <td>
                                <small>{{ $current['php_ini_path'] }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Web Server:</strong></td>
                            <td>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td><strong>OS:</strong></td>
                            <td>{{ PHP_OS }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if(!$check['supported'])
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tools mr-2"></i>Hướng dẫn nhanh</h6>
                </div>
                <div class="card-body">
                    <p class="small">Để hỗ trợ upload file lớn, bạn cần:</p>
                    <ol class="small">
                        <li>Mở file php.ini</li>
                        <li>Cập nhật các cài đặt</li>
                        <li>Restart web server</li>
                    </ol>
                    <button class="btn btn-sm btn-primary btn-block" onclick="showInstructions()">
                        Xem hướng dẫn chi tiết
                    </button>
                </div>
            </div>
            @endif

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-question-circle mr-2"></i>Giải thích</h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>upload_max_filesize:</strong> Kích thước tối đa mỗi file</p>
                        <p><strong>post_max_size:</strong> Tổng kích thước POST request</p>
                        <p><strong>max_execution_time:</strong> Thời gian xử lý tối đa</p>
                        <p><strong>memory_limit:</strong> Giới hạn bộ nhớ</p>
                        <p><strong>max_file_uploads:</strong> Số file tối đa cùng lúc</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Instructions Modal -->
<div class="modal fade" id="instructionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hướng dẫn cấu hình PHP</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="instructions-content">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="copyConfigSnippet()">
                    <i class="fas fa-copy mr-1"></i>Copy cấu hình
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let configData = null;

function refreshConfig() {
    location.reload();
}

function testUploadLimits() {
    const testSize = document.getElementById('test-size').value;
    const resultsDiv = document.getElementById('test-results');
    
    resultsDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Testing...</div>';
    
    fetch(`/admin/system/test-upload-limits?size=${testSize}`)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="alert alert-' + (data.can_upload_single && data.can_upload_multiple ? 'success' : 'warning') + '">';
            html += '<h6>Kết quả test ' + data.test_size + ':</h6>';
            html += '<ul class="mb-0">';
            html += '<li>Upload single file: ' + (data.can_upload_single ? '✅ Được hỗ trợ' : '❌ Không hỗ trợ') + '</li>';
            html += '<li>Upload multiple files: ' + (data.can_upload_multiple ? '✅ Được hỗ trợ' : '❌ Không hỗ trợ') + '</li>';
            html += '</ul>';
            
            if (data.recommendations.length > 0) {
                html += '<hr><strong>Khuyến nghị:</strong><ul class="mb-0">';
                data.recommendations.forEach(rec => {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
            }
            
            html += '</div>';
            resultsDiv.innerHTML = html;
        })
        .catch(error => {
            resultsDiv.innerHTML = '<div class="alert alert-danger">Lỗi khi test: ' + error.message + '</div>';
        });
}

function showInstructions() {
    const modal = $('#instructionsModal');
    const content = $('#instructions-content');
    
    modal.modal('show');
    content.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');
    
    fetch('/admin/system/generate-instructions')
        .then(response => response.json())
        .then(data => {
            configData = data;
            
            let html = '<div class="row">';
            
            // XAMPP Instructions
            html += '<div class="col-md-6">';
            html += '<h6><i class="fas fa-server mr-2"></i>Hướng dẫn cho XAMPP:</h6>';
            html += '<ol>';
            data.xampp_steps.forEach(step => {
                html += '<li>' + step + '</li>';
            });
            html += '</ol>';
            html += '</div>';
            
            // General Instructions
            html += '<div class="col-md-6">';
            html += '<h6><i class="fas fa-cog mr-2"></i>Hướng dẫn chung:</h6>';
            html += '<ol>';
            data.steps.forEach(step => {
                html += '<li>' + step + '</li>';
            });
            html += '</ol>';
            html += '</div>';
            
            html += '</div>';
            
            // PHP ini path
            html += '<div class="alert alert-info mt-3">';
            html += '<strong>Đường dẫn php.ini:</strong><br>';
            html += '<code>' + data.php_ini_path + '</code>';
            html += '</div>';
            
            // Config snippet
            html += '<h6>Cấu hình cần thêm/sửa:</h6>';
            html += '<pre id="config-snippet" class="bg-light p-3" style="font-size: 12px;">' + data.config_snippet + '</pre>';
            
            content.html(html);
        })
        .catch(error => {
            content.html('<div class="alert alert-danger">Lỗi khi tải hướng dẫn: ' + error.message + '</div>');
        });
}

function copyConfigSnippet() {
    if (configData && configData.config_snippet) {
        navigator.clipboard.writeText(configData.config_snippet).then(() => {
            alert('Đã copy cấu hình vào clipboard!');
        });
    }
}

// Auto test on page load
document.addEventListener('DOMContentLoaded', function() {
    @if(!$check['supported'])
        setTimeout(testUploadLimits, 1000);
    @endif
});
</script>
@endpush
