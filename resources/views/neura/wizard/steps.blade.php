@props([
    'variant' => 'tabs',
    'steps' => [],
    'currentStep' => 1,
    'orientation' => 'horizontal',
    'stepProperty' => 'step',
    'color' => 'neutral',
    'size' => 'md',
])
@php
    use Neura\Kit\Support\PackResolver;
    $wizardColors = PackResolver::wizardColor($color ?? 'neutral');
    
    // Size configuration for default variant
    $sizeConfig = match($size) {
        'sm' => [
            'circle' => 'w-8 h-8 text-xs',
            'checkIcon' => 'w-4 h-4',
            'label' => 'text-xs',
            'status' => 'text-[9px]',
            'connector' => 'left-[16px] top-8',
            'minHeight' => 'min-h-[70px]',
            'gap' => 'mx-2 min-w-[24px] max-w-[48px]',
        ],
        'lg' => [
            'circle' => 'w-14 h-14 text-base',
            'checkIcon' => 'w-6 h-6',
            'label' => 'text-base',
            'status' => 'text-xs',
            'connector' => 'left-[28px] top-14',
            'minHeight' => 'min-h-[100px]',
            'gap' => 'mx-6 min-w-[48px] max-w-[96px]',
        ],
        default => [
            'circle' => 'w-10 h-10 text-sm',
            'checkIcon' => 'w-5 h-5',
            'label' => 'text-sm',
            'status' => 'text-[10px]',
            'connector' => 'left-[20px] top-10',
            'minHeight' => 'min-h-[80px]',
            'gap' => 'mx-4 min-w-[32px] max-w-[64px]',
        ],
    };
@endphp

