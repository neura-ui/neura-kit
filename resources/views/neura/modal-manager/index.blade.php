@props([
    'components' => [],
])
@php
    $widthMap = [
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        'full' => 'max-w-full',
    ];
@endphp
<div
    x-data="modalManager()"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[9999] overflow-y-auto"
    @modal-close.window="setShow(false)"
    @keydown.escape.window="show && closeModalOnEscape()"
>
    <div
        class="fixed inset-0 bg-black/50 dark:bg-black/70"
        @click="closeModalOnClickAway()"
        aria-hidden="true"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="isLoading" class="relative flex w-fit justify-center">
            <div class="bg-white dark:bg-neutral-900 rounded-lg shadow-xl p-8">
                <neura::icon.loading data-slot="loading-indicator" class="text-neutral-600 dark:text-neutral-400"/>
            </div>
        </div>

        <div x-show="show && showActiveComponent && !isLoading"
             x-transition
             @click="closeModalOnClickAway()"
             class="relative flex w-full justify-center"
             x-trap.inert.noscroll="show && showActiveComponent"
             role="dialog"
             aria-modal="true"
             tabindex="-1"
        >
            @foreach ($components as $id => $component)
                @php
                    $attrs = $component['modalAttributes'] ?? [];
                    $maxWidth = $attrs['maxWidth'] ?? 'md';
                    $widthClass =
                        $attrs['maxWidthClass']
                        ?? $widthMap[$maxWidth]
                        ?? "max-w-[{$maxWidth}]";
                @endphp
                <div
                    wire:key="{{ $id }}"
                    x-ref="{{ $id }}"
                    data-modal-id="{{ $id }}"
                    x-show="activeComponent === '{{ $id }}' && !isTransitioning"
                    x-on:click.stop
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="
                        w-full
                        {{ $widthClass }}
                        bg-white dark:bg-neutral-900
                        rounded-lg shadow-xl
                        border border-neutral-200 dark:border-neutral-800
                    "
                >
                    @livewire(
                        $component['name'],
                        $component['arguments'],
                        key($id)
                    )
                </div>
            @endforeach
        </div>
    </div>
</div>
