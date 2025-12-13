@aware([
    'disabled' => false,
    'reverse' => false
])

<button
    @click="isVisible = !isVisible"
    :aria-expanded="isVisible"
    {{ $attributes->merge([
        'class' => Arr::toCssClasses([
            'flex w-full items-center gap-2 justify-start px-6 py-4 text-xl font-bold dark:text-white',
            'cursor-pointer' => !$disabled,
            'flex-row-reverse' => $reverse,
        ])
    ]) }}
>
    <span class="flex-1 text-start font-normal text-base">{{ $slot }}</span>
    <span x-show="isVisible" style="display: none"><neura::icon class="size-5" name="chevron-up" /></span>
    <span x-show="!isVisible"><neura::icon class="size-5" name="chevron-down" /></span>
</button>
