@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Tác giả', 'url' => route('admin.authors.index')],
        ['title' => 'Thêm tác giả mới']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>➕ Thêm tác giả mới</h2>
    </div>

    @include('admin.authors.form')
</div>
@endsection
