@props([
    'label',
    'method' => null,
    'icon' => null,
    'color' => null,
    'disabled' => false,
    'href' => null,
])

@php
    $tone = strtolower((string) $color);
    $tone = preg_match('/^[a-z][a-z0-9-]{0,30}$/', $tone) ? $tone : '';
    $action = [
        'label' => $label,
        'method' => $method,
        'disabled' => (bool) $disabled,
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

@if (filled($href) && ! $disabled)
    <a href="{{ $href }}" {{ $itemAttributes->except('type') }} x-on:click="choose({{ json_encode($action) }})">
@else
    <button {{ $itemAttributes }} x-on:click="choose({{ json_encode($action) }})">
@endif
    @if (filled($icon))
        <neura::icon :name="$icon" class="size-4 shrink-0" />
    @endif
    <span>{{ $slot->isNotEmpty() ? $slot : $label }}</span>
@if (filled($href) && ! $disabled)
    </a>
@else
    </button>
@endif
