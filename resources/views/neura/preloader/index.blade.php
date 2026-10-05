@props([
    'label' => null,
    'indicator' => null,
    'orbState' => null,
    'orbSize' => null,
    'spinnerVariant' => 'ring',
    'logo' => null,
    'logoLight' => null,
    'logoDark' => null,
    'brand' => null,
    'autoHide' => null,
    'minDuration' => null,
    'zIndex' => 100,
    'overlay' => null,
])

@php
    use Illuminate\Support\Arr;

    $indicator = $indicator ?: (neura_config('preloader', 'indicator') ?: 'mark');
    $orbState = $orbState ?: (neura_config('preloader', 'orbState') ?: 'working');
    $orbSize = $orbSize ?: (neura_config('preloader', 'orbSize') ?: 72);
    $minDuration = $minDuration !== null
        ? (int) $minDuration
        : (int) (neura_config('preloader', 'minDuration') ?: 400);
    $autoHide = $autoHide !== null
        ? filter_var($autoHide, FILTER_VALIDATE_BOOLEAN)
        : (bool) (neura_config('preloader', 'autoHide') ?? true);
    $overlay = $overlay !== null
        ? filter_var($overlay, FILTER_VALIDATE_BOOLEAN)
        : true;
    $label = $label ?? neura_trans('loading');

    $hasBrand = filled($brand) || filled($logo) || filled($logoLight) || filled($logoDark);
    $markLight = $logoLight ?: $logo;
    $markDark = $logoDark ?: $logo ?: $logoLight;

    $shellClasses = Arr::toCssClasses([
        'nk-preloader flex flex-col items-center justify-center gap-6',
        'bg-surface text-fg',
        'transition-opacity duration-standard ease-enter motion-reduce:transition-none',
        'fixed inset-0' => $overlay,
        'relative min-h-72 w-full overflow-hidden rounded-2xl border border-dashed border-edge' => ! $overlay,
    ]);
@endphp

<div
    data-slot="preloader"
    data-nk-preloader
    data-indicator="{{ $indicator }}"
    role="status"
    aria-live="polite"
    aria-busy="true"
    x-data="neuraPreloader({
        autoHide: @js($autoHide),
        minDuration: @js($minDuration),
        remove: @js($overlay),
    })"
    x-show="visible"
    x-transition:leave="transition ease-standard duration-standard motion-reduce:transition-none"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->class([$shellClasses])->merge($overlay ? ['style' => "z-index: {$zIndex}"] : []) }}
>
    @if ($indicator === 'mark')
        <div class="nk-preloader-scene">
            <div class="nk-preloader-stage">
                <span class="nk-preloader-glow" aria-hidden="true"></span>
                <svg class="nk-preloader-orbits" viewBox="0 0 160 160" fill="none" aria-hidden="true">
                    <circle class="nk-preloader-ring" cx="80" cy="80" r="74" />
                    <circle class="nk-preloader-ring nk-preloader-ring--dashed" cx="80" cy="80" r="62" />
                    <circle class="nk-preloader-arc" cx="80" cy="80" r="74" pathLength="100" />
                    <circle class="nk-preloader-arc nk-preloader-arc--reverse" cx="80" cy="80" r="62" pathLength="100" />
                    <g class="nk-preloader-bead">
                        <circle cx="80" cy="6" r="2.35" />
                    </g>
                    <g class="nk-preloader-bead nk-preloader-bead--slow">
                        <circle cx="142" cy="80" r="1.6" />
                    </g>
                </svg>
                <div class="nk-preloader-mark">
                    @if ($markLight || $markDark)
                        @if ($markLight)
                            <img src="{{ $markLight }}" alt="" class="nk-preloader-logo dark:hidden" width="48" height="49">
                        @endif
                        @if ($markDark)
                            <img src="{{ $markDark }}" alt="" class="nk-preloader-logo hidden dark:block" width="48" height="49">
                        @endif
                    @else
                        <svg class="nk-preloader-glyph" viewBox="0 0 64 68" fill="none" aria-hidden="true">
                            <path pathLength="100" d="M1 58 L1 14 L15 1 L32 13 L49 1 L63 12 L63 58 L49 67 L32 56 L15 67 Z" />
                            <path pathLength="100" d="M1 14 L63 58" />
                            <path pathLength="100" d="M49 1 L49 26 L32 13 L15 67" />
                        </svg>
                    @endif
                </div>
            </div>

            @if (filled($brand))
                <p class="nk-preloader-word">{{ $brand }}</p>
            @endif

            <span class="nk-preloader-bar" aria-hidden="true"><span></span></span>

            @if (filled($label))
                <p class="nk-preloader-label">{{ $label }}</p>
            @endif
        </div>
    @else
        @if ($hasBrand)
            <div class="shrink-0">
                <neura::brand
                    :name="$brand"
                    :logo="$logo"
                    :logoLight="$logoLight"
                    :logoDark="$logoDark"
                    class="justify-center"
                />
            </div>
        @endif

        <div class="flex flex-col items-center gap-4">
            @if ($indicator === 'orb')
                <neura::orb state="{{ $orbState }}" :size="(int) $orbSize" color="muted" />
            @elseif ($indicator === 'spinner')
                <neura::spinner variant="{{ $spinnerVariant }}" size="xl" color="primary" />
            @endif

            @if (filled($label))
                <neura::text size="sm" class="font-mono uppercase tracking-[0.18em] text-fg-muted">
                    {{ $label }}
                </neura::text>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
