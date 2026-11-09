@aware([
    'separator' => null,
    'truncate' => null,
])

@props([
    'href' => null,
    'icon' => null,
    'iconVariant' => 'mini',
    'separator' => null,
    'current' => null,
    'label' => null,
    'initials' => null,
    'src' => null,
    'color' => null,
    'dot' => null,
    'badge' => null,
    'badgeColor' => null,
    'options' => null,
    'searchable' => null,
    'footer' => null,
])

@php
    use Illuminate\View\ComponentSlot;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\TimeGrid\Layout;

    // The trail's separator, or this item's own: a built-in name, an icon name, or a slot.
    $separator = $separator ?? neura_config('breadcrumbs', 'separator') ?? 'chevron';
    $builtIn = ['chevron', 'slash', 'dot', 'arrow', 'pipe'];
    $customSeparator = ! is_string($separator) || ! in_array($separator, $builtIn, true);
    $separatorKey = $customSeparator ? 'custom' : $separator;

    $text = trim((string) ($label ?? preg_replace('/\s+/', ' ', strip_tags((string) $slot))));
    $tag = $href ? 'a' : 'span';
    $truncate = filled($truncate) && $truncate !== false;

    // Neighbours to switch to: names, or ['label', 'href', 'active', 'dot', 'initials', 'color', 'icon', 'meta'].
    $optionList = [];
    foreach (is_array($options) ? $options : [] as $key => $option) {
        $option = is_array($option) ? $option : ['label' => (string) $option, 'href' => is_string($key) ? $key : null];
        $option['label'] = (string) ($option['label'] ?? '');
        $option['active'] = (bool) ($option['active'] ?? $option['label'] === $text);
        $optionList[] = $option;
    }
    $searchable = $searchable ?? count($optionList) > 6;
    $avatarClass = $initials || $src ? PackResolver::avatarColor($color ?: 'neutral') : null;
@endphp

<li data-bc-item data-bc-label="{{ $text }}" data-bc-href="{{ $href }}" data-sep="{{ $separatorKey }}" class="nk-bc-item" @if ($current === true) data-bc-current @endif
    @if ($optionList)
        x-data="{ open: false, query: '' }"
        {{-- No ">" in this tag: the trail reads its items' tags --}}
        x-init="$watch('open', function (value) { if (value) $nextTick(function () { if ($refs.search) $refs.search.focus() }) })"
        x-on:keydown.escape="if (open) { open = false; $refs.switch.focus() }"
    @endif
>
    @php ob_start(); @endphp
    <{{ $tag }}
        @if ($href) href="{{ $href }}" @endif
        data-bc-target
        @if ($current === true) aria-current="page" @endif
        {{ $attributes->class([$href ? 'nk-bc-link' : 'nk-bc-text']) }}
    >
        @if ($initials || $src)
            <span @class(['nk-bc-avatar', $avatarClass]) aria-hidden="true">
                @if ($src)
                    <img src="{{ $src }}" alt="" />
                @else
                    {{ $initials }}
                @endif
            </span>
        @endif
        @if ($dot)
            <span class="nk-bc-dot" style="background: {{ Layout::cssColor((string) $dot) }}" aria-hidden="true"></span>
        @endif
        @if ($icon)
            <neura::icon :name="$icon" :variant="$iconVariant" class="nk-bc-icon" />
        @endif
        @if (trim((string) $slot) !== '')
            <span class="nk-bc-label">{{ $slot }}</span>
        @endif
        @if (filled($badge))
            <neura::badge size="xs" variant="soft" :color="$badgeColor ?? 'neutral'" pill>{{ $badge }}</neura::badge>
        @endif
    </{{ $tag }}>
    @php $target = ob_get_clean(); @endphp

    {{-- A label cut by `truncate` shows in full in a tooltip, and only when it is cut --}}
    @if ($truncate && $text !== '')
        <neura::tooltip :content="$text" overflow position="bottom">{!! $target !!}</neura::tooltip>
    @else
        {!! $target !!}
    @endif

    @if ($optionList)
        <button
            type="button"
            class="nk-bc-switch"
            x-ref="switch"
            aria-haspopup="true"
            aria-expanded="false"
            x-bind:aria-expanded="open"
            x-on:click="open = ! open"
            aria-label="{{ neura_trans('breadcrumbsSwitch', ['label' => $text], 'Switch {label}') }}"
        >
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 6l3-3 3 3M5 10l3 3 3-3" /></svg>
        </button>
        <div
            class="nk-bc-menu nk-menu-motion"
            data-side="bottom"
            data-align="start"
            x-show="open"
            x-anchor.bottom-start.offset.6="$el.closest('li')"
            x-on:click.outside="open = false"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
            style="display: none;"
        >
            @if ($searchable)
                <label class="nk-bc-search">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="7" cy="7" r="4.5" /><path d="m10.5 10.5 3 3" /></svg>
                    <input type="text" x-ref="search" x-model="query" autocomplete="off" placeholder="{{ neura_trans('breadcrumbsSearch', null, 'Search…') }}" aria-label="{{ neura_trans('breadcrumbsSearch', null, 'Search…') }}" />
                </label>
            @endif
            @foreach ($optionList as $option)
                <a
                    href="{{ $option['href'] ?? '#' }}"
                    class="nk-bc-option"
                    @if ($option['active']) aria-current="true" @endif
                    @if ($searchable) x-show="! query || @js(mb_strtolower($option['label'])).includes(query.toLowerCase())" @endif
                >
                    @if (! empty($option['initials']))
                        <span @class(['nk-bc-avatar', PackResolver::avatarColor($option['color'] ?? 'neutral')]) aria-hidden="true">{{ $option['initials'] }}</span>
                    @elseif (! empty($option['dot']))
                        <span class="nk-bc-dot" style="background: {{ Layout::cssColor((string) $option['dot']) }}" aria-hidden="true"></span>
                    @elseif (! empty($option['icon']))
                        <neura::icon :name="$option['icon']" variant="mini" class="nk-bc-icon" />
                    @endif
                    <span class="nk-bc-option-label">{{ $option['label'] }}</span>
                    @if (! empty($option['meta']))
                        <span class="nk-bc-option-meta">{{ $option['meta'] }}</span>
                    @endif
                    @if ($option['active'])
                        <svg class="nk-bc-check" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3.5 8.5 3 3 6-7" /></svg>
                    @endif
                </a>
            @endforeach
            @if ($footer)
                <div class="nk-bc-menu-footer">{{ $footer }}</div>
            @endif
        </div>
    @endif

    @if ($customSeparator)
        <span class="nk-bc-sep" aria-hidden="true">
            @if (! is_string($separator))
                {{ $separator }}
            @else
                <neura::icon :name="$separator" variant="mini" class="nk-bc-icon" />
            @endif
        </span>
    @endif
</li>
