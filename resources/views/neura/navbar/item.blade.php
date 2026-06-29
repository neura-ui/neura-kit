@props([
    'icon' => null,
    'badge' => null,
    'label' => null,
    'href' => null,
    'active' => null,
    'variant' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $itemStyles = PackResolver::navbarItem($variant ?? neura_config('navbar', 'variant'));

    $classes = [
        $itemStyles['base'] ?? '',
        $itemStyles['active'] ?? '',
        $itemStyles['hover'] ?? '',
        $itemStyles['icon'] ?? '',
        $itemStyles['badge'] ?? '',
    ];

    $iconAttributes = new \Illuminate\View\ComponentAttributeBag();
    $badgeAttributes = new \Illuminate\View\ComponentAttributeBag();

    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'icon:')) {
            $iconAttributes[substr($key, 5)] = $value;
        } elseif (str_starts_with($key, 'badge:')) {
            $badgeAttributes[substr($key, 6)] = $value;
        }
    }

    $active = $active ?? (url($href) === url()->current());

@endphp

<neura::button.abstract
    :$href
    data-slot="navlist-item"
    {{ $attributes
        ->when($active, fn($attrs) => $attrs->merge(['data-active-link' => 'true'] ))
        ->class($classes)
    }}
>
    @if($icon)
        <neura::icon
            :attributes="$iconAttributes->class('[:where(&)]:size-5')"
            :name="$icon"
        />
    @endif

    <span class="text-base">
        {{ $label }}
    </span>

    @if($badge)
        <neura::badge
            :attributes="$badgeAttributes->class('ml-auto')->merge([
                'size' => 'sm'
            ])"
        >
            {{ $badge }}
        </neura::badge>
    @endif
</neura::button.abstract>
