@props([
    'value' => null,
    'variant' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $variant = $variant ?? neura_config('tabs', 'variant');

    $baseClasses = 'inline-flex items-center justify-center whitespace-nowrap text-sm font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50';
    $variantClasses = PackResolver::tabsVariant($variant);
    $roundedClass = $variant === 'pills'
        ? PackResolver::rounded(neura_config('tabs', 'rounded'))
        : '';
@endphp

<button
    type="button"
    role="tab"
    x-on:click="activeTab = @js($value)"
    :data-state="activeTab === @js($value) ? 'active' : 'inactive'"
    :aria-selected="activeTab === @js($value)"
    data-tab-trigger="{{ $value }}"
    {{ $attributes->merge(['class' => trim($baseClasses . ' ' . $roundedClass . ' ' . $variantClasses)]) }}
>
    {{ $slot }}
</button>
