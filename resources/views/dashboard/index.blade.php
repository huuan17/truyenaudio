@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
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
                    <a href="{{ route('stories.index') }}" class="small-box-footer">
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
                        <p>Chapter có Audio</p>
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
                        <p>Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="{{ route('users.index') }}" class="small-box-footer">
                        Quản lý <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            @endif
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
                                <a href="{{ route('stories.create') }}" class="btn btn-primary btn-block">
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
                                <a href="{{ route('users.create') }}" class="btn btn-warning btn-block">
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
