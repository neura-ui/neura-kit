<?php

namespace Neura\Kit\Tests\Unit\Components;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Neura\Kit\Components\Atoms\Table;
use Neura\Kit\Support\Table\Column;
use Neura\Kit\Tests\TestCase;

class TableFilterTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Livewire\Component::class)) {
            $this->markTestSkipped('Livewire is not installed');
        }

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->boolean('is_active');
            $table->string('city');
            $table->date('created_on');
        });

        DB::table('items')->insert([
            ['status' => 'Active', 'is_active' => true, 'city' => 'Paris', 'created_on' => '2026-10-21'],
            ['status' => 'Inactive', 'is_active' => false, 'city' => 'Lyon', 'created_on' => '2026-10-22'],
        ]);
    }

    public function test_a_select_filter_keeps_only_the_exact_option(): void
    {
        $this->assertSame(['Active'], $this->filtered(['status' => 'Active'])->pluck('status')->all());
    }

    public function test_a_boolean_filter_reads_one_and_zero(): void
    {
        $this->assertSame(['Paris'], $this->filtered(['is_active' => '1'])->pluck('city')->all());
        $this->assertSame(['Lyon'], $this->filtered(['is_active' => '0'])->pluck('city')->all());
    }

    public function test_a_date_filter_keeps_the_rows_of_that_day(): void
    {
        $this->assertSame(['Lyon'], $this->filtered(['created_on' => '2026-10-22'])->pluck('city')->all());
    }

    public function test_a_text_filter_matches_part_of_the_value_whatever_its_case(): void
    {
        $this->assertSame(['Paris'], $this->filtered(['city' => 'AR'])->pluck('city')->all());
    }

    public function test_a_boolean_filter_set_to_zero_counts_as_active(): void
    {
        $table = new FilterableItemsTable;
        $table->filters = ['is_active' => '0', 'status' => '', 'city' => null];

        $this->assertSame(1, $table->activeFilterCount());
        $this->assertTrue($table->hasActiveFilters());
    }

    public function test_the_toolbar_offers_a_field_per_filterable_column(): void
    {
        $this->skipWithoutHeroicons();

        Livewire::test(FilterableItemsTable::class)
            ->assertSee(neura_trans('filters'))
            ->assertSeeHtml('wire:model.live="filters.status"')
            ->assertSeeHtml('wire:model.live="filters.is_active"')
            ->assertSeeHtml('wire:model.live="filters.created_on"')
            ->assertSeeHtml('wire:model.live.debounce.300ms="filters.city"')
            ->assertSee('Yes, active')
            ->assertDontSeeHtml('nk-table-filtercount');
    }

    public function test_the_toolbar_counts_the_active_filters_and_offers_to_clear_them(): void
    {
        $this->skipWithoutHeroicons();

        Livewire::test(FilterableItemsTable::class)
            ->set('filters.is_active', '0')
            ->assertSeeHtml('<span class="nk-table-filtercount">1</span>')
            ->assertSee(neura_trans('clearFilters'))
            ->assertSee('Lyon')
            ->assertDontSee('Paris')
            ->call('clearFilters')
            ->assertSee('Paris');
    }

    private function skipWithoutHeroicons(): void
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): \Illuminate\Support\Collection
    {
        $table = new FilterableItemsTable;
        $table->filters = $filters;

        return $table->filtered()->orderBy('id')->get();
    }
}

class FilterableItemsTable extends Table
{
    public function filtered(): QueryBuilder
    {
        return $this->applyFilters($this->query());
    }

    protected function query(): QueryBuilder
    {
        return DB::table('items');
    }

    protected function columns(): array
    {
        return [
            Column::make('status', 'Status')->filterable('select', ['Active' => 'Active', 'Inactive' => 'Inactive']),
            Column::make('is_active', 'Active')->filterable('boolean', ['1' => 'Yes, active', '0' => 'No, inactive']),
            Column::make('city', 'City')->filterable(),
            Column::make('created_on', 'Created')->filterable('date'),
        ];
    }
}
