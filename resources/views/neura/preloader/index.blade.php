@props([
    'label' => null,
    'indicator' => null,
    'orbState' => null,
    'orbSize' => null,
    'spinnerVariant' => 'default',
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

    $indicator = $indicator ?: (neura_config('preloader', 'indicator') ?: 'orb');
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

    $shellClasses = Arr::toCssClasses([
        'nk-preloader flex flex-col items-center justify-center gap-6',
        'bg-surface text-fg',
        'transition-opacity duration-300 ease-out motion-reduce:transition-none',
        'fixed inset-0' => $overlay,
        'relative min-h-72 w-full overflow-hidden rounded-2xl border border-dashed border-edge' => ! $overlay,
    ]);
@endphp

<div
    data-slot="preloader"
    data-nk-preloader
    role="status"
    aria-live="polite"
    aria-busy="true"
    x-data="neuraPreloader({
        autoHide: @js($autoHide),
        minDuration: @js($minDuration),
        remove: @js($overlay),
    })"
    x-show="visible"
    x-transition:leave="transition ease-out duration-300 motion-reduce:transition-none"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->class([$shellClasses])->merge($overlay ? ['style' => "z-index: {$zIndex}"] : []) }}
>
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

    {{ $slot }}
</div>
