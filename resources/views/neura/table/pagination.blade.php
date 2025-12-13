
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ neura_trans('paginationNavigation') }}" class="flex items-center justify-between w-full">
        <div class="flex justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <neura::button variant="outline" disabled class="w-full justify-center">
                    {!! __('pagination.previous') !!}
                </neura::button>
            @else
                <neura::button variant="outline" wire:click="previousPage" wire:loading.attr="disabled" class="w-full justify-center">
                    {!! __('pagination.previous') !!}
                </neura::button>
            @endif

            @if ($paginator->hasMorePages())
                <neura::button variant="outline" wire:click="nextPage" wire:loading.attr="disabled" class="w-full justify-center">
                    {!! __('pagination.next') !!}
                </neura::button>
            @else
                <neura::button variant="outline" disabled class="w-full justify-center">
                    {!! __('pagination.next') !!}
                </neura::button>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-neutral-700 dark:text-neutral-400">
                    {{ neura_trans('showing') }}
                        @if ($paginator->firstItem())
                        <span class="font-medium text-neutral-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                        {{ neura_trans('to') }}
                        <span class="font-medium text-neutral-900 dark:text-white">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {{ neura_trans('of') }}
                    <span class="font-medium text-neutral-900 dark:text-white">{{ $paginator->total() }}</span>
                    {{ neura_trans('results') }}
                </p>
            </div>

            <div>
                <div class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <neura::button variant="outline" disabled class="rounded-r-none px-2.5" icon="chevron-left" iconVariant="mini" />
                    @else
                        <neura::button variant="outline" wire:click="previousPage" wire:loading.attr="disabled" class="rounded-r-none px-2.5" icon="chevron-left" iconVariant="mini" />
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-neutral-700 bg-white border border-neutral-300 cursor-default leading-5 dark:bg-neutral-950 dark:border-neutral-800 dark:text-neutral-400">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <neura::button variant="outline" class="rounded-none -ml-px bg-neutral-50 dark:bg-neutral-800 text-neutral-900 dark:text-white ring-1 ring-neutral-950/5 dark:ring-white/10 z-10" disabled>
                                        {{ $page }}
                                    </neura::button>
                                @else
                                    <neura::button variant="outline" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled" class="rounded-none -ml-px bg-white dark:bg-neutral-950 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                        {{ $page }}
                                    </neura::button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <neura::button variant="outline" wire:click="nextPage" wire:loading.attr="disabled" class="rounded-l-none -ml-px px-2.5" icon="chevron-right" iconVariant="mini" />
                    @else
                        <neura::button variant="outline" disabled class="rounded-l-none -ml-px px-2.5" icon="chevron-right" iconVariant="mini" />
                    @endif
                </div>
            </div>
        </div>
    </nav>
@endif
