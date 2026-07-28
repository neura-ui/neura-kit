{{--
    Inlines every file-kind icon once as a <symbol>, so the rows rendered by
    Alpine can pick one at runtime with <use> instead of shipping a dozen SVGs
    per entry.
--}}
@props(['id' => 'nk-fm', 'icons' => []])

@php
    use Illuminate\Support\Facades\Blade;

    $symbols = [];

    foreach (array_unique($icons) as $icon) {
        $markup = Blade::render('<neura::icon name="'.$icon.'" set="heroicons" />');

        if (! preg_match('/<svg([^>]*)>(.*)<\/svg>/s', $markup, $matches)) {
            continue;
        }

        // Outline heroicons carry stroke/fill on the root node; keep those so
        // the <use> reference renders identically, drop the sizing/identity ones.
        $attributes = preg_replace(
            '/\s(class|width|height|id|data-slot|data-variant|aria-hidden|xmlns)="[^"]*"/',
            '',
            $matches[1]
        );

        $symbols[$icon] = [
            'attributes' => trim((string) $attributes),
            'body' => trim($matches[2]),
        ];
    }
@endphp

<svg class="hidden" aria-hidden="true" focusable="false">
    @foreach ($symbols as $icon => $symbol)
        <symbol id="{{ $id }}-{{ $icon }}" {!! $symbol['attributes'] !!}>{!! $symbol['body'] !!}</symbol>
    @endforeach
</svg>
