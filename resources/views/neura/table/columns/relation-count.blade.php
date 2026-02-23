@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $relation = $extraAttributes['relation'] ?? null;
    $showPopover = $extraAttributes['showPopover'] ?? false;
    $popoverAttribute = $extraAttributes['popoverAttribute'] ?? 'name';
    $count = 0;
    $relatedItems = collect();

    if ($relation && $row) {
        $related = $row->{$relation} ?? null;
        if (is_countable($related)) {
            $count = count($related);
            $relatedItems = collect($related);
        } elseif (is_iterable($related)) {
            $count = iterator_count($related);
            $relatedItems = collect($related);
        }
    } elseif (is_countable($value)) {
        $count = count($value);
        $relatedItems = collect($value);
    } elseif (is_numeric($value)) {
        $count = (int) $value;
    }
@endphp

@if($showPopover && $count > 0)
    <div
        x-data="{
            open: false,
            toggle() { this.open = !this.open; },
            hide() { this.open = false; }
        }"
        x-on:click.away="hide()"
        x-on:keydown.escape="hide()"
        class="relative inline-block"
    >
        <div
            x-ref="popoverTrigger"
            x-on:click="toggle()"
            class="flex items-center cursor-pointer"
        >
            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-xs font-medium tabular-nums bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/15 transition-colors duration-100">
                {{ $count }}
            </span>
        </div>

        <template x-teleport="body">
            <div
                x-show="open"
                x-anchor.right.offset.3="$refs.popoverTrigger"
                x-on:click.away="hide()"
                x-on:keydown.escape="hide()"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                style="display:none; z-index: 9999;"
                class="absolute bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/[0.1] rounded-lg shadow-lg min-w-48 max-w-64 max-h-[calc(100vh-2rem)]"
            >
                <div class="p-1.5">
                    <div class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 px-2 py-1 border-b border-neutral-100 dark:border-white/[0.06] mb-1">
                        {{ $column->label ?? __('Items') }}
                    </div>
                    <div class="space-y-px">
                        @foreach($relatedItems as $item)
                            <div class="px-2 py-1.5 text-[13px] text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-white/[0.04] rounded-md transition-colors duration-75">
                                {{ data_get($item, $popoverAttribute, $item) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </template>
    </div>
@else
    <div class="flex items-center">
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-xs font-medium tabular-nums bg-neutral-100 dark:bg-white/[0.06] text-neutral-600 dark:text-neutral-400">
            {{ $count }}
        </span>
    </div>
@endif
