@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">&lsaquo; Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">&lsaquo; Prev</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span aria-disabled="true">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active-page" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">Next &rsaquo;</a>
        @else
            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">Next &rsaquo;</span>
        @endif

    </nav>
@endif
