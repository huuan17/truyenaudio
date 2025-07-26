@props([
    'id' => '',
    'title' => '',
    'size' => '', // sm, lg, xl
    'centered' => false,
    'scrollable' => false,
    'backdrop' => 'true', // true, false, static
    'keyboard' => 'true',
    'focus' => 'true',
    'show' => false,
    'fade' => true,
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
    'class' => ''
])

@php
    $modalClasses = 'modal' . ($fade ? ' fade' : '') . ($class ? ' ' . $class : '');
    $dialogClasses = 'modal-dialog';
    
    if ($size) $dialogClasses .= " modal-{$size}";
    if ($centered) $dialogClasses .= ' modal-dialog-centered';
    if ($scrollable) $dialogClasses .= ' modal-dialog-scrollable';
@endphp

<div 
    class="{{ $modalClasses }}" 
    id="{{ $id }}" 
    tabindex="-1" 
    aria-labelledby="{{ $id }}Label" 
    aria-hidden="true"
    data-bs-backdrop="{{ $backdrop }}"
    data-bs-keyboard="{{ $keyboard }}"
    data-bs-focus="{{ $focus }}"
    @if($show) style="display: block;" @endif
>
    <div class="{{ $dialogClasses }}">
        <div class="modal-content">
            @if($title || isset($header))
                <div class="modal-header {{ $headerClass }}">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    @endisset
                </div>
            @endif
            
            <div class="modal-body {{ $bodyClass }}">
                {{ $slot }}
            </div>
            
            @isset($footer)
                <div class="modal-footer {{ $footerClass }}">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
