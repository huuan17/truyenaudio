@extends('layouts.app')

@section('title', 'TTS Management')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Quản lý Truyện',
            'url' => route('admin.stories.index')
        ],
        [
            'title' => $story->title,
            'url' => route('admin.stories.show', $story)
        ],
        [
            'title' => 'TTS Management',
            'badge' => 'Text-to-Speech'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-microphone mr-2"></i>TTS Management - {{ $story->title }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Quay lại
                        </a>
                        <a href="{{ route('admin.stories.chapters', $story) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-list mr-1"></i>Chapter Management
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
            
            <form action="{{ route('admin.stories.tts', $story) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="voice">Giọng đọc</label>
                            <select name="voice" id="voice" class="form-control" required>
                                @foreach($voices as $code => $name)
                                    <option value="{{ $code }}" {{ $story->default_tts_voice == $code ? 'selected' : '' }}>
                                        {{ $name }}
                                        @if($story->default_tts_voice == $code)
                                            <span class="text-success">(Mặc định)</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Giọng mặc định: <strong>{{ $voices[$story->default_tts_voice] ?? 'Không xác định' }}</strong>
                            </small>
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
                                <option value="64" {{ $story->default_tts_bitrate == 64 ? 'selected' : '' }}>64 kbps</option>
                                <option value="128" {{ $story->default_tts_bitrate == 128 ? 'selected' : '' }}>128 kbps</option>
                                <option value="192" {{ $story->default_tts_bitrate == 192 ? 'selected' : '' }}>192 kbps</option>
                                <option value="256" {{ $story->default_tts_bitrate == 256 ? 'selected' : '' }}>256 kbps</option>
                                <option value="320" {{ $story->default_tts_bitrate == 320 ? 'selected' : '' }}>320 kbps</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Bitrate mặc định: <strong>{{ $story->default_tts_bitrate }} kbps</strong>
                            </small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="speed">Tốc độ đọc</label>
                            <select name="speed" id="speed" class="form-control" required>
                                <option value="0.5" {{ (string)$story->default_tts_speed === '0.5' ? 'selected' : '' }}>Chậm (0.5x)</option>
                                <option value="1.0" {{ (string)$story->default_tts_speed === '1' || (string)$story->default_tts_speed === '1.0' ? 'selected' : '' }}>Bình thường (1.0x)</option>
                                <option value="1.5" {{ (string)$story->default_tts_speed === '1.5' ? 'selected' : '' }}>Nhanh (1.5x)</option>
                                <option value="2.0" {{ (string)$story->default_tts_speed === '2' || (string)$story->default_tts_speed === '2.0' ? 'selected' : '' }}>Rất nhanh (2.0x)</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Tốc độ mặc định: <strong>{{ $story->default_tts_speed }}x</strong>
                            </small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="volume">Âm lượng</label>
                            <select name="volume" id="volume" class="form-control" required>
                                <option value="1.0" {{ (string)$story->default_tts_volume === '1' || (string)$story->default_tts_volume === '1.0' ? 'selected' : '' }}>100% (Bình thường)</option>
                                <option value="1.5" {{ (string)$story->default_tts_volume === '1.5' ? 'selected' : '' }}>150% (To)</option>
                                <option value="2.0" {{ (string)$story->default_tts_volume === '2' || (string)$story->default_tts_volume === '2.0' ? 'selected' : '' }}>200% (Rất to)</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Âm lượng mặc định: <strong>{{ ($story->default_tts_volume * 100) }}%</strong>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Thông tin TTS:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Hệ thống sẽ chuyển đổi nội dung text thành audio</li>
                        <li>Quá trình này có thể mất thời gian tùy thuộc vào số lượng chương và độ dài nội dung</li>
                        <li>Các chapter đã có audio sẽ được tự động bỏ qua</li>
                        <li><strong>Cài đặt mặc định:</strong>
                            @php $ttsSettings = $story->getFormattedTtsSettings(); @endphp
                            Giọng {{ $ttsSettings['voice'] }},
                            {{ $ttsSettings['bitrate'] }},
                            {{ $ttsSettings['speed'] }} speed,
                            {{ $ttsSettings['volume'] }} volume</li>
                    </ul>
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