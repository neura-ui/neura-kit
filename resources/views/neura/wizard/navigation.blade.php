@aware([
    'steps',
    'stepProperty',
    'totalSteps',
    'currentStep',
    'id',
])

@props([
    'showPrevious' => true,
    'showNext' => true,
    'previousLabel' => 'Back',
    'nextLabel' => 'Next',
    'finishLabel' => 'Finish',
    'steps' => [],
    'totalSteps' => null,
    'stepProperty' => 'step',
    'currentStep' => 1,
    'id' => null,
    'showCancel' => false,
    'cancelUrl' => null,
    'cancelLabel' => 'Cancel',
    'showCompleteButton' => true,
    'showCounter' => false,
    'completeLabel' => null,
    'completeUrl' => null,
    'cancelVariant' => 'ghost',
    'previousVariant' => 'outline',
    'nextVariant' => 'primary',
    'finishVariant' => 'primary',
    'completeVariant' => null,
    'previousAction' => 'previous',
    'nextAction' => 'next',
    'completeAction' => 'complete',
    'restartAction' => 'restart',
])

@php
    use Illuminate\View\ComponentAttributeBag;

    $completeVariant ??= $finishVariant;
    $total = $totalSteps ?? (count($steps) ?: 4);

    // Without a Livewire binding there is no server to call, so navigation
    // is handled entirely in Alpine.
    $clientOnly = ! $stepProperty;

    // Directives cannot appear inside a component tag's attribute list, so the
    // server-action vs. client-only choice is resolved into an attribute bag.
    $trigger = fn (?string $action, string $fallback) => new ComponentAttributeBag(
        $action && ! $clientOnly ? ['wire:click' => $action] : ['x-on:click' => $fallback],
    );

    $previousTrigger = $trigger($previousAction, 'previous()');
    $nextTrigger = $trigger($nextAction, 'next()');
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 pt-6']) }}
    data-slot="wizard-navigation"
    x-data="{
        step: @if ($stepProperty) @entangle($stepProperty).live @else {{ (int) ($currentStep ?: 1) }} @endif,
        total: {{ $total }},
        get current() {
            const value = Number(this.step);

            return Number.isFinite(value) && value >= 1 ? value : 1;
        },
        get isComplete() {
            return this.current > this.total;
        },
        next() {
            if (this.current <= this.total) this.step = this.current + 1;
        },
        previous() {
            if (this.current > 1) this.step = this.current - 1;
        },
    }"
>
    <div class="flex items-center gap-3">
        @if ($showCancel && $cancelUrl)
            <neura::button
                :variant="$cancelVariant"
                as="a"
                :href="$cancelUrl"
                x-show="! isComplete"
                x-cloak
            >
                {{ $cancelLabel }}
            </neura::button>
        @endif

        @if ($showPrevious)
            <neura::button
                type="button"
                :variant="$previousVariant"
                icon="chevron-left"
                :attributes="$previousTrigger"
                x-show="current > 1 && ! isComplete"
                x-cloak
            >
                {{ $previousLabel }}
            </neura::button>
        @endif
    </div>

    <div class="flex items-center gap-3">
        @if ($showCounter)
            <neura::text
                size="sm"
                class="text-fg-muted tabular-nums"
                x-show="! isComplete"
                x-cloak
            >
                {{ __('Step') }} <span x-text="current"></span> {{ __('of') }} {{ $total }}
            </neura::text>
        @endif

        @if ($showNext)
            <neura::button
                type="button"
                :variant="$nextVariant"
                icon-after="chevron-right"
                :attributes="$nextTrigger"
                x-show="current < total"
                x-cloak
            >
                {{ $nextLabel }}
            </neura::button>
        @endif

        @if (! $clientOnly)
            <neura::button
                type="button"
                :variant="$finishVariant"
                wire:click="{{ $completeAction }}"
                wire:loading.attr="disabled"
                wire:target="{{ $completeAction }}"
                x-show="current === total"
                x-cloak
            >
                <span wire:loading.remove wire:target="{{ $completeAction }}">
                    {{ $finishLabel }}
                </span>
                <span wire:loading wire:target="{{ $completeAction }}">
                    {{ __('Processing...') }}
                </span>
            </neura::button>
        @endif

        @if ($showCompleteButton)
            @if ($completeUrl)
                <neura::button
                    :variant="$completeVariant"
                    as="a"
                    :href="$completeUrl"
                    x-show="isComplete"
                    x-cloak
                >
                    {{ $completeLabel ?? __('Done') }}
                </neura::button>
            @elseif (! $clientOnly)
                <neura::button
                    type="button"
                    :variant="$completeVariant"
                    wire:click="{{ $restartAction }}"
                    x-show="isComplete"
                    x-cloak
                >
                    {{ $completeLabel ?? __('Start Over') }}
                </neura::button>
            @endif
        @endif
    </div>

    {{-- Announce step changes to assistive tech without moving focus. --}}
    <span class="sr-only" aria-live="polite" aria-atomic="true">
        <template x-if="! isComplete">
            <span>{{ __('Step') }} <span x-text="current"></span> {{ __('of') }} {{ $total }}</span>
        </template>
    </span>
</div>
