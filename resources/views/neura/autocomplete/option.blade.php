@props([
    'value',
    'label' => null,
])

<li
    data-slot="option"
    data-value="{{ $value }}"
    data-label="{{ $label ?? $value }}"
    class="hidden"
>
    {{ $label ?? $value }}
</li>

