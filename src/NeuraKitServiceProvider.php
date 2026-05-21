<?php

namespace Neura\Kit;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class NeuraKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/neura-kit.php', 'neura-kit');

        $this->app->singleton('neura.compiler', function ($app) {
            return new NeuraTagCompiler(
                $app['blade.compiler']->getClassComponentAliases(),
                $app['blade.compiler']->getClassComponentNamespaces(),
                $app['blade.compiler']
            );
        });
    }

    public function boot(): void
    {
        $this->configurePublishing();
        $this->registerHelpers();
        $this->bootComponentPath();
        $this->bootTagCompiler();
        $this->configureComponents();
        $this->registerRoutes();
        $this->loadTranslations();
    }

    protected function loadTranslations(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
    }

    protected function configurePublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallNeuraKitCommand::class,
            Console\InstallDependenciesCommand::class,
            Console\MakeModalCommand::class,
            Console\MakeTableCommand::class,
            Console\MakeSpotlightCommand::class,
        ]);

        $this->publishes([
            __DIR__.'/../config/neura-kit.php' => config_path('neura-kit.php'),
        ], 'neura-config');

        $this->publishes([
            __DIR__.'/../resources/views/neura' => resource_path('views/neura'),
        ], 'neura-views');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/neura-kit'),
            __DIR__.'/../resources/css' => resource_path('css/neura-kit'),
        ], 'neura-assets');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang'),
        ], 'neura-lang');
    }

    protected function registerHelpers(): void
    {
        if (! function_exists('neura_trans')) {
            require_once __DIR__.'/Helpers.php';
        }
    }

    protected function bootComponentPath(): void
    {
        $packageViews = realpath(__DIR__.'/../resources/views');
        $packageNeura = realpath(__DIR__.'/../resources/views/neura');
        $appNeura = resource_path('views/neura');

        if ($packageViews) {
            $this->loadViewsFrom($packageViews, 'neura-kit');
        }

        if (file_exists($appNeura) && is_dir($appNeura)) {
            $this->loadViewsFrom($appNeura, 'neura');
            Blade::anonymousComponentPath($appNeura, 'neura');
        }

        if ($packageNeura) {
            $this->loadViewsFrom($packageNeura, 'neura');
            Blade::anonymousComponentPath($packageNeura, 'neura');
        }
    }

    protected function bootTagCompiler(): void
    {
        $compiler = $this->app->make('neura.compiler');

        app('blade.compiler')->precompiler(function ($string) use ($compiler) {
            if (stripos($string, '<neura::') === false) {
                return $string;
            }

            return $compiler->compile($string);
        });
    }

    protected function configureComponents(): void
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('neura-kit.modal-manager', Components\Atoms\ModalManager::class);
            Livewire::component('neura-kit.sideover-manager', Components\Atoms\SideoverManager::class);
            Livewire::component('neura-kit.spotlight-manager', Components\Atoms\SpotlightManager::class);
        }

        Blade::directive('neuraKit', function () {
            return "<?php echo view('neura-kit::components.neura-kit-managers')->render(); ?>";
        });
    }

    protected function registerRoutes(): void
    {
        /** @var CachesRoutes $app */
        $app = $this->app;
        if ($app->routesAreCached()) {
            return;
        }

        Route::get('/neura-kit/lang/{locale}.json', Http\Controllers\TranslationsController::class)
            ->name('neura-kit.translations');

        $middleware = $this->routeMiddleware();

        Route::middleware($middleware)->prefix('neura-kit')->group(function () {
            Route::post('/upload/chunks', [Http\Controllers\ChunkController::class, 'upload'])
                ->name('neura-kit.upload.chunks');

            Route::get('/upload/file/{uuid}', [Http\Controllers\ChunkController::class, 'getFile'])
                ->name('neura-kit.upload.file');

            Route::post('/editor/upload-image', [Http\Controllers\EditorImageController::class, 'uploadImage'])
                ->name('neura-kit.editor.upload-image');

            Route::post('/editor/fetch-url', [Http\Controllers\EditorImageController::class, 'fetchUrl'])
                ->name('neura-kit.editor.fetch-url');
        });
    }

    /**
     * @return list<string>
     */
    protected function routeMiddleware(): array
    {
        $configured = config('neura-kit.routes.middleware', ['web']);

        if (is_string($configured)) {
            $configured = array_filter(array_map('trim', explode(',', $configured)));
        }

        $middleware = array_values(array_filter((array) $configured));

        $throttle = config('neura-kit.routes.throttle');

        if (filled($throttle) && ! in_array('throttle:'.$throttle, $middleware, true)) {
            $middleware[] = 'throttle:'.$throttle;
        }

        return $middleware ?: ['web'];
    }
}
