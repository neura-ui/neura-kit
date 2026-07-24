@props([
    'name' => null,
    'variant' => null,
    'set' => null,
    'animate' => null,
    'state' => null,
    'trigger' => null,
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;

    $isPhosphorSet = str($name)->startsWith(['ps:', 'phosphor:']);

    // Resolve whether to render from the modular Neura set.
    //  - explicit set="neura" always wins (when the icon exists in the set)
    //  - with no explicit set, auto-resolve to Neura only for names that are
    //    NOT shared with Heroicons, so existing usages never change silently.
    $neuraDefaultSet = neura_config('icon', 'set') ?: 'neura';
    $neuraView = 'neura::icons.'.$name;
    $useNeura = ! $isPhosphorSet
        && $set !== 'heroicons'
        && $name
        && view()->exists($neuraView)
        && (
            $set === 'neura'
            || ($set === null
                && $neuraDefaultSet === 'neura'
                && ! view()->exists('heroicons::components.outline.'.$name))
        );
@endphp

@if ($useNeura)
    @php
        $iconVariant = $variant ?: (neura_config('icon', 'variant') ?: 'line');
        $iconTrigger = $trigger ?: PackResolver::iconAnimationTrigger($animate);

        $hasSize = str($attributes->get('class'))->contains(['size-', 'w-', 'h-']);
        $defaultSize = neura_config('icon', 'size') ?: 'size-5';

        $iconClasses = Arr::toCssClasses([
            'nk-icon',
            $defaultSize => ! $hasSize,
            $attributes->get('class'),
        ]);

        $iconData = ['data-slot' => 'icon', 'data-variant' => $iconVariant];

        if ($animate) {
            $iconData['data-animate'] = $animate;
            $iconData['data-trigger'] = $iconTrigger;
        }

        if ($state) {
            $iconData['data-state'] = $state;
        }
    @endphp

    <x-dynamic-component :component="$neuraView"
        {{ $attributes->except('class')->merge($iconData)->class($iconClasses) }} />
@else
    @php
        $isHeroiconsSet = ! $isPhosphorSet;

        $iconName = $isPhosphorSet
            ? str($name)->after(':')
            : $name;

        $componentName = match (true) {
            $isPhosphorSet => match ($variant) {
                'thin', 'light', 'fill', 'regular', 'duotone', 'bold' => "phosphor.icons::{$variant}.{$iconName}",
                default => "phosphor.icons::regular.{$iconName}",
            },
            $isHeroiconsSet => match ($variant) {
                'solid', 'outline' => "heroicons::{$variant}.{$iconName}",
                'mini', 'micro' => "heroicons::{$variant}.solid.{$iconName}",
                default => "heroicons::outline.{$iconName}",
            },
        };

        if ($isPhosphorSet && ! str($attributes->get('class'))->contains(['size-', 'w-', 'h-'])) {
            $attributes = $attributes->merge(['class' => 'size-6']);
        }
    @endphp

    <x-dynamic-component :component="$componentName" {{ $attributes }} data-slot="icon" />
@endif
