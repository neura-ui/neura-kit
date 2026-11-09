<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Neura\Kit\NeuraKitConfig;
use Neura\Kit\Tests\TestCase;

/**
 * The slider draws its rail, marks and bars and hands its scale to the script; its size,
 * corners and colour come from config('neura-kit.slider') like the other form components.
 */
class SliderComponentTest extends TestCase
{
    public function test_a_default_slider_has_one_native_thumb_on_a_linear_scale()
    {
        $html = Blade::render('<x-neura::slider :value="40" />');
        $config = $this->config($html);

        $this->assertSame(1, substr_count($html, 'type="range"'));
        $this->assertSame(['min' => 0, 'max' => 100, 'step' => 1, 'range' => false], array_intersect_key($config, array_flip(['min', 'max', 'step', 'range'])));
        $this->assertEquals(40, $config['value']);
        $this->assertStringContainsString('--nk-slider-thumb: 1.125rem', $html);
        $this->assertStringContainsString('--nk-slider-radius: calc(infinity * 1px)', $html);
        $this->assertStringContainsString('--nk-slider-tone: var(--color-primary-500, var(--color-primary))', $html);
    }

    public function test_a_range_has_two_labelled_thumbs()
    {
        $html = Blade::render('<x-neura::slider range label="Age" :min="18" :max="65" :value="[25, 44]" />');

        $this->assertSame(2, substr_count($html, 'type="range"'));
        $this->assertStringContainsString('aria-label="Age – Minimum"', $html);
        $this->assertStringContainsString('aria-label="Age – Maximum"', $html);
        $this->assertStringContainsString('data-range', $html);
    }

    public function test_listed_steps_are_spaced_evenly_and_labelled()
    {
        $html = Blade::render('<x-neura::slider :steps="[0.1, 0.25, 0.5, 1, 2, 5]" :marks="true" suffix=" €" locale="fr" />');
        $config = $this->config($html);

        $this->assertEquals([0.1, 0.25, 0.5, 1, 2, 5], $config['steps']);
        $this->assertSame(2, $config['decimals']);
        // The inputs hold indexes, so the six values share the track evenly.
        $this->assertStringContainsString('min="0"', $html);
        $this->assertStringContainsString('max="5"', $html);
        $this->assertSame(6, substr_count($html, 'class="nk-slider-tick"'));
        $this->assertStringContainsString('--nk-slider-at: 0.6', $html);
        $this->assertStringContainsString('0,10 €', $html);
    }

    public function test_marks_with_labels_of_their_own_and_a_histogram()
    {
        $html = Blade::render('<x-neura::slider :marks="[0 => \'Off\', 50 => \'Half\', 100 => \'Full\']" :histogram="[1, 4, 2, 0]" />');

        $this->assertStringContainsString('>Half</span>', $html);
        $this->assertStringContainsString('data-edge="start"', $html);
        $this->assertStringContainsString('data-edge="end"', $html);
        $this->assertStringContainsString('height: 100%', $html);
        $this->assertStringContainsString('height: 25%', $html);
    }

    public function test_config_sets_size_corners_and_colour()
    {
        Config::set('neura-kit.slider', NeuraKitConfig::slider(['default' => ['size' => 'lg', 'rounded' => 'md', 'color' => 'emerald']]));
        $html = Blade::render('<x-neura::slider />');

        $this->assertStringContainsString('--nk-slider-thumb: 1.375rem', $html);
        $this->assertStringContainsString('--nk-slider-radius: var(--radius-md)', $html);
        $this->assertStringContainsString('--nk-slider-tone: var(--color-emerald-500, var(--color-emerald))', $html);
        $this->assertStringContainsString('text-base', $html);

        // A prop still wins over the config.
        $this->assertStringContainsString('--nk-slider-thumb: 0.875rem', Blade::render('<x-neura::slider size="sm" />'));
    }

    public function test_a_bound_slider_keeps_its_value_out_of_the_markup_and_posts_hidden_fields()
    {
        $html = Blade::render('<x-neura::slider range name="age" x-model="age" />');

        $this->assertNull($this->config($html)['value']);
        $this->assertStringContainsString('name="age[0]"', $html);
        $this->assertStringContainsString('name="age[1]"', $html);
        $this->assertSame(
            $this->key(Blade::render('<x-neura::slider wire:model="a" />')),
            $this->key(Blade::render('<x-neura::slider wire:model="a" />')),
        );
    }

    public function test_number_fields_follow_the_input_corners()
    {
        Config::set('neura-kit.input.default.rounded', 'sm');
        $html = Blade::render('<x-neura::slider input label="Budget" suffix=" €" />');

        $this->assertMatchesRegularExpression('/class="nk-slider-field [^"]*rounded-sm/', $html);
        $this->assertStringContainsString('inputmode="decimal"', $html);
    }

    private function key(string $html): string
    {
        preg_match('/wire:key="([^"]+)"/', $html, $match);

        return $match[1];
    }

    private function config(string $html): array
    {
        $this->assertMatchesRegularExpression('/x-data="neuraSlider\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html);
        preg_match('/x-data="neuraSlider\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html, $match);

        return json_decode(json_decode('"'.html_entity_decode($match[1]).'"'), true);
    }
}
