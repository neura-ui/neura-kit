<?php

namespace Neura\Kit;

use Neura\Kit\Enum\Packs\Color;
use Neura\Kit\Enum\Packs\Rounded;
use Neura\Kit\Enum\Packs\Shadow;
use Neura\Kit\Enum\Packs\Size;
use Neura\Kit\Enum\Packs\Variant;

class NeuraKitConfig
{
    public const GLOBAL = 'global';

    public static function button(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => self::GLOBAL,
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
                'size' => Size::SM->value,
                'variant' => Variant::DARK->value,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
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
                'rounded' => self::GLOBAL,
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
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
                'colors' => Packs\Alert\Color::class,
            ],
        ], $options);
    }

    public static function avatar(array $options = []): array
    {
        return self::mix([
            'default' => [
                'size' => Size::MD->value,
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
                'color' => null,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
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
                'shadow' => self::GLOBAL,
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
                'max-width' => 'xl',
                'close-on-click-away' => true,
                'close-on-escape' => true,
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
            'component_defaults' => [
                'modal_max_width' => 'xl',
                'close_modal_on_click_away' => true,
                'close_modal_on_escape' => true,
                'close_modal_on_escape_is_forceful' => true,
                'dispatch_close_event' => true,
                'destroy_on_close' => true,
            ],
        ], $options);
    }

    public static function sideover(array $options = []): array
    {
        return self::mix([
            'default' => [
                'side' => 'right',
                'width' => 'md',
                'close-on-click-away' => true,
                'close-on-escape' => true,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
            'component_defaults' => [
                'sideover_side' => 'right',
                'sideover_width' => 'md',
                'close_sideover_on_click_away' => true,
                'close_sideover_on_escape' => true,
                'close_sideover_on_escape_is_forceful' => true,
                'dispatch_close_event' => false,
                'destroy_on_close' => true,
            ],
        ], $options);
    }

    public static function card(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
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
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function popup(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
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
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function navlist(array $options = []): array
    {
        return self::mix([
            'default' => [
                'size' => Size::MD->value,
                'variant' => 'default',
                'color' => 'neutral',
                'rounded' => self::GLOBAL,
            ],
            'packs' => [
                'sizes' => Packs\Navlist\Size::class,
                'colors' => Packs\Navlist\Color::class,
                'variants' => Packs\Navlist\Variant::class,
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function navbar(array $options = []): array
    {
        return self::mix([
            'default' => [
                'variant' => 'default',
            ],
            'packs' => [
                'items' => Packs\Navbar\Item::class,
            ],
        ], $options);
    }

    public static function tabs(array $options = []): array
    {
        return self::mix([
            'default' => [
                'variant' => 'line',
                'rounded' => self::GLOBAL,
            ],
            'packs' => [
                'variants' => Packs\Tabs\Variant::class,
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function link(array $options = []): array
    {
        return self::mix([
            'default' => [
                'variant' => 'default',
                'primary' => true,
            ],
            'packs' => [
                'variants' => Packs\Link\Variant::class,
            ],
        ], $options);
    }

    public static function callout(array $options = []): array
    {
        return self::mix([
            'default' => [
                'variant' => Color::PRIMARY->value,
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'colors' => Packs\Callout\Color::class,
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function table(array $options = []): array
    {
        return self::mix([
            'default' => [
                'variant' => 'default',
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
                'density' => 'normal',
            ],
            'packs' => [
                'variants' => Packs\Table\Variant::class,
                'rounders' => Packs\Table\Rounded::class,
                'shadows' => Packs\Table\Shadow::class,
                'densities' => Packs\Table\Density::class,
            ],
        ], $options);
    }

    public static function wizard(array $options = []): array
    {
        return self::mix([
            'default' => [
                'color' => 'neutral',
            ],
            'packs' => [
                'colors' => Packs\Wizard\Color::class,
            ],
        ], $options);
    }

    public static function dropzone(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function fieldset(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function dialog(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function toast(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function emptyState(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function progress(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => 'full',
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function skeleton(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function lottie(array $options = []): array
    {
        return self::mix([
            'default' => [
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function chart(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function tree(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function flow(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function kanban(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function imageGallery(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
            ],
        ], $options);
    }

    public static function command(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function spotlight(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
            ],
        ], $options);
    }

    public static function composer(array $options = []): array
    {
        return self::mix([
            'default' => [
                'rounded' => self::GLOBAL,
                'shadow' => self::GLOBAL,
            ],
            'packs' => [
                'rounders' => Packs\Rounded::class,
                'shadows' => Packs\Shadow::class,
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
