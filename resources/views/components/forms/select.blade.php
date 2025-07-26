@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'selected' => '',
    'placeholder' => 'Chọn...',
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'help' => '',
    'error' => '',
    'class' => '',
    'labelClass' => '',
    'selectClass' => '',
    'wrapperClass' => 'form-group',
    'id' => null,
    'attributes' => []
])

@php
    $inputId = $id ?? $name;
    $hasError = !empty($error) || $errors->has($name);
    $selectClasses = 'form-control ' . $selectClass . ($hasError ? ' is-invalid' : '');
    $selectedValue = old($name, $selected);
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
    
    <select 
        id="{{ $inputId }}"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        class="{{ $selectClasses }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($multiple) multiple @endif
        {{ $attributes }}
    >
        @if($placeholder && !$multiple)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $value => $label)
            @if(is_array($label))
                <optgroup label="{{ $value }}">
                    @foreach($label as $subValue => $subLabel)
                        <option 
                            value="{{ $subValue }}"
                            @if($multiple && is_array($selectedValue))
                                {{ in_array($subValue, $selectedValue) ? 'selected' : '' }}
                            @else
                                {{ $selectedValue == $subValue ? 'selected' : '' }}
                            @endif
                        >
                            {{ $subLabel }}
                        </option>
                    @endforeach
                </optgroup>
            @else
                <option 
                    value="{{ $value }}"
                    @if($multiple && is_array($selectedValue))
                        {{ in_array($value, $selectedValue) ? 'selected' : '' }}
                    @else
                        {{ $selectedValue == $value ? 'selected' : '' }}
                    @endif
                >
                    {{ $label }}
                </option>
            @endif
        @endforeach
    </select>
    
    @if($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback">
            {{ $error ?: $errors->first($name) }}
        </div>
    @endif
</div>
