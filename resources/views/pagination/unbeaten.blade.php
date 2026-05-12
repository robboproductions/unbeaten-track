@if ($paginator->hasPages())
    <nav class="ut-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="ut-pagination__mobile">
            <ul class="ut-pagination__list ut-pagination__list--mobile">
                @if ($paginator->onFirstPage())
                    <li><span class="ut-pagination__btn ut-pagination__btn--disabled">@lang('pagination.previous')</span></li>
                @else
                    <li><a class="ut-pagination__btn ut-pagination__btn--link" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a></li>
                @endif
                @if ($paginator->hasMorePages())
                    <li><a class="ut-pagination__btn ut-pagination__btn--link" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a></li>
                @else
                    <li><span class="ut-pagination__btn ut-pagination__btn--disabled">@lang('pagination.next')</span></li>
                @endif
            </ul>
        </div>

        <div class="ut-pagination__desktop">
            <p class="ut-pagination__meta">
                @lang('Showing')
                @if ($paginator->firstItem())
                    <strong>{{ $paginator->firstItem() }}</strong>
                    @lang('to')
                    <strong>{{ $paginator->lastItem() }}</strong>
                @else
                    {{ $paginator->count() }}
                @endif
                @lang('of')
                <strong>{{ $paginator->total() }}</strong>
                @lang('results')
            </p>

            <ul class="ut-pagination__list">
                @if ($paginator->onFirstPage())
                    <li><span class="ut-pagination__btn ut-pagination__btn--disabled ut-pagination__btn--edge" aria-hidden="true" title="@lang('pagination.previous')">‹</span></li>
                @else
                    <li><a class="ut-pagination__btn ut-pagination__btn--link ut-pagination__btn--edge" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">‹</a></li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="ut-pagination__btn ut-pagination__btn--ellipsis">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li><span class="ut-pagination__btn ut-pagination__btn--current" aria-current="page">{{ $page }}</span></li>
                            @else
                                <li><a class="ut-pagination__btn ut-pagination__btn--link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li><a class="ut-pagination__btn ut-pagination__btn--link ut-pagination__btn--edge" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">›</a></li>
                @else
                    <li><span class="ut-pagination__btn ut-pagination__btn--disabled ut-pagination__btn--edge" aria-hidden="true" title="@lang('pagination.next')">›</span></li>
                @endif
            </ul>
        </div>
    </nav>
@endif
