@props([
    'signer' => '',
    'hint' => 'Sign here',
    'ink' => 'black',
    'nib' => 'medium',
    'fileName' => 'signature',
    'label' => 'Signature pad',
    'name' => null,
    'id' => null,
    'size' => null,
    'variant' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'disabled' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];
    $size = $size ?? neura_config('signature-pad', 'size') ?? 'md';
    $variant = $variant ?? neura_config('signature-pad', 'variant') ?? 'outline';
    $color = $color ?? neura_config('signature-pad', 'color') ?? 'primary';
    $rounded = $rounded ?? neura_config('signature-pad', 'rounded');
    $shadow = $shadow ?? neura_config('signature-pad', 'shadow');

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
    $color = preg_match('/^[a-z0-9-]+$/', $color) ? $color : 'primary';
    $roundedToken = PackResolver::roundedToken($rounded);
    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = in_array($variant, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);
    $toneStyle = $color !== '' && $color !== 'neutral'
        ? "--nk-tone: var(--color-{$color}-600); --nk-tone-strong: var(--color-{$color}-900); --nk-tone-soft: var(--color-{$color}-100);"
        : '';
    // The toolbar controls follow the same rounded token as the pad.
    $toneStyle .= ' --nk-sign-radius: '.PackResolver::radiusValue($rounded).';';

    $inks = ['black', 'blue', 'violet'];
    $nibs = ['fine', 'medium', 'bold'];
    $ink = in_array($ink, $inks, true) ? $ink : 'black';
    $nib = in_array($nib, $nibs, true) ? $nib : 'medium';

    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $attrValue) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $attrValue;
            break;
        }
    }

    $noteId = ($id ?: 'nk-sign-'.substr(md5(uniqid('', true)), 0, 8)).'-note';
    $fieldName = $name ?: $wireModel;

    $config = [
        'signer' => (string) $signer,
        'hint' => (string) ($hint !== '' ? $hint : 'Sign here'),
        'ink' => $ink,
        'nib' => $nib,
        'fileName' => (string) $fileName,
        'label' => (string) ($label !== '' ? $label : 'Signature pad'),
        'disabled' => (bool) $disabled,
        'wireProperty' => $wireModel,
    ];

    $root = [
        'data-size' => $size,
        'data-variant' => $variant,
        'data-color' => $color !== '' ? $color : 'neutral',
        'data-rounded' => $roundedToken,
        'x-data' => 'neuraSignaturePad('.json_encode($config).')',
        'x-modelable' => 'svgText',
        'x-bind:class' => "signed ? 'is-signed' : null",
        'x-bind:data-disabled' => 'disabled ? "" : null',
        'x-on:keydown' => 'onKey($event)',
    ];

    $userStyle = trim((string) $attributes->get('style'));
    $style = trim($toneStyle.' '.$userStyle);
    if ($style !== '') {
        $root['style'] = $style;
    }

    $rootAttributes = $attributes
        ->whereDoesntStartWith('wire:model')
        ->except(['style'])
        ->merge($root)
        ->class(['nk-sign']);
@endphp

