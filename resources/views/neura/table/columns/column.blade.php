@props([
    'value',
    'row' => null,
    'column' => null,
    'format' => null,
    'formatUsing' => null,
    'html' => null,
    'extraAttributes' => [],
])

@php
    $displayValue = $value;

    if ($format && is_callable($format)) {
        $displayValue = $format($value, $row);
    } elseif ($formatUsing) {
        $displayValue = match($formatUsing) {
            'currency' => '$' . number_format((float)$value, 2),
            'number' => number_format((float)$value),
            'percentage' => number_format((float)$value, 2) . '%',
            default => $value,
        };
    }

    $align = $extraAttributes['align'] ?? 'start';
    $alignClass = match($align) {
        'center' => 'text-center',
        'end', 'right' => 'text-right',
        default => 'text-left',
    };

    $isCopyable = $extraAttributes['copyable'] ?? false;
    $truncate = $extraAttributes['truncate'] ?? false;
    $truncateLength = $extraAttributes['truncateLength'] ?? 50;
    $placeholder = $extraAttributes['placeholder'] ?? null;

    $isEmpty = $displayValue === null || $displayValue === '';
@endphp

<div class="{{ $alignClass }}">
    @if ($isEmpty && $placeholder)
        <span class="nk-table-muted">{{ $placeholder }}</span>
    @elseif ($isEmpty)
        <span class="nk-table-dash">—</span>
    @else
        <div class="inline-flex items-center gap-1.5 max-w-full">
            <span class="nk-table-value {{ $truncate ? 'truncate' : '' }}"
                @if($truncate) style="max-width: {{ $truncateLength * 0.55 }}em;" title="{{ $displayValue }}" @endif
            >{{ $displayValue }}</span>

            @if($isCopyable && !$isEmpty)
                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click.stop="
                        await $clipboard('{{ addslashes($displayValue) }}');
                        copied = true;
                        setTimeout(() => copied = false, 1500);
                    "
                    class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-fast ease-standard"
                >
                    <template x-if="!copied">
                        <neura::icon name="clipboard-document" class="size-3.5 text-fg-muted hover:text-fg transition-colors" />
                    </template>
                    <template x-if="copied">
                        <neura::icon name="check" class="size-3.5 text-emerald-500" />
                    </template>
                </button>
            @endif
        </div>
    @endif
</div>
