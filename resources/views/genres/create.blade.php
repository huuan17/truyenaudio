@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Thêm thể loại</h1>
    <form action="{{ route('genres.store') }}" method="POST">
        @csrf
        @include('genres.form')
    </form>
</div>
@endsection
