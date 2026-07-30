{{--
    Wizard container. Every prop below other than `orientation` is declared so
    that nested parts can inherit it with `@aware` — set `steps`, `color`,
    `stepProperty` etc. once here instead of repeating them on each child.
--}}
@props([
    'steps' => [],
    'orientation' => 'horizontal',
    'stepProperty' => 'step',
    'totalSteps' => null,
    'currentStep' => 1,
    'linear' => true,
    'color' => null,
    'size' => null,
])

@php
    // Panels live in column two, row one; navigation in column two, row two.
    // Only one panel is ever displayed, so the placement stays deterministic
    // without asking callers to add a wrapper element.
    // Rows stay `auto` — a `1fr` row in an intrinsically sized grid expands with
    // content and can blow up page height (and paint a second document scrollbar).
    $verticalGrid = implode(' ', [
        'md:grid md:grid-cols-[18rem_minmax(0,1fr)] md:grid-rows-[auto_auto] md:gap-x-8',
        '[&>[data-slot=wizard-steps]]:md:col-start-1 [&>[data-slot=wizard-steps]]:md:row-span-full',
        '[&>[data-slot=wizard-step]]:md:col-start-2 [&>[data-slot=wizard-step]]:md:row-start-1',
        '[&>[data-slot=wizard-navigation]]:md:col-start-2 [&>[data-slot=wizard-navigation]]:md:row-start-2',
    ]);
@endphp

<div
    {{ $attributes->merge([
        'class' => $orientation === 'vertical' ? 'w-full ' . $verticalGrid : 'w-full',
    ]) }}
    data-slot="wizard"
    data-orientation="{{ $orientation }}"
>
    {{ $slot }}
</div>
