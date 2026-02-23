@props([
    'icon' => null,
    'label' => '',
    'shortcut' => null,
    'variant' => 'default', // default, danger, primary
    'disabled' => false
])

@php
    $baseClasses = "w-full flex items-center gap-2 px-2.5 py-1.5 text-[13px] leading-snug text-left rounded-md transition-colors duration-100 cursor-pointer select-none";
    
    $variantClasses = match($variant) {
        'danger' => 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10',
        'primary' => 'text-primary hover:bg-primary/10',
        default => 'text-fg hover:bg-hover',
    };

    if ($disabled) {
        $variantClasses = 'text-fg-disabled cursor-not-allowed opacity-40';
    }
@endphp

<button type="button" 
    @if($disabled) disabled @else @click="close" @endif
    {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}>
    
    @if($icon)
        <neura::icon :name="$icon" class="size-4 shrink-0 opacity-70" />
    @elseif(filled($slot))
        <div class="size-4 shrink-0 flex items-center justify-center opacity-70">
            {{ $slot }}
        </div>
    @endif

    <span class="flex-1 truncate">
        {{ $label }}
    </span>

    @if($shortcut)
        <span class="text-xs text-fg-disabled font-sans ml-auto pl-4">
            {{ $shortcut }}
        </span>
    @endif
</button>


