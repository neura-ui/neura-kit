<?php

declare(strict_types=1);

namespace Neura\Kit\Middleware;

use Closure;
use Illuminate\Http\Request;
use Neura\Kit\Services\License\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class LicenseMiddleware
{
    public function __construct(
        private LicenseService $licenseService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->licenseService->isActivated()) {
            return $next($request);
        }

        return $next($request);
    }
}
