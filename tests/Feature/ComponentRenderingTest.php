<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

class ComponentRenderingTest extends TestCase
{
    public function test_button_component_renders_correctly()
    {
        $html = Blade::render('<x-neura::button>Click me</x-neura::button>');

        $this->assertStringContainsString('Click me', $html);
        $this->assertStringContainsString('button', $html);
    }

    public function test_button_component_with_attributes()
    {
        $html = Blade::render('<x-neura::button variant="outline" size="lg" class="custom-class">Button</x-neura::button>');

        $this->assertStringContainsString('Button', $html);
        $this->assertStringContainsString('custom-class', $html);
    }

    public function test_button_component_with_icon()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-neura::button icon="check">Save</x-neura::button>');
            $this->assertStringContainsString('Save', $html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_modal_manager_component_renders()
    {
        $html = Blade::render('<x-neura::modal-manager :components="[]" />');

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager()"', $html);
    }

    public function test_table_livewire_column_component_renders()
    {
        $html = Blade::render('<x-neura::table.columns.livewire :value="\'test-value\'" :row="null" :column="null" :extraAttributes="[\'component\' => \'test-component\']" />');

        $this->assertNotEmpty($html);
    }

    public function test_select_component_renders()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-neura::select name="test" />');
            $this->assertNotEmpty($html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_input_component_renders()
    {
        $html = Blade::render('<x-neura::input name="email" type="email" />');

        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('type="email"', $html);
    }

    public function test_checkbox_component_renders()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-neura::checkbox name="agree" />');
            $this->assertNotEmpty($html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_textarea_component_renders()
    {
        $html = Blade::render('<x-neura::textarea name="message" />');

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('textarea', $html);
    }
}
