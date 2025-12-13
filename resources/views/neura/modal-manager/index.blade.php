@props([
    'components' => [],
])

@php
    $widthMap = [
        'xs' => 'max-w-xs', 'sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg',
        'xl' => 'max-w-xl', '2xl' => 'max-w-2xl', '3xl' => 'max-w-3xl', '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl', '6xl' => 'max-w-6xl', '7xl' => 'max-w-7xl', 'full' => 'max-w-full',
    ];
@endphp

<div
    x-data="modalManager()"
    x-on:close.stop="setShow(false)"
    x-on:keydown.escape.window="show && closeModalOnEscape()"
    x-show="show"
    class="fixed inset-0 z-9999 overflow-y-auto"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
>
    <div
        class="fixed inset-0 bg-black/50 dark:bg-black/70"
        x-on:click="closeModalOnClickAway()"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show && showActiveComponent"
            x-on:click.stop
            class="relative flex w-full justify-center items-center"
            x-trap.noscroll="show && showActiveComponent"
            aria-modal="true"
            role="dialog"
            tabindex="-1"
        >
            @foreach($components as $id => $component)
                <div
                    class="block w-full bg-white dark:bg-neutral-900 rounded-lg shadow-xl border border-neutral-200 dark:border-neutral-800 {{ $widthMap[$component['modalAttributes']['maxWidth'] ?? 'md'] ?? 'max-w-[' . ($component['modalAttributes']['maxWidth'] ?? 'md') . ']' }}"
                    x-show="activeComponent === '{{ $id }}'"
                    x-ref="{{ $id }}"
                    wire:key="{{ $id }}"
                    data-modal-id="{{ $id }}"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    @livewire($component['name'], $component['arguments'], key($id))
                </div>
            @endforeach
        </div>
    </div>
</div>
