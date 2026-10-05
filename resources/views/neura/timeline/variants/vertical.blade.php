@props(['variant' => 'vertical'])

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="absolute left-5 top-0 bottom-0 w-px -translate-x-1/2 bg-edge"></div>
    
    <div class="space-y-8" x-data="{ variant: '{{ $variant }}' }">
        {{ $slot }}
    </div>
</div>
