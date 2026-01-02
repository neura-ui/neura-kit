<?php

declare(strict_types=1);

namespace Neura\Kit\Services;

use Throwable;
use function htmlspecialchars;
use function json_encode;
use function sprintf;
use function view;

final class NeuraKitService
{
    /**
     * @throws Throwable
     */
    public function renderManagers(): string
    {
        $view = 'neura-kit::components.neura-kit-managers';

        return view()->exists($view) ? view($view)->render() : '';
    }

    public function openModal(array $params = []): string
    {
        $component = $params['component'] ?? null;

        if (! $component) {
            return '';
        }

        $args = $params['arguments'] ?? [];
        $modalAttributes = $params['modalAttributes'] ?? [];

        return sprintf(
            'window.NeuraKitModal.open(%s, %s, %s);',
            $this->json($component),
            $this->json($args),
            $this->json($modalAttributes),
        );
    }

    private function json(mixed $value): string
    {
        return htmlspecialchars((string) json_encode($value), ENT_QUOTES, 'UTF-8');
    }
}
