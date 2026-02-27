@props([
    'align' => 'right',
])

@php
    $alignClasses = match($align) {
        'left' => 'justify-start',
        'center' => 'justify-center',
        'right' => 'justify-end',
        'between' => 'justify-between',
        default => 'justify-end',
    };
@endphp

<div {{ $attributes->merge(['class' => 'shrink-0 flex items-center gap-3 ' . $alignClasses . ' px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] border-t border-separator bg-surface backdrop-blur-xl']) }}>
    {{ $slot }}
</div>
