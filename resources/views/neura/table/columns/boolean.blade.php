@props([
    'value',
    'row' => null,
    'column' => null,
])

@php
    $isTrue = filter_var($value, FILTER_VALIDATE_BOOLEAN);
@endphp

<div class="flex items-center">
    @if($isTrue)
        <div class="size-5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center">
            <neura::icon name="check" variant="micro" class="size-3 text-emerald-600 dark:text-emerald-400" />
        </div>
    @else
        <div class="size-5 rounded-full bg-neutral-100 dark:bg-white/[0.06] flex items-center justify-center">
            <neura::icon name="x-mark" variant="micro" class="size-3 text-neutral-400 dark:text-neutral-500" />
        </div>
    @endif
</div>
