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

    $classes = [
        'isolate',
        'flex items-center [:where(&)]:justify-start',

        '[:has([data-collapsed]_&)_&]:justify-center',

        'data-active-link:bg-primary-50 dark:data-active-link:bg-primary-950/50
         data-active-link:!text-primary-600 dark:data-active-link:!text-primary-400
         data-active-link:[&_[data-slot=icon]]:!text-primary-600 dark:data-active-link:[&_[data-slot=icon]]:!text-primary-400',

        '[&:not([data-active-link])]:hover:bg-primary-50 dark:[&:not([data-active-link])]:hover:bg-primary-950/50
        [&:not([data-active-link])]:hover:!text-primary-600 dark:[&:not([data-active-link])]:hover:!text-primary-400
        [&:not([data-active-link])]:hover:[&_[data-slot=icon]]:!text-primary-600 dark:[&:not([data-active-link])]:hover:[&_[data-slot=icon]]:!text-primary-400',

        'dark:text-neutral-400 text-neutral-600',

        '[&_[data-slot=icon]]:dark:text-neutral-400
         [&_[data-slot=icon]]:text-neutral-600
         data-[active-link]:text-primary-600 dark:data-[active-link]:text-primary-400',

        'gap-x-2 pl-3 pr-1 py-1 rounded-box',

        '[:has([data-collapsed]_&)_&]:p-2',
    ];

    $iconAttributes = new \Illuminate\View\ComponentAttributeBag();
    $badgeAttributes = new \Illuminate\View\ComponentAttributeBag();

    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'icon:')) {
            $iconAttributes[substr($key, 5)] = $value;
        } elseif (str_starts_with($key, 'badge:')) {
            $badgeAttributes[substr($key, 6)] = $value;
        }
    }

    $active = $active ?? (url($href) === url()->current());

@endphp
<a
    href="{{ $href }}"

    @if($active)
       data-active-link
    @endif

    data-slot="navlist-item"
    {{ $attributes->class($classes) }}
>
    @if($icon)
        <neura::navlist.has-tooltip
            :tooltip="$label"
            :condition="$collapsible"
        >
            <neura::icon
                :attributes="$iconAttributes->class('[:where(&)]:' . $iconSize)"
                :name="$icon"
            />
        </neura::navlist.has-tooltip>
    @endif

    <span class="{{ $textSize }} in-[:has([data-collapsed]_&)]:hidden">
        {{ $label }}
    </span>

    @if($badge)
        <neura::badge
            :attributes="$badgeAttributes->merge([
                'size' => 'sm'
            ])"
            class="in-[:has([data-collapsed]_&)]:hidden  ml-auto"
        >{{ $badge }}</neura::badge>
    @endif
</a>