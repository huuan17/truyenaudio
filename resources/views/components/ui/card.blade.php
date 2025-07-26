@props([
    'title' => '',
    'subtitle' => '',
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
    'class' => '',
    'border' => '', // primary, secondary, success, danger, warning, info, light, dark
    'textAlign' => '', // start, center, end
])

@php
    $cardClasses = 'card';
    if ($border) $cardClasses .= " border-{$border}";
    if ($textAlign) $cardClasses .= " text-{$textAlign}";
    if ($class) $cardClasses .= " {$class}";
@endphp

<div class="{{ $cardClasses }}">
    @if($title || $subtitle || isset($header))
        <div class="card-header {{ $headerClass }}">
            @isset($header)
                {{ $header }}
            @else
                @if($title)
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                @endif
                @if($subtitle)
                    <h6 class="card-subtitle text-muted">{{ $subtitle }}</h6>
                @endif
            @endisset
        </div>
    @endif
    
    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
    
    @isset($footer)
        <div class="card-footer {{ $footerClass }}">
            {{ $footer }}
        </div>
    @endisset
</div>
