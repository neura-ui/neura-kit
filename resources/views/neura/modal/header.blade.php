@props([
    'closeable' => true,
])

<div class="flex items-center justify-between gap-4 p-6 border-b border-neutral-200 dark:border-neutral-800">
    <div class="flex-1 min-w-0">
        {{ $slot }}
    </div>

    @if($closeable)
        <button
            type="button"
            @click.stop="$dispatch('modal-close')"
            class="shrink-0 p-1 rounded-lg text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
            aria-label="{{ neura_trans('close') }}"
        >
            <neura::icon name="x-mark" class="size-5" />
        </button>
    @endif
</div>