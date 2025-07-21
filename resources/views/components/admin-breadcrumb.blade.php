@props(['items' => []])

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-white p-3 rounded shadow-sm border">
        <!-- Dashboard Link (always first) -->
        <li class="breadcrumb-item">
            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-secondary">
                Bảng điều khiển
            </a>
        </li>

        <!-- Dynamic breadcrumb items -->
        @foreach($items as $index => $item)
            @if($loop->last)
                <!-- Last item (current page) -->
                <li class="breadcrumb-item active text-dark" aria-current="page">
                    {{ $item['title'] }}
                    @if(isset($item['badge']))
                        <span class="badge badge-light border ms-2">{{ $item['badge'] }}</span>
                    @endif
                </li>
            @else
                <!-- Intermediate items (links) -->
                <li class="breadcrumb-item">
                    <a href="{{ $item['url'] }}" class="text-decoration-none text-secondary">
                        {{ $item['title'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ol>
</nav>

@push('styles')
<style>
    /* Breadcrumb Styling */
    .breadcrumb {
        background: #ffffff;
        border: 1px solid #dee2e6;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: #6c757d;
        font-weight: bold;
    }

    .breadcrumb-item a {
        transition: all 0.3s ease;
        color: #6c757d;
    }

    .breadcrumb-item a:hover {
        color: #495057 !important;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .breadcrumb-item.active {
        font-weight: 600;
        color: #495057;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .breadcrumb {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .breadcrumb-item {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
</style>
@endpush
