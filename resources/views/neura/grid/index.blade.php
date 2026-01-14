@props([
    'cols' => '1',
    'gap' => 'md',
    'responsive' => true,
    'align' => 'stretch',
    'justify' => 'stretch',
    'colStart' => null,
    'colEnd' => null,
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
    '2xl' => null,
])

@php
    /*
    |--------------------------------------------------------------------------
    | Dynamic Responsive Columns Generator
    |--------------------------------------------------------------------------
    */
    if (!function_exists('generateResponsiveCols')) {
        function generateResponsiveCols($cols, $responsive, $sm = null, $md = null, $lg = null, $xl = null, $xxl = null) {
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

            // Custom CSS value with brackets, repeat, or minmax
            if (is_string($cols) && (
                str_contains($cols, '[') ||
                str_contains($cols, 'repeat') ||
                str_contains($cols, 'minmax')
            )) {
                return "grid-cols-[{$cols}]";
            }

            // Convert to number
            $numCols = is_numeric($cols) ? (int)$cols : (int)$cols;

            // Fallback if conversion fails
            if ($numCols <= 0 || !is_numeric($cols)) {
                return 'grid-cols-1';
            }

            // If not responsive, return simple class
            if (!$responsive) {
                return $numCols <= 12 ? "grid-cols-{$numCols}" : "grid-cols-[{$numCols}]";
            }

            // Calculate smart defaults for breakpoints
            if ($sm === null) {
                $sm = match(true) {
                    $numCols >= 4 => 2,
                    $numCols >= 2 => 2,
                    default => null,
                };
            }

            if ($md === null) {
                $md = match(true) {
                    $numCols >= 6 => 3,
                    $numCols >= 3 => 2,
                    $numCols >= 2 => 2,
                    default => null,
                };
            }

            if ($lg === null) {
                $lg = $numCols;
            }

            // Build responsive classes array
            $classes = ['grid-cols-1']; // Mobile-first: always start with 1

            // Only add breakpoint if it's different and valid
            if ($sm !== null && $sm > 0 && $sm <= 12 && $sm != 1) {
                $classes[] = "sm:grid-cols-{$sm}";
            }

            if ($md !== null && $md > 0 && $md <= 12 && $md != $sm) {
                $classes[] = "md:grid-cols-{$md}";
            }

            if ($lg !== null && $lg > 0 && $lg <= 12) {
                $classes[] = "lg:grid-cols-{$lg}";
            }

            // Add xl and 2xl if provided
            if ($xl !== null && $xl > 0 && $xl <= 12) {
                $classes[] = "xl:grid-cols-{$xl}";
            }

            if ($xxl !== null && $xxl > 0 && $xxl <= 12) {
                $classes[] = "2xl:grid-cols-{$xxl}";
            }

            return implode(' ', $classes);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Build all classes
    |--------------------------------------------------------------------------
    */

    // Columns
    $colsClasses = generateResponsiveCols($cols, $responsive, $sm, $md, $lg, $xl, $attributes->get('2xl'));

    // Gap
    $gapClasses = match ($gap) {
        'none' => 'gap-0',
        'xs' => 'gap-1',
        'sm' => 'gap-2',
        'lg' => 'gap-6',
        'xl' => 'gap-8',
        default => 'gap-4',
    };

    // Alignment (vertical)
    $alignClasses = match ($align) {
        'start' => 'items-start',
        'center' => 'items-center',
        'end' => 'items-end',
        default => 'items-stretch',
    };

    // Justify (horizontal)
    $justifyClasses = match ($justify) {
        'start' => 'justify-items-start',
        'center' => 'justify-items-center',
        'end' => 'justify-items-end',
        default => 'justify-items-stretch',
    };

    // Optional column positioning (validate range)
    $colStartClass = ($colStart && $colStart >= 1 && $colStart <= 13)
        ? "col-start-{$colStart}"
        : null;

    $colEndClass = ($colEnd && $colEnd >= 1 && $colEnd <= 13)
        ? "col-end-{$colEnd}"
        : null;

    // Assemble base grid classes (without custom user classes)
    $gridClasses = array_filter([
        'grid',
        $colsClasses,
        $gapClasses,
        $alignClasses,
        $justifyClasses,
        $colStartClass,
        $colEndClass,
    ]);

    /**
     * Merge with user-provided classes from $attributes
     * This allows: <x-grid cols="6" class="bg-gray-100 p-4">
     */
    $mergedAttributes = $attributes->class($gridClasses);
@endphp

<div {{ $mergedAttributes }} data-slot="grid">
    {{ $slot }}
</div>
