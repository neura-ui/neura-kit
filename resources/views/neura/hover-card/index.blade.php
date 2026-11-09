@props([
    'trigger' => null,
    'position' => null,
    'offset' => null,
    'delay' => null,
    'closeDelay' => null,
    'size' => null,
    'rounded' => null,
    'shadow' => null,
    'disabled' => false,
])

@php
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;

    // Side, delays, size, corners and shadow come from config('neura-kit.hover-card').
    $position = (string) ($position ?? neura_config('hover-card', 'position') ?: 'bottom-start');
    $position = preg_match('/^(top|bottom|left|right)(-(start|end))?$/', $position) ? $position : 'bottom-start';
    [$side, $align] = array_pad(explode('-', $position, 2), 2, 'center');
    $offset = (int) ($offset ?? 8);
    $sizes = PackResolver::hoverCardSize($size ?? neura_config('hover-card', 'size'));
    $roundedClass = PackResolver::rounded($rounded ?? neura_config('hover-card', 'rounded'));
    $shadowClass = PackResolver::shadow($shadow ?? neura_config('hover-card', 'shadow'));
    $disabled = filled($disabled) && $disabled;

    $config = [
        'delay' => max(0, (int) ($delay ?? neura_config('hover-card', 'delay') ?? 300)),
        'closeDelay' => max(0, (int) ($closeDelay ?? neura_config('hover-card', 'closeDelay') ?? 150)),
        'disabled' => $disabled,
    ];
@endphp

{{--
    A card that opens over its trigger on hover, and on keyboard focus.

        <neura::hover-card>
            <x-slot:trigger><a href="/advertisers/12">Demo Advertiser</a></x-slot:trigger>
            <neura::hover-card.header title="Demo Advertiser" subtitle="Advertiser" initials="DA" />
            <neura::hover-card.stats>
                <neura::hover-card.stat label="Leads" value="3 140" />
            </neura::hover-card.stats>
        </neura::hover-card>

    It waits `delay` before opening and stays open while the pointer is on the trigger or the
    card. The card is moved to the body, so it can sit inside a paragraph or a table cell.
--}}
<span
    x-data="neuraHoverCard({{ Js::from($config) }})"
    x-on:pointerenter="enter($event)"
    x-on:pointerleave="leave($event)"
    x-on:focusin="focus($event)"
    x-on:focusout="blur($event)"
    x-on:keydown.escape="hide()"
    {{ $attributes->class(['nk-hover-card-trigger']) }}
    data-slot="hover-card"
>
    <span x-ref="trigger" class="nk-hover-card-anchor">{{ $trigger }}</span>

    <template x-teleport="body">
        <div
            x-ref="card"
            x-show="open"
            x-anchor.{{ $position }}.offset.{{ $offset }}="$refs.trigger"
            x-on:pointerenter="enter($event)"
            x-on:pointerleave="leave($event)"
            x-on:focusin="keep()"
            x-on:focusout="blur($event)"
            x-on:keydown.escape="hide(true)"
            data-side="{{ $side }}"
            data-align="{{ $align === 'center' ? 'center' : $align }}"
            data-slot="hover-card-content"
            @class([
                'nk-menu-motion nk-hover-card absolute z-50 flex flex-col',
                $sizes['width'], $sizes['padding'], $sizes['gap'],
                $roundedClass, $shadowClass,
            ])
            style="display: none;"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
        >
            {{ $slot }}
        </div>
    </template>
</span>
