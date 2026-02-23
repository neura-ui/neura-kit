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
        'soft' => 'hover:bg-hover focus:bg-hover',
        'danger' => 'hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 dark:hover:text-red-400 focus:text-red-600 focus:bg-red-50 dark:focus:bg-red-500/10 dark:focus:text-red-400',
    };

    $iconClasses = [
        'inline-flex shrink-0 mr-2 text-fg-muted',
        match ($variant) {
            'soft' => '',
            'danger' => 'group-hover:text-red-500 dark:group-hover:text-red-400',
        },
    ];

    $iconAttributes = (new Illuminate\View\ComponentAttributeBag())
        ->class($iconClasses);

    $classes = [
        'group flex items-center gap-2',
        'w-full px-2.5 py-1.5 text-[13px] leading-snug transition-colors duration-100 text-start',
        'text-fg',
        'rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))]',
        'opacity-40 cursor-not-allowed text-fg-disabled' => $disabled,
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
