@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $urlValue = $extraAttributes['url'] ?? $value ?? '#';
    $targetValue = $extraAttributes['target'] ?? '_self';
    $labelValue = $extraAttributes['label'] ?? $value ?? 'Link';

    if (is_callable($urlValue) && !is_string($urlValue)) {
        $urlValue = $urlValue($row);
    }

    if (is_callable($labelValue) && !is_string($labelValue)) {
        $labelValue = $labelValue($row);
    }
@endphp

<div>
    <a
        href="{{ $urlValue }}"
        target="{{ $targetValue }}"
        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline"
    >
        {{ $labelValue }}
    </a>
</div>
