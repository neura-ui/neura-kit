@props([
    'icon' => null,
    'iconVariant' => 'outline',
    'color' => neura_config('icon-stack', 'color'),
    'size' => neura_config('icon-stack', 'size'),
    'badge' => null,
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;

    $sizeConfig = PackResolver::iconStackSize($size);
    $colorConfig = PackResolver::iconStackColor($color);

    // The frame color drives currentColor on the container so it is inherited
    // by the panel strokes and the ground shadow.
    $containerClasses = Arr::toCssClasses([
        'relative inline-block',
        $sizeConfig['container'] ?? 'h-20 w-18',
        $colorConfig['frame'] ?? 'text-fg',
        $attributes->get('class'),
    ]);

    $contentColorClass = $colorConfig['content'] ?? 'text-fg-muted';

    $iconClasses = Arr::toCssClasses([
        $sizeConfig['icon'] ?? 'size-6',
        $attributes->get('icon:class'),
    ]);

    // Offset isometric panels, back → front. Increasing opacity brings the
    // front panel forward; the last one is "active" (slightly stronger edge).
    $layers = [
        ['opacity' => '0.4', 'x' => 0, 'y' => 0, 'active' => false],
        ['opacity' => '0.6', 'x' => 13.65, 'y' => 6.04, 'active' => false],
        ['opacity' => '0.8', 'x' => 27.32, 'y' => 12.08, 'active' => true],
    ];

    $hasContent = filled($icon) || $slot->isNotEmpty();
@endphp

<div {{ $attributes->except(['class', 'icon:class'])->class($containerClasses) }}
    data-slot="icon-stack" data-size="{{ $size }}">

    <svg aria-hidden="true" viewBox="0 0 72 81" fill="none" class="h-full w-full overflow-visible">
        {{-- Ground shadow --}}
        <ellipse cx="36" cy="76" rx="30" ry="7" fill="currentColor" fill-opacity="0.055" class="blur-[4px]" />

        {{-- Stacked isometric panels --}}
        @foreach ($layers as $layer)
            <g opacity="{{ $layer['opacity'] }}" transform="translate({{ $layer['x'] }} {{ $layer['y'] }})">
                <path data-slot="icon-stack-layer" class="fill-surface"
                    d="M42.2538 2.046C41.4408 1.6325 40.3965 1.6677 39.2612 2.2424L7.9616 18.1934C5.3895 19.5039 3.301 23.1064 3.301 26.2322V64.3226C3.301 66.0677 3.9458 67.2943 4.962 67.8199L1.8363 66.229C0.8201 65.7104 0.1753 64.4771 0.1753 62.732V24.6412C0.1753 21.5085 2.2638 17.913 4.8359 16.6024L36.1355 0.6515C37.2778 0.0698 38.322 0.0416 39.128 0.4551L42.2538 2.046Z"
                    stroke="currentColor" stroke-opacity="{{ $layer['active'] ? '0.3' : '0.2' }}"
                    stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round" />
                <path data-slot="icon-stack-layer" class="fill-surface"
                    d="M42.2545 2.0456C43.2707 2.5643 43.9155 3.7979 43.9155 5.543V43.6337C43.9155 46.7665 41.827 50.3616 39.2549 51.6722L7.9554 67.6235C6.813 68.2052 5.7687 68.2331 4.9628 67.8196C3.9465 67.301 3.3018 66.0673 3.3018 64.3222V26.2318C3.3018 23.0991 5.3903 19.5036 7.9624 18.193L39.2619 2.2421C40.4043 1.6604 41.4486 1.6321 42.2545 2.0456Z"
                    stroke="currentColor" stroke-opacity="{{ $layer['active'] ? '0.3' : '0.2' }}"
                    stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round" />
            </g>
        @endforeach
    </svg>

    {{-- Centered content, skewed to sit on the front panel plane --}}
    @if ($hasContent)
        <div data-slot="icon-stack-content"
            class="pointer-events-none absolute top-[58%] left-[71%] flex -translate-x-1/2 -translate-y-1/2 scale-x-90 -skew-y-26 items-center justify-center {{ $contentColorClass }}">
            @if (is_string($icon))
                <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}" class="{{ $iconClasses }}" />
            @else
                <div class="{{ $iconClasses }}">{{ $slot }}</div>
            @endif
        </div>
    @endif

    {{-- Optional status badge pinned to the front panel --}}
    @if ($badge)
        <div data-slot="icon-stack-badge"
            class="absolute top-[38%] left-[84%] z-10 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center">
            {{ $badge }}
        </div>
    @endif
</div>
