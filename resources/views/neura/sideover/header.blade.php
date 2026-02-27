@props([
    'closeable' => true,
])

<div {{ $attributes->merge(['class' => 'shrink-0 flex items-center justify-between gap-4 px-5 h-16 border-b border-separator bg-surface backdrop-blur-xl']) }}>
    <div class="flex-1 min-w-0">
        {{ $slot }}
    </div>

    @if($closeable)
        <neura::button
            icon="x-mark"
            variant="ghost"
            size="sm"
            wire:click="closeSideover"
            aria-label="{{ neura_trans('close') }}"
            class="rounded-full -m-1"
        />
    @endif
</div>
