@props([
    'label',
    'method' => null,
    'icon' => null,
    'color' => null,
    'disabled' => false,
    'swap' => true,
    'pendingLabel' => null,
    'successLabel' => null,
    'doneLabel' => null,
    'resetAfter' => null,
])

@php
    $tone = strtolower((string) $color);
    $tone = preg_match('/^[a-z][a-z0-9-]{0,30}$/', $tone) ? $tone : '';
    $success = $successLabel ?: $doneLabel;
    $action = [
        'label' => $label,
        'method' => $method,
        'icon' => $icon,
        'disabled' => (bool) $disabled,
        'swap' => (bool) $swap,
        'pendingLabel' => filled($pendingLabel) ? (string) $pendingLabel : null,
        'successLabel' => filled($success) ? (string) $success : null,
        'resetAfter' => ($resetAfter === null || $resetAfter === '') ? null : (int) $resetAfter,
    ];

    $itemAttributes = $attributes
        ->class(['nk-menu-item', 'has-tone' => $tone !== ''])
        ->merge([
            'type' => 'button',
            'role' => 'menuitem',
            'disabled' => $disabled ? true : null,
            'style' => $tone !== ''
                ? '--nk-item: var(--color-'.$tone.'-600); --nk-item-soft: var(--color-'.$tone.'-400)'
                : null,
        ]);
@endphp

<button {{ $itemAttributes }} x-on:click="choose({{ json_encode($action) }})">
    @if (filled($icon))
        <neura::icon :name="$icon" class="size-4 shrink-0" />
    @endif

    @if ($slot->isNotEmpty())
        <span>{{ $slot }}</span>
    @else
        <span>{{ $label }}</span>
    @endif
</button>
