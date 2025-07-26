@props([
    'headers' => [],
    'data' => [],
    'actions' => [],
    'sortable' => true,
    'searchable' => true,
    'pagination' => null,
    'emptyMessage' => 'Không có dữ liệu',
    'class' => '',
    'tableClass' => 'table table-striped table-hover',
    'responsive' => true,
    'checkboxes' => false,
    'bulkActions' => []
])

<div class="table-container {{ $class }}">
    @if($searchable || $checkboxes)
        <div class="table-controls mb-3 d-flex justify-content-between align-items-center">
            @if($searchable)
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="Tìm kiếm..." id="tableSearch">
                </div>
            @endif
            
            @if($checkboxes && count($bulkActions) > 0)
                <div class="bulk-actions" style="display: none;">
                    <select class="form-select" id="bulkActionSelect">
                        <option value="">Chọn hành động...</option>
                        @foreach($bulkActions as $action => $label)
                            <option value="{{ $action }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-primary ms-2" onclick="executeBulkAction()">Thực hiện</button>
                </div>
            @endif
        </div>
    @endif
    
    <div class="{{ $responsive ? 'table-responsive' : '' }}">
        <table class="{{ $tableClass }}">
            <thead>
                <tr>
                    @if($checkboxes)
                        <th width="50">
                            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes(this)">
                        </th>
                    @endif
                    
                    @foreach($headers as $key => $header)
                        <th 
                            @if($sortable && isset($header['sortable']) && $header['sortable'])
                                class="sortable" 
                                data-sort="{{ $key }}"
                                style="cursor: pointer;"
                            @endif
                        >
                            {{ $header['label'] ?? $header }}
                            @if($sortable && isset($header['sortable']) && $header['sortable'])
                                <i class="fas fa-sort ms-1"></i>
                            @endif
                        </th>
                    @endforeach
                    
                    @if(count($actions) > 0)
                        <th width="150">Hành động</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        @if($checkboxes)
                            <td>
                                <input type="checkbox" class="row-checkbox" value="{{ $row['id'] ?? $loop->index }}">
                            </td>
                        @endif
                        
                        @foreach($headers as $key => $header)
                            <td>
                                @if(isset($header['render']))
                                    {!! $header['render']($row) !!}
                                @else
                                    {{ data_get($row, $key) }}
                                @endif
                            </td>
                        @endforeach
                        
                        @if(count($actions) > 0)
                            <td>
                                <div class="btn-group" role="group">
                                    @foreach($actions as $action)
                                        @if(isset($action['condition']) && !$action['condition']($row))
                                            @continue
                                        @endif
                                        
                                        <a 
                                            href="{{ $action['url']($row) }}" 
                                            class="btn btn-sm {{ $action['class'] ?? 'btn-outline-primary' }}"
                                            @if(isset($action['confirm']))
                                                onclick="return confirm('{{ $action['confirm'] }}')"
                                            @endif
                                            @if(isset($action['target']))
                                                target="{{ $action['target'] }}"
                                            @endif
                                        >
                                            @if(isset($action['icon']))
                                                <i class="{{ $action['icon'] }}"></i>
                                            @endif
                                            {{ $action['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) + ($checkboxes ? 1 : 0) + (count($actions) > 0 ? 1 : 0) }}" class="text-center py-4">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($pagination)
        <div class="d-flex justify-content-center mt-3">
            {{ $pagination->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
// Table search functionality
document.getElementById('tableSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Checkbox functionality
function toggleAllCheckboxes(selectAll) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    toggleBulkActions();
}

function toggleBulkActions() {
    const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
}

// Add event listeners to row checkboxes
document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('change', toggleBulkActions);
});

function executeBulkAction() {
    const action = document.getElementById('bulkActionSelect').value;
    const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (!action || ids.length === 0) {
        alert('Vui lòng chọn hành động và ít nhất một mục');
        return;
    }
    
    if (confirm(`Bạn có chắc muốn thực hiện hành động này cho ${ids.length} mục?`)) {
        // Emit custom event for parent component to handle
        window.dispatchEvent(new CustomEvent('bulkAction', {
            detail: { action, ids }
        }));
    }
}
</script>
@endpush
