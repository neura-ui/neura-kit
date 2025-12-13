@props(['variant' => 'horizontal'])

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="absolute top-5 left-0 right-0 h-0.5 bg-neutral-200 dark:bg-neutral-800"></div>
    
    <div class="flex items-start justify-between gap-4" x-data="{ variant: '{{ $variant }}' }">
        {{ $slot }}
    </div>
</div>
