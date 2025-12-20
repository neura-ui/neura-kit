<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class LicenseCache
{
    private const LICENSE_CACHE_PATH = 'neura-kit/license.json';

    public function get(): ?array
    {
        $fileLicense = $this->getFromFile();
        $dbLicense = $this->getFromDatabase();

        if ($fileLicense) {
            return $fileLicense;
        }

        if ($dbLicense) {
            return $dbLicense;
        }

        return null;
    }

    public function put(array $licenseData): void
    {
        $this->saveToDatabase($licenseData);
        $this->saveToFile($licenseData);
    }

    public function forget(): void
    {
        $this->clearDatabase();
        $this->clearFile();
    }

    private function getFromFile(): ?array
    {
        $path = storage_path('app/' . self::LICENSE_CACHE_PATH);

        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $license = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($license)) {
            return null;
        }

        return $license;
    }

    private function getFromDatabase(): ?array
    {
        try {
            if (!$this->hasDatabaseSupport()) {
                return null;
            }

            $projectIdentifier = $this->getProjectIdentifier();
            $license = DB::table('neura_kit_licenses')
                ->where('project_identifier', $projectIdentifier)
                ->first();

            if (!$license) {
                return null;
            }

            return [
                'license_key' => $license->license_key,
                'project_identifier' => $license->project_identifier,
                'environment' => $license->environment,
                'primary_domain' => $license->primary_domain,
                'plan' => $license->plan,
                'project_limit' => $license->project_limit,
                'assigned_projects' => json_decode($license->assigned_projects ?? '[]', true),
                'expires_at' => $license->expires_at,
                'features' => json_decode($license->features ?? '[]', true),
                'signature' => $license->signature,
                'system_metadata' => json_decode($license->system_metadata ?? '{}', true),
                'package_info' => json_decode($license->package_info ?? '{}', true),
                'activated_at' => $license->activated_at,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function saveToFile(array $licenseData): void
    {
        try {
            $directory = storage_path('app/neura-kit');

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $path = storage_path('app/' . self::LICENSE_CACHE_PATH);
            File::put($path, json_encode($licenseData, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
        }
    }

    private function saveToDatabase(array $licenseData): void
    {
        try {
            if (!$this->hasDatabaseSupport()) {
                return;
            }

            $projectIdentifier = $licenseData['project_identifier'] ?? $this->getProjectIdentifier();

            DB::table('neura_kit_licenses')->updateOrInsert(
                [
                    'license_key' => $licenseData['license_key'] ?? env('NEURA_KIT_LICENSE_KEY'),
                    'project_identifier' => $projectIdentifier,
                ],
                [
                    'environment' => $licenseData['environment'] ?? config('app.env', 'local'),
                    'primary_domain' => $licenseData['primary_domain'] ?? null,
                    'plan' => $licenseData['plan'] ?? null,
                    'project_limit' => $licenseData['project_limit'] ?? null,
                    'assigned_projects' => json_encode($licenseData['assigned_projects'] ?? []),
                    'expires_at' => $licenseData['expires_at'] ?? null,
                    'features' => json_encode($licenseData['features'] ?? []),
                    'signature' => $licenseData['signature'] ?? '',
                    'system_metadata' => json_encode($licenseData['system_metadata'] ?? []),
                    'package_info' => json_encode($licenseData['package'] ?? []),
                    'activated_at' => $licenseData['activated_at'] ?? now(),
                    'last_validated_at' => now(),
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        } catch (\Exception $e) {
        }
    }

    private function clearFile(): void
    {
        $path = storage_path('app/' . self::LICENSE_CACHE_PATH);
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function clearDatabase(): void
    {
        try {
            if (!$this->hasDatabaseSupport()) {
                return;
            }

            $projectIdentifier = $this->getProjectIdentifier();
            DB::table('neura_kit_licenses')
                ->where('project_identifier', $projectIdentifier)
                ->delete();
        } catch (\Exception $e) {
        }
    }

    private function hasDatabaseSupport(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('neura_kit_licenses');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getProjectIdentifier(): string
    {
        return hash('sha256', base_path());
    }
}

