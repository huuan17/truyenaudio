@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Tác giả', 'url' => route('admin.authors.index')],
        ['title' => $author->name, 'url' => route('admin.authors.show', $author)],
        ['title' => 'Chỉnh sửa']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>✏️ Chỉnh sửa: {{ $author->name }}</h2>
        <div>
            <a href="{{ route('admin.authors.show', $author) }}" class="btn btn-info">
                <i class="fas fa-eye mr-1"></i>Xem chi tiết
            </a>
            <a href="{{ route('author.show', $author->slug) }}" class="btn btn-success" target="_blank">
                <i class="fas fa-external-link-alt mr-1"></i>Xem frontend
            </a>
        </div>
    </div>

    @include('admin.authors.form')
</div>
@endsection
