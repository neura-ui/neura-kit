<?php

namespace Neura\Kit\Services\Editor;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for storing and managing editor images
 */
class ImageStorageService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Store an uploaded image file
     *
     * @param UploadedFile $file
     * @param string|null $disk
     * @param string|null $path
     * @return array{path: string, url: string, width: int|null, height: int|null}
     * @throws \RuntimeException
     */
    public function store(UploadedFile $file, ?string $disk = null, ?string $path = null): array
    {
        $disk = $disk ?? config('neura-kit.editor.image_disk', 'public');
        $path = $path ?? config('neura-kit.editor.image_path', 'editor/images');

        $this->validateDisk($disk);
        $this->ensureDirectoryExists($disk, $path);

        $filename = $this->generateUniqueFilename($file);
        $storedPath = $this->storeFile($file, $disk, $path, $filename);
        $url = $this->generateUrl($disk, $storedPath);
        $dimensions = $this->getImageDimensions($file);

        return [
            'path' => $storedPath,
            'url' => $url,
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
        ];
    }

    /**
     * Validate that the disk exists in configuration
     *
     * @throws \RuntimeException
     */
    protected function validateDisk(string $disk): void
    {
        if (!array_key_exists($disk, config('filesystems.disks', []))) {
            $this->logger->error("Disk '{$disk}' not found in filesystems config");
            throw new \RuntimeException("Storage disk '{$disk}' is not configured");
        }
    }

    /**
     * Ensure the storage directory exists
     */
    protected function ensureDirectoryExists(string $disk, string $path): void
    {
        if (!Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
        }
    }

    /**
     * Generate a unique filename for the uploaded file
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $extension = preg_replace('/[^a-z0-9]/i', '', (string) $extension) ?: 'bin';
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$basename}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Store the file to disk
     *
     * @throws \RuntimeException
     */
    protected function storeFile(UploadedFile $file, string $disk, string $path, string $filename): string
    {
        try {
            // Check if file is valid
            if (!$file->isValid()) {
                $this->logger->error('Invalid uploaded file', [
                    'error' => $file->getErrorMessage(),
                    'filename' => $filename,
                ]);
                throw new \RuntimeException('Invalid file: ' . $file->getErrorMessage());
            }

            // Check disk permissions
            $storage = Storage::disk($disk);
            if (!$storage->exists($path)) {
                if (!$storage->makeDirectory($path)) {
                    $this->logger->error('Failed to create directory', [
                        'disk' => $disk,
                        'path' => $path,
                    ]);
                    throw new \RuntimeException("Failed to create directory: {$path}");
                }
            }

            // Store the file with retry logic
            $storedPath = null;
            $maxAttempts = 2;
            
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $storedPath = $storage->putFileAs($path, $file, $filename);
                    
                    if ($storedPath) {
                        // Verify file was actually stored
                        if (!$storage->exists($storedPath)) {
                            throw new \RuntimeException('File was not found after storage');
                        }
                        break;
                    }
                } catch (\Exception $e) {
                    if ($attempt === $maxAttempts) {
                        $this->logger->error('Failed to store file after retries', [
                            'disk' => $disk,
                            'path' => $path,
                            'filename' => $filename,
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                        throw new \RuntimeException('Failed to store image file: ' . $e->getMessage(), 0, $e);
                    }
                    // Wait before retry (exponential backoff)
                    usleep(500000 * $attempt); // 0.5s, 1s
                }
            }

            if (!$storedPath) {
                $this->logger->error('Failed to store file', [
                    'disk' => $disk,
                    'path' => $path,
                    'filename' => $filename,
                ]);
                throw new \RuntimeException('Failed to store image file');
            }

            return $storedPath;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error storing file', [
                'disk' => $disk,
                'path' => $path,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to store image file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate public URL for the stored file
     *
     * @throws \RuntimeException
     */
    protected function generateUrl(string $disk, string $path): string
    {
        $storage = Storage::disk($disk);

        try {
            if ($disk === 'public') {
                $url = $storage->url($path);
                // Ensure URL is absolute
                if (!str_starts_with($url, 'http')) {
                    $url = asset($url);
                }
                return $url;
            }

            if ($disk === 'local') {
                $this->logger->warning('Using local disk for images, consider using public disk for web accessibility');
                return $storage->url($path);
            }

            // For S3 and other cloud disks
            $url = $storage->url($path);

            // Fallback for S3 if url() doesn't work
            if (empty($url) && $disk === 's3') {
                $url = $this->buildS3Url($disk, $path);
            }

            return $url;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate file URL', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to generate image URL', 0, $e);
        }
    }

    /**
     * Build S3 URL manually
     */
    protected function buildS3Url(string $disk, string $path): string
    {
        $bucket = config("filesystems.disks.{$disk}.bucket");
        $region = config("filesystems.disks.{$disk}.region", 'us-east-1');
        $endpoint = config("filesystems.disks.{$disk}.endpoint");

        if ($endpoint) {
            return rtrim($endpoint, '/') . '/' . ltrim($path, '/');
        }

        if ($bucket) {
            return "https://{$bucket}.s3.{$region}.amazonaws.com/" . ltrim($path, '/');
        }

        throw new \RuntimeException('Unable to construct S3 URL: missing bucket or endpoint configuration');
    }

    /**
     * Get image dimensions
     *
     * @return array{width?: int, height?: int}
     */
    protected function getImageDimensions(UploadedFile $file): array
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());

            if ($imageInfo !== false) {
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get image dimensions', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }
}