<div
    {{ $attributes->merge([
        'class' => match(true) {
            $orientation === 'vertical' => 'flex flex-col w-full md:w-80 shrink-0 bg-neutral-50/50 dark:bg-neutral-900/50 border-r border-neutral-200 dark:border-neutral-800 p-8 space-y-6',

            $variant === 'pills' => 'inline-flex h-10 w-fit items-center justify-center rounded-md bg-neutral-100 dark:bg-neutral-800 p-1 mb-8',
            $variant === 'tabs' => 'inline-flex h-10 items-center justify-start border-b border-neutral-200 dark:border-neutral-800 w-full mb-8',
            default => 'flex items-center w-full mb-8 px-2 sm:px-4',
        }
    ]) }}
    role="tablist"
    aria-orientation="{{ $orientation }}"
    x-data="{
        orientation: @js($orientation),
        stepProperty: @js($stepProperty),
        colorConfig: @js($wizardColors),
        sizeConfig: @js($sizeConfig),
        get currentStep() {
            return $wire.get(this.stepProperty) || @js($currentStep ?? 1);
        },
        get steps() {
            return @js($steps);
        },
        isStepActive(step) {
            return this.currentStep === step;
        },
        isStepCompleted(step) {
            return this.currentStep > step;
        },
        canGoToStep(step) {
            return step <= this.currentStep;
        },
        goToStep(step) {
            if (this.canGoToStep(step)) {
                $wire.set(this.stepProperty, step);
            }
        }
    }">

    @if($orientation === 'vertical')
        <!-- Sidebar Content Title (Optional, mimicking the design) -->
        <div class="mb-2">
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">Steps</h3>
            <p class="text-xs text-neutral-500 mt-1">Complete these steps to get started.</p>
        </div>
    @endif

    @if($variant === 'pills')
        <div class="{{ $orientation === 'vertical' ? 'flex flex-col gap-1 w-full' : 'flex w-full gap-1' }}">
            <template x-for="(step, idx) in steps" :key="idx">
                <button 
                    type="button" 
                    role="tab" 
                    x-on:click="goToStep(idx + 1)"
                    :aria-selected="isStepActive(idx + 1)"
                    :disabled="!canGoToStep(idx + 1)"
                    :data-state="isStepActive(idx + 1) ? 'active' : 'inactive'"
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-white dark:ring-offset-neutral-950 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-400 focus-visible:ring-offset-2 dark:focus-visible:ring-neutral-500 disabled:pointer-events-none disabled:opacity-50"
                    :class="[
                        'w-full',
                        orientation === 'horizontal' && 'flex-1',
                        isStepActive(idx + 1) && (colorConfig.activePill || colorConfig.active),
                        !isStepActive(idx + 1) && canGoToStep(idx + 1) && 'text-neutral-500 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700 hover:text-neutral-900 dark:hover:text-neutral-50',
                        !canGoToStep(idx + 1) && 'text-neutral-400 dark:text-neutral-600 cursor-not-allowed'
                    ].filter(Boolean).join(' ')"
                >
                    <span x-text="step"></span>
                </button>
            </template>
        </div>
    @elseif($variant === 'tabs')
        <template x-for="(step, idx) in steps" :key="idx">
            <button
                type="button"
                role="tab"
                x-on:click="goToStep(idx + 1)"
                :aria-selected="isStepActive(idx + 1)"
                :disabled="!canGoToStep(idx + 1)"
                class="inline-flex items-center justify-center whitespace-nowrap border-b-2 border-transparent px-4 py-2.5 text-sm font-medium ring-offset-white dark:ring-offset-neutral-950 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-400 focus-visible:ring-offset-2 dark:focus-visible:ring-neutral-500 disabled:pointer-events-none disabled:opacity-50"
                :class="[
                    'border-b-2 -mb-[2px]',
                    orientation === 'vertical' && 'w-full justify-start border-r-2 -mr-[1px] text-left',
                    isStepActive(idx + 1) && (colorConfig.activeTab || colorConfig.active),
                    !isStepActive(idx + 1) && canGoToStep(idx + 1) && 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50',
                    !canGoToStep(idx + 1) && 'text-neutral-400 dark:text-neutral-600 cursor-not-allowed'
                ].filter(Boolean).join(' ')"
            >
                <span x-text="step"></span>
            </button>
        </template>
    @else
        <template x-for="(step, idx) in steps" :key="idx">
            <div :class="[orientation === 'vertical' ? 'flex flex-col relative ' + sizeConfig.minHeight : 'flex items-center'].filter(Boolean).join(' ')">
                {{-- Step indicator and label --}}
                <div
                    :class="{
                        'cursor-pointer group': canGoToStep(idx + 1),
                        'cursor-default': !canGoToStep(idx + 1),
                        'flex flex-col items-center': orientation === 'horizontal',
                        'flex items-center gap-3': orientation === 'vertical'
                    }"
                    x-on:click="goToStep(idx + 1)"
                >
                    {{-- Circle indicator --}}
                    <div class="relative flex-shrink-0">
                        {{-- Main circle --}}
                        <div
                            class="relative flex items-center justify-center transition-all duration-300 font-semibold rounded-full border-2"
                            :class="[
                                sizeConfig.circle,
                                isStepActive(idx + 1) && (colorConfig.active + ' shadow-md'),
                                isStepCompleted(idx + 1) && colorConfig.completed,
                                !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'bg-white dark:bg-neutral-950 border-neutral-200 dark:border-neutral-800 text-neutral-400 dark:text-neutral-500',
                                canGoToStep(idx + 1) && !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'group-hover:border-neutral-300 dark:group-hover:border-neutral-700 group-hover:text-neutral-600 dark:group-hover:text-neutral-400'
                            ].filter(Boolean).join(' ')"
                        >
                            <template x-if="isStepCompleted(idx + 1)">
                                <svg :class="sizeConfig.checkIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <template x-if="!isStepCompleted(idx + 1)">
                                <span x-text="idx + 1" class="leading-none"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Step label --}}
                    <div :class="orientation === 'horizontal' ? 'mt-2 text-center max-w-[100px]' : 'text-left'">
                        <span
                            class="font-medium transition-all duration-300 block leading-tight"
                            :class="[
                                sizeConfig.label,
                                isStepActive(idx + 1) && (colorConfig.labelActive || 'text-neutral-900 dark:text-neutral-50'),
                                isStepCompleted(idx + 1) && (colorConfig.labelCompleted || 'text-neutral-600 dark:text-neutral-400'),
                                !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'text-neutral-400 dark:text-neutral-500',
                                canGoToStep(idx + 1) && !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'group-hover:text-neutral-600 dark:group-hover:text-neutral-400'
                            ].filter(Boolean).join(' ')"
                            x-text="step"
                        ></span>
                    </div>
                </div>

                {{-- Connector line --}}
                <template x-if="idx < steps.length - 1">
                    <div
                        class="flex-1"
                        :class="[
                            orientation === 'horizontal' && sizeConfig.gap,
                            orientation === 'vertical' && ('absolute bottom-0 ' + sizeConfig.connector)
                        ].filter(Boolean).join(' ')"
                    >
                        <div
                            class="h-[2px] w-full rounded-full transition-all duration-300"
                            :class="[
                                orientation === 'vertical' && 'w-[2px] h-full',
                                isStepCompleted(idx + 1) ? colorConfig.connector : 'bg-neutral-200 dark:bg-neutral-800'
                            ].filter(Boolean).join(' ')"
                        ></div>
                    </div>
                </template>
            </div>
        </template>
    @endif
</div>
