<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Modal;

use Illuminate\Contracts\Routing\UrlRoutable;
use InvalidArgumentException;
use Livewire\Component;

final class ModalCall
{
    private array $args = [];
    private array $attrs = [];

    /**
     * @param class-string<ModalComponent> $modal
     */
    public function __construct(
        private readonly Component $caller,
        private readonly string $modal,
    ) {
        if (!is_subclass_of($modal, ModalComponent::class)) {
            throw new InvalidArgumentException(sprintf(
                'Modal [%s] must extend %s.',
                $modal,
                ModalComponent::class
            ));
        }

        $this->attrs['maxWidth'] ??= $modal::modalMaxWidth();
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
        return $this;
    }

    public function attrs(array $attrs): self
    {
        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }

    public function maxWidth(string $width): self
    {
        $this->attrs['maxWidth'] = $width;
        return $this;
    }

    /* -------------------------------------------------------------
     | Actions
     |------------------------------------------------------------- */

    public function open(?array $overrideArgs = null, bool $dispatch = true): string|null
    {
        if ($overrideArgs) {
            $this->withMany($overrideArgs);
        }

        if ($dispatch) {
            // Normalize arguments for JSON serialization (convert models to IDs)
            $normalizedArgs = $this->normalizeArgsForJs($this->args);
            
            // Call openModal directly via JavaScript
            $js = sprintf(
                'NeuraKitModal.open(%s, %s, %s)',
                json_encode($this->modal),
                json_encode($normalizedArgs),
                json_encode($this->attrs)
            );
            
            $this->caller->js($js);
        }

        return sprintf(
            'NeuraKitModal.open(%s, %s, %s)',
            json_encode($this->modal),
            json_encode($this->normalizeArgsForJs($this->args)),
            json_encode($this->attrs)
        );
    }

    /**
     * Normalize arguments for JSON serialization.
     * Converts Eloquent models and other objects to their route keys/IDs.
     */
    private function normalizeArgsForJs(array $args): array
    {
        return array_map(function ($value) {
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

    public function close(bool $force = false, bool $dispatch = true): string
    {
        $js = sprintf(
            'NeuraKitModal.close(%s)',
            $force ? 'true' : 'false'
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }
}
