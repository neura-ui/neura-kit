@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $src = $value ?? $extraAttributes['src'] ?? '';
    $alt = $extraAttributes['alt'] ?? '';
    $width = $extraAttributes['width'] ?? 40;
    $height = $extraAttributes['height'] ?? 40;
    $rounded = $extraAttributes['rounded'] ?? true;
@endphp

<div class="flex items-center">
    @if($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            @class([
                'object-cover',
                'rounded-full' => $rounded === 'full',
                'rounded-lg' => $rounded === true && $rounded !== 'full',
            ])
        />
    @else
        <div
            class="bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center"
            style="width: {{ $width }}px; height: {{ $height }}px;"
            @class([
                'rounded-full' => $rounded === 'full',
                'rounded-lg' => $rounded === true && $rounded !== 'full',
            ])
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    @endif
</div>

