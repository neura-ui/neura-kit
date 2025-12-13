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

<div class="flex items-center justify-center gap-2">
    <div
        class="w-6 h-6 rounded border border-neutral-200 dark:border-neutral-700 shrink-0"
        style="background-color: {{ $displayColor }}"
    ></div>
</div>
