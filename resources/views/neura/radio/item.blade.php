@aware([
    'variant' => 'default',
    'disabled' => false,
    'indicator' => true,
    'name' => '',
])

@props([
    'value',
    'label',
    'checked' => false,
    'disabled' => false,
    'description' => '',
    'icon' => '',
    'iconVariant' => 'outline',
    'iconClass' => '',
])

@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('radio');

    $isSegmented = $variant === 'segmented';
    $isCards = $variant === 'cards';
    $isPills = $variant === 'pills';
    $showIcon = ($isSegmented || $isCards) && filled($icon);
    $showInput = !$isSegmented && (!$isCards || $indicator) && !$isPills;

    $descriptionId = $description ? $value . '-' . $name . '-desc' : null;

    $labelClasses = [
        'flex-1 cursor-pointer text-sm font-medium flex items-center gap-3 transition-all duration-200 select-none',
        'text-fg-secondary hover:text-fg',
        'peer-disabled:opacity-50 peer-disabled:cursor-not-allowed',

        'peer-checked:[&_[data-slot=radio-item-indicator]]:bg-primary-500 dark:peer-checked:[&_[data-slot=radio-item-indicator]]:bg-primary-500',
        'peer-checked:[&_[data-slot=radio-item-indicator]]:border-primary-500 dark:peer-checked:[&_[data-slot=radio-item-indicator]]:border-primary-500',
        'peer-checked:[&_[data-slot=radio-item-indicator]]:after:opacity-100 peer-checked:[&_[data-slot=radio-item-indicator]]:after:scale-100',
        'peer-checked:[&_[data-slot=radio-item-indicator]]:shadow-sm',

        'text-fg-muted hover:text-fg p-2 rounded-field peer-checked:shadow-xs peer-checked:bg-white dark:peer-checked:bg-white/[0.08] hover:bg-neutral-50 dark:hover:bg-white/[0.06]' => $isSegmented,

        'px-3 py-1.5 rounded-full border border-neutral-200 dark:border-white/[0.12] bg-transparent text-fg-secondary hover:bg-neutral-50 dark:hover:bg-white/[0.04] hover:border-neutral-300 dark:hover:border-white/[0.18] peer-checked:bg-primary-50 dark:peer-checked:bg-primary-500/[0.12] peer-checked:text-primary-700 dark:peer-checked:text-primary-300 peer-checked:border-primary-200 dark:peer-checked:border-primary-500/25 focus-within:ring-2 focus-within:ring-primary-500/25' => $isPills,
    ];

    $containerClasses = [
        'relative isolate transition-all duration-200 flex items-center w-full',
        'group border rounded-xl bg-transparent border-neutral-200 dark:border-white/[0.10] border-l-[3px] border-l-transparent p-4 hover:bg-neutral-50/60 dark:hover:bg-white/[0.03] hover:border-neutral-300 dark:hover:border-white/[0.15] has-[:checked]:border-l-primary-500 dark:has-[:checked]:border-l-primary-400 has-[:checked]:bg-primary-50/40 dark:has-[:checked]:bg-primary-500/[0.05] has-[:checked]:border-primary-200 dark:has-[:checked]:border-white/[0.10] focus-within:ring-2 focus-within:ring-primary-500/25 focus-within:ring-offset-1 dark:focus-within:ring-offset-neutral-950' => $isCards,
        'opacity-50 cursor-not-allowed pointer-events-none' => $disabled,
    ];

    $descriptionClasses = [
        'text-fg-muted w-full text-xs text-start leading-relaxed',
        'pl-0 mt-0.5' => $isCards,
        '' => !$isCards,
    ];
@endphp

<div @class($containerClasses) x-init="$nextTick(() => {
    if (@js($checked) && ($data.state == null)) {
        $data.state = @js($value);
    }
})">
    <input data-slot="radio-item-control" class="peer" name="{{ $name }}" hidden
        id="{{ $value }}-{{ $name }}" value="{{ $value }}" type="radio" x-model="$data.state"
        @if($descriptionId) aria-describedby="{{ $descriptionId }}" @endif
        @if ($disabled) disabled @endif />

    <label for="{{ $value }}-{{ $name }}" @class($labelClasses)>
        @if ($indicator && !$isPills && !$isCards)
            <neura::radio.indicator />
        @endif

        @if ($isCards && $showIcon)
            <div class="flex items-center justify-center size-9 rounded-lg bg-neutral-100 dark:bg-white/[0.06] text-fg-secondary shrink-0 transition-colors duration-200 group-has-[:checked]:bg-primary-100 dark:group-has-[:checked]:bg-primary-500/10 group-has-[:checked]:text-primary-600 dark:group-has-[:checked]:text-primary-400">
                <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}" class="size-5 {{ $iconClass }}" />
            </div>
        @endif

        <div class="flex flex-col flex-1 min-w-0">
            <div class="flex items-center gap-2">
                @if ($showIcon && !$isCards)
                    <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}" class="{{ $iconClass }}" />
                @endif
                <span class="font-medium text-sm leading-tight">{{ $label }}</span>
            </div>

            @if ($description && !$isPills)
                <p data-slot="radio-item-control-description" @if($descriptionId) id="{{ $descriptionId }}" @endif @class($descriptionClasses)>
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($isCards && $indicator)
            <neura::radio.indicator />
        @endif

        @if ($isPills)
            <svg x-show="$data.state === @js($value)" x-transition:enter="transition-all duration-150"
                x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
                class="size-3.5 shrink-0 -ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"
                style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        @endif
    </label>

    @if ($isCards && $slot->isNotEmpty())
        <div class="mt-2">
            {{ $slot }}
        </div>
    @endif
</div>
