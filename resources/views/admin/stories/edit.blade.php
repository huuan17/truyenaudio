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
            'title' => $story->title ?? 'Chỉnh sửa truyện',
            'url' => route('admin.stories.show', $story->slug ?? '#')
        ],
        [
            'title' => 'Chỉnh sửa'
        ]
    ]" />

    <div class="card card-warning">
      <div class="card-header">
        <h3 class="card-title">Chỉnh sửa truyện</h3>
      </div>
      <div class="card-body">
        @include('admin.stories.form')
      </div>
    </div>
</div>
@endsection
