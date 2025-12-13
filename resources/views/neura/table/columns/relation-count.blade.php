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
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                {{ $count }}
            </span>
        </div>

        <template x-teleport="body">
            <div
                x-show="open"
                x-anchor.right.offset.3="$refs.popoverTrigger"
                x-on:click.away="hide()"
                x-on:keydown.escape="hide()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                style="display:none; z-index: 9999;"
                class="absolute bg-white dark:bg-neutral-800 border dark:border-neutral-700 border-neutral-200 rounded-lg shadow-lg min-w-48 max-w-64 max-h-[calc(100vh-2rem)]"
            >
                <div class="p-2">
                    <div class="text-xs font-semibold text-neutral-700 dark:text-neutral-300 px-2 py-1.5 border-b border-neutral-200 dark:border-neutral-700 mb-1">
                        {{ $column->label ?? __('Items') }}
                    </div>
                    <div class="space-y-0.5">
                        @foreach($relatedItems as $item)
                            <div class="px-2 py-1.5 text-sm text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-md transition-colors">
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
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
            {{ $count }}
        </span>
    </div>
@endif
