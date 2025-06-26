@if ($paginator->hasPages())
    <nav aria-label="Simple Pagination Navigation">
        <ul class="pagination pagination-sm m-0 float-right">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        <i class="fas fa-angle-left"></i> @lang('pagination.previous')
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="fas fa-angle-left"></i> @lang('pagination.previous')
                    </a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        @lang('pagination.next') <i class="fas fa-angle-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        @lang('pagination.next') <i class="fas fa-angle-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>

    {{-- Simple Pagination Info --}}
    <div class="float-left">
        <small class="text-muted">
            Trang {{ $paginator->currentPage() }}
        </small>
    </div>
    <div class="clearfix"></div>
@endif
