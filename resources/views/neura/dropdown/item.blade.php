@props([
    'disabled' => false,
    'icon' => null,
    'iconAfter' => null,
    'iconVariant' => 'mini',
    'shortcut' => null,
    'variant' => 'soft',
    'as' => 'div',
])

@php
    $isForm = $as === 'form';

    $variantClasses = match ($variant) {
        'soft' => 'hover:bg-neutral-100 focus:bg-neutral-100 dark:hover:bg-white/5 dark:focus:bg-white/5',
        'danger' => 'hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-400/20 dark:hover:text-red-400 focus:text-red-600 focus:bg-red-50 dark:focus:bg-red-400/20 dark:focus:text-red-400',
    };

    $iconClasses = [
        'inline-flex shrink-0 mr-2',
        match ($variant) {
            'soft' => '',
            'danger' => 'hover:text-red-500 dark:hover:text-red-400 focus:text-red-500 dark:focus:text-red-400',
        },
    ];

    $iconAttributes = (new Illuminate\View\ComponentAttributeBag())
        ->class($iconClasses);

    $classes = [
        'flex items-center gap-2',
        'w-full px-3 py-1.5 text-sm transition-colors duration-200 text-start',
        'text-neutral-800 dark:text-white',
        'rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))]',
        'opacity-50 cursor-not-allowed text-neutral-500 dark:text-neutral-400' => $disabled,
        $variantClasses . ' cursor-pointer' => !$disabled,
    ];

    $itemAttributes = $attributes
        ->class(Arr::toCssClasses($classes))
        ->merge([
            'disabled' => $disabled,
            'tabindex' => $disabled ? '-1' : '0',
        ]);
@endphp
<neura::button.abstract
    :as="$as"
    :attributes="$itemAttributes"
    data-slot="dropdown-item"
>
    @if ($isForm)
        <button type="submit" class="flex w-full items-center gap-2 text-left cursor-pointer">
    @endif

    @if (filled($icon))
        <neura::icon :name="$icon" :variant="$iconVariant" :attributes="$iconAttributes" />
    @endif

    @if ($slot->isNotEmpty())
        <span class="flex-1">
            {{ $slot }}
        </span>
    @endif

    @if (filled($iconAfter))
        <neura::icon :name="$iconAfter" :variant="$iconVariant" :attributes="$iconAttributes" />
    @endif

    @if ($isForm)
        </button>
    @endif
</neura::button.abstract>
