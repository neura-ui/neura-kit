<div
    {{ $attributes->merge(['class' => 'flex items-center px-3 bg-surface border border-primary-200 dark:border-primary-900 text-fg-secondary shadow-sm data-[slot=input-prefix]:rounded-l-box data-[slot=input-suffix]:rounded-r-box data-[slot=input-prefix]:border-r-0 data-[slot=input-suffix]:border-l-0']) }}>
    {{ $slot }}
</div>
