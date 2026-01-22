@aware([
    'disabled' => null,
    'invalid' => null,
    'size' => null,
    'variant' => null,
    'label' => null,
    'description' => null,
])

@props([
    'label' => null,
    'description' => null,
])

<div class="grid text-start grid-cols-[auto_1fr] gap-x-3 gap-y-1">
    <div class="grid grid-cols-subgrid col-span-2 items-center  ">
        <neura::checkbox.indicator class="col-start-1" />

        @if ($label)
            <neura::checkbox.label class="col-start-2" />
        @endif
    </div>

    @if ($description)
        <span data-slot="checkbox-description" class="text-sm text-neutral-500 dark:text-neutral-400 col-start-2">
            {{ $description }}
        </span>
    @endif
</div>
