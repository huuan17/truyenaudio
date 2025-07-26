@props([
    'name' => '',
    'label' => '',
    'accept' => '',
    'multiple' => false,
    'required' => false,
    'disabled' => false,
    'help' => '',
    'error' => '',
    'preview' => false,
    'previewType' => 'image', // image, audio, video
    'maxSize' => '',
    'class' => '',
    'labelClass' => '',
    'inputClass' => '',
    'wrapperClass' => 'form-group',
    'id' => null,
    'attributes' => []
])

@php
    $inputId = $id ?? $name;
    $hasError = !empty($error) || $errors->has($name);
    $inputClasses = 'form-control-file ' . $inputClass . ($hasError ? ' is-invalid' : '');
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $inputId }}" class="form-label {{ $labelClass }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="file"
        id="{{ $inputId }}"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        class="{{ $inputClasses }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($multiple) multiple @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($preview) onchange="previewFile(this, '{{ $previewType }}')" @endif
        {{ $attributes }}
    >
    
    @if($help)
        <small class="form-text text-muted">
            {{ $help }}
            @if($maxSize)
                <br>Kích thước tối đa: {{ $maxSize }}
            @endif
        </small>
    @endif
    
    @if($preview)
        <div id="{{ $inputId }}_preview" class="mt-2" style="display: none;">
            @if($previewType === 'image')
                <img id="{{ $inputId }}_preview_img" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
            @elseif($previewType === 'audio')
                <audio id="{{ $inputId }}_preview_audio" controls class="w-100"></audio>
            @elseif($previewType === 'video')
                <video id="{{ $inputId }}_preview_video" controls class="w-100" style="max-height: 300px;"></video>
            @endif
        </div>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback">
            {{ $error ?: $errors->first($name) }}
        </div>
    @endif
</div>

@if($preview)
    @push('scripts')
    <script>
    function previewFile(input, type) {
        const file = input.files[0];
        const previewContainer = document.getElementById(input.id + '_preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (type === 'image') {
                    const img = document.getElementById(input.id + '_preview_img');
                    img.src = e.target.result;
                } else if (type === 'audio') {
                    const audio = document.getElementById(input.id + '_preview_audio');
                    audio.src = e.target.result;
                } else if (type === 'video') {
                    const video = document.getElementById(input.id + '_preview_video');
                    video.src = e.target.result;
                }
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    }
    </script>
    @endpush
@endif
