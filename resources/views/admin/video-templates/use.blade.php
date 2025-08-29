@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Batch Results Display -->
    @if(session('batch_results') || session('batch_errors'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar mr-2"></i>Kết quả Batch Processing
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('batch_results'))
                        @php $results = session('batch_results'); @endphp
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle mr-2"></i>Video đã tạo thành công ({{ count($results) }})</h6>
                            <ul class="mb-0">
                                @foreach($results as $result)
                                    <li>{{ $result['video_name'] }} - {{ $result['message'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('batch_errors'))
                        @php $batchErrors = session('batch_errors'); @endphp
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle mr-2"></i>Video gặp lỗi ({{ count($batchErrors) }})</h6>
                            <ul class="mb-0">
                                @foreach($batchErrors as $error)
                                    <li><strong>{{ $error['video_name'] }}:</strong> {{ $error['error'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Quản lý Template Video', 'url' => route('admin.video-templates.index')],
        ['title' => 'Sử dụng Template: ' . $videoTemplate->name]
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-play mr-2"></i>Sử dụng Template: {{ $videoTemplate->name }}
                    </h5>

                    <!-- Mode Selection -->
                    <div class="mt-3">
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-light btn-sm active">
                                <input type="radio" name="template_mode" id="single_mode" value="single" checked>
                                <i class="fas fa-video mr-1"></i>Tạo 1 video
                            </label>
                            <label class="btn btn-outline-light btn-sm">
                                <input type="radio" name="template_mode" id="batch_mode" value="batch">
                                <i class="fas fa-layer-group mr-1"></i>Tạo nhiều video (Batch)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Single Video Form -->
                    <div id="single-form-container">
                        <form method="POST" action="{{ route('admin.video-generator.generate-from-template') }}"
                              enctype="multipart/form-data" id="templateUseForm">
                        @csrf
                        <input type="hidden" name="template_id" value="{{ $videoTemplate->id }}">

                        <!-- Required Inputs -->
                        @if($videoTemplate->required_inputs && count($videoTemplate->required_inputs) > 0)
                        <div class="form-section mb-4">
                            <h6 class="section-title text-danger">
                                <i class="fas fa-asterisk mr-2"></i>Thông tin bắt buộc
                            </h6>

                            @foreach($videoTemplate->required_inputs as $input)
                            <div class="form-group">
                                <label for="input_{{ $input['name'] }}">
                                    {{ $input['label'] }} <span class="text-danger">*</span>
                                </label>

                                @if($input['type'] === 'text')
                                    <input type="text" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control"
                                           value="{{ old('inputs.'.$input['name']) }}"
                                           placeholder="{{ $input['placeholder'] ?? '' }}" required>

                                @elseif($input['type'] === 'textarea')
                                    <textarea name="inputs[{{ $input['name'] }}]"
                                              id="input_{{ $input['name'] }}" class="form-control"
                                              rows="4" placeholder="{{ $input['placeholder'] ?? '' }}" required>{{ old('inputs.'.$input['name']) }}</textarea>

                                @elseif($input['type'] === 'audio')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control-file"
                                           accept="audio/*" required>
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn file âm thanh (MP3, WAV, M4A)' }}</small>

                                @elseif($input['type'] === 'image')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control-file"
                                           accept="image/*" required>
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn hình ảnh (JPG, PNG, GIF)' }}</small>

                                @elseif($input['type'] === 'images')
                                    <input type="file" name="inputs[{{ $input['name'] }}][]"
                                           id="input_{{ $input['name'] }}" class="form-control-file"
                                           accept="image/*" multiple required>
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn nhiều hình ảnh' }}</small>

                                @elseif($input['type'] === 'video')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control-file"
                                           accept="video/*" required>
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn file video (MP4, AVI, MOV)' }}</small>

                                @elseif($input['type'] === 'url')
                                    <input type="url" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control"
                                           placeholder="{{ $input['placeholder'] ?? 'https://...' }}" required>

                                @elseif($input['type'] === 'number')
                                    <input type="number" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control"
                                           placeholder="{{ $input['placeholder'] ?? '' }}" required>

                                @elseif($input['type'] === 'select')
                                    <select name="inputs[{{ $input['name'] }}]"
                                            id="input_{{ $input['name'] }}" class="form-control" required>
                                        <option value="">{{ $input['placeholder'] ?? 'Chọn...' }}</option>
                                        @if(isset($input['options']))
                                            @foreach($input['options'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                @elseif($input['type'] === 'file')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="input_{{ $input['name'] }}" class="form-control-file"
                                           accept="*/*" required>
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn file' }}</small>

                                @elseif($input['type'] === 'checkbox')
                                    <div class="form-check">
                                        <input type="checkbox" name="inputs[{{ $input['name'] }}]"
                                               id="input_{{ $input['name'] }}" class="form-check-input" value="1">
                                        <label class="form-check-label" for="input_{{ $input['name'] }}">
                                            {{ $input['placeholder'] ?? $input['label'] }}
                                        </label>
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Optional Inputs -->
                        @if($videoTemplate->optional_inputs && count($videoTemplate->optional_inputs) > 0)
                        <div class="form-section mb-4">
                            <h6 class="section-title text-info">
                                <i class="fas fa-plus-circle mr-2"></i>Thông tin tùy chọn
                            </h6>

                            @foreach($videoTemplate->optional_inputs as $input)
                            <div class="form-group">
                                <label for="optional_{{ $input['name'] }}">{{ $input['label'] }}</label>

                                @if($input['type'] === 'text')
                                    <input type="text" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control"
                                           value="{{ old('inputs.'.$input['name']) }}"
                                           placeholder="{{ $input['placeholder'] ?? '' }}">

                                @elseif($input['type'] === 'textarea')
                                    <textarea name="inputs[{{ $input['name'] }}]"
                                              id="optional_{{ $input['name'] }}" class="form-control"
                                              rows="3" placeholder="{{ $input['placeholder'] ?? '' }}">{{ old('inputs.'.$input['name']) }}</textarea>

                                    <!-- Font Selection for Subtitle -->
                                    @if(str_contains($input['name'], 'sub') || str_contains($input['name'], 'subtitle'))
                                    <div class="mt-2">
                                        <label for="subtitle_font_{{ $input['name'] }}" class="small text-muted">
                                            <i class="fas fa-font mr-1"></i>Font chữ cho subtitle
                                        </label>
                                        <select name="subtitle_font" id="subtitle_font_{{ $input['name'] }}" class="form-control form-control-sm">
                                            <option value="Lato">Lato (Khuyến nghị cho tiếng Việt)</option>
                                            <option value="Roboto">Roboto (Hiện đại, dễ đọc)</option>
                                            <option value="Arial">Arial (Cổ điển)</option>
                                            <option value="Calibri">Calibri (Mềm mại)</option>
                                            <option value="Tahoma">Tahoma (Rõ nét)</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Lato và Roboto hỗ trợ tốt nhất cho ký tự tiếng Việt
                                        </small>
                                    </div>
                                    @endif

                                @elseif($input['type'] === 'audio')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control-file"
                                           accept="audio/*">
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn file âm thanh (tùy chọn)' }}</small>

                                @elseif($input['type'] === 'image')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control-file"
                                           accept="image/*">
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn hình ảnh (tùy chọn)' }}</small>

                                @elseif($input['type'] === 'images')
                                    <input type="file" name="inputs[{{ $input['name'] }}][]"
                                           id="optional_{{ $input['name'] }}" class="form-control-file"
                                           accept="image/*" multiple>
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn nhiều hình ảnh (tùy chọn)' }}</small>

                                @elseif($input['type'] === 'video')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control-file"
                                           accept="video/*">
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn file video (tùy chọn)' }}</small>

                                @elseif($input['type'] === 'select')
                                    <select name="inputs[{{ $input['name'] }}]"
                                            id="optional_{{ $input['name'] }}" class="form-control">
                                        <option value="">{{ $input['placeholder'] ?? 'Chọn...' }}</option>
                                        @if(isset($input['options']))
                                            @foreach($input['options'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                @elseif($input['type'] === 'file')
                                    <input type="file" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control-file"
                                           accept="*/*">
                                    <small class="form-text text-muted">{{ $input['placeholder'] ?? 'Chọn file (tùy chọn)' }}</small>

                                @elseif($input['type'] === 'number')
                                    <input type="number" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control"
                                           placeholder="{{ $input['placeholder'] ?? '' }}">

                                @elseif($input['type'] === 'url')
                                    <input type="url" name="inputs[{{ $input['name'] }}]"
                                           id="optional_{{ $input['name'] }}" class="form-control"
                                           placeholder="{{ $input['placeholder'] ?? 'https://...' }}">

                                @elseif($input['type'] === 'checkbox')
                                    <div class="form-check">
                                        <input type="checkbox" name="inputs[{{ $input['name'] }}]"
                                               id="optional_{{ $input['name'] }}" class="form-check-input" value="1">
                                        <label class="form-check-label" for="optional_{{ $input['name'] }}">
                                            {{ $input['placeholder'] ?? $input['label'] }}
                                        </label>
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Duration Settings Info -->
                        @if($videoTemplate->required_inputs && collect($videoTemplate->required_inputs)->contains(function($input) { return str_contains($input['name'], 'duration'); }))
                        <div class="alert alert-info">
                            <h6><i class="fas fa-clock mr-2"></i>Cài đặt độ dài video</h6>
                            <p class="mb-2">Template này hỗ trợ kiểm soát độ dài video theo nhiều cách:</p>
                            <ul class="mb-0">
                                <li><strong>Theo ảnh:</strong> Độ dài = số ảnh × thời gian mỗi ảnh</li>
                                <li><strong>Theo âm thanh:</strong> Video sẽ khớp với độ dài file âm thanh</li>
                                <li><strong>Theo video nền:</strong> Sử dụng độ dài của video nền</li>
                                <li><strong>Cố định:</strong> Độ dài được định trước</li>
                            </ul>
                        </div>
                        @endif

                        <!-- Video Information -->
                        <div class="form-group">
                            <label for="video_title">
                                <i class="fas fa-video mr-2"></i>Tên video
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="video_title"
                                   id="video_title"
                                   class="form-control @error('video_title') is-invalid @enderror"
                                   value="{{ old('video_title', $videoTemplate->name) }}"
                                   placeholder="Nhập tên cho video..."
                                   required>
                            @error('video_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Tên này sẽ được sử dụng làm tiêu đề video và tên file output.
                            </small>
                        </div>

                        <!-- Audio Library Selection -->
                        @if($videoTemplate->background_music_type !== 'none')
                            @php $bgMusicInfo = $videoTemplate->getBackgroundMusicInfo(); @endphp
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Template có nhạc nền mặc định:</strong><br>
                                <strong>Loại:</strong> {{ $bgMusicInfo['type'] }}<br>
                                <strong>Tên:</strong> {{ $bgMusicInfo['name'] }}<br>
                                <strong>Âm lượng:</strong> {{ $bgMusicInfo['volume'] }}%<br>
                                <small class="text-muted">Bạn có thể thay đổi hoặc ghi đè bằng cách chọn nhạc khác bên dưới</small>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="background_audio">
                                <i class="fas fa-music mr-2"></i>Nhạc nền / Âm thanh
                                <span class="text-muted">(Tùy chọn - Ghi đè nhạc template nếu có)</span>
                            </label>
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="background_audio_id" id="background_audio" class="form-control">
                                        <option value="">-- Sử dụng nhạc nền template (nếu có) --</option>
                                        <optgroup label="Nhạc nền">
                                            @foreach($audioLibrary->where('category', 'music') as $audio)
                                                <option value="{{ $audio->id }}"
                                                        data-duration="{{ $audio->duration }}"
                                                        data-size="{{ $audio->file_size }}"
                                                        data-format="{{ $audio->format }}"
                                                        {{ old('background_audio_id') == $audio->id ? 'selected' : '' }}>
                                                    {{ $audio->title }}
                                                    @if($audio->duration > 0)
                                                        ({{ gmdate('i:s', $audio->duration) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Hiệu ứng âm thanh">
                                            @foreach($audioLibrary->where('category', 'effect') as $audio)
                                                <option value="{{ $audio->id }}"
                                                        data-duration="{{ $audio->duration }}"
                                                        data-size="{{ $audio->file_size }}"
                                                        data-format="{{ $audio->format }}"
                                                        {{ old('background_audio_id') == $audio->id ? 'selected' : '' }}>
                                                    {{ $audio->title }}
                                                    @if($audio->duration > 0)
                                                        ({{ gmdate('i:s', $audio->duration) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Khác">
                                            @foreach($audioLibrary->whereNotIn('category', ['music', 'effect']) as $audio)
                                                <option value="{{ $audio->id }}"
                                                        data-duration="{{ $audio->duration }}"
                                                        data-size="{{ $audio->file_size }}"
                                                        data-format="{{ $audio->format }}"
                                                        {{ old('background_audio_id') == $audio->id ? 'selected' : '' }}>
                                                    {{ $audio->title }}
                                                    @if($audio->duration > 0)
                                                        ({{ gmdate('i:s', $audio->duration) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-info btn-block" id="previewAudioBtn" disabled>
                                        <i class="fas fa-play mr-1"></i>Nghe thử
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Chọn file âm thanh từ thư viện để làm nhạc nền cho video.
                                Âm thanh sẽ được lặp lại hoặc cắt ngắn để khớp với độ dài video.
                            </small>

                            <!-- Audio Info Display -->
                            <div id="audioInfo" class="mt-2" style="display: none;">
                                <div class="audio-info-card">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-2">
                                                <i class="fas fa-music mr-2 text-primary"></i>Thông tin audio
                                            </h6>
                                            <div id="audioDetails" class="audio-details"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-2">
                                                <i class="fas fa-headphones mr-2 text-success"></i>Nghe thử
                                            </h6>
                                            <div class="audio-preview-controls">
                                                <audio id="audioPreview" controls class="w-100" style="height: 40px;">
                                                    <source src="" type="audio/mpeg">
                                                    Trình duyệt không hỗ trợ audio.
                                                </audio>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Progress -->
                        <div id="uploadProgress" class="form-group" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-upload mr-2"></i>Đang upload...
                                    </h6>
                                    <div class="progress mb-2">
                                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small id="progressText">Chuẩn bị upload...</small>
                                        <small id="progressPercent">0%</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Channel Integration -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-upload mr-2"></i>Kênh đăng tải (tùy chọn)
                            </h6>
                            <p class="text-muted">Chọn kênh để tự động đăng tải video sau khi tạo xong</p>

                            <div class="form-group">
                                <label for="channel_id">Kênh đăng tải</label>
                                <select name="channel_id" id="channel_id" class="form-control">
                                    <option value="">-- Không tự động đăng tải --</option>
                                    @if(isset($channels))
                                        @foreach($channels as $channel)
                                            <option value="{{ $channel->id }}"
                                                    data-platform="{{ $channel->platform }}"
                                                    {{ old('channel_id', $videoTemplate->default_channel_id) == $channel->id ? 'selected' : '' }}>
                                                {{ $channel->name }} ({{ ucfirst($channel->platform) }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Channel Metadata -->
                            <div id="channel-metadata" style="display: none;">
                                <div class="form-group">
                                    <label for="video_title">Tiêu đề video</label>
                                    <input type="text" name="video_title" id="video_title" class="form-control"
                                           value="{{ old('video_title') }}"
                                           placeholder="Nhập tiêu đề video hoặc để trống để tự động tạo">
                                </div>

                                <div class="form-group">
                                    <label for="video_description">Mô tả video</label>
                                    <textarea name="video_description" id="video_description" class="form-control" rows="4"
                                              placeholder="Nhập mô tả video...">{{ old('video_description') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="video_tags">Tags (phân cách bằng dấu phẩy)</label>
                                    <input type="text" name="video_tags" id="video_tags" class="form-control"
                                           value="{{ old('video_tags') }}"
                                           placeholder="tag1, tag2, tag3">
                                </div>

                                <div class="form-group">
                                    <label for="video_hashtags">Hashtags (phân cách bằng dấu phẩy)</label>
                                    <input type="text" name="video_hashtags" id="video_hashtags" class="form-control"
                                           value="{{ old('video_hashtags') }}"
                                           placeholder="#hashtag1, #hashtag2, #hashtag3">
                                </div>

                                <div class="form-group">
                                    <label for="video_thumbnail">Thumbnail (tùy chọn)</label>
                                    <input type="file" name="video_thumbnail" id="video_thumbnail" class="form-control-file"
                                           accept="image/*">
                                    <small class="form-text text-muted">
                                        Upload ảnh thumbnail tùy chỉnh. Để trống để sử dụng thumbnail tự động.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="submitBtn">
                                <i class="fas fa-video mr-2"></i>Tạo Video từ Template
                            </button>
                            <a href="{{ route('admin.video-templates.index') }}" class="btn btn-secondary btn-lg ml-2 px-4">
                                <i class="fas fa-arrow-left mr-2"></i>Quay lại
                            </a>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Upload lớn có thể mất vài phút. Vui lòng không đóng trang trong quá trình xử lý.
                                </small>
                            </div>
                        </div>
                        </form>
                    </div>

                    <!-- Batch Video Form -->
                    <div id="batch-form-container" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Chế độ Batch:</strong> Tạo nhiều video cùng lúc với các nội dung khác nhau nhưng cùng template.
                        </div>

                        <form method="POST" action="{{ route('admin.video-generator.generate-batch-from-template') }}"
                              enctype="multipart/form-data" id="templateBatchForm">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $videoTemplate->id }}">

                            <!-- Batch Settings -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary">
                                    <i class="fas fa-cogs mr-2"></i>Cài đặt chung cho Batch
                                </h6>

                                <!-- Number of Videos -->
                                <div class="form-group">
                                    <label for="batch_count">
                                        <i class="fas fa-hashtag mr-1"></i>Số lượng video muốn tạo
                                    </label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <input type="number" name="batch_count" id="batch_count"
                                                   class="form-control" min="2" max="20" value="3"
                                                   onchange="generateBatchInputs()">
                                        </div>
                                        <div class="col-md-8">
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Tối đa 20 video mỗi lần. Nhiều video sẽ mất thời gian xử lý lâu hơn.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Common Audio -->
                                <div class="form-group">
                                    <label for="batch_background_audio">
                                        <i class="fas fa-music mr-2"></i>Nhạc nền chung cho tất cả video
                                        <span class="text-muted">(Tùy chọn)</span>
                                    </label>
                                    <select name="batch_background_audio_id" id="batch_background_audio" class="form-control">
                                        <option value="">-- Sử dụng nhạc nền template (nếu có) --</option>
                                        <optgroup label="Nhạc nền">
                                            @foreach($audioLibrary->where('category', 'music') as $audio)
                                                <option value="{{ $audio->id }}">
                                                    {{ $audio->title }}
                                                    @if($audio->duration > 0)
                                                        ({{ gmdate('i:s', $audio->duration) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>

                                <!-- Common Channel -->
                                <div class="form-group">
                                    <label for="batch_channel">
                                        <i class="fas fa-tv mr-2"></i>Kênh upload chung
                                        <span class="text-muted">(Tùy chọn)</span>
                                    </label>
                                    <select name="batch_channel_id" id="batch_channel" class="form-control">
                                        <option value="">-- Không upload tự động --</option>
                                        @foreach($channels as $channel)
                                            <option value="{{ $channel->id }}">
                                                {{ $channel->platform }} - {{ $channel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Batch Inputs -->
                            <div id="batch-inputs-container">
                                <!-- Will be generated by JavaScript -->
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-success btn-lg px-4" id="batchSubmitBtn">
                                    <i class="fas fa-layer-group mr-2"></i>Tạo <span id="batch-count-display">3</span> Video từ Template
                                </button>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-clock mr-1"></i>
                                        Batch processing có thể mất nhiều thời gian. Các video sẽ được xử lý tuần tự.
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Info Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin Template</h6>
                </div>
                <div class="card-body">
                    @if($videoTemplate->thumbnail)
                        <img src="{{ Storage::url($videoTemplate->thumbnail) }}"
                             class="img-fluid mb-3 rounded">
                    @endif

                    <h6>{{ $videoTemplate->name }}</h6>
                    <p class="text-muted">{{ $videoTemplate->description }}</p>

                    <div class="template-stats">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="stat-item">
                                    <h5 class="text-primary">{{ $videoTemplate->usage_count }}</h5>
                                    <small class="text-muted">Lượt sử dụng</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <h5 class="text-info">{{ count($videoTemplate->required_inputs ?? []) }}</h5>
                                    <small class="text-muted">Input bắt buộc</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="template-meta">
                        <small class="text-muted d-block">
                            <i class="fas fa-user mr-1"></i>
                            Tạo bởi: {{ $videoTemplate->creator->name ?? 'Unknown' }}
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-calendar mr-1"></i>
                            {{ $videoTemplate->created_at->format('d/m/Y') }}
                        </small>
                        @if($videoTemplate->last_used_at)
                        <small class="text-muted d-block">
                            <i class="fas fa-clock mr-1"></i>
                            Dùng lần cuối: {{ $videoTemplate->last_used_at->diffForHumans() }}
                        </small>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Template Settings Preview -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-cog mr-2"></i>Cài đặt Template</h6>
                </div>
                <div class="card-body">
                    <div class="settings-preview">
                        @php
                            $settings = $videoTemplate->settings;
                        @endphp

                        @if(isset($settings['platform']))
                        <div class="setting-item mb-2">
                            <strong>Platform:</strong>
                            <span class="badge badge-{{ $settings['platform'] === 'tiktok' ? 'info' : ($settings['platform'] === 'youtube' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($settings['platform']) }}
                            </span>
                        </div>
                        @endif

                        @if(isset($settings['media_type']))
                        <div class="setting-item mb-2">
                            <strong>Loại media:</strong> {{ $settings['media_type'] }}
                        </div>
                        @endif

                        @if(isset($settings['enable_subtitle']))
                        <div class="setting-item mb-2">
                            <strong>Phụ đề:</strong>
                            <span class="badge badge-{{ $settings['enable_subtitle'] ? 'success' : 'secondary' }}">
                                {{ $settings['enable_subtitle'] ? 'Có' : 'Không' }}
                            </span>
                        </div>
                        @endif

                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Các cài đặt này sẽ được áp dụng tự động
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-section {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 1rem;
}

.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
}

.stat-item {
    padding: 0.5rem;
}

.setting-item {
    padding: 0.25rem 0;
    border-bottom: 1px solid #f1f1f1;
}

.setting-item:last-child {
    border-bottom: none;
}

.template-stats {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
    margin: 1rem 0;
}

/* Audio selector styling */
.audio-info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border: 1px solid #bbdefb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.audio-preview-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.audio-preview-controls audio {
    flex: 1;
    height: 40px;
}

#previewAudioBtn {
    min-width: 100px;
    transition: all 0.3s ease;
}

#previewAudioBtn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.audio-details {
    font-size: 0.9rem;
}

.audio-details div {
    margin-bottom: 0.25rem;
}

/* Batch specific styles */
#batch-form-container .card {
    border: 1px solid #e3f2fd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#batch-form-container .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

#batch-inputs-container .card {
    transition: all 0.3s ease;
}

#batch-inputs-container .card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.batch-video-card {
    border-left: 4px solid #28a745;
}

.batch-count-badge {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.batch-results {
    max-height: 300px;
    overflow-y: auto;
}

.batch-results ul {
    max-height: 200px;
    overflow-y: auto;
}

.btn-group-toggle .btn {
    border-radius: 0.375rem !important;
}

.btn-group-toggle .btn:first-child {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}

.btn-group-toggle .btn:last-child {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}

.btn-group-toggle .btn.active {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: white !important;
}
</style>
@endpush

@push('scripts')
<script>
// Audio Library Selection Handler
const bgSelect = document.getElementById('background_audio');
if (bgSelect) {
    bgSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const audioInfo = document.getElementById('audioInfo');
        const audioDetails = document.getElementById('audioDetails');
        const audioPreview = document.getElementById('audioPreview');
        const previewBtn = document.getElementById('previewAudioBtn');

    if (this.value) {
        // Show audio info
        audioInfo.style.display = 'block';
        previewBtn.disabled = false;

        // Get audio data
        const duration = selectedOption.dataset.duration;
        const size = selectedOption.dataset.size;
        const format = selectedOption.dataset.format;
        const audioId = this.value;

        // Format duration
        const formattedDuration = duration > 0 ?
            new Date(duration * 1000).toISOString().substr(14, 5) : 'N/A';

        // Format file size
        const formattedSize = size ? (size / 1024 / 1024).toFixed(2) + ' MB' : 'N/A';

        // Update details
        audioDetails.innerHTML = `
            <div><strong>Tên:</strong> ${selectedOption.text}</div>
            <div><strong>Thời lượng:</strong> ${formattedDuration}</div>
            <div><strong>Kích thước:</strong> ${formattedSize}</div>
            <div><strong>Định dạng:</strong> ${format || 'N/A'}</div>
        `;

        // Set audio source
        const audioUrl = `/admin/audio-library/${audioId}/stream`;
        audioPreview.src = audioUrl;
        audioPreview.load();

    } else {
        // Hide audio info
        audioInfo.style.display = 'none';
        previewBtn.disabled = true;
        audioPreview.src = '';
    }
});
}


// Preview button handler
const previewBtnEl = document.getElementById('previewAudioBtn');
if (previewBtnEl) previewBtnEl.addEventListener('click', function() {
    const audioPreview = document.getElementById('audioPreview');

    if (audioPreview.paused) {
        audioPreview.play();
        this.innerHTML = '<i class="fas fa-pause mr-1"></i>Dừng';
    } else {
        audioPreview.pause();
        this.innerHTML = '<i class="fas fa-play mr-1"></i>Nghe thử';
    }
});

// Reset preview button when audio ends
const audioPreviewEl = document.getElementById('audioPreview');
if (audioPreviewEl) audioPreviewEl.addEventListener('ended', function() {
    const previewBtn = document.getElementById('previewAudioBtn');
    previewBtn.innerHTML = '<i class="fas fa-play mr-1"></i>Nghe thử';
});

// File size validation
function validateFileSize() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    const maxFileSize = serverLimits.max_single_file || 500 * 1024 * 1024;
    const maxTotalSize = serverLimits.max_total_upload || 1024 * 1024 * 1024;
    let totalSize = 0;
    let hasError = false;

    fileInputs.forEach(function(input) {
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach(function(file) {
                if (file.size > maxFileSize) {
                    hasError = true;
                    const maxSizeMB = (maxFileSize / 1024 / 1024).toFixed(0);
                    const fileSizeMB = (file.size / 1024 / 1024).toFixed(1);
                    alert(`File "${file.name}" quá lớn (${fileSizeMB}MB). Tối đa ${maxSizeMB}MB mỗi file.`);
                    input.classList.add('is-invalid');
                }
                totalSize += file.size;
            });
        }
    });

    if (totalSize > maxTotalSize) {
        hasError = true;
        const maxTotalMB = (maxTotalSize / 1024 / 1024).toFixed(0);
        const totalSizeMB = (totalSize / 1024 / 1024).toFixed(1);
        alert(`Tổng dung lượng files quá lớn (${totalSizeMB}MB). Tối đa ${maxTotalMB}MB tổng cộng.`);
    }

    // Warning for large uploads
    if (totalSize > maxTotalSize * 0.8 && !hasError) {
        const warningMB = (totalSize / 1024 / 1024).toFixed(1);
        const maxMB = (maxTotalSize / 1024 / 1024).toFixed(0);
        if (!confirm(`Upload lớn (${warningMB}MB/${maxMB}MB). Quá trình có thể mất vài phút. Tiếp tục?`)) {
            hasError = true;
        }
    }

    return !hasError;
}

// Show file size info
function showFileSizeInfo() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    let totalSize = 0;
    let fileCount = 0;

    fileInputs.forEach(function(input) {
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach(function(file) {
                totalSize += file.size;
                fileCount++;
            });
        }
    });

    if (fileCount > 0) {
        const sizeInfo = document.getElementById('fileSizeInfo') || createFileSizeInfo();
        sizeInfo.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Files đã chọn:</strong> ${fileCount} files,
                tổng ${(totalSize / 1024 / 1024).toFixed(1)}MB
                ${totalSize > 500 * 1024 * 1024 ? '<span class="text-warning">(Dung lượng lớn - có thể mất thời gian upload)</span>' : ''}
            </div>
        `;
        sizeInfo.style.display = 'block';
    }
}

function createFileSizeInfo() {
    const info = document.createElement('div');
    info.id = 'fileSizeInfo';
    info.style.display = 'none';
    document.getElementById('templateUseForm').appendChild(info);
    return info;
}

// Load server limits on page load
let serverLimits = {};
document.addEventListener('DOMContentLoaded', function() {
    // Load server upload limits
    fetch('/admin/check-upload-limits')
        .then(response => response.json())
        .then(data => {
            serverLimits = data.recommendations;
            console.log('Server upload limits loaded:', serverLimits);
        })
        .catch(error => {
            console.warn('Could not load server limits:', error);
            // Fallback limits - match server validation
            serverLimits = {
                max_total_upload: 1024 * 1024 * 1024, // 1GB total
                max_single_file: 500 * 1024 * 1024,   // 500MB per file
                max_files: 50
            };
        });

    // Add file change listeners
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', showFileSizeInfo);

        // Show previously selected file names if available
        const inputName = input.name.replace('inputs[', '').replace(']', '');
        const oldInputs = @json(old('inputs')) || {};
        const oldFileName = oldInputs[inputName + '_file'];
        const oldFileNames = oldInputs[inputName + '_files'];

        if (oldFileName) {
            showPreviousFileSelection(input, [oldFileName]);
        } else if (oldFileNames && oldFileNames.length > 0) {
            showPreviousFileSelection(input, oldFileNames);
        }
    });

    // Restore background audio selection and show info
    const backgroundAudioSelect = document.getElementById('background_audio');
    if (backgroundAudioSelect && backgroundAudioSelect.value) {
        // Trigger change event to show audio info
        backgroundAudioSelect.dispatchEvent(new Event('change'));
    }

    // Channel integration functionality
    const channelSelect = document.getElementById('channel_id');
    const channelMetadata = document.getElementById('channel-metadata');

    if (channelSelect && channelMetadata) {
        channelSelect.addEventListener('change', function() {
            if (this.value) {
                channelMetadata.style.display = 'block';

                // Auto-populate metadata from template if available
                const videoTitle = document.getElementById('video_title');
                if (videoTitle && !videoTitle.value) {
                    // Generate auto title from template name
                    videoTitle.value = '{{ $videoTemplate->generateVideoName() }}';
                }
            } else {
                channelMetadata.style.display = 'none';
            }
        });

        // Trigger change event if channel is pre-selected
        if (channelSelect.value) {
            channelSelect.dispatchEvent(new Event('change'));
        }
    }
});

// Show previously selected file names
function showPreviousFileSelection(input, fileNames) {
    const container = input.parentElement;
    let infoDiv = container.querySelector('.previous-file-info');

    if (!infoDiv) {
        infoDiv = document.createElement('div');
        infoDiv.className = 'previous-file-info alert alert-info mt-2';
        container.appendChild(infoDiv);
    }

    const fileList = fileNames.map(name => `<span class="badge badge-secondary mr-1">${name}</span>`).join('');
    infoDiv.innerHTML = `
        <small>
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Files đã chọn trước đó:</strong><br>
            ${fileList}
            <br><em class="text-muted">Chọn file mới để thay thế hoặc giữ nguyên để sử dụng lại.</em>
        </small>
    `;
}

// Form validation
const singleForm = document.getElementById('templateUseForm');
if (singleForm) singleForm.addEventListener('submit', function(e) {
    const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    let hasError = false;

    // Validate required fields
    requiredInputs.forEach(function(input) {
        if (!input.value.trim()) {
            hasError = true;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    // Validate file sizes
    if (!validateFileSize()) {
        hasError = true;
    }

    if (hasError) {
        e.preventDefault();
        return false;
    }

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';

        // Re-enable after 30 seconds to prevent permanent disable
        setTimeout(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-video mr-2"></i>Tạo Video';
        }, 30000);
    }
});

// File input preview
document.querySelectorAll('input[type="file"]').forEach(function(input) {
    input.addEventListener('change', function() {
        const files = this.files;
        if (files.length > 0) {
            let fileNames = [];
            for (let i = 0; i < files.length; i++) {
                fileNames.push(files[i].name);
            }

            // Show selected files
            let preview = this.parentNode.querySelector('.file-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'file-preview mt-2';
                this.parentNode.appendChild(preview);
            }

            preview.innerHTML = '<small class="text-success"><i class="fas fa-check mr-1"></i>' +
                               fileNames.join(', ') + '</small>';
        }
    });
});

// Handle duration control visibility
document.addEventListener('change', function(e) {
    // Handle duration control selects
    if (e.target.name && (e.target.name.includes('duration_control') ||
                         e.target.name.includes('duration_strategy'))) {
        toggleDurationInputs(e.target);
    }
});

function toggleDurationInputs(selectElement) {
    const value = selectElement.value;
    const form = selectElement.closest('form');

    // Hide all duration-related inputs first
    const durationInputs = form.querySelectorAll('input[name*="duration"], input[name*="time"]');
    durationInputs.forEach(input => {
        const group = input.closest('.form-group');
        if (group) group.style.display = 'none';
    });

    // Show relevant inputs based on selection
    if (value === 'fixed_duration' || value === 'custom_length' || value === 'fixed_marketing') {
        showInputByName(form, 'fixed_duration_seconds');
        showInputByName(form, 'custom_video_length');
        showInputByName(form, 'tiktok_target_duration');
        showInputByName(form, 'youtube_target_duration');
    }

    if (value === 'auto_images' || value === 'content_based' || value === 'content_driven') {
        showInputByName(form, 'image_duration_seconds');
        showInputByName(form, 'lesson_image_duration');
        showInputByName(form, 'product_showcase_time');
    }

    if (value === 'audio_length' || value === 'audio_sync' || value === 'audio_matched') {
        showInputByName(form, 'sync_tolerance');
        showInputByName(form, 'max_video_duration');
    }

    if (value === 'platform_optimal') {
        showInputByName(form, 'tiktok_target_duration');
        showInputByName(form, 'youtube_target_duration');
        showInputByName(form, 'cta_duration');
    }
}

function showInputByName(form, inputName) {
    const input = form.querySelector(`input[name*="${inputName}"]`);
    if (input) {
        const group = input.closest('.form-group');
        if (group) group.style.display = 'block';
    }
}

// Initialize duration controls on page load
document.addEventListener('DOMContentLoaded', function() {
    const durationSelects = document.querySelectorAll('select[name*="duration"]');
    durationSelects.forEach(select => {
        if (select.value) {
            toggleDurationInputs(select);
        }
    });

    // Add form submission handler
    const form = document.getElementById('templateUseForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== TEMPLATE FORM SUBMISSION ===');
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);

            // Log form data
            const formData = new FormData(form);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(key + ':', 'FILE -', value.name, value.size + ' bytes');
                } else {
                    console.log(key + ':', value);
                }
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang tạo video...';
            }
        });
    }

    // Template mode switching
    const templateModeRadios = document.querySelectorAll('input[name="template_mode"]');
    templateModeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const singleContainer = document.getElementById('single-form-container');
            const batchContainer = document.getElementById('batch-form-container');
            if (!singleContainer || !batchContainer) return;

            if (this.value === 'single') {
                singleContainer.style.display = 'block';
                batchContainer.style.display = 'none';
            } else {
                singleContainer.style.display = 'none';
                batchContainer.style.display = 'block';
                console.log('[Batch] switched to batch mode');
                generateBatchInputs(); // Generate initial batch inputs
            }
        });
    });

    // Auto switch to batch if query mode=batch
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('mode') === 'batch') {
        const batchRadio = document.getElementById('batch_mode');
        if (batchRadio) {
            batchRadio.checked = true;
            batchRadio.dispatchEvent(new Event('change'));
        }
    }
});

/**
 * Generate batch input forms based on count
 */
function generateBatchInputs() {
    const count = parseInt(document.getElementById('batch_count').value) || 3;
    const container = document.getElementById('batch-inputs-container');
    const template = @json($videoTemplate);

    // Update display count
    document.getElementById('batch-count-display').textContent = count;

    let html = '<div class="form-section mb-4">';
    html += '<h6 class="section-title text-success">';
    html += '<i class="fas fa-list mr-2"></i>Nội dung cho từng video';
    html += '</h6>';

    for (let i = 1; i <= count; i++) {
        html += `<div class="card mb-3">`;
        html += `<div class="card-header bg-light">`;
        html += `<h6 class="mb-0"><i class="fas fa-video mr-2"></i>Video ${i}</h6>`;
        html += `</div>`;
        html += `<div class="card-body">`;

        // Video name
        html += `<div class="form-group">`;
        html += `<label for="batch_video_name_${i}">Tên video ${i} <span class="text-danger">*</span></label>`;
        html += `<input type="text" name="batch_videos[${i-1}][video_name]" id="batch_video_name_${i}" class="form-control" required>`;
        html += `</div>`;

        // Required inputs
        if (template.required_inputs && template.required_inputs.length > 0) {
            template.required_inputs.forEach(input => {
                html += generateBatchInputField(input, i, true);
            });
        }

        // Optional inputs
        if (template.optional_inputs && template.optional_inputs.length > 0) {
            template.optional_inputs.forEach(input => {
                html += generateBatchInputField(input, i, false);
            });
        }

        html += `</div>`;
        html += `</div>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

/**
 * Generate input field for batch
 */
function generateBatchInputField(input, videoIndex, isRequired) {
    const fieldName = `batch_videos[${videoIndex-1}][inputs][${input.name}]`;
    const fieldId = `batch_${input.name}_${videoIndex}`;
    const requiredAttr = isRequired ? 'required' : '';
    const requiredMark = isRequired ? '<span class="text-danger">*</span>' : '';

    let html = `<div class="form-group">`;
    html += `<label for="${fieldId}">${input.label} ${requiredMark}</label>`;

    switch (input.type) {
        case 'text':
            html += `<input type="text" name="${fieldName}" id="${fieldId}" class="form-control" ${requiredAttr} placeholder="${input.placeholder || ''}">`;
            break;

        case 'textarea':
            html += `<textarea name="${fieldName}" id="${fieldId}" class="form-control" rows="3" ${requiredAttr} placeholder="${input.placeholder || ''}"></textarea>`;
            break;

        case 'image':
            html += `<input type="file" name="${fieldName}" id="${fieldId}" class="form-control-file" accept="image/*" ${requiredAttr}>`;
            html += `<small class="form-text text-muted">${input.placeholder || 'Chọn hình ảnh'}</small>`;
            break;

        case 'images':
            html += `<input type="file" name="${fieldName}[]" id="${fieldId}" class="form-control-file" accept="image/*" multiple ${requiredAttr}>`;
            html += `<small class="form-text text-muted">${input.placeholder || 'Chọn nhiều hình ảnh'}</small>`;
            break;

        case 'video':
            html += `<input type="file" name="${fieldName}" id="${fieldId}" class="form-control-file" accept="video/*" ${requiredAttr}>`;
            html += `<small class="form-text text-muted">${input.placeholder || 'Chọn file video'}</small>`;
            break;

        case 'audio':
            html += `<input type="file" name="${fieldName}" id="${fieldId}" class="form-control-file" accept="audio/*" ${requiredAttr}>`;
            html += `<small class="form-text text-muted">${input.placeholder || 'Chọn file âm thanh'}</small>`;
            break;

        case 'select':
            html += `<select name="${fieldName}" id="${fieldId}" class="form-control" ${requiredAttr}>`;
            html += `<option value="">${input.placeholder || 'Chọn...'}</option>`;
            if (input.options) {
                Object.entries(input.options).forEach(([value, label]) => {
                    html += `<option value="${value}">${label}</option>`;
                });
            }
            html += `</select>`;
            break;

        case 'number':
            html += `<input type="number" name="${fieldName}" id="${fieldId}" class="form-control" ${requiredAttr} placeholder="${input.placeholder || ''}">`;
            break;

        case 'checkbox':
            html += `<div class="form-check">`;
            html += `<input type="checkbox" name="${fieldName}" id="${fieldId}" class="form-check-input" value="1">`;
            html += `<label class="form-check-label" for="${fieldId}">${input.placeholder || input.label}</label>`;
            html += `</div>`;
            break;

        default:
            html += `<input type="text" name="${fieldName}" id="${fieldId}" class="form-control" ${requiredAttr} placeholder="${input.placeholder || ''}">`;
    }

    html += `</div>`;
    return html;
}

</script>

<!-- Video Preview Script -->
<script src="{{ asset('js/video-preview.js') }}"></script>
@endpush
