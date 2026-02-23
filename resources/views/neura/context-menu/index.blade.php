@props([
    'disabled' => false
])

<div x-data="contextMenu" 
    x-ref="trigger"
    @if(!$disabled) @contextmenu.prevent="open" @endif
    {{ $attributes->merge(['class' => 'inline-block']) }}>
    
    {{-- Trigger Content --}}
    {{ $slot }}

    {{-- Menu Content (Teleported to body to avoid overflow issues) --}}
    <template x-teleport="body">
        <div x-show="isOpen" 
             x-ref="menu"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop=""
             @contextmenu.prevent="" 
             @keydown.escape.window="close"
             class="fixed z-50 min-w-[180px] bg-surface-raised backdrop-blur-xl border border-edge rounded-lg shadow-lg py-1 overflow-hidden"
             :style="`top: ${y}px; left: ${x}px`"
             style="display: none;">
            
            @if(isset($content))
                {{ $content }}
            @endif
        </div>
    </template>
</div>


