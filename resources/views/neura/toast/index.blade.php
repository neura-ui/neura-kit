@props([
    'position' => 'bottom-right',
    'maxToasts' => 5,
    'progressBarVariant' => 'full',
    'progressBarAlignment' => 'bottom'
])

@php
    $positions = ['bottom-right', 'bottom-left', 'top-right', 'top-left'];
    $position = in_array($position, $positions, true) ? $position : 'bottom-right';
    $isAlignmentsToBottom = str_starts_with($position, 'bottom');
    $sessionToast = session()->pull('notify');

    $config = [
        'maxToasts' => max(1, (int) $maxToasts),
        'fromBottom' => $isAlignmentsToBottom,
        'session' => is_array($sessionToast) ? $sessionToast : null,
    ];

    $root = [
        'data-position' => $position,
        'data-edge' => $isAlignmentsToBottom ? 'bottom' : 'top',
        'x-data' => 'neuraToast('.json_encode($config).')',
        'x-on:notify.window' => 'addToast($event.detail)',
        'x-on:keydown' => 'onKey($event)',
        'role' => 'region',
        'aria-label' => 'Notifications',
        'aria-live' => 'polite',
        'aria-relevant' => 'additions text',
    ];
@endphp

<div {{ $attributes->merge($root)->class(['nk-toast']) }}>
    <div
        class="nk-toast-pile"
        x-ref="pile"
        x-on:mouseenter="expand(true)"
        x-on:mouseleave="expand(false)"
        x-on:focusin="expand(true)"
        x-on:focusout="onFocusOut($event)"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <neura::toast.item/>
        </template>
    </div>
</div>
