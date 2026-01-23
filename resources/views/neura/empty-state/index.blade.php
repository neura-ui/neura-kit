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
        'bordered' => 'border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl bg-neutral-50/50 dark:bg-neutral-900/50',
        'card' => 'border border-neutral-200 dark:border-neutral-800 rounded-xl bg-white dark:bg-neutral-950 shadow-sm',
        'ghost' => 'bg-transparent',
        default => '',
    };

    $iconBgClass = match($variant) {
        'bordered', 'card' => 'bg-neutral-100 dark:bg-neutral-800',
        default => 'bg-neutral-100 dark:bg-neutral-800/60',
    };

    $iconColorClass = 'text-neutral-400 dark:text-neutral-500';
@endphp

<div {{ $attributes->class([
    'flex flex-col items-center justify-center text-center',
    $sizeClasses['container'],
    $sizeClasses['gap'],
    $variantClasses,
    'max-w-md mx-auto' => !$compact,
]) }}>
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
                'font-semibold text-neutral-900 dark:text-neutral-100',
                $sizeClasses['title'],
            ])>
                {{ $title }}
            </h3>
        @endif

        @if($description)
            <p @class([
                'text-neutral-500 dark:text-neutral-400 max-w-sm mx-auto',
                $sizeClasses['description'],
            ])>
                {{ $description }}
            </p>
        @endif

        {{-- Default slot for custom content --}}
        @if($slot->isNotEmpty())
            <div class="text-neutral-500 dark:text-neutral-400">
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
            'text-neutral-400 dark:text-neutral-500',
            $sizeClasses['description'],
        ])>
            {{ $footer }}
        </div>
    @endif
</div>
