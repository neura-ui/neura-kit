<?php

namespace Neura\Kit\Actions\Editor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Neura\Kit\Services\Editor\ImageStorageService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Store an image dropped or picked in the editor and hand back its URL.
 */
class UploadEditorImage
{
    public function __construct(
        private readonly ImageStorageService $imageStorage,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $this->assertWithinPhpLimits($request);

            $validated = $this->validate($request);
            $result = $this->imageStorage->store($validated['image']);

            $this->logger->info('Editor image uploaded', [
                'url' => $result['url'],
                'path' => $result['path'],
                'duration_ms' => $this->elapsed($startedAt),
            ]);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $result['url'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                ],
                // Also at the root, which is what the editor's uploader reads.
                'url' => $result['url'],
                'width' => $result['width'],
                'height' => $result['height'],
            ]);
        } catch (ValidationException $e) {
            $this->logger->warning('Editor image upload rejected', [
                'errors' => $e->validator->errors()->toArray(),
                'duration_ms' => $this->elapsed($startedAt),
            ]);

            return $this->failure($e->validator->errors()->first(), 422);
        } catch (RuntimeException $e) {
            $this->logger->error('Editor image upload failed', [
                'error' => $e->getMessage(),
                'duration_ms' => $this->elapsed($startedAt),
            ]);

            return $this->failure($this->publicMessage($e, 'Failed to upload image'), 500);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error during editor image upload', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
                'duration_ms' => $this->elapsed($startedAt),
            ]);

            return $this->failure($this->publicMessage($e, 'An unexpected error occurred'), 500);
        }
    }

    /**
     * @throws ValidationException
     *
     * @return array{image: \Illuminate\Http\UploadedFile}
     */
    private function validate(Request $request): array
    {
        $maxSize = (int) config('neura-kit.editor.max_image_size', 10240); // KB

        return Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', "max:{$maxSize}"],
        ])->validate();
    }

    /**
     * A file larger than PHP's own limits never reaches validation intact, so
     * it is worth failing with a message that names the real cause.
     */
    private function assertWithinPhpLimits(Request $request): void
    {
        $file = $request->file('image');
        if (! $file) {
            return;
        }

        $limitMb = min(
            (int) ini_get('upload_max_filesize') ?: 0,
            (int) ini_get('post_max_size') ?: 0
        );

        if ($limitMb > 0 && $file->getSize() > $limitMb * 1024 * 1024) {
            throw new RuntimeException("File size exceeds the PHP limit ({$limitMb}MB)");
        }
    }

    private function failure(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => 0, 'message' => $message], $status);
    }

    /** Internal error text is only safe to expose while debugging. */
    private function publicMessage(Throwable $e, string $fallback): string
    {
        return config('app.debug') ? $e->getMessage() : $fallback;
    }

    private function elapsed(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 2);
    }
}
