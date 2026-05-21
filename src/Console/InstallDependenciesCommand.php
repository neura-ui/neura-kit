<?php

namespace Neura\Kit\Console;

use Illuminate\Console\Command;

class InstallDependenciesCommand extends Command
{
    protected $signature = 'neura-kit:install-deps {--check : Only check dependencies without installing}';

    protected $description = 'Install missing JavaScript dependencies for Neura Kit';

    protected array $dependencies = [
        // Chart.js
        'chart.js' => '^4.0.0',
        
        // Lottie
        'lottie-web' => '^5.0.0',
        
        // Tiptap Editor (variant 1)
        '@tiptap/core' => '^2.0.0',
        '@tiptap/starter-kit' => '^2.0.0',
        '@tiptap/extension-link' => '^2.0.0',
        '@tiptap/extension-image' => '^2.0.0',
        '@tiptap/extension-placeholder' => '^2.0.0',
        '@tiptap/extension-text-align' => '^2.0.0',
        '@tiptap/extension-underline' => '^2.0.0',
        '@tiptap/extension-highlight' => '^2.0.0',
        
        // Editor.js (variant 2) - Core
        '@editorjs/editorjs' => '^2.28.0',
        '@editorjs/header' => '^2.7.0',
        '@editorjs/list' => '^1.8.0',
        '@editorjs/quote' => '^2.6.0',
        '@editorjs/code' => '^2.8.0',
        '@editorjs/image' => '^2.9.0',
        '@editorjs/inline-code' => '^1.4.0',
        
        // Editor.js - Optional but recommended tools
        '@editorjs/link' => '^2.5.0',
        '@editorjs/marker' => '^1.3.0',
        '@editorjs/delimiter' => '^1.3.0',
        '@editorjs/table' => '^2.2.0',
        
        // Alpine Flow - Workflow/flowchart UI (Alpine.js provided by Livewire)
        '@copyfactory/alpine-flow' => '^0.3.0',
    ];

    public function handle(): int
    {
        $packageJsonPath = base_path('package.json');

        if (! file_exists($packageJsonPath)) {
            $this->error('package.json not found! Please run npm init first.');

            return self::FAILURE;
        }

        $packageJson = json_decode(file_get_contents($packageJsonPath), true);

        if (! $packageJson) {
            $this->error('Invalid package.json file!');

            return self::FAILURE;
        }

        $dependencies = $packageJson['dependencies'] ?? [];
        $devDependencies = $packageJson['devDependencies'] ?? [];
        $allDeps = array_merge($dependencies, $devDependencies);

        $missing = [];
        $needsUpdate = [];

        foreach ($this->dependencies as $package => $version) {
            if (! isset($allDeps[$package])) {
                $missing[$package] = $version;
            } elseif ($this->requiresUpdate($allDeps[$package], $version)) {
                $needsUpdate[$package] = $version;
            }
        }

        if (empty($missing) && empty($needsUpdate)) {
            $this->info('✅ All Neura Kit dependencies are installed and up to date!');

            return self::SUCCESS;
        }

        if (! empty($missing)) {
            $this->warn('Missing dependencies:');
            foreach ($missing as $package => $version) {
                $this->line("  - {$package}@{$version}");
            }
        }

        if (! empty($needsUpdate)) {
            $this->warn('Dependencies that need updating:');
            foreach ($needsUpdate as $package => $version) {
                $current = $allDeps[$package];
                $this->line("  - {$package}: {$current} → {$version}");
            }
        }

        if ($this->option('check')) {
            $this->line('');
            $this->info('Run without --check to install missing dependencies.');

            return self::SUCCESS;
        }

        $this->line('');

        if (! $this->confirm('Install missing dependencies?', true)) {
            return self::SUCCESS;
        }

        $packagesToInstall = [];
        foreach ($missing as $package => $version) {
            $packagesToInstall[] = "{$package}@{$version}";
        }
        foreach ($needsUpdate as $package => $version) {
            $packagesToInstall[] = "{$package}@{$version}";
        }

        $command = $this->detectPackageManager();
        $installCommand = $this->buildInstallCommand($command, $packagesToInstall);

        $this->info("Installing dependencies using {$command}...");
        $this->line("Running: {$installCommand}");
        $this->line('');

        passthru($installCommand, $exitCode);

        if ($exitCode === 0) {
            $this->line('');
            $this->info('✅ All dependencies installed successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to install dependencies. Please install manually.');

        return self::FAILURE;
    }

    protected function detectPackageManager(): string
    {
        if (file_exists(base_path('yarn.lock'))) {
            return 'yarn';
        }

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            return 'pnpm';
        }

        return 'npm';
    }

    protected function buildInstallCommand(string $manager, array $packages): string
    {
        $packageList = implode(' ', $packages);

        return match ($manager) {
            'yarn' => "yarn add {$packageList}",
            'pnpm' => "pnpm add {$packageList}",
            default => "npm install {$packageList}",
        };
    }

    protected function requiresUpdate(string $current, string $required): bool
    {
        $normalizedCurrent = $this->normalizeVersion($current);
        $normalizedRequired = $this->normalizeVersion($required);

        if ($normalizedCurrent === null || $normalizedRequired === null) {
            return false;
        }

        return version_compare($normalizedCurrent, $normalizedRequired, '<');
    }

    protected function normalizeVersion(string $version): ?string
    {
        $trimmed = ltrim($version, '^~>=< v');
        $cleaned = preg_replace('/[^0-9.]/', '', $trimmed);

        return $cleaned !== '' ? $cleaned : null;
    }
}
