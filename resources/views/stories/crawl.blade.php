@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Crawl truyện - {{ $story->title }}</h3>
            <div class="card-tools">
                <a href="{{ route('stories.show', $story) }}" class="btn btn-sm btn-secondary">
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
            
            <form action="{{ route('stories.crawl', $story) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="source_url">URL nguồn</label>
                            <input type="text" class="form-control" value="{{ $story->source_url }}" readonly>
                            <small class="form-text text-muted">URL nguồn của truyện để crawl</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="crawl_path">Thư mục lưu trữ</label>
                            <input type="text" class="form-control" value="{{ $story->crawl_path }}" readonly>
                            <small class="form-text text-muted">Đường dẫn thư mục lưu nội dung crawl</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_chapter">Chương bắt đầu</label>
                            <input type="number" name="start_chapter" id="start_chapter" class="form-control" value="{{ $story->start_chapter }}" min="1" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_chapter">Chương kết thúc</label>
                            <input type="number" name="end_chapter" id="end_chapter" class="form-control" value="{{ $story->end_chapter }}" min="1" required>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Hệ thống sẽ crawl từ chương <strong>{{ $story->start_chapter }}</strong> đến chương <strong>{{ $story->end_chapter }}</strong>. 
                    Quá trình này có thể mất thời gian tùy thuộc vào số lượng chương.
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-spider"></i> Bắt đầu crawl
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection