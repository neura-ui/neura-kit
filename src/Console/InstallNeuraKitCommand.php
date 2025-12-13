<?php

namespace Neura\Kit\Console;

use Illuminate\Console\Command;

class InstallNeuraKitCommand extends Command
{
    protected $signature = 'neura-kit:install';
    
    protected $description = 'Install Neura Kit assets automatically in Vite configuration';

    public function handle(): int
    {
        $viteConfigPath = base_path('vite.config.js');
        
        if (!file_exists($viteConfigPath)) {
            $this->error('vite.config.js not found!');
            return self::FAILURE;
        }

        $viteConfig = file_get_contents($viteConfigPath);
        
        if (str_contains($viteConfig, 'neuraKit()')) {
            $this->info('Neura Kit is already configured in vite.config.js');
            return self::SUCCESS;
        }

        $pluginPath = realpath(__DIR__.'/../../resources/js/index.ts');
        
        if (!$pluginPath || !file_exists($pluginPath)) {
            $this->error('Vite plugin not found!');
            return self::FAILURE;
        }

        $vendorPath = base_path('vendor/neura/neura-kit/resources/js/index.ts');
        
        if (!file_exists($vendorPath)) {
            $this->error('Neura Kit package not found in vendor directory!');
            return self::FAILURE;
        }
        
        $pluginImport = "import neuraKit from './vendor/neura/neura-kit/resources/js/index.ts';";

        $viteConfig = $this->ensureImport($viteConfig, $pluginImport);

        if ($viteConfig === null) {
            $this->error('Could not automatically configure vite.config.js. Please add manually:');
            $this->line('');
            $this->line("1. Add import: {$pluginImport}");
            $this->line("2. Add plugin: neuraKit()");
            return self::FAILURE;
        }

        $viteConfig = $this->ensurePluginUsage($viteConfig);

        if ($viteConfig === null) {
            $this->error('Could not automatically configure vite.config.js. Please add manually:');
            $this->line('');
            $this->line("1. Add import: {$pluginImport}");
            $this->line("2. Add plugin: neuraKit()");
            return self::FAILURE;
        }

        file_put_contents($viteConfigPath, $viteConfig);
        
        $this->info('✅ Neura Kit has been automatically configured in vite.config.js');
        
        $this->configureTailwindSource();
        
        $this->info('The assets will be automatically injected without modifying app.css and app.js');
        $this->line('');
        $this->info('💡 Next step: Run "php artisan neura-kit:install-deps" to install JavaScript dependencies');
        
        return self::SUCCESS;
    }

    private function configureTailwindSource(): void
    {
        $cssPath = resource_path('css/app.css');
        
        if (!file_exists($cssPath)) {
            $this->warn('⚠️  resources/css/app.css not found. Please add manually:');
            $this->line("@source '../../vendor/neura/neura-kit/**/*.{js,ts,vue,blade.php,php}';");
            return;
        }

        $cssContent = file_get_contents($cssPath);
        $sourceDirective = "@source '../../vendor/neura/neura-kit/**/*.{js,ts,vue,blade.php,php}';";

        if (str_contains($cssContent, 'vendor/neura/neura-kit')) {
            $this->info('✅ Tailwind source directive already configured in app.css');
            return;
        }

        $lines = explode("\n", $cssContent);
        $insertIndex = 0;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '@import') || str_starts_with($trimmed, '@source') || str_starts_with($trimmed, '@tailwind')) {
                $insertIndex = $index + 1;
            }
        }

        array_splice($lines, $insertIndex, 0, [$sourceDirective]);
        
        file_put_contents($cssPath, implode("\n", $lines));
        
        $this->info('✅ Tailwind source directive added to app.css');
    }

    private function ensureImport(string $viteConfig, string $pluginImport): ?string
    {
        if (str_contains($viteConfig, 'neura-kit/resources/js/index.ts')) {
            return $viteConfig;
        }

        $lines = explode("\n", $viteConfig);
        $lastImportIndex = null;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'import ')) {
                $lastImportIndex = $index;
            }
        }

        if ($lastImportIndex === null) {
            array_unshift($lines, $pluginImport);
        } else {
            array_splice($lines, $lastImportIndex + 1, 0, [$pluginImport]);
        }

        return implode("\n", $lines);
    }

    private function ensurePluginUsage(string $viteConfig): ?string
    {
        if (str_contains($viteConfig, 'neuraKit()')) {
            return $viteConfig;
        }

        $updated = preg_replace('/plugins:\s*\[/m', "plugins: [\n        neuraKit(),\n        ", $viteConfig, 1, $count);

        return $count > 0 ? $updated : null;
    }
}

