@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Thư viện Audio', 'url' => route('admin.audio-library.index')],
        ['title' => $audioLibrary->title, 'url' => route('admin.audio-library.show', $audioLibrary)],
        ['title' => 'Chỉnh sửa']
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit mr-2"></i>Chỉnh sửa Audio</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.audio-library.update', $audioLibrary) }}" id="audioEditForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Current Audio Preview -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Audio hiện tại</h6>
                            <div class="current-audio-preview">
                                <audio controls class="w-100 mb-2" preload="metadata">
                                    <source src="{{ $audioLibrary->file_url }}" type="audio/{{ $audioLibrary->file_extension }}">
                                    Your browser does not support the audio element.
                                </audio>
                                <div class="audio-info">
                                    <small class="text-muted">
                                        <strong>{{ $audioLibrary->file_name }}</strong> - 
                                        {{ $audioLibrary->formatted_duration }} - 
                                        {{ $audioLibrary->formatted_file_size }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Thông tin cơ bản</h6>
                            
                            <div class="form-group">
                                <label for="title">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control" 
                                       value="{{ old('title', $audioLibrary->title) }}" required>
                                @error('title')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea name="description" id="description" class="form-control" rows="3" 
                                          placeholder="Mô tả ngắn gọn về audio này...">{{ old('description', $audioLibrary->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Classification -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Phân loại</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category">Danh mục <span class="text-danger">*</span></label>
                                        <select name="category" id="category" class="form-control" required>
                                            <option value="">Chọn danh mục</option>
                                            @foreach($categories as $key => $label)
                                                <option value="{{ $key }}" {{ old('category', $audioLibrary->category) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="source_type">Nguồn <span class="text-danger">*</span></label>
                                        <select name="source_type" id="source_type" class="form-control" required>
                                            <option value="">Chọn nguồn</option>
                                            @foreach($sourceTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('source_type', $audioLibrary->source_type) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('source_type')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="language">Ngôn ngữ <span class="text-danger">*</span></label>
                                        <select name="language" id="language" class="form-control" required>
                                            @foreach($languages as $key => $label)
                                                <option value="{{ $key }}" {{ old('language', $audioLibrary->language) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('language')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="voice_type">Loại giọng</label>
                                        <select name="voice_type" id="voice_type" class="form-control">
                                            <option value="">Không xác định</option>
                                            @foreach($voiceTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('voice_type', $audioLibrary->voice_type) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('voice_type')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="mood">Tâm trạng</label>
                                        <select name="mood" id="mood" class="form-control">
                                            <option value="">Không xác định</option>
                                            @foreach($moodTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('mood', $audioLibrary->mood) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('mood')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tags and Settings -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">Tags và Cài đặt</h6>
                            
                            <div class="form-group">
                                <label for="tags">Tags</label>
                                <input type="text" name="tags" id="tags" class="form-control" 
                                       value="{{ old('tags', $audioLibrary->tags ? implode(', ', $audioLibrary->tags) : '') }}" 
                                       placeholder="tag1, tag2, tag3...">
                                <small class="form-text text-muted">
                                    Nhập các tags cách nhau bằng dấu phẩy để dễ tìm kiếm
                                </small>
                                @error('tags')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" 
                                           {{ old('is_public', $audioLibrary->is_public) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">
                                        Công khai (cho phép người khác sử dụng)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Cập nhật
                            </button>
                            <a href="{{ route('admin.audio-library.show', $audioLibrary) }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Technical Information (Read-only) -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin kỹ thuật</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>File:</strong></td>
                            <td>{{ $audioLibrary->file_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Định dạng:</strong></td>
                            <td>{{ $audioLibrary->format }}</td>
                        </tr>
                        <tr>
                            <td><strong>Thời lượng:</strong></td>
                            <td>{{ $audioLibrary->formatted_duration }}</td>
                        </tr>
                        <tr>
                            <td><strong>Kích thước:</strong></td>
                            <td>{{ $audioLibrary->formatted_file_size }}</td>
                        </tr>
                        @if($audioLibrary->bitrate)
                        <tr>
                            <td><strong>Bitrate:</strong></td>
                            <td>{{ $audioLibrary->bitrate }} kbps</td>
                        </tr>
                        @endif
                        @if($audioLibrary->sample_rate)
                        <tr>
                            <td><strong>Sample Rate:</strong></td>
                            <td>{{ number_format($audioLibrary->sample_rate) }} Hz</td>
                        </tr>
                        @endif
                    </table>
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle mr-1"></i>
                        Thông tin kỹ thuật không thể chỉnh sửa. Để thay đổi file audio, vui lòng tạo mới.</small>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Thống kê sử dụng</h6>
                </div>
                <div class="card-body">
                    <div class="stat-item">
                        <strong>Số lần sử dụng:</strong> {{ $audioLibrary->usage_count }}
                    </div>
                    <div class="stat-item">
                        <strong>Lần cuối sử dụng:</strong><br>
                        <small class="text-muted">
                            {{ $audioLibrary->last_used_at ? $audioLibrary->last_used_at->format('d/m/Y H:i') : 'Chưa sử dụng' }}
                        </small>
                    </div>
                    <div class="stat-item">
                        <strong>Ngày tạo:</strong><br>
                        <small class="text-muted">{{ $audioLibrary->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt mr-2"></i>Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.audio-library.download', $audioLibrary) }}" class="btn btn-success btn-block mb-2">
                        <i class="fas fa-download mr-2"></i>Tải xuống
                    </a>
                    <button class="btn btn-primary btn-block mb-2" onclick="useInVideoGenerator()">
                        <i class="fas fa-video mr-2"></i>Dùng tạo video
                    </button>
                    <a href="{{ route('admin.audio-library.show', $audioLibrary) }}" class="btn btn-info btn-block">
                        <i class="fas fa-eye mr-2"></i>Xem chi tiết
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-section {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 1rem;
}

.section-title {
    color: #5a5c69;
    font-weight: 600;
    margin-bottom: 1rem;
}

.current-audio-preview {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
    border: 1px solid #e3e6f0;
}

.stat-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e3e6f0;
}

.stat-item:last-child {
    border-bottom: none;
}
</style>
@endpush

@push('scripts')
<script>
function useInVideoGenerator() {
    // Open video generator in new tab with audio pre-selected
    const url = '{{ route("admin.video-generator.index") }}' + 
                '?audio_source=library&library_audio_id={{ $audioLibrary->id }}';
    window.open(url, '_blank');
}

// Form validation
document.getElementById('audioEditForm').addEventListener('submit', function(e) {
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang cập nhật...';
    submitBtn.disabled = true;
});
</script>
@endpush
