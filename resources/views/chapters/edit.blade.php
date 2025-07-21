@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sửa chương {{ $chapter->chapter_number }} - {{ $chapter->story->title }}</h3>
            <div class="card-tools">
                <a href="{{ route('stories.chapters', $chapter->story) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <!-- Thông tin nguồn dữ liệu -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Thông tin nguồn dữ liệu:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Trạng thái:</strong> 
                        @if($chapter->is_crawled)
                            <span class="badge badge-success">Đã crawl</span>
                        @else
                            <span class="badge badge-secondary">Thêm thủ công</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Nguồn nội dung:</strong>
                        @if($chapter->hasContentInDatabase())
                            <span class="badge badge-primary">Database</span>
                        @endif
                        @if($chapter->file_path)
                            <span class="badge badge-info">File</span>
                        @endif
                    </div>
                </div>
                @if($chapter->file_path)
                    <div class="mt-2">
                        <strong>File:</strong> <code>{{ $chapter->file_path }}</code>
                        @if($chapter->formatted_file_size)
                            ({{ $chapter->formatted_file_size }})
                        @endif
                    </div>
                @endif
            </div>
            
            <form action="{{ route('chapters.update', $chapter) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="chapter_number">Số chương</label>
                            <input type="number" name="chapter_number" id="chapter_number" class="form-control" 
                                   value="{{ old('chapter_number', $chapter->chapter_number) }}" required min="1">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Tiêu đề</label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="{{ old('title', $chapter->title) }}" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="content">Nội dung</label>
                    @if(!$chapter->hasContentInDatabase() && $chapter->file_path)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Nội dung hiện đang được đọc từ file. Nếu bạn sửa và lưu, nội dung sẽ được lưu vào database.
                        </div>
                    @endif
                    <x-tinymce-editor
                        name="content"
                        id="content"
                        :value="old('content', $chapter->content)"
                        :height="500"
                        placeholder="Nhập nội dung chapter..."
                        toolbar="full"
                        required />
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_crawled" value="1" class="form-check-input" id="is_crawled"
                               {{ old('is_crawled', $chapter->is_crawled) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_crawled">
                            Đánh dấu là đã crawl
                        </label>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    <a href="{{ route('stories.chapters', $chapter->story) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
