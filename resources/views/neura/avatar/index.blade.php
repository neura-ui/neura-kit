@props([
    'iconVariant' => 'outline',
    'initials' => null,
    'circle' => null,
    'color' => neura_config('avatar', 'color'),
    'badge' => null,
    'name' => null,
    'icon' => null,
    'size' => neura_config('avatar', 'size'),
    'src' => null,
    'href' => null,
    'alt' => null,
    'as' => 'div',
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Packs\Avatar\Color as AvatarColors;

    // Generate initials from name
    if ($name && !$initials) {
        $parts = explode(' ', trim($name));

        if ($attributes->has('initials:single')) {
            $initials = strtoupper(mb_substr($parts[0], 0, 1));
        } else {
            $parts = collect($parts)->filter()->values()->all();

            if (count($parts) > 1) {
                $initials = strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
            } elseif (count($parts) === 1) {
                $initials = strtoupper(mb_substr($parts[0], 0, 1)) . strtolower(mb_substr($parts[0], 1, 1));
            }
        }
    }

    $hasTextContent = $icon ?? ($initials ?? $slot->isNotEmpty());

    if (!$hasTextContent) {
        $icon = 'user';
    }

    // Auto color handling
    $autoColors = AvatarColors::autoColors();

    if ($color === 'auto') {
        $colorSeed = $attributes->get('color:seed') ?? ($name ?? ($icon ?? ($initials ?? $slot)));
        $hash = crc32((string) $colorSeed);
        $color = $autoColors[$hash % count($autoColors)];
    }

    // Size configuration
    $sizeConfig = PackResolver::avatarSize($size ?? 'md');

    $avatarRadius = $circle
        ? '[--avatar-radius:calc(infinity*1px)]'
        : $sizeConfig['radius'] ?? '[--avatar-radius:var(--radius-lg)]';

    $avatarSize = $sizeConfig['container'] ?? '[:where(&)]:size-10 [:where(&)]:text-sm';
    $avatarColor = PackResolver::avatarColor($color ?? 'neutral');

    $containerClasses = Arr::toCssClasses([
        'relative flex items-center justify-center rounded-(--avatar-radius) overflow-hidden',
        'shadow-sm transition-all duration-200',
        'ring-1 ring-black/5 dark:ring-white/10',
        'hover:shadow-md hover:ring-black/10 dark:hover:ring-white/15' => $href,
        $avatarRadius,
        $avatarSize,
        $avatarColor,
        $attributes->get('class'),
    ]);

    // Icon styling - inherits avatar color by default
    $iconClasses = Arr::toCssClasses([
        'text-current opacity-90',
        $sizeConfig['icon'] ?? 'size-6',
        $attributes->get('icon:class'),
    ]);

    // Slot styling for initials/text content
    $slotClasses = Arr::toCssClasses(['select-none font-semibold tracking-tight', $attributes->get('slot:class')]);

    // Badge configuration
    $badgeColor = $attributes->get('badge:color') ?? (is_object($badge) ? $badge?->attributes?->get('color') : null);
    $badgeCircle = $attributes->get('badge:circle') ?? (is_object($badge) ? $badge?->attributes?->get('circle') : null);
    $badgePill = !is_null($badgeCircle) ? (bool) $badgeCircle : true;
    $badgePosition =
        $attributes->get('badge:position') ?? (is_object($badge) ? $badge?->attributes?->get('position') : null);
    $badgeVariant =
        $attributes->get('badge:variant') ??
        ((is_object($badge) ? $badge?->attributes?->get('variant') : null) ?? 'solid');

    $badgePositionClass = match ($badgePosition) {
        'top left' => '-top-1 -left-1',
        'top right' => '-top-1 -right-1',
        'bottom left' => '-bottom-1 -left-1',
        'bottom right' => '-bottom-1 -right-1',
        default => '-bottom-1 -right-1',
    };

    $badgeColorClass = PackResolver::badgeColor($badgeColor ?? 'secondary', $badgeVariant);
    $badgeSizeClass = PackResolver::badgeSize('xs', true);
    $badgeRoundedClass = 'rounded-full';

    $badgeClasses = Arr::toCssClasses([
        'absolute z-10',
        'ring-2 ring-white dark:ring-neutral-900',
        'transition-transform duration-200',
        'flex items-center justify-center tabular-nums',
        $badgePositionClass,
        $badgeColorClass,
        $badgeSizeClass,
        $badgeRoundedClass,
        $attributes->get('badge:class'),
    ]);
@endphp

<div class="relative inline-flex" data-slot="avatar" data-size="{{ $size }}">
    <neura::button.abstract :href="$href" :as="$as"
        {{ $attributes->except(['class', 'icon:class', 'slot:class', 'badge:class', 'badge:color', 'badge:circle', 'badge:position', 'badge:variant', 'color:seed', 'initials:single'])->class($containerClasses) }}>

        {{-- Image content --}}
        @if ($src)
            <img src="{{ $src }}" alt="{{ $alt || $name }}" class="object-cover h-full w-full" />
            {{-- Icon from name --}}
        @elseif (is_string($icon))
            <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}" class="{{ $iconClasses }}" />
            {{-- SVG Icon slot --}}
        @elseif ($slot->isNotEmpty())
            <div class="{{ $iconClasses }}">
                {{ $slot }}
            </div>
            {{-- Initials as fallback --}}
        @else
            <span class="{{ $slotClasses }}">{{ $initials }}</span>
        @endif
    </neura::button.abstract>

    {{-- Badge --}}
    @if ($badge instanceof \Illuminate\View\ComponentSlot)
        <div {{ $badge->attributes->except(['class', 'color', 'circle', 'position', 'variant'])->class($badgeClasses) }}
            aria-hidden="true">
            {{ $badge }}
        </div>
    @elseif ($badge)
        <div class="{{ $badgeClasses }}" aria-hidden="true">
            {{ is_string($badge) ? $badge : '' }}
        </div>
    @endif
</div>
