@props([
    'name' => null,
    'variant' => null,
])

@php

    $isPhosphorSet = str($name)->startsWith(['ps:', 'phosphor:']);
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
        $attributes = $attributes->class('size-6');
    }
@endphp

<x-dynamic-component :component="$componentName" {{ $attributes }} data-slot="icon" />
