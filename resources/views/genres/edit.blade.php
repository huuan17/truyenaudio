@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Sửa thể loại</h1>
    <form action="{{ route('genres.update', $genre) }}" method="POST">
        @csrf
        @method('PUT')
        @include('genres.form')
    </form>
</div>
@endsection
