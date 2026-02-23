@props([
    'components' => [],
])
@php
    $widthMap = [
        'xs' => 'w-full sm:w-72 max-w-full',
        'sm' => 'w-full sm:w-80 max-w-full',
        'md' => 'w-full sm:w-96 max-w-full',
        'lg' => 'w-full sm:w-[28rem] max-w-full',
        'xl' => 'w-full sm:w-[32rem] max-w-full',
        '2xl' => 'w-full sm:w-[36rem] max-w-full',
        'full' => 'w-full max-w-full',
    ];

    $widthRemMap = [
        'xs' => '18rem',
        'sm' => '20rem',
        'md' => '24rem',
        'lg' => '28rem',
        'xl' => '32rem',
        '2xl' => '36rem',
        'full' => '100vw',
    ];
@endphp
<div x-data="sideoverManager()" x-show="show" x-cloak class="fixed inset-0 z-[9998] pointer-events-none"
    @sideover-close.window="setShow(false)" @keydown.escape.window="show && closeSideoverOnEscape()">

    {{-- Backdrop (mobile) --}}
    <div
        x-show="show && showActiveComponent && !isTransitioning"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeSideoverOnClickAway()"
        class="pointer-events-auto absolute inset-0 bg-black/40 sm:bg-black/20 backdrop-blur-[2px] sm:backdrop-blur-none"
    ></div>

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
                    $widthClass = 'w-full sm:max-w-full';
                    $customStyle = "width: 100%; --sm-width: {$width};";
                }
            }

            $widthKey = $attrs['width'] ?? 'md';
            $pushWidth = $widthRemMap[$widthKey] ?? $widthKey;
        @endphp
        <div wire:key="{{ $id }}" x-ref="{{ $id }}" data-sideover-id="{{ $id }}"
            data-sideover-push-width="{{ $pushWidth }}"
            data-sideover-side="{{ $side }}"
            x-show="show && showActiveComponent && !isLoading && activeComponent === '{{ $id }}' && !isTransitioning"
            x-on:click.stop
            x-transition:enter="transition-transform ease-out duration-300" x-transition:enter-start="{{ $translateEnter }}"
            x-transition:enter-end="{{ $translateEnd }}"
            x-transition:leave="transition-transform ease-in duration-200" x-transition:leave-start="{{ $translateEnd }}"
            x-transition:leave-end="{{ $translateEnter }}"
            @if ($customStyle) style="{{ $customStyle }}" @endif
            class="pointer-events-auto fixed inset-y-0 {{ $isRight ? 'right-0' : 'left-0' }} flex flex-col {{ $widthClass }} bg-surface backdrop-blur-xl {{ $isRight ? 'sm:border-l' : 'sm:border-r' }} border-separator shadow-2xl sm:shadow-lg"
            role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="show && showActiveComponent && activeComponent === '{{ $id }}'">
            @livewire($component['name'], $component['arguments'], key($id))
        </div>
    @endforeach
</div>
