@props([
    'icon' => null,
    'label' => '',
    'shortcut' => null,
    'variant' => 'default', // default, danger, primary
    'disabled' => false
])

@php
    $baseClasses = "w-full flex items-center gap-2 px-3 py-1.5 text-sm text-left transition-colors cursor-pointer select-none";
    
    $variantClasses = match($variant) {
        'danger' => 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20',
        'primary' => 'text-primary hover:bg-primary/10',
        default => 'text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700/50',
    };

    if ($disabled) {
        $variantClasses = 'text-neutral-400 dark:text-neutral-600 cursor-not-allowed opacity-50';
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
        <span class="text-xs text-neutral-400 dark:text-neutral-500 font-sans ml-auto pl-4">
            {{ $shortcut }}
        </span>
    @endif
</button>


