@props([
    'label' => 'Merge',
    'actions' => [],
    'method' => null,
    'icon' => null,
    'variant' => null,
    'size' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'doneLabel' => null,
    'pendingLabel' => null,
    'successLabel' => null,
    'resetAfter' => 2400,
    'delay' => 800,
    'disabled' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $actions = array_values(array_map(static function ($action): array {
        if (! is_array($action)) {
            return ['label' => (string) $action];
        }

        $pending = $action['pendingLabel'] ?? $action['pending-label'] ?? null;
        $success = $action['successLabel'] ?? $action['success-label'] ?? $action['doneLabel'] ?? $action['done-label'] ?? null;
        $reset = $action['resetAfter'] ?? $action['reset-after'] ?? null;

        return [
            'label' => $action['label'] ?? '',
            'method' => $action['method'] ?? null,
            'icon' => $action['icon'] ?? null,
            'color' => $action['color'] ?? null,
            'disabled' => (bool) ($action['disabled'] ?? false),
            'swap' => array_key_exists('swap', $action) ? (bool) $action['swap'] : true,
            'pendingLabel' => filled($pending) ? (string) $pending : null,
            'successLabel' => filled($success) ? (string) $success : null,
            'resetAfter' => ($reset === null || $reset === '') ? null : (int) $reset,
        ];
    }, is_array($actions) ? $actions : []));

    $wireClick = $attributes->get('wire:click');
    if (is_string($wireClick) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $wireClick)) {
        $method = $method ?: $wireClick;
        $attributes = $attributes->except('wire:click');
    }

    $hasMenu = $actions !== []
        || (isset($menu) && $menu->isNotEmpty())
        || $slot->isNotEmpty();

    $size = $size ?? neura_config('split-button', 'size') ?? 'sm';
    $rounded = $rounded ?? neura_config('split-button', 'rounded');
    $shadow = $shadow ?? neura_config('split-button', 'shadow');
    $variant = $variant ?? neura_config('split-button', 'variant') ?? 'dark';

    $semanticColors = ['primary', 'secondary', 'danger', 'success', 'warning', 'info'];
    $tailwindColors = [
        'slate', 'gray', 'zinc', 'neutral', 'stone',
        'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald',
        'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple',
        'fuchsia', 'pink', 'rose',
    ];
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];
    $defaultColorStyle = [
        'primary' => 'dark',
        'secondary' => 'dark',
    ];

    if (str_contains((string) $variant, '-')) {
        $parts = explode('-', $variant, 2);
        $color = $color ?? $parts[0];
        $style = $parts[1];
    } elseif (in_array($variant, array_merge($semanticColors, $tailwindColors), true)) {
        $color = $color ?? $variant;
        $style = $defaultColorStyle[$color] ?? 'solid';
    } elseif (in_array($variant, $variantStyles, true)) {
        $color = $color ?? neura_config('split-button', 'color') ?? 'primary';
        $style = $variant;
    } else {
        $color = $color ?? 'primary';
        $style = $defaultColorStyle[$color] ?? 'solid';
    }

    $sizeConfig = PackResolver::buttonSize($size);
    $iconClass = $sizeConfig['icon'] ?? 'size-4';
    $colorConfig = PackResolver::buttonColor($color, $style);
    $stripShadow = static fn (?string $classes): string => trim((string) preg_replace('/\s*shadow-[^\s]+/', '', $classes ?? ''));
    $shadowClass = in_array($style, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);
    $roundedClass = PackResolver::rounded($rounded);
    $menuRoundedClass = PackResolver::rounded(neura_config('dropdown', 'rounded'));
    $menuShadowClass = PackResolver::shadow(neura_config('dropdown', 'shadow'));

    $icons = collect([$icon])
        ->merge(collect($actions)->pluck('icon'))
        ->filter(fn ($name) => filled($name))
        ->unique()
        ->values();

    $spinnerSize = in_array($size, ['xs'], true) ? 'xs' : 'sm';

    $config = [
        'label' => $label,
        'method' => $method,
        'icon' => $icon,
        'actions' => $actions,
        'pendingLabel' => $pendingLabel,
        'successLabel' => $successLabel ?: $doneLabel,
        'resetAfter' => (int) $resetAfter,
        'delay' => (int) $delay,
        'disabled' => (bool) $disabled,
    ];

    $rootAttributes = $attributes->merge([
        'x-data' => 'neuraSplitButton('.json_encode($config).')',
        'x-bind:data-state' => 'state',
        'x-on:keydown.escape.prevent.stop' => 'closeMenu(true)',
    ])->class([
        'nk-split font-medium',
        $stripShadow($colorConfig['base'] ?? ''),
        $roundedClass,
        $shadowClass,
    ]);
@endphp

