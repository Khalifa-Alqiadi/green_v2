@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="velocity-pagination">
            @if ($paginator->onFirstPage())
                <li><span class="velocity-pagination__disabled" aria-disabled="true">{!! __('pagination.previous') !!}</span></li>
            @else
                <li><a class="velocity-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{!! __('pagination.previous') !!}</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="velocity-pagination__disabled" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="velocity-pagination__current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="velocity-pagination__link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a class="velocity-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">{!! __('pagination.next') !!}</a></li>
            @else
                <li><span class="velocity-pagination__disabled" aria-disabled="true">{!! __('pagination.next') !!}</span></li>
            @endif
        </ul>
    </nav>
@endif
