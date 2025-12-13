@props([
    'value' => null,
])

<div
    x-show="activeTab === @js($value)"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    role="tabpanel"
    :aria-hidden="activeTab !== @js($value)"
    {{ $attributes->class('mt-6') }}
    data-slot="tab-content"
>
    {{ $slot }}
</div>
