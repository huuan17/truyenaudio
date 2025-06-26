@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">🕷️ Quản lý Crawl Truyện</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Tiêu đề</th>
                <th>Tên thư mục</th>
                <th>Chương</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stories as $story)
                <tr>
                    <td>{{ $story->id }}</td>
                    <td>{{ $story->title }}</td>
                    <td><code>{{ $story->folder_name }}</code></td>
                    <td>
                        <form action="{{ route('crawl.run', $story) }}" method="POST" class="d-flex gap-1">
                            @csrf
                            <input type="number" name="start_chapter" value="{{ $story->start_chapter }}" class="form-control form-control-sm" style="width:90px" placeholder="Start">
                            <input type="number" name="end_chapter" value="{{ $story->end_chapter }}" class="form-control form-control-sm" style="width:90px" placeholder="End">
                    </td>
                    <td>
                        @php
                            $statusLabels = ['<span class="badge bg-secondary">Chưa crawl</span>', '<span class="badge bg-success">Đã crawl</span>', '<span class="badge bg-warning text-dark">Cần crawl</span>'];
                        @endphp
                        {!! $statusLabels[$story->crawl_status ?? 0] !!}
                    </td>
                    <td>
                            <button class="btn btn-sm btn-primary">▶️ Crawl</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
