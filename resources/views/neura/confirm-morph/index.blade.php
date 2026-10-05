@props([
    'label' => 'Delete',
    'icon' => null,
    'prompt' => null,
    'confirmLabel' => 'Delete',
    'cancelLabel' => 'Cancel',
    'pendingLabel' => 'Deleting',
    'doneLabel' => 'Deleted',
    'errorLabel' => 'Could not finish',
    'retryLabel' => 'Retry',
    'undoLabel' => 'Undo',
    'undoingLabel' => 'Restoring',
    'color' => null,
    'size' => null,
    'variant' => null,
    'method' => null,
    'undoMethod' => null,
    'confirmTimeout' => 6000,
    'resultTimeout' => 5000,
    'rounded' => null,
    'shadow' => null,
    'disabled' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];
    $size = $size ?? neura_config('confirm-morph', 'size') ?? 'md';
    $variant = $variant ?? neura_config('confirm-morph', 'variant') ?? 'outline';
    $color = $color ?? neura_config('confirm-morph', 'color') ?? 'danger';
    $rounded = $rounded ?? neura_config('confirm-morph', 'rounded');
    $shadow = $shadow ?? neura_config('confirm-morph', 'shadow');

    if (str_contains((string) $variant, '-')) {
        [$maybeColor, $maybeVariant] = explode('-', (string) $variant, 2);
        if (in_array($maybeVariant, $variantStyles, true)) {
            $color = $color ?: $maybeColor;
            $variant = $maybeVariant;
        }
    }

    $size = in_array($size, $sizes, true) ? $size : 'md';
    $variant = in_array($variant, $variantStyles, true) ? $variant : 'outline';
    $color = strtolower((string) $color);
    $color = preg_match('/^[a-z0-9-]+$/', $color) ? $color : 'danger';
    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = in_array($variant, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);
    $toneStyle = $color !== '' && $color !== 'neutral'
        ? "--nk-tone: var(--color-{$color}-600); --nk-tone-strong: var(--color-{$color}-900); --nk-tone-soft: var(--color-{$color}-100);"
        : '';
    $promptText = filled($prompt) ? $prompt : ($label.'?');

    $config = [
        'method' => $method,
        'undoMethod' => $undoMethod,
        'pendingLabel' => $pendingLabel,
        'undoingLabel' => $undoingLabel,
        'doneLabel' => $doneLabel,
        'errorLabel' => $errorLabel,
        'undoLabel' => $undoLabel,
        'confirmTimeout' => (int) $confirmTimeout,
        'resultTimeout' => (int) $resultTimeout,
    ];

    $root = [
        'data-state' => 'idle',
        'data-size' => $size,
        'data-variant' => $variant,
        'data-color' => $color !== '' ? $color : 'neutral',
        'data-prompt' => $promptText,
        'tabindex' => '-1',
        'x-data' => 'neuraConfirmMorph('.json_encode($config).')',
        'x-bind:data-state' => 'state',
        'x-bind:aria-busy' => "state === 'pending' ? 'true' : null",
        'x-on:keydown' => 'onKey($event)',
        'x-on:pointerenter' => 'hold(true)',
        'x-on:pointerleave' => 'hold(false)',
    ];

    if ($toneStyle !== '') {
        $root['style'] = $toneStyle;
    }

    $rootAttributes = $attributes->merge($root)->class(['nk-confirm', $roundedClass]);
@endphp

<div {{ $rootAttributes }}>
    <div @class(['nk-confirm-surface', $roundedClass, $shadowClass]) x-ref="surface">
        <div class="nk-confirm-face" data-face="idle">
            <button type="button" class="nk-confirm-trigger" data-autofocus @disabled($disabled) x-on:click="ask()">
                @if (filled($icon))
                    <span class="nk-confirm-icon" aria-hidden="true">
                        <neura::icon :name="$icon" />
                    </span>
                @endif
                <span>{{ $label }}</span>
            </button>
        </div>

        <div class="nk-confirm-face" data-face="confirming" hidden role="group" aria-label="{{ $promptText }}">
            <span class="nk-confirm-prompt">{{ $promptText }}</span>
            <button type="button" @class(['nk-confirm-secondary', $roundedClass]) data-autofocus x-on:click="cancel()">{{ $cancelLabel }}</button>
            <button type="button" @class(['nk-confirm-primary', $roundedClass]) x-on:click="perform('confirm')">{{ $confirmLabel }}</button>
        </div>

        <div class="nk-confirm-face" data-face="pending" hidden>
            <span class="nk-confirm-status">
                <neura::spinner variant="ring" size="xs" color="current" />
                <span x-show="working === 'confirm'">{{ $pendingLabel }}</span>
                <span x-show="working === 'undo'" x-cloak>{{ $undoingLabel }}</span>
            </span>
        </div>

        <div class="nk-confirm-face" data-face="done" hidden>
            <span class="nk-confirm-status" data-tone="success">
                <svg class="nk-confirm-check" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <circle cx="9" cy="9" r="8" />
                    <path d="M5.6 9.3 7.8 11.4 12.4 6.7" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>{{ $doneLabel }}</span>
            </span>
            @if (filled($undoMethod))
                <button type="button" @class(['nk-confirm-secondary', $roundedClass]) data-autofocus x-on:click="perform('undo')">{{ $undoLabel }}</button>
            @endif
        </div>

        <div class="nk-confirm-face" data-face="error" hidden>
            <span class="nk-confirm-status">
                <neura::icon name="exclamation-circle" class="size-4 text-danger-600" />
                <span>{{ $errorLabel }}</span>
            </span>
            <button type="button" @class(['nk-confirm-secondary', $roundedClass]) data-autofocus x-on:click="retry()">{{ $retryLabel }}</button>
        </div>

        <span class="nk-confirm-drain" aria-hidden="true">
            <span class="nk-confirm-drain-fill" x-ref="drain"></span>
        </span>
    </div>
    <span class="sr-only" role="status" aria-live="polite" x-text="announcement"></span>
</div>
