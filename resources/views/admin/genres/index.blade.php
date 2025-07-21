@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Thể loại',
            'badge' => ($genres->count() ?? 0) . ' thể loại'
        ]
    ]" />

    <h1>Danh sách thể loại</h1>

    <a href="{{ route('admin.genres.create') }}" class="btn btn-primary mb-3">+ Thêm thể loại</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Tiêu đề</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($genres as $genre)
                <tr>
                    <td>
                        <strong>{{ $genre->name }}</strong>
                        @if($genre->description)
                            <br><small class="text-muted">{{ Str::limit($genre->description, 50) }}</small>
                        @endif
                    </td>
                    <td>{{ $genre->title ?: $genre->name }}</td>
                    <td>
                        @if($genre->is_public)
                            <span class="badge bg-success">Công khai</span>
                        @else
                            <span class="badge bg-secondary">Ẩn</span>
                        @endif
                    </td>
                    <td>{{ $genre->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('admin.genres.edit', $genre) }}" class="btn btn-sm btn-warning">Sửa</a>
                        <form action="{{ route('admin.genres.destroy', $genre) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc muốn xoá?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Xoá</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $genres->links() }}
</div>
@endsection
