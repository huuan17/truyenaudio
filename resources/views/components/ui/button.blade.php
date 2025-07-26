@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, light, dark
    'size' => '', // sm, lg
    'outline' => false,
    'disabled' => false,
    'loading' => false,
    'icon' => '',
    'iconPosition' => 'left', // left, right
    'href' => '',
    'target' => '',
    'class' => '',
    'id' => '',
    'attributes' => []
])

@php
    $baseClasses = 'btn';
    $variantClass = $outline ? "btn-outline-{$variant}" : "btn-{$variant}";
    $sizeClass = $size ? "btn-{$size}" : '';
    $disabledClass = ($disabled || $loading) ? 'disabled' : '';
    
    $buttonClasses = trim("{$baseClasses} {$variantClass} {$sizeClass} {$disabledClass} {$class}");
    
    $tag = $href ? 'a' : 'button';
    $typeAttr = $href ? '' : "type=\"{$type}\"";
    $hrefAttr = $href ? "href=\"{$href}\"" : '';
    $targetAttr = ($href && $target) ? "target=\"{$target}\"" : '';
@endphp

<{{ $tag }} 
    @if($id) id="{{ $id }}" @endif
    class="{{ $buttonClasses }}"
    {!! $typeAttr !!}
    {!! $hrefAttr !!}
    {!! $targetAttr !!}
    @if($disabled || $loading) 
        @if($tag === 'button') disabled @endif
        @if($tag === 'a') tabindex="-1" aria-disabled="true" @endif
    @endif
    {{ $attributes }}
>
    @if($loading)
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    @elseif($icon && $iconPosition === 'left')
        <i class="{{ $icon }} me-2"></i>
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right')
        <i class="{{ $icon }} ms-2"></i>
    @endif
</{{ $tag }}>
