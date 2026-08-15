@props([
    'label' => null,
    'size' => 'md',
    'animate' => true,
    /** @var list<string>|null */
    'reasoning' => null,
    /** @var list<int|float>|null Per-step reveal delays in ms */
    'delays' => null,
    'duration' => null,
    'autoPlay' => false,
    'done' => false,
    'open' => false,
    'maxHeight' => 180,
    'thinkingLabel' => null,
    'thoughtFor' => null,
    /** How many reasoning steps are visible (Livewire progressive reveal). */
    'revealed' => null,
])

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Js;

    $steps = collect($reasoning ?? [])
        ->filter(fn ($step) => filled($step))
        ->values()
        ->all();

    $hasReasoning = count($steps) > 0;

    $sizeClasses = match ($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
        'xl' => 'text-lg',
        default => 'text-sm',
    };

    $thinkingText = $thinkingLabel ?? ($label ?? neura_trans('thinkingEllipsis'));
    $thoughtForTemplate = $thoughtFor ?? neura_trans('thoughtFor');
    $simpleText = $slot->isNotEmpty()
        ? null
        : ($label ?? neura_trans('thinking'));
    $staticColor = $animate ? '' : 'text-fg-secondary';

    $config = [
        'steps' => $steps,
        'delays' => $delays,
        'duration' => $duration,
        'autoPlay' => (bool) $autoPlay,
        'done' => (bool) $done,
        'open' => (bool) $open,
        'maxHeight' => (int) $maxHeight,
        'thoughtFor' => $thoughtForTemplate,
        'revealed' => $revealed,
    ];
@endphp

@if ($hasReasoning)
    <div
        {{ $attributes->merge([
            'class' => 'nk-thinking-reasoning w-full max-w-xl',
            'data-slot' => 'thinking-state',
            'data-variant' => 'reasoning',
        ]) }}
        x-data="neuraThinkingState({{ Js::from($config) }})"
        role="status"
        aria-live="polite"
    >
        <button
            type="button"
            class="nk-tr-header group flex w-full items-center gap-1.5 text-left {{ $sizeClasses }}"
            :class="done && 'nk-tr-header--clickable cursor-pointer'"
            :aria-expanded="expanded"
            aria-label="{{ neura_trans('toggleThought') }}"
            @click="toggle()"
            :disabled="!done"
        >
            <template x-if="done">
                <span
                    class="nk-tr-label font-medium text-fg-secondary tracking-tight"
                    x-text="summaryLabel"
                ></span>
            </template>
            <template x-if="!done">
                <span
                    class="nk-tr-label nk-tr-shimmer inline-block font-medium tracking-tight"
                    data-animate="{{ $animate ? 'true' : 'false' }}"
                >{{ $thinkingText }}</span>
            </template>
            <template x-if="done">
                <svg
                    class="nk-tr-chevron size-3 shrink-0 text-fg-muted transition-transform duration-200"
                    :class="open && 'nk-tr-chevron--open'"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="m4.5 15.75 7.5-7.5 7.5 7.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
        </button>

        <div
            x-ref="viewport"
            class="nk-tr-viewport overflow-hidden transition-[height,margin] duration-200 ease-out"
            :class="scrollable && 'nk-tr-viewport--scroll overflow-y-auto'"
            :style="{
                height: viewH + 'px',
                marginTop: expanded && viewH > 0 ? '6px' : '0px',
                WebkitMaskImage: mask,
                maskImage: mask,
            }"
            @scroll="scrollable && onScroll()"
        >
            <div
                x-ref="stream"
                class="nk-tr-stream flex flex-col transition-transform duration-200 ease-out"
                :style="{
                    gap: gap + 'px',
                    transform: 'translateY(' + translate + 'px)',
                }"
            >
                <template x-for="(line, i) in steps.slice(0, count)" :key="i">
                    <p
                        class="nk-tr-sentence m-0 text-sm leading-5 text-fg-secondary"
                        x-text="line"
                    ></p>
                </template>
            </div>
        </div>
    </div>
@else
    <span
        {{ $attributes->merge([
            'class' => Arr::toCssClasses([
                'inline-block font-medium tracking-tight select-none',
                $sizeClasses,
                $staticColor,
            ]),
            'data-slot' => 'thinking-state',
            'data-animate' => $animate ? 'true' : 'false',
            'role' => 'status',
            'aria-live' => 'polite',
        ]) }}
    >
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ $simpleText }}
        @endif
    </span>
@endif
