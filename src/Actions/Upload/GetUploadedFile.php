<?php

namespace Neura\Kit\Actions\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Return the metadata written when a chunked upload was assembled.
 */
class GetUploadedFile
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $disk = config('neura-kit.upload.disk', 'local');
            $path = "livewire-tmp/{$uuid}";
            $metaPath = "{$path}.meta";

            if (! Storage::disk($disk)->exists($path) || ! Storage::disk($disk)->exists($metaPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or expired',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => json_decode(Storage::disk($disk)->get($metaPath), true),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to retrieve file metadata', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve file',
            ], 500);
        }
    }
}
