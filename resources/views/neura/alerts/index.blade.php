@props([
    'type' => neura_config('alert', 'type'),
    'color' => null,
    'rounded' => neura_config('alert', 'rounded'),
    'icon' => true,
    'iconName' => null,
    'iconClass' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    // Support both 'type' (legacy) and 'color' (new) props
    $alertColor = $color ?? ($type ?? 'info');
    $colorConfig = PackResolver::alertColor($alertColor);
    $iconName ??= $colorConfig['iconName'] ?? 'information-circle';
    $iconColor = $colorConfig['icon'] ?? 'text-blue-500';
    $roundedClass = PackResolver::rounded($rounded ?? 'md');

    $containerClass = [
        'border px-4 py-2 text-neutral-900 dark:text-neutral-100',
        $roundedClass,
        $colorConfig['container'] ?? '',
        '[&:has([data-slot=alert-heading]+[data-slot=alert-content])>[data-slot=alert-heading]]:mb-2',
        '[&:has([data-slot=alert-content]+[data-slot=alert-actions])>[data-slot=alert-content]]:mb-2',
    ];

    $attributes = $attributes->merge(['class' => Arr::toCssClasses($containerClass)]);
@endphp

<div {{ $attributes }}>
    <div class="flex items-center gap-x-1" data-slot="alert-heading">
        <div class="shrink-0 size-7 grid place-items-center">
            <neura::icon name="{{ $iconName }}" class="{{ Arr::toCssClasses([$iconColor, $iconClass]) }}" />
        </div>

        <div class="flex-1 text-start">
            {{ $heading ?? $slot }}
        </div>
    </div>

    @if (isset($content) && !$content->isEmpty())
        <div class="text-start" data-slot='alert-content'>
            {{ $content }}
        </div>
    @endif

    @if (isset($actions) && !$actions->isEmpty())
        <div data-slot='alert-actions'>
            {{ $actions }}
        </div>
    @endif
</div>
