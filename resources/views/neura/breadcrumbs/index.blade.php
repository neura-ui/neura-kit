@props([
    'variant' => null,
    'separator' => null,
    'size' => null,
    'color' => null,
    'tinted' => false,
    'rounded' => null,
    'maxItems' => null,
    'itemsBefore' => null,
    'itemsAfter' => null,
    'responsive' => null,
    'truncate' => null,
    'label' => null,
    'actions' => null,
])

@php
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\TimeGrid\Layout;

    // Look, size, colour and folding come from config('neura-kit.breadcrumbs'); a prop wins.
    $variants = ['default', 'ghost', 'pills', 'steps', 'contained', 'underline'];
    $variant = $variant ?? neura_config('breadcrumbs', 'variant');
    $variant = in_array($variant, $variants, true) ? $variant : 'default';
    $separator = $separator ?? neura_config('breadcrumbs', 'separator') ?? 'chevron';
    $builtIn = ['chevron', 'slash', 'dot', 'arrow', 'pipe'];
    $separatorKey = is_string($separator) && in_array($separator, $builtIn, true) ? $separator : 'custom';
    $sizes = PackResolver::breadcrumbsSize($size ?? neura_config('breadcrumbs', 'size'));
    $radius = PackResolver::radiusValue($rounded ?? neura_config('breadcrumbs', 'rounded'));
    $color = (string) ($color ?? neura_config('breadcrumbs', 'color') ?: 'neutral');
    $neutral = in_array($color, ['neutral', 'foreground', 'fg'], true);
    $tone = $neutral ? 'var(--color-fg)' : Layout::cssColor($color);
    $onTone = $neutral ? 'var(--color-surface)' : '#fff';
    $tinted = filled($tinted) && $tinted;
    $responsive = (bool) ($responsive ?? neura_config('breadcrumbs', 'responsive'));
    $maxItems = $maxItems ?? neura_config('breadcrumbs', 'maxItems');
    $maxItems = is_numeric($maxItems) && (int) $maxItems > 0 ? (int) $maxItems : null;
    $truncate = filled($truncate) && $truncate !== false ? ($truncate === true ? '12rem' : (string) $truncate) : null;

    // The items, read from their own tags: label, link and position.
    $html = (string) $slot;
    preg_match_all('/<li data-bc-item\b[^>]*>/', $html, $found, PREG_OFFSET_CAPTURE);
    $items = [];
    foreach ($found[0] as [$tag, $offset]) {
        preg_match('/data-bc-label="([^"]*)"/', $tag, $labelMatch);
        preg_match('/data-bc-href="([^"]*)"/', $tag, $hrefMatch);
        $items[] = [
            'tag' => $tag,
            'offset' => $offset,
            'label' => html_entity_decode($labelMatch[1] ?? '', ENT_QUOTES),
            'href' => html_entity_decode($hrefMatch[1] ?? '', ENT_QUOTES) ?: null,
        ];
    }
    $count = count($items);

    // The middle that folds into "…": all but the first `itemsBefore` and the last `itemsAfter`.
    $before = max(0, (int) ($itemsBefore ?? neura_config('breadcrumbs', 'itemsBefore') ?? 1));
    $after = max(1, (int) ($itemsAfter ?? neura_config('breadcrumbs', 'itemsAfter') ?? 2));
    $middle = $before < $count - $after ? range($before, $count - $after - 1) : [];
    $folded = $maxItems && $count > $maxItems ? $middle : [];

    // Mark each item with its position (and folded ones as hidden), from the last so offsets hold.
    for ($i = $count - 1; $i >= 0; $i--) {
        $marks = ' data-bc-index="'.$i.'"'.(in_array($i, $folded, true) ? ' data-bc-folded hidden' : '');
        $tag = substr_replace($items[$i]['tag'], '<li data-bc-item'.$marks, 0, strlen('<li data-bc-item'));
        $html = substr_replace($html, $tag, $items[$i]['offset'], strlen($items[$i]['tag']));
    }

    // The last item is the page you are on, unless an item says `current` itself.
    if ($count && ! str_contains($html, 'data-bc-current')) {
        $lastAt = strrpos($html, '<li data-bc-item');
        $html = substr($html, 0, $lastAt).preg_replace('/data-bc-target/', 'data-bc-target aria-current="page"', substr($html, $lastAt), 1);
    }

    // On a narrow screen only a link back to the parent stays.
    $parent = null;
    for ($i = $count - 2; $i >= 0; $i--) {
        if ($items[$i]['href']) {
            $parent = $items[$i];
            break;
        }
    }

    $scripted = $folded || ($responsive && ($middle || $parent));
    $config = ['responsive' => $responsive, 'before' => $before, 'after' => $after];

    // The menu needs the trail's own state, so it comes only with the script.
    $showMore = $middle && $scripted;

    // "…" sits where the middle starts; the trail is cut there to put it in.
    $head = $html;
    $tail = '';
    if ($showMore) {
        $at = strpos($html, '<li data-bc-item data-bc-index="'.$before.'"');
        $head = substr($html, 0, $at);
        $tail = substr($html, $at);
    }

    $style = "--nk-bc-gap: {$sizes['gap']}; --nk-bc-icon: {$sizes['icon']}; --nk-bc-chip: {$sizes['chip']}; --nk-bc-radius: {$radius}; --nk-bc-tone: {$tone}; --nk-bc-on-tone: {$onTone};";
    if ($truncate) {
        $style .= " --nk-bc-truncate: {$truncate};";
    }
