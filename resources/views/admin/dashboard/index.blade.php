@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Bảng điều khiển</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <span class="badge badge-info">Xin chào, {{ auth()->user()->name }}!</span>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total_stories'] }}</h3>
                        <p>Truyện</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <a href="{{ route('admin.stories.index') }}" class="small-box-footer">
                        Xem thêm <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['total_chapters'] }}</h3>
                        <p>Chương</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Xem thêm <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['chapters_with_audio'] }}</h3>
                        <p>Chương có Audio</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-volume-up"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Xem thêm <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['total_users'] }}</h3>
                        <p>Người dùng</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                        Quản lý <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            @endif
        </div>

        <!-- Queue Worker Status -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-info-circle"></i> Trạng thái Queue Worker</h5>
                    <p class="mb-2">
                        <strong>Tự động Crawl & Tạo Video:</strong>
                        Để sử dụng tính năng tự động, hãy chạy Queue Worker:
                    </p>
                    <p class="mb-0">
                        <code>start-queue-worker.bat</code> → Chọn queue phù hợp → Giữ cửa sổ mở
                        <a href="{{ route('admin.crawl-monitor.index') }}" class="btn btn-sm btn-primary ml-2">
                            <i class="fas fa-spider"></i> Giám sát Crawl
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Thao tác nhanh</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="{{ route('admin.stories.create') }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i> Thêm truyện mới
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('admin.genres.create') }}" class="btn btn-success btn-block">
                                    <i class="fas fa-tags"></i> Thêm thể loại
                                </a>
                            </div>
                            @if(auth()->user()->isAdmin())
                            <div class="col-md-3">
                                <a href="{{ route('admin.users.create') }}" class="btn btn-warning btn-block">
                                    <i class="fas fa-user-plus"></i> Thêm user
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
