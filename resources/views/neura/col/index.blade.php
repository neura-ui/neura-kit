@props([
    'span' => null,
    'start' => null,
    'end' => null,
    'responsive' => true,
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
    '2xl' => null,
    'rowSpan' => null,
    'rowStart' => null,
    'rowEnd' => null,
])

@php
    /**
     * Pass props as data attributes for TypeScript to consume
     * This is the cleanest approach for Tailwind 4
     */
    $dataAttributes = array_filter([
        'data-col-span' => $span,
        'data-col-start' => $start,
        'data-col-end' => $end,
        'data-responsive' => $responsive ? 'true' : 'false',
        'data-sm' => $sm,
        'data-md' => $md,
        'data-lg' => $lg,
        'data-xl' => $xl,
        'data-2xl' => $attributes->get('2xl'), // Handle numeric prop name
        'data-row-span' => $rowSpan,
        'data-row-start' => $rowStart,
        'data-row-end' => $rowEnd,
    ], fn($value) => $value !== null);

    /**
     * Build static classes array
     * These are safe Tailwind classes that are always in the build
     */
    $staticClasses = [];

    // Add 'auto' value classes
    if ($span === 'auto') $staticClasses[] = 'col-auto';
    if ($start === 'auto') $staticClasses[] = 'col-start-auto';
    if ($end === 'auto') $staticClasses[] = 'col-end-auto';
    if ($rowSpan === 'auto') $staticClasses[] = 'row-auto';

    // Always add the dynamic col class if we have dynamic props
    $hasDynamicProps = $span !== null || $sm !== null || $md !== null || $lg !== null ||
                       $xl !== null || $start !== null || $end !== null ||
                       $rowSpan !== null || $rowStart !== null || $rowEnd !== null;

    if ($hasDynamicProps) {
        $staticClasses[] = 'col-dynamic';
    }

    /**
     * Merge with user-provided classes from $attributes
     * This allows: <x-col span="4" class="bg-red-500 p-4">
     */
    $mergedAttributes = $attributes->class($staticClasses);
@endphp

<div
{{ $mergedAttributes }}
@foreach($dataAttributes as $key => $value)
    {{ $key }}="{{ $value }}"
@endforeach
data-slot="col"
>
{{ $slot }}
</div>
