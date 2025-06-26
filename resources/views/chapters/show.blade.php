@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Chương {{ $chapter->chapter_number }} - {{ $chapter->title }}</h3>
            <div class="card-tools">
                <a href="{{ route('stories.chapters', $chapter->story) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
                <a href="{{ route('chapters.edit', $chapter) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Truyện:</strong> {{ $chapter->story->title }}
                </div>
                <div class="col-md-6">
                    <strong>Chương:</strong> {{ $chapter->chapter_number }}
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Trạng thái:</strong>
                    @if($chapter->is_crawled)
                        <span class="badge badge-success">
                            <i class="fas fa-file-alt"></i> Đã crawl
                        </span>
                    @else
                        <span class="badge badge-secondary">
                            <i class="fas fa-keyboard"></i> Thủ công
                        </span>
                    @endif
                </div>
                <div class="col-md-6">
                    <strong>Ngày tạo:</strong> {{ $chapter->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            @if($chapter->file_path)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>File path:</strong> <code>{{ $chapter->file_path }}</code>
                    </div>
                </div>
            @endif

            <hr>

            <div class="content-section">
                <h5>Nội dung:</h5>
                <div class="content-text" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9;">
                    {!! nl2br(e($chapter->content)) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
