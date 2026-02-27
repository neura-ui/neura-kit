@props([
    'tooltip' => null,
    'mobile' => false,
])

@if ($mobile)
    <button
        {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center size-9 rounded-lg cursor-pointer text-fg-muted hover:text-fg hover:bg-neutral-100 dark:hover:bg-white/5 transition-colors duration-150"]) }}
        x-on:click="toggle()" data-slot="sidebar-toggle">
        <neura::icon name="bars-3" variant="micro" class="size-4" />
    </button>
@else
    <button
        {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center size-7 rounded-md cursor-pointer text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/[0.06] active:bg-neutral-200 dark:active:bg-white/10 transition-all duration-150"]) }}
        x-on:click="toggle()" data-slot="sidebar-toggle">
        <neura::icon name="code-bracket-square" class="size-6" />
    </button>
@endif
