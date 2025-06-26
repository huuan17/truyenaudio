@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Chuyển đổi Text-to-Speech</h3>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <form action="{{ route('tts.convert') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="story_id">Chọn truyện</label>
                            <select name="story_id" id="story_id" class="form-control select2" required>
                                <option value="">-- Chọn truyện --</option>
                                @foreach($stories as $story)
                                    <option value="{{ $story->id }}">
                                        {{ $story->title }} ({{ $story->folder_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="voice">Giọng đọc</label>
                            <select name="voice" id="voice" class="form-control" required>
                                @foreach($voices as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bitrate">Bitrate</label>
                            <select name="bitrate" id="bitrate" class="form-control" required>
                                <option value="64">64 kbps</option>
                                <option value="128" selected>128 kbps</option>
                                <option value="192">192 kbps</option>
                                <option value="256">256 kbps</option>
                                <option value="320">320 kbps</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="speed">Tốc độ đọc</label>
                            <select name="speed" id="speed" class="form-control" required>
                                <option value="0.7">Chậm (0.7x)</option>
                                <option value="0.8">Hơi chậm (0.8x)</option>
                                <option value="0.9">Bình thường chậm (0.9x)</option>
                                <option value="1.0" selected>Bình thường (1.0x)</option>
                                <option value="1.1">Hơi nhanh (1.1x)</option>
                                <option value="1.2">Nhanh (1.2x)</option>
                                <option value="1.3">Rất nhanh (1.3x)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-microphone"></i> Bắt đầu chuyển đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Chọn truyện",
            allowClear: true
        });
    });
</script>
@endpush