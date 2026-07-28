@props([
    'items' => [],
    'view' => null,
    'sort' => 'name',
    'direction' => 'asc',
    'selectable' => true,
    'multiple' => true,
    'searchable' => true,
    'searchMode' => 'local',
    'searchScope' => 'folder',
    'searchMatcher' => null,
    'searchDelay' => 300,
    'searchPlaceholder' => null,
    'scopeToggle' => true,
    'details' => true,
    'uploadable' => true,
    'creatable' => true,
    'downloadable' => true,
    'deletable' => true,
    'renamable' => true,
    'loading' => false,
    'rootLabel' => null,
    'height' => null,
    'size' => null,
    'rounded' => null,
    'shadow' => null,
])

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use Neura\Kit\Support\PackResolver;

    $sizes = PackResolver::fileManagerSize($size);
    $colors = PackResolver::fileManagerColor();

    $roundedClass = PackResolver::rounded($rounded ?: neura_config('file-manager', 'rounded'));
    $shadowClass = PackResolver::shadow($shadow ?: neura_config('file-manager', 'shadow'));

    $view = $view ?: (neura_config('file-manager', 'view') ?: 'list');
    $selectable = filled($selectable) && $selectable;
    $multiple = $selectable && filled($multiple) && $multiple;
    $details = filled($details) && $details;

    $uid = 'nk-fm-'.Str::random(8);

    // kind => heroicon name, mirrored from the ICONS map in file-manager.ts
    $kindIcons = [
        'folder' => 'folder',
        'image' => 'photo',
        'video' => 'film',
        'audio' => 'musical-note',
        'pdf' => 'document-text',
        'sheet' => 'table-cells',
        'doc' => 'document-text',
        'archive' => 'archive-box',
        'code' => 'code-bracket',
        'text' => 'document-text',
        'file' => 'document',
    ];

    $shellClasses = Arr::toCssClasses([
        'flex w-full flex-col overflow-hidden border transition-shadow duration-200',
        $roundedClass,
        $shadowClass,
        $colors['shell'],
        $colors['dropping'],
    ]);

    $barClasses = Arr::toCssClasses([
        'flex shrink-0 items-center border-b',
        $sizes['toolbar'],
        $colors['bar'],
    ]);

    $actionClasses = Arr::toCssClasses([
        'inline-flex shrink-0 items-center justify-center rounded-lg transition-colors',
        $sizes['action'],
        $colors['action'],
    ]);

    $toggleClasses = Arr::toCssClasses([
        'inline-flex items-center justify-center rounded-md transition-colors',
        $sizes['action'],
        $colors['toggle']['base'],
    ]);

    $menuItemClasses = 'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-1.5 '
        .$sizes['meta'].' text-fg-secondary transition-colors hover:bg-hover hover:text-fg';

    $menuDangerClasses = 'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-1.5 '
        .$sizes['meta'].' text-danger-600 transition-colors hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-950/40';

    $entryProps = [
        'sizes' => $sizes,
        'colors' => $colors,
        'sprite' => $uid,
        'selectable' => $selectable,
        'downloadable' => $downloadable,
        'deletable' => $deletable,
    ];
@endphp

<div
    data-nk-file-manager
    x-data="neuraFileManager({
        items: @js($items),
        view: @js($view),
        sort: @js($sort),
        direction: @js($direction),
        selectable: @js($selectable),
        multiple: @js($multiple),
        searchable: @js((bool) $searchable),
        searchMode: @js($searchMode),
        searchScope: @js($searchScope),
        searchMatcher: @js($searchMatcher),
        searchDelay: @js((int) $searchDelay),
        details: @js($details),
        downloadable: @js((bool) $downloadable),
        deletable: @js((bool) $deletable),
        renamable: @js((bool) $renamable),
        loading: @js((bool) $loading),
        rootLabel: @js($rootLabel ?? neura_trans('home')),
        locale: @js(str_replace('_', '-', app()->getLocale())),
    })"
    x-on:dragenter.prevent="handleDragEnter($event)"
    x-on:dragover.prevent
    x-on:dragleave.prevent="handleDragLeave()"
    x-on:drop.prevent="handleDrop($event)"
    x-on:contextmenu.prevent="openMenu($event)"
    x-on:click="closeMenu()"
    x-on:keydown.escape="closeMenu()"
    :data-dropping="dropping || null"
    {{ $attributes->class(['relative', $shellClasses]) }}
    @if ($height) style="height: {{ $height }}" @endif
