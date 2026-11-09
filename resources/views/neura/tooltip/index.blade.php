@props([
    'content' => null,
    'kbd' => null,
    'position' => null,
    'offset' => null,
    'variant' => null,
    'arrow' => null,
    'delay' => null,
    'rounded' => null,
    'overflow' => false,
    'disabled' => false,
])

@php
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;

    // Variant, side, arrow, delay and corners come from config('neura-kit.tooltip').
    $variant = in_array($variant ?? neura_config('tooltip', 'variant'), ['dark', 'light'], true)
        ? ($variant ?? neura_config('tooltip', 'variant'))
        : 'dark';
    $position = (string) ($position ?? neura_config('tooltip', 'position') ?: 'top');
    $position = preg_match('/^(top|bottom|left|right)(-(start|end))?$/', $position) ? $position : 'top';
    [$side, $align] = array_pad(explode('-', $position, 2), 2, 'center');
    $offset = (int) ($offset ?? 8);
    $arrow = $arrow ?? neura_config('tooltip', 'arrow');
    $arrow = $arrow === true || $arrow === 'true' || $arrow === 1 || $arrow === '1';
    $delay = max(0, (int) ($delay ?? neura_config('tooltip', 'delay') ?? 250));
    $radius = PackResolver::radiusValue($rounded ?? neura_config('tooltip', 'rounded'));
    $disabled = filled($disabled) && $disabled;

    // Keys as a list, or one string split on spaces: "⌘ K" is two keys.
    $keys = is_array($kbd) ? array_values(array_filter(array_map('strval', $kbd), 'strlen'))
        : (filled($kbd) ? preg_split('/\s+/', trim((string) $kbd)) : []);

    // `overflow`: only when the trigger's text is cut (a truncated label).
    $overflow = filled($overflow) && $overflow;
    $config = ['delay' => $delay, 'disabled' => $disabled, 'overflow' => $overflow];
@endphp

{{--
    A short label for its trigger, shown on hover after a delay and on keyboard focus.

        <neura::tooltip content="Run" kbd="⌘ ↵"><neura::button icon="play" /></neura::tooltip>

    The first tooltip waits; the next ones in a toolbar open at once (the shared skip window).
    Rich content goes in a `content` slot.
--}}
<span
    x-data="neuraTooltip({{ Js::from($config) }})"
    x-id="['nk-tooltip']"
    x-on:pointerenter="enter($event)"
    x-on:pointerleave="leave()"
    x-on:focusin="focus($event)"
    x-on:focusout="leave()"
    x-on:pointerdown="hide()"
    x-on:keydown.escape="hide()"
    {{ $attributes->class(['nk-tooltip-trigger relative inline-flex']) }}
    data-slot="tooltip"
>
    {{ $slot }}

    @if (filled((string) $content) || $keys)
        <span
            x-ref="tip"
            x-show="open"
            x-anchor.{{ $position }}.offset.{{ $offset }}="$root"
            x-bind:id="$id('nk-tooltip')"
            x-bind:data-instant="instant ? '' : null"
            role="tooltip"
            class="nk-menu-motion nk-tooltip"
            data-side="{{ $side }}"
            data-align="{{ $align === 'center' ? 'center' : $align }}"
            data-variant="{{ $variant }}"
            @if ($keys) data-keys @endif
            style="display: none; --nk-tooltip-radius: {{ $radius }};"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
        >
            @if (filled((string) $content))
                <span class="nk-tooltip-body">{{ $content }}</span>
            @endif
            @if ($keys)
                <span class="nk-tooltip-keys">
                    @foreach ($keys as $key)
                        <kbd>{{ $key }}</kbd>
                    @endforeach
                </span>
            @endif
            @if ($arrow)
                <span class="nk-tooltip-arrow" aria-hidden="true"></span>
            @endif
        </span>
    @endif
</span>
