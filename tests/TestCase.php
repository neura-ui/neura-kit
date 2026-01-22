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

        // Set app key for encryption
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Force route loading for tests
        $this->loadRoutesFrom();

        if (class_exists(\BladeUI\Icons\Factory::class) && $this->app->bound(\BladeUI\Icons\Factory::class)) {
            try {
                $factory = $this->app->make(\BladeUI\Icons\Factory::class);
                $factory->registerComponents();
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Force routes to be loaded in tests
     */
    protected function loadRoutesFrom(): void
    {
        // Simulate route registration from service provider
        \Illuminate\Support\Facades\Route::get('/neura-kit/lang/{locale}.json', \Neura\Kit\Http\Controllers\TranslationsController::class)
            ->name('neura-kit.translations');

        \Illuminate\Support\Facades\Route::middleware(['web'])->prefix('neura-kit')->group(function () {
            \Illuminate\Support\Facades\Route::post('/upload/chunks', [\Neura\Kit\Http\Controllers\ChunkUploadController::class, 'upload'])
                ->name('neura-kit.upload.chunks');

            \Illuminate\Support\Facades\Route::get('/upload/file/{uuid}', [\Neura\Kit\Http\Controllers\ChunkUploadController::class, 'getFile'])
                ->name('neura-kit.upload.file');
        });
    }
}
