@aware([
    'href' => '#',
    'logo' => null,
    'logoLight' => null,
    'logoDark' => null,
    'name' => '',
    'alt' => '',
    'target' => '_self',
    'logoClass' => '',
    'align' => 'left', // left | center | right
])

@props([
    'href' => '#',
    'logo' => null,
    'logoLight' => null,
    'logoDark' => null,
    'name' => '',
    'alt' => '',
    'target' => '_self',
    'logoClass' => '',
    'align' => 'left',
])

@php
    /**
     * Normalize logo rendering
     */
    $renderLogo = function ($logo, $alt, $logoClass) {
        if (is_string($logo)) {
            return '<img src="' . e($logo) . '" alt="' . e($alt) . ' Logo" class="h-8 w-auto ' . e($logoClass) . '">';
        }

        return $logo?->isNotEmpty() ? $logo : null;
    };

    $hasThemeLogos = $logoLight || $logoDark;
    $hasAnyLogo = $logo || $hasThemeLogos;

    $alignClass = match ($align) {
        'center' => 'justify-center',
        'right'  => 'justify-end',
        default  => 'justify-start',
    };
@endphp

<a
    href="{{ $href }}"
    target="{{ $target }}"
    {{ $attributes->merge([
        'class' => "
            group
            flex items-center gap-x-3
            transition-opacity hover:opacity-80
            text-black dark:text-white
            {$alignClass}

            in-[:has([data-collapsed]_&)]:justify-center
        ",
    ]) }}
>
    {{-- Logo --}}
    @if ($hasAnyLogo)
        <div class="shrink-0">
            {{-- Light --}}
            @if ($logoLight || $logo)
                <div class="dark:hidden">
                    {!! $renderLogo($logoLight ?? $logo, $alt, $logoClass) !!}
                </div>
            @endif

            {{-- Dark --}}
            @if ($logoDark || $logo)
                <div class="hidden dark:block">
                    {!! $renderLogo($logoDark ?? $logo, $alt, $logoClass) !!}
                </div>
            @endif
        </div>
    @endif

    {{-- Brand name --}}
    @if ($name)
        <div
            data-slot="brand-name"
            class="
                font-semibold text-lg whitespace-nowrap
                transition-opacity

                in-[:has([data-collapsed]_&)]:hidden
            "
        >
            {{ $name }}
        </div>
    @endif
</a>
