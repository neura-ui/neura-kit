<?php

namespace Neura\Kit\Actions\Editor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Neura\Kit\Services\Editor\PaperdocConverter;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Parse an uploaded document and return HTML the editor can load.
 *
 * The HTML is sanitized client-side by the editor's own schema before it is
 * inserted, so nothing from the uploaded file bypasses the allowlist.
 */
class ImportDocument
{
    public function __construct(
        private readonly PaperdocConverter $converter,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->converter->isAvailable()) {
            return response()->json([
                'message' => 'Document conversion is unavailable. Install paperdoc-dev/paperdoc-lib to enable it.',
            ], 501);
        }

        $formats = $this->converter->importFormats();
        $maxKilobytes = (int) config('neura-kit.editor.max_import_kilobytes', 20_480);

        try {
            $request->validate([
                'document' => [
                    'required',
                    'file',
                    'max:'.$maxKilobytes,
                    'mimes:'.implode(',', $formats),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first('document'),
                'accepts' => $formats,
            ], 422);
        }

        $file = $request->file('document');

        try {
            $html = $this->converter->import(
                path: $file->getRealPath(),
                extension: strtolower($file->getClientOriginalExtension()),
            );
        } catch (Throwable $e) {
            $this->logger->error('Editor import failed', [
                'extension' => $file->getClientOriginalExtension(),
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'That file could not be read.'], 422);
        }

        return response()->json([
            'html' => $html,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);
    }
}
