<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

class LivewireComponentsTest extends TestCase
{
    public function test_modal_manager_accepts_components_array()
    {
        $components = [
            'test-modal' => [
                'name' => 'test-component',
                'arguments' => ['id' => 1],
            ],
        ];

        $html = Blade::render('<x-atoms.modal-manager :components="$components" />', [
            'components' => $components,
        ]);

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager"', $html);
        $this->assertStringContainsString('test-modal', $html);
    }

    public function test_modal_manager_with_empty_components()
    {
        $html = Blade::render('<x-atoms.modal-manager :components="[]" />');

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager"', $html);
    }

    public function test_table_livewire_column_with_component()
    {
        $extraAttributes = [
            'component' => 'test-livewire-component',
            'props' => ['test' => 'value'],
        ];

        $html = Blade::render(
            '<x-atoms.table.columns.livewire :value="\'test-value\'" :row="null" :column="null" :extraAttributes="$extraAttributes" />',
            [
                'extraAttributes' => $extraAttributes,
            ]
        );

        $this->assertNotEmpty($html);
    }

    public function test_table_livewire_column_without_component()
    {
        $html = Blade::render(
            '<x-atoms.table.columns.livewire :value="\'test-value\'" :row="null" :column="null" :extraAttributes="[]" />'
        );

        $this->assertIsString($html);
    }

    public function test_modal_manager_has_livewire_directive()
    {
        $components = [
            'test' => [
                'name' => 'test-component',
                'arguments' => [],
            ],
        ];

        $html = Blade::render('<x-atoms.modal-manager :components="$components" />', [
            'components' => $components,
        ]);

        $this->assertStringContainsString('livewire', $html);
    }

    public function test_modal_manager_has_wire_key()
    {
        $components = [
            'test-modal' => [
                'name' => 'test-component',
                'arguments' => [],
            ],
        ];

        $html = Blade::render('<x-atoms.modal-manager :components="$components" />', [
            'components' => $components,
        ]);

        $this->assertStringContainsString('wire:key', $html);
    }
}

