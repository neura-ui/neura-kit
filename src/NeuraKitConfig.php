<?php

namespace Neura\Kit;

use Neura\Kit\Enum\Packs\Color;
use Neura\Kit\Enum\Packs\Rounded;
use Neura\Kit\Enum\Packs\Shadow;
use Neura\Kit\Enum\Packs\Size;
use Neura\Kit\Enum\Packs\Variant;
use Neura\Kit\Packs;

class NeuraKitConfig
{
    public const GLOBAL = 'global';

    public static function button(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'rounded' => self::GLOBAL,
                'size' => Size::SM->value,
                'variant' => Variant::DARK->value,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'colors' => Packs\Button\Color::class,
                'sizes' => Packs\Button\Size::class,
                'icon-sizes' => Packs\Button\IconSize::class,
            ],
        ], $options);
    }

    public static function badge(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'rounded' => Rounded::MD->value,
                'size' => Size::SM->value,
                'variant' => Variant::SOLID->value,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'colors' => Packs\Badge\Color::class,
                'sizes' => Packs\Badge\Size::class,
            ],
        ], $options);
    }

    public static function alert(array $options = []): array
    {
        return self::mix([
            'default' => [
                'type' => Color::INFO->value,
                'rounded' => Rounded::MD->value,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'colors' => Packs\Alert\Color::class,
            ],
        ], $options);
    }

    public static function avatar(array $options = []): array
    {
        return self::mix([
            'default' => [
                'size' => Size::MD->value,
                'rounded' => Rounded::LG->value,
                'color' => null,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'sizes' => Packs\Avatar\Size::class,
                'colors' => Packs\Avatar\Color::class,
            ],
        ], $options);
    }

    public static function input(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'shadow' => Shadow::SM->value,
                'rounded' => self::GLOBAL,
                'size' => Size::MD->value,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
                'rounders' => Packs\Rounded::class,
                'sizes' => Packs\Input\Size::class,
                'colors' => Packs\Input\Color::class,
            ],
        ], $options);
    }

    public static function modal(array $options = []): array
    {
        return self::mix([
            'default' => [
                'max-width' => 'lg',
                'rounded' => Rounded::LG->value,
                'close-on-click-away' => true,
                'close-on-escape' => true,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function card(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => Shadow::SM->value,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function dropdown(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => Rounded::LG->value,
                'shadow' => Shadow::LG->value,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function checkbox(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'size' => Size::SM->value,
                'rounded' => Rounded::SM->value,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function radio(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'size' => Size::SM->value,
            ],
        ], $options);
    }

    public static function toggle(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'size' => Size::SM->value,
            ],
        ], $options);
    }

    protected static function mix(array $default, array $options): array
    {
        foreach ($options as $key => $value) {
            if (is_array($value) && isset($default[$key]) && is_array($default[$key])) {
                $default[$key] = self::mix($default[$key], $value);
            } else {
                $default[$key] = $value;
            }
        }

        return $default;
    }
}

