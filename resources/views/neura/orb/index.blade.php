@props([
    'state' => null,
    'variant' => null,
    'size' => null,
    'speed' => null,
    'color' => null,
    'label' => null,
    'pill' => false,
])

@php
    use Neura\Kit\Support\OrbCssGeometry;
    use Neura\Kit\Support\PackResolver;

    $cssVariant = OrbCssGeometry::normalize($variant);
@endphp

@if ($cssVariant)
    <x-neura::orb.css
        :variant="$cssVariant"
        :size="$size"
        :color="$color"
        :label="$label"
        :pill="$pill"
        {{ $attributes }}
    />
@else
    @php
        $orbState = $state ?: (neura_config('orb', 'state') ?: 'searching');
        $mode = PackResolver::orbMode($orbState);
        $orbSize = $size ?: (neura_config('orb', 'size') ?: 64);
        $orbSpeed = $speed !== null ? $speed : (neura_config('orb', 'speed') ?: 1);
        $orbColor = PackResolver::orbColor($color);
    @endphp

    {{-- Canvas orb: dots follow CSS `color` (currentColor). --}}
    <canvas
        data-slot="orb"
        data-engine="canvas"
        data-nk-orb
        data-state="{{ $orbState }}"
        x-data="neuraOrb({ mode: '{{ $mode }}', speed: {{ (float) $orbSpeed }} })"
        {{ $attributes->class(['inline-block align-middle', $orbColor])->merge(['style' => "width: {$orbSize}px; height: {$orbSize}px;"]) }}
        aria-hidden="true"
    ></canvas>
@endif
