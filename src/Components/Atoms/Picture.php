<?php

namespace Neura\Kit\Components\Atoms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Picture extends Component
{
    public function __construct(
        public ?string $src = null,
        public string $alt = '',
        public string $size = 'md',
        public string $shape = 'rounded',
        public bool $lazy = true,
        public mixed $fallback = null,
        public string $objectFit = 'cover',
    ) {}

    public function render(): View|Closure|string
    {
        return view('neura::picture.index');
    }
}

