@extends('layouts.app')

@section('content')
<div class="card card-warning">
  <div class="card-header">
    <h3 class="card-title">Chỉnh sửa truyện</h3>
  </div>
  <div class="card-body">
    @include('stories.form')
  </div>
</div>
@endsection
