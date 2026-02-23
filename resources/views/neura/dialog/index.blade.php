@props([
    'open' => false,
    'title' => null,
    'description' => null,
    'entangle' => null,
])

<div
    x-data="{
        open: @if($entangle) @entangle($entangle).live @else @js($open) @endif,
        close() { this.open = false; },
        openDialog() { this.open = true; }
    }"
    x-show="open"
    x-on:keydown.escape.window="close()"
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
    <div class="fixed inset-0 bg-surface-overlay" x-on:click="close()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-on:click.stop
            class="relative bg-surface-raised backdrop-blur-xl rounded-lg shadow-xl max-w-md w-full p-6 border border-edge"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            @if($title)
                <h3 class="text-lg font-semibold text-fg mb-2">
                    {{ $title }}
                </h3>
            @endif

            @if($description)
                <p class="text-sm text-fg-secondary mb-6">
                    {{ $description }}
                </p>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
