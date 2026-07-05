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

    $displayLabel = is_string($rawLabel) ? $rawLabel : (string) $rawLabel;
    $slotContent = filled($slot->__toString()) ? $slot->__toString() : $displayLabel;
@endphp

<li tabindex="0" x-bind:data-value="@js($value)" x-bind:data-label="@js($displayLabel)"
    x-show="isItemShown(@js($value))" x-on:mouseleave="handleMouseLeave($el)"
    @if (!$searchable) x-on:mouseover="$focus.focus($el)" @endif
    x-on:mouseover="handleMouseEnter(@js($value))"
    x-bind:id="'option-' + getFilteredIndex(@js($value))"
    x-on:click="select(@js($value))"
    x-bind:class="{
        'bg-active': isFocused(@js($value)),

        '*:data-[slot=icon]:opacity-100': isSelected(@js($value)),
    }"
    role="option" data-slot="option"
    class="
        rounded-[calc(var(--popup-round)-var(--popup-padding))] col-span-full grid grid-cols-subgrid items-center
        focus:bg-active px-2.5 py-1 w-full text-sm
        self-center cursor-pointer hover:bg-hover transition-colors
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
        <neura::checkbox.indicator size="xs" :checked="false" :disabled="true" class="shrink-0 pointer-events-none" />
    </div>

    <div class="flex items-center gap-1.5 min-w-0">
        @if (filled($icon))
            <neura::icon :name="$icon" variant="{{ $iconVariant }}" @class([
                'size-4 text-fg-muted shrink-0',
                $iconClass,
            ]) />
        @endif

        <span class="text-start text-fg truncate min-w-0">{{ $displayLabel }}</span>
    </div>
</li>
