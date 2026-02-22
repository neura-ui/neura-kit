<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Sideover;

use Illuminate\Contracts\Routing\UrlRoutable;
use InvalidArgumentException;
use Livewire\Component;

final class SideoverCall
{
    private array $args = [];

    private array $attrs = [];

    /**
     * @param  class-string<SideoverComponent>  $sideover
     */
    public function __construct(
        private readonly Component $caller,
        private readonly string $sideover,
    ) {
        if (! is_subclass_of($sideover, SideoverComponent::class)) {
            throw new InvalidArgumentException(sprintf(
                'Sideover [%s] must extend %s.',
                $sideover,
                SideoverComponent::class
            ));
        }

        $this->attrs['side'] ??= $sideover::sideoverSide();
        $this->attrs['width'] ??= $sideover::sideoverWidth();
        $this->syncWidthClass($this->attrs['width']);
    }

    /* -------------------------------------------------------------
     | Arguments
     |------------------------------------------------------------- */

    public function with(string $key, mixed $value): self
    {
        $this->args[$key] = $value;

        return $this;
    }

    public function withMany(array $args): self
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /* -------------------------------------------------------------
     | Attributes
     |------------------------------------------------------------- */

    public function attr(string $key, mixed $value): self
    {
        $this->attrs[$key] = $value;

        if ($key === 'width') {
            $this->syncWidthClass((string) $value);
        }

        return $this;
    }

    public function attrs(array $attrs): self
    {
        $this->attrs = array_merge($this->attrs, $attrs);

        if (isset($attrs['width']) && ! isset($attrs['widthClass'])) {
            $this->syncWidthClass((string) $attrs['width']);
        }

        return $this;
    }

    public function side(string $side): self
    {
        $this->attrs['side'] = $side;

        return $this;
    }

    public function width(string $width): self
    {
        $this->attrs['width'] = $width;
        $this->syncWidthClass($width);

        return $this;
    }

    private function syncWidthClass(string $width): void
    {
        $class = SideoverComponent::getWidthClass($width);

        if ($class !== null) {
            $this->attrs['widthClass'] = $class;
        } else {
            unset($this->attrs['widthClass']);
        }
    }

    /* -------------------------------------------------------------
     | Actions
     |------------------------------------------------------------- */

    public function open(?array $overrideArgs = null, bool $dispatch = true): string
    {
        if ($overrideArgs !== null) {
            $this->withMany($overrideArgs);
        }

        $js = $this->buildOpenJs();

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    public function close(bool $force = false, bool $dispatch = true): string
    {
        $js = sprintf('NeuraKitSideover.close(%s)', $force ? 'true' : 'false');

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    private function buildOpenJs(): string
    {
        return sprintf(
            'NeuraKitSideover.open(%s, %s, %s)',
            json_encode($this->sideover),
            json_encode($this->normalizeArgsForJs($this->args)),
            json_encode($this->attrs)
        );
    }

    private function normalizeArgsForJs(array $args): array
    {
        return array_map(function (mixed $value): mixed {
            if ($value instanceof UrlRoutable) {
                return $value->getRouteKey();
            }

            if (is_object($value) && method_exists($value, 'getRouteKey')) {
                return $value->getRouteKey();
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return $value;
        }, $args);
    }
}
