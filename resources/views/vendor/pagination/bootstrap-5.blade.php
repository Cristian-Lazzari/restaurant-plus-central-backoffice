@if ($paginator->hasPages())
    {{-- Section: Pagination — admin dark theme --}}
    <nav class="admin-pager" role="navigation" aria-label="{{ __('pagination.aria_label', [], null) ?: 'Navigazione pagine' }}">

        {{-- Info risultati (solo desktop, quando il totale è disponibile) --}}
        @if (method_exists($paginator, 'total'))
            <p class="admin-pager__info" aria-live="polite">
                {{ __('pagination.showing') ?: 'Risultati' }}
                <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
                {{ __('pagination.of') ?: 'di' }}
                <strong>{{ $paginator->total() }}</strong>
            </p>
        @endif

        {{-- Controlli navigazione --}}
        <div class="admin-pager__controls">

            {{-- ← Precedente --}}
            @if ($paginator->onFirstPage())
                <span class="admin-pager__btn admin-pager__btn--disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </span>
            @else
                <a class="admin-pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
            @endif

            {{-- Numeri pagina (nascosti su schermi piccoli) --}}
            <div class="admin-pager__pages" role="list">
                @foreach ($elements as $element)
                    {{-- Separatore "..." --}}
                    @if (is_string($element))
                        <span class="admin-pager__btn admin-pager__btn--dots" aria-hidden="true">{{ $element }}</span>
                    @endif

                    {{-- Gruppo di link --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="admin-pager__btn admin-pager__btn--active" aria-current="page" role="listitem">{{ $page }}</span>
                            @else
                                <a class="admin-pager__btn" href="{{ $url }}" role="listitem">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- → Successivo --}}
            @if ($paginator->hasMorePages())
                <a class="admin-pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="admin-pager__btn admin-pager__btn--disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </span>
            @endif

        </div>
    </nav>
@endif
