@aware([
    'stepProperty',
    'currentStep',
    'id',
])

@props([
    'step' => 1,
    'stepProperty' => 'step',
    'currentStep' => 1,
    'id' => null,
])

@php
    $prefix = $id ?: 'neura-wizard';
@endphp

<div
    {{ $attributes->merge(['class' => 'w-full']) }}
    id="{{ $prefix }}-panel-{{ $step }}"
    data-slot="wizard-step"
    role="tabpanel"
    tabindex="0"
    aria-labelledby="{{ $prefix }}-tab-{{ $step }}"
    x-cloak
    x-data="{
        step: @if ($stepProperty) @entangle($stepProperty).live @else {{ (int) ($currentStep ?: 1) }} @endif,
        get isActive() {
            return Number(this.step) === {{ (int) $step }};
        },
    }"
    x-show="isActive"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0"
>
    {{ $slot }}
</div>
