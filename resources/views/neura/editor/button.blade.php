@props([
    'active' => false,
    'icon' => null,
    'label' => null,
    'action' => null,
])

<button
    type="button"
    @if($action)
        x-on:click.prevent="{!! $action !!}"
    @endif
    @class([
        'p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none',
        'text-primary bg-primary/10' => $active,
        'text-neutral-500 dark:text-neutral-400' => !$active,
    ])
    {{ $attributes }}
    :class="{ 'text-primary bg-primary/10': {{ $active ? 'true' : 'false' }} }"
>
    @if($icon)
        <neura::icon :name="$icon" class="size-4" />
    @endif
    @if($label)
        <span class="sr-only">{{ $label }}</span>
    @endif
</button>

