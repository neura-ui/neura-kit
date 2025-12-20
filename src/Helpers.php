<?php

use Neura\Kit\Services\License\LicenseService;
use Neura\Kit\Support\PackResolver;

if (!function_exists('neura_trans')) {
    function neura_trans($key, $default = null) {
        $locale = app()->getLocale();
        $translationsPath = resource_path("lang/{$locale}.json");

        if (!file_exists($translationsPath)) {
            $translationsPath = resource_path("lang/en.json");
        }

        if (!file_exists($translationsPath)) {
            $packagePath = __DIR__ . "/../resources/lang/{$locale}.json";
            if (file_exists($packagePath)) {
                $translationsPath = $packagePath;
            } else {
                $translationsPath = __DIR__ . "/../resources/lang/en.json";
            }
        }

        $translations = file_exists($translationsPath)
            ? json_decode(file_get_contents($translationsPath), true)
            : [];

        return $translations[$key] ?? $default ?? $key;
    }
}

if (!function_exists('neura_pack')) {
    function neura_pack(string $component, string $pack, string $key): ?string {
        return PackResolver::pack($component, $pack, $key);
    }
}

if (!function_exists('neura_rounded')) {
    function neura_rounded(?string $value = null): string {
        return PackResolver::rounded($value);
    }
}

if (!function_exists('neura_shadow')) {
    function neura_shadow(?string $value = null): string {
        return PackResolver::shadow($value);
    }
}

if (!function_exists('neura_button_size')) {
    function neura_button_size(?string $size = null): array {
        return PackResolver::buttonSize($size);
    }
}

if (!function_exists('neura_button_color')) {
    function neura_button_color(?string $color = null, string $variant = 'solid'): array {
        return PackResolver::buttonColor($color, $variant);
    }
}

if (!function_exists('neura_badge_size')) {
    function neura_badge_size(?string $size = null, bool $pill = false): string {
        return PackResolver::badgeSize($size, $pill);
    }
}

if (!function_exists('neura_badge_color')) {
    function neura_badge_color(?string $color = null, string $variant = 'solid'): string {
        return PackResolver::badgeColor($color, $variant);
    }
}

if (!function_exists('neura_avatar_size')) {
    function neura_avatar_size(?string $size = null): array {
        return PackResolver::avatarSize($size);
    }
}

if (!function_exists('neura_avatar_color')) {
    function neura_avatar_color(?string $color = null): string {
        return PackResolver::avatarColor($color);
    }
}

if (!function_exists('neura_input_size')) {
    function neura_input_size(?string $size = null): string {
        return PackResolver::inputSize($size);
    }
}

if (!function_exists('neura_alert_color')) {
    function neura_alert_color(?string $type = null): array {
        return PackResolver::alertColor($type);
    }
}

if (!function_exists('neura_config')) {
    function neura_config(string $component, string $property): mixed {
        return PackResolver::componentDefault($component, $property);
    }
}

if (!function_exists('neura_license')) {
    function neura_license(): LicenseService {
        return app(LicenseService::class);
    }
}

if (!function_exists('neura_is_activated')) {
    function neura_is_activated(): bool {
        try {
            return neura_license()->isActivated();
        } catch (\Exception $e) {
            return false;
        }
    }
}
