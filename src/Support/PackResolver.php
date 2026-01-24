<?php

namespace Neura\Kit\Support;

use Illuminate\Support\Facades\Config;
use Neura\Kit\Packs\Alert;
use Neura\Kit\Packs\Avatar;
use Neura\Kit\Packs\Badge;
use Neura\Kit\Packs\Button;
use Neura\Kit\Packs\Input;
use Neura\Kit\Packs\Rounded;
use Neura\Kit\Packs\Shadow;
use Neura\Kit\Packs\Wizard;

class PackResolver
{
    public static function globalStyle(string $property): string
    {
        $style = Config::get("neura-kit.style.{$property}");

        if ($style instanceof \BackedEnum) {
            return $style->value;
        }

        return $style ?? self::defaultGlobalStyle($property);
    }

    protected static function defaultGlobalStyle(string $property): string
    {
        return match ($property) {
            'color' => 'primary',
            'rounded' => 'lg',
            'shadow' => 'sm',
            default => '',
        };
    }

    public static function packClass(string $component, string $pack): ?string
    {
        return Config::get("neura-kit.{$component}.packs.{$pack}");
    }

    public static function componentDefault(string $component, string $property): mixed
    {
        $value = Config::get("neura-kit.{$component}.default.{$property}");

        if ($value === 'global' || $value === null) {
            return self::globalStyle($property);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    public static function pack(string $component, string $pack, string $key): ?string
    {
        $packClass = self::packClass($component, $pack);

        if (! $packClass || ! class_exists($packClass)) {
            return null;
        }

        return $packClass::get($key);
    }

    public static function packAll(string $component, string $pack): array
    {
        $packClass = self::packClass($component, $pack);

        if (! $packClass || ! class_exists($packClass)) {
            return [];
        }

        return method_exists($packClass, 'all') ? $packClass::all() : $packClass::default();
    }

    public static function packWithVariant(string $component, string $pack, string $key, string $variant): ?array
    {
        $colors = self::packAll($component, $pack);

        return $colors[$key][$variant] ?? null;
    }

    public static function rounded(?string $value): string
    {
        $value = $value ?: self::globalStyle('rounded');

        return Rounded::get($value) ?? 'rounded-lg';
    }

    public static function shadow(?string $value): string
    {
        $value = $value ?: self::globalStyle('shadow');

        return Shadow::get($value) ?? 'shadow-sm';
    }

    public static function buttonSize(?string $size): array
    {
        $size = $size ?: 'sm';
        $sizes = Button\Size::default();

        return $sizes[$size] ?? $sizes['sm'];
    }

    public static function buttonColor(?string $color, string $variant = 'solid'): array
    {
        $color = $color ?: self::globalStyle('color');
        $colors = Button\Color::all();

        return $colors[$color][$variant] ?? $colors['primary'][$variant] ?? [
            'base' => 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900 shadow-sm',
            'hover' => 'hover:bg-neutral-800 dark:hover:bg-neutral-200',
        ];
    }

    public static function badgeSize(?string $size, bool $pill = false): string
    {
        $size = $size ?: 'sm';
        if ($pill) {
            $sizes = Badge\Size::pill();
        } else {
            $sizes = Badge\Size::default();
        }

        return $sizes[$size] ?? $sizes['sm'];
    }

    public static function badgeColor(?string $color, string $variant = 'solid'): string
    {
        $color = $color ?: self::globalStyle('color');
        $colors = Badge\Color::all();

        return $colors[$color][$variant] ?? $colors['secondary'][$variant] ?? 'bg-neutral-200 text-neutral-800';
    }

    public static function avatarSize(?string $size): array
    {
        $size = $size ?: 'md';
        $sizes = Avatar\Size::default();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function avatarColor(?string $color): string
    {
        $color = $color ?: 'neutral';
        $colors = Avatar\Color::default();

        return $colors[$color] ?? $colors['neutral'];
    }

    public static function inputSize(?string $size): string
    {
        $size = $size ?: 'md';
        $sizes = Input\Size::default();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function inputColor(string $element = 'base'): array
    {
        $colors = Input\Color::default();

        return $colors[$element] ?? $colors['base'];
    }

    public static function alertColor(?string $type): array
    {
        $type = $type ?: 'info';
        $colors = Alert\Color::default();

        return $colors[$type] ?? $colors['info'];
    }

    public static function wizardColor(?string $color): array
    {
        $color = $color ?: 'neutral';
        $colors = Wizard\Color::all();

        return $colors[$color] ?? $colors['neutral'];
    }
}
