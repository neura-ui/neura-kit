@props([
    'icon' => 'ellipsis-horizontal',
    'iconVariant' => 'mini',
    'panel' => null,
    'label' => null,
])

<button type="button" data-slot="option-action"
    @if ($panel) x-on:click.stop="openPanel(@js($panel))" @endif
    @if (filled($label)) aria-label="{{ $label }}" title="{{ $label }}" @endif
    {{ $attributes->merge(['class' => 'flex items-center justify-center size-6 -my-0.5 rounded-md text-fg-muted hover:text-fg hover:bg-active cursor-pointer transition-colors focus:outline-none focus:bg-active shrink-0']) }}>
    @if (filled($icon))
        <neura::icon :name="$icon" :variant="$iconVariant" class="size-4" />
    @endif

    {{ $slot }}
</button>
