@aware([
    'steps',
    'stepProperty',
    'totalSteps',
    'orientation',
    'currentStep',
    'linear',
    'color',
    'size',
    'id',
])

@props([
    'variant' => 'tabs',
    'steps' => [],
    'currentStep' => 1,
    'orientation' => 'horizontal',
    'stepProperty' => 'step',
    'totalSteps' => null,
    'linear' => true,
    'color' => null,
    'size' => null,
    'id' => null,
    'label' => 'Progress',
    'heading' => null,
    'subheading' => null,
])

@php
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\Wizard\State;

    $colors = PackResolver::wizardColor($color ?? neura_config('wizard', 'color'));
    $tokens = State::sizes($size ?? neura_config('wizard', 'size'));
    $items = State::normalize($steps);
    $total = $totalSteps ?? (count($items) ?: 1);
    $vertical = $orientation === 'vertical';
    $isStepper = $variant !== 'pills' && $variant !== 'tabs';

    $prefix = $id ?: 'neura-wizard';
    $activeClasses = $variant === 'pills'
        ? ($colors['activePill'] ?? $colors['active'])
        : ($colors['activeTab'] ?? $colors['active']);

    $container = match (true) {
        $vertical => 'flex flex-col w-full',
        $variant === 'pills' => 'inline-flex w-full items-center rounded-md bg-surface-inset p-1 mb-8',
        $variant === 'tabs' => 'flex items-center justify-start border-b border-edge w-full mb-8 overflow-x-auto',
        default => 'flex items-start w-full mb-8',
    };

    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-surface';
@endphp

<div
    {{ $attributes->merge(['class' => $container]) }}
    @if ($id) id="{{ $id }}-steps" @endif
    data-slot="wizard-steps"
    data-variant="{{ $variant }}"
    role="tablist"
    aria-label="{{ $label }}"
    aria-orientation="{{ $orientation }}"
    x-data="{
        step: @if ($stepProperty) @entangle($stepProperty).live @else {{ (int) ($currentStep ?: 1) }} @endif,
        total: {{ $total }},
        linear: {{ $linear ? 'true' : 'false' }},
        vertical: {{ $vertical ? 'true' : 'false' }},
        shared: { furthest: 1 },
        init() {
            const alpine = window.Alpine;
            const registry = window.NeuraWizardState
                ?? (window.NeuraWizardState = alpine ? alpine.reactive({}) : {});
            const key = (this.$wire?.id ?? 'standalone') + ':' + @js(State::scope($id, $stepProperty));

            if (! registry[key]) {
                registry[key] = alpine
                    ? alpine.reactive({ furthest: this.current })
                    : { furthest: this.current };
            }

            this.shared = registry[key];
            this.reach();
            this.$watch('step', (value, previous) => {
                // Returning to the start from the completion screen is a restart,
                // so previously reached steps should not stay unlocked.
                if (Number(previous) > this.total && Number(value) <= 1) {
                    this.shared.furthest = 1;
                }

                this.reach();
            });
        },
        reach() {
            // Capped at total so the completion screen is never recorded as
            // reached — otherwise it could be jumped to directly later on.
            const reached = Math.min(this.current, this.total);

            if (reached > this.shared.furthest) this.shared.furthest = reached;
        },
        get current() {
            const value = Number(this.step);

            return Number.isFinite(value) && value >= 1 ? value : 1;
        },
        get furthest() {
            return Math.max(this.shared.furthest, this.current);
        },
        isActive(step) {
            return this.current === step;
        },
        isDone(step) {
            return this.current > step;
        },
        canGoTo(step) {
            // The step you are on is always considered reachable, so a
            // completion step beyond `total` is never marked disabled.
            if (this.isActive(step)) return true;
            if (step < 1 || step > this.total) return false;

            return this.linear ? step <= this.furthest : true;
        },
        goTo(step) {
            if (this.canGoTo(step)) this.step = step;
        },
        focusStep(index) {
            // $root, not $el: these run from a handler bound to the button.
            const tabs = Array.from(this.$root.querySelectorAll('[data-wizard-tab]'));
            tabs[Math.max(0, Math.min(tabs.length - 1, index))]?.focus();
        },
        onKeydown(event, index) {
            if (event.key === (this.vertical ? 'ArrowDown' : 'ArrowRight')) this.focusStep(index + 1);
            else if (event.key === (this.vertical ? 'ArrowUp' : 'ArrowLeft')) this.focusStep(index - 1);
            else if (event.key === 'Home') this.focusStep(0);
            else if (event.key === 'End') this.focusStep(this.total - 1);
            else return;

            event.preventDefault();
        },
    }"
