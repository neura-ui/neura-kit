@props(['variant' => 'vertical'])

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-neutral-200 dark:bg-neutral-800"></div>
    
    <div class="space-y-8" x-data="{ variant: '{{ $variant }}' }">
        {{ $slot }}
    </div>
</div>
