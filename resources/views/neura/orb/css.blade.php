@props([
    'variant' => 'S1',
    'size' => null,
    'label' => null,
    'pill' => false,
    'color' => null,
])

@php
    use Neura\Kit\Support\OrbCssGeometry;
    use Neura\Kit\Support\PackResolver;

    $variant = OrbCssGeometry::normalize($variant) ?? 'S1';
    $family = OrbCssGeometry::family($variant);
    $nodes = OrbCssGeometry::nodes($variant);
    $orbSize = (int) ($size ?: OrbCssGeometry::DEFAULT_SIZE);
    $scale = $orbSize / OrbCssGeometry::STAGE;
    $task = OrbCssGeometry::task($variant);
    $text = $label ?? ($task.'…');
    $orbColor = PackResolver::orbColor($color);
    $isPill = filter_var($pill, FILTER_VALIDATE_BOOLEAN);
@endphp

<span
    {{ $attributes->class(['nk-orb-css inline-flex', $orbColor])->merge([
        'data-slot' => 'orb',
        'data-engine' => 'css',
        'data-variant' => $variant,
        'data-pill' => $isPill ? '' : null,
    ]) }}
>
    <span
        class="nk-orb-glyph"
        @if ($isPill) aria-hidden="true" @else role="img" aria-label="{{ $text }}" @endif
        style="width: {{ $orbSize }}px; height: {{ $orbSize }}px; --orb-k: {{ $scale }};"
    >
        @if ($family === 'lattice')
            <span class="nk-orb-lattice" data-variant="{{ $variant }}">
                @foreach ($nodes as $cell)
                    <span
                        class="nk-orb-cell"
                        @if ($cell['still']) data-still @endif
                        @if ($cell['mid']) data-mid @endif
                        style="
                            left: {{ $cell['left'] }}px;
                            top: {{ $cell['top'] }}px;
                            animation-delay: {{ $cell['delay'] }}ms;
                            --orb-ax: {{ $cell['ax'] }}px;
                            --orb-ay: {{ $cell['ay'] }}px;
                            --orb-bx: {{ $cell['bx'] }}px;
                            --orb-by: {{ $cell['by'] }}px;
                        "
                    ></span>
                @endforeach
            </span>
        @elseif ($family === 'ring')
            <span class="nk-orb-ring" data-variant="{{ $variant }}">
                @foreach ($nodes as $dot)
                    <span
                        class="nk-orb-ring-dot"
                        style="
                            --orb-rx: {{ $dot['rx'] }}px;
                            --orb-ry: {{ $dot['ry'] }}px;
                            animation-delay: {{ $dot['delay'] }}ms;
                        "
                    ></span>
                @endforeach
            </span>
        @elseif ($family === 'helix')
            <span class="nk-orb-helix" data-variant="{{ $variant }}">
                @foreach ($nodes as $dot)
                    <span
                        class="nk-orb-helix-dot"
                        style="@foreach ($dot['style'] as $prop => $value){{ $prop }}: {{ $value }};@endforeach"
                    ></span>
                @endforeach
            </span>
        @elseif ($family === 'morph')
            <span class="nk-orb-morph" data-variant="{{ $variant }}">
                @foreach ($nodes as $dot)
                    <span
                        class="nk-orb-morph-dot"
                        style="
                            --m-1: {{ $dot['m1'] }};
                            --m-2: {{ $dot['m2'] }};
                            --m-3: {{ $dot['m3'] }};
                            --m-4: {{ $dot['m4'] }};
                            @if ($dot['depth']) --m-depth: {{ $dot['depth'] }}; @endif
                            @if ($dot['delay']) animation-delay: {{ $dot['delay'] }}; @endif
                        "
                    ></span>
                @endforeach
            </span>
        @else
            <span class="nk-orb-lens" data-variant="{{ $variant }}">
                <span class="nk-orb-shape nk-orb-shape-a"></span>
                <span class="nk-orb-shape nk-orb-shape-b"></span>
                <span class="nk-orb-shape nk-orb-shape-c"></span>
                @if ($variant === 'B1')
                    <span class="nk-orb-shape nk-orb-shape-d"></span>
                @endif
            </span>
        @endif
    </span>

    @if ($isPill)
        <span class="nk-orb-pill-label">{{ $text }}</span>
    @endif
</span>
