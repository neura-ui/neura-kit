@aware([
    'disabled' => null,
    'invalid' => null,
    'size' => null,
    'variant' => null,
    'label' => null,
    'description' => null,
    'indicator' => true,
])

@props([
    'label' => null,
])

<span
    x-bind:data-checked="_checked && !_indeterminate"
    x-bind:data-indeterminate="_indeterminate"

    x-bind:aria-checked="_indeterminate ? 'mixed' : (_checked ? 'true' : 'false')"
    x-bind:aria-invalid="@js($invalid) ? 'true' : null"

    x-ref="checkboxControl"
    x-on:click="toggle()"
    x-on:keydown.space.prevent="toggle()"
    x-on:keydown.enter.prevent="toggle()"

    tabindex="{{ $disabled ? '-1' : '0' }}"
    type="button"
    role="checkbox"

    class="
        rounded-full data-checked:bg-primary-600 dark:data-checked:bg-primary-500
        data-checked:text-white
        dark:bg-white/10 bg-neutral-800/10 text-md w-fit px-2 py-0.5
    "
>
{{ $label }}
</span>
