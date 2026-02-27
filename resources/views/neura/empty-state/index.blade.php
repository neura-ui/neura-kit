@props([
    'icon' => null,
    'image' => null,
    'title' => null,
    'description' => null,
    'size' => 'md',
    'variant' => 'default',
    'compact' => false,
])

@php
    use Illuminate\Support\Arr;

    $sizeClasses = match($size) {
        'sm' => [
            'container' => 'py-6 px-4',
            'icon' => 'size-10',
            'iconWrapper' => 'size-16',
            'title' => 'text-base',
            'description' => 'text-sm',
            'gap' => 'gap-3',
        ],
        'lg' => [
            'container' => 'py-16 px-8',
            'icon' => 'size-16',
            'iconWrapper' => 'size-28',
            'title' => 'text-2xl',
            'description' => 'text-base',
            'gap' => 'gap-5',
        ],
        default => [
            'container' => 'py-12 px-6',
            'icon' => 'size-12',
            'iconWrapper' => 'size-20',
            'title' => 'text-lg',
            'description' => 'text-sm',
            'gap' => 'gap-4',
        ],
    };

    $variantClasses = match($variant) {
        'bordered' => 'border border-dashed border-edge rounded-xl bg-surface-inset',
        'card' => 'border border-edge rounded-xl bg-surface shadow-sm',
        'ghost' => 'bg-transparent',
        default => '',
    };

    $iconBgClass = match($variant) {
        'bordered', 'card' => 'bg-surface-inset',
        default => 'bg-surface-inset',
    };

    $iconColorClass = 'text-fg-disabled';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center ' . $sizeClasses['container'] . ' ' . $sizeClasses['gap'] . ' ' . $variantClasses . (!$compact ? ' max-w-md mx-auto' : '')]) }}>
    {{-- Icon or Image --}}
    @if($image)
        <div class="shrink-0">
            <img src="{{ $image }}" alt="" class="max-w-[120px] max-h-[120px] object-contain" />
        </div>
    @elseif($icon)
        <div @class([
            'shrink-0 flex items-center justify-center rounded-full',
            $sizeClasses['iconWrapper'],
            $iconBgClass,
        ])>
            <neura::icon :name="$icon" @class([
                $sizeClasses['icon'],
                $iconColorClass,
            ]) />
        </div>
    @elseif(isset($illustration))
        <div class="shrink-0">
            {{ $illustration }}
        </div>
    @endif

    {{-- Content --}}
    <div class="space-y-2">
        @if($title)
            <h3 @class([
                'font-semibold text-fg',
                $sizeClasses['title'],
            ])>
                {{ $title }}
            </h3>
        @endif

        @if($description)
            <p @class([
                'text-fg-muted max-w-sm mx-auto',
                $sizeClasses['description'],
            ])>
                {{ $description }}
            </p>
        @endif

        {{-- Default slot for custom content --}}
        @if($slot->isNotEmpty())
            <div class="text-fg-muted">
                {{ $slot }}
            </div>
        @endif
    </div>

    {{-- Actions --}}
    @if(isset($actions))
        <div class="flex flex-wrap items-center justify-center gap-3 mt-2">
            {{ $actions }}
        </div>
    @endif

    {{-- Footer --}}
    @if(isset($footer))
        <div @class([
            'text-fg-disabled',
            $sizeClasses['description'],
        ])>
            {{ $footer }}
        </div>
    @endif
</div>
