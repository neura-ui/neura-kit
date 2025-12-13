@props([
    'separator' => null,
    'iconVariant' => 'mini',
    'icon' => null,
    'href' => null,
])

@php
    $classes = ['group/breadcrumbs flex items-center gap-x-0.5'];

    $linkClasses = [
        'text-black dark:text-white text-sm flex items-center gap-x-1'
    ];

    $staticTextClasses = [
        'dark:text-neutral-300 text-sm flex items-center gap-x-1'
    ];

    $iconClasses = [
        'size-5' => $iconVariant === 'outline'
    ];
@endphp

<div class="{{ Arr::toCssClasses($classes) }}">
    @if ($href)
        <a href="{{ $href }}" {{ $attributes->class(Arr::toCssClasses($linkClasses)) }}>
            @if ($icon)
                <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}"
                    class="{{ Arr::toCssClasses($iconClasses) }}" />
            @endif
            {{ $slot }}
        </a>
    @else
        <div {{ $attributes->class(Arr::toCssClasses($staticTextClasses)) }}>
            @if ($icon)
                <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}"
                    class="{{ Arr::toCssClasses($iconClasses) }}" />
            @endif
            {{ $slot }}
        </div>
    @endif

    @if ($separator == null)
        <neura::icon name="chevron-right" variant="mini" class="group-last/breadcrumbs:hidden rtl:hidden" />
        <neura::icon name="chevron-left" variant="mini" class="group-last/breadcrumbs:hidden hidden rtl:inline" />
    @elseif (!is_string($separator))
        {{ $separator }}
    @elseif ($separator === 'slash')
        <neura::icon name="slash" variant="mini" class="group-last/breadcrumbs:hidden rtl:-scale-x-100" />
    @else
        <neura::icon :name="$separator" variant="mini" class="group-last/breadcrumbs:hidden" />
    @endif
</div>
