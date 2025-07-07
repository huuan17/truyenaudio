@extends('layouts.app')

@section('content')
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Thêm truyện mới</h3>
  </div>
  <div class="card-body">
    @include('stories.form')
  </div>
</div>
@endsection
