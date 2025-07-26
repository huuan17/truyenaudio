@props([
    'type' => 'info', // success, danger, warning, info, primary, secondary
    'message' => '',
    'dismissible' => false,
    'icon' => null,
    'class' => ''
])

@php
    $alertClasses = "alert alert-{$type}";
    if ($dismissible) $alertClasses .= ' alert-dismissible fade show';
    if ($class) $alertClasses .= " {$class}";
    
    // Auto-detect icon based on type
    if (!$icon) {
        $icons = [
            'success' => 'fas fa-check-circle',
            'danger' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info' => 'fas fa-info-circle',
            'primary' => 'fas fa-info-circle',
            'secondary' => 'fas fa-info-circle',
        ];
        $icon = $icons[$type] ?? 'fas fa-info-circle';
    }
@endphp

<div class="{{ $alertClasses }}" role="alert">
    @if($icon)
        <i class="{{ $icon }} me-2"></i>
    @endif
    
    @if($message)
        {{ $message }}
    @else
        {{ $slot }}
    @endif
    
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
