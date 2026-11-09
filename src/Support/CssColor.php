<?php

namespace Neura\Kit\Support;

/**
 * A design-system colour name as the CSS variable that paints it.
 *
 * `primary` is the theme's own shade (its 500, or the bare token), `warning-600` one
 * shade of a palette; anything else — a hex, `transparent`, a `var()` — is kept as given.
 */
class CssColor
{
    public static function resolve(string $color): string
    {
        $color = trim($color);

        if (! preg_match('/^([a-z]+)(?:-(\d{2,3}))?$/', $color, $match)
            || in_array($match[1], ['transparent', 'currentcolor', 'inherit', 'none'], true)) {
            return $color;
        }

        return isset($match[2])
            ? "var(--color-{$match[1]}-{$match[2]})"
            : "var(--color-{$match[1]}-500, var(--color-{$match[1]}))";
    }
}
