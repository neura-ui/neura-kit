@props([
    'label' => 'Actions',
    'items' => [],
    'dropdown' => null,
    'variant' => null,
    'size' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'orientation' => 'horizontal',
    'collapseLabels' => true,
    'disabled' => false,
    'attached' => true,
    'vertical' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $items = array_values(is_array($items) ? $items : []);
    $dropdown = is_array($dropdown) ? $dropdown : [];
    $dropdownItems = array_values(is_array($dropdown['items'] ?? null) ? $dropdown['items'] : []);
    $dropdownLabel = $dropdown['label'] ?? 'More actions';
    $hasMenu = $dropdownItems !== [] || (isset($menu) && $menu->isNotEmpty());
    $orientation = $orientation === 'vertical' ? 'vertical' : 'horizontal';
    $stack = (bool) $vertical || $orientation === 'vertical';

    $slotHtml = '';
    $isCluster = false;
    if ($items === [] && ! $hasMenu && $slot->isNotEmpty()) {
        $slotHtml = $slot->toHtml();
        $isCluster = ! str_contains($slotHtml, 'nk-group-segment');
    }

    $size = $size ?? neura_config('button-group', 'size') ?? 'sm';
    if (! in_array($size, ['xs', 'sm', 'md', 'lg', 'xl'], true)) {
        $size = 'sm';
    }

    $rounded = $rounded ?? neura_config('button-group', 'rounded');
    $shadow = $shadow ?? neura_config('button-group', 'shadow');
    $variant = $variant ?? neura_config('button-group', 'variant') ?? 'outline';

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
        $color = $color ?? neura_config('button-group', 'color') ?? 'primary';
        $style = $variant;
    } else {
        $color = $color ?? 'primary';
        $style = $defaultColorStyle[$color] ?? 'solid';
    }

    $colorConfig = PackResolver::buttonColor($color, $style);
    $strip = static function (?string $classes): string {
        $classes = preg_replace('/\s*shadow-[^\s]+/', '', (string) $classes) ?? '';
        $classes = preg_replace('/\s*(?:[\w-]+:)*hover:[^\s]+/', '', $classes) ?? '';

        return trim($classes);
    };
    $shadowClass = in_array($style, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);
    $roundedClass = PackResolver::rounded($rounded);
    $base = $strip($colorConfig['base'] ?? '');
    $frame = str_contains($base, 'border') ? '' : 'border border-transparent';
    $menuRoundedClass = PackResolver::rounded(neura_config('dropdown', 'rounded'));
    $menuShadowClass = PackResolver::shadow(neura_config('dropdown', 'shadow'));

    $normalize = static function (array $item, int $index): array {
        return [
            'id' => (string) ($item['id'] ?? 'item-'.$index),
            'label' => (string) ($item['label'] ?? ''),
            'icon' => $item['icon'] ?? null,
            'method' => $item['method'] ?? null,
            'href' => $item['href'] ?? null,
            'disabled' => (bool) ($item['disabled'] ?? false),
            'iconOnly' => (bool) ($item['iconOnly'] ?? $item['icon-only'] ?? false),
            'content' => $item['content'] ?? null,
            'confirm' => $item['confirm'] ?? null,
            'confirmIcon' => $item['confirmIcon'] ?? $item['confirm-icon'] ?? 'check',
            'reserve' => array_values($item['reserve'] ?? []),
            'resetAfter' => (int) ($item['resetAfter'] ?? $item['reset-after'] ?? 1800),
        ];
    };

    $items = array_map($normalize, $items, array_keys($items));

    $config = [
        'disabled' => (bool) $disabled,
        'orientation' => $orientation,
        'collapse' => (bool) $collapseLabels,
    ];

    $rootAttributes = $attributes->merge([
        'x-data' => 'neuraButtonGroup('.json_encode($config).')',
        'x-bind:data-compact' => 'compact ? "" : null',
        'x-bind:aria-disabled' => 'disabled ? "true" : null',
    ])->class([
        'nk-group font-medium',
        $base,
        $frame,
        $roundedClass,
        $shadowClass,
    ]);
@endphp

@if ($isCluster)
<div
    {{ $attributes->class(['nk-btn-group']) }}
    role="group"
    @if ($stack) data-vertical @endif
    @if ($attached) data-attached @endif
>{!! $slotHtml !!}</div>
@else
<div
    {{ $rootAttributes }}
    role="group"
    aria-label="{{ $label }}"
    data-size="{{ $size }}"
    data-orientation="{{ $orientation }}"
    x-on:pointermove="onPointerMove($event)"
    x-on:pointerleave="hover = null"
    x-on:pointerdown="onPointerDown($event)"
    x-on:focusin="onFocus($event)"
    x-on:focusout="onBlur($event)"
    x-on:keydown="onKeyDown($event)"
    x-on:keydown.escape="if (open) { $event.preventDefault(); closeMenu(true) }"
>
    <span class="nk-group-highlight" x-ref="highlight" aria-hidden="true"></span>

    @foreach ($items as $item)
        @include('neura::button-group.segment', ['item' => $item])
    @endforeach

    @if ($slotHtml !== '')
        {!! $slotHtml !!}
    @elseif ($slot->isNotEmpty())
        {{ $slot }}
    @endif

    @if ($hasMenu)
        <button
            type="button"
            class="nk-group-segment nk-group-trigger"
            data-key="button-group-menu"
            x-ref="trigger"
            x-bind:disabled="disabled ? true : null"
            x-bind:aria-expanded="open.toString()"
            x-bind:data-quiet="quiet('button-group-menu')"
            aria-haspopup="menu"
            aria-label="{{ $dropdownLabel }}"
            title="{{ $dropdownLabel }}"
            x-on:click.stop="onTriggerClick($event)"
        >
            <span class="nk-group-content">
                <svg class="nk-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <template x-teleport="body">
            <div
                x-show="open"
                x-ref="menu"
                x-cloak
                @if ($orientation === 'vertical')
                    x-anchor.bottom-start.offset.6="$refs.trigger"
                    data-align="start"
                @else
                    x-anchor.bottom-end.offset.6="$refs.trigger"
                    data-align="end"
                @endif
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
                role="menu"
                @class([
                    'nk-group-menu nk-menu-motion isolate bg-surface-raised text-fg border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
                    $menuRoundedClass,
                    $menuShadowClass,
                ])
            >
                @foreach ($dropdownItems as $index => $action)
                    @php
                        $tone = strtolower((string) ($action['color'] ?? ''));
                        $tone = preg_match('/^[a-z][a-z0-9-]{0,30}$/', $tone) ? $tone : '';
                        $itemStyle = '--i: '.(int) $index;
                        if ($tone !== '') {
                            $itemStyle .= '; --nk-item: var(--color-'.$tone.'-600); --nk-item-soft: var(--color-'.$tone.'-400)';
                        }
                        $payload = [
                            'method' => $action['method'] ?? null,
                            'disabled' => (bool) ($action['disabled'] ?? false),
                        ];
                    @endphp
                    <button
                        type="button"
                        role="menuitem"
                        @class(['nk-menu-item', 'has-tone' => $tone !== ''])
                        style="{{ $itemStyle }}"
                        @if (!empty($action['disabled'])) disabled @endif
                        x-on:click="choose({{ json_encode($payload) }})"
                    >
                        @if (!empty($action['icon']))
                            <neura::icon :name="$action['icon']" class="size-4 shrink-0" />
                        @endif
                        <span>{{ $action['label'] ?? '' }}</span>
                    </button>
                @endforeach

                @isset($menu)
                    {{ $menu }}
                @endisset
            </div>
        </template>
    @endif

    <span class="sr-only" aria-live="polite" x-text="announcement"></span>
</div>
@endif
