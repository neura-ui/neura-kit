@props([
    'name' => null,
    'heading' => null,
    'back' => true,
    'backLabel' => null,
])

@php
    $backLabel = $backLabel ?? ucfirst(neura_trans('back'));
@endphp

<div x-show="activePanel === @js($name)" x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0"
    style="display:none" x-cloak data-slot="select-panel" data-panel="{{ $name }}"
    {{ $attributes->merge(['class' => 'min-w-0']) }}>

    @if ($back || filled($heading))
        <div class="flex items-center gap-1.5 px-1 pb-2 mb-1 border-b border-separator">
            @if ($back)
                <button type="button" x-on:click.stop="backPanel()" data-slot="select-panel-back"
                    class="flex items-center justify-center size-6 rounded-md text-fg-muted hover:text-fg hover:bg-hover cursor-pointer transition-colors focus:outline-none focus:bg-hover"
                    aria-label="{{ $backLabel }}" title="{{ $backLabel }}">
                    <neura::icon name="chevron-left" variant="mini" class="size-4" />
                </button>
            @endif

            <span @class([
                'text-sm truncate',
                'font-medium text-fg' => filled($heading),
                'text-fg-muted' => blank($heading),
            ])>{{ $heading ?? $backLabel }}</span>
        </div>
    @endif

    <div class="px-1 pb-1 pt-0.5">
        {{ $slot }}
    </div>
</div>
