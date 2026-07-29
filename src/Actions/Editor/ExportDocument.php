<?php

namespace Neura\Kit\Actions\Editor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neura\Kit\Services\Editor\PaperdocConverter;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Render the editor's contents to a downloadable document.
 *
 * The page setup travels with the HTML, so the produced PDF or DOCX uses the
 * same paper size, orientation and margins the document was written against.
 */
class ExportDocument
{
    public function __construct(
        private readonly PaperdocConverter $converter,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): Response|JsonResponse
    {
        if (! $this->converter->isAvailable()) {
            return response()->json([
                'message' => 'Document conversion is unavailable. Install paperdoc-dev/paperdoc-lib to enable it.',
            ], 501);
        }

        $maxBytes = (int) config('neura-kit.editor.max_export_bytes', 5_242_880);

        $data = $request->validate([
            'html' => ['required', 'string', 'max:'.$maxBytes],
            'format' => ['required', 'string', 'in:'.implode(',', $this->converter->exportFormats())],
            'title' => ['nullable', 'string', 'max:200'],
            'page.size' => ['nullable', 'string', 'in:a4,letter,legal'],
            'page.orientation' => ['nullable', 'string', 'in:portrait,landscape'],
            // Margins arrive in CSS pixels at 96dpi, matching the editor.
            'page.margins.top' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'page.margins.right' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'page.margins.bottom' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'page.margins.left' => ['nullable', 'numeric', 'min:0', 'max:400'],
        ]);

        $title = trim((string) ($data['title'] ?? '')) ?: 'document';

        try {
            $binary = $this->converter->export(
                html: $data['html'],
                format: $data['format'],
                title: $title,
                page: $data['page'] ?? [],
            );
        } catch (Throwable $e) {
            $this->logger->error('Editor export failed', [
                'format' => $data['format'],
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'The document could not be converted.'], 422);
        }

        $filename = $this->safeFilename($title).'.'.$data['format'];

        return response($binary, 200, [
            'Content-Type' => $this->converter->mimeType($data['format']),
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Reduce a user-supplied title to something safe for a filename.
     *
     * The title ends up in a Content-Disposition header, so quotes, newlines
     * and path separators all have to go — otherwise the header can be split or
     * the name can escape its directory on the client side.
     */
    private function safeFilename(string $title): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}\-_ ]+/u', '', $title) ?? '';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        return $clean === '' ? 'document' : mb_substr($clean, 0, 80);
    }
}
