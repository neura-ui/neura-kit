@aware([
    'searchable' => false,
])

@props([
    'value' => null,
    'label' => null,
    'icon' => null,
    'iconClass' => null,
    'iconVariant' => 'outline',
])

@php

    $rawLabel = $label ?? (filled($slot->__toString()) ? $slot->__toString() : $value);

    $displayLabel = is_string($rawLabel) ? html_entity_decode($rawLabel, ENT_QUOTES, 'UTF-8') : $rawLabel;
    $slotContent = filled($slot->__toString()) ? $slot->__toString() : $displayLabel;
@endphp

<li tabindex="0" x-bind:data-value="@js($value)" x-bind:data-label="@js($displayLabel)"
    x-show="isItemShown(@js($value))" x-on:mouseleave="handleMouseLeave($el)"
    @if (!$searchable) x-on:mouseover="$focus.focus($el)" @endif
    x-on:mouseover="handleMouseEnter(@js($value))"
    x-bind:id="'option-' + getFilteredIndex(@js($value))"
    x-on:click="select(@js($value))"
    x-bind:class="{
        'bg-neutral-300 dark:bg-neutral-700 ': isFocused(@js($value)),

        '*:data-[slot=icon]:opacity-100': isSelected(@js($value)),
    }"
    role="option" data-slot="option"
    class="
        rounded-[calc(var(--popup-round)-var(--popup-padding))] col-span-full grid grid-cols-subgrid items-center
        focus:bg-neutral-100 focus:dark:bg-neutral-700 px-3 py-1.5 w-full text-[1rem]
        self-center cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors
    ">
    <div x-data="{
        _checked: false,
        _indeterminate: false,
        init() {
            this._checked = isSelected(@js($value));
            this.$watch(() => isSelected(@js($value)), (selected) => {
                this._checked = selected;
            });
        }
    }" x-on:click.stop="select(@js($value))">
        <neura::checkbox.indicator size="sm" :checked="false" :disabled="true" class="shrink-0" />
    </div>
    @if (filled($icon))
        <neura::icon :name="$icon" variant="{{ $iconVariant }}" @class([
            'z-10 size-5 text-neutral-500 dark:text-neutral-400 flex-shrink-0',
            $iconClass,
        ]) />
    @else
        <span class="w-5"></span>
    @endif

    <span class="text-start text-neutral-950 dark:text-neutral-50 truncate min-w-0">{!! $displayLabel !!}</span>
</li>
