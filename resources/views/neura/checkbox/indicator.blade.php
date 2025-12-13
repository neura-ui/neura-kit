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

$sizeClasses = match($size) {
    'xs' => 'size-4',
    'sm' => 'size-5',
    'md' => 'size-6',
    'lg' => 'size-7',
    'xl' => 'size-8',
    default => 'size-6',
};

$iconVariant = match($size) {
    'xs' => 'micro',
    'sm' => 'micro',
    'md' => 'micro',
    'lg' => 'mini',
    'xl' => 'mini',
    default => 'micro',
};

$iconSizeClasses = match($size) {
    'xs' => 'size-3',
    'sm' => 'size-4',
    'md' => 'size-4',
    'lg' => 'size-5',
    'xl' => 'size-5',
    default => 'size-4',
};

$buttonClasses = [
    'flex items-center justify-center border overflow-hidden appearance-none',
    'bg-white dark:bg-neutral-900',
    'disabled:cursor-not-allowed disabled:opacity-50',
    'transition-all duration-200',
    'shadow-none dark:shadow-sm disabled:shadow-none',
    'focus:ring-offset-0 focus:outline-none',
    $sizeClasses,
    'rounded-field',
    $checkboxColors['border'] => !$invalid,
    $checkboxColors['focus'] => !$invalid,
    $checkboxColors['invalid'] => $invalid,
    'hover:border-primary-400 dark:hover:border-primary-500' => !$disabled && !$invalid,
    $checkboxColors['checked'],
    $checkboxColors['indeterminate'],
];

$iconClasses = [
    $iconSizeClasses,
    'text-white dark:text-white',
    'transition-all duration-200'
];
@endphp

<div
    x-bind:data-checked="_checked && !_indeterminate"
    x-bind:data-indeterminate="_indeterminate"
    x-bind:aria-checked="_indeterminate ? 'mixed' : (_checked ? 'true' : 'false')"
    x-bind:aria-invalid="@js($invalid) ? 'true' : null"

    x-ref="checkboxControl"

    @if(!$disabled)
        x-on:click.stop="toggle()"
        x-on:keydown.space.prevent="toggle()"
        x-on:keydown.enter.prevent="toggle()"
    @endif

    tabindex="{{ $disabled ? '-1' : '0' }}"
    type="button"
    role="checkbox"

    @if($disabled)
        disabled
        aria-disabled="true"
    @endif

    data-slot="checkbox-indicator"

    {{ $attributes->class($buttonClasses) }}
>

    <neura::icon
        name="check"
        :variant="$iconVariant"
        @class($iconClasses)
        x-show="_checked && !_indeterminate"
        style="display:none"
    />

    <neura::icon
        name="minus"
        :variant="$iconVariant"
        @class($iconClasses)
        x-show="_indeterminate"
        style="display:none"
    />
</div>