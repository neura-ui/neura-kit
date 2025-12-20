@aware([
    'collapsable' => true,
    'variant' => 'default',
    'label' => null,
    'icon' => null,
])

{{-- Group header --}}
<div class="in-[:has([data-collapsed]_&)]:hidden">
    <div
        @if($collapsable)
            x-on:click="expand()"
        @endif
        class="
            group
            flex items-center justify-between
            w-full
            px-3 py-2
            rounded-lg
            cursor-pointer
            text-sm font-medium
            text-neutral-600 dark:text-neutral-300
            hover:bg-neutral-100 dark:hover:bg-white/5
            transition
        "
    >
        <div class="flex items-center gap-2">
            @if($icon)
                <neura::icon
                    name="{{ $icon }}"
                    class="size-4 text-neutral-400"
                />
            @endif

            <neura::heading
                level="h6"
                size="xs"
                class="font-medium text-inherit"
            >
                {{ $label }}
            </neura::heading>
        </div>

        {{-- Chevron --}}
        <neura::icon
            x-show="expanded"
            name="chevron-down"
            class="size-4 text-neutral-400"
        />
        <neura::icon
            x-show="!expanded"
            name="chevron-right"
            style="display:none;"
            class="size-4 text-neutral-400"
        />
    </div>
</div>

{{-- Group items --}}
<div
    @if ($collapsable)
        x-show="expanded"
        x-collapse
    @endif
    class="
        mt-1 ml-3 pl-3
        relative
        flex flex-col gap-y-1
        border-l border-neutral-200 dark:border-white/10

        in-[:has([data-collapsed]_&)]:border-0
        in-[:has([data-collapsed]_&)]:pl-0
        in-[:has([data-collapsed]_&)]:ml-0
    "
>
    {{ $slot }}
</div>