<div {{ $rootAttributes }}>
    <div
        @class(['nk-sign-pad', $roundedClass, $shadowClass])
        x-ref="pad"
        role="img"
        tabindex="{{ $disabled ? '-1' : '0' }}"
        aria-roledescription="signature pad"
        aria-describedby="{{ $noteId }}"
        x-bind:aria-label="padLabel"
        x-bind:tabindex="disabled ? -1 : 0"
        x-on:pointerdown="start($event)"
    >
        <div class="nk-sign-stage" x-ref="stage">
            <svg class="nk-sign-svg" viewBox="0 0 600 260" aria-hidden="true">
                <g x-ref="ink"></g>
                <circle class="nk-sign-nib-dot" x-ref="nib" cx="0" cy="0" r="3.4" />
            </svg>
        </div>
        <div class="nk-sign-base" aria-hidden="true"></div>
        <div class="nk-sign-wipe" x-ref="wipe" aria-hidden="true"></div>
        <span class="nk-sign-hint" aria-hidden="true"><span>{{ $hint !== '' ? $hint : 'Sign here' }}</span></span>
        @if ($signer !== '')
            <span class="nk-sign-signer">{{ $signer }}</span>
        @endif
    </div>

    <div class="nk-sign-tools">
        <div class="nk-sign-radios" role="radiogroup" aria-label="Ink color" x-ref="colors">
            <span class="nk-sign-glide" x-ref="colorGlide" aria-hidden="true"></span>
            <button type="button" class="nk-sign-swatch" role="radio" aria-label="Black" x-bind:aria-checked="ink === 'black'" x-bind:tabindex="ink === 'black' ? 0 : -1" x-on:click="setInk('black')">
                <span class="nk-sign-dot" data-ink="black"></span>
            </button>
            <button type="button" class="nk-sign-swatch" role="radio" aria-label="Blue" x-bind:aria-checked="ink === 'blue'" x-bind:tabindex="ink === 'blue' ? 0 : -1" x-on:click="setInk('blue')">
                <span class="nk-sign-dot" data-ink="blue"></span>
            </button>
            <button type="button" class="nk-sign-swatch" role="radio" aria-label="Violet" x-bind:aria-checked="ink === 'violet'" x-bind:tabindex="ink === 'violet' ? 0 : -1" x-on:click="setInk('violet')">
                <span class="nk-sign-dot" data-ink="violet"></span>
            </button>
        </div>

        <div class="nk-sign-radios" role="radiogroup" aria-label="Nib width" x-ref="widths">
            <span class="nk-sign-glide" x-ref="widthGlide" aria-hidden="true"></span>
            <button type="button" class="nk-sign-width" role="radio" aria-label="Fine" x-bind:aria-checked="nib === 'fine'" x-bind:tabindex="nib === 'fine' ? 0 : -1" x-on:click="setNib('fine')">
                <span class="nk-sign-mark" data-nib="fine"></span>
            </button>
            <button type="button" class="nk-sign-width" role="radio" aria-label="Medium" x-bind:aria-checked="nib === 'medium'" x-bind:tabindex="nib === 'medium' ? 0 : -1" x-on:click="setNib('medium')">
                <span class="nk-sign-mark" data-nib="medium"></span>
            </button>
            <button type="button" class="nk-sign-width" role="radio" aria-label="Bold" x-bind:aria-checked="nib === 'bold'" x-bind:tabindex="nib === 'bold' ? 0 : -1" x-on:click="setNib('bold')">
                <span class="nk-sign-mark" data-nib="bold"></span>
            </button>
        </div>

        <button type="button" class="nk-sign-tool" aria-label="Undo" title="Undo" x-on:click="undo()" x-bind:disabled="step === 0">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6.2 4.2 3.4 7l2.8 2.8M4 7h5.1a3.1 3.1 0 1 1 0 6.2H7.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="nk-sign-tool" aria-label="Redo" title="Redo" x-on:click="redo()" x-bind:disabled="step >= history.length - 1">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M9.8 4.2 12.6 7 9.8 9.8M12 7H6.9a3.1 3.1 0 1 0 0 6.2H8.8" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="nk-sign-tool" aria-label="Clear" title="Clear" x-on:click="clear()" x-bind:disabled="strokes.length === 0">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 4.5h9M6.2 4.5V3.2h3.6v1.3M4.6 4.5l.5 8.2h5.8l.5-8.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="nk-sign-tool" aria-label="Replay" title="Replay" x-on:click="replay()" x-bind:disabled="strokes.length === 0 || replaying">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M13 8.2a5 5 0 1 1-1.4-3.5M13 3.2v3.1H9.9" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <div class="nk-sign-save">
            <span class="nk-sign-save-label">Save as</span>
            <button type="button" class="nk-sign-export" x-on:click="save('png')" x-bind:disabled="strokes.length === 0">PNG</button>
            <button type="button" class="nk-sign-export" x-on:click="save('svg')" x-bind:disabled="strokes.length === 0">SVG</button>
        </div>
    </div>

    <p class="nk-sign-note" id="{{ $noteId }}">Press Control or Command Z to undo, add Shift to redo, and Delete to clear.</p>
    <span class="sr-only" aria-live="polite" x-text="live"></span>
    @if ($fieldName)
        <input type="hidden" name="{{ $fieldName }}" x-bind:value="svgText">
    @endif
</div>
