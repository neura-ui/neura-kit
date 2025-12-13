@props([
    'value' => null,
    'variant' => 'line',
])

@php
    $baseClasses = 'inline-flex items-center justify-center whitespace-nowrap text-sm font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 dark:focus-visible:ring-neutral-300';

    $variantClasses = match($variant) {
        'pills' => 'px-3 py-1.5 rounded-sm data-[state=active]:bg-white data-[state=active]:text-neutral-950 data-[state=active]:shadow-sm dark:data-[state=active]:bg-neutral-950 dark:data-[state=active]:text-neutral-50',
        'line' => 'px-3 py-1.5 border-b-2 border-transparent data-[state=active]:border-neutral-900 data-[state=active]:text-neutral-900 dark:data-[state=active]:border-neutral-50 dark:data-[state=active]:text-neutral-50 text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-50 -mb-[2px]',
        default => 'px-3 py-1.5 border-b-2 border-transparent data-[state=active]:border-neutral-900 data-[state=active]:text-neutral-900 dark:data-[state=active]:border-neutral-50 dark:data-[state=active]:text-neutral-50 text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-50 -mb-[2px]',
    };
@endphp

<button
    type="button"
    role="tab"
    x-on:click="activeTab = @js($value)"
    :data-state="activeTab === @js($value) ? 'active' : 'inactive'"
    :aria-selected="activeTab === @js($value)"
    data-tab-trigger="{{ $value }}"
    {{ $attributes->class([$baseClasses, $variantClasses]) }}
>
    {{ $slot }}
</button>
