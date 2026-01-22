@aware([
    'disabled' => null,
    'invalid' => null,
    'size' => null,
    'variant' => null,
    'label' => null,
    'description' => null,
    'indicator' => true,
])

@props([
    'label' => null,
    'description' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('checkbox');

    $paddingClasses = match ($size) {
        'xs' => 'p-2 gap-2',
        'sm' => 'p-3 gap-3',
        'md' => 'p-4 gap-4',
        'lg' => 'p-5 gap-5',
        'xl' => 'p-6 gap-6',
        default => 'p-4 gap-4',
    };
@endphp

<div x-bind:data-checked="_checked && !_indeterminate" x-bind:data-indeterminate="_indeterminate"
    x-bind:aria-checked="_indeterminate ? 'mixed' : (_checked ? 'true' : 'false')"
    x-bind:aria-invalid="@js($invalid) ? 'true' : null" x-ref="checkboxControl"
    @if (!$disabled) x-on:click.stop="toggle()"
        x-on:keydown.space.prevent="toggle()"
        x-on:keydown.enter.prevent="toggle()" @endif
    tabindex="{{ $disabled ? '-1' : '0' }}" role="checkbox" @class([
        'isolate grid grid-cols-[1fr_auto] items-center transition-all duration-200 cursor-pointer select-none',
        'rounded-box border bg-white dark:bg-neutral-900',
        'border-neutral-200 dark:border-neutral-800',
        'hover:bg-neutral-50 dark:hover:bg-neutral-800/50 hover:border-neutral-300 dark:hover:border-neutral-700',
        'focus:outline-none focus:ring-2 focus:ring-primary-500/20',
        $paddingClasses,
        'data-[checked]:border-primary-500/50 data-[checked]:bg-primary-50/30 dark:data-[checked]:bg-primary-500/5 dark:data-[checked]:border-primary-500/50',
        'opacity-50 cursor-not-allowed pointer-events-none' => $disabled,
        $colors['invalid'] => $invalid && !$disabled,
    ])>

    <div class="flex flex-col gap-1">
        @if ($label)
            <span class="font-medium text-neutral-900 dark:text-neutral-100">
                {{ $label }}
            </span>
        @else
            <neura::checkbox.label />
        @endif

        @if ($description)
            <span class="text-sm text-neutral-500 dark:text-neutral-400">
                {{ $description }}
            </span>
        @endif
    </div>

    @if ($indicator)
        <neura::checkbox.indicator class="z-10" />
    @endif
</div>
