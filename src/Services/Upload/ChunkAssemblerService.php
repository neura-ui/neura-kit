<?php

namespace Neura\Kit\Services\Upload;

use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

/**
 * Service for assembling chunked file uploads
 */
class ChunkAssemblerService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Assemble chunks into a single file
     *
     * @param string $disk Storage disk
     * @param string $chunkDir Directory containing chunks
     * @param string $fileName Original filename
     * @param int $totalChunks Total number of chunks
     * @param string $uuid Unique identifier
     * @return array{uuid: string, filename: string, path: string, size: int, mime: string}
     * @throws \RuntimeException
     */
    public function assemble(
        string $disk,
        string $chunkDir,
        string $fileName,
        int $totalChunks,
        string $uuid
    ): array {
        $finalPath = "livewire-tmp/{$uuid}";
        $finalFullPath = Storage::disk($disk)->path($finalPath);

        try {
            $this->ensureDirectoryExists($finalFullPath);
            $this->assembleChunksToFile($disk, $chunkDir, $totalChunks, $finalFullPath);
            $this->cleanupChunks($disk, $chunkDir);

            $metadata = $this->createMetadata($fileName, $finalFullPath, $finalPath);
            $this->storeMetadata($disk, $finalPath, $metadata);

            return [
                'uuid' => $uuid,
                'filename' => $fileName,
                'path' => $finalPath,
                'size' => $metadata['size'],
                'mime' => $metadata['mime'],
            ];

        } catch (\Exception $e) {
            $this->cleanupChunks($disk, $chunkDir);
            
            if (isset($finalFullPath) && file_exists($finalFullPath)) {
                @unlink($finalFullPath);
            }

            throw new \RuntimeException('Failed to assemble chunks: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ensure the directory exists
     */
    protected function ensureDirectoryExists(string $path): void
    {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Assemble all chunks into a single file
     *
     * @throws \RuntimeException
     */
    protected function assembleChunksToFile(string $disk, string $chunkDir, int $totalChunks, string $outputPath): void
    {
        $output = fopen($outputPath, 'wb');
        if (!$output) {
            throw new \RuntimeException('Failed to create output file');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = Storage::disk($disk)->path("{$chunkDir}/chunk_{$i}");

                if (!file_exists($chunkPath)) {
                    throw new \RuntimeException("Chunk {$i} is missing");
                }

                $chunkHandle = fopen($chunkPath, 'rb');
                if (!$chunkHandle) {
                    throw new \RuntimeException("Failed to read chunk {$i}");
                }

                stream_copy_to_stream($chunkHandle, $output);
                fclose($chunkHandle);
            }
        } finally {
            fclose($output);
        }
    }

    /**
     * Create metadata for the assembled file
     */
    protected function createMetadata(string $fileName, string $fullPath, string $relativePath): array
    {
        return [
            'filename' => $fileName,
            'size' => filesize($fullPath),
            'mime' => mime_content_type($fullPath),
            'path' => $relativePath,
            'uploaded_at' => now()->timestamp,
        ];
    }

    /**
     * Store metadata file
     */
    protected function storeMetadata(string $disk, string $path, array $metadata): void
    {
        Storage::disk($disk)->put(
            "{$path}.meta",
            json_encode($metadata)
        );
    }

    /**
     * Cleanup chunk files
     */
    public function cleanupChunks(string $disk, string $chunkDir): void
    {
        try {
            if (Storage::disk($disk)->exists($chunkDir)) {
                Storage::disk($disk)->deleteDirectory($chunkDir);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to cleanup chunks', [
                'disk' => $disk,
                'chunkDir' => $chunkDir,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
