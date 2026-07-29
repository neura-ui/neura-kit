@props(['documentTitle' => 'Untitled document'])

{{--
    Document title and the File / Edit / View / Insert / Format menus.

    Every item maps onto a command the Alpine component already exposes, so the
    menu is a second route to the toolbar rather than a parallel implementation.

    An item may carry `children`, which renders as a nested submenu opening to
    the side — that is how the export formats sit under a single "Download".
--}}

@php
    $exportFormats = collect(config('neura-kit.editor.export_formats', []))
        ->map(fn ($format) => [
            'label' => strtoupper($format),
            'action' => "exportAs('{$format}')",
        ])
        ->all();

    $menus = [
        'File' => [
            ['label' => 'Open…', 'action' => 'importDocument()', 'requires' => 'import'],
            [
                'label' => 'Download',
                'requires' => 'export',
                'children' => $exportFormats,
            ],
            ['label' => 'Rename…', 'action' => '$refs.titleInput.select(); $refs.titleInput.focus()'],
            ['label' => 'Page setup…', 'action' => 'showPageSetup = true'],
            ['label' => 'Print', 'action' => 'print()', 'hint' => 'Ctrl+P'],
        ],
        'Edit' => [
            ['label' => 'Undo', 'action' => 'undo()', 'hint' => 'Ctrl+Z'],
            ['label' => 'Redo', 'action' => 'redo()', 'hint' => 'Ctrl+Y'],
            ['label' => 'Clear formatting', 'action' => 'clearFormatting()', 'hint' => 'Ctrl+\\'],
        ],
        'View' => [
            ['label' => 'Show outline', 'action' => 'showOutline = !showOutline'],
            ['label' => 'Toggle pagination', 'action' => 'togglePagination()'],
            ['label' => 'Zoom to 100%', 'action' => 'setZoom(100)'],
        ],
        'Insert' => [
            ['label' => 'Image…', 'action' => 'pickImage()'],
            ['label' => 'Table…', 'action' => 'openTableDialog()'],
            ['label' => 'Link…', 'action' => 'openLinkDialog()', 'hint' => 'Ctrl+K'],
            ['label' => 'Horizontal rule', 'action' => 'insertRule()'],
            ['label' => 'Page break', 'action' => 'insertPageBreak()', 'hint' => 'Ctrl+Enter'],
            ['label' => 'Header', 'action' => "editHeaderFooter('header')"],
            ['label' => 'Footer', 'action' => "editHeaderFooter('footer')"],
            [
                'label' => 'Field',
                'children' => [
                    ['label' => 'Page number', 'action' => "insertField('page')"],
                    ['label' => 'Page count', 'action' => "insertField('pages')"],
                    ['label' => 'Document title', 'action' => "insertField('title')"],
                    ['label' => "Today's date", 'action' => "insertField('date')"],
                ],
            ],
        ],
        'Format' => [
            ['label' => 'Bold', 'action' => "toggle('bold')", 'hint' => 'Ctrl+B'],
            ['label' => 'Italic', 'action' => "toggle('italic')", 'hint' => 'Ctrl+I'],
            ['label' => 'Underline', 'action' => "toggle('underline')", 'hint' => 'Ctrl+U'],
            ['label' => 'Superscript', 'action' => "toggle('superscript')"],
            ['label' => 'Subscript', 'action' => "toggle('subscript')"],
            ['label' => 'Quote', 'action' => "setStyle('blockquote')"],
            ['label' => 'Code block', 'action' => "setStyle('codeblock')"],
        ],
    ];

    $itemClasses = 'nk-editor-menu-item disabled:opacity-40';
@endphp

