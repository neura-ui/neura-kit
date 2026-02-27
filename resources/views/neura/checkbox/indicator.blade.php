@aware([
    'checked' => false,
    'indeterminate' => false,
    'disabled' => false,
    'invalid' => false,
    'size' => 'md',
])

@props([
    'disabled' => false,
    'invalid' => false,
    'size' => 'md',
])

@php
    use Neura\Kit\Support\PackResolver;

    $checkboxColors = PackResolver::inputColor('checkbox');

    $sizeClasses = match ($size) {
        'xs' => 'size-4',
        'sm' => 'size-[18px]',
        'md' => 'size-5',
        'lg' => 'size-6',
        'xl' => 'size-7',
        default => 'size-5',
    };

    $iconVariant = match ($size) {
        'xs' => 'micro',
        'sm' => 'micro',
        'md' => 'micro',
        'lg' => 'mini',
        'xl' => 'mini',
        default => 'micro',
    };

    $iconSizeClasses = match ($size) {
        'xs' => 'size-2.5',
        'sm' => 'size-3',
        'md' => 'size-3.5',
        'lg' => 'size-4',
        'xl' => 'size-5',
        default => 'size-3.5',
    };

    $buttonClasses = [
        'flex items-center justify-center border overflow-hidden appearance-none',
        'bg-transparent',
        'disabled:cursor-not-allowed disabled:opacity-50',
        'transition-all duration-200',
        'shadow-none disabled:shadow-none',
        'focus:ring-offset-0 focus:outline-none',
        $sizeClasses,
        'rounded-[5px]',
        $checkboxColors['border'] => !$invalid,
        $checkboxColors['focus'] => !$invalid,
        $checkboxColors['invalid'] => $invalid,
        'hover:border-neutral-400 dark:hover:border-white/30' => !$disabled && !$invalid,
        $checkboxColors['checked'],
        $checkboxColors['indeterminate'],
    ];

    $iconClasses = [$iconSizeClasses, 'text-white', 'transition-all duration-200'];
@endphp

<div x-bind:data-checked="_checked && !_indeterminate" x-bind:data-indeterminate="_indeterminate"
    x-bind:aria-checked="_indeterminate ? 'mixed' : (_checked ? 'true' : 'false')"
    x-bind:aria-invalid="@js($invalid) ? 'true' : null" x-ref="checkboxControl"
    @if (!$disabled) x-on:click.stop="toggle()"
        x-on:keydown.space.prevent="toggle()"
        x-on:keydown.enter.prevent="toggle()" @endif
    tabindex="{{ $disabled ? '-1' : '0' }}" type="button" role="checkbox"
    @if ($disabled) disabled
        aria-disabled="true" @endif data-slot="checkbox-indicator"
    {{ $attributes->merge(['class' => Arr::toCssClasses($buttonClasses)]) }}>

    <neura::icon name="check" :variant="$iconVariant" @class($iconClasses) x-show="_checked && !_indeterminate"
        x-transition:enter="transition-all duration-150"
        x-transition:enter-start="opacity-0 scale-50"
        x-transition:enter-end="opacity-100 scale-100"
        style="display:none" />

    <neura::icon name="minus" :variant="$iconVariant" @class($iconClasses) x-show="_indeterminate"
        x-transition:enter="transition-all duration-150"
        x-transition:enter-start="opacity-0 scale-50"
        x-transition:enter-end="opacity-100 scale-100"
        style="display:none" />
</div>
