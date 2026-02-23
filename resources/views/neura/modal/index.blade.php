@props([
    'name' => null,
    'open' => false,
    'managed' => false,
    'size' => 'md',
    'closeable' => true,
    'persistent' => false,
    'closeOnBackdrop' => true,
    'closeOnEscape' => true,
    'entangle' => null,
    'maxWidth' => null,
])

@php
    // Map predefined sizes to Tailwind classes
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

    // Determine max width class and custom style
    if ($maxWidth) {
        // Check if it's a predefined size
        if (isset($widthMap[$maxWidth])) {
            $maxWidthClass = $widthMap[$maxWidth];
            $customMaxWidth = null;
        } else {
            // Custom value - use inline style
            $maxWidthClass = '';
            $customMaxWidth = $maxWidth;
        }
    } else {
        // Use size prop
        $maxWidthClass = $widthMap[$size] ?? 'max-w-md';
        $customMaxWidth = null;
    }

    $isOpen = $entangle
        ? $entangle
        : ($open ?? false);
@endphp

<div
    x-data="{
        @if($name)
        open: @entangle("modals.{$name}.open").live,
        @elseif($entangle)
        open: @entangle($entangle).live,
        @else
        open: @js($isOpen),
        @endif
        close() {
            @if($persistent)
                return;
            @endif

            @if($name)
                if (typeof $wire !== 'undefined' && typeof $wire.closeModal === 'function') {
                    $wire.closeModal('{{ $name }}');
                } else {
                    this.open = false;
                }
            @else
                this.open = false;
            @endif
        },
        openModal() {
            @if($name)
                if (typeof $wire !== 'undefined' && typeof $wire.openModal === 'function') {
                    $wire.openModal('{{ $name }}');
                } else {
                    this.open = true;
                }
            @else
                this.open = true;
            @endif
        }
    }"
    @modal-close.window="close()"
    @command-close.window="close()"
    x-show="open"
    x-on:keydown.escape.window="@if($closeOnEscape && !$persistent) close() @endif"
    style="display: none; z-index: 100;"
    class="fixed inset-0 overflow-y-auto"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
>
    <div
        class="fixed inset-0 bg-surface-overlay"
        @if($closeOnBackdrop) x-on:click="close()" @endif
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-on:click.stop
            @if($customMaxWidth) style="max-width: {{ $customMaxWidth }};" @endif
            class="relative bg-surface-raised backdrop-blur-xl rounded-lg shadow-xl w-full {{ $maxWidthClass }} border border-edge"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            {{ $slot }}
        </div>
    </div>
</div>
