@props([
    'closeable' => true,
    'managed' => false,
])

<div class="flex items-center justify-between gap-4 px-6 py-2 border-b border-neutral-200 dark:border-neutral-800">
    <div class="flex-1 min-w-0">
        {{ $slot }}
    </div>

    @if($closeable)
        <neura::button
            icon="x-mark"
            variant="ghost"
            size="sm"
            x-on:click.stop="$dispatch('modal-close')"
            aria-label="{{ neura_trans('close') }}"
        />
    @endif
</div>
