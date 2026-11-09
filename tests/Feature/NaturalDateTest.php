<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The calendar's optional natural-language field: off by default, on with `natural` (or
 * config('neura-kit.calendar.default.natural')), handed the calendar's locale, first day of
 * the week and a hint for its mode. The date picker passes it on to its calendar.
 */
class NaturalDateTest extends TestCase
{
    public function test_the_field_is_off_by_default()
    {
        $this->assertStringNotContainsString('neuraNaturalDate(', Blade::render('<x-neura::calendar />'));
    }

    public function test_natural_adds_the_field_with_the_calendar_settings()
    {
        $config = $this->config(Blade::render('<x-neura::calendar natural locale="fr" :first-day-of-week="1" />'));

        $this->assertSame('fr', $config['locale']);
        $this->assertSame(1, $config['weekStart']);
        $this->assertNotEmpty($config['placeholder']);
    }

    public function test_the_hint_follows_the_mode()
    {
        $single = $this->config(Blade::render('<x-neura::calendar natural />'))['placeholder'];
        $range = $this->config(Blade::render('<x-neura::calendar natural range />'))['placeholder'];
        $multiple = $this->config(Blade::render('<x-neura::calendar natural multiple />'))['placeholder'];
        $own = $this->config(Blade::render('<x-neura::calendar natural natural-placeholder="When?" />'))['placeholder'];

        $this->assertCount(3, array_unique([$single, $range, $multiple]));
        $this->assertSame('When?', $own);
    }

    public function test_config_turns_it_on_everywhere()
    {
        config()->set('neura-kit.calendar.default.natural', true);

        $this->assertStringContainsString('neuraNaturalDate(', Blade::render('<x-neura::calendar />'));
        $this->assertStringNotContainsString('neuraNaturalDate(', Blade::render('<x-neura::calendar :natural="false" />'));
    }

    public function test_the_date_picker_passes_it_on()
    {
        $this->assertStringContainsString('neuraNaturalDate(', Blade::render('<x-neura::date-picker natural />'));
        $this->assertStringNotContainsString('neuraNaturalDate(', Blade::render('<x-neura::date-picker />'));

        $withTime = $this->config(Blade::render('<x-neura::date-picker natural time />'));
        $this->assertTrue($withTime['time']);
    }

    private function config(string $html): array
    {
        $this->assertMatchesRegularExpression('/x-data="neuraNaturalDate\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html);
        preg_match('/x-data="neuraNaturalDate\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html, $match);

        return json_decode(json_decode('"'.html_entity_decode($match[1]).'"'), true);
    }
}
