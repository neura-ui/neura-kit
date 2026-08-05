<?php

namespace Neura\Kit\Support;

use Illuminate\Support\Facades\Config;
use Neura\Kit\Packs\Alert;
use Neura\Kit\Packs\Avatar;
use Neura\Kit\Packs\Badge;
use Neura\Kit\Packs\Button;
use Neura\Kit\Packs\Dropzone;
use Neura\Kit\Packs\EventCalendar;
use Neura\Kit\Packs\FileManager;
use Neura\Kit\Packs\Filters;
use Neura\Kit\Packs\RuleBuilder;
use Neura\Kit\Packs\Icon;
use Neura\Kit\Packs\IconStack;
use Neura\Kit\Packs\Input;
use Neura\Kit\Packs\Callout;
use Neura\Kit\Packs\Link;
use Neura\Kit\Packs\Navlist;
use Neura\Kit\Packs\Orb;
use Neura\Kit\Packs\Navbar;
use Neura\Kit\Packs\Rounded;
use Neura\Kit\Packs\Shadow;
use Neura\Kit\Packs\Tabs;
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

    /**
     * Resolve a rounded token (e.g. "lg") from a config value or class name.
     */
    public static function roundedToken(?string $value): string
    {
        $value = $value ?: self::globalStyle('rounded');

        if ($value === 'global') {
            $value = self::globalStyle('rounded');
        }

        if (str_starts_with($value, 'rounded-')) {
            return substr($value, strlen('rounded-')) ?: 'lg';
        }

        return Rounded::get($value) ? $value : 'lg';
    }

    /**
     * Leading / trailing rounded utilities.
     * All branches return complete class literals so Tailwind can scan them.
     *
     * @return array{0: string, 1: string}
     */
    public static function roundedSides(?string $value): array
    {
        return match (self::roundedToken($value)) {
            'none' => ['rounded-l-none', 'rounded-r-none'],
            'sm' => ['rounded-l-sm', 'rounded-r-sm'],
            'md' => ['rounded-l-md', 'rounded-r-md'],
            'lg' => ['rounded-l-lg', 'rounded-r-lg'],
            'xl' => ['rounded-l-xl', 'rounded-r-xl'],
            '2xl' => ['rounded-l-2xl', 'rounded-r-2xl'],
            '3xl' => ['rounded-l-3xl', 'rounded-r-3xl'],
            'full' => ['rounded-l-full', 'rounded-r-full'],
            default => ['rounded-l-lg', 'rounded-r-lg'],
        };
    }

    /**
     * Logical (start / end) rounded utilities, RTL-aware.
     * All branches return complete class literals so Tailwind can scan them.
     *
     * @return array{0: string, 1: string}
     */
    public static function roundedSidesLogical(?string $value): array
    {
        return match (self::roundedToken($value)) {
            'none' => ['rounded-s-none', 'rounded-e-none'],
            'sm' => ['rounded-s-sm', 'rounded-e-sm'],
            'md' => ['rounded-s-md', 'rounded-e-md'],
            'lg' => ['rounded-s-lg', 'rounded-e-lg'],
            'xl' => ['rounded-s-xl', 'rounded-e-xl'],
            '2xl' => ['rounded-s-2xl', 'rounded-e-2xl'],
            '3xl' => ['rounded-s-3xl', 'rounded-e-3xl'],
            'full' => ['rounded-s-full', 'rounded-e-full'],
            default => ['rounded-s-lg', 'rounded-e-lg'],
        };
    }

    /**
     * OTP cell first/last rounded variant classes (complete literals for Tailwind).
     *
     * @return array{0: string, 1: string}
     */
    public static function otpInputRounded(?string $value): array
    {
        return match (self::roundedToken($value)) {
            'none' => ['[&:where(&:first-child)]:rounded-l-none', '[&:where(&:last-child)]:rounded-r-none'],
            'sm' => ['[&:where(&:first-child)]:rounded-l-sm', '[&:where(&:last-child)]:rounded-r-sm'],
            'md' => ['[&:where(&:first-child)]:rounded-l-md', '[&:where(&:last-child)]:rounded-r-md'],
            'lg' => ['[&:where(&:first-child)]:rounded-l-lg', '[&:where(&:last-child)]:rounded-r-lg'],
            'xl' => ['[&:where(&:first-child)]:rounded-l-xl', '[&:where(&:last-child)]:rounded-r-xl'],
            '2xl' => ['[&:where(&:first-child)]:rounded-l-2xl', '[&:where(&:last-child)]:rounded-r-2xl'],
            '3xl' => ['[&:where(&:first-child)]:rounded-l-3xl', '[&:where(&:last-child)]:rounded-r-3xl'],
            'full' => ['[&:where(&:first-child)]:rounded-l-full', '[&:where(&:last-child)]:rounded-r-full'],
            default => ['[&:where(&:first-child)]:rounded-l-lg', '[&:where(&:last-child)]:rounded-r-lg'],
        };
    }

    /**
     * OTP group rounded classes around separators (complete literals for Tailwind).
     *
     * @return array{0: string, 1: string}
     */
    public static function otpGroupRounded(?string $value): array
    {
        return match (self::roundedToken($value)) {
            'none' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-none',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-none',
            ],
            'sm' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-sm',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-sm',
            ],
            'md' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-md',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-md',
            ],
            'lg' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-lg',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-lg',
            ],
            'xl' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-xl',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-xl',
            ],
            '2xl' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-2xl',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-2xl',
            ],
            '3xl' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-3xl',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-3xl',
            ],
            'full' => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-full',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-full',
            ],
            default => [
                '[&:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-lg',
                '[&:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-lg',
            ],
        };
    }

    public static function shadow(?string $value): string
    {
        $value = $value ?: self::globalStyle('shadow');

        return Shadow::get($value) ?? 'shadow-sm';
    }

    public static function dropShadow(?string $value): string
    {
        $value = $value ?: self::globalStyle('shadow');

        return match ($value) {
            'none' => 'drop-shadow-none',
            'sm' => 'drop-shadow-sm',
            'base' => 'drop-shadow',
            'md' => 'drop-shadow-md',
            'lg' => 'drop-shadow-lg',
            'xl' => 'drop-shadow-xl',
            '2xl' => 'drop-shadow-2xl',
            default => 'drop-shadow-lg',
        };
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

    public static function orbMode(?string $state): string
    {
        $state = $state ?: (self::componentDefault('orb', 'state') ?: 'searching');
        $map = self::packAll('orb', 'states') ?: Orb\State::all();

        return $map[$state] ?? 'globe';
    }

    public static function orbColor(?string $color): string
    {
        $color = $color ?: (self::componentDefault('orb', 'color') ?: 'foreground');
        $colors = self::packAll('orb', 'colors') ?: Orb\Color::all();

        return $colors[$color] ?? $colors['foreground'] ?? 'text-fg';
    }

    public static function iconAnimationTrigger(?string $animate): string
    {
        if (! $animate) {
            return 'loop';
        }

        $map = self::packAll('icon', 'animations') ?: Icon\Animation::all();

        return $map[$animate] ?? 'loop';
    }

    public static function iconStackSize(?string $size): array
    {
        $size = $size ?: self::componentDefault('icon-stack', 'size') ?: 'md';
        $sizes = self::packAll('icon-stack', 'sizes') ?: IconStack\Size::all();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function iconStackColor(?string $color): array
    {
        $color = $color ?: self::componentDefault('icon-stack', 'color') ?: 'foreground';
        $colors = self::packAll('icon-stack', 'colors') ?: IconStack\Color::all();

        return $colors[$color] ?? $colors['foreground'];
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

    public static function dropzoneSize(?string $size): array
    {
        $size = $size ?: self::componentDefault('dropzone', 'size') ?: 'md';
        $sizes = self::packAll('dropzone', 'sizes') ?: Dropzone\Size::all();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function dropzoneColor(?string $element = null): array
    {
        $colors = self::packAll('dropzone', 'colors') ?: Dropzone\Color::all();

        if ($element === null) {
            return $colors;
        }

        return (array) ($colors[$element] ?? []);
    }

    public static function fileManagerSize(?string $size): array
    {
        $size = $size ?: self::componentDefault('file-manager', 'size') ?: 'md';
        $sizes = self::packAll('file-manager', 'sizes') ?: FileManager\Size::all();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function fileManagerColor(?string $element = null): array
    {
        $colors = self::packAll('file-manager', 'colors') ?: FileManager\Color::all();

        if ($element === null) {
            return $colors;
        }

        return (array) ($colors[$element] ?? []);
    }

    public static function filtersSize(?string $size): array
    {
        $size = $size ?: 'sm';
        $sizes = Filters\Size::default();

        return $sizes[$size] ?? $sizes['sm'];
    }

    public static function ruleBuilderSize(?string $size): array
    {
        $size = $size ?: 'sm';
        $sizes = RuleBuilder\Size::default();

        return $sizes[$size] ?? $sizes['sm'];
    }

    public static function eventCalendarSize(?string $size): array
    {
        $size = $size ?: self::componentDefault('event-calendar', 'size') ?: 'md';
        $sizes = self::packAll('event-calendar', 'sizes') ?: EventCalendar\Size::all();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function alertColor(?string $type): array
    {
        $type = $type ?: 'info';
        $colors = Alert\Color::default();

        return $colors[$type] ?? $colors['info'];
    }

    public static function wizardColor(?string $color): array
    {
        $color = $color ?: self::componentDefault('wizard', 'color') ?: 'neutral';
        $colors = self::packAll('wizard', 'colors') ?: Wizard\Color::all();

        return $colors[$color] ?? $colors['neutral'];
    }

    public static function navlistSize(?string $size): array
    {
        $size = $size ?: self::componentDefault('navlist', 'size') ?: 'md';
        $sizes = self::packAll('navlist', 'sizes') ?: Navlist\Size::all();

        return $sizes[$size] ?? $sizes['md'];
    }

    public static function navlistColor(?string $color): array
    {
        $color = $color ?: self::componentDefault('navlist', 'color') ?: 'neutral';
        $colors = self::packAll('navlist', 'colors') ?: Navlist\Color::all();

        return $colors[$color] ?? $colors['neutral'] ?? [];
    }

    public static function navlistVariant(?string $variant): array
    {
        $variant = $variant ?: self::componentDefault('navlist', 'variant') ?: 'default';
        $variants = self::packAll('navlist', 'variants') ?: Navlist\Variant::all();

        return $variants[$variant] ?? $variants['default'];
    }

    public static function navbarItem(?string $variant = null): array
    {
        $variant = $variant ?: self::componentDefault('navbar', 'variant') ?: 'default';
        $items = self::packAll('navbar', 'items') ?: Navbar\Item::all();

        return $items[$variant] ?? $items['default'];
    }

    public static function tabsVariant(?string $variant): string
    {
        $variant = $variant ?: self::componentDefault('tabs', 'variant') ?: 'line';
        $variants = self::packAll('tabs', 'variants') ?: Tabs\Variant::all();

        return $variants[$variant] ?? $variants['line'];
    }

    public static function linkVariant(?string $variant, bool $primary = true): array
    {
        $variant = $variant ?: self::componentDefault('link', 'variant') ?: 'default';
        $variants = self::packAll('link', 'variants') ?: Link\Variant::all();
        $config = $variants[$variant] ?? $variants['default'];

        $tone = $primary ? 'primary' : 'secondary';

        return [
            'decoration' => $config['decoration'] ?? 'underline',
            'color' => $config[$tone] ?? $config['primary'] ?? '',
        ];
    }

    public static function calloutColor(?string $variant): array
    {
        $variant = $variant ?: self::componentDefault('callout', 'variant') ?: 'primary';
        $colors = self::packAll('callout', 'colors') ?: Callout\Color::all();

        return $colors[$variant] ?? $colors['primary'];
    }

    public static function tablePack(string $pack): array
    {
        return self::packAll('table', $pack);
    }
}
