@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $buttons = $extraAttributes['buttons'] ?? [];
    $buttons = is_array($buttons) ? $buttons : (is_callable($buttons) ? $buttons($row) : []);
@endphp

<div class="flex items-center gap-2">
    @foreach($buttons as $button)
        @php
            $button = is_array($button) ? $button : ['label' => $button];
            $label = $button['label'] ?? '';
            $url = $button['url'] ?? '#';
            $variant = $button['variant'] ?? 'ghost';
            $size = $button['size'] ?? 'sm';
        @endphp
        <neura::button
            :variant="$variant"
            :size="$size"
            href="{{ $url }}"
        >
            {{ $label }}
        </neura::button>
    @endforeach
</div>

