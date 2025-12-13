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
    'class' => '',
    'badgeClass' => ''
])

@php
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Packs\Avatar\Color as AvatarColors;

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
        $hasTextContent = true;
    }

    $autoColors = AvatarColors::autoColors();

    if ($hasTextContent && $color === 'auto') {
        $colorSeed = $attributes->get('color:seed') ?? ($name ?? ($icon ?? ($initials ?? $slot)));
        $hash = crc32((string) $colorSeed);
        $color = $autoColors[$hash % count($autoColors)];
    }

    $sizeConfig = PackResolver::avatarSize($size ?? 'md');

    $avatarRadius = $circle
        ? '[--avatar-radius:calc(infinity*1px)]'
        : ($sizeConfig['radius'] ?? '[--avatar-radius:var(--radius-lg)]');

    $avatarSize = $sizeConfig['container'] ?? '[:where(&)]:size-10 [:where(&)]:text-sm';
    $avatarColor = PackResolver::avatarColor($color ?? 'neutral');

    $classes = [
        'flex items-center justify-center rounded-(--avatar-radius) overflow-hidden',
        $avatarRadius,
        $avatarSize,
        $avatarColor,
        $class
    ];

    $iconClasses = [
        'text-black!',
        $sizeConfig['icon'] ?? 'size-6',
    ];

    $badgeColor = $attributes->get('badge:color') ?: (is_object($badge) ? $badge?->attributes?->get('color') : null);
    $badgeCircle = $attributes->get('badge:circle') ?: (is_object($badge) ? $badge?->attributes?->get('circle') : null);
    $badgePosition =
        $attributes->get('badge:position') ?: (is_object($badge) ? $badge?->attributes?->get('position') : null);
    $badgeVariant =
        $attributes->get('badge:variant') ?: (is_object($badge) ? $badge?->attributes?->get('variant') : null);

    $badgeSize = $sizeConfig['badge'] ?? 'h-3 min-w-3';
    $badgeColorClass = match ($badgeColor) {
        'red' => 'bg-red-500 dark:bg-red-400',
        'orange' => 'bg-orange-500 dark:bg-orange-400',
        'amber' => 'bg-amber-500 dark:bg-amber-400',
        'yellow' => 'bg-yellow-500 dark:bg-yellow-400',
        'lime' => 'bg-lime-500 dark:bg-lime-400',
        'green' => 'bg-green-500 dark:bg-green-400',
        'emerald' => 'bg-emerald-500 dark:bg-emerald-400',
        'teal' => 'bg-teal-500 dark:bg-teal-400',
        'cyan' => 'bg-cyan-500 dark:bg-cyan-400',
        'sky' => 'bg-sky-500 dark:bg-sky-400',
        'blue' => 'bg-blue-500 dark:bg-blue-400',
        'indigo' => 'bg-indigo-500 dark:bg-indigo-400',
        'violet' => 'bg-violet-500 dark:bg-violet-400',
        'purple' => 'bg-purple-500 dark:bg-purple-400',
        'fuchsia' => 'bg-fuchsia-500 dark:bg-fuchsia-400',
        'pink' => 'bg-pink-500 dark:bg-pink-400',
        'rose' => 'bg-rose-500 dark:bg-rose-400',
        'zinc' => 'bg-zinc-400 dark:bg-zinc-300',
        'gray' => 'bg-neutral-400 dark:bg-neutral-300',
        default => 'bg-white dark:bg-neutral-900',
    };
    $badgePositionClass = match ($badgePosition) {
        'top left' => 'top-0 left-0',
        'top right' => 'top-0 right-0',
        'bottom left' => 'bottom-0 left-0',
        'bottom right' => 'bottom-0 right-0',
        default => 'bottom-0 right-0',
    };
    $badgeClasses = [
        'absolute ring-[2px] ring-white dark:ring-neutral-900 z-10',
        'flex items-center justify-center tabular-nums overflow-hidden',
        'text-[.625rem] text-black dark:text-white font-medium',
        'after:absolute after:inset-[3px] after:bg-white dark:after:bg-neutral-900' => $badgeVariant === 'outline',
        'rounded-full after:rounded-full' => $badgeCircle,
        'rounded-[3px] after:rounded-[1px]' => !$badgeCircle,
        $badgeSize,
        $badgePositionClass,
        $badgeColorClass,
        $badgeClass
    ];

@endphp

<div class="relative w-fit" data-slot="avatar" data-size="{{ $avatarSize }}">
    <neura::button.abstract :href="$href" :as="$as" {{ $attributes->class(Arr::toCssClasses($classes)) }}>
        @if ($src)
            <img src="{{ $src }}" alt="{{ $alt || $name }}" class="object-cover h-full w-full">
        @elseif ($icon)
            <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}"
                class="{{ Arr::toCssClasses($iconClasses) }}" />
        @elseif ($hasTextContent)
            <span class="select-none">{{ $initials ?? $slot }}</span>
        @endif

        @if ($badge instanceof \Illuminate\View\ComponentSlot)
            <div {{ $badge->attributes->class(Arr::toCssClasses($badgeClasses)) }} aria-hidden="true">{{ $badge }}
            </div>
        @elseif ($badge)
            <div class="{{ Arr::toCssClasses($badgeClasses) }}" aria-hidden="true">{{ is_string($badge) ? $badge : '' }}
            </div>
        @endif
    </neura::button.abstract>
</div>
