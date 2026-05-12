@if ($paginator->hasPages())
    <nav class="ut-pagination ut-pagination--simple" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="ut-pagination__list ut-pagination__list--simple">
            @if ($paginator->onFirstPage())
                <li><span class="ut-pagination__btn ut-pagination__btn--disabled">{!! __('pagination.previous') !!}</span></li>
            @else
                <li><a class="ut-pagination__btn ut-pagination__btn--link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{!! __('pagination.previous') !!}</a></li>
            @endif

            @if ($paginator->hasMorePages())
                <li><a class="ut-pagination__btn ut-pagination__btn--link" href="{{ $paginator->nextPageUrl() }}" rel="next">{!! __('pagination.next') !!}</a></li>
            @else
                <li><span class="ut-pagination__btn ut-pagination__btn--disabled">{!! __('pagination.next') !!}</span></li>
            @endif
        </ul>
    </nav>
@endif
