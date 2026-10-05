@php
    $icon = $icon ?? null;
    $method = $method ?? null;
    $doneLabel = $doneLabel ?? null;
    $color = strtolower((string) ($color ?? ''));
    $color = preg_match('/^[a-z0-9-]+$/', $color) ? $color : '';
    $disabled = $disabled ?? false;
    $expanded = $expanded ?? false;
    $toneStyle = $color !== '' && $color !== 'neutral'
        ? "--nk-ebg-tone: var(--color-{$color}-600); --nk-ebg-tone-strong: var(--color-{$color}-900); --nk-ebg-tone-soft: var(--color-{$color}-100);"
        : '';
@endphp

<button
    type="button"
    class="nk-ebg-item"
    data-ebg-item
    data-id="{{ $id }}"
    @if ($method) data-method="{{ $method }}" @endif
    @if (filled($doneLabel)) data-done="{{ $doneLabel }}" @endif
    @if ($color !== '') data-color="{{ $color }}" @endif
    @if ($toneStyle !== '') style="{{ $toneStyle }}" @endif
    data-state="idle"
    @if ($expanded) data-expanded @endif
    aria-label="{{ $label }}"
    @if ($disabled) aria-disabled="true" @endif
    x-on:pointerdown="notePress($event)"
    x-on:click="onItemClick($event)"
    x-on:focus="noteFocus($event)"
>
    <span class="nk-ebg-icon" aria-hidden="true">
        <span class="nk-ebg-glyph" data-glyph="icon" data-on>
            @if (filled($icon))
                <neura::icon :name="$icon" />
            @endif
        </span>
        <span class="nk-ebg-glyph" data-glyph="check">
            <svg class="nk-ebg-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 12.5l5 5L20 6.5" />
            </svg>
        </span>
        <span class="nk-ebg-glyph" data-glyph="busy">
            <neura::spinner variant="ring" size="xs" color="current" />
        </span>
    </span>
    <span class="nk-ebg-label" aria-hidden="true">
        <span class="nk-ebg-word" data-word="rest" data-on>{{ $label }}</span>
        @if (filled($doneLabel))
            <span class="nk-ebg-word" data-word="done">{{ $doneLabel }}</span>
        @endif
        <span class="nk-ebg-sizer" data-sizer>
            <span>{{ $label }}</span>
            @if (filled($doneLabel))
                <span>{{ $doneLabel }}</span>
            @endif
        </span>
    </span>
</button>
