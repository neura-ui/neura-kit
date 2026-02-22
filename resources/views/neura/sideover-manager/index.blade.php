@props([
    'components' => [],
])
@php
    $widthMap = [
        'xs' => 'w-72 max-w-full',
        'sm' => 'w-80 max-w-full',
        'md' => 'w-96 max-w-full',
        'lg' => 'w-[28rem] max-w-full',
        'xl' => 'w-[32rem] max-w-full',
        '2xl' => 'w-[36rem] max-w-full',
        'full' => 'w-full max-w-full',
    ];
@endphp
<div x-data="sideoverManager()" x-show="show" x-cloak class="fixed inset-0 z-[9998] overflow-hidden"
    @sideover-close.window="setShow(false)" @keydown.escape.window="show && closeSideoverOnEscape()">
    <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="closeSideoverOnClickAway()" aria-hidden="true"
        x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <div x-show="isLoading" class="fixed inset-0 flex items-center justify-center pointer-events-none">
        <div class="bg-white dark:bg-neutral-900 rounded-lg shadow-xl p-8">
            <neura::icon.loading data-slot="loading-indicator" class="text-neutral-600 dark:text-neutral-400" />
        </div>
    </div>

    @foreach ($components as $id => $component)
        @php
            $attrs = $component['sideoverAttributes'] ?? [];
            $side = $attrs['side'] ?? 'right';
            $isRight = $side === 'right';
            $translateEnter = $isRight ? 'translate-x-full' : '-translate-x-full';
            $translateEnd = 'translate-x-0';

            if (isset($attrs['widthClass'])) {
                $widthClass = $attrs['widthClass'];
                $customStyle = '';
            } else {
                $width = $attrs['width'] ?? 'md';
                if (isset($widthMap[$width])) {
                    $widthClass = $widthMap[$width];
                    $customStyle = '';
                } else {
                    $widthClass = 'max-w-full';
                    $customStyle = "width: {$width};";
                }
            }
        @endphp
        <div wire:key="{{ $id }}" x-ref="{{ $id }}" data-sideover-id="{{ $id }}"
            x-show="show && showActiveComponent && !isLoading && activeComponent === '{{ $id }}' && !isTransitioning"
            x-on:click.stop
            x-transition:enter="ease-out duration-300" x-transition:enter-start="{{ $translateEnter }}"
            x-transition:enter-end="{{ $translateEnd }}"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="{{ $translateEnd }}"
            x-transition:leave-end="{{ $translateEnter }}"
            @if ($customStyle) style="{{ $customStyle }}" @endif
            class="fixed inset-y-0 {{ $isRight ? 'right-0' : 'left-0' }} flex flex-col {{ $widthClass }} bg-white dark:bg-neutral-900 shadow-xl border-neutral-200 dark:border-neutral-800 {{ $isRight ? 'border-l' : 'border-r' }}"
            role="dialog" aria-modal="true" tabindex="-1" x-trap.inert.noscroll="show && showActiveComponent && activeComponent === '{{ $id }}'">
            @livewire($component['name'], $component['arguments'], key($id))
        </div>
    @endforeach
</div>
