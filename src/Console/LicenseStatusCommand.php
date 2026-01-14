<?php

declare(strict_types=1);

namespace Neura\Kit\Console;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Neura\Kit\Services\License\LicenseService;

class LicenseStatusCommand extends Command
{
    protected $signature = 'neura-kit:license:status {--refresh : Refresh the license token}';

    protected $description = 'Check Neura Kit license status';

    public function handle(LicenseService $licenseService): int
    {
        $this->displayHeader();

        if ($this->option('refresh')) {
            return $this->handleRefresh($licenseService);
        }

        if (!$licenseService->isActivated()) {
            return $this->displayNotActivated();
        }

        return $this->displayLicenseInfo($licenseService);
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║    Neura Kit License Status            ║');
        $this->info('╔════════════════════════════════════════╗');
        $this->newLine();
    }

    private function handleRefresh(LicenseService $licenseService): int
    {
        $this->line('Refreshing license token...');

        try {
            if ($licenseService->refresh()) {
                $this->info('✅ License token refreshed successfully');
                $this->newLine();
                return $this->displayLicenseInfo($licenseService);
            }

            $this->error('❌ Failed to refresh license token');
            $this->newLine();
            $this->warn('Your license may have expired or been revoked.');
            $this->line('Please contact support or reactivate your license.');

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error('❌ Error refreshing license: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayNotActivated(): int
    {
        $this->error('❌ License is not activated');
        $this->newLine();
        $this->line('To activate your license, run:');
        $this->comment('  php artisan neura-kit:activate YOUR_LICENSE_KEY');
        $this->newLine();

        return self::FAILURE;
    }

    private function displayLicenseInfo(LicenseService $licenseService): int
    {
        try {
            $this->info('✅ License is activated');
            $this->newLine();

            // Display basic token information
            $this->displayTokenInfo($licenseService);

            // Display expiration status
            $expirationStatus = $this->displayExpirationInfo($licenseService);

            // Display environment and domain
            $this->displayEnvironmentInfo($licenseService);

            $this->newLine();

            // Return appropriate status code based on expiration
            return $expirationStatus ? self::SUCCESS : self::FAILURE;

        } catch (Exception $e) {
            $this->error('❌ Error reading license data: ' . $e->getMessage());
            $this->newLine();
            $this->line('Try clearing the cache:');
            $this->comment('  php artisan cache:clear');

            return self::FAILURE;
        }
    }

    private function displayTokenInfo(LicenseService $licenseService): void
    {
        $licenseId = $licenseService->getLicenseId();
        $projectId = $licenseService->getProjectId();

        if ($licenseId) {
            $this->line("License ID: <fg=cyan>{$licenseId}</>");
        }

        if ($projectId) {
            $this->line("Project ID: <fg=cyan>{$projectId}</>");
        }
    }

    private function displayExpirationInfo(LicenseService $licenseService): bool
    {
        // Show TOKEN expiration
        $tokenExpiresAt = $licenseService->getExpiresAt();

        if ($tokenExpiresAt !== null) {
            $this->line("Token expires: <fg=cyan>{$tokenExpiresAt->toDateTimeString()}</>");
            $this->line("   ({$tokenExpiresAt->diffForHumans()})");
        }

        // Show LICENSE expiration (more important!)
        $licenseExpiresAt = $licenseService->getLicenseExpiresAt();

        if ($licenseExpiresAt === null) {
            $this->info('License: <fg=green>Perpetual (no expiration)</>'  );
            return true;
        }

        $now = Carbon::now();
        $isExpired = $licenseExpiresAt->isPast();

        if ($isExpired) {
            $this->error("❌ License expired: {$licenseExpiresAt->toDateTimeString()}");
            $this->line("   Expired {$licenseExpiresAt->diffForHumans()}");
            $this->newLine();
            $this->warn('⚠️  Your license has expired.');
            $this->line('   The library will stop working.');
            $this->line('   Please renew your license to continue using Neura Kit.');
            $this->newLine();
            $this->line('To renew, run:');
            $this->comment('  php artisan neura-kit:activate YOUR_NEW_LICENSE_KEY');

            return false;
        }

        // Show different warnings based on how close to expiration
        $daysUntilExpiration = $now->diffInDays($licenseExpiresAt);

        if ($daysUntilExpiration <= 7) {
            $this->warn("⚠️  License expires soon: {$licenseExpiresAt->toDateTimeString()}");
            $this->line("   Expires {$licenseExpiresAt->diffForHumans()}");
        } elseif ($daysUntilExpiration <= 30) {
            $this->line("License expires: <fg=yellow>{$licenseExpiresAt->toDateTimeString()}</>");
            $this->line("   ({$licenseExpiresAt->diffForHumans()})");
        } else {
            $this->line("License expires: <fg=green>{$licenseExpiresAt->toDateTimeString()}</>");
            $this->line("   ({$licenseExpiresAt->diffForHumans()})");
        }

        // Show plan and features
        $plan = $licenseService->getPlan();
        if ($plan) {
            $this->newLine();
            $this->line("Plan: <fg=cyan>{$plan}</>");
        }

        return true;
    }

    private function displayEnvironmentInfo(LicenseService $licenseService): void
    {
        $this->newLine();

        $environment = $licenseService->getEnvironment();
        $domain = $licenseService->getPrimaryDomain();

        $this->line("Environment: <fg=cyan>{$environment}</>");

        if ($domain && $domain !== 'unknown') {
            $this->line("Primary Domain: <fg=cyan>{$domain}</>");
        }

        // Show cache refresh hint
        if ($licenseService->isExpired()) {
            $this->newLine();
            $this->line('To refresh your license status, run:');
            $this->comment('  php artisan neura-kit:license:status --refresh');
        }
    }
}
