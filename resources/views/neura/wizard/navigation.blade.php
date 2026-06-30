@props([
    'showPrevious' => true,
    'showNext' => true,
    'previousLabel' => 'Back',
    'nextLabel' => 'Next',
    'finishLabel' => 'Finish',
    'totalSteps' => 4,
    'showCancel' => false,
    'cancelUrl' => null,
    'cancelLabel' => 'Cancel',
    'showCompleteButton' => true,
    'completeLabel' => null,
    'completeUrl' => null,
    'cancelVariant' => 'ghost',
    'previousVariant' => 'outline',
    'nextVariant' => 'primary',
    'finishVariant' => 'primary',
    'completeVariant' => null,
])

@php
    $completeVariant ??= $finishVariant;
@endphp

<div
    wire:ignore
    wire:key="wizard-navigation"
    x-data="{
        get currentStep() {
            return $wire.step || 1;
        },
        get totalSteps() {
            return @js($totalSteps);
        }
    }"
    {{ $attributes->merge([
        'class' => 'flex items-center justify-between pt-6'
    ]) }}
>
    <div class="flex items-center gap-3">
        @if($showCancel && $cancelUrl)
            <neura::button
                variant="{{ $cancelVariant }}"
                as="a"
                href="{{ $cancelUrl }}"
            >
                {{ $cancelLabel }}
            </neura::button>
        @endif

        <neura::button
            type="button"
            variant="{{ $previousVariant }}"
            wire:click="previous"
            icon="chevron-left"
            x-show="currentStep > 1 && @js($showPrevious)"
            x-cloak
        >
            {{ $previousLabel }}
        </neura::button>
    </div>

    <div>
        <neura::button
            type="button"
            variant="{{ $nextVariant }}"
            wire:click="next"
            icon-after="chevron-right"
            x-show="currentStep < totalSteps && @js($showNext)"
            x-cloak
        >
            {{ $nextLabel }}
        </neura::button>

        <neura::button
            type="button"
            variant="{{ $finishVariant }}"
            wire:click="complete"
            wire:loading.attr="disabled"
            wire:target="complete"
            x-show="currentStep === totalSteps"
            x-cloak
        >
            <span wire:loading.remove wire:target="complete">
                {{ $finishLabel }}
            </span>
            <span wire:loading wire:target="complete">
                {{ __('Processing...')}}
            </span>
        </neura::button>

        @if($completeUrl)
            <neura::button
                variant="{{ $completeVariant }}"
                as="a"
                href="{{ $completeUrl }}"
                x-show="currentStep > totalSteps && @js($showCompleteButton)"
                x-cloak
            >
                {{ $completeLabel ?? __('Done') }}
            </neura::button>
        @else
            <neura::button
                type="button"
                variant="{{ $completeVariant }}"
                wire:click="restart"
                x-show="currentStep > totalSteps && @js($showCompleteButton)"
                x-cloak
            >
                {{ $completeLabel ?? __('Start Over') }}
            </neura::button>
        @endif
    </div>
</div>
