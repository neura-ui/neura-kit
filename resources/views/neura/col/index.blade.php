@props([
    'span' => null,      // col-span (1, 2, 3, etc. or 'full' for all columns)
    'start' => null,     // col-start
    'end' => null,       // col-end
    'responsive' => true, // Make columns responsive (full width on mobile if start/end are set)
    
    // Optional row properties
    'rowSpan' => null,  // row-span
    'rowStart' => null,  // row-start
    'rowEnd' => null,    // row-end
])

@php
    /*
    |--------------------------------------------------------------------------
    | Column Span
    |--------------------------------------------------------------------------
    */
    $hasStartOrEnd = ($start !== null || $end !== null) && $span === null;
    
    $colSpanClass = match (true) {
        $hasStartOrEnd && $responsive => 'col-span-1',
        $span === 'full' => 'col-span-full',
        $span === 'auto' => 'col-auto',
        is_numeric($span) && (int)$span > 0 && (int)$span <= 12 => "col-span-{$span}",
        is_numeric($span) && (int)$span > 12 => "col-span-[{$span}]",
        default => null,
    };

    /*
    |--------------------------------------------------------------------------
    | Column Start (responsive)
    |--------------------------------------------------------------------------
    | On mobile/sm/md, if start/end are set, columns take full width
    | On lg+, start/end properties apply (when grid has full column count)
    */
    $colStartClass = match (true) {
        $start === 'auto' => 'col-start-auto',
        $responsive && $hasStartOrEnd && is_numeric($start) && (int)$start > 0 && (int)$start <= 13 => 
            "lg:col-start-{$start}",
        $responsive && $hasStartOrEnd && $start !== null => 
            "lg:col-start-[{$start}]",
        is_numeric($start) && (int)$start > 0 && (int)$start <= 13 => "col-start-{$start}",
        $start !== null => "col-start-[{$start}]",
        default => null,
    };

    /*
    |--------------------------------------------------------------------------
    | Column End (responsive)
    |--------------------------------------------------------------------------
    | On mobile/sm/md, if start/end are set, columns take full width
    | On lg+, end properties apply (when grid has full column count)
    */
    $colEndClass = match (true) {
        $end === 'auto' => 'col-end-auto',
        $responsive && $hasStartOrEnd && ($end === 'full' || $end === 'rest') => 
            'lg:col-end-[-1]',
        $responsive && $hasStartOrEnd && is_numeric($end) && (int)$end > 0 && (int)$end <= 13 => 
            "lg:col-end-{$end}",
        $responsive && $hasStartOrEnd && $end !== null => 
            "lg:col-end-[{$end}]",
        $end === 'full' || $end === 'rest' => 'col-end-[-1]',
        is_numeric($end) && (int)$end > 0 && (int)$end <= 13 => "col-end-{$end}",
        $end !== null => "col-end-[{$end}]",
        default => null,
    };

    /*
    |--------------------------------------------------------------------------
    | Row Span
    |--------------------------------------------------------------------------
    */
    $rowSpanClass = match (true) {
        $rowSpan === 'full' => 'row-span-full',
        $rowSpan === 'auto' => 'row-auto',
        is_numeric($rowSpan) && (int)$rowSpan > 0 && (int)$rowSpan <= 6 => "row-span-{$rowSpan}",
        is_numeric($rowSpan) && (int)$rowSpan > 6 => "row-span-[{$rowSpan}]",
        default => null,
    };

    /*
    |--------------------------------------------------------------------------
    | Row Start
    |--------------------------------------------------------------------------
    */
    $rowStartClass = match (true) {
        $rowStart === 'auto' => 'row-start-auto',
        is_numeric($rowStart) && (int)$rowStart > 0 && (int)$rowStart <= 7 => "row-start-{$rowStart}",
        $rowStart !== null => "row-start-[{$rowStart}]",
        default => null,
    };

    /*
    |--------------------------------------------------------------------------
    | Row End
    |--------------------------------------------------------------------------
    */
    $rowEndClass = match (true) {
        $rowEnd === 'auto' => 'row-end-auto',
        $rowEnd === 'full' || $rowEnd === 'rest' => 'row-end-[-1]',
        is_numeric($rowEnd) && (int)$rowEnd > 0 && (int)$rowEnd <= 7 => "row-end-{$rowEnd}",
        $rowEnd !== null => "row-end-[{$rowEnd}]",
        default => null,
    };

    /*
    |--------------------------------------------------------------------------
    | Final classes
    |--------------------------------------------------------------------------
    */
    $classes = array_filter([
        $colSpanClass,
        $colStartClass,
        $colEndClass,
        $rowSpanClass,
        $rowStartClass,
        $rowEndClass,
    ]);
@endphp

<div {{ $attributes->class($classes) }} data-slot="col">
    {{ $slot }}
</div>

