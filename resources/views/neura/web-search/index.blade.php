@props([
    'query' => '',
    /** @var list<array{title: string, url?: string, state?: string, discover?: int, finish?: int}> */
    'sources' => [],
    'autoPlay' => false,
    'loop' => false,
    'open' => true,
    'done' => false,
    'searchingLabel' => null,
    'emptyText' => null,
])

@php
    use Illuminate\Support\Js;

    $queryText = (string) ($query ?? '');
    $sites = collect($sources ?? [])
        ->filter(fn ($s) => filled(data_get($s, 'title')))
        ->values()
        ->map(function ($s) {
            $state = data_get($s, 'state', 'pending');
            if (! in_array($state, ['pending', 'loading', 'done'], true)) {
                $state = 'pending';
            }

            return [
                'title' => (string) data_get($s, 'title'),
                'url' => (string) (data_get($s, 'url') ?? ''),
                'state' => $state,
                'discover' => data_get($s, 'discover'),
                'finish' => data_get($s, 'finish'),
            ];
        })
        ->all();

    $allDone = (bool) $done || (
        count($sites) > 0 && collect($sites)->every(fn ($s) => $s['state'] === 'done')
    );

    $prefix = $searchingLabel ?? neura_trans('searching');
    $empty = $emptyText ?? neura_trans('webSearchEmpty');
    $config = [
        'sources' => $sites,
        'query' => $queryText,
        'autoPlay' => (bool) $autoPlay,
        'loop' => (bool) $loop,
        'open' => (bool) $open,
        'done' => $allDone,
    ];
@endphp

<div
    {{ $attributes->class(['nk-ws'])->merge([
        'data-slot' => 'web-search',
    ]) }}
    x-data="neuraWebSearch({{ Js::from($config) }})"
    :data-state="done ? 'done' : 'loading'"
    role="status"
    aria-live="polite"
>
    <div class="nk-ws-row">
        <span class="nk-ws-search-icon" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>

        <span class="nk-ws-label">
            <span class="nk-ws-shimmer" :class="done && 'nk-ws-shimmer-done'">
                {{ $prefix }}
                @if ($queryText !== '')
                    <span class="nk-ws-quote">“{{ $queryText }}”</span>
                @endif
            </span>
            <button
                type="button"
                class="nk-ws-chevron"
                aria-label="{{ neura_trans('toggleSearchResults') }}"
                :aria-expanded="open"
                @click="toggle()"
            >
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                </svg>
            </button>
        </span>
    </div>

    <div class="nk-ws-collapsible" :class="!open && 'nk-ws-collapsed'">
        <div class="nk-ws-collapsible-inner">
            @if (count($sites) === 0)
                <p class="nk-ws-empty mt-2 text-sm text-fg-muted">{{ $empty }}</p>
            @else
                <div class="nk-ws-results">
                    <span class="nk-ws-rail" aria-hidden="true"></span>
                    <ul class="nk-ws-list">
                        <template x-for="(site, i) in sources" :key="site.url || site.title || i">
                            <li
                                class="nk-ws-site"
                                :data-state="states[i]"
                                :style="'animation-delay:' + (i * 70) + 'ms'"
                                @click="states[i] === 'done' && openSource(site.url)"
                            >
                                <span class="nk-ws-bullet" aria-hidden="true">
                                    <span class="nk-ws-dots">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                                            <circle cx="12" cy="12" r="9" stroke-width="1.8" stroke-dasharray="1.8 3.6" stroke-linecap="round" />
                                        </svg>
                                    </span>
                                    <span class="nk-ws-globe">
                                        <svg viewBox="0 0 12 12" width="12" height="12" fill="none" stroke="currentColor" stroke-width="0.85" stroke-linecap="round" style="overflow:visible">
                                            <circle cx="6" cy="6" r="5.7" opacity="0.9" />
                                            <line x1="0.3" y1="6" x2="11.7" y2="6" opacity="0.9" />
                                            <path d="M6.057 11.565 C2.081 11.565 0.371 8.159 0.371 5.964 C0.371 3.642 2.152 0.329 6.05 0.329" opacity="0.9">
                                                <animate attributeName="d" dur="7.2s" begin="0s" repeatCount="indefinite" calcMode="spline" keyTimes="0;0.25;0.5;0.75;1" keySplines="0.42 0 0.58 1;0.42 0 0.58 1;0.42 0 0.58 1;0.42 0 0.58 1" values="M6.057 11.565 C2.081 11.565 0.371 8.159 0.371 5.964 C0.371 3.642 2.152 0.329 6.05 0.329;M6.012 11.55 C4.575 10.496 3.333 8.116 3.321 5.964 C3.307 3.399 4.974 0.977 6.012 0.329;M6.012 11.55 C7.211 10.781 8.715 8.287 8.715 5.964 C8.715 3.399 7.24 1.233 6.012 0.329;M6.012 11.55 C9.677 11.55 11.65 8.487 11.65 5.964 C11.65 3.499 9.748 0.329 6.012 0.329;M6.057 11.565 C2.081 11.565 0.371 8.159 0.371 5.964 C0.371 3.642 2.152 0.329 6.05 0.329" />
                                            </path>
                                        </svg>
                                    </span>
                                    <span class="nk-ws-check">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </span>
                                </span>
                                <span class="nk-ws-site-title" x-text="site.title"></span>
                                <span class="nk-ws-sep" x-show="site.url">·</span>
                                <span class="nk-ws-url" x-text="site.url" x-show="site.url"></span>
                                <span class="nk-ws-arrow" aria-hidden="true">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                                    </svg>
                                </span>
                            </li>
                        </template>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
