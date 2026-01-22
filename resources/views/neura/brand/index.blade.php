@aware([
    'href' => '#',
    'logo' => null,
    'logoLight' => null,
    'logoDark' => null,
    'name' => '',
    'alt' => '',
    'target' => '_self',
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
    'align' => 'left',
])

@php
    use Illuminate\Support\Arr;

    /**
     * Normalize logo rendering with merged attributes
     */
    $renderLogo = function ($logo, $alt, $logoAttributes) {
        if (is_string($logo)) {
            $logoClass = Arr::toCssClasses(['h-8 w-auto', $logoAttributes]);

            return '<img src="' . e($logo) . '" alt="' . e($alt) . ' Logo" class="' . $logoClass . '">';
        }

        return $logo?->isNotEmpty() ? $logo : null;
    };

    $hasThemeLogos = $logoLight || $logoDark;
    $hasAnyLogo = $logo || $hasThemeLogos;

    $alignClass = match ($align) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-start',
    };
@endphp

<a href="{{ $href }}" target="{{ $target }}"
    {{ $attributes->merge([
        'class' => Arr::toCssClasses([
            'group',
            'flex items-center gap-x-3',
            'transition-opacity hover:opacity-80',
            'text-black dark:text-white',
            $alignClass,
            'in-[:has([data-collapsed]_&)]:justify-center',
        ]),
    ]) }}>
    {{-- Logo --}}
    @if ($hasAnyLogo)
        <div class="shrink-0">
            {{-- Light --}}
            @if ($logoLight || $logo)
                <div class="dark:hidden">
                    {!! $renderLogo($logoLight ?? $logo, $alt, $attributes->get('class', '')) !!}
                </div>
            @endif

            {{-- Dark --}}
            @if ($logoDark || $logo)
                <div class="hidden dark:block">
                    {!! $renderLogo($logoDark ?? $logo, $alt, $attributes->get('class', '')) !!}
                </div>
            @endif
        </div>
    @endif

    {{-- Brand name --}}
    @if ($name)
        <div data-slot="brand-name"
            class="{{ Arr::toCssClasses([
                'font-semibold text-lg whitespace-nowrap',
                'transition-opacity',
                'in-[:has([data-collapsed]_&)]:hidden',
            ]) }}">
            {{ $name }}
        </div>
    @endif
</a>
