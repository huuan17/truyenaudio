@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Danh sách Chương</h3>
    <a href="{{ route('admin.chapters.create') }}" class="btn btn-primary float-right">Thêm chương</a>
  </div>
  <div class="card-body p-0">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Truyện</th>
          <th>Chương</th>
          <th>Tiêu đề</th>
        </tr>
      </thead>
      <tbody>
        @foreach($chapters as $chapter)
        <tr>
          <td>{{ $chapter->id }}</td>
          <td>{{ $chapter->story->title }}</td>
          <td>{{ $chapter->chapter_number }}</td>
          <td>{{ $chapter->title }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
