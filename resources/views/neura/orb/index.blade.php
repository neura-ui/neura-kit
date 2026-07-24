@props([
    'state' => null,
    'size' => null,
    'speed' => null,
    'color' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $orbState = $state ?: (neura_config('orb', 'state') ?: 'searching');
    $mode = PackResolver::orbMode($orbState);
    $orbSize = $size ?: (neura_config('orb', 'size') ?: 64);
    $orbSpeed = $speed !== null ? $speed : (neura_config('orb', 'speed') ?: 1);
    $orbColor = PackResolver::orbColor($color);
@endphp

{{-- Dots follow CSS `color` (currentColor). Use the `color` prop or any text-* utility. --}}
<canvas
    data-slot="orb"
    data-nk-orb
    data-state="{{ $orbState }}"
    x-data="neuraOrb({ mode: '{{ $mode }}', speed: {{ (float) $orbSpeed }} })"
    x-init="init()"
    {{ $attributes->class(['inline-block align-middle', $orbColor])->merge(['style' => "width: {$orbSize}px; height: {$orbSize}px;"]) }}
    aria-hidden="true"
></canvas>
