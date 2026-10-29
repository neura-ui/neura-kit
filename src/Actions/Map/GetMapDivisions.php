<?php

namespace Neura\Kit\Actions\Map;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Neura\Kit\Services\Map\DivisionStore;
use Neura\Kit\Services\Map\DivisionsUnavailable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Serve the subdivisions of a country at a level, or those inside one division
 * (`?within=FR-18&of=states`). The first request for a country prepares its files, which can
 * take a while; every later one is a file from disk.
 */
class GetMapDivisions
{
    public function __construct(
        private readonly DivisionStore $store,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request, string $country, string $level): BinaryFileResponse|JsonResponse
    {
        $within = $request->query('within');
        $of = $request->query('of', 'states');

        if (($within !== null && (! is_string($within) || strlen($within) > 100)) || ! is_string($of)) {
            return response()->json(['message' => 'Invalid division.'], 422);
        }

        // Preparing a country downloads and cuts its source once.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $path = $within !== null && $within !== ''
                ? $this->store->within($country, $level, $of, $within)
                : $this->store->level($country, $level);
        } catch (DivisionsUnavailable $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            $this->logger->error('Map divisions could not be prepared', [
                'country' => $country,
                'level' => $level,
                'within' => $within,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'The boundaries could not be prepared.'], 502);
        }

        return response()->file($path, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
