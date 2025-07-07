@extends('layouts.app')

@section('title', 'Quản Lý Logo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-image mr-2"></i>Quản Lý Logo
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Quản lý logo để sử dụng trong video Stories và TikTok. Hỗ trợ định dạng PNG, JPG, GIF, SVG.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form upload logo -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-upload mr-2"></i>Upload Logo Mới
                    </h4>
                </div>
                <form action="{{ route('admin.logos.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        
                        <!-- File logo -->
                        <div class="form-group">
                            <label for="logo_file">
                                <i class="fas fa-file-image mr-1"></i>Chọn File Logo *
                            </label>
                            <input type="file" name="logo_file" id="logo_file" 
                                   class="form-control-file" accept="image/png,image/jpg,image/jpeg,image/gif,image/svg+xml" required>
                            <small class="form-text text-muted">
                                Định dạng: PNG, JPG, GIF, SVG. Tối đa 5MB. Nền trong suốt (PNG) được khuyến nghị.
                            </small>
                            @error('logo_file')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tên logo -->
                        <div class="form-group">
                            <label for="logo_name">
                                <i class="fas fa-tag mr-1"></i>Tên Logo (Tùy chọn)
                            </label>
                            <input type="text" name="logo_name" id="logo_name" class="form-control" 
                                   placeholder="Ví dụ: logo-company" value="{{ old('logo_name') }}">
                            <small class="form-text text-muted">
                                Để trống để sử dụng tên file gốc
                            </small>
                            @error('logo_name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Preview -->
                        <div class="form-group">
                            <label>Preview:</label>
                            <div id="logo_preview" class="border rounded p-3 text-center" style="min-height: 100px; background: #f8f9fa;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                                <p class="text-muted mt-2">Chọn file để xem preview</p>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload mr-2"></i>Upload Logo
                        </button>
                        <button type="reset" class="btn btn-secondary ml-2">
                            <i class="fas fa-undo mr-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Hướng dẫn -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>Hướng Dẫn
                    </h4>
                </div>
                <div class="card-body">
                    <h6>Khuyến nghị:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success mr-2"></i>Định dạng PNG với nền trong suốt</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Kích thước: 200x200px trở lên</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Tỷ lệ vuông (1:1) hoặc chữ nhật</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Độ phân giải cao cho chất lượng tốt</li>
                    </ul>
                    
                    <h6 class="mt-3">Vị trí logo:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-arrow-up mr-2 text-primary"></i>Góc trên: trái/phải</li>
                        <li><i class="fas fa-arrow-down mr-2 text-primary"></i>Góc dưới: trái/phải</li>
                        <li><i class="fas fa-dot-circle mr-2 text-primary"></i>Giữa màn hình</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Danh sách logo -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-images mr-2"></i>Logo Đã Upload ({{ count($logos) }})
                    </h4>
                </div>
                <div class="card-body">
                    @if(count($logos) > 0)
                        <div class="row">
                            @foreach($logos as $logo)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-body text-center p-3">
                                            <!-- Logo preview -->
                                            <div class="logo-preview mb-3" style="height: 120px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px;">
                                                <img src="{{ $logo['url'] }}" alt="{{ $logo['name'] }}" 
                                                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            </div>
                                            
                                            <!-- Logo info -->
                                            <h6 class="card-title mb-1" title="{{ $logo['name'] }}">
                                                {{ Str::limit(pathinfo($logo['name'], PATHINFO_FILENAME), 15) }}
                                            </h6>
                                            <small class="text-muted d-block">{{ $logo['size_formatted'] }}</small>
                                            <small class="text-muted d-block">{{ $logo['created_formatted'] }}</small>
                                            
                                            <!-- Actions -->
                                            <div class="mt-3">
                                                <button class="btn btn-sm btn-info" onclick="previewLogo('{{ $logo['name'] }}')" title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="{{ route('admin.logos.download', $logo['name']) }}" 
                                                   class="btn btn-sm btn-success" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteLogo('{{ $logo['name'] }}')" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-images fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có logo nào</h5>
                            <p class="text-muted">Upload logo đầu tiên để bắt đầu sử dụng</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="logoPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Logo</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="modal_logo_preview" style="min-height: 300px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px;">
                    <!-- Logo sẽ được load ở đây -->
                </div>
                <div class="mt-3">
                    <h6 id="modal_logo_name"></h6>
                    <p class="text-muted" id="modal_logo_info"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Preview file upload
document.getElementById('logo_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('logo_preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 80px; object-fit: contain;">`;
        };
        reader.readAsDataURL(file);
        
        // Auto-fill tên nếu chưa có
        const nameInput = document.getElementById('logo_name');
        if (!nameInput.value) {
            const fileName = file.name.split('.')[0];
            nameInput.value = fileName;
        }
    } else {
        preview.innerHTML = `
            <i class="fas fa-image fa-3x text-muted"></i>
            <p class="text-muted mt-2">Chọn file để xem preview</p>
        `;
    }
});

// Preview logo trong modal
function previewLogo(filename) {
    const logoUrl = '{{ route("admin.logo.serve", ":filename") }}'.replace(':filename', filename);
    
    document.getElementById('modal_logo_preview').innerHTML = 
        `<img src="${logoUrl}" style="max-width: 100%; max-height: 250px; object-fit: contain;">`;
    document.getElementById('modal_logo_name').textContent = filename;
    document.getElementById('modal_logo_info').textContent = 'Click vào các nút bên dưới để download hoặc sử dụng logo này';
    
    $('#logoPreviewModal').modal('show');
}

// Xóa logo
function deleteLogo(filename) {
    if (confirm('Bạn có chắc muốn xóa logo này?\n\nLưu ý: Logo đang được sử dụng trong video sẽ bị ảnh hưởng.')) {
        $.ajax({
            url: '{{ route("admin.logos.delete") }}',
            method: 'DELETE',
            data: {
                filename: filename,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Lỗi: ' + response.message);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra khi xóa logo');
            }
        });
    }
}

// Reset form
document.querySelector('button[type="reset"]').addEventListener('click', function() {
    document.getElementById('logo_preview').innerHTML = `
        <i class="fas fa-image fa-3x text-muted"></i>
        <p class="text-muted mt-2">Chọn file để xem preview</p>
    `;
});
</script>
@endpush
@endsection
