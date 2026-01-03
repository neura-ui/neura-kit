<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class LicenseCache
{
    private const string CACHE_KEY = 'license_token_data';
    private const string FILE_PATH = 'license/token.json';

    public function get(): ?array
    {
        // Try memory cache first
        $data = Cache::get(self::CACHE_KEY);

        if ($data !== null) {
            return $data;
        }

        // Try file cache
        $data = $this->getFromFile();

        if ($data !== null) {
            Cache::put(self::CACHE_KEY, $data, now()->addHours(24));
            return $data;
        }

        return null;
    }

    public function put(array $data): void
    {
        Cache::put(self::CACHE_KEY, $data, now()->addHours(24));
        $this->saveToFile($data);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->clearFile();
    }

    private function getFromFile(): ?array
    {
        $path = storage_path('app/' . self::FILE_PATH);

        if (!File::exists($path)) {
            return null;
        }

        try {
            $content = File::get($path);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return null;
            }

            return $data;
        } catch (Exception $e) {
            return null;
        }
    }

    private function saveToFile(array $data): void
    {
        try {
            $directory = storage_path('app/license');

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $path = storage_path('app/' . self::FILE_PATH);
            File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {
        }
    }

    private function clearFile(): void
    {
        $path = storage_path('app/' . self::FILE_PATH);

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
