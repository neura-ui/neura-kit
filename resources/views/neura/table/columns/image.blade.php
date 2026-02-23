@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $src = $value ?? $extraAttributes['src'] ?? '';
    $alt = $extraAttributes['alt'] ?? '';
    $width = $extraAttributes['width'] ?? 32;
    $height = $extraAttributes['height'] ?? 32;
    $rounded = $extraAttributes['rounded'] ?? true;
@endphp

<div class="flex items-center">
    @if($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="lazy"
            @class([
                'object-cover',
                'rounded-full' => $rounded === 'full',
                'rounded-md' => $rounded === true && $rounded !== 'full',
            ])
        />
    @else
        <div
            class="bg-neutral-100 dark:bg-white/[0.06] flex items-center justify-center text-neutral-400 dark:text-neutral-500"
            style="width: {{ $width }}px; height: {{ $height }}px;"
            @class([
                'rounded-full' => $rounded === 'full',
                'rounded-md' => $rounded === true && $rounded !== 'full',
            ])
        >
            <neura::icon name="photo" class="size-4" />
        </div>
    @endif
</div>
