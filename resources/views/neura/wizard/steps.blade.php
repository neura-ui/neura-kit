@props([
    'variant' => 'tabs',
    'steps' => [],
    'currentStep' => 1,
    'orientation' => 'horizontal',
])

<div
    {{ $attributes->merge([
        'class' => match(true) {
            $orientation === 'vertical' => 'flex flex-col w-full md:w-80 shrink-0 bg-neutral-50/50 dark:bg-neutral-900/50 border-r border-neutral-200 dark:border-neutral-800 p-8 space-y-6',

            $variant === 'pills' => 'inline-flex h-9 w-fit items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800 p-[3px] mb-8',
            $variant === 'tabs' => 'inline-flex h-10 items-center justify-start border-b border-neutral-200 dark:border-neutral-800 w-full mb-8',
            default => 'flex items-center justify-center mb-12',
        }
    ]) }}
    role="tablist"
    aria-orientation="{{ $orientation }}"
    x-data="{
        orientation: @js($orientation),
        get currentStep() {
            return $wire.step || @js($currentStep ?? 1);
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
                $wire.step = step;
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
        <div class="{{ $orientation === 'vertical' ? 'flex flex-col gap-2 w-full' : 'flex w-full' }}">
            <template x-for="(step, idx) in steps" :key="idx">
                <button type="button" role="tab" x-on:click="goToStep(idx + 1)"
                    :aria-selected="isStepActive(idx + 1)"
                    :disabled="!canGoToStep(idx + 1)"
                    :data-state="isStepActive(idx + 1) ? 'active' : 'inactive'"
                    class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150 disabled:pointer-events-none disabled:opacity-50"
                    :class="{
                        'w-full justify-start': orientation === 'vertical',
                        'flex-1 justify-center h-[calc(100%-6px)]': orientation === 'horizontal',

                        // Active State
                        'bg-white shadow-sm border border-neutral-200 text-neutral-900 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-50': isStepActive(idx + 1) && orientation === 'vertical',
                        'bg-white text-neutral-900 shadow-sm dark:bg-neutral-950 dark:text-neutral-50': isStepActive(idx + 1) && orientation === 'horizontal',

                        // Inactive but accessible
                        'text-neutral-600 hover:bg-neutral-200/50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-50': !isStepActive(idx + 1) && canGoToStep(idx + 1),

                        // Disabled/Future
                        'text-neutral-400 dark:text-neutral-600 cursor-not-allowed': !canGoToStep(idx + 1)
                    }">

                    <!-- Step Indicator for Vertical Mode -->
                    <span x-show="orientation === 'vertical'"
                          class="flex h-6 w-6 items-center justify-center rounded-full text-xs border transition-colors duration-150"
                          :class="{
                              'bg-neutral-900 text-white border-neutral-900 dark:bg-white dark:text-neutral-900 dark:border-white': isStepActive(idx + 1),
                              'bg-green-500 text-white border-green-500': isStepCompleted(idx + 1),
                              'bg-transparent border-neutral-300 text-neutral-500 dark:border-neutral-700': !isStepActive(idx + 1) && !isStepCompleted(idx + 1)
                          }">
                        <template x-if="isStepCompleted(idx + 1)">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="!isStepCompleted(idx + 1)">
                            <span x-text="idx + 1"></span>
                        </template>
                    </span>

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
                class="inline-flex items-center whitespace-nowrap text-sm font-medium transition-all duration-150 px-4 py-2.5"
                :class="{
                    'justify-center border-b-2 -mb-[2px]': orientation === 'horizontal',
                    'justify-start w-full border-r-2 -mr-[1px] text-left pl-0 hover:pl-1': orientation === 'vertical',
                    'border-neutral-900 text-neutral-900 dark:border-neutral-50 dark:text-neutral-50': isStepActive(idx + 1),
                    'border-transparent text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-50': !isStepActive(idx + 1) && canGoToStep(idx + 1),
                    'border-transparent text-neutral-400 dark:text-neutral-600 cursor-not-allowed': !canGoToStep(idx + 1)
                }"
            >
                <span x-text="step"></span>
            </button>
        </template>
    @else
        <template x-for="(step, idx) in steps" :key="idx">
            <div :class="orientation === 'vertical' ? 'flex flex-col relative min-h-[80px]' : 'flex items-center'">
                <div
                    :class="{
                        'cursor-pointer': canGoToStep(idx + 1),
                        'flex flex-col items-center': orientation === 'horizontal',
                        'flex items-center gap-4': orientation === 'vertical'
                    }"
                    x-on:click="goToStep(idx + 1)"
                >
                    <div
                        class="flex items-center justify-center transition-all duration-150 text-sm font-medium z-10 relative"
                        :class="{
                            'w-8 h-8 rounded-full': true,
                            'bg-neutral-900 text-white dark:bg-neutral-50 dark:text-neutral-900': isStepActive(idx + 1),
                            'bg-neutral-900 text-white dark:bg-neutral-50 dark:text-neutral-900': isStepCompleted(idx + 1),
                            'bg-neutral-200 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400': !isStepActive(idx + 1) && !isStepCompleted(idx + 1)
                        }"
                    >
                        <template x-if="isStepCompleted(idx + 1)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="!isStepCompleted(idx + 1)">
                            <span x-text="idx + 1"></span>
                        </template>
                    </div>

                    <span
                        class="text-xs transition-all duration-150"
                        :class="{
                            'mt-2 max-w-[80px] text-center': orientation === 'horizontal',
                            'text-left': orientation === 'vertical',
                            'text-neutral-900 font-medium dark:text-neutral-100': isStepActive(idx + 1),
                            'text-neutral-600 dark:text-neutral-400': isStepCompleted(idx + 1),
                            'text-neutral-500 dark:text-neutral-500': !isStepActive(idx + 1) && !isStepCompleted(idx + 1)
                        }"
                        x-text="step"
                    ></span>
                </div>

                <div
                    x-show="idx < steps.length - 1"
                    class="transition-all duration-150"
                    :class="{
                        'mx-3 w-12 h-[2px]': orientation === 'horizontal',
                        'absolute left-4 top-8 bottom-0 w-[2px] -ml-[1px]': orientation === 'vertical',
                        'bg-neutral-900 dark:bg-neutral-50': isStepCompleted(idx + 2),
                        'bg-neutral-200 dark:bg-neutral-800': !isStepCompleted(idx + 2)
                    }"
                ></div>
            </div>
        </template>
    @endif
</div>
