<?php

namespace Neura\Kit\Services\Editor;

use Paperdoc\Document\Style\PageSetup;
use Paperdoc\Enum\Format;
use Paperdoc\Enum\PageSize;
use Paperdoc\Support\DocumentManager;
use RuntimeException;

/**
 * Bridge between the native editor and paperdoc-dev/paperdoc-lib.
 *
 * Paperdoc is an optional dependency: the editor works without it and only the
 * export/import menu items go away, so everything here is guarded by
 * `isAvailable()` rather than assuming the classes exist.
 */
class PaperdocConverter
{
    /**
     * CSS pixels per inch, as the browser counts them.
     *
     * The editor lays pages out in 96dpi pixels; Paperdoc's page setup is in
     * typographic points at 72 per inch. Every geometry value crossing this
     * boundary goes through {@see pxToPoints()}.
     */
    private const CSS_DPI = 96.0;

    private const POINTS_PER_INCH = 72.0;

    public function isAvailable(): bool
    {
        return class_exists(DocumentManager::class);
    }

    /**
     * Formats the editor offers as downloads.
     *
     * @return list<string>
     */
    public function exportFormats(): array
    {
        return array_values(array_filter(
            (array) config('neura-kit.editor.export_formats', ['pdf', 'docx', 'html', 'md']),
            fn ($format) => is_string($format) && $format !== ''
        ));
    }

    /**
     * Formats the editor accepts as uploads.
     *
     * @return list<string>
     */
    public function importFormats(): array
    {
        return array_values(array_filter(
            (array) config('neura-kit.editor.import_formats', ['docx', 'html', 'md', 'txt', 'csv', 'pdf']),
            fn ($format) => is_string($format) && $format !== ''
        ));
    }

    public function mimeType(string $format): string
    {
        $case = Format::tryFrom(strtolower($format));

        return $case?->mimeType() ?? 'application/octet-stream';
    }

    /**
     * Render editor HTML to the requested format.
     *
     * @param  array<string, mixed>  $page  Page setup as the editor reports it.
     */
    public function export(string $html, string $format, string $title, array $page = []): string
    {
        $this->assertAvailable();

        $target = Format::tryFrom(strtolower($format))
            ?? throw new RuntimeException("Unsupported export format [{$format}].");

        $document = DocumentManager::openString($this->wrapHtml($html, $title), Format::HTML);
        $document->setTitle($title);

        $this->applyPageSetup($document, $page);

        return DocumentManager::renderAs($document, $target);
    }

    /**
     * Parse an uploaded file and return it as HTML for the editor to load.
     */
    public function import(string $path, string $extension): string
    {
        $this->assertAvailable();

        if (! is_readable($path)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $format = Format::tryFrom(strtolower($extension))
            ?? throw new RuntimeException("Unsupported import format [{$extension}].");

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        // Deliberately not `open($path)`: Laravel stores uploads as extension-
        // less temp files (`/tmp/phpXXXXXX`), and Paperdoc detects the format
        // from the filename. `openString` re-materialises the bytes under the
        // right extension, so detection sees what the user actually uploaded.
        $document = DocumentManager::openString($contents, $format);

        return DocumentManager::renderAs($document, Format::HTML);
    }

    /**
     * Push the editor's paper size, orientation and margins onto every section.
     *
     * @param  array<string, mixed>  $page
     */
    private function applyPageSetup(object $document, array $page): void
    {
        if (! class_exists(PageSetup::class) || ! class_exists(PageSize::class)) {
            return;
        }

        $size = PageSize::tryFrom(strtolower((string) ($page['size'] ?? 'a4'))) ?? PageSize::A4;
        $orientation = ($page['orientation'] ?? 'portrait') === 'landscape'
            ? PageSetup::ORIENTATION_LANDSCAPE
            : PageSetup::ORIENTATION_PORTRAIT;

        $margins = is_array($page['margins'] ?? null) ? $page['margins'] : [];

        $setup = PageSetup::fromSize($size, $orientation)
            ->setPaddingTop($this->pxToPoints($margins['top'] ?? 96))
            ->setPaddingRight($this->pxToPoints($margins['right'] ?? 96))
            ->setPaddingBottom($this->pxToPoints($margins['bottom'] ?? 96))
            ->setPaddingLeft($this->pxToPoints($margins['left'] ?? 96));

        $sections = method_exists($document, 'getSections') ? $document->getSections() : [];

        if ($sections === [] && method_exists($document, 'addSection')) {
            $document->addSection();
            $sections = $document->getSections();
        }

        foreach ($sections as $section) {
            if (method_exists($section, 'setPageSetup')) {
                // Each section gets its own instance: they are mutable, and
                // sharing one would tie every section's geometry together.
                $section->setPageSetup(clone $setup);
            }
        }
    }

    private function pxToPoints(mixed $px): float
    {
        return round(((float) $px) / self::CSS_DPI * self::POINTS_PER_INCH, 2);
    }

    /**
     * Wrap the editor's body HTML in a minimal document.
     *
     * The editor emits a bare run of block elements; parsers behave far more
     * predictably given a real document with a declared charset, and the title
     * carries through into PDF and DOCX metadata.
     */
    private function wrapHtml(string $body, string $title): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8"><title>{$safeTitle}</title></head>
        <body>{$body}</body>
        </html>
        HTML;
    }

    private function assertAvailable(): void
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException(
                'paperdoc-dev/paperdoc-lib is not installed; run "composer require paperdoc-dev/paperdoc-lib".'
            );
        }
    }
}
