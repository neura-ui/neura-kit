<?php

declare(strict_types=1);

namespace Neura\Kit\Concerns;

use Neura\Kit\Support\Clipboard\ClipboardCall;
use Neura\Kit\Support\Dialog\DialogCall;
use Neura\Kit\Support\Modal\ModalCall;
use Neura\Kit\Support\Sideover\SideoverCall;
use Neura\Kit\Support\Spotlight\SpotlightCall;
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

    public function sideover(string $sideover): SideoverCall
    {
        return new SideoverCall($this, $sideover);
    }

    public function dialog(?string $title): DialogCall
    {
        return new DialogCall($this, $title);
    }

    public function clipboard(?string $text = null): ClipboardCall
    {
        return new ClipboardCall($this, $text);
    }

    public function spotlight(): SpotlightCall
    {
        return new SpotlightCall($this);
    }
}
