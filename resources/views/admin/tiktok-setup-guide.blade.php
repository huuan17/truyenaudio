@extends('layouts.app')

@section('title', 'TikTok OAuth Setup Guide')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">TikTok OAuth Setup Guide</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Client Key Error</h5>
                        Nếu bạn gặp lỗi "client_key", hãy làm theo hướng dẫn dưới đây để tạo app TikTok mới.
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <h4>Bước 1: Tạo TikTok Developer App</h4>
                            <ol>
                                <li>Truy cập <a href="https://developers.tiktok.com/" target="_blank">TikTok Developer Portal</a></li>
                                <li>Đăng nhập với tài khoản TikTok</li>
                                <li>Nhấn "Create an app"</li>
                                <li>Điền thông tin app:
                                    <ul>
                                        <li><strong>App name:</strong> Test OAuth App</li>
                                        <li><strong>App description:</strong> Testing OAuth integration</li>
                                        <li><strong>Category:</strong> Tools & Utilities</li>
                                    </ul>
                                </li>
                                <li>Nhấn "Submit for review" (có thể mất vài phút)</li>
                            </ol>

                            <h4>Bước 2: Cấu hình OAuth Settings</h4>
                            <ol>
                                <li>Vào app vừa tạo</li>
                                <li>Chọn tab "Login Kit"</li>
                                <li>Thêm Redirect URI: <code>{{ route('admin.channels.tiktok.oauth.callback') }}</code></li>
                                <li>Chọn Scopes:
                                    <ul>
                                        <li>✅ user.info.basic</li>
                                        <li>✅ video.upload</li>
                                        <li>✅ video.publish</li>
                                    </ul>
                                </li>
                                <li>Lưu cấu hình</li>
                            </ol>

                            <h4>Bước 3: Lấy Client Credentials</h4>
                            <ol>
                                <li>Copy <strong>Client Key</strong> từ app dashboard</li>
                                <li>Copy <strong>Client Secret</strong> từ app dashboard</li>
                                <li>Cập nhật file .env:
                                    <pre class="bg-light p-2">
TIKTOK_CLIENT_ID=your_client_key_here
TIKTOK_CLIENT_SECRET=your_client_secret_here</pre>
                                </li>
                            </ol>

                            <h4>Bước 4: Test OAuth</h4>
                            <ol>
                                <li>Mở <a href="{{ route('admin.test.tiktok.oauth') }}" target="_blank">TikTok OAuth Test Page</a></li>
                                <li>Nhập Client Key và Secret mới</li>
                                <li>Nhấn "Test OAuth URL"</li>
                                <li>Nhấn "Start OAuth" để test</li>
                            </ol>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Quick Test</h5>
                                </div>
                                <div class="card-body">
                                    <form id="quickTestForm">
                                        <div class="form-group">
                                            <label for="test_client_key">Client Key</label>
                                            <input type="text" id="test_client_key" class="form-control form-control-sm" 
                                                   placeholder="Nhập client key mới">
                                        </div>
                                        <div class="form-group">
                                            <label for="test_client_secret">Client Secret</label>
                                            <input type="password" id="test_client_secret" class="form-control form-control-sm" 
                                                   placeholder="Nhập client secret">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm btn-block" onclick="quickTest()">
                                            <i class="fas fa-play"></i> Quick Test
                                        </button>
                                    </form>

                                    <div id="quickTestResult" class="mt-3" style="display: none;"></div>
                                </div>
                            </div>

                            <div class="card bg-info mt-3">
                                <div class="card-header">
                                    <h5>Current Settings</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless text-white">
                                        <tr>
                                            <td><strong>Sandbox:</strong></td>
                                            <td>{{ config('services.tiktok.sandbox') ? 'true' : 'false' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Redirect URI:</strong></td>
                                            <td><small>{{ config('services.tiktok.redirect_uri') }}</small></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="card bg-warning mt-3">
                                <div class="card-header">
                                    <h5>Required URLs for TikTok</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Terms of Service:</strong></td>
                                            <td><small><a href="{{ route('terms.service') }}" target="_blank" class="text-dark">{{ route('terms.service') }}</a></small></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Privacy Policy:</strong></td>
                                            <td><small><a href="{{ route('privacy.policy') }}" target="_blank" class="text-dark">{{ route('privacy.policy') }}</a></small></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Web/Desktop URL:</strong></td>
                                            <td><small><a href="{{ config('app.url') }}" target="_blank" class="text-dark">{{ config('app.url') }}</a></small></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Redirect URI:</strong></td>
                                            <td><small><code>{{ route('admin.channels.tiktok.oauth.callback') }}</code></small></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="card bg-success mt-3">
                                <div class="card-header">
                                    <h5>Useful Links</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled text-white">
                                        <li><a href="https://developers.tiktok.com/" target="_blank" class="text-white">
                                            <i class="fas fa-external-link-alt"></i> TikTok Developer Portal
                                        </a></li>
                                        <li><a href="{{ route('admin.test.tiktok.oauth') }}" target="_blank" class="text-white">
                                            <i class="fas fa-vial"></i> OAuth Test Page
                                        </a></li>
                                        <li><a href="{{ route('admin.channels.create') }}" target="_blank" class="text-white">
                                            <i class="fas fa-plus"></i> Create Channel
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickTest() {
    const clientKey = document.getElementById('test_client_key').value;
    const clientSecret = document.getElementById('test_client_secret').value;
    
    if (!clientKey || !clientSecret) {
        alert('Vui lòng nhập Client Key và Client Secret');
        return;
    }
    
    fetch('{{ route("admin.channels.tiktok.oauth.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            client_key: clientKey,
            client_secret: clientSecret
        })
    })
    .then(response => response.json())
    .then(data => {
        const result = document.getElementById('quickTestResult');
        
        if (data.success) {
            result.innerHTML = `
                <div class="alert alert-success alert-sm">
                    <h6>✅ Success!</h6>
                    <p><small>OAuth URL generated successfully</small></p>
                    <a href="${data.auth_url}" target="_blank" class="btn btn-success btn-sm">
                        <i class="fab fa-tiktok"></i> Test OAuth
                    </a>
                </div>
            `;
        } else {
            result.innerHTML = `
                <div class="alert alert-danger alert-sm">
                    <h6>❌ Error</h6>
                    <p><small>${data.error}</small></p>
                </div>
            `;
        }
        
        result.style.display = 'block';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra: ' + error.message);
    });
}
</script>
@endsection
