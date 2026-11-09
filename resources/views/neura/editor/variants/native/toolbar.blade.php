{{--
    Native editor toolbar.

    Icons are inline SVG rather than <neura::icon> on purpose: the glyphs a word
    processor needs (the four alignments, line spacing, indent arrows) have no
    equivalent in Heroicons, and inlining keeps the variant free of any icon
    package being installed.
--}}

@php
    $t = fn (string $key, string $fallback) => neura_trans('editor'.$key, null, $fallback);
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
    aria-label="{{ $t('Formatting', 'Formatting') }}"
    x-on:mousedown.prevent
>
    {{-- History and print ------------------------------------------------ --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="{{ $t('Undo', 'Undo') }} (Ctrl+Z)" x-on:click="undo()" :disabled="!canUndo()">
        <svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-4"/></svg>
        <span class="sr-only">{{ $t('Undo', 'Undo') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Redo', 'Redo') }} (Ctrl+Y)" x-on:click="redo()" :disabled="!canRedo()">
        <svg viewBox="0 0 24 24"><path d="m15 14 5-5-5-5"/><path d="M20 9H9a5 5 0 0 0 0 10h4"/></svg>
        <span class="sr-only">{{ $t('Redo', 'Redo') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Print', 'Print') }} (Ctrl+P)" x-on:click="print()">
        <svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 15h12v6H6z"/></svg>
        <span class="sr-only">{{ $t('Print', 'Print') }}</span>
    </button>

    {{-- Click picks up the formatting at the caret for the next selection; double-click keeps it until Escape. --}}
    <button
        type="button"
        class="nk-tool"
        title="{{ $t('PaintFormat', 'Paint format') }}"
        :aria-pressed="painterOn"
        x-on:click="togglePainter(false)"
        x-on:dblclick="togglePainter(true)"
    >
        <svg viewBox="0 0 24 24"><rect x="4" y="3" width="13" height="5" rx="1"/><path d="M17 5.5h2.5V11H11v3"/><rect x="9.5" y="14" width="3" height="7" rx="1"/></svg>
        <span class="sr-only">{{ $t('PaintFormat', 'Paint format') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('ClearFormatting', 'Clear formatting') }} (Ctrl+\)" x-on:click="clearFormatting()">
        <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M13 4 9 20"/><path d="m17 14 5 5m0-5-5 5"/></svg>
        <span class="sr-only">{{ $t('ClearFormatting', 'Clear formatting') }}</span>
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
            data-side="bottom"
            data-align="start"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
            class="nk-menu-motion nk-editor-menu absolute left-0 top-full z-30 mt-1.5 w-48"
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
            title="{{ $t('Font', 'Font') }}"
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
        <button type="button" class="nk-tool" title="{{ $t('FontSmaller', 'Decrease font size') }}" x-on:click="bumpFontSize(-1)">
            <svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
        </button>

        <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
            <button
                type="button"
                class="nk-tool w-10 justify-center rounded border border-edge text-xs"
                x-on:click="open = !open"
                x-text="currentFontSize()"
                title="{{ $t('FontSize', 'Font size') }}"
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

        <button type="button" class="nk-tool" title="{{ $t('FontBigger', 'Increase font size') }}" x-on:click="bumpFontSize(1)">
            <svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
    </div>

    {!! $divider !!}

    {{-- Inline marks ------------------------------------------------------ --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="{{ $t('Bold', 'Bold') }} (Ctrl+B)" :aria-pressed="isActive('bold')" x-on:click="toggle('bold')">
        <svg viewBox="0 0 24 24"><path d="M7 5h6a3.5 3.5 0 0 1 0 7H7z"/><path d="M7 12h7a3.5 3.5 0 0 1 0 7H7z"/></svg>
        <span class="sr-only">{{ $t('Bold', 'Bold') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Italic', 'Italic') }} (Ctrl+I)" :aria-pressed="isActive('italic')" x-on:click="toggle('italic')">
        <svg viewBox="0 0 24 24"><path d="M15 5h-5"/><path d="M14 19H9"/><path d="m14 5-4 14"/></svg>
        <span class="sr-only">{{ $t('Italic', 'Italic') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Underline', 'Underline') }} (Ctrl+U)" :aria-pressed="isActive('underline')" x-on:click="toggle('underline')">
        <svg viewBox="0 0 24 24"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><path d="M5 20h14"/></svg>
        <span class="sr-only">{{ $t('Underline', 'Underline') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Strike', 'Strikethrough') }}" :aria-pressed="isActive('strike')" x-on:click="toggle('strike')">
        <svg viewBox="0 0 24 24"><path d="M4 12h16"/><path d="M17 7a4 4 0 0 0-4-2h-2a3 3 0 0 0-1 5.8"/><path d="M7 17a4 4 0 0 0 4 2h2a3 3 0 0 0 1-5.8"/></svg>
        <span class="sr-only">{{ $t('Strike', 'Strikethrough') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('InlineCode', 'Inline code') }}" :aria-pressed="isActive('code')" x-on:click="toggle('code')">
        <svg viewBox="0 0 24 24"><path d="m9 18-6-6 6-6"/><path d="m15 6 6 6-6 6"/></svg>
        <span class="sr-only">{{ $t('InlineCode', 'Inline code') }}</span>
    </button>

    {{-- Text colour ------------------------------------------------------- --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" class="nk-tool" title="{{ $t('TextColor', 'Text colour') }}" x-on:click="open = !open">
            <svg viewBox="0 0 24 24"><path d="m6 18 6-13 6 13"/><path d="M8.5 14h7"/></svg>
            <span class="sr-only">{{ $t('TextColor', 'Text colour') }}</span>
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
        <button type="button" class="nk-tool" title="{{ $t('Highlight', 'Highlight') }}" :aria-pressed="isActive('highlight')" x-on:click="open = !open">
            <svg viewBox="0 0 24 24"><path d="m9 11-6 6v3h3l6-6"/><path d="M15 5l4 4"/><path d="m12 8 4-4 4 4-4 4z"/></svg>
            <span class="sr-only">{{ $t('Highlight', 'Highlight') }}</span>
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
    <button type="button" class="nk-tool" title="{{ $t('InsertLink', 'Insert link') }} (Ctrl+K)" :aria-pressed="isActive('link')" x-on:click="openLinkDialog()">
        <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
        <span class="sr-only">{{ $t('InsertLink', 'Insert link') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('InsertImage', 'Insert image') }}" x-on:click="pickImage()">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 16-5-5L5 20"/></svg>
        <span class="sr-only">{{ $t('InsertImage', 'Insert image') }}</span>
    </button>

    {{-- Table: the size is chosen in a dialog; row/column actions appear once
         the caret is inside a table. --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button
            type="button"
            class="nk-tool"
            title="{{ $t('TableShort', 'Table') }}"
            x-on:click="inTable() ? (open = !open) : openTableDialog()"
            :aria-expanded="open"
        >
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/></svg>
            <span class="sr-only">{{ $t('TableShort', 'Table') }}</span>
        </button>

        <div
            x-show="open"
            x-cloak
            data-side="bottom"
            data-align="start"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
            class="nk-menu-motion nk-editor-menu nk-table-menu absolute left-0 top-full z-30 mt-1"
        >
            <button type="button" class="nk-editor-menu-item" x-on:click="openTableDialog(); open = false">{{ $t('InsertTable', 'Insert table…') }}</button>

            <div class="nk-table-menu-divider" aria-hidden="true"></div>

            <neura::editor.variants.native.table-actions close="open = false" />
        </div>
    </div>

    <button type="button" class="nk-tool" title="{{ $t('HorizontalRule', 'Horizontal rule') }}" x-on:click="insertRule()">
        <svg viewBox="0 0 24 24"><path d="M4 12h16"/><path d="M6 7h12M6 17h12" opacity=".4"/></svg>
        <span class="sr-only">{{ $t('HorizontalRule', 'Horizontal rule') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('PageBreak', 'Page break') }} (Ctrl+Enter)" x-on:click="insertPageBreak()">
        <svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v4"/><path d="M6 21h12v-6"/><path d="M3 12h18" stroke-dasharray="3 2"/></svg>
        <span class="sr-only">{{ $t('PageBreak', 'Page break') }}</span>
    </button>
    </div>

    {!! $divider !!}

    {{-- Alignment --------------------------------------------------------- --}}
    <div class="nk-tool-group">
    <button type="button" class="nk-tool" title="{{ $t('AlignLeft', 'Align left') }}" :aria-pressed="currentAlign() === 'left'" x-on:click="setAlign('left')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h10M4 14h16M4 18h10"/></svg>
        <span class="sr-only">{{ $t('AlignLeft', 'Align left') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('AlignCenter', 'Align centre') }}" :aria-pressed="currentAlign() === 'center'" x-on:click="setAlign('center')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M7 10h10M4 14h16M7 18h10"/></svg>
        <span class="sr-only">{{ $t('AlignCenter', 'Align centre') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('AlignRight', 'Align right') }}" :aria-pressed="currentAlign() === 'right'" x-on:click="setAlign('right')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M10 10h10M4 14h16M10 18h10"/></svg>
        <span class="sr-only">{{ $t('AlignRight', 'Align right') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Justify', 'Justify') }}" :aria-pressed="currentAlign() === 'justify'" x-on:click="setAlign('justify')">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <span class="sr-only">{{ $t('Justify', 'Justify') }}</span>
    </button>

    {{-- Line spacing ------------------------------------------------------ --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" class="nk-tool" title="{{ $t('LineSpacing', 'Line spacing') }}" x-on:click="open = !open">
            <svg viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 8V4m0 0L2 6m2-2 2 2"/><path d="M4 16v4m0 0 2-2m-2 2-2-2"/></svg>
            <span class="sr-only">{{ $t('LineSpacing', 'Line spacing') }}</span>
        </button>

        <div
            x-show="open"
            x-cloak
            class="nk-editor-menu absolute left-0 top-full z-30 mt-1.5 w-32"
        >
            {{-- Pairs, not a keyed array: PHP casts float keys to int, which
                 collapsed 1 / 1.15 / 1.5 onto key 1 and left the menu with two
                 wrong entries. --}}
            @foreach([[1, $t('Single', 'Single')], [1.15, '1.15'], [1.5, '1.5'], [2, $t('Double', 'Double')], [2.5, '2.5']] as [$value, $label])
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
    <button type="button" class="nk-tool" title="{{ $t('BulletList', 'Bulleted list') }} (Ctrl+Shift+8)" :aria-pressed="isList('ul')" x-on:click="toggleBulletList()">
        <svg viewBox="0 0 24 24"><path d="M9 6h12M9 12h12M9 18h12"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/></svg>
        <span class="sr-only">{{ $t('BulletList', 'Bulleted list') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('NumberedList', 'Numbered list') }} (Ctrl+Shift+7)" :aria-pressed="isList('ol')" x-on:click="toggleOrderedList()">
        <svg viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 5h1v4M3 15h2.5L3 18h2.5"/></svg>
        <span class="sr-only">{{ $t('NumberedList', 'Numbered list') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Checklist', 'Checklist') }} (Ctrl+Shift+9)" :aria-pressed="isChecklist()" x-on:click="toggleChecklist()">
        <svg viewBox="0 0 24 24"><rect x="3.5" y="4.5" width="5" height="5" rx="1"/><path d="m4.5 16.5 1.5 1.5 2.5-3"/><path d="M12 7h9M12 17h9"/></svg>
        <span class="sr-only">{{ $t('Checklist', 'Checklist') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Outdent', 'Decrease indent') }} (Shift+Tab)" x-on:click="outdent()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M10 12h10M4 18h16"/><path d="m7 9-3 3 3 3"/></svg>
        <span class="sr-only">{{ $t('Outdent', 'Decrease indent') }}</span>
    </button>

    <button type="button" class="nk-tool" title="{{ $t('Indent', 'Increase indent') }} (Tab)" x-on:click="indent()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M10 12h10M4 18h16"/><path d="m4 9 3 3-3 3"/></svg>
        <span class="sr-only">{{ $t('Indent', 'Increase indent') }}</span>
    </button>
    </div>

    {{-- Review: comment, the panel, and how edits are made ---------------- --}}
    <div class="nk-tool-group nk-review-group">
    <button type="button" class="nk-tool" title="{{ $t('AddComment', 'Add comment') }} (Ctrl+Alt+M)" x-show="canReview" x-on:click="startComment()">
        <svg viewBox="0 0 24 24"><path d="M20 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/><path d="M12 8v6M9 11h6"/></svg>
        <span class="sr-only">{{ $t('AddComment', 'Add comment') }}</span>
    </button>

    <button type="button" class="nk-tool nk-review-toggle" title="{{ $t('ReviewPanel', 'Comments and suggestions') }}" :aria-pressed="reviewOpen ? 'true' : 'false'" x-on:click="toggleReview()">
        <svg viewBox="0 0 24 24"><path d="M20 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/></svg>
        <span class="nk-review-badge" x-show="openCount()" x-text="openCount()"></span>
        <span class="sr-only">{{ $t('ReviewPanel', 'Comments and suggestions') }}</span>
    </button>

    @php
        $modes = [
            'editing' => [$t('ModeEditing', 'Editing'), $t('ModeEditingHint', 'Edit the document directly'), '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>'],
            'suggesting' => [$t('ModeSuggesting', 'Suggesting'), $t('ModeSuggestingHint', 'Edits become suggestions'), '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 20h6" />'],
            'viewing' => [$t('ModeViewing', 'Viewing'), $t('ModeViewingHint', 'Read the final document'), '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>'],
        ];
    @endphp
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" class="nk-tool nk-mode-button" x-bind:data-mode="editMode" title="{{ $t('Mode', 'Mode') }} (Ctrl+Alt+Shift+Z / X / C)" x-on:click="open = !open" :aria-expanded="open ? 'true' : 'false'">
            @foreach($modes as $mode => [$label, $hint, $icon])
                <svg viewBox="0 0 24 24" x-show="editMode === '{{ $mode }}'" aria-hidden="true">{!! $icon !!}</svg>
            @endforeach
            <span class="nk-mode-label" x-text="@js(collect($modes)->map(fn ($m) => $m[0]))[editMode]"></span>
            <svg viewBox="0 0 24 24" class="nk-mode-chevron" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div
            x-show="open"
            x-cloak
            data-side="bottom"
            data-align="end"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
            class="nk-menu-motion nk-editor-menu nk-mode-menu absolute right-0 top-full z-40 mt-1.5"
        >
            @foreach($modes as $mode => [$label, $hint, $icon])
                <button
                    type="button"
                    class="nk-editor-menu-item nk-mode-item"
                    role="menuitemradio"
                    x-bind:aria-checked="editMode === '{{ $mode }}' ? 'true' : 'false'"
                    x-bind:disabled="'{{ $mode }}' !== 'viewing' && !canReview"
                    x-on:click="setEditMode('{{ $mode }}'); open = false"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg>
                    <span><span class="block">{{ $label }}</span><small>{{ $hint }}</small></span>
                </button>
            @endforeach
        </div>
    </div>
    </div>
</div>
