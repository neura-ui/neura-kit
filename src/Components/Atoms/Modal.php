<?php

namespace Neura\Kit\Components\Atoms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public function __construct(
        public ?string $name = null,
        public bool $open = false,
        public string $size = 'md',
        public bool $closeable = true,
        public bool $persistent = false,
        public bool $closeOnBackdrop = true,
        public bool $closeOnEscape = true,
        public ?string $entangle = null,
        public ?string $maxWidth = null,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view('neura::modal.index');
    }
}

