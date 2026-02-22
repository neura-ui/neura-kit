<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Sideover\Contracts;

interface SideoverComponent
{
    public function closeSideover(): void;

    public function closeSideoverWithEvents(array $events): void;

    public function goBack(): void;

    public static function sideoverSide(): string;

    public static function sideoverWidth(): string;

    public static function sideoverWidthClass(): string;

    public static function closeSideoverOnClickAway(): bool;

    public static function closeSideoverOnEscape(): bool;

    public static function closeSideoverOnEscapeIsForceful(): bool;

    public static function dispatchCloseEvent(): bool;

    public static function destroyOnClose(): bool;
}
