<?php

declare(strict_types=1);

namespace Neura\Kit\Console;

use Illuminate\Console\Command;
use Neura\Kit\Services\License\LicenseService;

class LicenseStatusCommand extends Command
{
    protected $signature = 'neura-kit:license-status';

    protected $description = 'Check Neura Kit license status';

    public function handle(LicenseService $licenseService): int
    {
        $this->info('Neura Kit License Status');
        $this->line('');

        if (! $licenseService->isActivated()) {
            $this->error('❌ License is not activated');
            $this->line('');
            $this->line('To activate your license, run:');
            $this->line('php artisan neura-kit:activate');

            return self::FAILURE;
        }

        $license = $licenseService->getLicense();

        if (! $license) {
            $this->error('❌ License data is invalid');

            return self::FAILURE;
        }

        $this->info('✅ License is activated');
        $this->line('');

        $this->line('Plan: '.($license['plan'] ?? 'Unknown'));

        if (isset($license['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($license['expires_at']);
            $isExpired = $expiresAt->isPast();

            if ($isExpired) {
                $this->warn('⚠️  Updates expired: '.$license['expires_at']);
                $this->line('');
                $this->line('Your license has expired. Existing projects will continue to work,');
                $this->line('but new installs and updates are blocked. Please renew your license.');
            } else {
                $this->info('Updates expire: '.$license['expires_at']);
            }
        }

        if (isset($license['project_limit'])) {
            $this->line('Project limit: '.($license['project_limit'] ?? 'Unlimited'));
        }

        $environment = $license['environment'] ?? 'local';
        $assignedProjects = $license['assigned_projects'] ?? [];
        $currentProject = $license['project_identifier'] ?? null;

        if ($environment === 'production') {
            $count = count($assignedProjects);
            $this->line('Assigned projects: '.$count);
            if ($currentProject && ! in_array($currentProject, $assignedProjects)) {
                $this->warn('⚠️  Current project is not in assigned projects list');
            }
        } else {
            $this->line('Environment: '.$environment.' (does not consume project slots)');
            if ($currentProject) {
                $this->line('Current project: '.substr($currentProject, 0, 16).'...');
            }
        }

        if (isset($license['features']) && is_array($license['features'])) {
            $this->line('Features: '.implode(', ', $license['features']));
        }

        return self::SUCCESS;
    }
}
