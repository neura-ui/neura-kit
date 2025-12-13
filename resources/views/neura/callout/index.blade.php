@props([
    'icon' => null,
    'variant' => 'primary',
    'inline' => false,
])

@php
    $inline = filled($inline) && $inline;
    
    $variantClasses = match($variant) {
        'secondary' => 'bg-secondary-50 dark:bg-secondary-900/50 border-secondary-200 dark:border-secondary-700/60',
        'success' => 'bg-success-50 dark:bg-success-950/60 border-success-300 dark:border-success-700/60',
        'warning' => 'bg-warning-50 dark:bg-warning-950/60 border-warning-300 dark:border-warning-600/60',
        'danger' => 'bg-danger-50 dark:bg-danger-950/60 border-danger-300 dark:border-danger-700/60',
        'info' => 'bg-info-50 dark:bg-info-950/60 border-info-300 dark:border-info-700/60',
        default => 'bg-primary-50 dark:bg-primary-950/60 border-primary-200 dark:border-primary-700/60',
    };

    $iconColorClass = match($variant) {
        'secondary' => 'text-secondary-600 dark:text-secondary-400',
        'success' => 'text-success-600 dark:text-success-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'danger' => 'text-danger-600 dark:text-danger-400',
        'info' => 'text-info-600 dark:text-info-400',
        default => 'text-primary-600 dark:text-primary-400',
    };
@endphp

<div
    {{ $attributes->class([
        'rounded-box border p-4',
        $variantClasses,
        'flex items-start gap-3' => $inline,
        'space-y-3' => !$inline,
    ]) }}
>
    @if($icon && !$inline)
        <div class="flex gap-3">
            <div class="shrink-0">
                <neura::icon :name="$icon" :class="'size-5 ' . $iconColorClass" />
            </div>
            <div class="flex-1 space-y-2">
                {{ $slot }}
                
                @if(isset($actions) && !$inline)
                    <div class="flex gap-2 flex-wrap mt-3">
                        {{ $actions }}
                    </div>
                @endif
            </div>
            
            @if(isset($controls))
                <div class="shrink-0">
                    {{ $controls }}
                </div>
            @endif
        </div>
    @else
        <div class="flex gap-3 items-start flex-1">
            @if($icon && $inline)
                <div class="shrink-0">
                    <neura::icon :name="$icon" :class="'size-5 ' . $iconColorClass" />
                </div>
            @endif
            
            <div class="flex-1 {{ $inline ? 'flex items-center gap-3 flex-wrap' : 'space-y-2' }}">
                {{ $slot }}
            </div>
            
            @if(isset($actions) && $inline)
                <div class="flex gap-2 shrink-0 flex-wrap">
                    {{ $actions }}
                </div>
            @endif
            
            @if(isset($controls))
                <div class="shrink-0">
                    {{ $controls }}
                </div>
            @endif
        </div>
        
        @if(isset($actions) && !$inline && !$icon)
            <div class="flex gap-2 flex-wrap mt-3">
                {{ $actions }}
            </div>
        @endif
    @endif
</div>


