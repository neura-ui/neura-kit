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
    'description' => null
])

<div
    class="
        isolate grid gap-3 dark:data-[checked]:bg-neutral-700 data-[checked]:bg-neutral-300 group p-4 border rounded-box bg-white dark:bg-neutral-800 hover:bg-neutral-200
        dark:hover:bg-neutral-700 transition-colors duration-200 border-black/10 dark:border-white/15 w-full text-start grid-cols-[1fr_24px]
    "

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
>

    <div class="col-start-1 flex flex-col gap-2">
        <neura::checkbox.label/>

        <span class="text-sm text-neutral-500 dark:text-neutral-400 col-start-1">
            {{ $description }}
        </span>
    </div>

    <neura::checkbox.indicator class="col-start-2 group-hover:bg-transparent group-hover:bg-neutral-300 z-10" />

</div>