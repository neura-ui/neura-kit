<?php

namespace Neura\Kit\Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Neura\Kit\NeuraKitServiceProvider;
use Neura\Kit\Tests\TestCase;

class NeuraKitServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered()
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(
            NeuraKitServiceProvider::class,
            $providers
        );
    }

    public function test_components_are_registered()
    {
        $this->assertTrue(
            View::exists('atoms.button.index'),
            'Button component should be registered'
        );
    }

    public function test_button_component_can_be_rendered()
    {
        $html = Blade::render('<x-atoms.button>Test Button</x-atoms.button>');

        $this->assertStringContainsString('Test Button', $html);
        $this->assertStringContainsString('data-slot="button"', $html);
    }

    public function test_button_component_with_variant()
    {
        $html = Blade::render('<x-atoms.button variant="primary">Primary</x-atoms.button>');

        $this->assertStringContainsString('Primary', $html);
    }

    public function test_icon_component_can_be_rendered()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-atoms.icon name="check" />');
            $this->assertNotEmpty($html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_input_component_can_be_rendered()
    {
        $html = Blade::render('<x-atoms.input name="test" />');

        $this->assertStringContainsString('name="test"', $html);
    }

    public function test_nested_components_work()
    {
        $html = Blade::render('<x-atoms.button.abstract>Test</x-atoms.button.abstract>');

        $this->assertStringContainsString('Test', $html);
    }
}
