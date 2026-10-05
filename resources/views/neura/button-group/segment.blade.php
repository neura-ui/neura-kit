@php
    $id = (string) ($item['id'] ?? '');
    $label = (string) ($item['label'] ?? '');
    $icon = $item['icon'] ?? null;
    $iconOnly = (bool) ($item['iconOnly'] ?? false);
    $content = $item['content'] ?? null;
    $confirm = $item['confirm'] ?? null;
    $confirmIcon = $item['confirmIcon'] ?? 'check';
    $href = $item['href'] ?? null;
    $itemDisabled = (bool) ($item['disabled'] ?? false);
    $collapsible = filled($icon) && ! $iconOnly && $content === null;
    $named = $iconOnly || $content !== null;
    $reserve = collect([$label])
        ->merge($item['reserve'] ?? [])
        ->push($confirm)
        ->filter(fn ($text) => filled($text))
        ->unique()
        ->values();
    $payload = [
        'id' => $id,
        'label' => $label,
        'method' => $item['method'] ?? null,
        'disabled' => $itemDisabled,
        'confirm' => $confirm,
        'resetAfter' => (int) ($item['resetAfter'] ?? 1800),
    ];
    if (isset($item['step']) && $item['step'] !== '' && $item['step'] !== null) {
        $payload['step'] = (int) $item['step'];
        if (filled($item['reel'] ?? null)) {
            $payload['reel'] = (string) $item['reel'];
        }
    }
    $reelValue = null;
    $reelSuffix = '';
    $reelDigits = [];
    if ($content !== null && preg_match('/^(\d+)(\D{0,4})$/', trim((string) $content), $reelMatch)) {
        $reelValue = (int) $reelMatch[1];
        $reelSuffix = $reelMatch[2];
        $reelDigits = str_split((string) $reelValue);
    }
    $reelMin = isset($item['min']) && $item['min'] !== '' && $item['min'] !== null ? (int) $item['min'] : null;
    $reelMax = isset($item['max']) && $item['max'] !== '' && $item['max'] !== null ? (int) $item['max'] : null;
    $morphGlyphs = [];
    $morphValues = [];
    if ($reelValue === null && $content !== null) {
        $seen = [];
        foreach (mb_str_split((string) $content) as $char) {
            $count = $seen[$char] ?? 0;
            $morphGlyphs[] = ['char' => $char, 'key' => $char.'-'.$count];
            $seen[$char] = $count + 1;
        }
        foreach ((array) ($item['values'] ?? []) as $value) {
            if (is_scalar($value) && (string) $value !== '') {
                $morphValues[] = (string) $value;
            }
        }
    }
    $tag = filled($href) && ! $itemDisabled ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @else type="button" @endif
    @class(['nk-group-segment'])
    data-key="{{ $id }}"
    @if ($iconOnly) data-icon-only @endif
    @if ($collapsible) data-collapsible @endif
    @if ($itemDisabled) aria-disabled="true" @endif
    @if ($named) aria-label="{{ $label }}" title="{{ $label }}" @endif
    x-bind:data-quiet="quiet({{ json_encode($id) }})"
    x-bind:disabled="disabled ? true : null"
    x-on:click="select({{ json_encode($payload) }})"
>
    <span class="nk-group-content" aria-hidden="true">
        <span class="nk-group-grid">
            @if (! $iconOnly && $content === null)
                @foreach ($reserve as $text)
                    <span class="nk-group-sizer">
                        @if (filled($icon))
                            <span class="nk-group-icon-box" aria-hidden="true"></span>
                        @endif
                        <span class="nk-group-label" @if ($collapsible) data-collapsible @endif>{{ $text }}</span>
                    </span>
                @endforeach
            @endif
            <span class="nk-group-phase" data-on x-bind:data-on="confirmId === {{ json_encode($id) }} ? null : ''">
                @if (filled($icon))
                    <span class="nk-group-icon" aria-hidden="true">
                        <neura::icon :name="$icon" />
                    </span>
                @endif
                @if ($reelValue !== null)
                    <span
                        class="nk-group-value nk-group-reel"
                        data-reel="{{ $reelValue }}"
                        @if ($reelMin !== null) data-min="{{ $reelMin }}" @endif
                        @if ($reelMax !== null) data-max="{{ $reelMax }}" @endif
                    >
                        <span class="nk-group-reel-track" wire:ignore>
                            @foreach ($reelDigits as $reelDigit)
                                <span class="nk-group-reel-column" data-digit="{{ $reelDigit }}">
                                    <span class="nk-group-reel-sizer">0</span>
                                    @for ($glyph = 0; $glyph <= 9; $glyph++)
                                        <span class="nk-group-reel-glyph" @if ((int) $reelDigit === $glyph) data-on @endif>{{ $glyph }}</span>
                                    @endfor
                                </span>
                            @endforeach
                        </span>
                        @if ($reelSuffix !== '')
                            <span class="nk-group-reel-suffix">{{ $reelSuffix }}</span>
                        @endif
                    </span>
                @elseif ($content !== null)
                    <span
                        class="nk-group-value nk-group-morph"
                        data-morph="{{ $content }}"
                        @if ($morphValues !== []) data-values="{{ json_encode($morphValues) }}" @endif
                    >
                        <span class="nk-group-morph-frame">
                            <span class="nk-group-morph-track" wire:ignore>
                                @foreach ($morphGlyphs as $glyph)
                                    <span class="nk-group-morph-glyph" data-glyph="{{ $glyph['key'] }}">{{ $glyph['char'] === ' ' ? "\u{00A0}" : $glyph['char'] }}</span>
                                @endforeach
                            </span>
                        </span>
                    </span>
                @elseif (! $iconOnly)
                    <span class="nk-group-label" @if ($collapsible) data-collapsible @endif>{{ $label }}</span>
                @endif
            </span>
            @if (filled($confirm))
                <span class="nk-group-phase nk-group-phase-in" x-bind:data-on="confirmId === {{ json_encode($id) }} ? '' : null">
                    <span class="nk-group-icon" aria-hidden="true">
                        <neura::icon :name="$confirmIcon" />
                    </span>
                    <span class="nk-group-label">{{ $confirm }}</span>
                </span>
            @endif
        </span>
    </span>
    @unless ($named)
        <span class="sr-only" x-text="confirmId === {{ json_encode($id) }} ? {{ json_encode($confirm ?: $label) }} : {{ json_encode($label) }}">{{ $label }}</span>
    @endunless
</{{ $tag }}>
