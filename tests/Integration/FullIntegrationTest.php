<?php

namespace Neura\Kit\Tests\Integration;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Support\Table\Column;
use Neura\Kit\Services\ToastService;
use Neura\Kit\Tests\TestCase;

class FullIntegrationTest extends TestCase
{
    public function test_blade_components_work()
    {
        $html = Blade::render('<x-neura::button>Test</x-neura::button>');

        $this->assertStringContainsString('Test', $html);
        $this->assertStringContainsString('button', $html);
    }

    public function test_table_column_builder_works()
    {
        $column = Column::make('name', 'Name')
            ->searchable()
            ->sortable()
            ->filterable();

        $this->assertEquals('name', $column->key);
        $this->assertEquals('Name', $column->label);
        $this->assertTrue($column->searchable);
        $this->assertTrue($column->sortable);
        $this->assertTrue($column->filterable);
    }

    public function test_table_column_static_methods_work()
    {
        $textColumn = Column::text('title', 'Title');
        $this->assertEquals('title', $textColumn->key);
        $this->assertEquals('neura::table.columns.column', $textColumn->component);

        $dateColumn = Column::date('created_at', 'Created At');
        $this->assertEquals('created_at', $dateColumn->key);
        $this->assertEquals('neura::table.columns.date', $dateColumn->component);

        $booleanColumn = Column::boolean('active', 'Active');
        $this->assertEquals('active', $booleanColumn->key);
        $this->assertEquals('neura::table.columns.boolean', $booleanColumn->component);
    }

    public function test_toast_helper_works()
    {
        $toast = app(ToastService::class);

        $toast->success('Test success');
        $toast->error('Test error');
        $toast->warning('Test warning');
        $toast->info('Test info');

        $this->assertTrue(
            method_exists(ToastService::class, 'success'),
            'ToastService::success should exist'
        );
    }

    public function test_modal_manager_view_renders()
    {
        $html = Blade::render('<x-neura::modal-manager :components="[]" />');

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager()"', $html);
    }

    public function test_nested_components_work()
    {
        $html = Blade::render('<x-neura::button.abstract>Nested</x-neura::button.abstract>');

        $this->assertStringContainsString('Nested', $html);
    }

    public function test_component_with_attributes()
    {
        $html = Blade::render(
            '<x-neura::button variant="primary" size="lg" class="custom">Click</x-neura::button>'
        );

        $this->assertStringContainsString('Click', $html);
        $this->assertStringContainsString('custom', $html);
    }

    public function test_input_component_works()
    {
        $html = Blade::render('<x-neura::input name="email" type="email" />');

        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('type="email"', $html);
    }

    public function test_textarea_component_works()
    {
        $html = Blade::render('<x-neura::textarea name="message" />');

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('textarea', $html);
    }
}
