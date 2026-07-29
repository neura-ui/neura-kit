<?php

namespace Neura\Kit\Actions\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Neura\Kit\Services\Upload\ChunkAssemblerService;
use Neura\Kit\Services\Upload\FileNameSanitizerService;
use Neura\Kit\Support\Security\UploadMimeValidator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Receive one chunk of a chunked upload, assembling the file on the last one.
 */
class UploadChunk
{
    public function __construct(
        private readonly ChunkAssemblerService $assembler,
        private readonly FileNameSanitizerService $sanitizer,
        private readonly UploadMimeValidator $mimeValidator,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request);

            $this->mimeValidator->assertAllowed($validated['chunk']);
            $this->assertWithinSizeLimit($validated['fileSize']);

            $result = $this->store(
                chunk: $validated['chunk'],
                chunkIndex: $validated['chunkIndex'],
                totalChunks: $validated['totalChunks'],
                uuid: $validated['uuid'],
                fileName: $this->sanitizer->sanitize($validated['fileName']),
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (RuntimeException $e) {
            $this->logger->error('Chunk upload failed', ['error' => $e->getMessage()]);

            // An over-size upload is a payload problem, not a server fault.
            $status = str_contains($e->getMessage(), 'exceeds maximum') ? 413 : 500;

            return response()->json(['success' => false, 'message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error during chunk upload', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Upload failed'], 500);
        }
    }

    /**
     * @throws ValidationException
     */
    private function validate(Request $request): array
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
     * @return array{success: bool, message: string, data?: array}
     */
    private function store(
        mixed $chunk,
        int $chunkIndex,
        int $totalChunks,
        string $uuid,
        string $fileName,
    ): array {
        $disk = config('neura-kit.upload.disk', 'local');
        $chunkDir = "livewire-tmp/chunks/{$uuid}";

        Storage::disk($disk)->putFileAs($chunkDir, $chunk, "chunk_{$chunkIndex}");

        if ($chunkIndex !== $totalChunks - 1) {
            return [
                'success' => true,
                'message' => "Chunk {$chunkIndex} uploaded successfully",
            ];
        }

        $result = $this->assembler->assemble($disk, $chunkDir, $fileName, $totalChunks, $uuid);

        // Re-check the assembled file: individual chunks can each look benign
        // while the whole is not.
        $this->mimeValidator->assertAllowed(Storage::disk($disk)->path($result['path']));

        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $result,
        ];
    }

    /**
     * @throws RuntimeException
     */
    private function assertWithinSizeLimit(int $fileSize): void
    {
        $maxMb = (int) config('neura-kit.upload.max_size', 100);

        if ($fileSize > $maxMb * 1024 * 1024) {
            throw new RuntimeException("File size exceeds maximum allowed size of {$maxMb}MB");
        }
    }
}
