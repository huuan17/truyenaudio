@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-md-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3>{{ $storyCount }}</h3>
        <p>Tổng số truyện</p>
      </div>
      <div class="icon">
        <i class="fas fa-book"></i>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3>{{ $chapterCount }}</h3>
        <p>Tổng số chương</p>
      </div>
      <div class="icon">
        <i class="fas fa-list"></i>
      </div>
    </div>
  </div>
</div>
@endsection
