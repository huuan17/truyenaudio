<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait SortableTrait
{
    /**
     * Apply sorting to query based on request parameters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param array $allowedSorts
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query, Request $request, array $allowedSorts, string $defaultSort = 'id', string $defaultDirection = 'desc')
    {
        $sortBy = $request->get('sort', $defaultSort);
        $sortDirection = $request->get('direction', $defaultDirection);
        
        // Validate sort parameters
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = $defaultSort;
        }
        
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = $defaultDirection;
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Generate sortable header link
     *
     * @param string $routeName
     * @param array $routeParams
     * @param string $column
     * @param string $title
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function generateSortableHeader(string $routeName, array $routeParams, string $column, string $title, Request $request): string
    {
        $currentSort = $request->get('sort');
        $currentDirection = $request->get('direction', 'asc');
        
        // Determine new direction
        $newDirection = ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc';
        
        // Merge route parameters with sort parameters
        $params = array_merge($routeParams, $request->all(), [
            'sort' => $column,
            'direction' => $newDirection
        ]);
        
        $url = route($routeName, $params);
        
        // Generate icon
        $icon = '';
        if ($currentSort === $column) {
            $icon = $currentDirection === 'asc' 
                ? '<i class="fas fa-sort-up text-primary"></i>' 
                : '<i class="fas fa-sort-down text-primary"></i>';
        } else {
            $icon = '<i class="fas fa-sort text-muted"></i>';
        }
        
        return sprintf(
            '<a href="%s" class="sortable-header text-decoration-none text-dark">%s %s</a>',
            $url,
            $title,
            $icon
        );
    }
}
