@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Chuyển đổi Text-to-Speech - {{ $story->title }}</h3>
            <div class="card-tools">
                <a href="{{ route('stories.show', $story) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <form action="{{ route('stories.tts', $story) }}" method="POST">
                @csrf
                <div class="row">
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
                            <label for="conversion_type">Loại chuyển đổi</label>
                            <select name="conversion_type" id="conversion_type" class="form-control" required>
                                <option value="all">Tất cả chapters</option>
                                <option value="single">Một chapter cụ thể</option>
                                <option value="multiple">Nhiều chapters</option>
                                <option value="pending_only">Chỉ chapters chưa có audio</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" id="chapter_input_group" style="display: none;">
                            <label for="chapters">Số chương</label>
                            <input type="text" name="chapters" id="chapters" class="form-control" placeholder="VD: 1 hoặc 1,3,5-10,15">
                            <small class="form-text text-muted">
                                <strong>Một chapter:</strong> 5<br>
                                <strong>Nhiều chapters:</strong> 1,3,5,7<br>
                                <strong>Khoảng chapters:</strong> 5-10 (từ chương 5 đến 10)<br>
                                <strong>Kết hợp:</strong> 1,3,5-10,15
                            </small>
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
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Hệ thống sẽ chuyển đổi nội dung text thành audio.
                    Quá trình này có thể mất thời gian tùy thuộc vào số lượng chương và độ dài nội dung.
                    <br><strong>Lưu ý:</strong> Các chapter đã có audio sẽ được tự động bỏ qua.
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

@push('scripts')
<script>
$(document).ready(function() {
    // Xử lý thay đổi loại chuyển đổi
    $('#conversion_type').change(function() {
        var type = $(this).val();
        var $inputGroup = $('#chapter_input_group');
        var $chaptersInput = $('#chapters');

        if (type === 'single' || type === 'multiple') {
            $inputGroup.show();
            $chaptersInput.prop('required', true);

            if (type === 'single') {
                $chaptersInput.attr('placeholder', 'VD: 5');
                $inputGroup.find('small').hide();
            } else {
                $chaptersInput.attr('placeholder', 'VD: 1,3,5-10,15');
                $inputGroup.find('small').show();
            }
        } else {
            $inputGroup.hide();
            $chaptersInput.prop('required', false);
        }
    });

    // Trigger change event để set trạng thái ban đầu
    $('#conversion_type').trigger('change');
});
</script>
@endpush

@endsection