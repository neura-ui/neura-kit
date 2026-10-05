@props([
    'disabled' => false
])

@php
    use Neura\Kit\Support\PackResolver;

    $menuRoundedClass = PackResolver::rounded(neura_config('context-menu', 'rounded'));
    $menuShadowClass = PackResolver::shadow(neura_config('context-menu', 'shadow'));
@endphp

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
             x-transition:enter="nk-menu-motion"
             x-transition:enter-start="nk-menu-from"
             x-transition:enter-end="nk-menu-to"
             x-transition:leave="nk-menu-leaving"
             x-transition:leave-start="nk-menu-to"
             x-transition:leave-end="nk-menu-out"
             data-side="bottom"
             data-align="start"
             @click.stop=""
             @contextmenu.prevent="" 
             @keydown.escape.window="close"
             @class([
                 'nk-menu-motion fixed z-50 min-w-[180px] bg-surface-raised backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03] p-1 overflow-hidden',
                 $menuRoundedClass,
                 $menuShadowClass,
             ])
             :style="`top: ${y}px; left: ${x}px`"
             style="display: none;">
            
            @if(isset($content))
                {{ $content }}
            @endif
        </div>
    </template>
</div>


