<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Support\CssColor;
use Neura\Kit\Tests\TestCase;

/**
 * The file manager's accent — selection, focus, active chips, progress — comes
 * from `color`, read from the config then the prop, like the time grid's.
 */
class FileManagerAccentTest extends TestCase
{
    public function test_accent_defaults_to_the_theme_primary()
    {
        $html = Blade::render('<x-neura::file-manager />');

        $this->assertStringContainsString('nk-file-manager', $html);
        $this->assertStringContainsString('--nk-fm-accent: var(--color-primary-500, var(--color-primary))', $html);
    }

    public function test_accent_follows_the_prop_then_the_config()
    {
        $this->assertStringContainsString(
            '--nk-fm-accent: var(--color-info-500, var(--color-info))',
            Blade::render('<x-neura::file-manager color="info" />'),
        );

        config(['neura-kit.file-manager.default.color' => 'success-600']);

        $this->assertStringContainsString(
            '--nk-fm-accent: var(--color-success-600)',
            Blade::render('<x-neura::file-manager />'),
        );
    }

    public function test_css_color_resolves_like_the_time_grid()
    {
        $this->assertSame('var(--color-primary-500, var(--color-primary))', CssColor::resolve('primary'));
        $this->assertSame('var(--color-warning-600)', CssColor::resolve('warning-600'));
        $this->assertSame('#ff0000', CssColor::resolve('#ff0000'));
        $this->assertSame('transparent', CssColor::resolve('transparent'));
    }
}
