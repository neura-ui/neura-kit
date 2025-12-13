@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $componentName = $extraAttributes['component'] ?? 'neura::table.columns.column';
@endphp

<x-dynamic-component
    :component="$componentName"
    :value="$value"
    :row="$row"
/>

