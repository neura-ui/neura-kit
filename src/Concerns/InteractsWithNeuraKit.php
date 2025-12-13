<?php

declare(strict_types=1);

namespace Neura\Kit\Concerns;

trait InteractsWithNeuraKit
{
    public function toast(?string $content = null): object
    {
        $component = $this;
        $data = ['content' => $content ?? '', 'type' => 'info', 'duration' => 4000];

        return new class($component, $data) {
            public function __construct(private $c, private array $d) {}

            public function duration(int $ms): self { $this->d['duration'] = $ms; return $this; }

            public function success(?string $content = null): void { $this->send($content, 'success'); }
            public function error(?string $content = null): void { $this->send($content, 'error'); }
            public function warning(?string $content = null): void { $this->send($content, 'warning'); }
            public function info(?string $content = null): void { $this->send($content, 'info'); }

            private function send(?string $content, string $type): void {
                if ($content) $this->d['content'] = $content;
                if ($this->d['content']) {
                    $this->c->js(sprintf('NeuraKitToast.show(%s,%s,%d)', json_encode($this->d['content']), json_encode($type), $this->d['duration']));
                }
            }
        };
    }

    public function modal(?string $name = null): object
    {
        $component = $this;
        $data = ['name' => $name, 'args' => [], 'attrs' => []];

        return new class($component, $data) {
            public function __construct(private $c, private array $d) {}

            public function with(array $args): self { $this->d['args'] = array_merge($this->d['args'], $args); return $this; }
            public function attrs(array $attrs): self { $this->d['attrs'] = array_merge($this->d['attrs'], $attrs); return $this; }
            public function maxWidth(string $w): self { $this->d['attrs']['maxWidth'] = $w; return $this; }

            public function open(?array $args = null): void {
                if ($args) $this->d['args'] = array_merge($this->d['args'], $args);
                if ($this->d['name']) {
                    $this->c->js(sprintf('NeuraKitModal.open(%s,%s,%s)', json_encode($this->d['name']), json_encode($this->d['args'] ?: (object)[]), json_encode($this->d['attrs'] ?: (object)[])));
                }
            }

            public function close(bool $force = false): void {
                $this->c->js('NeuraKitModal.close(' . ($force ? 'true' : 'false') . ')');
            }
        };
    }

    public function dialog(?string $title = null): object
    {
        $component = $this;
        $data = ['type' => 'info', 'title' => $title ?? '', 'message' => '', 'confirmText' => 'Confirm', 'cancelText' => 'Cancel', 'showCancel' => true, 'confirmVariant' => 'primary', 'size' => 'sm', 'onConfirm' => null, 'onConfirmParams' => [], 'onCancel' => null, 'onCancelParams' => []];

        return new class($component, $data) {
            public function __construct(private $c, private array $d) {}

            public function title(string $t): self { $this->d['title'] = $t; return $this; }
            public function message(string $m): self { $this->d['message'] = $m; return $this; }
            public function info(): self { $this->d['type'] = 'info'; $this->d['confirmVariant'] = 'primary'; return $this; }
            public function success(): self { $this->d['type'] = 'success'; $this->d['confirmVariant'] = 'success'; return $this; }
            public function warning(): self { $this->d['type'] = 'warning'; $this->d['confirmVariant'] = 'primary'; return $this; }
            public function danger(): self { $this->d['type'] = 'danger'; $this->d['confirmVariant'] = 'danger'; return $this; }
            public function confirmText(string $t): self { $this->d['confirmText'] = $t; return $this; }
            public function cancelText(string $t): self { $this->d['cancelText'] = $t; return $this; }
            public function hideCancel(): self { $this->d['showCancel'] = false; return $this; }
            public function size(string $s): self { $this->d['size'] = $s; return $this; }
            public function onConfirm(string $method, mixed ...$params): self { $this->d['onConfirm'] = $method; $this->d['onConfirmParams'] = $params; return $this; }
            public function onCancel(string $method, mixed ...$params): self { $this->d['onCancel'] = $method; $this->d['onCancelParams'] = $params; return $this; }

            public function show(): void {
                if (!$this->d['title']) return;
                $confirm = $this->d['onConfirm'] ? sprintf('()=>$wire.%s(%s)', $this->d['onConfirm'], implode(',', array_map('json_encode', $this->d['onConfirmParams']))) : 'null';
                $cancel = $this->d['onCancel'] ? sprintf('()=>$wire.%s(%s)', $this->d['onCancel'], implode(',', array_map('json_encode', $this->d['onCancelParams']))) : 'null';
                $this->c->js(sprintf("\$dispatch('dialog',{type:%s,title:%s,message:%s,confirmText:%s,cancelText:%s,showCancel:%s,confirmVariant:%s,size:%s,onConfirm:%s,onCancel:%s})",
                    json_encode($this->d['type']), json_encode($this->d['title']), json_encode($this->d['message']),
                    json_encode($this->d['confirmText']), json_encode($this->d['cancelText']), $this->d['showCancel'] ? 'true' : 'false',
                    json_encode($this->d['confirmVariant']), json_encode($this->d['size']), $confirm, $cancel
                ));
            }
        };
    }

    public function openModal(string $component, array $args = [], array $attrs = []): void
    {
        $this->modal($component)->with($args)->attrs($attrs)->open();
    }

    public function closeModal(bool $force = false): void
    {
        $this->modal()->close($force);
    }
}
