@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách thể loại</h1>

    <a href="{{ route('admin.genres.create') }}" class="btn btn-primary mb-3">+ Thêm thể loại</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($genres as $genre)
                <tr>
                    <td>{{ $genre->name }}</td>
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
