<?php

declare(strict_types=1);

namespace Neura\Kit\Concerns;

use Neura\Kit\Support\Dialog\DialogCall;
use Neura\Kit\Support\Modal\ModalCall;
use Neura\Kit\Support\Modal\ModalComponent;
use Neura\Kit\Support\Toast\ToastCall;

trait InteractsWithNeuraKit
{
    public function toast(?string $content = null): ToastCall
    {
        return new ToastCall($this, $content);
    }

    public function modal(string $modal): ModalCall
    {
        return new ModalCall($this, $modal);
    }

    public function dialog(?string $title): DialogCall
    {
        return new DialogCall($this, $title);
    }

}
