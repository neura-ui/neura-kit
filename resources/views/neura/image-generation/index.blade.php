@props([
    'prompt' => '',
    'resolution' => null,
    'aspect' => 'square',
    'label' => null,
    /** generating | done */
    'status' => 'generating',
    /** Image URL when status=done */
    'src' => null,
    'alt' => null,
])

@php
    $promptText = (string) ($prompt ?? '');
    $isDone = $status === 'done' || filled($src);
    $labelText = $label ?? ($isDone ? neura_trans('imageGenerated') : neura_trans('generatingImage'));
    $altText = $alt ?? ($promptText !== '' ? $promptText : $labelText);

    $box = match ($aspect) {
        'portrait' => ['w' => 200, 'h' => 260, 'label' => '768 × 1024'],
        'landscape' => ['w' => 280, 'h' => 180, 'label' => '1024 × 768'],
        default => ['w' => 220, 'h' => 220, 'label' => '1024 × 1024'],
    };

    $resolutionText = $resolution ?? $box['label'];
    $glow = (int) round(0.67 * min($box['w'], $box['h']));
@endphp

<div
    {{ $attributes->class(['nk-ig-wrap'])->merge([
        'data-slot' => 'image-generation',
        'data-aspect' => $aspect,
        'data-status' => $isDone ? 'done' : 'generating',
        'style' => "width: {$box['w']}px; max-width: 100%;",
    ]) }}
>
    <div
        class="nk-ig-canvas"
        role="img"
        aria-label="{{ $altText }}"
        style="height: {{ $box['h'] }}px; max-width: 100%; aspect-ratio: auto;"
    >
        @if ($isDone && filled($src))
            <img class="nk-ig-image" src="{{ $src }}" alt="{{ $altText }}" />
        @else
            <span class="nk-ig-dots" aria-hidden="true"></span>
            <span class="nk-ig-glow" aria-hidden="true" style="--ig-glow: {{ $glow }}px;"></span>
        @endif
        <span class="nk-ig-res">{{ $resolutionText }}</span>
    </div>

    <div class="nk-ig-meta" style="max-width: none;">
        <span @class(['nk-ig-label', 'nk-ig-shimmer' => ! $isDone])>{{ $labelText }}</span>
        @if ($promptText !== '')
            <span class="nk-ig-prompt">“{{ $promptText }}”</span>
        @endif
    </div>
</div>
