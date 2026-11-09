<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Neura\Kit\NeuraKitConfig;
use Neura\Kit\Tests\TestCase;

/**
 * The rating is a slider of stars for the keyboard and screen readers; read-only, it draws any
 * value, fractions included, without a script.
 */
class RatingComponentTest extends TestCase
{
    public function test_an_interactive_rating_is_a_slider_of_stars()
    {
        $html = Blade::render('<x-neura::rating :value="4" />');

        $this->assertStringContainsString('x-data="neuraRating(', $html);
        $this->assertStringContainsString('role="slider"', $html);
        $this->assertStringContainsString('aria-valuemax="5"', $html);
        $this->assertStringContainsString('aria-valuetext="4 out of 5"', $html);
        $this->assertSame(5, substr_count($html, 'class="nk-rating-star"'));
    }

    public function test_read_only_draws_a_fraction_without_a_script()
    {
        app()->setLocale('fr');
        $html = Blade::render('<x-neura::rating readonly :value="4.3" show-value :count="128" locale="fr" />');

        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringContainsString('aria-label="4,3 sur 5"', $html);
        $this->assertStringContainsString('--nk-rating-fill: 100%', $html);
        $this->assertStringContainsString('--nk-rating-fill: 30%', $html);
        $this->assertStringContainsString('128 avis', $html);
    }

    public function test_labels_and_max()
    {
        app()->setLocale('fr');
        $html = Blade::render('<x-neura::rating :max="10" labels />');

        $this->assertSame(10, substr_count($html, 'class="nk-rating-star"'));
        $this->assertStringContainsString('Très mauvais', html_entity_decode($html));
    }

    public function test_config_sets_size_colour_and_icon()
    {
        Config::set('neura-kit.rating', NeuraKitConfig::rating(['default' => ['size' => 'lg', 'color' => 'rose', 'icon' => 'heart']]));
        $html = Blade::render('<x-neura::rating />');

        $this->assertStringContainsString('--nk-rating-icon: 2rem', $html);
        $this->assertStringContainsString('--nk-rating-tone: var(--color-rose-500, var(--color-rose))', $html);
        $this->assertStringContainsString('M11.645 20.91', $html);
    }
}
