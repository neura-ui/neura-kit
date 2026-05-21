<?php

namespace Neura\Kit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Neura\Kit\Services\Upload\ChunkAssemblerService;
use Neura\Kit\Services\Upload\FileNameSanitizerService;
use Neura\Kit\Support\Security\UploadMimeValidator;
use Psr\Log\LoggerInterface;

/**
 * Controller for chunked file uploads
 */
class ChunkController extends Controller
{
    public function __construct(
        private readonly ChunkAssemblerService $assembler,
        private readonly FileNameSanitizerService $sanitizer,
        private readonly UploadMimeValidator $mimeValidator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Upload chunk endpoint
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequest($request);

            $this->mimeValidator->assertAllowed($validated['chunk']);

            // Validate file size
            $this->validateFileSize($validated['fileSize']);
            
            // Sanitize filename
            $fileName = $this->sanitizer->sanitize($validated['fileName']);
            
            // Store chunk
            $result = $this->storeChunk(
                $validated['chunk'],
                $validated['chunkIndex'],
                $validated['totalChunks'],
                $validated['uuid'],
                $fileName,
                $validated['fileSize']
            );

            return response()->json($result);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\RuntimeException $e) {
            $this->logger->error('Chunk upload failed', [
                'error' => $e->getMessage(),
            ]);

            $statusCode = str_contains($e->getMessage(), 'exceeds maximum') ? 413 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during chunk upload', [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
            ], 500);
        }
    }

    /**
     * Get uploaded file metadata
     */
    public function getFile(string $uuid): JsonResponse
    {
        try {
            $disk = config('neura-kit.upload.disk', 'local');
            $path = "livewire-tmp/{$uuid}";
            $metaPath = "{$path}.meta";

            if (!Storage::disk($disk)->exists($path) || !Storage::disk($disk)->exists($metaPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or expired',
                ], 404);
            }

            $metadata = json_decode(Storage::disk($disk)->get($metaPath), true);

            return response()->json([
                'success' => true,
                'data' => $metadata,
            ]);

        } catch (\Exception $e) {
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

    /**
     * Store chunk and optionally assemble
     *
     * @return array{success: bool, message: string, data?: array}
     */
    protected function storeChunk(
        $chunk,
        int $chunkIndex,
        int $totalChunks,
        string $uuid,
        string $fileName,
        int $fileSize
    ): array {
        $tmpDisk = config('neura-kit.upload.disk', 'local');
        $chunkDir = "livewire-tmp/chunks/{$uuid}";

        // Store the chunk
        Storage::disk($tmpDisk)->putFileAs(
            $chunkDir,
            $chunk,
            "chunk_{$chunkIndex}"
        );

        // If this is the last chunk, assemble the file
        if ($chunkIndex === $totalChunks - 1) {
            $result = $this->assembler->assemble(
                $tmpDisk,
                $chunkDir,
                $fileName,
                $totalChunks,
                $uuid
            );

            $assembledPath = Storage::disk($tmpDisk)->path($result['path']);
            $this->mimeValidator->assertAllowed($assembledPath);

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result,
            ];
        }

        // Return progress for intermediate chunks
        return [
            'success' => true,
            'message' => "Chunk {$chunkIndex} uploaded successfully",
        ];
    }

    /**
     * Validate chunk upload request
     *
     * @throws ValidationException
     */
    protected function validateRequest(Request $request): array
    {
        return Validator::make($request->all(), [
            'chunk' => ['required', 'file'],
            'chunkIndex' => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'uuid' => ['required', 'string', 'uuid'],
            'fileName' => ['required', 'string', 'max:255'],
            'fileSize' => ['required', 'integer', 'min:1'],
            'field' => ['nullable', 'string', 'max:100'],
        ])->validate();
    }

    /**
     * Validate file size
     *
     * @throws \RuntimeException
     */
    protected function validateFileSize(int $fileSize): void
    {
        $maxSize = config('neura-kit.upload.max_size', 100); // MB
        $maxBytes = $maxSize * 1024 * 1024;

        if ($fileSize > $maxBytes) {
            throw new \RuntimeException("File size exceeds maximum allowed size of {$maxSize}MB");
        }
    }
}
