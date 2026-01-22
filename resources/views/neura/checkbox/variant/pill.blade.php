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
])

@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('checkbox');

    $sizeClasses = match ($size) {
        'xs' => 'px-2 py-0.5 text-xs',
        'sm' => 'px-2.5 py-0.5 text-sm',
        'md' => 'px-3 py-1 text-sm',
        'lg' => 'px-4 py-1.5 text-base',
        'xl' => 'px-5 py-2 text-base',
        default => 'px-3 py-1 text-sm',
    };
@endphp

<span x-bind:data-checked="_checked && !_indeterminate" x-bind:data-indeterminate="_indeterminate"
    x-bind:aria-checked="_indeterminate ? 'mixed' : (_checked ? 'true' : 'false')"
    x-bind:aria-invalid="@js($invalid) ? 'true' : null" x-ref="checkboxControl"
    @if (!$disabled) x-on:click.stop="toggle()"
        x-on:keydown.space.prevent="toggle()"
        x-on:keydown.enter.prevent="toggle()" @endif
    tabindex="{{ $disabled ? '-1' : '0' }}" role="checkbox" @class([
        'inline-flex items-center justify-center font-medium transition-all duration-200 select-none cursor-pointer',
        'rounded-full border',
        'bg-white dark:bg-neutral-900 border-primary-200 dark:border-primary-900 text-neutral-700 dark:text-neutral-300',
        'hover:bg-neutral-50 dark:hover:bg-neutral-800',
        'focus:outline-none focus:ring-2 focus:ring-primary-500/20',
        $sizeClasses,
        $colors['checked'],
        'data-[checked]:text-white dark:data-[checked]:text-white data-[checked]:border-transparent',
        'opacity-50 cursor-not-allowed pointer-events-none' => $disabled,
        $colors['invalid'] => $invalid && !$disabled,
    ])>
    {{ $label ?? $slot }}
</span>
