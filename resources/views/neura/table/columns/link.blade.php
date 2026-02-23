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
        class="text-[13px] text-neutral-900 dark:text-neutral-100 hover:text-primary-600 dark:hover:text-primary-400 underline decoration-neutral-300 dark:decoration-neutral-600 underline-offset-2 hover:decoration-primary-400 dark:hover:decoration-primary-500 transition-colors"
    >
        {{ $labelValue }}
    </a>
</div>
