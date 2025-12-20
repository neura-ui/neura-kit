<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Dialog;

use Livewire\Component;

final class DialogCall
{
    private array $data = [
        'type' => 'info',
        'title' => '',
        'message' => '',
        'confirmText' => 'Confirm',
        'cancelText' => 'Cancel',
        'showCancel' => true,
        'confirmVariant' => 'primary',
        'size' => 'lg',
        'onConfirm' => null,
        'onConfirmParams' => [],
        'onCancel' => null,
        'onCancelParams' => [],
        'showInput' => false,
        'inputPlaceholder' => '',
        'inputValue' => '',
    ];

    public function __construct(
        private readonly Component $caller,
        ?string $title = null
    ) {
        if ($title !== null) {
            $this->data['title'] = $title;
        }
    }

    /* -------------------------------------------------------------
     | Content
     |------------------------------------------------------------- */

    public function title(string $title): self
    {
        $this->data['title'] = $title;
        return $this;
    }

    public function message(string $message): self
    {
        $this->data['message'] = $message;
        return $this;
    }

    /* -------------------------------------------------------------
     | Type / Variant
     |------------------------------------------------------------- */

    public function info(): self
    {
        $this->data['type'] = 'info';
        $this->data['confirmVariant'] = 'primary';
        return $this;
    }

    public function success(): self
    {
        $this->data['type'] = 'success';
        $this->data['confirmVariant'] = 'success';
        return $this;
    }

    public function warning(): self
    {
        $this->data['type'] = 'warning';
        $this->data['confirmVariant'] = 'primary';
        return $this;
    }

    public function danger(): self
    {
        $this->data['type'] = 'danger';
        $this->data['confirmVariant'] = 'danger';
        return $this;
    }

    /* -------------------------------------------------------------
     | Buttons & Layout
     |------------------------------------------------------------- */

    public function confirmText(string $text): self
    {
        $this->data['confirmText'] = $text;
        return $this;
    }

    public function cancelText(string $text): self
    {
        $this->data['cancelText'] = $text;
        return $this;
    }

    public function hideCancel(): self
    {
        $this->data['showCancel'] = false;
        return $this;
    }

    public function size(string $size): self
    {
        $this->data['size'] = $size;
        return $this;
    }

    /* -------------------------------------------------------------
     | Input
     |------------------------------------------------------------- */

    public function showInput(): self
    {
        $this->data['showInput'] = true;
        return $this;
    }

    public function inputPlaceholder(string $placeholder): self
    {
        $this->data['inputPlaceholder'] = $placeholder;
        return $this;
    }

    public function inputValue(string $value): self
    {
        $this->data['inputValue'] = $value;
        return $this;
    }

    /* -------------------------------------------------------------
     | Callbacks
     |------------------------------------------------------------- */

    public function onConfirm(string $method, mixed ...$params): self
    {
        $this->data['onConfirm'] = $method;
        $this->data['onConfirmParams'] = $params;
        return $this;
    }

    public function onCancel(string $method, mixed ...$params): self
    {
        $this->data['onCancel'] = $method;
        $this->data['onCancelParams'] = $params;
        return $this;
    }

    /* -------------------------------------------------------------
     | Dispatch
     |------------------------------------------------------------- */

    public function show(): void
    {
        if ($this->data['title'] === '') {
            return;
        }

        $confirm = $this->buildConfirmCallback();
        $cancel  = $this->buildCancelCallback();

        $this->caller->js(sprintf(
            "\$dispatch('dialog', %s)",
            json_encode(array_merge(
                $this->data,
                [
                    'onConfirm' => $confirm,
                    'onCancel'  => $cancel,
                ]
            ), JSON_THROW_ON_ERROR)
        ));
    }

    /* -------------------------------------------------------------
     | Internals
     |------------------------------------------------------------- */

    private function buildConfirmCallback(): string|null
    {
        if (!$this->data['onConfirm']) {
            return null;
        }

        $params = array_map('json_encode', $this->data['onConfirmParams']);

        if ($this->data['showInput']) {
            return sprintf(
                '(value) => $wire.%s(value%s)',
                $this->data['onConfirm'],
                $params ? ',' . implode(',', $params) : ''
            );
        }

        return sprintf(
            '() => $wire.%s(%s)',
            $this->data['onConfirm'],
            implode(',', $params)
        );
    }

    private function buildCancelCallback(): string|null
    {
        if (!$this->data['onCancel']) {
            return null;
        }

        return sprintf(
            '() => $wire.%s(%s)',
            $this->data['onCancel'],
            implode(',', array_map('json_encode', $this->data['onCancelParams']))
        );
    }
}
