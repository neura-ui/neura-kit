{{--
    Native editor toolbar.

    Icons are inline SVG rather than <neura::icon> on purpose: the glyphs a word
    processor needs (the four alignments, line spacing, indent arrows) have no
    equivalent in Heroicons, and inlining keeps the variant free of any icon
    package being installed.
--}}

@php
    $divider = '<div class="nk-tool-divider" aria-hidden="true"></div>';

    $palette = [
        '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#efefef', '#ffffff',
        '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#9900ff',
        '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#d9d2e9',
        '#a61c00', '#cc0000', '#e69138', '#f1c232', '#6aa84f', '#45818e', '#3c78d8', '#674ea7',
    ];

    $highlights = ['#fff475', '#ccff90', '#a7ffeb', '#cbf0f8', '#d7aefb', '#fdcfe8', '#e6c9a8', 'transparent'];
@endphp

<div
    class="nk-native-toolbar"
    role="toolbar"
    aria-label="Formatting"
    x-on:mousedown.prevent
>
    {{-- History and print ------------------------------------------------ --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="Undo (Ctrl+Z)" x-on:click="undo()" :disabled="!canUndo()">
        <svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-4"/></svg>
        <span class="sr-only">Undo</span>
    </button>

    <button type="button" class="nk-tool" title="Redo (Ctrl+Y)" x-on:click="redo()" :disabled="!canRedo()">
        <svg viewBox="0 0 24 24"><path d="m15 14 5-5-5-5"/><path d="M20 9H9a5 5 0 0 0 0 10h4"/></svg>
        <span class="sr-only">Redo</span>
    </button>

    <button type="button" class="nk-tool" title="Print (Ctrl+P)" x-on:click="print()">
        <svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 15h12v6H6z"/></svg>
        <span class="sr-only">Print</span>
    </button>

    <button type="button" class="nk-tool" title="Clear formatting (Ctrl+\)" x-on:click="clearFormatting()">
        <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M13 4 9 20"/><path d="m17 14 5 5m0-5-5 5"/></svg>
        <span class="sr-only">Clear formatting</span>
    </button>
    </div>

    {!! $divider !!}

    {{-- Paragraph style --------------------------------------------------- --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button
            type="button"
            class="nk-tool w-32 justify-between px-2 text-xs"
            x-on:click="open = !open"
            :aria-expanded="open"
        >
            <span class="truncate" x-text="currentStyleLabel()"></span>
            <svg viewBox="0 0 24 24" class="ml-1 !h-3 !w-3"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="nk-editor-menu absolute left-0 top-full z-30 mt-1.5 w-48"
        >
            <template x-for="style in styleMenu" :key="style.name">
                <button
                    type="button"
                    class="nk-editor-menu-item"
                    :class="currentStyle() === style.name ? 'text-primary' : ''"
                    x-on:click="setStyle(style.name); open = false"
                    x-text="style.label"
                ></button>
            </template>
        </div>
    </div>

    {!! $divider !!}

    {{-- Font family ------------------------------------------------------- --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button
            type="button"
            class="nk-tool w-28 justify-between px-2 text-xs"
            x-on:click="open = !open"
            title="Font"
        >
            <span class="truncate" x-text="fontFamilies.find(f => f.value === currentFont())?.label ?? 'Arial'"></span>
            <svg viewBox="0 0 24 24" class="ml-1 !h-3 !w-3"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div
            x-show="open"
            x-cloak
            class="nk-editor-menu absolute left-0 top-full z-30 mt-1.5 w-48"
        >
            <template x-for="font in fontFamilies" :key="font.value">
                <button
                    type="button"
                    class="nk-editor-menu-item"
                    :style="`font-family: ${font.value}`"
                    x-on:click="setFont(font.value); open = false"
                    x-text="font.label"
                ></button>
            </template>
        </div>
    </div>

    {{-- Font size --------------------------------------------------------- --}}
    <div class="ml-1 flex items-center gap-0.5">
        <button type="button" class="nk-tool" title="Decrease font size" x-on:click="bumpFontSize(-1)">
            <svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
        </button>

        <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
            <button
                type="button"
                class="nk-tool w-10 justify-center rounded border border-edge text-xs"
                x-on:click="open = !open"
                x-text="currentFontSize()"
                title="Font size"
            ></button>

            <div
                x-show="open"
                x-cloak
                class="absolute left-0 top-full z-30 mt-1 max-h-64 w-20 overflow-y-auto rounded-md border border-edge bg-surface py-1 shadow-lg"
            >
                <template x-for="size in fontSizes" :key="size">
                    <button
                        type="button"
                        class="block w-full px-3 py-1 text-left text-sm hover:bg-hover"
                        x-on:click="setFontSize(size); open = false"
                        x-text="size"
                    ></button>
                </template>
            </div>
        </div>

        <button type="button" class="nk-tool" title="Increase font size" x-on:click="bumpFontSize(1)">
            <svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
    </div>

    {!! $divider !!}

    {{-- Inline marks ------------------------------------------------------ --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="Bold (Ctrl+B)" :aria-pressed="isActive('bold')" x-on:click="toggle('bold')">
        <svg viewBox="0 0 24 24"><path d="M7 5h6a3.5 3.5 0 0 1 0 7H7z"/><path d="M7 12h7a3.5 3.5 0 0 1 0 7H7z"/></svg>
        <span class="sr-only">Bold</span>
    </button>

    <button type="button" class="nk-tool" title="Italic (Ctrl+I)" :aria-pressed="isActive('italic')" x-on:click="toggle('italic')">
        <svg viewBox="0 0 24 24"><path d="M15 5h-5"/><path d="M14 19H9"/><path d="m14 5-4 14"/></svg>
        <span class="sr-only">Italic</span>
    </button>

    <button type="button" class="nk-tool" title="Underline (Ctrl+U)" :aria-pressed="isActive('underline')" x-on:click="toggle('underline')">
        <svg viewBox="0 0 24 24"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><path d="M5 20h14"/></svg>
        <span class="sr-only">Underline</span>
    </button>

    <button type="button" class="nk-tool" title="Strikethrough" :aria-pressed="isActive('strike')" x-on:click="toggle('strike')">
        <svg viewBox="0 0 24 24"><path d="M4 12h16"/><path d="M17 7a4 4 0 0 0-4-2h-2a3 3 0 0 0-1 5.8"/><path d="M7 17a4 4 0 0 0 4 2h2a3 3 0 0 0 1-5.8"/></svg>
        <span class="sr-only">Strikethrough</span>
    </button>

    <button type="button" class="nk-tool" title="Inline code" :aria-pressed="isActive('code')" x-on:click="toggle('code')">
        <svg viewBox="0 0 24 24"><path d="m9 18-6-6 6-6"/><path d="m15 6 6 6-6 6"/></svg>
        <span class="sr-only">Code</span>
    </button>

    {{-- Text colour ------------------------------------------------------- --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" class="nk-tool" title="Text colour" x-on:click="open = !open">
            <svg viewBox="0 0 24 24"><path d="m6 18 6-13 6 13"/><path d="M8.5 14h7"/></svg>
            <span class="sr-only">Text colour</span>
        </button>

        <div
            x-show="open"
            x-cloak
            class="nk-editor-menu absolute left-0 top-full z-30 mt-1.5 grid w-56 grid-cols-8 gap-1 p-2"
        >
            @foreach($palette as $color)
                <button
                    type="button"
                    class="size-5 rounded border border-edge"
                    style="background: {{ $color }}"
                    title="{{ $color }}"
                    x-on:click="setColor('{{ $color }}'); open = false"
                ></button>
            @endforeach
        </div>
    </div>

    {{-- Highlight --------------------------------------------------------- --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" class="nk-tool" title="Highlight" :aria-pressed="isActive('highlight')" x-on:click="open = !open">
            <svg viewBox="0 0 24 24"><path d="m9 11-6 6v3h3l6-6"/><path d="M15 5l4 4"/><path d="m12 8 4-4 4 4-4 4z"/></svg>
            <span class="sr-only">Highlight</span>
        </button>

        <div
            x-show="open"
            x-cloak
            class="nk-editor-menu absolute left-0 top-full z-30 mt-1.5 grid w-40 grid-cols-4 gap-1 p-2"
        >
            @foreach($highlights as $color)
                <button
                    type="button"
                    class="size-6 rounded border border-edge"
                    style="background: {{ $color }}"
                    x-on:click="setHighlight('{{ $color }}'); open = false"
                ></button>
            @endforeach
        </div>
    </div>
    </div>

    {!! $divider !!}

    {{-- Insert ------------------------------------------------------------ --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="Insert link (Ctrl+K)" :aria-pressed="isActive('link')" x-on:click="openLinkDialog()">
        <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
        <span class="sr-only">Link</span>
    </button>

    <button type="button" class="nk-tool" title="Insert image" x-on:click="pickImage()">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 16-5-5L5 20"/></svg>
        <span class="sr-only">Image</span>
    </button>

    {{-- Table: the size is chosen in a dialog; row/column actions appear once
         the caret is inside a table. --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button
            type="button"
            class="nk-tool"
            title="Table"
            x-on:click="inTable() ? (open = !open) : openTableDialog()"
            :aria-expanded="open"
        >
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/></svg>
            <span class="sr-only">Table</span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="absolute left-0 top-full z-30 mt-1 w-52 rounded-md border border-edge bg-surface py-1 shadow-lg"
        >
            <button
                type="button"
                class="block w-full px-3 py-1.5 text-left text-sm text-fg hover:bg-hover"
                x-on:click="openTableDialog(); open = false"
            >Insert table…</button>

            <div class="my-1 border-t border-edge"></div>

            @php
                $tableActions = [
                    ['Insert row above', "insertRow('above')"],
                    ['Insert row below', "insertRow('below')"],
                    ['Insert column left', "insertColumn('left')"],
                    ['Insert column right', "insertColumn('right')"],
                    ['Toggle header row', 'toggleHeaderRow()'],
                ];
            @endphp

            @foreach($tableActions as [$label, $action])
                <button
                    type="button"
                    class="block w-full px-3 py-1.5 text-left text-sm text-fg hover:bg-hover"
                    x-on:click="{{ $action }}; open = false"
                >{{ $label }}</button>
            @endforeach

            @foreach([['Delete row', 'deleteRow()'], ['Delete column', 'deleteColumn()'], ['Delete table', 'deleteTable()']] as [$label, $action])
                <button
                    type="button"
                    class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-hover dark:text-red-400"
                    x-on:click="{{ $action }}; open = false"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <button type="button" class="nk-tool" title="Horizontal rule" x-on:click="insertRule()">
        <svg viewBox="0 0 24 24"><path d="M4 12h16"/><path d="M6 7h12M6 17h12" opacity=".4"/></svg>
        <span class="sr-only">Horizontal rule</span>
    </button>

    <button type="button" class="nk-tool" title="Page break (Ctrl+Enter)" x-on:click="insertPageBreak()">
        <svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v4"/><path d="M6 21h12v-6"/><path d="M3 12h18" stroke-dasharray="3 2"/></svg>
        <span class="sr-only">Page break</span>
    </button>
    </div>

    {!! $divider !!}

    {{-- Alignment --------------------------------------------------------- --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="Align left" :aria-pressed="currentAlign() === 'left'" x-on:click="setAlign('left')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h10M4 14h16M4 18h10"/></svg>
        <span class="sr-only">Align left</span>
    </button>

    <button type="button" class="nk-tool" title="Align centre" :aria-pressed="currentAlign() === 'center'" x-on:click="setAlign('center')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M7 10h10M4 14h16M7 18h10"/></svg>
        <span class="sr-only">Align centre</span>
    </button>

    <button type="button" class="nk-tool" title="Align right" :aria-pressed="currentAlign() === 'right'" x-on:click="setAlign('right')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M10 10h10M4 14h16M10 18h10"/></svg>
        <span class="sr-only">Align right</span>
    </button>

    <button type="button" class="nk-tool" title="Justify" :aria-pressed="currentAlign() === 'justify'" x-on:click="setAlign('justify')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <span class="sr-only">Justify</span>
    </button>

    {{-- Line spacing ------------------------------------------------------ --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" class="nk-tool" title="Line spacing" x-on:click="open = !open">
            <svg viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 8V4m0 0L2 6m2-2 2 2"/><path d="M4 16v4m0 0 2-2m-2 2-2-2"/></svg>
            <span class="sr-only">Line spacing</span>
        </button>

        <div
            x-show="open"
            x-cloak
            class="nk-editor-menu absolute left-0 top-full z-30 mt-1.5 w-32"
        >
            {{-- Pairs, not a keyed array: PHP casts float keys to int, which
                 collapsed 1 / 1.15 / 1.5 onto key 1 and left the menu with two
                 wrong entries. --}}
            @foreach([[1, 'Single'], [1.15, '1.15'], [1.5, '1.5'], [2, 'Double'], [2.5, '2.5']] as [$value, $label])
                <button
                    type="button"
                    class="nk-editor-menu-item"
                    :class="currentSpacing() === {{ $value }} ? 'text-primary' : ''"
                    x-on:click="setSpacing({{ $value }}); open = false"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- Lists and indentation --------------------------------------------- --}}
    <button type="button" class="nk-tool" title="Bulleted list (Ctrl+Shift+8)" :aria-pressed="isList('ul')" x-on:click="toggleBulletList()">
        <svg viewBox="0 0 24 24"><path d="M9 6h12M9 12h12M9 18h12"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/></svg>
        <span class="sr-only">Bulleted list</span>
    </button>

    <button type="button" class="nk-tool" title="Numbered list (Ctrl+Shift+7)" :aria-pressed="isList('ol')" x-on:click="toggleOrderedList()">
        <svg viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 5h1v4M3 15h2.5L3 18h2.5"/></svg>
        <span class="sr-only">Numbered list</span>
    </button>

    <button type="button" class="nk-tool" title="Decrease indent (Shift+Tab)" x-on:click="outdent()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M10 12h10M4 18h16"/><path d="m7 9-3 3 3 3"/></svg>
        <span class="sr-only">Decrease indent</span>
    </button>

    <button type="button" class="nk-tool" title="Increase indent (Tab)" x-on:click="indent()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M10 12h10M4 18h16"/><path d="m4 9 3 3-3 3"/></svg>
        <span class="sr-only">Increase indent</span>
    </button>
    </div>
</div>
