@props([
    'openInNewTab' => null,
    'primary' => null,
    'variant' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $primary = filter_var($primary ?? neura_config('link', 'primary'), FILTER_VALIDATE_BOOLEAN);
    $variant = $variant ?? neura_config('link', 'variant');
    $variantConfig = PackResolver::linkVariant($variant, $primary);

    $classes = [
        'inline w-fit whitespace-nowrap font-medium text-base text-start',
        'underline-offset-[6px] hover:decoration-current',
        $variantConfig['decoration'],
        $variantConfig['color'],
    ];
@endphp

<a
    {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }}
    data-slot="link"
    @if ($openInNewTab) target="_blank" rel="noopener noreferrer" @endif
>
    {{ $slot }}
</a>
