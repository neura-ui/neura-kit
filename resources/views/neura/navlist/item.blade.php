@aware([
    'collapsible' => true,
    'size' => 'md'
])

@props([
    'icon' => null,
    'badge' => null,
    'label' => null,
    'href' => '#',
    'active' => null,
    'size' => null
])

@php
    $size = $size ?? $attributes->get('size') ?? 'md';

    $textSize = match ($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
        'xl' => 'text-xl',
        default => 'text-base',
    };

    $iconSize = match ($size) {
        'xs' => 'size-4',
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-6',
        'xl' => 'size-7',
        default => 'size-5',
    };

    $active = $active ?? (url($href) === url()->current());

    $classes = [
        'cursor-pointer',
        // layout
        'relative isolate flex items-center gap-x-2',
        'w-full px-3 py-1.5 ml-1',

        // typography
        'rounded-xl transition-colors duration-150',
        'text-neutral-600 dark:text-neutral-400',

        
        // default hover
        '[&:not([data-active-link])]:hover:bg-primary-50',
        'dark:[&:not([data-active-link])]:hover:bg-primary-950/50',
        '[&:not([data-active-link])]:hover:text-primary-600',
        'dark:[&:not([data-active-link])]:hover:text-primary-400',

        // active state (pill)
        'data-active-link:bg-white dark:data-active-link:bg-neutral-900',
        'data-active-link:text-primary-600 dark:data-active-link:text-primary-400',
        'data-active-link:shadow-sm',

        // icon coloring
        '[&_[data-slot=icon]]:text-neutral-500',
        'dark:[&_[data-slot=icon]]:text-neutral-400',
        'data-active-link:[&_[data-slot=icon]]:text-primary-600',
        'dark:data-active-link:[&_[data-slot=icon]]:text-primary-400',

        // collapsed sidebar behavior
        '[:has([data-collapsed]_&)_&]:justify-center',
    ];
@endphp

<a
    href="{{ $href }}"
    data-slot="navlist-item"
    @if ($active) data-active-link @endif
    {{ $attributes->class($classes) }}
>
    @if ($icon)
        <neura::navlist.has-tooltip
            :tooltip="$label"
            :condition="$collapsible"
        >
            <neura::icon
                :name="$icon"
                :attributes="(new Illuminate\View\ComponentAttributeBag())
                    ->class('size-5')"
            />
        </neura::navlist.has-tooltip>
    @endif

    <span class="{{ $textSize }} flex-1 in-[:has([data-collapsed]_&)]:hidden">
        {{ $label }}
    </span>

    @if ($badge)
        <neura::badge
            size="sm"
            class="ml-auto in-[:has([data-collapsed]_&)]:hidden"
        >
            {{ $badge }}
        </neura::badge>
    @endif
</a>
