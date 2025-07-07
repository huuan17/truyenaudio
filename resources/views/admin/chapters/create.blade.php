@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Thêm chương mới</h3>
            <div class="card-tools">
                @if(isset($story))
                    <a href="{{ route('admin.stories.chapters', $story) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                @else
                    <a href="{{ route('admin.chapters.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.chapters.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="story_id">Truyện</label>
                    @if(isset($story))
                        <input type="hidden" name="story_id" value="{{ $story->id }}">
                        <input type="text" class="form-control" value="{{ $story->title }}" readonly>
                    @else
                        <select name="story_id" id="story_id" class="form-control select2" required>
                            <option value="">-- Chọn truyện --</option>
                            @foreach($stories as $story)
                                <option value="{{ $story->id }}">{{ $story->title }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                
                <div class="form-group">
                    <label for="chapter_number">Số chương</label>
                    <input type="number" name="chapter_number" id="chapter_number" class="form-control" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="title">Tiêu đề</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Nội dung</label>
                    <textarea name="content" id="content" class="form-control" rows="15" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Lưu chương</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
</augment_code_snippet>