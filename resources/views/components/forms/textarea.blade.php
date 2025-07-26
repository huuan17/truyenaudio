@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'placeholder' => '',
    'rows' => 4,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'help' => '',
    'error' => '',
    'class' => '',
    'labelClass' => '',
    'textareaClass' => '',
    'wrapperClass' => 'form-group',
    'id' => null,
    'attributes' => []
])

@php
    $inputId = $id ?? $name;
    $hasError = !empty($error) || $errors->has($name);
    $textareaClasses = 'form-control ' . $textareaClass . ($hasError ? ' is-invalid' : '');
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
    
    <textarea 
        id="{{ $inputId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        class="{{ $textareaClasses }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>
    
    @if($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback">
            {{ $error ?: $errors->first($name) }}
        </div>
    @endif
</div>
