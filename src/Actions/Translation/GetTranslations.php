<?php

namespace Neura\Kit\Actions\Translation;

use Illuminate\Http\JsonResponse;

/**
 * Serve the kit's client-side translation bundle for a locale.
 *
 * The response is cached hard and tagged with an ETag: the strings only change
 * on deploy, so browsers should not re-fetch them on every page.
 */
class GetTranslations
{
    public function __invoke(string $locale): JsonResponse
    {
        $translations = $this->load($this->sanitizeLocale($locale));

        if ($translations === []) {
            return response()->json([], 404);
        }

        $etag = '"'.md5((string) json_encode($translations, JSON_UNESCAPED_UNICODE)).'"';

        if (request()->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json($translations, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'ETag' => $etag,
        ]);
    }

    /** The locale reaches the filesystem, so strip anything path-like. */
    private function sanitizeLocale(string $locale): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', $locale) ?? '';
    }

    /**
     * First readable bundle wins: the app's own overrides come before the
     * kit's, and English is the fallback for both.
     *
     * @return array<string, mixed>
     */
    private function load(string $locale): array
    {
        $candidates = [
            resource_path("lang/{$locale}.json"),
            resource_path('lang/en.json'),
            __DIR__."/../../../resources/lang/{$locale}.json",
            __DIR__.'/../../../resources/lang/en.json',
        ];

        foreach ($candidates as $path) {
            if (! file_exists($path)) {
                continue;
            }

            $translations = json_decode((string) file_get_contents($path), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($translations)) {
                return $translations;
            }
        }

        return [];
    }
}
