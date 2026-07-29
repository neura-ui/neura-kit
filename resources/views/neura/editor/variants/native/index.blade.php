@props([
    'name' => null,
    'value' => null,
    'mode' => 'html',
    'placeholder' => 'Start writing…',
    'disabled' => false,
    'debounce' => 300,
    'uploadUrl' => null,
    'uploadField' => 'image',
    'pageSize' => null,
    'orientation' => null,
    'zoom' => 100,
    'paginated' => true,
    'menubar' => true,
    'toolbar' => true,
    'ruler' => true,
    'statusbar' => true,
    'documentTitle' => 'Untitled document',
    'header' => null,
    'footer' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $wireModel = $attributes->wire('model');
    $entangle = $wireModel->value();
    $initialValue = $mode === 'json' && is_array($value) ? json_encode($value) : $value;

    $pageSize = $pageSize ?? config('neura-kit.editor.page_size', 'a4');
    $orientation = $orientation ?? config('neura-kit.editor.orientation', 'portrait');
    // Relative on purpose. The upload route carries no domain constraint, so an
    // absolute route() URL is built from APP_URL — which is the wrong host on
    // any other domain the app serves (docs.*, api.*). The POST then lands
    // cross-origin and comes back as an HTML error page instead of JSON.
    $uploadUrl = $uploadUrl ?? (\Illuminate\Support\Facades\Route::has('neura-kit.editor.upload-image')
        ? route('neura-kit.editor.upload-image', absolute: false)
        : null);

    // Conversion is only offered when Paperdoc is actually installed.
    $converter = app(\Neura\Kit\Services\Editor\PaperdocConverter::class);
    $canConvert = $converter->isAvailable();
    $exportUrl = $canConvert && \Illuminate\Support\Facades\Route::has('neura-kit.editor.export')
        ? route('neura-kit.editor.export', absolute: false)
        : null;
    $importUrl = $canConvert && \Illuminate\Support\Facades\Route::has('neura-kit.editor.import')
        ? route('neura-kit.editor.import', absolute: false)
        : null;

    $editorRoundedClass = PackResolver::rounded(neura_config('rich-editor', 'rounded'));
    $editorShadowClass = PackResolver::shadow(neura_config('rich-editor', 'shadow'));

    $config = [
        'placeholder' => $placeholder,
        'editable' => ! $disabled,
        'mode' => $mode,
        'debounce' => (int) $debounce,
        'pageSize' => $pageSize,
        'orientation' => $orientation,
        'zoom' => (int) $zoom,
        'paginated' => (bool) $paginated,
        'uploadUrl' => $uploadUrl,
        'uploadField' => $uploadField,
        'documentTitle' => $documentTitle,
        'header' => $header,
        'footer' => $footer,
        'exportUrl' => $exportUrl,
        'importUrl' => $importUrl,
        'exportFormats' => $canConvert ? $converter->exportFormats() : [],
        'importFormats' => $canConvert ? $converter->importFormats() : [],
    ];
@endphp

<div
    data-nk-editor
    data-nk-variant="native"
    @if($entangle)
        x-data="nativeEditor({ state: @entangle($entangle).live, ...@js($config) })"
    @else
        x-data="nativeEditor({ state: @js($initialValue), ...@js($config) })"
    @endif
    x-init="init()"
    x-on:keydown.escape="linkOpen = false; showPageSetup = false; showTableDialog = false; tableMenuOpen = false; exitHeaderFooter()"
    {{ $attributes->whereDoesntStartWith(['wire:model', 'class']) }}
    @class([
        'nk-native relative w-full border border-edge bg-surface overflow-hidden',
        $editorRoundedClass,
        $editorShadowClass,
    ])
    wire:ignore
>
    @if($menubar || $toolbar)
        <div class="nk-native-chrome shrink-0">
            @if($menubar)
                <neura::editor.variants.native.menubar :documentTitle="$documentTitle" />
            @endif

            @if($toolbar && ! $disabled)
                <neura::editor.variants.native.toolbar />
            @endif
        </div>
    @endif

    <div class="nk-native-canvas" style="max-height: 78vh;">
        @if($ruler)
            <neura::editor.variants.native.ruler />
        @endif

        {{--
            One contenteditable region wrapping every sheet. Keeping the pages
            inside a single editable root is what lets the caret walk from the
            bottom of one page to the top of the next without any interception.
        --}}
        <div
            class="nk-pages"
            x-ref="pages"
            @if(! $disabled) contenteditable="true" @endif
            role="textbox"
            aria-multiline="true"
            aria-label="{{ $documentTitle }}"
            spellcheck="true"
            x-bind:style="`--nk-zoom: ${zoom / 100}`"
        >
            <div class="nk-page" data-nk-page="1">
                <div class="nk-page-header" contenteditable="false" data-nk-header
                     x-on:dblclick="editHeaderFooter('header')"></div>

                <div
                    class="nk-page-body"
                    data-nk-content
                    data-nk-placeholder="{{ $placeholder }}"
                ></div>
                <div class="nk-page-footer" contenteditable="false" data-nk-footer
                     x-on:dblclick="editHeaderFooter('footer')"></div>
            </div>
        </div>
    </div>

    {{-- Cloned by the flow engine when a new sheet is needed. Kept outside the
         editable region so the caret can never land in it. --}}
    <div class="nk-page" data-nk-page-template hidden aria-hidden="true">
        <div class="nk-page-header" contenteditable="false" data-nk-header></div>
        <div class="nk-page-body" data-nk-content></div>
        <div class="nk-page-footer" contenteditable="false" data-nk-footer></div>
    </div>

    @if($statusbar)
        <div class="nk-native-statusbar">
            <div class="flex items-center gap-2">
                <span class="nk-status-chip">
                    Page <span class="font-medium text-fg" x-text="activePage"></span>
                    <span class="opacity-50">/</span>
                    <span x-text="pageCount"></span>
                </span>
                <span class="nk-status-chip"><span class="font-medium text-fg" x-text="words"></span> words</span>
                <span class="nk-status-chip"><span class="font-medium text-fg" x-text="characters"></span> chars</span>
            </div>

            <div class="flex items-center gap-2">
                <span x-show="busy" x-cloak class="nk-status-chip">Converting…</span>
                <span
                    x-show="conversionError"
                    x-cloak
                    class="max-w-xs truncate rounded-md bg-red-500/10 px-2 py-0.5 text-red-600 dark:text-red-400"
                    x-text="conversionError"
                    x-on:click="conversionError = ''"
                    title="Click to dismiss"
                ></span>
                <button
                    type="button"
                    class="nk-status-chip hover:text-fg"
                    x-on:click="togglePagination()"
                    x-text="paginated ? 'Paged' : 'Continuous'"
                    title="Toggle pagination"
                ></button>
                <select
                    class="nk-status-select"
                    x-model.number="zoom"
                    aria-label="Zoom"
                >
                    @foreach([50, 75, 90, 100, 125, 150, 200] as $level)
                        <option value="{{ $level }}">{{ $level }}%</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    {{-- Insert table: choose the size before inserting --------------------- --}}
    <div
        x-show="showTableDialog"
        x-cloak
        x-transition.opacity
        class="nk-editor-backdrop"
        x-on:click.self="showTableDialog = false"
    >
        <div class="nk-editor-dialog">
            <h3>Insert table</h3>

            <div class="mb-3 grid grid-cols-2 gap-3">
                <label class="block">
                    <span class="mb-1 block text-xs text-fg-muted">Rows</span>
                    <input
                        type="number" min="1" max="50" step="1"
                        class="nk-editor-field"
                        x-model.number="tableDialogRows"
                        x-on:keydown.enter.prevent="confirmTableDialog()"
                    >
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-fg-muted">Columns</span>
                    <input
                        type="number" min="1" max="20" step="1"
                        class="nk-editor-field"
                        x-model.number="tableDialogCols"
                        x-on:keydown.enter.prevent="confirmTableDialog()"
                    >
                </label>
            </div>

            {{-- Same numbers, pickable by hovering the grid. --}}
            <p class="mb-1.5 text-xs text-fg-muted">Or pick a size</p>
            <div
                class="mb-4 grid grid-cols-10 gap-0.5 rounded-md border border-edge/70 p-1.5"
                style="background: color-mix(in srgb, var(--nk-canvas) 55%, transparent)"
                x-on:mouseleave="setGrid(0, 0)"
            >
                @for($row = 1; $row <= 8; $row++)
                    @for($col = 1; $col <= 10; $col++)
                        <button
                            type="button"
                            class="size-4 rounded-[2px] border border-edge bg-surface transition-colors duration-75 hover:border-primary/60 hover:bg-primary/25"
                            :class="{
                                'bg-primary! border-primary! ring-1 ring-primary/50': gridRows >= {{ $row }} && gridCols >= {{ $col }},
                                'bg-primary/45! border-primary/70!': gridRows > 0 && ({{ $row }} === gridRows || {{ $col }} === gridCols) && (gridRows < {{ $row }} || gridCols < {{ $col }}),
                            }"
                            x-on:mouseenter="setGrid({{ $row }}, {{ $col }}); tableDialogRows = {{ $row }}; tableDialogCols = {{ $col }}"
                            x-on:click="tableDialogRows = {{ $row }}; tableDialogCols = {{ $col }}; confirmTableDialog(); setGrid(0, 0)"
                            aria-label="{{ $row }} by {{ $col }}"
                        ></button>
                    @endfor
                @endfor
            </div>

            <div class="flex items-center justify-between gap-2">
                <span class="text-xs tabular-nums text-fg-muted">
                    <span class="font-medium text-fg" x-text="tableDialogRows"></span>
                    ×
                    <span class="font-medium text-fg" x-text="tableDialogCols"></span>
                </span>
                <div class="flex gap-2">
                    <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="showTableDialog = false">Cancel</button>
                    <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="confirmTableDialog()">Insert</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table context menu (right-click inside a table) ------------------- --}}
    <div
        x-show="tableMenuOpen"
        x-cloak
        x-transition.opacity
        class="nk-editor-menu absolute z-40 w-52"
        x-bind:style="`left: ${tableMenuX}px; top: ${tableMenuY}px`"
        x-on:click.outside="tableMenuOpen = false"
    >
        <p class="px-2.5 py-1.5 text-[11px] text-fg-muted">
            <template x-if="tableInfo()">
                <span>
                    Row <span x-text="tableInfo().rowIndex + 1"></span> of <span x-text="tableInfo().rows"></span>,
                    column <span x-text="tableInfo().cellIndex + 1"></span> of <span x-text="tableInfo().columns"></span>
                </span>
            </template>
        </p>

        @php
            $menuActions = [
                ['Insert row above', "insertRow('above')"],
                ['Insert row below', "insertRow('below')"],
                ['Insert column left', "insertColumn('left')"],
                ['Insert column right', "insertColumn('right')"],
                ['Toggle header row', 'toggleHeaderRow()'],
            ];
            $menuDestructive = [
                ['Delete row', 'deleteRow()'],
                ['Delete column', 'deleteColumn()'],
                ['Delete table', 'deleteTable()'],
            ];
        @endphp

        @foreach($menuActions as [$label, $action])
            <button
                type="button"
                class="nk-editor-menu-item"
                x-on:click="runTableAction(() => {{ $action }})"
            >{{ $label }}</button>
        @endforeach

        <div class="my-1 border-t border-edge"></div>

        @foreach($menuDestructive as [$label, $action])
            <button
                type="button"
                class="nk-editor-menu-item is-danger"
                x-on:click="runTableAction(() => {{ $action }})"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- Link editor ------------------------------------------------------ --}}
    <div
        x-show="linkOpen"
        x-cloak
        x-transition.opacity
        class="absolute inset-x-0 bottom-12 z-30 mx-auto w-full max-w-md"
        x-on:click.outside="linkOpen = false"
    >
        <div class="nk-editor-dialog mx-auto w-full max-w-md">
            <label class="mb-1.5 block text-xs font-medium text-fg-muted" for="{{ $name ?? 'nk' }}-link">
                Link
            </label>
            <div class="flex items-center gap-2">
                <input
                    id="{{ $name ?? 'nk' }}-link"
                    type="url"
                    class="nk-editor-field min-w-0 flex-1"
                    placeholder="https://example.com"
                    x-model="linkUrl"
                    x-on:keydown.enter.prevent="applyLink()"
                >
                <button
                    type="button"
                    class="nk-editor-btn nk-editor-btn-primary"
                    x-on:click="applyLink()"
                >Apply</button>
                <button
                    type="button"
                    class="nk-editor-btn nk-editor-btn-ghost"
                    x-on:click="removeLink()"
                >Remove</button>
            </div>
        </div>
    </div>

    {{-- Page setup ------------------------------------------------------- --}}
    <div
        x-show="showPageSetup"
        x-cloak
        x-transition.opacity
        class="nk-editor-backdrop"
        x-on:click.self="showPageSetup = false"
    >
        <div class="nk-editor-dialog" style="width: min(100% - 2rem, 22rem)">
            <h3>Page setup</h3>

            <label class="mb-1 block text-xs text-fg-muted">Paper size</label>
            <select class="nk-editor-field mb-3" x-model="pageSize">
                <option value="a4">A4 (210 × 297 mm)</option>
                <option value="letter">Letter (8.5 × 11 in)</option>
                <option value="legal">Legal (8.5 × 14 in)</option>
            </select>

            <label class="mb-1 block text-xs text-fg-muted">Orientation</label>
            <select class="nk-editor-field mb-3" x-model="orientation">
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
            </select>

            <label class="mb-1 block text-xs text-fg-muted">Margins (inches)</label>
            <div class="mb-4 grid grid-cols-2 gap-2">
                @foreach(['top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right'] as $side => $label)
                    <label class="flex items-center gap-1.5 text-xs">
                        <span class="w-12 text-fg-muted">{{ $label }}</span>
                        <input
                            type="number" min="0" max="4" step="0.25"
                            class="nk-editor-field"
                            :value="marginInches('{{ $side }}')"
                            x-on:input="setMargin('{{ $side }}', parseFloat($event.target.value) || 0)"
                        >
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="showPageSetup = false">Cancel</button>
                <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="applyPageSetup()">Apply</button>
            </div>
        </div>
    </div>

    @if($name)
        <input
            type="hidden"
            name="{{ $name }}"
            x-bind:value="mode === 'json' ? JSON.stringify(state) : state"
        >
    @endif
</div>
