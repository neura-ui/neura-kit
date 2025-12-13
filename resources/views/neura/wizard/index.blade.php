@props([
    'steps' => [],
    'orientation' => 'horizontal',
])

<div
    {{ $attributes->merge(['class' => $orientation === 'vertical' ? 'flex w-full min-h-[600px]' : 'w-full']) }}
>
    {{ $slot }}
</div>
