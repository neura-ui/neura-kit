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
        'xs' => 'px-2 py-0.5 text-xs gap-1',
        'sm' => 'px-2.5 py-1 text-xs gap-1.5',
        'md' => 'px-3 py-1.5 text-sm gap-1.5',
        'lg' => 'px-4 py-2 text-sm gap-2',
        'xl' => 'px-5 py-2.5 text-base gap-2',
        default => 'px-3 py-1.5 text-sm gap-1.5',
    };

    $checkIconSize = match ($size) {
        'xs', 'sm' => 'size-3',
        'lg', 'xl' => 'size-4',
        default => 'size-3.5',
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
        'bg-transparent border-neutral-200 dark:border-white/[0.12] text-fg-secondary',
        'hover:bg-neutral-50 dark:hover:bg-white/[0.04] hover:border-neutral-300 dark:hover:border-white/[0.18]',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/25 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-neutral-950',
        $sizeClasses,
        'data-[checked]:bg-primary-50 dark:data-[checked]:bg-primary-500/[0.12] data-[checked]:text-primary-700 dark:data-[checked]:text-primary-300 data-[checked]:border-primary-200 dark:data-[checked]:border-primary-500/25',
        'opacity-50 cursor-not-allowed pointer-events-none' => $disabled,
        $colors['invalid'] => $invalid && !$disabled,
    ])>
    <svg x-show="_checked && !_indeterminate" x-transition:enter="transition-all duration-150"
        x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
        class="{{ $checkIconSize }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"
        style="display:none">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    {{ $label ?? $slot }}
</span>
