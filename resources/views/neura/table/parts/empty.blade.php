@props([
    'colspan' => 1,
])

<tr>
    <td colspan="{{ $colspan }}">
        <div class="flex flex-col items-center justify-center py-16 px-6">
            <div class="size-10 rounded-full bg-neutral-100 dark:bg-white/[0.04] flex items-center justify-center mb-3">
                <neura::icon name="inbox" class="size-5 text-neutral-400 dark:text-neutral-500" />
            </div>
            <div class="text-xs text-neutral-400 dark:text-neutral-500 text-center">
                {!! $this->emptyStateHtml() !!}
            </div>
        </div>
    </td>
</tr>
