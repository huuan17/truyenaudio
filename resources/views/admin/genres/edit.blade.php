@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Sửa thể loại</h1>
    <form action="{{ route('admin.genres.update', $genre) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.genres.form')
    </form>
</div>
@endsection