@endphp

{{--
    A trail of links to the page you are on, with `neura::breadcrumbs.item` children.

        variant     default | ghost | pills | steps | contained | underline
        separator   chevron | slash | dot | arrow | pipe | an icon name
        max-items   folds the middle into "…" past that many items
        responsive  folds the middle, then keeps only a back link, when the trail does not fit

    The last item is the current page (aria-current) unless an item has `current`.
--}}
<nav
    aria-label="{{ $label ?? neura_trans('breadcrumbsLabel', null, 'Breadcrumb') }}"
    {{ $attributes->class(['nk-bc', $sizes['text']])->merge(['style' => $style]) }}
    data-slot="breadcrumbs"
    data-variant="{{ $variant }}"
    data-mode="full"
    @if ($responsive) data-responsive @endif
    @if ($tinted) data-tinted @endif
    @if ($scripted) x-data="neuraBreadcrumbs({{ Js::from($config) }})" @endif
>
    <ol class="nk-bc-list" data-bc-list>
        {!! $head !!}
        @if ($showMore)
            <li class="nk-bc-item nk-bc-more" data-bc-more data-sep="{{ $separatorKey }}" @if (! $folded) hidden @endif>
                <button
                    type="button"
                    class="nk-bc-ellipsis"
                    x-ref="more"
                    aria-haspopup="true"
                    aria-expanded="false"
                    x-bind:aria-expanded="more"
                    x-on:click="more = ! more"
                    aria-label="{{ neura_trans('breadcrumbsMore', null, 'Show the hidden levels') }}"
                >
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="3.5" cy="8" r="1.3" /><circle cx="8" cy="8" r="1.3" /><circle cx="12.5" cy="8" r="1.3" /></svg>
                </button>
                <div
                    class="nk-bc-menu nk-menu-motion"
                    data-side="bottom"
                    data-align="start"
                    x-show="more"
                    x-anchor.bottom-start.offset.6="$refs.more"
                    x-on:click.outside="more = false"
                    x-on:keydown.escape.stop="more = false; $refs.more.focus()"
                    x-transition:enter="nk-menu-motion"
                    x-transition:enter-start="nk-menu-from"
                    x-transition:enter-end="nk-menu-to"
                    x-transition:leave="nk-menu-leaving"
                    x-transition:leave-start="nk-menu-to"
                    x-transition:leave-end="nk-menu-out"
                    style="display: none;"
                >
                    @foreach ($middle as $depth => $index)
                        @if ($items[$index]['href'])
                            <a href="{{ $items[$index]['href'] }}" class="nk-bc-option" style="--nk-bc-depth: {{ $depth }}" x-show="hidden.includes({{ $index }})">
                                <svg class="nk-bc-branch" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3v6.5h8M9 7l3 2.5-3 2.5" /></svg>
                                <span class="nk-bc-option-label">{{ $items[$index]['label'] }}</span>
                            </a>
                        @else
                            <span class="nk-bc-option" style="--nk-bc-depth: {{ $depth }}" x-show="hidden.includes({{ $index }})">
                                <svg class="nk-bc-branch" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3v6.5h8M9 7l3 2.5-3 2.5" /></svg>
                                <span class="nk-bc-option-label">{{ $items[$index]['label'] }}</span>
                            </span>
                        @endif
                    @endforeach
                </div>
                @if ($separatorKey === 'custom')
                    <span class="nk-bc-sep" aria-hidden="true">
                        @if (is_string($separator))
                            <neura::icon :name="$separator" variant="mini" class="nk-bc-icon" />
                        @else
                            {{ $separator }}
                        @endif
                    </span>
                @endif
            </li>
        @endif
        {!! $tail !!}
    </ol>

    @if ($responsive && $parent)
        <a href="{{ $parent['href'] }}" class="nk-bc-back" data-bc-back>
            <svg class="nk-bc-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 8h-9M7 4 3 8l4 4" /></svg>
            <span class="nk-bc-label">{{ $parent['label'] }}</span>
        </a>
    @endif

    @if ($actions)
        <div class="nk-bc-actions">{{ $actions }}</div>
    @endif
</nav>
