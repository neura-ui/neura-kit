@props([
    'variant' => 'vertical',
])

@if ($variant === 'horizontal')
    <neura::timeline.variants.horizontal variant="horizontal">
        {{ $slot }}
    </neura::timeline.variants.horizontal>
@else
    <neura::timeline.variants.vertical variant="vertical">
        {{ $slot }}
    </neura::timeline.variants.vertical>
@endif
