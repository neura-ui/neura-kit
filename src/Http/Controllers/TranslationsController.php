<?php

namespace Neura\Kit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TranslationsController extends Controller
{
    public function __invoke(string $locale): JsonResponse
    {
        $locale = $this->sanitizeLocale($locale);
        $translations = $this->loadTranslations($locale);

        if (empty($translations)) {
            return response()->json([], 404);
        }

        $body = json_encode($translations, JSON_UNESCAPED_UNICODE);
        $etag = '"'.md5($body).'"';

        if (request()->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        return response()->json($translations, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'ETag' => $etag,
        ]);
    }

    protected function sanitizeLocale(string $locale): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', $locale);
    }

    protected function loadTranslations(string $locale): array
    {
        $paths = $this->getTranslationPaths($locale);

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $contents = file_get_contents($path);
                $translations = json_decode($contents, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($translations)) {
                    return $translations;
                }
            }
        }

        return [];
    }

    protected function getTranslationPaths(string $locale): array
    {
        return [
            resource_path("lang/{$locale}.json"),
            resource_path('lang/en.json'),
            __DIR__."/../../../resources/lang/{$locale}.json",
            __DIR__.'/../../../resources/lang/en.json',
        ];
    }
}
