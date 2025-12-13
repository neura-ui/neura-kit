@props([
    'step' => 1,
])

<div
    x-data="{
        get currentStep() {
            return $wire.step || 1;
        }
    }"
    x-show="currentStep === @js($step)"
    {{ $attributes->merge(['class' => 'w-full']) }}
    role="tabpanel"
    :aria-hidden="currentStep !== @js($step)"
>
    {{ $slot }}
</div>
