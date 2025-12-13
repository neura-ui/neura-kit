@props(['label'])

<div {{ $attributes->merge(['class' => 'px-3 py-1.5 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider']) }}>
    {{ $label }}
</div>


