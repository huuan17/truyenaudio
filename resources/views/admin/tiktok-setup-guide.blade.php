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
                                    <h5>📋 Required URLs for TikTok Developer Portal</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info alert-sm">
                                        <small><i class="fas fa-info-circle"></i> Copy các URL này vào TikTok Developer Portal</small>
                                    </div>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Terms of Service URL:</strong></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" value="{{ route('terms.service') }}" readonly id="terms-url">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('terms-url')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Privacy Policy URL:</strong></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" value="{{ route('privacy.policy') }}" readonly id="privacy-url">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('privacy-url')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Web/Desktop URL:</strong></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" value="{{ config('app.url') }}" readonly id="web-url">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('web-url')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Redirect URI:</strong></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" value="{{ route('admin.channels.tiktok.oauth.callback') }}" readonly id="redirect-url">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('redirect-url')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="alert alert-warning alert-sm mt-2">
                                        <small><i class="fas fa-exclamation-triangle"></i> <strong>Lưu ý:</strong> Khi deploy lên production, cập nhật APP_URL trong .env và đăng ký lại URLs trong TikTok Developer Portal</small>
                                    </div>
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

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999); // For mobile devices

    try {
        document.execCommand('copy');

        // Show success feedback
        const button = element.nextElementSibling;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check text-success"></i>';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-secondary');

        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);

    } catch (err) {
        alert('Không thể copy. Vui lòng copy thủ công.');
    }
}
</script>
@endsection
