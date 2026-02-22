<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Sideover;

use InvalidArgumentException;
use Livewire\Component;
use Neura\Kit\Support\Sideover\Contracts\SideoverComponent as SideoverComponentContract;

abstract class SideoverComponent extends Component implements SideoverComponentContract
{
    /**
     * Map of size keys to Tailwind width classes for sideover panel.
     */
    public const WIDTH_CLASSES = [
        'xs' => 'w-72 max-w-full',
        'sm' => 'w-80 max-w-full',
        'md' => 'w-96 max-w-full',
        'lg' => 'w-[28rem] max-w-full',
        'xl' => 'w-[32rem] max-w-full',
        '2xl' => 'w-[36rem] max-w-full',
        'full' => 'w-full max-w-full',
    ];

    public bool $forceClose = false;

    public int $skipSideovers = 0;

    public bool $destroySkipped = false;

    protected static ?array $defaults = null;

    protected static function getDefaults(): array
    {
        return static::$defaults ??= config('neura-kit.sideover.component_defaults', []);
    }

    public function destroySkippedSideovers(): self
    {
        $this->destroySkipped = true;

        return $this;
    }

    public function skipPreviousSideovers(int $count = 1, bool $destroy = false): self
    {
        $this->skipSideovers = $count;
        $this->destroySkipped = $destroy;

        return $this;
    }

    public function forceClose(): self
    {
        $this->forceClose = true;

        return $this;
    }

    public function closeSideover(): void
    {
        $this->dispatch(
            'closeSideover',
            force: $this->forceClose,
            skipPreviousSideovers: $this->skipSideovers,
            destroySkipped: $this->destroySkipped
        );
    }

    /**
     * Go back to the previous sideover in the stack.
     */
    public function goBack(): void
    {
        $this->forceClose = true;
        $this->closeSideover();
    }

    public function closeSideoverWithEvents(array $events): void
    {
        $this->emitSideoverEvents($events);
        $this->closeSideover();
    }

    public static function sideoverSide(): string
    {
        return (string) (static::getDefaults()['sideover_side'] ?? 'right');
    }

    public static function sideoverWidth(): string
    {
        return (string) (static::getDefaults()['sideover_width'] ?? 'md');
    }

    public static function sideoverWidthClass(): string
    {
        $width = static::sideoverWidth();

        if (! isset(self::WIDTH_CLASSES[$width])) {
            throw new InvalidArgumentException(sprintf(
                'Sideover width [%s] is invalid. Valid: [%s].',
                $width,
                implode(', ', array_keys(self::WIDTH_CLASSES))
            ));
        }

        return self::WIDTH_CLASSES[$width];
    }

    public static function getWidthClass(string $size): ?string
    {
        return self::WIDTH_CLASSES[$size] ?? null;
    }

    public static function closeSideoverOnClickAway(): bool
    {
        return (bool) (static::getDefaults()['close_sideover_on_click_away'] ?? true);
    }

    public static function closeSideoverOnEscape(): bool
    {
        return (bool) (static::getDefaults()['close_sideover_on_escape'] ?? true);
    }

    public static function closeSideoverOnEscapeIsForceful(): bool
    {
        return (bool) (static::getDefaults()['close_sideover_on_escape_is_forceful'] ?? true);
    }

    public static function dispatchCloseEvent(): bool
    {
        return (bool) (static::getDefaults()['dispatch_close_event'] ?? false);
    }

    public static function destroyOnClose(): bool
    {
        return (bool) (static::getDefaults()['destroy_on_close'] ?? true);
    }

    private function emitSideoverEvents(array $events): void
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