>
    @if ($vertical && ($heading || $subheading))
        <div class="mb-6">
            @if ($heading)
                <h3 class="text-sm font-semibold text-fg">{{ $heading }}</h3>
            @endif
            @if ($subheading)
                <p class="mt-1 text-xs text-fg-muted">{{ $subheading }}</p>
            @endif
        </div>
    @endif

    @if (! $isStepper)
        <div @class(['flex w-full gap-1', 'flex-col' => $vertical])>
            @foreach ($items as $i => $item)
                @php $n = $i + 1; @endphp
                <button
                    type="button"
                    role="tab"
                    data-wizard-tab
                    id="{{ $prefix }}-tab-{{ $n }}"
                    aria-controls="{{ $prefix }}-panel-{{ $n }}"
                    x-on:click="goTo({{ $n }})"
                    x-on:keydown="onKeydown($event, {{ $i }})"
                    :aria-selected="isActive({{ $n }}) ? 'true' : 'false'"
                    :aria-current="isActive({{ $n }}) ? 'step' : null"
                    :tabindex="isActive({{ $n }}) ? 0 : -1"
                    {{-- aria-disabled rather than disabled: unreachable steps stay
                         focusable so arrow keys can traverse the whole tablist. --}}
                    :aria-disabled="canGoTo({{ $n }}) ? 'false' : 'true'"
                    :data-state="isActive({{ $n }}) ? 'active' : (isDone({{ $n }}) ? 'complete' : 'inactive')"
                    @class([
                        'inline-flex items-center whitespace-nowrap text-sm font-medium transition-colors',
                        'aria-disabled:opacity-50 aria-disabled:cursor-not-allowed',
                        $focus,
                        'rounded-sm px-3 py-1.5' => $variant === 'pills',
                        'flex-1 justify-center' => $variant === 'pills' && ! $vertical,
                        'w-full justify-start' => $vertical,
                        'px-4 py-2.5 border-transparent' => $variant === 'tabs',
                        'justify-center border-b-2 -mb-[1px]' => $variant === 'tabs' && ! $vertical,
                        'border-l-2 text-left' => $variant === 'tabs' && $vertical,
                    ])
                    :class="[
                        isActive({{ $n }}) && @js($activeClasses),
                        ! isActive({{ $n }}) && canGoTo({{ $n }}) && 'text-fg-muted hover:text-fg',
                        ! isActive({{ $n }}) && ! canGoTo({{ $n }}) && 'text-fg-disabled cursor-not-allowed',
                    ].filter(Boolean).join(' ')"
                >
                    <span class="sr-only">{{ __('Step :n of :total', ['n' => $n, 'total' => $total]) }}</span>
                    @if ($item['icon'])
                        <neura::icon :name="$item['icon']" class="{{ $tokens['icon'] }} me-2 shrink-0" />
                    @endif
                    <span>{{ $item['label'] }}</span>
                    @if ($item['description'])
                        <span class="sr-only">{{ $item['description'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    @else
        {{--
            Both orientations share one 2x2 grid; only the cell assignments
            differ. The connector is centred with self-center / justify-self-center
            against the marker's own track, so it needs no pixel offsets.
        --}}
        @foreach ($items as $i => $item)
            @php
                $n = $i + 1;
                $last = $n === count($items);
            @endphp
            <div @class([
                'grid grid-cols-[auto_minmax(0,1fr)]',
                'flex-1' => ! $vertical && ! $last,
                'flex-none' => ! $vertical && $last,
                'w-full grid-rows-[auto_auto]' => $vertical,
            ])>
                <button
                    type="button"
                    role="tab"
                    data-wizard-tab
                    id="{{ $prefix }}-tab-{{ $n }}"
                    aria-controls="{{ $prefix }}-panel-{{ $n }}"
                    x-on:click="goTo({{ $n }})"
                    x-on:keydown="onKeydown($event, {{ $i }})"
                    :aria-selected="isActive({{ $n }}) ? 'true' : 'false'"
                    :aria-current="isActive({{ $n }}) ? 'step' : null"
                    :tabindex="isActive({{ $n }}) ? 0 : -1"
                    :aria-disabled="canGoTo({{ $n }}) ? 'false' : 'true'"
                    :data-state="isActive({{ $n }}) ? 'active' : (isDone({{ $n }}) ? 'complete' : 'inactive')"
                    class="col-start-1 row-start-1 group rounded-full aria-disabled:cursor-not-allowed {{ $focus }}"
                >
                    <span
                        class="flex items-center justify-center rounded-full border transition-colors duration-300 {{ $tokens['circle'] }}"
                        :class="[
                            isActive({{ $n }}) && @js($colors['active'] . ' border-transparent'),
                            isDone({{ $n }}) && @js($colors['completed']),
                            ! isActive({{ $n }}) && ! isDone({{ $n }}) && 'bg-transparent border-neutral-300 dark:border-white/[0.15] text-fg-disabled',
                            ! isActive({{ $n }}) && ! isDone({{ $n }}) && canGoTo({{ $n }}) && 'group-hover:border-neutral-400 dark:group-hover:border-white/25 group-hover:text-fg-muted',
                        ].filter(Boolean).join(' ')"
                    >
                        <span class="sr-only">{{ __('Step :n of :total', ['n' => $n, 'total' => $total]) }}</span>
                        <span x-show="isDone({{ $n }})" x-cloak aria-hidden="true">
                            <neura::icon name="check" class="{{ $tokens['icon'] }}" />
                        </span>
                        <span
                            x-show="! isDone({{ $n }})"
                            class="font-medium leading-none"
                            aria-hidden="true"
                        >{{ $item['icon'] ? '' : $n }}</span>
                        @if ($item['icon'])
                            <neura::icon
                                :name="$item['icon']"
                                class="{{ $tokens['icon'] }}"
                                x-show="! isDone({{ $n }})"
                                aria-hidden="true"
                            />
                        @endif
                    </span>
                </button>

                @unless ($last)
                    <div @class([
                        'relative overflow-hidden rounded-full',
                        'col-start-2 row-start-1 self-center h-[2px] mx-3' => ! $vertical,
                        'col-start-1 row-start-2 justify-self-center w-[2px] my-1.5 min-h-4 h-full' => $vertical,
                    ])>
                        <div class="absolute inset-0 bg-neutral-200 dark:bg-white/[0.08]"></div>
                        <div
                            class="absolute rounded-full {{ $colors['connector'] }} {{ $vertical ? 'inset-x-0 top-0 transition-[height] duration-500 ease-out' : 'inset-y-0 left-0 transition-[width] duration-500 ease-out' }}"
                            :style="isDone({{ $n }}) ? '{{ $vertical ? 'height' : 'width' }}: 100%' : '{{ $vertical ? 'height' : 'width' }}: 0%'"
                        ></div>
                    </div>
                @endunless

                <div @class([
                    'col-start-1 col-span-2 row-start-2 mt-2 pe-3' => ! $vertical,
                    'col-start-2 row-start-1 row-span-2 ps-3' => $vertical,
                    $tokens['verticalPad'] => $vertical && ! $last,
                ])>
                    <span
                        class="block font-medium leading-tight transition-colors duration-300 {{ $tokens['label'] }}"
                        :class="[
                            isActive({{ $n }}) && @js($colors['labelActive'] ?? 'text-fg'),
                            isDone({{ $n }}) && @js($colors['labelCompleted'] ?? 'text-fg-secondary'),
                            ! isActive({{ $n }}) && ! isDone({{ $n }}) && 'text-fg-disabled',
                        ].filter(Boolean).join(' ')"
                    >{{ $item['label'] }}</span>

                    @if ($item['description'])
                        <span class="mt-0.5 block font-normal text-fg-muted {{ $tokens['description'] }}">
                            {{ $item['description'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
