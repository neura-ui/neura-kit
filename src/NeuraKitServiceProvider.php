<?php

namespace Neura\Kit;

use Exception;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Livewire\Livewire;
use Neura\Kit\Contracts\LicenseVerifier;
use Neura\Kit\Services\License\ActivationClient;
use Neura\Kit\Services\License\DomainDetector;
use Neura\Kit\Services\License\EnvironmentDetector;
use Neura\Kit\Services\License\LicenseCache;
use Neura\Kit\Services\License\LicenseService;
use Neura\Kit\Services\License\LicenseValidator;

class NeuraKitServiceProvider extends ServiceProvider
{
    private ?bool $licenseActivatedCache = null;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/neura-kit.php', 'neura-kit');

        $this->app->singleton(ActivationClient::class, function () {
            $serverUrl = config('neura-kit.license_api_url') ?: getenv('NEURA_KIT_LICENSE_API_URL');

            if (empty($serverUrl)) {
                throw new InvalidArgumentException(
                    'LICENSE_SERVER_URL is not configured. ' .
                    'Add LICENSE_SERVER_URL=https://your-license-server.com to your .env'
                );
            }

            return new ActivationClient($serverUrl);
        });

        $this->app->singleton(LicenseCache::class);
        $this->app->singleton(LicenseValidator::class);
        $this->app->singleton(LicenseVerifier::class, LicenseValidator::class);

        $this->app->singleton(EnvironmentDetector::class);
        $this->app->singleton(DomainDetector::class);

        $this->app->singleton(LicenseService::class, function ($app) {
            return new LicenseService(
                client: $app->make(ActivationClient::class),
                cache: $app->make(LicenseCache::class),
                environmentDetector: $app->make(EnvironmentDetector::class),
                domainDetector: $app->make(DomainDetector::class),
            );
        });

        $this->app->alias(LicenseService::class, 'neura.license');

        $this->app->singleton('neura.compiler', function ($app) {
            return new NeuraTagCompiler(
                $app['blade.compiler']->getClassComponentAliases(),
                $app['blade.compiler']->getClassComponentNamespaces(),
                $app['blade.compiler']
            );
        });
    }

    public function boot(): void {
        if ($this->isFirstPartyPlatform()) {
            $this->bootFullFeatures();
            return;
        }

        $this->registerActivateCommand();

        if (!$this->isLicenseActivated()) {
            $this->configureUnlicensedState();
            return;
        }

        $this->bootFullFeatures();
    }

    protected function isFirstPartyPlatform(): bool {
        $basePackagePath = base_path('neura-kit');
        $vendorPackagePath = base_path('vendor/neura-ui/neura-kit');

        if (file_exists($basePackagePath) && is_link($vendorPackagePath)) {
            return true;
        }

        return false;
    }

    protected function bootFullFeatures(): void {
        $this->configurePublishing();
        $this->registerHelpers();
        $this->bootComponentPath();
        $this->bootTagCompiler();
        $this->configureComponents();
        $this->registerRoutes();
    }

    protected function configurePublishing(): void {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\LicenseStatusCommand::class,
            Console\InstallNeuraKitCommand::class,
            Console\InstallDependenciesCommand::class,
            Console\MakeModalCommand::class,
            Console\MakeTableCommand::class,
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

    protected function registerHelpers(): void {
        if (!function_exists('neura_trans')) {
            require_once __DIR__.'/Helpers.php';
        }
    }

    protected function bootComponentPath(): void {
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

    protected function bootTagCompiler(): void {
        $compiler = $this->app->make('neura.compiler');

        app('blade.compiler')->precompiler(function ($string) use ($compiler) {
            return $compiler->compile($string);
        });
    }

    protected function configureComponents(): void {
        if (class_exists(Livewire::class)) {
            Livewire::component('neura-kit.modal-manager', Components\Atoms\ModalManager::class);
        }

        Blade::directive('neuraKit', function () {
            return "<?php echo view('neura-kit::components.neura-kit-managers')->render(); ?>";
        });
    }

    protected function registerRoutes(): void {
        /** @var CachesRoutes $app */
        $app = $this->app;
        if ($app->routesAreCached()) {
            return;
        }

        Route::get('/neura-kit/lang/{locale}.json', Http\Controllers\TranslationsController::class)
            ->name('neura-kit.translations');
    }

    protected function registerActivateCommand(): void {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\ActivateCommand::class,
        ]);
    }

    protected function isLicenseActivated(): bool {
        if ($this->licenseActivatedCache !== null) {
            return $this->licenseActivatedCache;
        }

        try {
            $this->licenseActivatedCache = $this->app->make(LicenseService::class)->isActivated();
        } catch (Exception $e) {
            $this->licenseActivatedCache = false;
        }

        return $this->licenseActivatedCache;
    }

    protected function configureUnlicensedState(): void {
        Blade::directive('neuraKit', function () {
            return '';
        });
    }
}
