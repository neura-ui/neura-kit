<?php

declare(strict_types=1);

namespace Neura\Kit\Console;

use Illuminate\Console\Command;
use Neura\Kit\Exceptions\LicenseException;
use Neura\Kit\Services\License\LicenseService;

class ActivateCommand extends Command
{
    protected $signature = 'neura:activate';

    protected $description = 'Activate Neura Kit license';

    public function handle(LicenseService $licenseService): int
    {
        $licenseKey = getenv('NEURA_KIT_LICENSE_KEY');

        if (empty($licenseKey)) {
            $this->error('NEURA_KIT_LICENSE_KEY environment variable is not set.');
            $this->line('');
            $this->line('Please set the license key in your .env file:');
            $this->line('NEURA_KIT_LICENSE_KEY=your-license-key-here');
            return self::FAILURE;
        }

        $this->info('Activating Neura Kit license...');
        $this->line('');

        try {
            $license = $licenseService->activate($licenseKey);

            $this->info('✅ License activated successfully!');
            $this->line('');
            $this->line('Plan: ' . ($license['plan'] ?? 'Unknown'));

            if (isset($license['expires_at'])) {
                $this->line('Updates expire: ' . $license['expires_at']);
            }

            if (isset($license['project_limit'])) {
                $this->line('Project limit: ' . $license['project_limit']);
            }

            return self::SUCCESS;
        } catch (LicenseException $e) {
            $this->error('❌ Activation failed: ' . $e->getMessage());
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('❌ Unexpected error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

