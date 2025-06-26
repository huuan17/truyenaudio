@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $story->title }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('stories.edit', $story) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Sửa truyện
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            @if($story->cover_image)
                                <img src="{{ asset($story->cover_image) }}" class="img-fluid mb-3" alt="{{ $story->title }}">
                            @else
                                <div class="bg-light text-center p-5 mb-3">
                                    <i class="fas fa-book fa-3x text-secondary"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px">Tác giả</th>
                                    <td>{{ $story->author ?? 'Không rõ' }}</td>
                                </tr>
                                <tr>
                                    <th>Thể loại</th>
                                    <td>
                                        @foreach($story->genres as $genre)
                                            <span class="badge badge-primary">{{ $genre->name }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nguồn</th>
                                    <td><a href="{{ $story->source_url }}" target="_blank">{{ $story->source_url }}</a></td>
                                </tr>
                                <tr>
                                    <th>Chương</th>
                                    <td>{{ $story->start_chapter }} - {{ $story->end_chapter }} (Tổng: {{ $chapterCount }}/{{ $story->end_chapter - $story->start_chapter + 1 }} chương)</td>
                                </tr>
                                <tr>
                                    <th>Trạng thái crawl</th>
                                    <td>
                                        @if($story->crawl_status == 0)
                                            <span class="badge badge-warning">Chưa crawl</span>
                                        @elseif($story->crawl_status == 1)
                                            <span class="badge badge-success">Đã crawl</span>
                                        @elseif($story->crawl_status == 2)
                                            <span class="badge badge-danger">Cần crawl lại</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Thư mục lưu trữ</th>
                                    <td><code>{{ $story->crawl_path }}</code></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quản lý truyện</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-spider"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Crawl dữ liệu</span>
                                    <span class="info-box-number">{{ $chapterCount }}/{{ $story->end_chapter - $story->start_chapter + 1 }} chương</span>
                                    <a href="{{ route('stories.crawl.form', $story) }}" class="btn btn-sm btn-info mt-2">
                                        <i class="fas fa-spider"></i> Crawl truyện
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-search"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Quét Chapter</span>
                                    <span class="info-box-number">Từ storage vào DB</span>
                                    <a href="{{ route('stories.scan.form', $story) }}" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-search"></i> Quét chapter
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-book"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Quản lý chương</span>
                                    <span class="info-box-number">{{ $chapterCount }} chương</span>
                                    <a href="{{ route('stories.chapters', $story) }}" class="btn btn-sm btn-success mt-2">
                                        <i class="fas fa-list"></i> Xem danh sách chương
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-microphone"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Text-to-Speech</span>
                                    <span class="info-box-number">Chuyển đổi sang audio</span>
                                    <a href="{{ route('stories.tts.form', $story) }}" class="btn btn-sm btn-warning mt-2">
                                        <i class="fas fa-microphone"></i> Chuyển đổi audio
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="info-box">
                                <span class="info-box-icon bg-purple"><i class="fas fa-video"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tạo Video</span>
                                    <span class="info-box-number">Kết hợp ảnh + audio thành video MP4</span>
                                    <a href="{{ route('stories.video', $story) }}" class="btn btn-sm btn-purple mt-2">
                                        <i class="fas fa-video"></i> Tạo video
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Chương mới nhất</h3>
                    <div class="card-tools">
                        <a href="{{ route('stories.chapters', $story) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-list"></i> Xem tất cả
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Chương</th>
                                <th>Tiêu đề</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestChapters as $chapter)
                                <tr>
                                    <td>{{ $chapter->chapter_number }}</td>
                                    <td>{{ $chapter->title }}</td>
                                    <td>
                                        @if($chapter->is_crawled)
                                            <span class="badge badge-success">
                                                <i class="fas fa-file-alt"></i> Đã crawl
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-keyboard"></i> Thủ công
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('chapters.edit', $chapter) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Chưa có chương nào</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection