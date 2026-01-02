<?php

declare(strict_types=1);

namespace Neura\Kit\Services;

use Illuminate\Support\Facades\Session;

class ToastService
{
    public function success(string $content, int $duration = 4000): void
    {
        $this->flash($content, 'success', $duration);
    }

    public function warning(string $content, int $duration = 4000): void
    {
        $this->flash($content, 'warning', $duration);
    }

    public function error(string $content, int $duration = 4000): void
    {
        $this->flash($content, 'error', $duration);
    }

    public function info(string $content, int $duration = 4000): void
    {
        $this->flash($content, 'info', $duration);
    }

    public function flash(string $content, string $type = 'info', int $duration = 4000): void
    {
        if ($content) {
            Session::flash('notify', compact('content', 'type', 'duration'));
        }
    }
}
