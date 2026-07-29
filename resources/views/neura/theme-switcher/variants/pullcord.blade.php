@aware([
    'ariaLabel' => null,
    'noEntrance' => false,
    'gravity' => null,
    'damping' => null,
    'iterations' => null,
    'stretchMax' => null,
    'embedded' => false,
])

@php
    $label = $ariaLabel ?: __('Pull the cord');
    $config = array_filter([
        'ariaLabel' => $label,
        'noEntrance' => (bool) $noEntrance,
        'gravity' => filled($gravity) ? (float) $gravity : null,
        'damping' => filled($damping) ? (float) $damping : null,
        'iterations' => filled($iterations) ? (int) $iterations : null,
        'stretchMax' => filled($stretchMax) ? (float) $stretchMax : null,
    ], fn ($v) => $v !== null);
@endphp

{{--
  Ceiling pull-cord. Physics runs in Alpine (`neuraPullCord`).
  Position / ink via CSS vars on any ancestor:

    --nk-pullcord-top / --nk-pullcord-right / --nk-pullcord-z / --nk-pullcord-ink

  Pass embedded to position:absolute inside a relative parent (docs previews).
--}}
<div
    @class(['nk-pullcord', 'nk-pullcord--embedded' => $embedded])
    data-nk-pullcord
    data-slot="pullcord"
    x-data="neuraPullCord(@js($config))"
>
    <div
        class="nk-pullcord-inner"
        x-ref="inner"
        x-bind:class="{ 'nk-pullcord-inner--drop': dropping }"
    >
        <svg
            viewBox="0 0 64 340"
            width="64"
            height="340"
            aria-hidden="true"
            class="nk-pullcord-svg"
        >
            <defs>
                <linearGradient id="nk-pc-knob-{{ $id = uniqid('pc') }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="100%" stop-color="#e7e7ec" />
                </linearGradient>
                <filter id="nk-pc-knob-sh-{{ $id }}" x="-70%" y="-70%" width="240%" height="240%">
                    <feDropShadow dx="0" dy="1.4" stdDeviation="1.5" flood-color="rgba(0,0,0,0.32)" />
                </filter>
            </defs>

            <path
                x-ref="path"
                d="M 32 0 L 32 176"
                stroke="var(--nk-pullcord-ink, var(--pullcord-ink, rgba(127, 127, 127, 0.45)))"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                fill="none"
                vector-effect="non-scaling-stroke"
            />

            <g x-ref="knobGroup">
                <g filter="url(#nk-pc-knob-sh-{{ $id }})">
                    <circle
                        cx="32"
                        cy="176"
                        r="6.5"
                        fill="url(#nk-pc-knob-{{ $id }})"
                        stroke="rgba(0,0,0,0.10)"
                        stroke-width="0.5"
                    />
                </g>
            </g>
        </svg>

        <button
            type="button"
            class="nk-pullcord-knob"
            x-ref="knob"
            x-bind:aria-label="ariaLabel"
            x-bind:aria-pressed="pulled.toString()"
            x-bind:title="ariaLabel"
        ></button>
    </div>
</div>
