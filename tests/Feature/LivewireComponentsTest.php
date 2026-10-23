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

        $html = Blade::render('<x-neura::modal-manager :components="$components" />', [
            'components' => $components,
        ]);

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager()"', $html);
        $this->assertStringContainsString('test-modal', $html);
    }

    public function test_modal_manager_with_empty_components()
    {
        $html = Blade::render('<x-neura::modal-manager :components="[]" />');

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager()"', $html);
    }

    /**
     * The panel slides from its edge on the first open and the last close, the manager holds
     * until that slide has ended, and stacked panels slide by the direction the stack moves.
     */
    public function test_sideover_manager_slides_stacked_panels_without_waiting()
    {
        $html = Blade::render('<x-neura::sideover-manager :components="$components" />', [
            'components' => ['test-sideover' => ['name' => 'test-component', 'arguments' => [], 'sideoverAttributes' => ['side' => 'left']]],
        ]);

        $this->assertStringContainsString('x-transition:leave="nk-sideover-hold"', $html);
        $this->assertStringContainsString('x-transition:enter-start="nk-sideover-enter-from"', $html);
        $this->assertStringContainsString('data-sideover-side="left"', $html);
        $this->assertStringContainsString('x-bind:data-stack="stackDirection"', $html);
        $this->assertStringNotContainsString('isTransitioning', $html);
    }

    /**
     * The backdrop fades, the dialog zooms on the first open and the last close, and stacked
     * panels slide by the direction the stack moves: no panel waits for another to vanish.
     */
    public function test_modal_manager_animates_backdrop_dialog_and_stacked_panels_apart()
    {
        $html = Blade::render('<x-neura::modal-manager :components="$components" />', [
            'components' => ['test-modal' => ['name' => 'test-component', 'arguments' => []]],
        ]);

        $this->assertStringContainsString('x-transition:enter="nk-modal-fade-enter"', $html);
        $this->assertStringContainsString('x-transition:leave="nk-modal-zoom-leave"', $html);
        $this->assertStringContainsString('x-transition:enter-start="nk-modal-step-enter-from"', $html);
        $this->assertStringContainsString('x-bind:data-stack="stackDirection"', $html);
        $this->assertStringNotContainsString('isTransitioning', $html);
    }

    public function test_table_livewire_column_with_component()
    {
        $extraAttributes = [
            'component' => 'test-livewire-component',
            'props' => ['test' => 'value'],
        ];

        $html = Blade::render(
            '<x-neura::table.columns.livewire :value="\'test-value\'" :row="null" :column="null" :extraAttributes="$extraAttributes" />',
            [
                'extraAttributes' => $extraAttributes,
            ]
        );

        $this->assertNotEmpty($html);
    }

    public function test_table_livewire_column_without_component()
    {
        $html = Blade::render(
            '<x-neura::table.columns.livewire :value="\'test-value\'" :row="null" :column="null" :extraAttributes="[]" />'
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

        $html = Blade::render('<x-neura::modal-manager :components="$components" />', [
            'components' => $components,
        ]);

        $this->assertStringContainsString('wire:id', $html);
    }

    public function test_modal_manager_has_wire_key()
    {
        $components = [
            'test-modal' => [
                'name' => 'test-component',
                'arguments' => [],
            ],
        ];

        $html = Blade::render('<x-neura::modal-manager :components="$components" />', [
            'components' => $components,
        ]);

        $this->assertStringContainsString('wire:key', $html);
    }
}
