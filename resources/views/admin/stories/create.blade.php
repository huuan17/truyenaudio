@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Truyện',
            'url' => route('admin.stories.index')
        ],
        [
            'title' => 'Thêm truyện mới'
        ]
    ]" />

    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Thêm truyện mới</h3>
      </div>
      <div class="card-body">
        @include('admin.stories.form')
      </div>
    </div>
</div>
@endsection
