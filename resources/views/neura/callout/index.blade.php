@props([
    'icon' => null,
    'variant' => null,
    'inline' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $inline = filled($inline) && $inline;
    $variant = $variant ?? neura_config('callout', 'variant');
    $colorConfig = PackResolver::calloutColor($variant);

    $variantClasses = $colorConfig['container'] ?? '';
    $iconColorClass = $colorConfig['icon'] ?? '';
@endphp

<div
    {{ $attributes->merge(['class' => 'rounded-box border p-4 ' . $variantClasses . ($inline ? ' flex items-start gap-3' : ' space-y-3')]) }}
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
