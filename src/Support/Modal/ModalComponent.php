<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Modal;

use InvalidArgumentException;
use Livewire\Component;
use Neura\Kit\Support\Modal\Contracts\ModalComponent as ModalComponentContract;

abstract class ModalComponent extends Component implements ModalComponentContract
{
    /**
     * Map of size keys to Tailwind max-width classes.
     */
    public const MAX_WIDTH_CLASSES = [
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        'full' => 'max-w-full',
    ];

    public bool $forceClose = false;

    public int $skipModals = 0;

    public bool $destroySkipped = false;

    protected static ?array $defaults = null;

    protected static function getDefaults(): array
    {
        return static::$defaults ??= config('neura-kit.modal.component_defaults', []);
    }

    public function destroySkippedModals(): self
    {
        $this->destroySkipped = true;

        return $this;
    }

    public function skipPreviousModals(int $count = 1, bool $destroy = false): self
    {
        $this->skipModals = $count;
        $this->destroySkipped = $destroy;

        return $this;
    }

    public function skipPreviousModal(int $count = 1, bool $destroy = false): self
    {
        return $this->skipPreviousModals($count, $destroy);
    }

    public function forceClose(): self
    {
        $this->forceClose = true;

        return $this;
    }

    public function closeModal(): void
    {
        $this->dispatch(
            'closeModal',
            force: $this->forceClose,
            skipPreviousModals: $this->skipModals,
            destroySkipped: $this->destroySkipped
        );
    }

    /**
     * Go back to the previous modal in the stack.
     * Alias for closeModal() with destroy enabled.
     */
    public function goBack(): void
    {
        $this->forceClose = true;
        $this->closeModal();
    }

    public function closeModalWithEvents(array $events): void
    {
        $this->emitModalEvents($events);
        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return (string) (static::getDefaults()['modal_max_width'] ?? 'lg');
    }

    public static function modalMaxWidthClass(): string
    {
        $width = static::modalMaxWidth();

        if (! isset(self::MAX_WIDTH_CLASSES[$width])) {
            throw new InvalidArgumentException(sprintf(
                'Modal max width [%s] is invalid. Valid: [%s].',
                $width,
                implode(', ', array_keys(self::MAX_WIDTH_CLASSES))
            ));
        }

        return self::MAX_WIDTH_CLASSES[$width];
    }

    /**
     * Get the max-width class for a given size key.
     */
    public static function getMaxWidthClass(string $size): ?string
    {
        return self::MAX_WIDTH_CLASSES[$size] ?? null;
    }

    /**
     * Check if a size key is a valid predefined size.
     */
    public static function isValidSize(string $size): bool
    {
        return isset(self::MAX_WIDTH_CLASSES[$size]);
    }

    public static function closeModalOnClickAway(): bool
    {
        return (bool) (static::getDefaults()['close_modal_on_click_away'] ?? true);
    }

    public static function closeModalOnEscape(): bool
    {
        return (bool) (static::getDefaults()['close_modal_on_escape'] ?? true);
    }

    public static function closeModalOnEscapeIsForceful(): bool
    {
        return (bool) (static::getDefaults()['close_modal_on_escape_is_forceful'] ?? true);
    }

    public static function dispatchCloseEvent(): bool
    {
        return (bool) (static::getDefaults()['dispatch_close_event'] ?? false);
    }

    public static function destroyOnClose(): bool
    {
        return (bool) (static::getDefaults()['destroy_on_close'] ?? true);
    }

    private function emitModalEvents(array $events): void
    {
        foreach ($events as $component => $event) {
            if (is_array($event)) {
                if (count($event) < 1) {
                    continue;
                }
                [$event, $params] = [$event[0], $event[1] ?? []];
            } else {
                $params = [];
            }

            if (! is_string($event)) {
                continue;
            }

            $dispatch = $this->dispatch($event, ...$params);

            if (! is_numeric($component)) {
                $dispatch->to($component);
            }
        }
    }
}
