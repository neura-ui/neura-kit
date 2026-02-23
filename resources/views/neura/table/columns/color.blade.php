@props([
    'value',
    'row' => null,
    'column' => null,
])

@php
    $color = $value ?? '#000000';

    if (empty($color) || !is_string($color)) {
        $color = '#000000';
    }

    $color = trim($color);
    $isHex = str_starts_with($color, '#');
    $displayColor = $isHex ? $color : '#' . $color;

    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $displayColor) && !preg_match('/^#[0-9A-Fa-f]{3}$/', $displayColor)) {
        $displayColor = '#000000';
    }
@endphp

<div class="flex items-center gap-2">
    <div
        class="size-5 rounded-md border border-neutral-200 dark:border-white/[0.1] shrink-0 shadow-xs"
        style="background-color: {{ $displayColor }}"
    ></div>
    <span class="text-[13px] text-neutral-500 dark:text-neutral-400 font-mono">{{ $displayColor }}</span>
</div>
