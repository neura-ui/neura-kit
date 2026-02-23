@aware([
    'disabled' => null,
    'invalid' => null,
    'size' => null,
    'variant' => null,
    'label' => null,
    'description' => null,
    'indicator' => true,
    'icon' => null,
    'iconVariant' => 'outline',
])

@props([
    'label' => null,
    'description' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('checkbox');

    $paddingClasses = match ($size) {
        'xs' => 'px-3 py-2 gap-3',
        'sm' => 'px-3.5 py-2.5 gap-3',
        'md' => 'px-4 py-3 gap-4',
        'lg' => 'px-5 py-4 gap-4',
        'xl' => 'px-6 py-5 gap-5',
        default => 'px-4 py-3 gap-4',
    };

    $descriptionId = $label ? 'desc-' . crc32($label) : null;
@endphp

<div x-bind:data-checked="_checked && !_indeterminate" x-bind:data-indeterminate="_indeterminate"
    x-bind:aria-checked="_indeterminate ? 'mixed' : (_checked ? 'true' : 'false')"
    x-bind:aria-invalid="@js($invalid) ? 'true' : null" x-ref="checkboxControl"
    @if($descriptionId) x-bind:aria-describedby="'{{ $descriptionId }}'" @endif
    @if (!$disabled) x-on:click.stop="toggle()"
        x-on:keydown.space.prevent="toggle()"
        x-on:keydown.enter.prevent="toggle()" @endif
    tabindex="{{ $disabled ? '-1' : '0' }}" role="checkbox" @class([
        'isolate grid grid-cols-[auto_1fr_auto] items-center transition-all duration-200 cursor-pointer select-none',
        'rounded-xl border',
        'bg-transparent',
        'border-neutral-200 dark:border-white/[0.10]',
        'border-l-[3px] border-l-transparent',
        'hover:bg-neutral-50/60 dark:hover:bg-white/[0.03] hover:border-neutral-300 dark:hover:border-white/[0.15]',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/25 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-neutral-950',
        $paddingClasses,
        'data-[checked]:border-l-primary-500 dark:data-[checked]:border-l-primary-400 data-[checked]:bg-primary-50/40 dark:data-[checked]:bg-primary-500/[0.05] data-[checked]:border-primary-200 dark:data-[checked]:border-white/[0.10]',
        'opacity-50 cursor-not-allowed pointer-events-none' => $disabled,
        $colors['invalid'] => $invalid && !$disabled,
    ])>

    @if ($icon)
        <div class="flex items-center justify-center size-9 rounded-lg bg-neutral-100 dark:bg-white/[0.06] text-fg-secondary shrink-0 transition-colors duration-200"
            x-bind:class="(_checked && !_indeterminate) ? '!bg-primary-100 dark:!bg-primary-500/10 !text-primary-600 dark:!text-primary-400' : ''"
        >
            <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}" class="size-5" />
        </div>
    @endif

    <div class="flex flex-col gap-0.5 min-w-0" @if(!$icon) style="grid-column: span 2" @endif>
        @if ($label)
            <span class="font-medium text-fg text-sm leading-tight">
                {{ $label }}
            </span>
        @else
            <neura::checkbox.label />
        @endif

        @if ($description)
            <span @if($descriptionId) id="{{ $descriptionId }}" @endif class="text-xs text-fg-muted leading-relaxed">
                {{ $description }}
            </span>
        @endif
    </div>

    @if ($indicator)
        <neura::checkbox.indicator class="z-10 shrink-0" />
    @endif
</div>
