<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The time grid renders its empty cells and hands its layout to the script, which paints
 * the selection. The cells sit in a wire:ignore container keyed by the layout, so a parent
 * render leaves the painting alone and a new layout rebuilds them.
 */
class TimeGridComponentTest extends TestCase
{
    public function test_a_default_grid_is_a_week_of_hours()
    {
        app()->setLocale('fr');
        $html = Blade::render('<x-neura::time-grid locale="fr" />');
        $config = $this->config($html);

        $this->assertSame(168, substr_count($html, 'role="gridcell"'));
        $this->assertSame('rows', $config['orientation']);
        $this->assertSame('day', $config['keyName']);
        $this->assertSame('ranges', $config['format']);
        $this->assertSame([[
            'key' => 'on',
            'label' => 'Sélectionné',
            'color' => 'var(--color-primary-500, var(--color-primary))',
        ]], $config['levels']);
    }

    public function test_the_layout_props_shape_the_cells()
    {
        $html = Blade::render('<x-neura::time-grid :days="[\'mon\', \'tue\']" :step="30" start="08:00" end="12:00" />');

        $this->assertSame(16, substr_count($html, 'role="gridcell"'));
    }

    public function test_levels_get_a_toolbar_and_colors()
    {
        $html = Blade::render('<x-neura::time-grid :levels="[\'on\' => \'Diffusion\', \'boost\' => [\'label\' => \'Prioritaire\', \'color\' => \'warning\']]" />');

        $this->assertSame(2, substr_count($html, 'data-tg-level='));
        $this->assertStringContainsString('--nk-tg-level-1: var(--color-warning-500, var(--color-warning))', $html);
    }

    public function test_readonly_has_no_toolbar()
    {
        $html = Blade::render('<x-neura::time-grid readonly />');

        $this->assertStringNotContainsString('data-tg-toolbar', $html);
        $this->assertTrue($this->config($html)['readonly']);
    }

    public function test_the_cells_survive_livewire_renders()
    {
        $first = Blade::render('<x-neura::time-grid />');
        $again = Blade::render('<x-neura::time-grid />');
        $finer = Blade::render('<x-neura::time-grid :step="30" />');

        $this->assertStringContainsString('wire:ignore', $first);
        $this->assertSame($this->key($first), $this->key($again));
        $this->assertNotSame($this->key($first), $this->key($finer));
    }

    public function test_the_size_comes_from_config()
    {
        $this->assertStringContainsString('--nk-tg-cell: 1.375rem', Blade::render('<x-neura::time-grid />'));
        $this->assertStringContainsString('--nk-tg-cell: 1.75rem', Blade::render('<x-neura::time-grid size="lg" />'));
    }

    public function test_cells_have_readable_labels()
    {
        $html = Blade::render('<x-neura::time-grid locale="en" :hour-cycle="24" />');

        $this->assertStringContainsString('aria-label="Monday, 09:00 – 10:00"', $html);
    }

    public function test_a_value_without_wire_model_is_part_of_the_key()
    {
        $early = $this->key(Blade::render('<x-neura::time-grid readonly :value="[[\'day\' => \'mon\', \'start\' => \'09:00\', \'end\' => \'11:00\']]" />'));
        $late = $this->key(Blade::render('<x-neura::time-grid readonly :value="[[\'day\' => \'mon\', \'start\' => \'11:00\', \'end\' => \'12:00\']]" />'));

        $this->assertNotSame($early, $late);
    }

    public function test_a_bound_value_stays_out_of_the_markup()
    {
        $early = Blade::render('<x-neura::time-grid wire:model="slots" :value="[[\'day\' => \'mon\', \'start\' => \'09:00\', \'end\' => \'11:00\']]" />');
        $late = Blade::render('<x-neura::time-grid wire:model="slots" :value="[[\'day\' => \'mon\', \'start\' => \'11:00\', \'end\' => \'12:00\']]" />');

        $this->assertSame($this->key($early), $this->key($late));
        $this->assertSame([], $this->config($early)['value']);
    }

    public function test_the_strings_are_part_of_the_key()
    {
        $grid = '<x-neura::time-grid :levels="[\'on\' => \'Live\']" />';
        app()->setLocale('en');
        $english = $this->key(Blade::render($grid));
        app()->setLocale('fr');
        $french = $this->key(Blade::render($grid));

        $this->assertNotSame($english, $french);
    }

    public function test_a_disabled_grid_is_locked_but_keeps_its_value()
    {
        $html = Blade::render('<x-neura::time-grid disabled />');

        $this->assertStringNotContainsString('data-tg-toolbar', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertTrue($this->config($html)['disabled']);
    }

    public function test_an_error_marks_the_root_livewire_updates()
    {
        view()->share('errors', (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['slots' => ['Pick a slot.']])));

        $html = Blade::render('<x-neura::time-grid name="slots" />');

        $this->assertMatchesRegularExpression('/data-slot="time-grid"\s+data-invalid/', $html);
    }

    private function key(string $html): string
    {
        $this->assertMatchesRegularExpression('/wire:key="(time-grid-[a-f0-9]+)"/', $html);
        preg_match('/wire:key="(time-grid-[a-f0-9]+)"/', $html, $match);

        return $match[1];
    }

    private function config(string $html): array
    {
        $this->assertMatchesRegularExpression('/x-data="neuraTimeGrid\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html);
        preg_match('/x-data="neuraTimeGrid\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html, $match);

        return json_decode(json_decode('"'.html_entity_decode($match[1]).'"'), true);
    }
}
