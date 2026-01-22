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

    $labelClasses = [
        'flex-1 cursor-pointer text-sm font-medium flex items-center gap-3 transition-all duration-200 select-none',
        'text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-neutral-100',
        'peer-disabled:opacity-50 peer-disabled:cursor-not-allowed',

        // Indicator styling via peer-checked
        'peer-checked:[&_[data-slot=radio-item-indicator]]:bg-primary-500 dark:peer-checked:[&_[data-slot=radio-item-indicator]]:bg-primary-800',
        'peer-checked:[&_[data-slot=radio-item-indicator]]:border-primary-500 dark:peer-checked:[&_[data-slot=radio-item-indicator]]:border-primary-800',
        'peer-checked:[&_[data-slot=radio-item-indicator]]:after:opacity-100 peer-checked:[&_[data-slot=radio-item-indicator]]:after:scale-100',
        'peer-checked:[&_[data-slot=radio-item-indicator]]:shadow-sm',

        'text-neutral-500 hover:text-neutral-900 p-2 rounded-field peer-checked:shadow-xs dark:text-white/70 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/50 hover:bg-primary-50/50 dark:hover:bg-primary-900/30' => $isSegmented,

        'px-3 py-1 rounded-full border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-500 peer-checked:text-white peer-checked:border-transparent' => $isPills,
    ];

    $containerClasses = [
        'relative isolate transition-all duration-200 flex items-center w-full',
        'group border rounded-box bg-white dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 has-[:checked]:border-primary-500/50 has-[:checked]:bg-primary-50/30 dark:has-[:checked]:bg-primary-500/5 dark:has-[:checked]:border-primary-500/50' => $isCards,
        'opacity-50 cursor-not-allowed pointer-events-none' => $disabled,
    ];

    $descriptionClasses = [
        'text-neutral-500 dark:text-neutral-400 w-full text-sm text-start',
        'pl-0 mt-1' => $isCards,
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
        @if ($disabled) disabled @endif />

    <label for="{{ $value }}-{{ $name }}" @class($labelClasses)>
        @if ($indicator && !$isPills && !$isCards)
            <neura::radio.indicator />
        @endif

        <div class="flex flex-col flex-1">
            <div class="flex items-center gap-2">
                @if ($showIcon)
                    <neura::icon name="{{ $icon }}" variant="{{ $iconVariant }}" class="{{ $iconClass }}" />
                @endif
                <span class="font-semibold">{{ $label }}</span>
            </div>

            @if ($description && !$isPills)
                <p data-slot="radio-item-control-description" @class($descriptionClasses)>
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($isCards && $indicator)
            <neura::radio.indicator />
        @endif
    </label>

    @if ($isCards && $slot->isNotEmpty())
        <div class="mt-2">
            {{ $slot }}
        </div>
    @endif
</div>
