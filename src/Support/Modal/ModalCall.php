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
     * @param  class-string<ModalComponent>  $modal
     */
    public function __construct(
        private readonly Component $caller,
        private readonly string $modal,
    ) {
        if (! is_subclass_of($modal, ModalComponent::class)) {
            throw new InvalidArgumentException(sprintf(
                'Modal [%s] must extend %s.',
                $modal,
                ModalComponent::class
            ));
        }

        $this->attrs['maxWidth'] ??= $modal::modalMaxWidth();
        $this->syncMaxWidthClass($this->attrs['maxWidth']);
    }

    /* -------------------------------------------------------------
     | Arguments
     |------------------------------------------------------------- */

    /**
     * Set a single argument for the modal component.
     */
    public function with(string $key, mixed $value): self
    {
        $this->args[$key] = $value;

        return $this;
    }

    /**
     * Set multiple arguments for the modal component.
     */
    public function withMany(array $args): self
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /* -------------------------------------------------------------
     | Attributes
     |------------------------------------------------------------- */

    /**
     * Set a single modal attribute.
     */
    public function attr(string $key, mixed $value): self
    {
        $this->attrs[$key] = $value;

        if ($key === 'maxWidth') {
            $this->syncMaxWidthClass($value);
        }

        return $this;
    }

    /**
     * Set multiple modal attributes.
     */
    public function attrs(array $attrs): self
    {
        $this->attrs = array_merge($this->attrs, $attrs);

        if (isset($attrs['maxWidth']) && ! isset($attrs['maxWidthClass'])) {
            $this->syncMaxWidthClass($attrs['maxWidth']);
        }

        return $this;
    }

    /**
     * Set the modal maximum width.
     */
    public function maxWidth(string $width): self
    {
        $this->attrs['maxWidth'] = $width;
        $this->syncMaxWidthClass($width);

        return $this;
    }

    /**
     * Synchronize maxWidthClass based on the maxWidth value.
     */
    private function syncMaxWidthClass(string $width): void
    {
        $class = ModalComponent::getMaxWidthClass($width);

        if ($class !== null) {
            $this->attrs['maxWidthClass'] = $class;
        } else {
            unset($this->attrs['maxWidthClass']);
        }
    }

    /* -------------------------------------------------------------
     | Actions
     |------------------------------------------------------------- */

    /**
     * Open the modal.
     */
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

    /**
     * Close the modal.
     */
    public function close(bool $force = false, bool $dispatch = true): string
    {
        $js = sprintf('NeuraKitModal.close(%s)', $force ? 'true' : 'false');

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Build the JavaScript call to open the modal.
     */
    private function buildOpenJs(): string
    {
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
