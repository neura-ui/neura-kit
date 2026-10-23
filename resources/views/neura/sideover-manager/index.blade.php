@props([
    'components' => [],
])
@php
    use Neura\Kit\Support\PackResolver;

    $sideoverShadowClass = PackResolver::shadow(neura_config('sideover', 'shadow'));

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
    x-transition:leave="nk-sideover-hold" x-transition:leave-start="" x-transition:leave-end=""
    x-bind:data-stack="stackDirection"
    @sideover-close.window="setShow(false)" @keydown.escape.window="show && closeSideoverOnEscape()">

    {{-- Backdrop (mobile) --}}
    <div
        x-show="show && showActiveComponent"
        x-transition:enter="transition-opacity ease-enter duration-standard"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-standard duration-fast"
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
            x-show="show && showActiveComponent && !isLoading && activeComponent === '{{ $id }}'"
            x-on:click.stop
            x-transition:enter="nk-sideover-enter" x-transition:enter-start="nk-sideover-enter-from"
            x-transition:enter-end="nk-sideover-enter-to"
            x-transition:leave="nk-sideover-leave" x-transition:leave-start="nk-sideover-leave-from"
            x-transition:leave-end="nk-sideover-leave-to"
            @if ($customStyle) style="{{ $customStyle }}" @endif
            @class([
                'pointer-events-auto fixed inset-y-0 flex flex-col bg-surface backdrop-blur-xl border-separator',
                $isRight ? 'right-0 sm:border-l' : 'left-0 sm:border-r',
                $widthClass,
                $sideoverShadowClass,
            ])
            role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="show && showActiveComponent && activeComponent === '{{ $id }}'">
            @livewire($component['name'], $component['arguments'], key($id))
        </div>
    @endforeach
</div>
