@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'date' => null,
    'active' => false,
    'completed' => false,
])

@php
    $iconClasses = [
        'flex items-center justify-center rounded-full border-2 z-10',
        $active ? 'bg-blue-600 border-blue-600 text-white' : ($completed ? 'bg-green-600 border-green-600 text-white' : 'bg-surface border-edge text-fg-muted'),
    ];
@endphp

<div {{ $attributes->merge(['class' => 'relative pl-14']) }}>
    <div class="absolute left-0 top-0">
        @if ($icon)
            <div class="{{ Arr::toCssClasses($iconClasses) }} size-10">
                <neura::icon :name="$icon" variant="outline" />
            </div>
        @else
            <div class="{{ Arr::toCssClasses($iconClasses) }} size-3"></div>
        @endif
    </div>
    
    @if ($title || $description || $date || $slot->isNotEmpty())
        <div class="mt-4">
            @if ($date)
                <neura::text class="text-xs text-fg-muted mb-1">
                    {{ $date }}
                </neura::text>
            @endif
            
            @if ($title)
                <neura::heading level="h4" size="sm" class="font-semibold mb-1">
                    {{ $title }}
                </neura::heading>
            @endif
            
            @if ($description)
                <neura::text class="text-sm text-fg-secondary">
                    {{ $description }}
                </neura::text>
            @endif
            
            @if ($slot->isNotEmpty())
                <div class="mt-2">
                    {{ $slot }}
                </div>
            @endif
        </div>
    @endif
</div>
