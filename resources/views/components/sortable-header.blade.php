@props(['route', 'routeParams' => [], 'column', 'title'])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'asc');
    
    // Determine new direction
    $newDirection = ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc';
    
    // Merge route parameters with sort parameters
    $params = array_merge($routeParams, request()->all(), [
        'sort' => $column,
        'direction' => $newDirection
    ]);
@endphp

<th>
    <a href="{{ route($route, $params) }}" 
       class="sortable-header text-decoration-none text-dark">
        {{ $title }}
        @if($currentSort === $column)
            @if($currentDirection === 'asc')
                <i class="fas fa-sort-up text-primary"></i>
            @else
                <i class="fas fa-sort-down text-primary"></i>
            @endif
        @else
            <i class="fas fa-sort text-muted"></i>
        @endif
    </a>
</th>
