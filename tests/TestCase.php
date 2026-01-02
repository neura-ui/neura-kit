<?php

namespace Neura\Kit\Tests;

use Neura\Kit\NeuraKitServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        $providers = [
            NeuraKitServiceProvider::class,
        ];

        if (class_exists(\BladeUI\Icons\BladeIconsServiceProvider::class)) {
            $providers[] = \BladeUI\Icons\BladeIconsServiceProvider::class;
        }

        if (class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $providers[] = \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class;
        }

        return $providers;
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [
            __DIR__.'/../resources/views',
            resource_path('views'),
        ]);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\BladeUI\Icons\Factory::class) && $this->app->bound(\BladeUI\Icons\Factory::class)) {
            try {
                $factory = $this->app->make(\BladeUI\Icons\Factory::class);
                $factory->registerComponents();
            } catch (\Exception $e) {
            }
        }
    }
}
