<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Neura\Kit\NeuraKitConfig;
use Neura\Kit\Tests\TestCase;

/**
 * The recurrence editor renders its controls in the locale and hands its start, week and
 * phrases to the script, which keeps the rule and writes it as an RRULE.
 */
class RecurrenceComponentTest extends TestCase
{
    public function test_a_default_editor_in_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::recurrence start="2026-11-06" />'));
        $config = $this->config(Blade::render('<x-neura::recurrence start="2026-11-06" />'));

        $this->assertSame('2026-11-06', $config['start']);
        $this->assertSame(1, $config['weekStart']);
        $this->assertSame('Toutes les', $config['phrases']['every_weekly']);
        $this->assertSame(5, $config['preview']);
        $this->assertStringContainsString('Décrire la répétition', $html);
        $this->assertStringContainsString('tous les lundis et jeudis à 9h', $html);
        // Weekday toggles from Monday, with their full names for screen readers.
        $this->assertMatchesRegularExpression('/aria-label="lundi".*aria-label="dimanche"/s', $html);
        $this->assertStringContainsString('>novembre</option>', $html);
        $this->assertStringContainsString('x-model="rule.time"', $html);
        $this->assertStringContainsString('x-model="rule.until"', $html);
        // The end not chosen dims its date or its count.
        $this->assertStringContainsString('x-bind:data-idle="rule.end !== \'until\'"', $html);
        $this->assertStringContainsString('x-bind:data-idle="rule.end !== \'count\'"', $html);
    }

    public function test_an_english_week_starts_on_sunday()
    {
        app()->setLocale('en');
        $config = $this->config(Blade::render('<x-neura::recurrence locale="en" />'));

        $this->assertSame(0, $config['weekStart']);
        $this->assertSame('Every', $config['phrases']['every_weekly']);
    }

    public function test_props_trim_the_editor()
    {
        $html = Blade::render('<x-neura::recurrence :frequencies="[\'weekly\', \'monthly\']" :natural="false" :time="false" :ends="false" :preview="0" :summary="false" optional show-rule />');

        $this->assertStringNotContainsString('nk-recurrence-natural', $html);
        $this->assertStringNotContainsString('rule.time', $html);
        $this->assertStringNotContainsString('rule.until', $html);
        $this->assertStringNotContainsString('nk-recurrence-dates', $html);
        $this->assertStringNotContainsString("setFreq('daily')", html_entity_decode($html));
        $this->assertStringContainsString('setNone()', $html);
        $this->assertStringContainsString('nk-recurrence-code', $html);
        $this->assertTrue($this->config($html)['optional']);
    }

    public function test_config_sets_size_corners_and_the_natural_field()
    {
        Config::set('neura-kit.recurrence', NeuraKitConfig::recurrence(['default' => ['size' => 'sm', 'rounded' => 'xl', 'natural' => false, 'preview' => 3]]));
        $html = Blade::render('<x-neura::recurrence />');

        $this->assertStringNotContainsString('nk-recurrence-natural', $html);
        $this->assertStringContainsString('nk-recurrence-body rounded-xl', $html);
        $this->assertStringContainsString('--nk-recurrence-radius: var(--radius-xl)', $html);
        $this->assertStringContainsString('size-7', $html);
        $this->assertSame(3, $this->config($html)['preview']);
    }

    public function test_a_bound_editor_keeps_its_value_out_of_the_markup()
    {
        $html = Blade::render('<x-neura::recurrence wire:model="rule" name="rule" />');

        $this->assertNull($this->config($html)['value']);
        $this->assertStringContainsString('name="rule"', $html);
        $this->assertStringContainsString('wire:ignore', $html);
    }

    private function config(string $html): array
    {
        $this->assertMatchesRegularExpression('/x-data="neuraRecurrence\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html);
        preg_match('/x-data="neuraRecurrence\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html, $match);

        return json_decode(json_decode('"'.html_entity_decode($match[1]).'"'), true);
    }
}
