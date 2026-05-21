<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Neura\Kit\Tests\TestCase;

class ComponentPathTest extends TestCase
{
    protected function tearDown(): void
    {
        $publishedPath = resource_path('views/neura');

        if (File::exists($publishedPath)) {
            File::deleteDirectory($publishedPath);
        }

        parent::tearDown();
    }

    public function test_components_work_with_package_views()
    {
        $html = Blade::render('<x-neura::button>Test</x-neura::button>');

        $this->assertStringContainsString('Test', $html);
    }

    public function test_components_work_with_published_views()
    {
        $packagePath = realpath(__DIR__.'/../../resources/views/neura');
        $publishedPath = resource_path('views/neura');

        if ($packagePath && ! File::exists($publishedPath)) {
            File::makeDirectory($publishedPath, 0755, true);
            File::copyDirectory($packagePath, $publishedPath);
        }

        $this->refreshApplication();

        $html = Blade::render('<x-neura::button>Published Test</x-neura::button>');

        $this->assertStringContainsString('Published Test', $html);
    }

    public function test_component_path_resolution()
    {
        $packageViewsPath = realpath(__DIR__.'/../../resources/views');

        $this->assertTrue(
            is_dir($packageViewsPath),
            'Package views directory should exist'
        );

        $this->assertTrue(
            is_dir($packageViewsPath.'/neura'),
            'Neura components directory should exist'
        );

        $this->assertTrue(
            is_file($packageViewsPath.'/neura/button/index.blade.php'),
            'Button component should exist'
        );
    }

    public function test_all_major_components_are_accessible()
    {
        $hasHeroicons = class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class);

        $components = [
            'button' => ['requiresHeroicons' => false],
            'input' => ['requiresHeroicons' => false],
            'icon' => ['requiresHeroicons' => true],
            'select' => ['requiresHeroicons' => true],
            'checkbox' => ['requiresHeroicons' => true],
            'textarea' => ['requiresHeroicons' => false],
        ];

        foreach ($components as $component => $config) {
            $requiresHeroicons = $config['requiresHeroicons'];

            if ($requiresHeroicons && ! $hasHeroicons) {
                $this->markTestSkipped("Component {$component} requires heroicons package");

                continue;
            }

            try {
                $data = $component === 'input' ? ['errors' => new \Illuminate\Support\ViewErrorBag] : [];
                $html = Blade::render("<x-neura::{$component} />", $data);
                $this->assertNotEmpty($html, "Component {$component} should render");
            } catch (\Exception $e) {
                if ($requiresHeroicons && str_contains($e->getMessage(), 'heroicons')) {
                    $this->markTestSkipped("Component {$component} requires heroicons: ".$e->getMessage());
                } else {
                    $this->fail("Component {$component} failed to render: ".$e->getMessage());
                }
            }
        }
    }
}
