<?php

namespace Neura\Kit\Tests\Unit;

use Neura\Kit\Tests\TestCase;

class ClassLoadingTest extends TestCase
{
    public function test_service_provider_can_be_loaded()
    {
        $this->assertTrue(
            class_exists(\Neura\Kit\NeuraKitServiceProvider::class),
            'NeuraKitServiceProvider should be loadable'
        );
    }

    public function test_support_classes_can_be_loaded()
    {
        $this->assertTrue(
            class_exists(\Neura\Kit\Support\Table\Column::class),
            'Column class should be loadable'
        );

        $this->assertTrue(
            class_exists(\Neura\Kit\Support\Toasts\Toast::class),
            'Toast class should be loadable'
        );

        $this->assertTrue(
            interface_exists(\Neura\Kit\Support\Modal\Contracts\ModalComponent::class),
            'ModalComponent contract should be loadable'
        );
    }

    public function test_component_classes_can_be_loaded()
    {
        $this->assertTrue(
            class_exists(\Neura\Kit\Components\Atoms\Modal::class),
            'Modal component should be loadable'
        );

        $this->assertTrue(
            class_exists(\Neura\Kit\Components\Atoms\Picture::class),
            'Picture component should be loadable'
        );
    }

    public function test_livewire_components_require_livewire()
    {
        if (!class_exists(\Livewire\Component::class)) {
            $this->markTestSkipped('Livewire is not installed. This is expected if Livewire is optional.');
            return;
        }

        $this->assertTrue(
            class_exists(\Neura\Kit\Components\Atoms\ModalManager::class),
            'ModalManager should be loadable when Livewire is installed'
        );

        $this->assertTrue(
            class_exists(\Neura\Kit\Components\Atoms\Table::class),
            'Table should be loadable when Livewire is installed'
        );

        $this->assertTrue(
            class_exists(\Neura\Kit\Support\Modal\ModalComponent::class),
            'ModalComponent should be loadable when Livewire is installed'
        );
    }

    public function test_column_class_has_static_methods()
    {
        $column = \Neura\Kit\Support\Table\Column::make('test', 'Test');
        
        $this->assertInstanceOf(\Neura\Kit\Support\Table\Column::class, $column);
        $this->assertEquals('test', $column->key);
        $this->assertEquals('Test', $column->label);
    }

    public function test_toast_class_has_static_methods()
    {
        $this->assertTrue(
            method_exists(\Neura\Kit\Support\Toasts\Toast::class, 'success'),
            'Toast should have success method'
        );

        $this->assertTrue(
            method_exists(\Neura\Kit\Support\Toasts\Toast::class, 'error'),
            'Toast should have error method'
        );

        $this->assertTrue(
            method_exists(\Neura\Kit\Support\Toasts\Toast::class, 'warning'),
            'Toast should have warning method'
        );

        $this->assertTrue(
            method_exists(\Neura\Kit\Support\Toasts\Toast::class, 'info'),
            'Toast should have info method'
        );
    }
}

