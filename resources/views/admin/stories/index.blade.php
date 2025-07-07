@extends('layouts.app')

@section('content')
<div class="container">
    <h2>📚 Danh sách truyện</h2>
    <a href="{{ route('admin.stories.create') }}" class="btn btn-primary mb-3">+ Thêm truyện</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Slug</th>
                <th>Chương</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stories as $story)
            <tr>
                <td>{{ $story->title }}</td>
                <td>{{ $story->slug }}</td>
                <td>{{ $story->start_chapter }} → {{ $story->end_chapter }}</td>
                <td>
                    @if($story->crawl_status == 0) <span class="text-secondary">Chưa crawl</span>
                    @elseif($story->crawl_status == 1) <span class="text-success">Đã crawl</span>
                    @else <span class="text-warning">Cần crawl lại</span> @endif
                </td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.stories.edit', $story) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.stories.destroy', $story) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa truyện này?')"
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $stories->links() }}
</div>
@endsection