<div class="nk-native-menubar">
    {{--
        The title is a real input so it can be renamed in place, sized to its
        content rather than a fixed width so it reads as text until focused.
    --}}
    <input
        type="text"
        x-ref="titleInput"
        x-model="documentTitle"
        x-on:change="$dispatch('title-change', documentTitle)"
        x-on:keydown.enter.prevent="$event.target.blur()"
        x-bind:size="Math.max(8, Math.min(40, (documentTitle || '').length + 1))"
        class="min-w-0 shrink truncate rounded-md border border-transparent bg-transparent px-2 py-1 text-sm font-semibold tracking-tight text-fg outline-none hover:border-edge hover:bg-surface/60 focus:border-primary focus:bg-surface"
        aria-label="Document name"
        placeholder="Untitled document"
    >

    <nav class="flex items-center gap-0.5 text-xs" aria-label="Menu">
        @foreach($menus as $label => $items)
            <div class="relative" x-data="{ open: false, sub: null }" x-on:click.outside="open = false; sub = null">
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1 text-fg-muted transition-colors hover:bg-hover hover:text-fg"
                    x-on:click="open = !open; sub = null"
                    :aria-expanded="open"
                >{{ $label }}</button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity
                    class="nk-editor-menu absolute left-0 top-full z-40 mt-1.5 w-56"
                >
                    @foreach($items as $index => $item)
                        @if(isset($item['children']))
                            {{-- Parent row: hovering or clicking reveals the submenu beside it. --}}
                            <div
                                class="relative"
                                x-on:mouseenter="sub = {{ $index }}"
                                x-on:mouseleave="sub === {{ $index }} && (sub = null)"
                                @isset($item['requires'])
                                    x-show="{{ $item['requires'] === 'import' ? 'importAvailable' : 'exportAvailable' }}"
                                @endisset
                            >
                                <button
                                    type="button"
                                    class="{{ $itemClasses }}"
                                    x-on:click="sub = sub === {{ $index }} ? null : {{ $index }}"
                                    :disabled="busy"
                                    :aria-expanded="sub === {{ $index }}"
                                >
                                    <span>{{ $item['label'] }}</span>
                                    <svg viewBox="0 0 24 24" class="size-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </button>

                                <div
                                    x-show="sub === {{ $index }}"
                                    x-cloak
                                    class="nk-editor-menu absolute left-full top-0 z-50 -mt-1 ml-1 w-40"
                                >
                                    @foreach($item['children'] as $child)
                                        <button
                                            type="button"
                                            class="{{ $itemClasses }}"
                                            x-on:click="{{ $child['action'] }}; open = false; sub = null"
                                            :disabled="busy"
                                        >
                                            <span>{{ $child['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <button
                                type="button"
                                class="{{ $itemClasses }}"
                                x-on:click="{{ $item['action'] }}; open = false; sub = null"
                                @isset($item['requires'])
                                    {{-- Conversion entries only work when Paperdoc is installed. --}}
                                    x-show="{{ $item['requires'] === 'import' ? 'importAvailable' : 'exportAvailable' }}"
                                    :disabled="busy"
                                @endisset
                            >
                                <span>{{ $item['label'] }}</span>
                                @isset($item['hint'])
                                    <span class="text-[11px] text-fg-muted">{{ $item['hint'] }}</span>
                                @endisset
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- Visible way out of header/footer editing, next to the menus. --}}
    <button
        type="button"
        x-show="hfMode"
        x-cloak
        class="ml-auto shrink-0 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-white shadow-sm"
        x-on:click="exitHeaderFooter()"
        x-text="hfMode === 'header' ? 'Close header' : 'Close footer'"
    ></button>

    {{-- Outline panel, toggled from the View menu. --}}
    <div
        x-show="showOutline"
        x-cloak
        class="nk-editor-menu absolute left-2 top-24 z-30 max-h-80 w-56 overflow-y-auto p-2"
    >
        <div class="mb-1.5 flex items-center justify-between px-1">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-fg-muted">Outline</span>
            <button type="button" class="rounded px-1 text-xs text-fg-muted hover:bg-hover hover:text-fg" x-on:click="showOutline = false">×</button>
        </div>

        <template x-if="outline.length === 0">
            <p class="px-1 py-2 text-xs text-fg-muted">Headings you add will appear here.</p>
        </template>

        <template x-for="heading in outline" :key="heading.id">
            <button
                type="button"
                class="block w-full truncate rounded-md px-1.5 py-1 text-left text-xs text-fg hover:bg-hover"
                :style="`padding-left: ${(heading.level - 1) * 0.75 + 0.25}rem`"
                x-on:click="scrollToHeading(heading.id)"
                x-text="heading.text"
            ></button>
        </template>
    </div>
</div>