>
    <neura::file-manager.sprite :id="$uid" :icons="array_values($kindIcons)" />

    {{-- Toolbar ------------------------------------------------------------ --}}
    <div class="{{ $barClasses }}">
        <button
            type="button"
            class="{{ $actionClasses }}"
            x-on:click="goUp()"
            :disabled="path.length === 0"
            :class="path.length === 0 && 'opacity-40 pointer-events-none'"
            :aria-label="@js(neura_trans('goUp'))"
        >
            <neura::icon name="arrow-left" class="{{ $sizes['glyph'] }}" />
        </button>

        <nav class="flex min-w-0 flex-1 items-center overflow-x-auto" aria-label="{{ neura_trans('path') }}">
            <ol class="flex items-center gap-0.5" role="list">
                <template x-for="(crumb, index) in visibleTrail" :key="crumb.id ?? 'root'">
                    <li class="flex shrink-0 items-center gap-0.5">
                        <neura::icon
                            name="chevron-right"
                            class="size-3.5 shrink-0 text-fg-disabled"
                            x-show="index > 0"
                        />
                        <button
                            type="button"
                            class="{{ $sizes['crumb'] }} truncate rounded-md px-1.5 py-1 font-medium transition-colors"
                            :class="index === visibleTrail.length - 1
                                ? 'text-fg'
                                : 'text-fg-muted hover:bg-hover hover:text-fg'"
                            :aria-current="index === visibleTrail.length - 1 ? 'page' : null"
                            :aria-label="crumb.ellipsis ? @js(neura_trans('goUp')) : null"
                            x-on:click="goTo(crumb.id)"
                            x-text="crumb.name"
                        ></button>
                    </li>
                </template>
            </ol>
        </nav>

        <div class="flex shrink-0 items-center gap-1">
            @if ($searchable)
                <div class="relative hidden items-center sm:flex">
                    <label class="relative flex items-center">
                        <span class="sr-only">{{ $searchPlaceholder ?? neura_trans('search') }}</span>
                        <neura::icon name="magnifying-glass" class="pointer-events-none absolute left-2 size-3.5 text-fg-muted" x-show="!searching" />
                        <span class="absolute left-2 size-3.5 animate-spin rounded-full border border-edge border-t-fg-muted" x-show="searching" x-cloak></span>
                        <input
                            type="search"
                            x-model="search"
                            placeholder="{{ $searchPlaceholder ?? neura_trans('search') }}"
                            class="h-8 w-36 rounded-lg border border-edge bg-surface pl-7 pr-7 {{ $sizes['meta'] }} text-fg outline-none transition-colors placeholder:text-fg-muted focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 lg:w-52"
                        />
                        <button
                            type="button"
                            class="absolute right-1.5 inline-flex size-5 items-center justify-center rounded text-fg-muted transition-colors hover:text-fg"
                            x-show="search.length > 0"
                            x-cloak
                            x-on:click="search = ''"
                            :aria-label="@js(neura_trans('clearSearch'))"
                        >
                            <neura::icon name="x-mark" class="size-3" />
                        </button>
                    </label>

                    @if ($scopeToggle)
                        {{-- Scope switch: this folder vs the whole tree. --}}
                        <div class="ms-1 flex items-center gap-0.5 rounded-lg bg-hover p-0.5" role="group"
                            aria-label="{{ neura_trans('searchScope') }}">
                            <button type="button" class="{{ $toggleClasses }}"
                                :class="searchScope === 'folder' && @js($colors['toggle']['active'])"
                                x-on:click="setScope('folder')"
                                :aria-pressed="(searchScope === 'folder').toString()"
                                :aria-label="@js(neura_trans('inThisFolder'))">
                                <neura::icon name="folder" class="{{ $sizes['glyph'] }}" />
                            </button>
                            <button type="button" class="{{ $toggleClasses }}"
                                :class="searchScope === 'all' && @js($colors['toggle']['active'])"
                                x-on:click="setScope('all')"
                                :aria-pressed="(searchScope === 'all').toString()"
                                :aria-label="@js(neura_trans('everywhere'))">
                                <neura::icon name="magnifying-glass" class="{{ $sizes['glyph'] }}" />
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Sort menu --}}
            <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false">
                <button
                    type="button"
                    class="{{ $actionClasses }}"
                    x-on:click="open = !open"
                    :aria-expanded="open.toString()"
                    :aria-label="@js(neura_trans('sortBy'))"
                >
                    <neura::icon name="bars-arrow-down" class="{{ $sizes['glyph'] }}" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity.duration.120ms
                    x-on:click.outside="open = false"
                    class="absolute end-0 z-30 mt-1 w-44 overflow-hidden rounded-xl border border-edge bg-surface p-1 shadow-lg"
                >
                    @foreach (['name' => 'name', 'size' => 'size', 'modified' => 'modified', 'kind' => 'type'] as $column => $labelKey)
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 rounded-lg px-2 py-1.5 {{ $sizes['meta'] }} text-fg-secondary transition-colors hover:bg-hover hover:text-fg"
                            x-on:click="sortBy('{{ $column }}'); open = false"
                        >
                            <span>{{ neura_trans($labelKey) }}</span>
                            {{-- Blade components escape their attributes, so @js() would be encoded twice here. --}}
                            <span class="flex size-3.5 shrink-0 items-center justify-center text-fg-muted">
                                <neura::icon name="bars-arrow-up" class="size-3.5" x-show="sortIcon('{{ $column }}') === 'asc'" />
                                <neura::icon name="bars-arrow-down" class="size-3.5" x-show="sortIcon('{{ $column }}') === 'desc'" />
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- View switch --}}
            <div class="flex items-center gap-0.5 rounded-lg bg-hover p-0.5" role="group" aria-label="{{ neura_trans('view') }}">
                <button
                    type="button"
                    class="{{ $toggleClasses }}"
                    :class="view === 'list' && @js($colors['toggle']['active'])"
                    x-on:click="setView('list')"
                    :aria-pressed="(view === 'list').toString()"
                    :aria-label="@js(neura_trans('listView'))"
                >
                    <neura::icon name="list-bullet" class="{{ $sizes['glyph'] }}" />
                </button>
                <button
                    type="button"
                    class="{{ $toggleClasses }}"
                    :class="view === 'grid' && @js($colors['toggle']['active'])"
                    x-on:click="setView('grid')"
                    :aria-pressed="(view === 'grid').toString()"
                    :aria-label="@js(neura_trans('gridView'))"
                >
                    <neura::icon name="squares-2x2" class="{{ $sizes['glyph'] }}" />
                </button>
            </div>

            @if ($details)
                <button
                    type="button"
                    class="{{ $actionClasses }}"
                    :class="detailsOpen && 'bg-active text-fg'"
                    x-on:click="detailsOpen = !detailsOpen"
                    :aria-pressed="detailsOpen.toString()"
                    :aria-label="@js(neura_trans('details'))"
                >
                    <neura::icon name="information-circle" class="{{ $sizes['glyph'] }}" />
                </button>
            @endif

            @isset($actions)
                {{ $actions }}
            @endisset

            @if ($creatable)
                <button
                    type="button"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 {{ $sizes['meta'] }} font-medium text-fg-secondary transition-colors hover:bg-hover hover:text-fg"
                    x-on:click="$dispatch('file-manager:new-folder', { path: [...path] })"
                >
                    <neura::icon name="folder-plus" class="{{ $sizes['glyph'] }}" />
                    <span class="hidden lg:inline">{{ neura_trans('newFolder') }}</span>
                </button>
            @endif

            @if ($uploadable)
                <button
                    type="button"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-primary-600 px-2.5 {{ $sizes['meta'] }} font-medium text-white transition-colors hover:bg-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
                    x-on:click="$dispatch('file-manager:upload-request', { path: [...path] })"
                >
                    <neura::icon name="arrow-up-tray" class="{{ $sizes['glyph'] }}" />
                    <span class="hidden lg:inline">{{ neura_trans('upload') }}</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Contextual selection bar ------------------------------------------- --}}
    @if ($selectable)
        <div
            x-show="selected.length > 0"
            x-cloak
            class="flex shrink-0 items-center justify-between gap-3 border-b px-2.5 py-2 {{ $colors['selection'] }}"
        >
            <p class="{{ $sizes['meta'] }} font-medium">
                <span x-text="selected.length"></span>
                {{ neura_trans('selected') }}
                <span class="font-normal opacity-70" x-show="selectionSize !== '0 B'">
                    <span aria-hidden="true">·</span> <span x-text="selectionSize"></span>
                </span>
            </p>

            <div class="flex items-center gap-1">
                @if ($downloadable)
                    <button type="button" class="{{ $actionClasses }}" x-on:click="action('download')"
                        :aria-label="@js(neura_trans('download'))">
                        <neura::icon name="arrow-down-tray" class="{{ $sizes['glyph'] }}" />
                    </button>
                @endif

                @if ($deletable)
                    <button type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg text-danger-500 transition-colors hover:bg-danger-100 hover:text-danger-600 dark:hover:bg-danger-900/40 {{ $sizes['action'] }}"
                        x-on:click="action('delete')" :aria-label="@js(neura_trans('delete'))">
                        <neura::icon name="trash" class="{{ $sizes['glyph'] }}" />
                    </button>
                @endif

                <button type="button" class="{{ $actionClasses }}" x-on:click="clearSelection()"
                    :aria-label="@js(neura_trans('clearSelection'))">
                    <neura::icon name="x-mark" class="{{ $sizes['glyph'] }}" />
                </button>
            </div>
        </div>
    @endif

    {{-- Body ---------------------------------------------------------------- --}}
    <div class="flex min-h-0 flex-1">
        <div
            {{-- Container query: the metadata columns collapse when the details
                 panel narrows the list, not when the viewport does. --}}
            {{-- The pack min-height is a floor for auto-height usage only: with an
                 explicit `height` it would stop the body shrinking and push the
                 status bar over the last row. --}}
            class="@container/list min-w-0 flex-1 overflow-y-auto outline-none {{ $height ? '' : $sizes['body'] }}"
            tabindex="0"
            role="listbox"
            :aria-multiselectable="@js($multiple)"
            aria-label="{{ neura_trans('files') }}"
            x-on:keydown="onKeydown($event)"
            x-on:click.self="clearSelection()"
        >
            {{-- Loading placeholder, sized like real rows to avoid a layout jump --}}
            <ul x-show="loading" x-cloak class="animate-pulse" role="presentation" aria-hidden="true">
                @for ($i = 0; $i < 6; $i++)
                    <li class="flex items-center border-b border-edge {{ $sizes['row'] }}">
                        <span class="{{ $sizes['icon'] }} shrink-0 rounded bg-hover"></span>
                        <span class="h-3 flex-1 rounded bg-hover" style="max-width: {{ [70, 45, 60, 38, 55, 48][$i] }}%"></span>
                        <span class="hidden h-3 w-16 shrink-0 rounded bg-hover @[30rem]/list:block"></span>
                        <span class="hidden h-3 w-28 shrink-0 rounded bg-hover @[44rem]/list:block"></span>
                    </li>
                @endfor
            </ul>

            {{-- List view --}}
            <div x-show="view === 'list' && !loading">
                <div
                    class="sticky top-0 z-10 flex items-center border-b {{ $sizes['row'] }} {{ $colors['head'] }} {{ $sizes['meta'] }} font-medium text-fg-muted"
                >
                    @if ($multiple)
                        <span class="flex w-6 shrink-0 items-center">
                            <input
                                type="checkbox"
                                class="size-3.5 rounded border-edge accent-primary-600 focus:ring-primary-500/30"
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                x-on:change="toggleAll()"
                                aria-label="{{ neura_trans('selectAll') }}"
                            />
                        </span>
                    @endif

                    <button type="button" class="flex min-w-0 flex-1 items-center gap-1 text-start transition-colors hover:text-fg"
                        x-on:click="sortBy('name')">
                        {{ neura_trans('name') }}
                        <neura::icon name="bars-arrow-up" class="size-3" x-show="sortIcon('name') === 'asc'" />
                        <neura::icon name="bars-arrow-down" class="size-3" x-show="sortIcon('name') === 'desc'" />
                    </button>

                    <button type="button" class="hidden w-24 shrink-0 items-center gap-1 text-start transition-colors hover:text-fg @[30rem]/list:flex"
                        x-on:click="sortBy('size')">
                        {{ neura_trans('size') }}
                        <neura::icon name="bars-arrow-up" class="size-3" x-show="sortIcon('size') === 'asc'" />
                        <neura::icon name="bars-arrow-down" class="size-3" x-show="sortIcon('size') === 'desc'" />
                    </button>

                    <button type="button" class="hidden w-40 shrink-0 items-center gap-1 text-start transition-colors hover:text-fg @[44rem]/list:flex"
                        x-on:click="sortBy('modified')">
                        {{ neura_trans('modified') }}
                        <neura::icon name="bars-arrow-up" class="size-3" x-show="sortIcon('modified') === 'asc'" />
                        <neura::icon name="bars-arrow-down" class="size-3" x-show="sortIcon('modified') === 'desc'" />
                    </button>

                    <span class="w-7 shrink-0"></span>
                </div>

                <ul role="list">
                    <template x-for="entry in entries" :key="entry.id">
                        <neura::file-manager.row
                            :sizes="$entryProps['sizes']"
                            :colors="$entryProps['colors']"
                            :sprite="$entryProps['sprite']"
                            :selectable="$entryProps['selectable']"
                            :multiple="$multiple"
                            :downloadable="$entryProps['downloadable']"
                            :deletable="$entryProps['deletable']"
                        />
                    </template>
                </ul>
            </div>

            {{-- Grid view --}}
            <div x-show="view === 'grid' && !loading" x-cloak class="p-3">
                <ul class="grid {{ $sizes['grid'] }}" role="list" x-ref="grid">
                    <template x-for="entry in entries" :key="entry.id">
                        <neura::file-manager.tile
                            :sizes="$entryProps['sizes']"
                            :colors="$entryProps['colors']"
                            :sprite="$entryProps['sprite']"
                            :selectable="$entryProps['selectable']"
                        />
                    </template>
                </ul>
            </div>

            {{-- Empty state --}}
            <div x-show="isEmpty && !loading" x-cloak class="flex flex-col items-center justify-center gap-2 px-6 py-16 text-center">
                @isset($empty)
                    {{ $empty }}
                @else
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-hover text-fg-muted ring-1 ring-edge">
                        <neura::icon name="folder-open" class="size-6" />
                    </span>
                    <p class="{{ $sizes['label'] }} font-medium text-fg" x-text="isFiltered
                        ? @js(neura_trans('noResultsFound'))
                        : @js(neura_trans('emptyFolder'))"></p>
                    <p class="{{ $sizes['meta'] }} text-fg-muted" x-text="isFiltered
                        ? @js(neura_trans('tryDifferentSearch'))
                        : @js(neura_trans('dropFilesHere'))"></p>
                @endisset
            </div>
        </div>

        @if ($details)
            <neura::file-manager.details
                :sizes="$sizes"
                :colors="$colors"
                :sprite="$uid"
                :downloadable="$downloadable"
            />
        @endif
    </div>

    {{-- Status bar ---------------------------------------------------------- --}}
    <div class="flex shrink-0 items-center justify-between gap-3 border-t px-2.5 py-1.5 {{ $sizes['meta'] }} {{ $colors['status'] }}">
        {{-- No counts while loading: "0 files" would be a lie, not a state. --}}
        <span x-text="loading ? @js(neura_trans('loading')) : summary"></span>
        <span class="hidden sm:inline" x-show="isFiltered && !loading" x-cloak>
            {{ neura_trans('filtered') }}
        </span>
    </div>

    {{-- Drop overlay -------------------------------------------------------- --}}
    <div
        x-show="dropping"
        x-cloak
        x-transition.opacity.duration.120ms
        class="pointer-events-none absolute inset-0 z-40 flex flex-col items-center justify-center gap-2
            bg-primary-50/85 text-center backdrop-blur-[2px] dark:bg-primary-950/80"
    >
        <span class="flex size-12 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 ring-1 ring-primary-300 dark:bg-primary-900/70 dark:text-primary-300 dark:ring-primary-700">
            <neura::icon name="arrow-up-tray" class="size-6" />
        </span>
        <p class="{{ $sizes['label'] }} font-semibold text-primary-700 dark:text-primary-200">
            {{ neura_trans('dropToUpload') }}
        </p>
        <p class="{{ $sizes['meta'] }} text-primary-600/80 dark:text-primary-300/80">
            <span x-text="trail[trail.length - 1]?.name"></span>
        </p>
    </div>

    {{-- Context menu -------------------------------------------------------- --}}
    <div
        data-slot="file-manager-menu"
        x-show="menu.open"
        x-cloak
        {{-- Laid out but hidden until measured, so it never paints at the wrong spot. --}}
        :class="menu.ready ? 'opacity-100' : 'invisible opacity-0'"
        class="absolute z-50 w-52 overflow-hidden rounded-xl border border-edge bg-surface p-1 shadow-lg transition-opacity duration-100"
        :style="`left: ${menu.x}px; top: ${menu.y}px`"
        x-on:click.stop
        role="menu"
    >
        <template x-if="menuEntry">
            <div>
                <button type="button" role="menuitem" class="{{ $menuItemClasses }}" x-on:click="runMenu('open')">
                    <neura::icon name="folder-open" class="size-4" x-show="menuEntry.isFolder" />
                    <neura::icon name="eye" class="size-4" x-show="!menuEntry.isFolder" />
                    <span>{{ neura_trans('open') }}</span>
                </button>

                @if ($downloadable)
                    <button type="button" role="menuitem" class="{{ $menuItemClasses }}"
                        x-show="!menuEntry.isFolder" x-on:click="runMenu('download')">
                        <neura::icon name="arrow-down-tray" class="size-4" />
                        <span>{{ neura_trans('download') }}</span>
                    </button>
                @endif

                @if ($renamable)
                    <button type="button" role="menuitem" class="{{ $menuItemClasses }}" x-on:click="runMenu('rename')">
                        <neura::icon name="pencil-square" class="size-4" />
                        <span>{{ neura_trans('rename') }}</span>
                    </button>
                @endif

                @if ($deletable)
                    <div class="my-1 h-px bg-edge/70"></div>
                    <button type="button" role="menuitem" class="{{ $menuDangerClasses }}" x-on:click="runMenu('delete')">
                        <neura::icon name="trash" class="size-4" />
                        <span>{{ neura_trans('delete') }}</span>
                        <span class="ms-auto {{ $sizes['meta'] }} opacity-60" x-show="selected.length > 1"
                            x-text="selected.length"></span>
                    </button>
                @endif
            </div>
        </template>

        <template x-if="!menuEntry">
            <div>
                @if ($creatable)
                    <button type="button" role="menuitem" class="{{ $menuItemClasses }}"
                        x-on:click="closeMenu(); $dispatch('file-manager:new-folder', { path: [...path] })">
                        <neura::icon name="folder-plus" class="size-4" />
                        <span>{{ neura_trans('newFolder') }}</span>
                    </button>
                @endif

                @if ($uploadable)
                    <button type="button" role="menuitem" class="{{ $menuItemClasses }}"
                        x-on:click="closeMenu(); $dispatch('file-manager:upload-request', { path: [...path] })">
                        <neura::icon name="arrow-up-tray" class="size-4" />
                        <span>{{ neura_trans('upload') }}</span>
                    </button>
                @endif

                @if ($multiple)
                    <button type="button" role="menuitem" class="{{ $menuItemClasses }}"
                        x-on:click="closeMenu(); selected = entries.map((entry) => entry.id); emitSelection()">
                        <neura::icon name="check-circle" class="size-4" />
                        <span>{{ neura_trans('selectAll') }}</span>
                    </button>
                @endif
            </div>
        </template>
    </div>

    <span class="sr-only" aria-live="polite" x-text="announcement"></span>
</div>
