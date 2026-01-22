<?php

namespace Neura\Kit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Neura\Kit\Services\Editor\ImageStorageService;
use Neura\Kit\Services\Editor\UrlMetadataService;
use Psr\Log\LoggerInterface;

/**
 * Controller for Editor.js image uploads and URL metadata
 */
class EditorImageController extends Controller
{
    public function __construct(
        private readonly ImageStorageService $imageStorage,
        private readonly UrlMetadataService $urlMetadata,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Upload image for Editor.js
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            // Log the upload attempt
            $this->logger->info('Image upload attempt', [
                'has_file' => $request->hasFile('image'),
                'file_size' => $request->hasFile('image') ? $request->file('image')->getSize() : null,
                'mime_type' => $request->hasFile('image') ? $request->file('image')->getMimeType() : null,
            ]);

            $validated = $this->validateImageRequest($request);
            $result = $this->imageStorage->store($validated['image']);

            // Log successful upload
            $this->logger->info('Image uploaded successfully', [
                'url' => $result['url'],
                'path' => $result['path'],
            ]);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $result['url'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                ],
                // Editor.js also expects these at root level
                'url' => $result['url'],
                'width' => $result['width'],
                'height' => $result['height'],
            ]);

        } catch (ValidationException $e) {
            $this->logger->warning('Image upload validation failed', [
                'errors' => $e->validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => 0,
                'message' => $e->validator->errors()->first(),
            ], 422);

        } catch (\RuntimeException $e) {
            $this->logger->error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => 0,
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to upload image',
            ], 500);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during image upload', [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => 0,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Fetch URL metadata for LinkTool
     */
    public function fetchUrl(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateUrlRequest($request);
            $metadata = $this->urlMetadata->fetch($validated['url']);

            return response()->json([
                'success' => 1,
                'meta' => [
                    'title' => $metadata['title'],
                    'description' => $metadata['description'],
                    'image' => [
                        'url' => $metadata['image'],
                    ],
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => 0,
                'message' => $e->validator->errors()->first(),
            ], 422);

        } catch (\Exception $e) {
            $this->logger->error('URL metadata fetch failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => 0,
                'message' => 'Failed to fetch URL metadata',
            ], 500);
        }
    }

    /**
     * Validate image upload request
     *
     * @throws ValidationException
     */
    protected function validateImageRequest(Request $request): array
    {
        $maxSize = config('neura-kit.editor.max_image_size', 10240); // KB

        return Validator::make($request->all(), [
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                "max:{$maxSize}",
            ],
        ])->validate();
    }

    /**
     * Validate URL fetch request
     *
     * @throws ValidationException
     */
    protected function validateUrlRequest(Request $request): array
    {
        return Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:2048'],
        ])->validate();
    }
}
