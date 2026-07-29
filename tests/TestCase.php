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

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('neura-kit.routes.middleware', ['web']);
        $app['config']->set('neura-kit.routes.throttle', null);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadRoutesFrom();

        if (class_exists(\BladeUI\Icons\Factory::class) && $this->app->bound(\BladeUI\Icons\Factory::class)) {
            try {
                $factory = $this->app->make(\BladeUI\Icons\Factory::class);
                $factory->registerComponents();
            } catch (\Exception $e) {
            }
        }
    }

    protected function loadRoutesFrom(): void
    {
        \Illuminate\Support\Facades\Route::get('/neura-kit/lang/{locale}.json', \Neura\Kit\Actions\Translation\GetTranslations::class)
            ->name('neura-kit.translations');

        $middleware = config('neura-kit.routes.middleware', ['web']);
        $throttle = config('neura-kit.routes.throttle');
        if (filled($throttle)) {
            $middleware[] = 'throttle:'.$throttle;
        }

        \Illuminate\Support\Facades\Route::middleware($middleware)->prefix('neura-kit')->group(function () {
            \Illuminate\Support\Facades\Route::post('/upload/chunks', \Neura\Kit\Actions\Upload\UploadChunk::class)
                ->name('neura-kit.upload.chunks');

            \Illuminate\Support\Facades\Route::get('/upload/file/{uuid}', \Neura\Kit\Actions\Upload\GetUploadedFile::class)
                ->name('neura-kit.upload.file');

            \Illuminate\Support\Facades\Route::post('/editor/upload-image', \Neura\Kit\Actions\Editor\UploadEditorImage::class)
                ->name('neura-kit.editor.upload-image');

            \Illuminate\Support\Facades\Route::post('/editor/export', \Neura\Kit\Actions\Editor\ExportDocument::class)
                ->name('neura-kit.editor.export');

            \Illuminate\Support\Facades\Route::post('/editor/import', \Neura\Kit\Actions\Editor\ImportDocument::class)
                ->name('neura-kit.editor.import');
        });
    }
}
