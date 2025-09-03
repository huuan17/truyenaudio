@extends('layouts.app')

@section('title', 'Test TikTok OAuth')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Test TikTok OAuth</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Hướng dẫn test TikTok OAuth</h5>
                        <ol>
                            <li>Nhập Client Key và Client Secret từ TikTok Developer Portal</li>
                            <li>Nhấn "Test OAuth URL" để kiểm tra URL được tạo</li>
                            <li>Nhấn "Start OAuth" để test thực tế</li>
                        </ol>
                        <p class="mb-0">
                            <strong>Gặp lỗi "client_key"?</strong>
                            <a href="{{ route('tiktok.setup.guide') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-book"></i> Xem hướng dẫn setup
                            </a>
                        </p>
                    </div>

                    <form id="testForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_key">Client Key</label>
                                    <input type="text" id="client_key" class="form-control" 
                                           placeholder="Nhập TikTok Client Key" value="aw2ya06arqm4bjdd">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_secret">Client Secret</label>
                                    <input type="password" id="client_secret" class="form-control" 
                                           placeholder="Nhập TikTok Client Secret">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="button" class="btn btn-info mr-2" onclick="testOAuthUrl()">
                                <i class="fas fa-link"></i> Test OAuth URL
                            </button>
                            <button type="button" class="btn btn-primary" onclick="startOAuth()">
                                <i class="fab fa-tiktok"></i> Start OAuth
                            </button>
                        </div>
                    </form>

                    <div id="results" class="mt-4" style="display: none;">
                        <h5>Kết quả:</h5>
                        <div id="resultContent"></div>
                    </div>

                    <div class="mt-4">
                        <h5>Thông tin cấu hình hiện tại:</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Sandbox Mode:</strong></td>
                                <td>{{ config('services.tiktok.sandbox') ? 'true' : 'false' }}</td>
                            </tr>
                            <tr>
                                <td><strong>API Version:</strong></td>
                                <td>{{ config('services.tiktok.api_version') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Redirect URI:</strong></td>
                                <td>{{ config('services.tiktok.redirect_uri') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Callback Route:</strong></td>
                                <td>{{ route('admin.channels.tiktok.oauth.callback') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testOAuthUrl() {
    const clientKey = document.getElementById('client_key').value;
    const clientSecret = document.getElementById('client_secret').value;
    
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
        const results = document.getElementById('results');
        const content = document.getElementById('resultContent');
        
        if (data.success) {
            // Parse URL to show PKCE parameters
            const url = new URL(data.auth_url);
            const params = new URLSearchParams(url.search);

            content.innerHTML = `
                <div class="alert alert-success">
                    <h6>✅ OAuth URL được tạo thành công với PKCE!</h6>
                    <p><strong>URL:</strong></p>
                    <div class="bg-light p-2 rounded">
                        <code>${data.auth_url}</code>
                    </div>

                    <div class="mt-3">
                        <h6>PKCE Parameters:</h6>
                        <table class="table table-sm">
                            <tr><td><strong>code_challenge:</strong></td><td><code>${params.get('code_challenge') || 'Not found'}</code></td></tr>
                            <tr><td><strong>code_challenge_method:</strong></td><td><code>${params.get('code_challenge_method') || 'Not found'}</code></td></tr>
                            <tr><td><strong>state:</strong></td><td><code>${params.get('state') || 'Not found'}</code></td></tr>
                        </table>
                    </div>

                    <p class="mt-2">
                        <a href="${data.auth_url}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Mở URL trong tab mới
                        </a>
                    </p>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h6>❌ Lỗi tạo OAuth URL</h6>
                    <p><strong>Error:</strong> ${data.error}</p>
                </div>
            `;
        }
        
        results.style.display = 'block';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra: ' + error.message);
    });
}

function startOAuth() {
    const clientKey = document.getElementById('client_key').value;
    const clientSecret = document.getElementById('client_secret').value;
    
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
        if (data.success) {
            window.location.href = data.auth_url;
        } else {
            alert('Lỗi: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra: ' + error.message);
    });
}
</script>
@endsection
