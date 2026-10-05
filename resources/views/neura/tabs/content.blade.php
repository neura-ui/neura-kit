@props([
    'value' => null,
])

<div
    x-show="activeTab === @js($value)"
    x-transition:enter="transition ease-enter duration-standard"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-standard duration-fast"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    role="tabpanel"
    :aria-hidden="activeTab !== @js($value)"
    {{ $attributes->merge(['class' => 'mt-6']) }}
    data-slot="tab-content"
>
    {{ $slot }}
</div>
