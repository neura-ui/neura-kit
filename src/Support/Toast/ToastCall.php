<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Toast;

use Livewire\Component;

final class ToastCall
{
    private string $content = '';
    private string $type = 'info';
    private int $duration = 4000;

    public function __construct(
        private readonly Component $caller,
        ?string $content = null
    ) {
        if ($content !== null) {
            $this->content = $content;
        }
    }

    /* -------------------------------------------------------------
     | Configuration
     |------------------------------------------------------------- */

    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function duration(int $milliseconds): self
    {
        $this->duration = $milliseconds;
        return $this;
    }

    /* -------------------------------------------------------------
     | Types
     |------------------------------------------------------------- */

    public function success(?string $content = null): void
    {
        $this->send('success', $content);
    }

    public function error(?string $content = null): void
    {
        $this->send('error', $content);
    }

    public function warning(?string $content = null): void
    {
        $this->send('warning', $content);
    }

    public function info(?string $content = null): void
    {
        $this->send('info', $content);
    }

    /* -------------------------------------------------------------
     | Internals
     |------------------------------------------------------------- */

    private function send(string $type, ?string $content): void
    {
        if ($content !== null) {
            $this->content = $content;
        }

        if ($this->content === '') {
            return;
        }

        $this->type = $type;

        $this->caller->js(
            sprintf(
                'NeuraKitToast.show(%s, %s, %d)',
                json_encode($this->content),
                json_encode($this->type),
                $this->duration
            )
        );
    }
}