<div {{ $rootAttributes }}>
    <button
        type="button"
        @class(['nk-split-main', $sizeConfig['base'] ?? '', $sizeConfig['padding'] ?? ''])
        x-on:click="run()"
        x-bind:disabled="disabled"
        x-bind:data-state="state"
        x-bind:aria-busy="state === 'pending' ? 'true' : 'false'"
        x-bind:aria-disabled="state === 'pending' || disabled ? 'true' : null"
    >
        <span class="nk-split-slot" x-ref="morph" aria-hidden="true">
            <span class="nk-split-content" x-ref="content">
                @if (isset($leading) && $leading->isNotEmpty())
                    <span class="nk-split-icon">{{ $leading }}</span>
                @elseif ($icons->isNotEmpty())
                    <span class="nk-split-icon">
                        @foreach ($icons as $name)
                            <span
                                class="nk-split-icon-phase"
                                x-show="icon === {{ json_encode($name) }}"
                                x-cloak
                                x-transition:enter="nk-split-icon-in"
                                x-transition:enter-start="nk-split-icon-from"
                                x-transition:enter-end="nk-split-icon-rest"
                                x-transition:leave="nk-split-icon-out"
                                x-transition:leave-start="nk-split-icon-rest"
                                x-transition:leave-end="nk-split-icon-from"
                            >
                                <neura::icon :name="$name" @class([$iconClass]) />
                            </span>
                        @endforeach
                    </span>
                @endif
                <span class="nk-action-glyphs" x-ref="glyphs">
                    <template x-for="glyph in glyphs" :key="glyph.id">
                        <span
                            class="nk-action-glyph"
                            x-text="glyph.char"
                            :data-glyph="glyph.id"
                            :data-fresh="glyph.fresh ? '' : null"
                            :style="`--glyph-i:${glyph.order}`"
                            x-transition:leave="nk-glyph-leave"
                            x-transition:leave-start="nk-glyph-leave-from"
                            x-transition:leave-end="nk-glyph-leave-to"
                        ></span>
                    </template>
                </span>
                <span class="nk-action-icon" x-show="state !== 'idle'" x-cloak aria-hidden="true">
                    <span class="nk-action-phase" x-show="state === 'pending'" x-cloak>
                        <neura::spinner variant="ring" :size="$spinnerSize" color="current" />
                    </span>
                    <span class="nk-action-phase" x-show="state === 'success'" x-cloak>
                        <svg class="nk-action-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 12l5 5L20 6" />
                        </svg>
                    </span>
                </span>
            </span>
        </span>
        <span class="sr-only" x-text="label"></span>
        <span class="sr-only" role="status" x-text="announcement"></span>
    </button>

    @if ($hasMenu)
    <button
        type="button"
        @class(['nk-split-trigger', $sizeConfig['base'] ?? '', $sizeConfig['paddingSquared'] ?? ''])
        x-ref="trigger"
        x-bind:disabled="disabled"
        x-bind:aria-expanded="open.toString()"
        x-bind:aria-disabled="state === 'pending' || disabled ? 'true' : null"
        x-bind:aria-label="`${label} more actions`"
        aria-haspopup="menu"
        x-on:click.stop="open ? closeMenu(false) : openMenu(false)"
        x-on:keydown="onTriggerKey($event)"
    >
        <svg viewBox="0 0 24 24" @class(['nk-split-chevron', $iconClass]) fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-ref="menu"
            x-cloak
            x-anchor.bottom-end.offset.4="$refs.trigger"
            x-on:click.away="if (!$root.contains($event.target)) closeMenu(false)"
            x-on:keydown.down.prevent.stop="focusMove(1)"
            x-on:keydown.up.prevent.stop="focusMove(-1)"
            x-on:keydown.home.prevent.stop="focusEdge('first')"
            x-on:keydown.end.prevent.stop="focusEdge('last')"
            x-on:keydown.escape.prevent.stop="closeMenu(true)"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
            data-side="bottom"
            data-align="end"
            role="menu"
            @class([
                'nk-split-menu nk-menu-motion isolate bg-surface-raised text-fg border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
                $menuRoundedClass,
                $menuShadowClass,
            ])
        >
            @foreach ($actions as $index => $action)
                @php
                    $tone = strtolower((string) ($action['color'] ?? ''));
                    $tone = preg_match('/^[a-z][a-z0-9-]{0,30}$/', $tone) ? $tone : '';
                    $itemStyle = '--i: '.(int) $index;
                    if ($tone !== '') {
                        $itemStyle .= '; --nk-item: var(--color-'.$tone.'-600); --nk-item-soft: var(--color-'.$tone.'-400)';
                    }
                @endphp
                <button
                    type="button"
                    role="menuitem"
                    @class(['nk-menu-item', 'has-tone' => $tone !== ''])
                    style="{{ $itemStyle }}"
                    @if (!empty($action['disabled'])) disabled @endif
                    x-on:click="choose({{ json_encode($action) }})"
                >
                    @if (!empty($action['icon']))
                        <neura::icon :name="$action['icon']" class="size-4 shrink-0" />
                    @endif
                    <span>{{ $action['label'] }}</span>
                </button>
            @endforeach

            @isset($menu)
                {{ $menu }}
            @endisset

            @if ($slot->isNotEmpty())
                {{ $slot }}
            @endif
        </div>
    </template>
    @endif
</div>
