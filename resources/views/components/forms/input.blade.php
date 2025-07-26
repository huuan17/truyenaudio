@props([
    'type' => 'text',
    'name' => '',
    'label' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'help' => '',
    'error' => '',
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
    $inputClasses = 'form-control ' . $inputClass . ($hasError ? ' is-invalid' : '');
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
        type="{{ $type }}"
        id="{{ $inputId }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        class="{{ $inputClasses }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        @foreach($attributes as $attr => $val)
            {{ $attr }}="{{ $val }}"
        @endforeach
        {{ $attributes }}
    >
    
    @if($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback">
            {{ $error ?: $errors->first($name) }}
        </div>
    @endif
</div>
