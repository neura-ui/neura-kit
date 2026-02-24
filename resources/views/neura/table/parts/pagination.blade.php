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
            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 tabular-nums">
                @if ($paginator->firstItem())
                    <span class="text-neutral-600 dark:text-neutral-300">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                    {{ neura_trans('of') }}
                    <span class="text-neutral-600 dark:text-neutral-300">{{ $paginator->total() }}</span>
                @else
                    0 {{ neura_trans('results') }}
                @endif
            </p>

            <div class="flex items-center gap-0.5">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <button disabled class="inline-flex items-center justify-center size-7 rounded-md text-neutral-300 dark:text-neutral-600 cursor-not-allowed">
                        <neura::icon name="chevron-left" class="size-3.5" variant="mini" />
                    </button>
                @else
                    <button wire:click="previousPage" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center size-7 rounded-md text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/[0.06] hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors duration-100">
                        <neura::icon name="chevron-left" class="size-3.5" variant="mini" />
                    </button>
                @endif

                {{-- Pages --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center justify-center size-7 text-[11px] text-neutral-300 dark:text-neutral-600">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <button disabled
                                    class="inline-flex items-center justify-center size-7 rounded-md text-[11px] font-medium bg-neutral-900 dark:bg-white/[0.12] text-white dark:text-neutral-100 tabular-nums">
                                    {{ $page }}
                                </button>
                            @else
                                <button wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center size-7 rounded-md text-[11px] font-medium text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/[0.06] hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors duration-100 tabular-nums">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button wire:click="nextPage" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center size-7 rounded-md text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/[0.06] hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors duration-100">
                        <neura::icon name="chevron-right" class="size-3.5" variant="mini" />
                    </button>
                @else
                    <button disabled class="inline-flex items-center justify-center size-7 rounded-md text-neutral-300 dark:text-neutral-600 cursor-not-allowed">
                        <neura::icon name="chevron-right" class="size-3.5" variant="mini" />
                    </button>
                @endif
            </div>
        </div>
    </nav>
@endif
