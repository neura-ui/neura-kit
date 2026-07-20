@aware([
    'searchable' => false,
    'multiple' => false,
])

@props([
    'value' => null,
    'label' => null,
    'icon' => null,
    'iconClass' => null,
    'iconVariant' => 'outline',
    'prefix' => null,
    'suffix' => null,
])

@php
    use Illuminate\View\ComponentSlot;
    use Neura\Kit\Support\PackResolver;

    $rawLabel = $label ?? (filled($slot->__toString()) ? $slot->__toString() : $value);

    $displayLabel = is_string($rawLabel) ? $rawLabel : (string) $rawLabel;

    // prefix / suffix accept either a string prop or an <x-slot:prefix|suffix> slot
    $hasPrefix = $prefix instanceof ComponentSlot ? $prefix->isNotEmpty() : filled($prefix);
    $hasSuffix = $suffix instanceof ComponentSlot ? $suffix->isNotEmpty() : filled($suffix);

    $checkboxColors = PackResolver::inputColor('checkbox');
    $checkboxRounded = PackResolver::rounded(neura_config('checkbox', 'rounded'));
@endphp

<li tabindex="0" x-bind:data-value="@js($value)" x-bind:data-label="@js($displayLabel)"
    x-show="isItemShown(@js($value))" x-on:mouseleave="handleMouseLeave($el)"
    @if (!$searchable)
        x-on:mouseover="$focus.focus($el); handleMouseEnter(@js($value))"
    @else
        x-on:mouseover="handleMouseEnter(@js($value))"
    @endif
    x-bind:id="'option-' + getFilteredIndex(@js($value))"
    x-on:click="select(@js($value))"
    x-bind:data-selected="isSelected(@js($value)) ? true : null"
    x-bind:aria-selected="isSelected(@js($value)) ? 'true' : 'false'"
    x-bind:class="{
        'bg-active': isFocused(@js($value)),
    }"
    role="option" data-slot="option"
    class="
        rounded-[calc(var(--popup-round)-var(--popup-padding))] col-span-full grid grid-cols-subgrid items-center
        focus:bg-active px-2.5 py-1.5 w-full text-sm
        self-center cursor-pointer hover:bg-hover transition-colors duration-100
    ">
    <div class="flex items-center gap-1.5 min-w-0">
        @if ($multiple)
            <span
                data-slot="option-checkbox"
                aria-hidden="true"
                x-bind:data-checked="isSelected(@js($value)) ? true : null"
                @class([
                    'flex items-center justify-center size-4 shrink-0 border overflow-hidden pointer-events-none transition-all duration-150',
                    $checkboxRounded,
                    $checkboxColors['border'],
                    $checkboxColors['checked'],
                ])
            >
                <span
                    class="flex items-center justify-center"
                    x-show="isSelected(@js($value))"
                    x-transition:enter="transition-all duration-150"
                    x-transition:enter-start="opacity-0 scale-50"
                    x-transition:enter-end="opacity-100 scale-100"
                    style="display:none"
                >
                    <neura::icon name="check" variant="micro" class="size-2.5 text-white" />
                </span>
            </span>
        @endif

        @if ($hasPrefix)
            <span data-slot="option-prefix" class="flex items-center shrink-0 text-fg-muted [&_[data-slot=icon]]:size-4">{{ $prefix }}</span>
        @elseif (filled($icon))
            <neura::icon :name="$icon" variant="{{ $iconVariant }}" @class([
                'size-4 text-fg-muted shrink-0',
                $iconClass,
            ]) />
        @endif

        <span class="text-start text-fg truncate min-w-0">{{ $displayLabel }}</span>
    </div>

    @if ($hasSuffix)
        <div data-slot="option-suffix" class="flex items-center gap-1 justify-self-end min-w-0">
            {{ $suffix }}
        </div>
    @endif
</li>
