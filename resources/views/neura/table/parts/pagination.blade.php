@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ neura_trans('paginationNavigation') }}" class="flex items-center justify-between">
        {{-- Mobile --}}
        <div class="flex justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <neura::button variant="outline" disabled size="sm" class="w-full justify-center">
                    {!! __('pagination.previous') !!}
                </neura::button>
            @else
                <neura::button variant="outline" size="sm" wire:click="previousPage" wire:loading.attr="disabled" class="w-full justify-center">
                    {!! __('pagination.previous') !!}
                </neura::button>
            @endif

            @if ($paginator->hasMorePages())
                <neura::button variant="outline" size="sm" wire:click="nextPage" wire:loading.attr="disabled" class="w-full justify-center">
                    {!! __('pagination.next') !!}
                </neura::button>
            @else
                <neura::button variant="outline" disabled size="sm" class="w-full justify-center">
                    {!! __('pagination.next') !!}
                </neura::button>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="nk-table-meta">
                @if ($paginator->firstItem())
                    <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
                    {{ neura_trans('of') }}
                    <strong>{{ $paginator->total() }}</strong>
                @else
                    0 {{ neura_trans('results') }}
                @endif
            </p>

            <div class="flex items-center gap-0.5">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <button type="button" disabled class="nk-table-iconbtn" aria-label="Previous page">
                        <neura::icon name="chevron-left" class="size-3.5" variant="mini" />
                    </button>
                @else
                    <button type="button" wire:click="previousPage" wire:loading.attr="disabled" class="nk-table-iconbtn" aria-label="Previous page">
                        <neura::icon name="chevron-left" class="size-3.5" variant="mini" />
                    </button>
                @endif

                {{-- Pages --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="nk-table-page" style="cursor: default; color: var(--nk-fg-disabled)">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <button type="button" disabled class="nk-table-page is-current" aria-current="page">
                                    {{ $page }}
                                </button>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled" class="nk-table-page">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage" wire:loading.attr="disabled" class="nk-table-iconbtn" aria-label="Next page">
                        <neura::icon name="chevron-right" class="size-3.5" variant="mini" />
                    </button>
                @else
                    <button type="button" disabled class="nk-table-iconbtn" aria-label="Next page">
                        <neura::icon name="chevron-right" class="size-3.5" variant="mini" />
                    </button>
                @endif
            </div>
        </div>
    </nav>
@endif
