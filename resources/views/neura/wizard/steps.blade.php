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
    
    $sizeConfig = match($size) {
        'sm' => [
            'circle' => 'w-7 h-7 text-xs',
            'ringSize' => 'w-9 h-9',
            'checkIcon' => 'w-3.5 h-3.5',
            'label' => 'text-xs',
            'description' => 'text-[10px]',
            'connector' => 'left-[14px] top-7',
            'connectorOffset' => 'mt-[14px]',
            'connectorHeight' => 'h-[2px]',
            'minHeight' => 'min-h-[64px]',
            'gap' => 'min-w-[20px]',
        ],
        'lg' => [
            'circle' => 'w-12 h-12 text-base',
            'ringSize' => 'w-14 h-14',
            'checkIcon' => 'w-5 h-5',
            'label' => 'text-base',
            'description' => 'text-xs',
            'connector' => 'left-[24px] top-12',
            'connectorOffset' => 'mt-[27px]',
            'connectorHeight' => 'h-[2px]',
            'minHeight' => 'min-h-[96px]',
            'gap' => 'min-w-[40px]',
        ],
        default => [
            'circle' => 'w-9 h-9 text-sm',
            'ringSize' => 'w-11 h-11',
            'checkIcon' => 'w-4 h-4',
            'label' => 'text-sm',
            'description' => 'text-[11px]',
            'connector' => 'left-[18px] top-9',
            'connectorOffset' => 'mt-[20px]',
            'connectorHeight' => 'h-[2px]',
            'minHeight' => 'min-h-[76px]',
            'gap' => 'min-w-[28px]',
        ],
    };
@endphp

<div
    {{ $attributes->merge([
        'class' => match(true) {
            $orientation === 'vertical' => 'flex flex-col w-full md:w-80 shrink-0 bg-surface-inset border-r border-edge p-8 space-y-6',

            $variant === 'pills' => 'inline-flex h-10 w-fit items-center justify-center rounded-md bg-surface-inset p-1 mb-8',
            $variant === 'tabs' => 'inline-flex h-10 items-center justify-start border-b border-edge w-full mb-8',
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
            <h3 class="text-sm font-semibold text-fg">Steps</h3>
            <p class="text-xs text-fg-muted mt-1">Complete these steps to get started.</p>
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
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-white dark:ring-offset-neutral-950 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50"
                    :class="[
                        'w-full',
                        orientation === 'horizontal' && 'flex-1',
                        isStepActive(idx + 1) && (colorConfig.activePill || colorConfig.active),
                        !isStepActive(idx + 1) && canGoToStep(idx + 1) && 'text-fg-muted hover:bg-active hover:text-fg',
                        !canGoToStep(idx + 1) && 'text-fg-disabled cursor-not-allowed'
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
                class="inline-flex items-center justify-center whitespace-nowrap border-b-2 border-transparent px-4 py-2.5 text-sm font-medium ring-offset-white dark:ring-offset-neutral-950 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50"
                :class="[
                    'border-b-2 -mb-[2px]',
                    orientation === 'vertical' && 'w-full justify-start border-r-2 -mr-[1px] text-left',
                    isStepActive(idx + 1) && (colorConfig.activeTab || colorConfig.active),
                    !isStepActive(idx + 1) && canGoToStep(idx + 1) && 'text-fg-muted hover:text-fg',
                    !canGoToStep(idx + 1) && 'text-fg-disabled cursor-not-allowed'
                ].filter(Boolean).join(' ')"
            >
                <span x-text="step"></span>
            </button>
        </template>
    @else
        <template x-for="(step, idx) in steps" :key="idx">
            <div :class="[orientation === 'vertical' ? 'flex flex-col relative ' + sizeConfig.minHeight : 'flex items-center flex-1 last:flex-none'].filter(Boolean).join(' ')">
                <div
                    :class="{
                        'cursor-pointer group': canGoToStep(idx + 1),
                        'cursor-default': !canGoToStep(idx + 1),
                        'flex flex-col items-center shrink-0': orientation === 'horizontal',
                        'flex items-center gap-3': orientation === 'vertical'
                    }"
                    x-on:click="goToStep(idx + 1)"
                    role="tab"
                    :aria-selected="isStepActive(idx + 1)"
                    :aria-current="isStepActive(idx + 1) ? 'step' : null"
                >
                    <div class="relative flex-shrink-0">
                        <div
                            class="flex items-center justify-center transition-all duration-300 rounded-full border"
                            :class="[
                                sizeConfig.circle,
                                isStepActive(idx + 1) && (colorConfig.active + ' border-transparent'),
                                isStepCompleted(idx + 1) && (colorConfig.completed),
                                !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'bg-transparent border-neutral-300 dark:border-white/[0.15] text-fg-disabled',
                                canGoToStep(idx + 1) && !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'group-hover:border-neutral-400 dark:group-hover:border-white/25 group-hover:text-fg-muted'
                            ].filter(Boolean).join(' ')"
                        >
                            <template x-if="isStepCompleted(idx + 1)">
                                <svg :class="sizeConfig.checkIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <template x-if="!isStepCompleted(idx + 1)">
                                <span x-text="idx + 1" class="leading-none font-medium"></span>
                            </template>
                        </div>
                    </div>

                    <div :class="orientation === 'horizontal' ? 'mt-1.5 text-center max-w-[120px]' : 'text-left'">
                        <span
                            class="font-medium transition-all duration-300 block leading-tight"
                            :class="[
                                sizeConfig.label,
                                isStepActive(idx + 1) && (colorConfig.labelActive || 'text-fg'),
                                isStepCompleted(idx + 1) && (colorConfig.labelCompleted || 'text-fg-secondary'),
                                !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'text-fg-disabled',
                                canGoToStep(idx + 1) && !isStepActive(idx + 1) && !isStepCompleted(idx + 1) && 'group-hover:text-fg-muted'
                            ].filter(Boolean).join(' ')"
                            x-text="typeof step === 'object' ? step.label : step"
                        ></span>
                        <template x-if="typeof step === 'object' && step.description">
                            <span
                                class="block mt-0.5 font-normal transition-all duration-300 text-fg-muted"
                                :class="sizeConfig.description"
                                x-text="step.description"
                            ></span>
                        </template>
                    </div>
                </div>

                <template x-if="idx < steps.length - 1">
                    <div
                        class="flex-1"
                        :class="[
                            orientation === 'horizontal' && ('self-start ' + sizeConfig.connectorOffset + ' ' + sizeConfig.gap + ' mx-3'),
                            orientation === 'vertical' && ('absolute bottom-0 ' + sizeConfig.connector)
                        ].filter(Boolean).join(' ')"
                    >
                        <div class="relative w-full overflow-hidden rounded-full"
                            :class="[
                                orientation === 'vertical' ? 'w-[2px] h-full' : sizeConfig.connectorHeight
                            ].filter(Boolean).join(' ')"
                        >
                            <div class="absolute inset-0 bg-neutral-200 dark:bg-white/[0.08] rounded-full"></div>
                            <div
                                class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 ease-out"
                                :class="colorConfig.connector"
                                :style="isStepCompleted(idx + 1) ? (orientation === 'vertical' ? 'height:100%' : 'width:100%') : (orientation === 'vertical' ? 'height:0%' : 'width:0%')"
                            ></div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    @endif
</div>
