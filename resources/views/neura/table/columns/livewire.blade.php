@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $component = $extraAttributes['component'] ?? null;
    $props = $extraAttributes['props'] ?? [];

    if (!$component) {
        return;
    }

    $componentProps = array_merge([
        'row' => $row,
        'value' => $value,
    ], $props);
@endphp

@if($component)
    @livewire($component, $componentProps)
@endif

