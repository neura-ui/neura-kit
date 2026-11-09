@props(['documentTitle' => null])

{{--
    Document title and the File / Edit / View / Insert / Format / Tools menus.

    Every item maps onto a command the Alpine component already exposes, so the
    menu is a second route to the toolbar rather than a parallel implementation.

    An item may carry `children`, which renders as a nested submenu opening to
    the side — that is how the export formats sit under a single "Download".
--}}

@php
    $t = fn (string $key, string $fallback) => neura_trans('editor'.$key, null, $fallback);

    $exportFormats = collect(config('neura-kit.editor.export_formats', []))
        ->map(fn ($format) => [
            'label' => strtoupper($format),
            'action' => "exportAs('{$format}')",
        ])
        ->all();

    $menus = [
        $t('MenuFile', 'File') => [
            ['label' => $t('Open', 'Open…'), 'action' => 'importDocument()', 'requires' => 'import'],
            [
                'label' => $t('Download', 'Download'),
                'requires' => 'export',
                'children' => $exportFormats,
            ],
            ['label' => $t('Rename', 'Rename…'), 'action' => '$refs.titleInput.select(); $refs.titleInput.focus()'],
            [
                'label' => $t('VersionHistory', 'Version history'),
                'children' => [
                    ['label' => $t('SeeVersionHistory', 'See version history'), 'action' => 'openHistory()'],
                    ['label' => $t('NameCurrentVersion', 'Name current version'), 'action' => 'nameCurrentVersion()'],
                ],
            ],
            ['label' => $t('PageSetup', 'Page setup…'), 'action' => 'showPageSetup = true'],
            ['label' => $t('Print', 'Print'), 'action' => 'print()', 'hint' => 'Ctrl+P'],
        ],
        $t('MenuEdit', 'Edit') => [
            ['label' => $t('Undo', 'Undo'), 'action' => 'undo()', 'hint' => 'Ctrl+Z'],
            ['label' => $t('Redo', 'Redo'), 'action' => 'redo()', 'hint' => 'Ctrl+Y'],
            ['label' => $t('FindReplace', 'Find and replace'), 'action' => 'openFind(true)', 'hint' => 'Ctrl+H'],
            ['label' => $t('ClearFormatting', 'Clear formatting'), 'action' => 'clearFormatting()', 'hint' => 'Ctrl+\\'],
        ],
        $t('MenuView', 'View') => [
            ['label' => $t('ShowOutline', 'Show outline'), 'action' => 'showOutline = !showOutline'],
            ['label' => $t('ReviewPanel', 'Comments and suggestions'), 'action' => 'toggleReview()'],
            ['label' => $t('TogglePagination', 'Pages or continuous'), 'action' => 'togglePagination()'],
            ['label' => $t('FitWidth', 'Fit to width'), 'action' => "setZoom('fit')"],
            ['label' => $t('Zoom100', 'Zoom to 100%'), 'action' => 'setZoom(100)'],
        ],
        $t('MenuInsert', 'Insert') => [
            ['label' => $t('Image', 'Image…'), 'action' => 'pickImage()'],
            ['label' => $t('Table', 'Table…'), 'action' => 'openTableDialog()'],
            ['label' => $t('Link', 'Link…'), 'action' => 'openLinkDialog()', 'hint' => 'Ctrl+K'],
            ['label' => $t('Checklist', 'Checklist'), 'action' => 'toggleChecklist()', 'hint' => 'Ctrl+Shift+9'],
            ['label' => $t('HorizontalRule', 'Horizontal rule'), 'action' => 'insertRule()'],
            [
                'label' => $t('Break', 'Break'),
                'children' => [
                    ['label' => $t('PageBreak', 'Page break').' · Ctrl+Enter', 'action' => "insertBreak('page')"],
                    ['label' => $t('LineBreak', 'Line break').' · Shift+Enter', 'action' => "insertBreak('line')"],
                    ['label' => $t('BreakOdd', 'Next odd page'), 'action' => "insertBreak('odd')"],
                    ['label' => $t('BreakEven', 'Next even page'), 'action' => "insertBreak('even')"],
                    ['label' => $t('BreakColumn', 'Column break'), 'action' => "insertBreak('column')", 'when' => 'columns > 1'],
                ],
            ],
            ['label' => $t('Mention', 'Person or date'), 'action' => 'startMention()', 'hint' => '@'],
            ['label' => $t('SpecialCharactersMenu', 'Special characters…'), 'action' => 'openCharacters()'],
            ['label' => $t('AddComment', 'Add comment'), 'action' => 'startComment()', 'hint' => 'Ctrl+Alt+M'],
            ['label' => $t('Footnote', 'Footnote'), 'action' => 'insertFootnote()', 'hint' => 'Ctrl+Alt+F'],
            [
                'label' => $t('TableOfContents', 'Table of contents'),
                'children' => [
                    ['label' => $t('TocPages', 'With page numbers'), 'action' => "insertToc('pages')"],
                    ['label' => $t('TocLinks', 'With blue links'), 'action' => "insertToc('links')"],
                ],
            ],
            ['label' => $t('Header', 'Header'), 'action' => "editHeaderFooter('header')"],
            ['label' => $t('Footer', 'Footer'), 'action' => "editHeaderFooter('footer')"],
            [
                'label' => $t('Field', 'Field'),
                'children' => [
                    ['label' => $t('FieldPage', 'Page number'), 'action' => "insertField('page')"],
                    ['label' => $t('FieldPages', 'Page count'), 'action' => "insertField('pages')"],
                    ['label' => $t('FieldTitle', 'Document title'), 'action' => "insertField('title')"],
                    ['label' => $t('FieldDate', "Today's date"), 'action' => "insertField('date')"],
                ],
            ],
        ],
        $t('MenuFormat', 'Format') => [
            ['label' => $t('Bold', 'Bold'), 'action' => "toggle('bold')", 'hint' => 'Ctrl+B'],
            ['label' => $t('Italic', 'Italic'), 'action' => "toggle('italic')", 'hint' => 'Ctrl+I'],
            ['label' => $t('Underline', 'Underline'), 'action' => "toggle('underline')", 'hint' => 'Ctrl+U'],
            ['label' => $t('Strike', 'Strikethrough'), 'action' => "toggle('strike')"],
            ['label' => $t('Superscript', 'Superscript'), 'action' => "toggle('superscript')"],
            ['label' => $t('Subscript', 'Subscript'), 'action' => "toggle('subscript')"],
            [
                'label' => $t('TextCase', 'Text case'),
                'children' => [
                    ['label' => $t('CaseLower', 'lowercase'), 'action' => "setCase('lower')"],
                    ['label' => $t('CaseUpper', 'UPPERCASE'), 'action' => "setCase('upper')"],
                    ['label' => $t('CaseTitle', 'Title Case'), 'action' => "setCase('title')"],
                    ['label' => $t('CaseSentence', 'Sentence case'), 'action' => "setCase('sentence')"],
                ],
            ],
            [
                'label' => $t('NumberingStyle', 'Numbered list style'),
                'children' => [
                    ['label' => '1. a. i.', 'action' => "setNumbering('decimal')"],
                    ['label' => '1) a) i)', 'action' => "setNumbering('paren')"],
                    ['label' => '1. 1.1. 1.1.1.', 'action' => "setNumbering('outline')"],
                    ['label' => 'A. a. i.', 'action' => "setNumbering('upper-alpha')"],
                    ['label' => 'a. i. 1.', 'action' => "setNumbering('lower-alpha')"],
                    ['label' => 'I. A. 1.', 'action' => "setNumbering('upper-roman')"],
                    ['label' => 'i. a. 1.', 'action' => "setNumbering('lower-roman')"],
                ],
            ],
            [
                'label' => $t('Columns', 'Columns'),
                'children' => [
                    ['label' => $t('ColumnsOne', 'One column'), 'action' => 'setColumns(1)', 'checked' => 'columns === 1'],
                    ['label' => $t('ColumnsTwo', 'Two columns'), 'action' => 'setColumns(2)', 'checked' => 'columns === 2'],
                    ['label' => $t('ColumnsThree', 'Three columns'), 'action' => 'setColumns(3)', 'checked' => 'columns === 3'],
                ],
            ],
            ['label' => $t('Quote', 'Quote'), 'action' => "setStyle('blockquote')"],
            ['label' => $t('CodeBlock', 'Code block'), 'action' => "setStyle('codeblock')"],
            ['label' => $t('PaintFormat', 'Paint format'), 'action' => 'togglePainter(false)'],
        ],
        $t('MenuTools', 'Tools') => [
            ['label' => $t('WordCount', 'Word count'), 'action' => 'openWordCount()', 'hint' => 'Ctrl+Shift+C'],
            ['label' => $t('FindReplace', 'Find and replace'), 'action' => 'openFind(true)', 'hint' => 'Ctrl+H'],
            ['label' => $t('ModeSuggesting', 'Suggesting'), 'action' => "setEditMode(editMode === 'suggesting' ? 'editing' : 'suggesting')", 'hint' => 'Ctrl+Alt+Shift+X'],
            ['label' => $t('VoiceTyping', 'Voice typing'), 'action' => 'toggleVoice()', 'hint' => 'Ctrl+Shift+S'],
            ['label' => $t('KeyboardShortcuts', 'Keyboard shortcuts'), 'action' => 'shortcutsOpen = true; shortcutsQuery = \'\'', 'hint' => 'Ctrl+/'],
            ['label' => $t('AcceptAllSuggestions', 'Accept all suggestions'), 'action' => 'settleAll(true)'],
            ['label' => $t('RejectAllSuggestions', 'Reject all suggestions'), 'action' => 'settleAll(false)'],
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
        aria-label="{{ $t('DocumentName', 'Document name') }}"
        placeholder="{{ $documentTitle ?? $t('Untitled', 'Untitled document') }}"
    >

    {{--
        One panel for every menu: moving to another title slides it there and
        swaps its items, rather than closing one panel and opening another.
    --}}
    <nav
        class="relative flex items-center gap-0.5 text-xs"
        aria-label="{{ $t('Menu', 'Menu') }}"
        x-data="nkMenubar"
        x-on:click.outside="menuClose()"
        x-on:keydown="menuKey($event)"
    >
        @foreach(array_keys($menus) as $index => $label)
            <button
                type="button"
                data-menubar-trigger
                class="rounded-md px-2.5 py-1 text-fg-muted transition-colors hover:bg-hover hover:text-fg"
                x-bind:class="menuAt === {{ $index }} && 'bg-hover text-fg'"
                x-on:click="menuToggle({{ $index }})"
                x-on:mouseenter="menuHover({{ $index }})"
                x-bind:aria-expanded="menuAt === {{ $index }} ? 'true' : 'false'"
            >{{ $label }}</button>
        @endforeach

        <div
            x-show="menuAt !== null"
            x-cloak
            data-side="bottom"
            data-align="start"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
            class="nk-menu-motion nk-editor-menu nk-menubar-frame absolute left-0 top-full z-40 mt-1.5"
        >
            @foreach(array_values($menus) as $menu => $items)
                <div
                    class="nk-menubar-list"
                    data-menubar-list="{{ $menu }}"
                    x-bind:data-at="menuSide({{ $menu }})"
                    x-bind:inert="menuShown !== {{ $menu }}"
                >
                    @foreach($items as $index => $item)
                        @if(isset($item['children']))
                            {{-- Parent row: hovering or clicking reveals the submenu beside it. --}}
                            <div
                                class="relative"
                                x-on:mouseenter="menuSub = {{ $index }}"
                                x-on:mouseleave="menuSub === {{ $index }} && (menuSub = null)"
                                @isset($item['requires'])
                                    x-show="{{ $item['requires'] === 'import' ? 'importAvailable' : 'exportAvailable' }}"
                                @endisset
                            >
                                <button
                                    type="button"
                                    class="{{ $itemClasses }}"
                                    x-on:click="menuSub = menuSub === {{ $index }} ? null : {{ $index }}"
                                    :disabled="busy"
                                    :aria-expanded="menuAt === {{ $menu }} && menuSub === {{ $index }}"
                                >
                                    <span>{{ $item['label'] }}</span>
                                    <svg viewBox="0 0 24 24" class="size-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </button>

                                <div
                                    x-show="menuAt === {{ $menu }} && menuSub === {{ $index }}"
                                    x-cloak
                                    data-side="right"
                                    data-align="start"
                                    x-transition:enter="nk-menu-motion"
                                    x-transition:enter-start="nk-menu-from"
                                    x-transition:enter-end="nk-menu-to"
                                    x-transition:leave="nk-menu-leaving"
                                    x-transition:leave-start="nk-menu-to"
                                    x-transition:leave-end="nk-menu-out"
                                    class="nk-menu-motion nk-editor-menu absolute left-full top-0 z-50 -mt-1 ml-1 w-40"
                                >
                                    @foreach($item['children'] as $child)
                                        <button
                                            type="button"
                                            class="{{ $itemClasses }}"
                                            x-on:click="{{ $child['action'] }}; menuClose()"
                                            :disabled="busy"
                                            @isset($child['when']) x-show="{{ $child['when'] }}" @endisset
                                            @isset($child['checked']) x-bind:aria-checked="{{ $child['checked'] }} ? 'true' : 'false'" role="menuitemcheckbox" @endisset
                                        >
                                            <span>{{ $child['label'] }}</span>
                                            @isset($child['checked'])
                                                <svg x-show="{{ $child['checked'] }}" viewBox="0 0 24 24" class="size-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                            @endisset
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <button
                                type="button"
                                class="{{ $itemClasses }}"
                                x-on:click="{{ $item['action'] }}; menuClose()"
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
            @endforeach
        </div>
    </nav>

    {{-- While editing a header or footer: whether the first page has its own, and the way out. --}}
    <label x-show="hfMode" x-cloak class="ml-auto flex shrink-0 cursor-pointer items-center gap-1.5 text-xs text-fg-muted">
        <input type="checkbox" class="size-3.5 accent-primary" x-bind:checked="differentFirstPage" x-on:mousedown.prevent x-on:click.prevent="toggleFirstPage()">
        {{ $t('DifferentFirstPage', 'Different first page') }}
    </label>
    <button
        type="button"
        x-show="hfMode"
        x-cloak
        class="shrink-0 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-white shadow-sm"
        x-on:click="exitHeaderFooter()"
        x-text="hfMode === 'header' ? @js($t('CloseHeader', 'Close header')) : @js($t('CloseFooter', 'Close footer'))"
    ></button>

    {{-- Outline panel, toggled from the View menu. --}}
    <div
        x-show="showOutline"
        x-cloak
        class="nk-editor-menu absolute left-2 top-24 z-30 max-h-80 w-56 overflow-y-auto p-2"
    >
        <div class="mb-1.5 flex items-center justify-between px-1">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-fg-muted">{{ $t('Outline', 'Outline') }}</span>
            <button type="button" class="rounded px-1 text-xs text-fg-muted hover:bg-hover hover:text-fg" x-on:click="showOutline = false" aria-label="{{ $t('Close', 'Close') }}">×</button>
        </div>

        <template x-if="outline.length === 0">
            <p class="px-1 py-2 text-xs text-fg-muted">{{ $t('OutlineEmpty', 'Headings you add will appear here.') }}</p>
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
