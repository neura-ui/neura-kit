<?php

namespace Neura\Kit;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Neura\Kit\Contracts\LicenseVerifier;
use Neura\Kit\Services\License\ActivationClient;
use Neura\Kit\Services\License\LicenseCache;
use Neura\Kit\Services\License\LicenseService;
use Neura\Kit\Services\License\LicenseValidator;

class NeuraKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/neura-kit.php', 'neura-kit');

        $this->app->singleton(LicenseCache::class);
        $this->app->singleton(LicenseValidator::class);
        $this->app->singleton(ActivationClient::class);
        $this->app->singleton(LicenseVerifier::class, LicenseValidator::class);

        $this->app->singleton(LicenseService::class, function ($app) {
            return new LicenseService(
                $app->make(LicenseCache::class),
                $app->make(LicenseValidator::class),
                $app->make(ActivationClient::class)
            );
        });

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

        if (!$this->isLicenseActivated()) {
            return;
        }

        $this->registerHelpers();
        $this->registerRoutes();
        $this->bootComponentPath();
        $this->bootTagCompiler();
        $this->configureComponents();
    }

    protected function registerRoutes(): void
    {
         /** @var CachesRoutes $app */
         $app = $this->app;
        if ($app->routesAreCached()) {
            return;
        }

        Route::get('/neura-kit/lang/{locale}.json', function ($locale) {
            $locale = $this->sanitizeLocale($locale);
            $translations = $this->loadTranslations($locale);

            if (empty($translations)) {
                return response()->json([], 404);
            }

            return response()->json($translations, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        })->name('neura-kit.translations');
    }

    protected function sanitizeLocale(string $locale): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', $locale);
    }

    protected function loadTranslations(string $locale): array
    {
        $paths = $this->getTranslationPaths($locale);

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $contents = file_get_contents($path);
                $translations = json_decode($contents, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($translations)) {
                    return $translations;
                }
            }
        }

        return [];
    }

    protected function getTranslationPaths(string $locale): array
    {
        return [
            resource_path("lang/{$locale}.json"),
            resource_path("lang/en.json"),
            __DIR__ . "/../resources/lang/{$locale}.json",
            __DIR__ . "/../resources/lang/en.json",
        ];
    }

    protected function bootComponentPath(): void
    {
        $packageViews = realpath(__DIR__.'/../resources/views');
        $packageNeura = realpath(__DIR__.'/../resources/views/neura');
        $appNeura = resource_path('views/neura');

        if ($packageViews) {
            $this->loadViewsFrom($packageViews, 'neura-kit');
        }

        // Load app views first (higher priority)
        if (file_exists($appNeura) && is_dir($appNeura)) {
            $this->loadViewsFrom($appNeura, 'neura');
            Blade::anonymousComponentPath($appNeura, 'neura');
        }

        // Then load package views (fallback)
        if ($packageNeura) {
            $this->loadViewsFrom($packageNeura, 'neura');
            Blade::anonymousComponentPath($packageNeura, 'neura');
        }
    }

    protected function bootTagCompiler(): void
    {
        $compiler = $this->app->make('neura.compiler');

        app('blade.compiler')->precompiler(function ($string) use ($compiler) {
            return $compiler->compile($string);
        });
    }

    protected function configurePublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\ActivateCommand::class,
            Console\LicenseStatusCommand::class,
            Console\InstallNeuraKitCommand::class,
            Console\InstallDependenciesCommand::class,
            Console\MakeModalCommand::class,
            Console\MakeTableCommand::class,
        ]);

        if (!$this->isLicenseActivated()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/neura-kit.php' => config_path('neura-kit.php'),
        ], 'neura-kit-config');

        $this->publishes([
            __DIR__.'/../resources/views/neura' => resource_path('views/neura'),
        ], 'neura-kit-views');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/neura-kit'),
            __DIR__.'/../resources/css' => resource_path('css/neura-kit'),
        ], 'neura-kit-assets');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang'),
        ], 'neura-kit-lang');
    }

    protected function configureComponents(): void
    {
        if (!$this->isLicenseActivated()) {
            Blade::directive('neuraKit', function () {
                return '';
            });
            return;
        }

        Blade::directive('neuraKit', function () {
            return "<?php echo view('neura-kit::components.neura-kit-managers')->render(); ?>";
        });

        if (class_exists(Livewire::class)) {
            Livewire::component('neura-kit.modal-manager', Components\Atoms\ModalManager::class);
        }
    }

    protected function registerHelpers(): void
    {
        if (!function_exists('neura_trans')) {
            require_once __DIR__ . '/Helpers.php';
        }
    }

    protected function isLicenseActivated(): bool
    {
        try {
            return $this->app->make(LicenseService::class)->isActivated();
        } catch (\Exception $e) {
            return false;
        }
    }
}
