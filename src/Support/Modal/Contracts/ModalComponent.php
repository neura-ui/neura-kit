<?php

namespace Neura\Kit\Support\Modal\Contracts;

interface ModalComponent
{
    public function closeModal(): void;

    public function closeModalWithEvents(array $events): void;

    public static function modalMaxWidth(): string;

    public static function modalMaxWidthClass(): string;

    public static function closeModalOnClickAway(): bool;

    public static function closeModalOnEscape(): bool;

    public static function closeModalOnEscapeIsForceful(): bool;

    public static function dispatchCloseEvent(): bool;

    public static function destroyOnClose(): bool;
}

