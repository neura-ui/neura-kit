@props([
    'label' => 'Save changes',
    'pendingLabel' => 'Saving',
    'successLabel' => 'Saved',
    'resetAfter' => 2400,
    'method' => null,
    'delay' => 800,
    'variant' => null,
    'size' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
])

@php
    $wireClick = $attributes->get('wire:click');
    if (is_string($wireClick) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $wireClick)) {
        $method = $method ?: $wireClick;
        $attributes = $attributes->except('wire:click');
    }

    $config = [
        'label' => $label,
        'pendingLabel' => $pendingLabel,
        'successLabel' => $successLabel,
        'resetAfter' => (int) $resetAfter,
        'method' => $method,
        'delay' => (int) $delay,
    ];

    $buttonAttributes = $attributes->class('nk-action overflow-hidden')->merge([
        'x-data' => 'neuraActionButton('.\Illuminate\Support\Js::from($config).')',
        'x-on:click' => 'run()',
        'x-bind:data-state' => 'state',
        'x-bind:aria-busy' => "state === 'pending' ? 'true' : 'false'",
        'x-bind:aria-disabled' => "state === 'pending' ? 'true' : null",
    ]);
@endphp

<neura::button
    :$variant
    :$size
    :$color
    :$rounded
    :$shadow
    :attributes="$buttonAttributes"
>
    <span class="nk-action-content">
        <span class="nk-action-morph" x-ref="morph" aria-hidden="true">
            <span class="nk-action-glyphs" x-ref="glyphs">
                <template x-for="glyph in glyphs" :key="glyph.id">
                    <span
                        class="nk-action-glyph"
                        x-text="glyph.char"
                        :data-glyph="glyph.id"
                        :data-fresh="glyph.fresh ? '' : null"
                        :style="`--glyph-i:${glyph.order}`"
                        x-transition:leave="nk-glyph-leave"
                        x-transition:leave-start="nk-glyph-leave-from"
                        x-transition:leave-end="nk-glyph-leave-to"
                    ></span>
                </template>
            </span>
        </span>

        <span class="nk-action-icon" aria-hidden="true">
            <span
                class="nk-action-phase"
                x-show="state === 'idle'"
                x-transition:enter="nk-phase-enter"
                x-transition:enter-start="nk-arrow-from"
                x-transition:enter-end="nk-phase-rest"
                x-transition:leave="nk-phase-leave"
                x-transition:leave-start="nk-phase-rest"
                x-transition:leave-end="nk-arrow-to"
            >
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14" />
                    <path d="m13 6 6 6-6 6" />
                </svg>
            </span>
            <span
                class="nk-action-phase"
                x-show="state === 'pending'"
                x-cloak
                x-transition:enter="nk-phase-enter"
                x-transition:enter-start="nk-icon-from"
                x-transition:enter-end="nk-phase-rest"
                x-transition:leave="nk-phase-leave"
                x-transition:leave-start="nk-phase-rest"
                x-transition:leave-end="nk-icon-to"
            >
                <neura::spinner variant="ring" size="sm" color="current" />
            </span>
            <span
                class="nk-action-phase"
                x-show="state === 'success'"
                x-cloak
                x-transition:enter="nk-phase-enter"
                x-transition:enter-start="nk-icon-from"
                x-transition:enter-end="nk-phase-rest"
                x-transition:leave="nk-phase-leave"
                x-transition:leave-start="nk-phase-rest"
                x-transition:leave-end="nk-icon-to"
            >
                <svg class="nk-action-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12l5 5L20 6" />
                </svg>
            </span>
        </span>
    </span>
    <span class="sr-only">{{ $label }}</span>
    <span class="sr-only" role="status" x-text="announcement"></span>
</neura::button>
