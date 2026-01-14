<?php

namespace Neura\Kit\Concerns;

use Illuminate\Support\Facades\Config;

trait WithPacks
{
    protected function resolveGlobalStyle(string $property): string
    {
        $style = Config::get("neura-kit.style.{$property}");

        if ($style instanceof \BackedEnum) {
            return $style->value;
        }

        return $style ?? '';
    }

    protected function getPackClass(string $component, string $pack): ?string
    {
        return Config::get("neura-kit.{$component}.packs.{$pack}");
    }

    protected function getComponentDefault(string $component, string $property): mixed
    {
        $value = Config::get("neura-kit.{$component}.default.{$property}");

        if ($value === 'global') {
            return $this->resolveGlobalStyle($property);
        }

        return $value;
    }

    protected function resolvePack(string $component, string $pack, string $key): ?string
    {
        $packClass = $this->getPackClass($component, $pack);

        if (! $packClass || ! class_exists($packClass)) {
            return null;
        }

        return $packClass::get($key);
    }

    protected function resolvePackWithVariant(string $component, string $pack, string $key, string $variant): ?array
    {
        $packClass = $this->getPackClass($component, $pack);

        if (! $packClass || ! class_exists($packClass)) {
            return null;
        }

        $colors = method_exists($packClass, 'all') ? $packClass::all() : $packClass::default();

        return $colors[$key][$variant] ?? null;
    }
}

