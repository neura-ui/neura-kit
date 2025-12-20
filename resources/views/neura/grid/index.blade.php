@props([
    'cols' => '1',
    'gap' => 'md',
    'responsive' => true,

    // NEW
    'align' => 'stretch',   // start | center | end | stretch
    'justify' => 'stretch', // start | center | end | stretch

    // OPTIONAL advanced
    'colStart' => null,
    'colEnd' => null,

    // Responsive breakpoints (can be customized)
    'sm' => null,  // Number of columns at sm breakpoint
    'md' => null,  // Number of columns at md breakpoint
    'lg' => null,  // Number of columns at lg breakpoint
])

@php
    /*
    |--------------------------------------------------------------------------
    | Dynamic Responsive Columns Generator
    |--------------------------------------------------------------------------
    | Generates responsive grid classes dynamically based on column count
    | Example: 6 columns -> grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6
    */
    if (!function_exists('generateResponsiveCols')) {
        function generateResponsiveCols($cols, $responsive, $sm = null, $md = null, $lg = null) {
        // CSS Grid auto functions - no responsive needed
        if ($cols === 'auto-fit') {
            return 'grid-cols-[repeat(auto-fit,minmax(0,1fr))]';
        }
        if ($cols === 'auto-fill') {
            return 'grid-cols-[repeat(auto-fill,minmax(0,1fr))]';
        }
        if ($cols === 'auto') {
            return 'grid-cols-[repeat(auto-fit,minmax(min-content,1fr))]';
        }

        // Custom CSS value - no responsive needed
        if (is_string($cols) && (str_contains($cols, '[') || str_contains($cols, 'repeat') || str_contains($cols, 'minmax'))) {
            return "grid-cols-[{$cols}]";
        }

        // If not responsive, return simple class
        if (!$responsive) {
            $numCols = is_numeric($cols) ? (int)$cols : $cols;
            return is_numeric($numCols) ? "grid-cols-{$numCols}" : "grid-cols-{$cols}";
        }

        // Convert cols to number for calculations
        $numCols = is_numeric($cols) ? (int)$cols : (int)$cols;
        
        if ($numCols <= 0) {
            return 'grid-cols-1';
        }

        // Calculate default breakpoints if not provided
        // Smart defaults based on column count
        if ($sm === null) {
            $sm = $numCols >= 4 ? 2 : ($numCols >= 2 ? 2 : null);
        }
        if ($md === null) {
            $md = $numCols >= 6 ? 3 : ($numCols >= 3 ? 2 : ($numCols >= 2 ? 2 : null));
        }
        if ($lg === null) {
            $lg = $numCols;
        }

        // Build responsive classes
        $classes = ['grid-cols-1']; // Always start with 1 column on mobile

        if ($sm !== null && $sm > 0 && $sm < $numCols) {
            $classes[] = "sm:grid-cols-{$sm}";
        }
        if ($md !== null && $md > 0 && $md < $numCols) {
            $classes[] = "md:grid-cols-{$md}";
        }
        if ($lg !== null && $lg > 0) {
            $classes[] = "lg:grid-cols-{$lg}";
        } else {
            $classes[] = "lg:grid-cols-{$numCols}";
        }

        return implode(' ', $classes);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    */
    $colsClasses = generateResponsiveCols($cols, $responsive, $sm, $md, $lg);

    /*
    |--------------------------------------------------------------------------
    | Gap
    |--------------------------------------------------------------------------
    */
    $gapClasses = match ($gap) {
        'none' => 'gap-0',
        'xs' => 'gap-1',
        'sm' => 'gap-2',
        'md' => 'gap-4',
        'lg' => 'gap-6',
        'xl' => 'gap-8',
        default => 'gap-4',
    };

    /*
    |--------------------------------------------------------------------------
    | Alignment (grid-safe)
    |--------------------------------------------------------------------------
    */
    $alignClasses = match ($align) {
        'start' => 'items-start',
        'center' => 'items-center',
        'end' => 'items-end',
        default => 'items-stretch',
    };

    $justifyClasses = match ($justify) {
        'start' => 'justify-items-start',
        'center' => 'justify-items-center',
        'end' => 'justify-items-end',
        default => 'justify-items-stretch',
    };

    /*
    |--------------------------------------------------------------------------
    | Optional column positioning
    |--------------------------------------------------------------------------
    */
    $colStartClass = $colStart ? "col-start-{$colStart}" : null;
    $colEndClass   = $colEnd ? "col-end-{$colEnd}" : null;

    /*
    |--------------------------------------------------------------------------
    | Final classes
    |--------------------------------------------------------------------------
    */
    $classes = array_filter([
        'grid',
        $colsClasses,
        $gapClasses,
        $alignClasses,
        $justifyClasses,
        $colStartClass,
        $colEndClass,
    ]);
@endphp

<div {{ $attributes->class($classes) }} data-slot="grid">
    {{ $slot }}
</div>
