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
])

<div
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
                variant="ghost"
                as="a"
                href="{{ $cancelUrl }}"
            >
                {{ $cancelLabel }}
            </neura::button>
        @endif
        <template x-if="currentStep > 1 && @js($showPrevious)">
            <neura::button
                type="button"
                variant="outline"
                wire:click="previous"
                icon="chevron-left"
            >
                {{ $previousLabel }}
            </neura::button>
        </template>
    </div>

    <div>
        <template x-if="currentStep < totalSteps && @js($showNext)">
            <neura::button
                type="button"
                variant="primary"
                wire:click="next"
                icon-after="chevron-right"
            >
                {{ $nextLabel }}
            </neura::button>
        </template>

        <template x-if="currentStep === totalSteps">
            <neura::button
                type="button"
                variant="primary"
                wire:click="complete"
                wire:loading.attr="disabled"
                wire:target="complete"
            >
                <span wire:loading.remove wire:target="complete">
                    {{ $finishLabel }}
                </span>
                <span wire:loading wire:target="complete">
                    {{ __('Processing...')}}
                </span>
            </neura::button>
        </template>

        {{-- Complete step (after all steps are done) --}}
        <template x-if="currentStep > totalSteps && @js($showCompleteButton)">
            @if($completeUrl)
                <neura::button
                    variant="primary"
                    as="a"
                    href="{{ $completeUrl }}"
                >
                    {{ $completeLabel ?? __('Done') }}
                </neura::button>
            @else
                <neura::button
                    type="button"
                    variant="primary"
                    wire:click="restart"
                >
                    {{ $completeLabel ?? __('Start Over') }}
                </neura::button>
            @endif
        </template>
    </div>
</div>
