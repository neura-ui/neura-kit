@props([
    'data' => null,
    'rootName' => 'root',
    'defaultExpandDepth' => 1,
    'searchable' => true,
    'pageSize' => 50,
    'copyable' => true,
    'showPath' => true,
    'maxHeight' => 420,
    'label' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $payload = $data;
    if (is_string($payload)) {
        $decoded = json_decode($payload, true);
        $payload = json_last_error() === JSON_ERROR_NONE ? $decoded : $payload;
    }

    $phrases = [
        'search' => neura_trans('jsonSearch', null, 'Search'),
        'empty' => neura_trans('jsonNoMatches', null, 'No matches'),
        'more' => neura_trans('jsonShowMore', null, 'Show more'),
        'copyValue' => neura_trans('jsonCopyValue', null, 'Copy value'),
        'copyPath' => neura_trans('jsonCopyPath', null, 'Copy path'),
        'copied' => neura_trans('jsonCopied', null, 'Copied'),
        'failed' => neura_trans('jsonCopyFailed', null, 'Copy failed'),
        'keys' => neura_trans('jsonKeys', null, 'keys'),
        'key' => neura_trans('jsonKey', null, 'key'),
        'items' => neura_trans('jsonItems', null, 'items'),
        'item' => neura_trans('jsonItem', null, 'item'),
    ];

    $config = [
        'data' => $payload,
        'rootName' => (string) $rootName,
        'defaultExpandDepth' => max(0, (int) $defaultExpandDepth),
        'searchable' => filter_var($searchable, FILTER_VALIDATE_BOOLEAN),
        'pageSize' => max(1, (int) $pageSize),
        'copyable' => filter_var($copyable, FILTER_VALIDATE_BOOLEAN),
        'showPath' => filter_var($showPath, FILTER_VALIDATE_BOOLEAN),
        'maxHeight' => max(120, (int) $maxHeight),
        'label' => $label ? (string) $label : neura_trans('jsonLabel', null, 'JSON'),
        'phrases' => $phrases,
    ];

    $rounded = PackResolver::rounded(neura_config('json-viewer', 'rounded'));
@endphp

<div
    {{ $attributes->class("nk-json {$rounded} border border-edge bg-surface") }}
    x-data="neuraJsonViewer({{ Js::from($config) }})"
    x-on:keydown="onKey($event)"
>
    <div class="nk-json-bar" x-show="searchable" x-cloak>
        <input
            x-ref="search"
            class="nk-json-search"
            type="search"
            autocomplete="off"
            spellcheck="false"
            x-model="query"
            x-bind:placeholder="phrases.search"
            x-bind:aria-label="phrases.search"
        />
        <span class="nk-json-count" aria-live="polite" x-text="matchLabel"></span>
    </div>

    <div
        class="nk-json-tree"
        role="tree"
        tabindex="0"
        x-ref="tree"
        x-bind:aria-label="label"
        x-bind:style="'max-height:' + maxHeight + 'px'"
    >
        <div class="nk-json-highlight" aria-hidden="true" x-bind:style="highlight"></div>

        <template x-for="row in visible" :key="row.path">
            <div
                class="nk-json-row"
                role="treeitem"
                x-bind:aria-level="row.level"
                x-bind:aria-posinset="row.pos || 1"
                x-bind:aria-setsize="row.size || 1"
                x-bind:aria-expanded="row.branch ? (row.open ? 'true' : 'false') : undefined"
                x-bind:aria-selected="selected === row.path"
                x-bind:class="selected === row.path && 'is-current'"
                x-bind:style="{ paddingInlineStart: 'calc(0.4rem + ' + (row.level - 1) + ' * var(--nk-json-indent))' }"
                x-on:click="select(row)"
            >
                <button
                    type="button"
                    class="nk-json-chevron"
                    x-show="row.branch && !row.more"
                    x-bind:class="row.open && 'is-open'"
                    x-on:click.stop="toggle(row)"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M4.5 2.5 8 6 4.5 9.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <span class="nk-json-spacer" x-show="!row.branch && !row.more" aria-hidden="true"></span>
                <button type="button" class="nk-json-more" x-show="row.more" x-on:click.stop="showMore(row)" x-text="phrases.more"></button>
                <span class="nk-json-key" x-show="!row.more" x-text="row.key"></span>
                <span class="nk-json-preview" x-show="!row.more" x-bind:data-type="row.type" x-text="row.preview" x-bind:title="row.title"></span>
                <span class="nk-json-meta" x-show="row.branch && !row.more" x-text="row.meta"></span>
                <span class="nk-json-actions" x-show="copyable && !row.more" x-bind:aria-hidden="selected !== row.path">
                    <button type="button" tabindex="-1" x-bind:aria-label="phrases.copyValue" x-on:click.stop="copy(row, 'value')">
                        <svg x-show="copied !== row.path + ':value'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
                            <rect x="5" y="5" width="8" height="8" rx="1.5" />
                            <path d="M4 11H3.5A1.5 1.5 0 0 1 2 9.5v-6A1.5 1.5 0 0 1 3.5 2h6A1.5 1.5 0 0 1 11 3.5V4" />
                        </svg>
                        <svg x-show="copied === row.path + ':value'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M3.5 8.5 6.5 11.5 12.5 4.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <button type="button" tabindex="-1" x-bind:aria-label="phrases.copyPath" x-on:click.stop="copy(row, 'path')">
                        <svg x-show="copied !== row.path + ':path'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
                            <path d="M6 3.5h6.5v9H6z" />
                            <path d="M4 5.5H3.5v8h7V12" />
                        </svg>
                        <svg x-show="copied === row.path + ':path'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M3.5 8.5 6.5 11.5 12.5 4.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </span>
            </div>
        </template>

        <p class="nk-json-empty" x-show="query && !visible.length" x-text="phrases.empty"></p>
    </div>

    <div class="nk-json-path" x-show="showPath && selectedNode" x-cloak>
        <span class="nk-json-path-text" x-text="selected"></span>
        <span class="nk-json-path-type" x-text="selectedType"></span>
        <button type="button" x-on:click="copySelected('path')" x-text="copied.endsWith(':path') ? phrases.copied : phrases.copyPath"></button>
    </div>

    <p class="nk-json-status" aria-live="polite" x-text="status"></p>
</div>
