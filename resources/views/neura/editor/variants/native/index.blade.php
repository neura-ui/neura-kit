@props([
    'name' => null,
    'value' => null,
    'mode' => 'html',
    'placeholder' => null,
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
    'documentTitle' => null,
    'header' => null,
    'footer' => null,
    'firstHeader' => null,
    'firstFooter' => null,
    'differentFirstPage' => false,
    'columns' => 1,
    'mentions' => [],
    'mentionsUrl' => null,
    'author' => null,
    'history' => null,
    'historyKey' => null,
    'versionsUrl' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    // Every word of the editor goes through the kit's strings (EN / FR), like the other components.
    $t = fn (string $key, string $fallback) => neura_trans('editor'.$key, null, $fallback);
    $placeholder ??= $t('Placeholder', 'Start writing…');
    $documentTitle ??= $t('Untitled', 'Untitled document');

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

    // People "@" offers: given here, or searched at `mentions-url` as you type.
    $mentions = collect($mentions ?? [])
        ->map(fn ($person) => [
            'id' => data_get($person, 'id'),
            'name' => (string) data_get($person, 'name', ''),
            'email' => data_get($person, 'email'),
            'avatar' => data_get($person, 'avatar'),
        ])
        ->filter(fn ($person) => $person['name'] !== '')
        ->values()
        ->all();
    $mentionsUrl ??= config('neura-kit.editor.mentions_url');

    // Who signs comments and suggestions: given, or the signed-in user.
    $author = is_string($author) ? ['name' => $author] : $author;
    if ($author === null) {
        $user = rescue(fn () => auth()->user(), null, false);
        $author = $user ? ['name' => data_get($user, 'name'), 'avatar' => data_get($user, 'avatar')] : null;
    }
    $author = $author ? ['name' => (string) data_get($author, 'name', ''), 'avatar' => data_get($author, 'avatar')] : null;

    // Version history: on unless turned off, kept under the field's name, in
    // the browser or at the app's endpoint.
    $historyConfig = (array) config('neura-kit.editor.history', []);
    $history = [
        'enabled' => (bool) ($history ?? ($historyConfig['enabled'] ?? true)),
        'key' => $historyKey ?? ($entangle ?: $name),
        'url' => $versionsUrl ?? ($historyConfig['url'] ?? null),
        'interval' => (int) ($historyConfig['interval'] ?? 300),
        'idle' => (int) ($historyConfig['idle'] ?? 20),
        'limit' => (int) ($historyConfig['limit'] ?? 100),
    ];

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
        'firstHeader' => $firstHeader,
        'firstFooter' => $firstFooter,
        'differentFirstPage' => (bool) $differentFirstPage,
        'columns' => max(1, min(3, (int) $columns)),
        'exportUrl' => $exportUrl,
        'importUrl' => $importUrl,
        'exportFormats' => $canConvert ? $converter->exportFormats() : [],
        'importFormats' => $canConvert ? $converter->importFormats() : [],
        'locale' => str_replace('_', '-', app()->getLocale()),
        'mentions' => $mentions,
        'mentionsUrl' => $mentionsUrl,
        'author' => $author,
        'history' => $history,
        'phrases' => [
            'untitled' => $t('Untitled', 'Untitled document'),
            // Suggested formatting, as the review cards say it.
            'marks' => [
                'bold' => $t('Bold', 'Bold'),
                'italic' => $t('Italic', 'Italic'),
                'underline' => $t('Underline', 'Underline'),
                'strike' => $t('Strike', 'Strikethrough'),
                'code' => $t('InlineCode', 'Code'),
                'superscript' => $t('Superscript', 'Superscript'),
                'subscript' => $t('Subscript', 'Subscript'),
                'highlight' => $t('Highlight', 'Highlight'),
                'color' => $t('TextColor', 'Text colour'),
                'fontFamily' => $t('Font', 'Font'),
                'fontSize' => $t('FontSize', 'Font size'),
            ],
            'aligns' => [
                'left' => $t('AlignLeft', 'Left align'),
                'center' => $t('AlignCenter', 'Center'),
                'right' => $t('AlignRight', 'Right align'),
                'justify' => $t('Justify', 'Justify'),
            ],
            'suggestFormat' => $t('SuggestFormat', 'Format: {changes}'),
            'suggestOff' => $t('SuggestOff', 'no {mark}'),
            'suggestParagraph' => $t('SuggestParagraph', 'Paragraph: {changes}'),
            'suggestSpacing' => $t('SuggestSpacing', 'line spacing {value}'),
            'suggestIndent' => $t('SuggestIndent', 'indent'),
            'suggestSplit' => $t('SuggestSplit', 'New paragraph'),
            'suggestJoin' => $t('SuggestJoin', 'Join paragraphs'),
            'styles' => [
                'paragraph' => $t('StyleParagraph', 'Normal text'),
                'title' => $t('StyleTitle', 'Title'),
                'subtitle' => $t('StyleSubtitle', 'Subtitle'),
                'heading1' => $t('StyleHeading1', 'Heading 1'),
                'heading2' => $t('StyleHeading2', 'Heading 2'),
                'heading3' => $t('StyleHeading3', 'Heading 3'),
                'heading4' => $t('StyleHeading4', 'Heading 4'),
                'blockquote' => $t('Quote', 'Quote'),
                'codeblock' => $t('CodeBlock', 'Code block'),
            ],
            'groups' => [
                'text' => $t('SlashText', 'Text'),
                'insert' => $t('SlashInsert', 'Insert'),
                'people' => $t('ChipPeople', 'People'),
                'dates' => $t('ChipDates', 'Dates'),
            ],
            'commands' => [
                'paragraph' => $t('StyleParagraph', 'Normal text'),
                'title' => $t('StyleTitle', 'Title'),
                'subtitle' => $t('StyleSubtitle', 'Subtitle'),
                'heading1' => $t('StyleHeading1', 'Heading 1'),
                'heading2' => $t('StyleHeading2', 'Heading 2'),
                'heading3' => $t('StyleHeading3', 'Heading 3'),
                'bulletList' => $t('BulletList', 'Bulleted list'),
                'orderedList' => $t('NumberedList', 'Numbered list'),
                'checklist' => $t('Checklist', 'Checklist'),
                'quote' => $t('Quote', 'Quote'),
                'codeblock' => $t('CodeBlock', 'Code block'),
                'table' => $t('TableShort', 'Table'),
                'image' => $t('ImageShort', 'Image'),
                'link' => $t('LinkShort', 'Link'),
                'divider' => $t('HorizontalRule', 'Horizontal rule'),
                'special' => $t('SpecialCharacters', 'Special characters'),
                'pageBreak' => $t('PageBreak', 'Page break'),
                'date' => $t('FieldDate', "Today's date"),
                'toc' => $t('TableOfContents', 'Table of contents'),
                'footnote' => $t('Footnote', 'Footnote'),
                'mention' => $t('Mention', 'Person or date'),
            ],
            'findCount' => $t('FindCount', '{current} of {total}'),
            'findNone' => $t('FindNone', 'No results'),
            'findInvalid' => $t('FindInvalid', 'Invalid expression'),
            'findReplaced' => $t('FindReplaced', '{count} replaced'),
            'captionHint' => $t('CaptionHint', 'Add a caption'),
            'breakColumn' => $t('BreakColumn', 'Column break'),
            'imageLocked' => $t('ImageLocked', 'This image comes from another site, which does not let it be edited here.'),
            'chipToday' => $t('ChipToday', 'Today'),
            'chipTomorrow' => $t('ChipTomorrow', 'Tomorrow'),
            'chipYesterday' => $t('ChipYesterday', 'Yesterday'),
            'noteHint' => $t('NoteHint', 'Footnote'),
            'tocHint' => $t('TocHint', 'Add headings (Format › Paragraph styles) and they will appear here.'),
            'you' => $t('You', 'You'),
            'versionRestored' => $t('VersionRestored', '“{name}” is the current version again.'),
            'comparedWithPrevious' => $t('ComparedWithPrevious', 'compared with the previous version'),
            'breakPage' => $t('PageBreak', 'Page break'),
            'breakOdd' => $t('BreakOdd', 'Next odd page'),
            'breakEven' => $t('BreakEven', 'Next even page'),
            'voiceUnsupported' => $t('VoiceUnsupported', 'Voice typing is not available in this browser. Use Chrome, Edge or Safari.'),
            'voiceDenied' => $t('VoiceDenied', 'The microphone is not allowed for this page.'),
            'voiceFailed' => $t('VoiceFailed', 'Voice typing stopped ({reason}).'),
            'voiceListening' => $t('VoiceListening', 'Listening… say {comma}, {line}…'),
            'exportFailed' => $t('ExportFailed', 'Export failed.'),
            'importFailed' => $t('ImportFailed', 'Import failed.'),
        ],
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
    x-on:keydown.escape="linkOpen = false; showPageSetup = false; showTableDialog = false; tableMenuOpen = false; showWordCount = false; chipOpen = false; charsOpen = false; shortcutsOpen = false; historyOpen && closeHistory(); exitHeaderFooter()"
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

    <div class="nk-native-stage" x-bind:class="{ 'has-review': reviewOpen }">
    {{-- Find and replace: floats over the pages, Ctrl+F / Ctrl+H --}}
    <div
        x-show="findOpen"
        x-cloak
        role="dialog"
        aria-label="{{ $t('FindReplace', 'Find and replace') }}"
        class="nk-find nk-menu-motion"
        data-side="bottom"
        data-align="end"
        x-transition:enter="nk-menu-motion"
        x-transition:enter-start="nk-menu-from"
        x-transition:enter-end="nk-menu-to"
        x-transition:leave="nk-menu-leaving"
        x-transition:leave-start="nk-menu-to"
        x-transition:leave-end="nk-menu-out"
        x-on:keydown.escape.stop="closeFind()"
    >
        <div class="nk-find-row">
            <label class="nk-find-field">
                <span class="sr-only">{{ $t('Find', 'Find') }}</span>
                <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="4.5"/><path d="m10.5 10.5 3 3"/></svg>
                <input
                    type="text"
                    x-ref="findInput"
                    x-model="findQuery"
                    x-on:input="runFind(false)"
                    x-on:keydown.enter.prevent="findStep($event.shiftKey ? -1 : 1)"
                    placeholder="{{ $t('Find', 'Find') }}"
                    autocomplete="off"
                    spellcheck="false"
                    x-bind:aria-invalid="findError ? 'true' : 'false'"
                >
                <span class="nk-find-count" aria-live="polite" x-text="findLabel()"></span>
            </label>
            <button type="button" class="nk-tool" title="{{ $t('FindPrevious', 'Previous result') }} (Shift+Enter)" x-on:click="findStep(-1)" x-bind:disabled="!findCount">
                <svg viewBox="0 0 24 24"><path d="m6 15 6-6 6 6"/></svg>
                <span class="sr-only">{{ $t('FindPrevious', 'Previous result') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('FindNext', 'Next result') }} (Enter)" x-on:click="findStep(1)" x-bind:disabled="!findCount">
                <svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                <span class="sr-only">{{ $t('FindNext', 'Next result') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('FindReplace', 'Find and replace') }}" :aria-pressed="findReplace" x-on:click="findReplace = !findReplace">
                <svg viewBox="0 0 24 24"><path d="M4 7h11l-3-3M20 17H9l3 3"/></svg>
                <span class="sr-only">{{ $t('FindReplace', 'Find and replace') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('Close', 'Close') }} (Escape)" x-on:click="closeFind()">
                <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
                <span class="sr-only">{{ $t('Close', 'Close') }}</span>
            </button>
        </div>
        <div class="nk-find-row" x-show="findReplace" x-cloak>
            <label class="nk-find-field">
                <span class="sr-only">{{ $t('ReplaceWith', 'Replace with') }}</span>
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.5 5h8l-2-2M13.5 11h-8l2 2"/></svg>
                <input
                    type="text"
                    x-model="replaceText"
                    x-on:keydown.enter.prevent="replaceOne()"
                    placeholder="{{ $t('ReplaceWith', 'Replace with') }}"
                    autocomplete="off"
                    spellcheck="false"
                >
            </label>
            <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="replaceOne()" x-bind:disabled="!findCount || !editable">{{ $t('Replace', 'Replace') }}</button>
            <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="replaceAll()" x-bind:disabled="!findCount || !editable">{{ $t('ReplaceAll', 'Replace all') }}</button>
        </div>
        <div class="nk-find-options">
            <label><input type="checkbox" x-model="findCase" x-on:change="runFind(false)"> {{ $t('MatchCase', 'Match case') }}</label>
            <label><input type="checkbox" x-model="findWhole" x-on:change="runFind(false)"> {{ $t('WholeWord', 'Whole words only') }}</label>
            <label><input type="checkbox" x-model="findRegex" x-on:change="runFind(false)"> {{ $t('Regex', 'Regular expressions') }}</label>
            <span class="nk-find-notice" x-show="findNotice" x-text="findNotice" aria-live="polite"></span>
        </div>
    </div>

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
                     data-nk-hint="{{ $t('HeaderHint', 'Double-click to add a header') }}"></div>

                <div
                    class="nk-page-body"
                    data-nk-content
                    data-nk-placeholder="{{ $placeholder }}"
                ></div>
                <div class="nk-page-footer" contenteditable="false" data-nk-footer
                     data-nk-hint="{{ $t('FooterHint', 'Double-click to add a footer') }}"></div>
            </div>
        </div>
    </div>

    {{-- Picked image: a frame with corner handles, and its own toolbar ------ --}}
    <div class="nk-image-layer" x-show="imageOpen" x-cloak>
        <div
            class="nk-image-frame"
            x-show="!cropOpen"
            x-bind:style="{ left: `${imageBox.left}px`, top: `${imageBox.top}px`, width: `${imageBox.width}px`, height: `${imageBox.height}px` }"
        >
            @foreach(['nw' => 'left', 'ne' => 'right', 'sw' => 'left', 'se' => 'right'] as $corner => $side)
                <span
                    class="nk-image-handle"
                    data-corner="{{ $corner }}"
                    title="{{ $t('ResizeImage', 'Resize') }}"
                    x-on:pointerdown.prevent.stop="resizeImage($event, '{{ $side }}')"
                ></span>
            @endforeach
            <span class="nk-image-size" x-show="imageWidth" x-text="`${imageWidth}%`"></span>
        </div>

        {{-- Cropping: the whole picture, dimmed but for the part kept --}}
        <div
            class="nk-crop"
            x-show="cropOpen"
            x-cloak
            x-on:mousedown.prevent
            x-bind:style="{ left: `${cropBox.left}px`, top: `${cropBox.top}px`, width: `${cropBox.width}px`, height: `${cropBox.height}px` }"
        >
            <img class="nk-crop-picture" x-bind:src="cropSrc" alt="" draggable="false">
            <div
                class="nk-crop-window"
                title="{{ $t('CropHint', 'Drag the handles to crop, then press Enter') }}"
                x-on:pointerdown.prevent.stop="dragCrop($event, 'move')"
                x-bind:style="{ top: `${cropInsets[0] * 100}%`, right: `${cropInsets[1] * 100}%`, bottom: `${cropInsets[2] * 100}%`, left: `${cropInsets[3] * 100}%` }"
            >
                @foreach(['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'] as $handle)
                    <span class="nk-crop-handle" data-handle="{{ $handle }}" x-on:pointerdown.prevent.stop="dragCrop($event, '{{ $handle }}')"></span>
                @endforeach
            </div>
        </div>

        {{-- Dragging the image: where it would land --}}
        <div
            class="nk-image-drop"
            x-show="dropLine"
            x-bind:style="dropLine ? { left: `${dropLine.left}px`, top: `${dropLine.top}px`, width: `${dropLine.width}px` } : {}"
        ></div>

        <div
            class="nk-image-bar"
            x-ref="imageBar"
            role="toolbar"
            aria-label="{{ $t('ImageOptions', 'Image options') }}"
            x-show="!imageDragging"
            x-bind:style="{ left: `${imageBar.left}px`, top: `${imageBar.top}px` }"
            x-on:mousedown.prevent
        >
            {{-- While cropping, only what ends it --}}
            <template x-if="cropOpen">
                <div class="nk-image-crop-bar">
                    <span class="nk-image-crop-hint">{{ $t('CropHint', 'Drag the handles to crop, then press Enter') }}</span>
                    <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="closeCrop()">{{ $t('Cancel', 'Cancel') }}</button>
                    <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="applyCrop()">{{ $t('CropDone', 'Done') }}</button>
                </div>
            </template>

            <template x-if="!cropOpen">
            <div class="nk-image-tools">
            @foreach([
                'inline' => [$t('WrapInline', 'In line with text'), '<rect x="7" y="8" width="10" height="8" rx="1"/><path d="M4 4.5h16M4 19.5h16"/>'],
                'left' => [$t('WrapLeft', 'Wrap text, image on the left'), '<rect x="3" y="5" width="9" height="9" rx="1"/><path d="M15 6h6M15 10h6M15 14h6M3 18.5h18"/>'],
                'right' => [$t('WrapRight', 'Wrap text, image on the right'), '<rect x="12" y="5" width="9" height="9" rx="1"/><path d="M3 6h6M3 10h6M3 14h6M3 18.5h18"/>'],
            ] as $wrap => [$label, $icon])
                <button type="button" class="nk-tool" title="{{ $label }}"
                    x-bind:aria-pressed="imageWrap === '{{ $wrap }}' ? 'true' : 'false'"
                    x-on:click="setImageWrap('{{ $wrap }}')">
                    <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg>
                    <span class="sr-only">{{ $label }}</span>
                </button>
            @endforeach

            <div class="nk-tool-divider" aria-hidden="true"></div>

            <div class="nk-image-sizes" role="group" aria-label="{{ $t('ImageSize', 'Size') }}">
                @foreach([25, 50, 75, 100] as $percent)
                    <button type="button" class="nk-tool nk-image-preset"
                        x-bind:aria-pressed="imageWidth === {{ $percent }} ? 'true' : 'false'"
                        x-on:click="setImageWidth({{ $percent }})">{{ $percent }}%</button>
                @endforeach
                <button type="button" class="nk-tool nk-image-preset" title="{{ $t('OriginalSize', 'Original size') }}"
                    x-bind:aria-pressed="imageWidth === null ? 'true' : 'false'"
                    x-on:click="setImageWidth(null)">{{ $t('AutoSize', 'Auto') }}</button>
            </div>

            <div class="nk-tool-divider" aria-hidden="true"></div>

            <button type="button" class="nk-tool" title="{{ $t('CropImage', 'Crop image') }}" x-on:click="startCrop()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                <span class="sr-only">{{ $t('CropImage', 'Crop image') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('RotateLeft', 'Rotate left') }}" x-on:click="rotateImage(-1)">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                <span class="sr-only">{{ $t('RotateLeft', 'Rotate left') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('RotateRight', 'Rotate right') }}" x-on:click="rotateImage(1)">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                <span class="sr-only">{{ $t('RotateRight', 'Rotate right') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('ImageBorder', 'Border') }}"
                x-bind:aria-pressed="imageBorder ? 'true' : 'false'"
                x-bind:aria-expanded="imageBorderOpen ? 'true' : 'false'"
                x-on:click="imageBorderOpen = !imageBorderOpen; altOpen = false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="2"/><rect x="8" y="8" width="8" height="8" rx="1" stroke-dasharray="2 2"/></svg>
                <span class="sr-only">{{ $t('ImageBorder', 'Border') }}</span>
            </button>
            <button type="button" class="nk-tool" title="{{ $t('ResetImage', 'Reset image') }}" x-show="imageEdited" x-on:click="resetImage()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
                <span class="sr-only">{{ $t('ResetImage', 'Reset image') }}</span>
            </button>

            <div class="nk-tool-divider" aria-hidden="true"></div>

            <button type="button" class="nk-tool" title="{{ $t('ImageCaption', 'Caption') }}"
                x-bind:aria-pressed="imageHasCaption ? 'true' : 'false'" x-on:click="toggleImageCaption()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3.5" width="16" height="11" rx="1"/><path d="M7 18.5h10"/></svg>
                <span class="sr-only">{{ $t('ImageCaption', 'Caption') }}</span>
            </button>
            <button type="button" class="nk-tool nk-image-preset" title="{{ $t('AltText', 'Alt text') }}" x-on:click="imageBorderOpen = false; openAltText()">Alt</button>
            <button type="button" class="nk-tool" title="{{ $t('ReplaceImage', 'Replace image') }}" x-on:click="replaceImage()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4v5h5"/><path d="M20 20v-5h-5"/><path d="M5 9a7.5 7.5 0 0 1 13.5-2.5M19 15a7.5 7.5 0 0 1-13.5 2.5"/></svg>
                <span class="sr-only">{{ $t('ReplaceImage', 'Replace image') }}</span>
            </button>
            <button type="button" class="nk-tool nk-image-delete" title="{{ $t('DeleteImage', 'Delete image') }}" x-on:click="deleteImage()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                <span class="sr-only">{{ $t('DeleteImage', 'Delete image') }}</span>
            </button>
            </div>
            </template>

            <p class="nk-image-notice" role="status" x-show="imageNotice" x-cloak x-text="imageNotice"></p>
        </div>

        <div
            class="nk-image-border nk-editor-dialog"
            x-show="imageBorderOpen && !cropOpen"
            x-cloak
            role="dialog"
            aria-label="{{ $t('ImageBorder', 'Border') }}"
            x-bind:style="{ left: `${imageBar.left}px`, top: `${imageBar.top + 44}px` }"
            x-on:mousedown.prevent
            x-on:keydown.escape.stop="imageBorderOpen = false"
        >
            <p class="mb-1.5 text-xs font-medium text-fg">{{ $t('ImageBorder', 'Border') }}</p>
            <div class="nk-table-weights">
                @foreach(['none' => $t('BorderNone', 'No border'), 'thin' => $t('BorderThin', 'Thin'), 'medium' => $t('BorderMedium', 'Medium'), 'thick' => $t('BorderThick', 'Thick')] as $weight => $label)
                    <button type="button" class="nk-table-weight" title="{{ $label }}" aria-label="{{ $label }}" data-weight="{{ $weight }}"
                        x-bind:aria-pressed="(imageBorder ?? 'none') === '{{ $weight }}' ? 'true' : 'false'"
                        x-on:click="setImageBorder({{ $weight === 'none' ? 'null' : "'{$weight}'" }})"><span aria-hidden="true"></span></button>
                @endforeach
            </div>
            <div class="nk-table-swatches" role="group" aria-label="{{ $t('BorderColor', 'Border colour') }}">
                @foreach(['#000000', '#666666', '#b7b7b7', '#ffffff', '#cc0000', '#e69138', '#6aa84f', '#3c78d8', '#674ea7'] as $color)
                    <button type="button" class="nk-table-swatch" style="background: {{ $color }}" title="{{ $color }}" aria-label="{{ $color }}"
                        x-bind:aria-pressed="imageBorder && (imageBorderColor ?? '#000000') === '{{ $color }}' ? 'true' : 'false'"
                        x-on:click="setImageBorderColor('{{ $color }}')"></button>
                @endforeach
            </div>
        </div>

        <div
            class="nk-image-alt nk-editor-dialog"
            x-show="altOpen"
            x-cloak
            role="dialog"
            aria-label="{{ $t('AltText', 'Alt text') }}"
            x-bind:style="{ left: `${imageBar.left}px`, top: `${imageBar.top + 44}px` }"
            x-on:keydown.escape.stop="altOpen = false"
        >
            <label class="mb-1 block text-xs font-medium text-fg" for="{{ $name ?? 'nk' }}-alt">{{ $t('AltText', 'Alt text') }}</label>
            <p class="mb-2 text-xs text-fg-muted">{{ $t('AltTextHint', 'Describe the image for people who cannot see it.') }}</p>
            <textarea
                id="{{ $name ?? 'nk' }}-alt"
                class="nk-editor-field mb-2 w-full"
                rows="2"
                x-model="altText"
                x-effect="altOpen && $nextTick(() => $el.focus())"
                x-on:keydown.enter.prevent="applyAltText()"
            ></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="altOpen = false">{{ $t('Cancel', 'Cancel') }}</button>
                <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="applyAltText()">{{ $t('Apply', 'Apply') }}</button>
            </div>
        </div>
    </div>

    {{-- A chip's card: the person or the date, and what can be done with it -- --}}
    <div
        class="nk-chip-card"
        x-ref="chipCard"
        x-show="chipOpen"
        x-cloak
        role="dialog"
        aria-label="{{ $t('ChipCard', 'Smart chip') }}"
        x-bind:style="{ left: `${chipBox.left}px`, top: `${chipBox.top}px` }"
    >
        <div class="nk-chip-person" x-show="chipCard.kind === 'person'">
            <span class="nk-chip-avatar" x-text="chipCard.initials" aria-hidden="true"></span>
            <div class="min-w-0">
                <p class="nk-chip-name" x-text="chipCard.label"></p>
                <p class="nk-chip-sub" x-show="chipCard.email" x-text="chipCard.email"></p>
            </div>
        </div>
        <div x-show="chipCard.kind === 'date'">
            <p class="nk-chip-name" x-text="chipCard.long"></p>
            <label class="nk-chip-sub mt-2 block" for="{{ $name ?? 'nk' }}-chip-date">{{ $t('ChipChangeDate', 'Change the date') }}</label>
            <input
                id="{{ $name ?? 'nk' }}-chip-date"
                type="date"
                class="nk-editor-field mt-1"
                x-bind:value="chipCard.date"
                x-bind:disabled="!editable"
                x-on:change="setChipDate($event.target.value)"
            >
        </div>
        <div class="nk-chip-actions">
            <a
                class="nk-editor-btn nk-editor-btn-ghost"
                x-show="chipCard.kind === 'person' && chipCard.email"
                x-bind:href="`mailto:${chipCard.email}`"
            >{{ $t('ChipEmail', 'Send an email') }}</a>
            <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-show="editable" x-on:click="removeChip()">{{ $t('ChipRemove', 'Remove') }}</button>
        </div>
    </div>

    {{-- Beside a selection: comment on it ------------------------------------ --}}
    <button
        type="button"
        class="nk-comment-fab"
        x-show="commentFab.show && !draftId"
        x-cloak
        title="{{ $t('AddComment', 'Add comment') }} (Ctrl+Alt+M)"
        x-bind:style="{ left: `${commentFab.left}px`, top: `${commentFab.top}px` }"
        x-on:mousedown.prevent
        x-on:click="startComment()"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/><path d="M12 8v6M9 11h6"/></svg>
        <span class="sr-only">{{ $t('AddComment', 'Add comment') }}</span>
    </button>

    {{-- Comments and suggestions, beside the pages ------------------------- --}}
    <aside class="nk-review" x-show="reviewOpen" x-cloak aria-label="{{ $t('ReviewPanel', 'Comments and suggestions') }}">
        <div class="nk-review-head">
            <span class="nk-review-title">{{ $t('ReviewPanel', 'Comments and suggestions') }}</span>
            <span class="nk-review-count" x-show="openCount()" x-text="openCount()"></span>
            <button type="button" class="nk-review-close" x-on:click="toggleReview(false)" aria-label="{{ $t('Close', 'Close') }}">×</button>
        </div>

        <div class="nk-review-tools">
            <label class="nk-review-check">
                <input type="checkbox" x-model="showResolved">
                <span>{{ $t('ShowResolved', 'Show resolved') }}</span>
            </label>
            <div class="nk-review-all" x-show="suggestions.length && canReview">
                <button type="button" class="nk-review-link" x-on:click="settleAll(true)">{{ $t('AcceptAll', 'Accept all') }}</button>
                <button type="button" class="nk-review-link" x-on:click="settleAll(false)">{{ $t('RejectAll', 'Reject all') }}</button>
            </div>
        </div>

        <div class="nk-review-list" x-ref="reviewList">
            {{-- A new comment, while it is being written. --}}
            <article class="nk-review-card is-active" x-show="draftId" x-cloak>
                <div class="nk-review-meta">
                    <span class="nk-review-avatar" x-text="initialsOf(authorName())"></span>
                    <span class="nk-review-name" x-text="authorName()"></span>
                </div>
                <textarea
                    x-ref="draftInput"
                    x-model="draftText"
                    rows="2"
                    class="nk-editor-field nk-review-input"
                    placeholder="{{ $t('CommentPlaceholder', 'Add a comment… @ to mention someone') }}"
                    x-on:input="commentInput($event, 'draft')"
                    x-on:keydown="commentKeys($event, 'draft')"
                    x-on:blur="commentMention = null"
                ></textarea>
                <div class="nk-comment-people" role="listbox" x-show="commentMention && commentMention.field === 'draft' && commentPeople().length" x-cloak x-on:mousedown.prevent>
                    <template x-for="(person, index) in commentPeople()" :key="person.id">
                        <button type="button" class="nk-comment-person" role="option" x-bind:aria-selected="index === commentMentionIndex ? 'true' : 'false'" x-on:click.stop="pickCommentPerson(person)">
                            <span class="nk-review-avatar" x-text="initialsOf(person.name)"></span>
                            <span class="nk-comment-person-name" x-text="person.name"></span>
                            <span class="nk-comment-person-email" x-text="person.email || ''"></span>
                        </button>
                    </template>
                </div>
                <div class="nk-review-buttons">
                    <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="cancelDraft()">{{ $t('Cancel', 'Cancel') }}</button>
                    <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-bind:disabled="!draftText.trim()" x-on:click="saveDraft()">{{ $t('CommentVerb', 'Comment') }}</button>
                </div>
            </article>

            <template x-for="item in reviewItems()" :key="item.key">
                <article
                    class="nk-review-card"
                    x-bind:data-review="item.key"
                    x-bind:class="{ 'is-active': activeReview === item.key, 'is-resolved': item.thread && item.thread.resolved }"
                    x-on:click="focusReview(item.key)"
                >
                    {{-- A comment thread. --}}
                    <template x-if="item.thread">
                        <div>
                            <p class="nk-review-quote" x-show="item.thread.quote" x-text="item.thread.quote"></p>
                            <p class="nk-review-orphan" x-show="item.orphan">{{ $t('CommentOrphan', 'The commented text was deleted.') }}</p>
                            <template x-for="(comment, index) in item.thread.comments" :key="index">
                                <div class="nk-review-comment">
                                    <div class="nk-review-meta">
                                        <span class="nk-review-avatar" x-text="initialsOf(comment.author)"></span>
                                        <span class="nk-review-name" x-text="comment.author"></span>
                                        <span class="nk-review-time" x-text="when(comment.time)"></span>
                                        <span class="nk-review-edited" x-show="comment.edited">{{ $t('CommentEdited', '(edited)') }}</span>
                                        {{-- One's own comments can be changed or deleted. --}}
                                        <span class="nk-review-own" x-show="isMine(comment) && !isEditing(item.key, index)">
                                            <button type="button" class="nk-review-mini" title="{{ $t('CommentEdit', 'Edit') }}" x-on:click.stop="startEdit(item.key, index)">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>
                                                <span class="sr-only">{{ $t('CommentEdit', 'Edit') }}</span>
                                            </button>
                                            <button type="button" class="nk-review-mini" title="{{ $t('CommentDelete', 'Delete') }}" x-on:click.stop="deleteComment(item.key, index)">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                                <span class="sr-only">{{ $t('CommentDelete', 'Delete') }}</span>
                                            </button>
                                        </span>
                                    </div>
                                    <p class="nk-review-text" x-show="!isEditing(item.key, index)"><template x-for="(run, at) in commentRuns(comment)" :key="at"><span x-bind:class="run.mention && 'nk-review-mention'" x-text="run.text"></span></template></p>
                                    <div class="nk-review-edit" x-show="isEditing(item.key, index)" x-on:click.stop>
                                        <textarea
                                            x-model="editText"
                                            rows="2"
                                            class="nk-editor-field nk-review-input"
                                            x-on:input="commentInput($event, 'edit')"
                                            x-on:keydown="commentKeys($event, 'edit')"
                                            x-on:blur="commentMention = null"
                                        ></textarea>
                                        <div class="nk-comment-people" role="listbox" x-show="commentMention && commentMention.field === 'edit' && commentPeople().length" x-cloak x-on:mousedown.prevent>
                    <template x-for="(person, index) in commentPeople()" :key="person.id">
                        <button type="button" class="nk-comment-person" role="option" x-bind:aria-selected="index === commentMentionIndex ? 'true' : 'false'" x-on:click.stop="pickCommentPerson(person)">
                            <span class="nk-review-avatar" x-text="initialsOf(person.name)"></span>
                            <span class="nk-comment-person-name" x-text="person.name"></span>
                            <span class="nk-comment-person-email" x-text="person.email || ''"></span>
                        </button>
                    </template>
                </div>
                                        <div class="nk-review-buttons">
                                            <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="cancelEdit()">{{ $t('Cancel', 'Cancel') }}</button>
                                            <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-bind:disabled="!editText.trim()" x-on:click="saveEdit()">{{ $t('CommentSave', 'Save') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div class="nk-review-actions" x-show="canReview">
                                <button type="button" class="nk-review-icon" x-show="!item.thread.resolved" title="{{ $t('Resolve', 'Resolve') }}" x-on:click.stop="resolveThread(item.key, true)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                    <span class="sr-only">{{ $t('Resolve', 'Resolve') }}</span>
                                </button>
                                <button type="button" class="nk-review-link" x-show="item.thread.resolved" x-on:click.stop="resolveThread(item.key, false)">{{ $t('Reopen', 'Reopen') }}</button>
                                <button type="button" class="nk-review-icon" title="{{ $t('DeleteThread', 'Delete thread') }}" x-on:click.stop="deleteThread(item.key)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                    <span class="sr-only">{{ $t('DeleteThread', 'Delete thread') }}</span>
                                </button>
                            </div>
                            <div class="nk-review-reply" x-show="canReview && activeReview === item.key && !item.thread.resolved" x-on:click.stop>
                                <textarea
                                    x-model="replyText"
                                    rows="1"
                                    class="nk-editor-field nk-review-input"
                                    placeholder="{{ $t('ReplyPlaceholder', 'Reply… @ to mention someone') }}"
                                    x-on:input="commentInput($event, 'reply')"
                                    x-on:keydown="commentKeys($event, 'reply', item.key)"
                                    x-on:blur="commentMention = null"
                                ></textarea>
                                <div class="nk-comment-people" role="listbox" x-show="commentMention && commentMention.field === 'reply' && commentPeople().length" x-cloak x-on:mousedown.prevent>
                    <template x-for="(person, index) in commentPeople()" :key="person.id">
                        <button type="button" class="nk-comment-person" role="option" x-bind:aria-selected="index === commentMentionIndex ? 'true' : 'false'" x-on:click.stop="pickCommentPerson(person)">
                            <span class="nk-review-avatar" x-text="initialsOf(person.name)"></span>
                            <span class="nk-comment-person-name" x-text="person.name"></span>
                            <span class="nk-comment-person-email" x-text="person.email || ''"></span>
                        </button>
                    </template>
                </div>
                                <div class="nk-review-buttons" x-show="replyText.trim()">
                                    <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="replyText = ''">{{ $t('Cancel', 'Cancel') }}</button>
                                    <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="reply(item.key)">{{ $t('ReplyVerb', 'Reply') }}</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- A suggested change. --}}
                    <template x-if="item.suggestion">
                        <div>
                            <div class="nk-review-meta">
                                <span class="nk-review-avatar is-suggestion" x-text="initialsOf(item.suggestion.author)"></span>
                                <span class="nk-review-name" x-text="item.suggestion.author"></span>
                                <span class="nk-review-time" x-text="when(item.suggestion.time)"></span>
                            </div>
                            <p class="nk-review-change">
                                <span x-show="item.suggestion.kind === 'insert'">{{ $t('SuggestAdd', 'Add') }} <ins x-text="item.suggestion.inserted"></ins></span>
                                <span x-show="item.suggestion.kind === 'delete'">{{ $t('SuggestDelete', 'Delete') }} <del x-text="item.suggestion.deleted"></del></span>
                                <span x-show="item.suggestion.kind === 'replace'">{{ $t('SuggestReplace', 'Replace') }} <del x-text="item.suggestion.deleted"></del> {{ $t('SuggestWith', 'with') }} <ins x-text="item.suggestion.inserted"></ins></span>
                                {{-- Formatting, a paragraph's look, a new paragraph or two joined: what, then on which text. --}}
                                <span x-show="['format', 'style', 'split', 'join'].includes(item.suggestion.kind)">
                                    <span class="nk-review-what" x-text="suggestionLabel(item.suggestion)"></span>
                                    <span class="nk-review-subject" x-show="item.suggestion.inserted" x-text="item.suggestion.inserted"></span>
                                </span>
                            </p>
                            <div class="nk-review-actions" x-show="canReview">
                                <button type="button" class="nk-review-icon is-accept" title="{{ $t('Accept', 'Accept') }}" x-on:click.stop="settleSuggestion(item.key, true)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                    <span class="sr-only">{{ $t('Accept', 'Accept') }}</span>
                                </button>
                                <button type="button" class="nk-review-icon is-reject" title="{{ $t('Reject', 'Reject') }}" x-on:click.stop="settleSuggestion(item.key, false)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                    <span class="sr-only">{{ $t('Reject', 'Reject') }}</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </article>
            </template>

            <p class="nk-review-empty" x-show="!draftId && !reviewItems().length">{{ $t('ReviewEmpty', 'No comments or suggestions. Select text and press Ctrl+Alt+M to comment.') }}</p>
        </div>
    </aside>

    {{-- Special characters and emoji, put in at the caret ------------------ --}}
    @php
        $charGroups = [
            'recent' => $t('CharsRecent', 'Recent'),
            'emoji' => $t('CharsEmoji', 'Emoji'),
            'symbols' => $t('CharsSymbols', 'Symbols'),
            'punctuation' => $t('CharsPunctuation', 'Punctuation'),
            'currency' => $t('CharsCurrency', 'Currency'),
            'math' => $t('CharsMath', 'Maths'),
            'arrows' => $t('CharsArrows', 'Arrows'),
            'letters' => $t('CharsLetters', 'Letters'),
            'greek' => $t('CharsGreek', 'Greek'),
        ];
    @endphp
    <div class="nk-chars" x-show="charsOpen" x-cloak role="dialog" aria-label="{{ $t('SpecialCharacters', 'Special characters') }}" x-on:keydown.escape.stop="charsOpen = false">
        <div class="nk-chars-head">
            <span>{{ $t('SpecialCharacters', 'Special characters') }}</span>
            <button type="button" class="nk-review-close" x-on:click="charsOpen = false" aria-label="{{ $t('Close', 'Close') }}">×</button>
        </div>
        <input
            type="search"
            x-ref="charsSearch"
            class="nk-editor-field nk-chars-search"
            x-model="charsQuery"
            placeholder="{{ $t('SearchCharacters', 'Search: euro, arrow, heart…') }}"
            aria-label="{{ $t('SearchCharacters', 'Search: euro, arrow, heart…') }}"
        >
        <div class="nk-chars-groups" role="group" x-show="!charsQuery.trim()">
            @foreach($charGroups as $group => $label)
                <button
                    type="button"
                    @if($group === 'recent') x-show="charsRecent.length" @endif
                    x-bind:aria-pressed="charsGroup === '{{ $group }}' ? 'true' : 'false'"
                    x-on:click="charsGroup = '{{ $group }}'"
                >{{ $label }}</button>
            @endforeach
        </div>
        <div class="nk-chars-grid" x-on:mousedown.prevent>
            <template x-for="entry in characterList()" :key="entry.group + entry.char">
                <button type="button" class="nk-chars-key" x-bind:title="entry.name" x-bind:aria-label="entry.name" x-on:click="insertCharacter(entry.char)" x-text="characterFace(entry.char)"></button>
            </template>
            <p class="nk-chars-empty" x-show="!characterList().length">{{ $t('CharsNone', 'Nothing matches.') }}</p>
        </div>
    </div>

    {{-- Voice typing: speak, the text goes in at the caret ------------------ --}}
    <div class="nk-voice" x-show="voiceOpen" x-cloak role="dialog" aria-label="{{ $t('VoiceTyping', 'Voice typing') }}" x-on:mousedown.prevent>
        <button
            type="button"
            class="nk-voice-mic"
            x-bind:aria-pressed="voiceListening ? 'true' : 'false'"
            x-bind:title="voiceListening ? @js($t('VoiceStop', 'Stop')) : @js($t('VoiceStart', 'Speak'))"
            x-bind:disabled="!voiceSupported"
            x-on:click="voiceListening ? stopVoice() : startVoice()"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>
            <span class="sr-only" x-text="voiceListening ? @js($t('VoiceStop', 'Stop')) : @js($t('VoiceStart', 'Speak'))"></span>
        </button>
        <div class="nk-voice-body">
            <span class="nk-voice-state" x-text="voiceError || (voiceListening ? voiceHint() : @js($t('VoiceIdle', 'Click the microphone and speak')))"></span>
            <span class="nk-voice-heard" x-show="voiceInterim" x-text="voiceInterim"></span>
            <select class="nk-voice-lang" x-bind:value="voiceLang" x-on:change="setVoiceLang($event.target.value)" aria-label="{{ $t('Language', 'Language') }}">
                @foreach(['fr-FR' => 'Français', 'en-US' => 'English (US)', 'en-GB' => 'English (UK)', 'es-ES' => 'Español', 'de-DE' => 'Deutsch', 'it-IT' => 'Italiano', 'pt-PT' => 'Português', 'nl-NL' => 'Nederlands', 'ar-SA' => 'العربية'] as $code => $language)
                    <option value="{{ $code }}">{{ $language }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="nk-review-close" x-on:click="closeVoice()" aria-label="{{ $t('Close', 'Close') }}">×</button>
    </div>

    {{-- A picked table of contents: its style, or take it away ------------- --}}
    <div
        class="nk-toc-bar"
        x-ref="tocBar"
        x-show="tocOpen"
        x-cloak
        role="toolbar"
        aria-label="{{ $t('TableOfContents', 'Table of contents') }}"
        x-bind:style="{ left: `${tocBar.left}px`, top: `${tocBar.top}px` }"
        x-on:mousedown.prevent
    >
        <button type="button" class="nk-tool nk-image-preset" x-bind:aria-pressed="tocStyle === 'pages' ? 'true' : 'false'" x-on:click="setTocStyle('pages')">{{ $t('TocPages', 'With page numbers') }}</button>
        <button type="button" class="nk-tool nk-image-preset" x-bind:aria-pressed="tocStyle === 'links' ? 'true' : 'false'" x-on:click="setTocStyle('links')">{{ $t('TocLinks', 'With blue links') }}</button>
        <div class="nk-tool-divider" aria-hidden="true"></div>
        <button type="button" class="nk-tool nk-image-delete" title="{{ $t('TocDelete', 'Delete table of contents') }}" x-on:click="deleteToc()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
            <span class="sr-only">{{ $t('TocDelete', 'Delete table of contents') }}</span>
        </button>
    </div>

    </div>

    {{-- Version history: a version to read on the left, the versions on the right -- --}}
    <div class="nk-history" x-show="historyOpen" x-cloak role="dialog" aria-modal="true" aria-label="{{ $t('VersionHistory', 'Version history') }}">
        <div class="nk-history-bar">
            <button type="button" class="nk-history-back" x-on:click="closeHistory()" aria-label="{{ $t('BackToDocument', 'Back to the document') }}" title="{{ $t('BackToDocument', 'Back to the document') }}">
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M12.5 8h-9M7 4 3 8l4 4"/></svg>
            </button>
            <div class="nk-history-heading">
                <span class="nk-history-title" x-text="versionTitle(versions.find((version) => version.id === selectedVersion))"></span>
                <span class="nk-history-subtitle" x-text="versionSubtitle()"></span>
            </div>
            <label class="nk-history-toggle">
                <input type="checkbox" x-bind:checked="historyDiff" x-on:change="toggleHistoryDiff()">
                <span>{{ $t('ShowChanges', 'Show changes') }}</span>
            </label>
            <button
                type="button"
                class="nk-editor-btn nk-editor-btn-primary"
                x-bind:disabled="!canReview || !versions.length || selectedVersion === versions[0].id"
                x-on:click="restoreVersion()"
            >{{ $t('RestoreVersion', 'Restore this version') }}</button>
        </div>

        <div class="nk-history-main">
            <div class="nk-history-canvas">
                <div class="nk-history-sheet">
                    <div class="nk-page-body nk-history-doc" x-ref="historyDoc" x-bind:aria-busy="historyBusy ? 'true' : 'false'"></div>
                    <div class="nk-history-legend" x-show="historyDiff && versions.findIndex((version) => version.id === selectedVersion) < versions.length - 1">
                        <span><span class="nk-diff-ins">{{ $t('LegendAdded', 'added') }}</span> {{ $t('DiffAdded', 'text added in this version') }}</span>
                        <span><span class="nk-diff-del">{{ $t('LegendRemoved', 'removed') }}</span> {{ $t('DiffRemoved', 'text removed') }}</span>
                    </div>
                </div>
            </div>

            <aside class="nk-history-side" aria-label="{{ $t('Versions', 'Versions') }}">
                <div class="nk-history-side-head">{{ $t('VersionHistory', 'Version history') }}</div>
                <div class="nk-history-filter" role="group" aria-label="{{ $t('Filter', 'Filter') }}">
                    <button type="button" x-bind:aria-pressed="historyNamed ? 'false' : 'true'" x-on:click="historyNamed = false">{{ $t('AllVersions', 'All') }}</button>
                    <button type="button" x-bind:aria-pressed="historyNamed ? 'true' : 'false'" x-on:click="historyNamed = true">{{ $t('NamedVersions', 'Named versions') }}</button>
                </div>
                <div class="nk-history-list" x-ref="historyList">
                    <template x-for="group in versionGroups()" :key="group.label">
                        <div>
                            <span class="nk-history-day" x-text="group.label"></span>
                            <template x-for="version in group.items" :key="version.id">
                                <div class="nk-history-item" x-bind:class="{ 'is-selected': version.id === selectedVersion }">
                                    <button
                                        type="button"
                                        class="nk-history-pick"
                                        x-bind:aria-pressed="version.id === selectedVersion ? 'true' : 'false'"
                                        x-on:click="selectVersion(version.id)"
                                    >
                                        <span class="nk-history-row">
                                            <span class="nk-history-name" x-bind:class="{ 'is-named': version.name }" x-text="versionTitle(version)"></span>
                                            <span class="nk-history-current" x-show="version.id === versions[0].id">{{ $t('CurrentVersion', 'Current') }}</span>
                                        </span>
                                        <span class="nk-history-when" x-show="version.name" x-text="versionTime(version.time)"></span>
                                        <span class="nk-history-authors">
                                            <template x-for="name in version.authors" :key="name">
                                                <span><span class="nk-history-dot" x-bind:style="{ background: authorColor(name) }"></span><span x-text="name"></span></span>
                                            </template>
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="nk-history-rename"
                                        x-show="canReview"
                                        title="{{ $t('NameVersion', 'Name this version') }}"
                                        aria-label="{{ $t('NameVersion', 'Name this version') }}"
                                        x-on:click="renameVersion(version.id)"
                                    >
                                        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10.5 2.5l3 3-8 8h-3v-3z"/></svg>
                                    </button>
                                    <template x-if="renamingVersion === version.id">
                                        <div class="nk-history-naming">
                                            <input
                                                type="text"
                                                data-version-name
                                                class="nk-editor-field"
                                                x-model="versionDraft"
                                                maxlength="120"
                                                placeholder="{{ $t('VersionName', 'Version name') }}"
                                                aria-label="{{ $t('VersionName', 'Version name') }}"
                                                x-on:keydown.enter.prevent="saveVersionName(version.id)"
                                                x-on:keydown.escape.stop="renamingVersion = ''"
                                            >
                                            <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="saveVersionName(version.id)">OK</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <p class="nk-history-empty" x-show="!versions.length && !historyBusy">{{ $t('NoVersions', 'No versions yet: they are recorded as you write.') }}</p>
                </div>
            </aside>
        </div>
    </div>

    {{-- "/" menu: opens where a slash is typed at the start of a line or after a space --}}
    <div
        x-show="slashOpen"
        x-cloak
        x-ref="slashMenu"
        role="listbox"
        aria-label="{{ $t('SlashInsert', 'Insert') }}"
        class="nk-slash nk-editor-menu"
        x-bind:style="{ left: `${slashX}px`, top: `${slashY}px` }"
        x-on:mousedown.prevent
        x-on:click.outside="closeSlash()"
    >
        <template x-for="item in slashItems()" :key="item.id">
            <div>
                <p class="nk-slash-group" x-show="item.heading" x-text="item.heading"></p>
                <button
                    type="button"
                    role="option"
                    class="nk-slash-item"
                    x-bind:aria-selected="item.index === slashIndex ? 'true' : 'false'"
                    x-on:mouseenter="slashIndex = item.index"
                    x-on:click="runSlash(item.id)"
                >
                    <span class="nk-slash-icon" x-show="!item.initials"><svg viewBox="0 0 16 16" aria-hidden="true"><path x-bind:d="item.icon"/></svg></span>
                    <span class="nk-slash-avatar" x-show="item.initials" aria-hidden="true">
                        <img x-show="item.avatar" x-bind:src="item.avatar || ''" alt="">
                        <span x-show="!item.avatar" x-text="item.initials"></span>
                    </span>
                    <span class="nk-slash-text">
                        <span x-text="item.label"></span>
                        <small x-show="item.sub" x-text="item.sub"></small>
                    </span>
                </button>
            </div>
        </template>
        <p class="nk-slash-empty" x-show="!slashItems().length" x-text="slashTrigger === '@' ? @js($t('ChipEmpty', 'No one or no date matches')) : @js($t('SlashEmpty', 'No command'))"></p>
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
                    {{ $t('Page', 'Page') }} <span class="font-medium text-fg" x-text="activePage"></span>
                    <span class="opacity-50">/</span>
                    <span x-text="pageCount"></span>
                </span>
                <button type="button" class="nk-status-chip hover:text-fg" x-on:click="openWordCount()" title="{{ $t('WordCount', 'Word count') }} (Ctrl+Shift+C)"><span class="font-medium text-fg" x-text="words"></span> {{ $t('Words', 'words') }}</button>
                <span class="nk-status-chip"><span class="font-medium text-fg" x-text="characters"></span> {{ $t('Chars', 'chars') }}</span>
            </div>

            <div class="flex items-center gap-2">
                <span x-show="busy" x-cloak class="nk-status-chip">{{ $t('Converting', 'Converting…') }}</span>
                <span x-show="historyNotice" x-cloak class="nk-status-chip text-fg" role="status" x-text="historyNotice"></span>
                <button
                    type="button"
                    class="nk-status-chip hover:text-fg"
                    x-show="versions.length"
                    x-cloak
                    x-on:click="openHistory()"
                    title="{{ $t('SeeVersionHistory', 'See version history') }} (Ctrl+Alt+Shift+H)"
                >{{ $t('LastVersion', 'Last version') }} <span class="font-medium text-fg" x-text="versions.length ? versionTime(versions[0].time) : ''"></span></button>
                <span
                    x-show="conversionError"
                    x-cloak
                    class="max-w-xs truncate rounded-md bg-red-500/10 px-2 py-0.5 text-red-600 dark:text-red-400"
                    x-text="conversionError"
                    x-on:click="conversionError = ''"
                    title="{{ $t('Dismiss', 'Click to dismiss') }}"
                ></span>
                <button
                    type="button"
                    class="nk-status-chip hover:text-fg"
                    x-on:click="togglePagination()"
                    x-text="paginated ? @js($t('Paged', 'Paged')) : @js($t('Continuous', 'Continuous'))"
                    title="{{ $t('TogglePagination', 'Pages or continuous') }}"
                ></button>
                <select
                    class="nk-status-select"
                    x-bind:value="zoomMode === 'fit' ? 'fit' : zoom"
                    x-on:change="setZoom($event.target.value === 'fit' ? 'fit' : Number($event.target.value))"
                    aria-label="{{ $t('Zoom', 'Zoom') }}"
                >
                    <option value="fit">{{ $t('Fit', 'Fit') }}</option>
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
            <h3>{{ $t('InsertTableTitle', 'Insert table') }}</h3>

            <div class="mb-3 grid grid-cols-2 gap-3">
                <label class="block">
                    <span class="mb-1 block text-xs text-fg-muted">{{ $t('Rows', 'Rows') }}</span>
                    <input
                        type="number" min="1" max="50" step="1"
                        class="nk-editor-field"
                        x-model.number="tableDialogRows"
                        x-on:keydown.enter.prevent="confirmTableDialog()"
                    >
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-fg-muted">{{ $t('Columns', 'Columns') }}</span>
                    <input
                        type="number" min="1" max="20" step="1"
                        class="nk-editor-field"
                        x-model.number="tableDialogCols"
                        x-on:keydown.enter.prevent="confirmTableDialog()"
                    >
                </label>
            </div>

            {{-- Same numbers, pickable by hovering the grid. --}}
            <p class="mb-1.5 text-xs text-fg-muted">{{ $t('PickSize', 'Or pick a size') }}</p>
            <div
                class="mb-4 grid grid-cols-10 gap-0.5 rounded-md border border-edge/70 p-1.5"
                style="background: color-mix(in srgb, var(--nk-canvas) 55%, transparent)"
                x-on:mouseleave="setGrid(0, 0)"
            >
                @for($row = 1; $row <= 8; $row++)
                    @for($col = 1; $col <= 10; $col++)
                        <button
                            type="button"
                            class="size-4 rounded-[2px] border border-edge bg-surface transition-colors duration-fast ease-standard hover:border-primary/60 hover:bg-primary/25"
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
                    <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="showTableDialog = false">{{ $t('Cancel', 'Cancel') }}</button>
                    <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="confirmTableDialog()">{{ $t('Insert', 'Insert') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table context menu (right-click inside a table) ------------------- --}}
    {{-- The point it grows out of: the pointer, not some earlier element. --}}
    <span
        class="nk-menu-anchor"
        data-menu-trigger
        aria-hidden="true"
        x-bind:style="{ left: `${tableMenuAnchor.x}px`, top: `${tableMenuAnchor.y}px` }"
    ></span>
    <div
        x-show="tableMenuOpen"
        x-cloak
        data-align="start"
        x-transition:enter="nk-menu-motion"
        x-transition:enter-start="nk-menu-from"
        x-transition:enter-end="nk-menu-to"
        x-transition:leave="nk-menu-leaving"
        x-transition:leave-start="nk-menu-to"
        x-transition:leave-end="nk-menu-out"
        class="nk-menu-motion nk-editor-menu nk-table-menu absolute z-40"
        x-ref="tableMenu"
        x-bind:data-side="tableMenuSide"
        x-bind:style="{ left: `${tableMenuX}px`, top: `${tableMenuY}px` }"
        x-on:click.outside="tableMenuOpen = false"
        x-on:nk-unfold="$nextTick(() => placeTableMenu())"
    >
        <p class="nk-table-menu-note">
            <template x-if="tableInfo()">
                <span>
                    {{ $t('RowWord', 'Row') }} <span x-text="tableInfo().rowIndex + 1"></span> {{ $t('Of', 'of') }} <span x-text="tableInfo().rows"></span>,
                    {{ $t('ColumnWord', 'column') }} <span x-text="tableInfo().column + 1"></span> {{ $t('Of', 'of') }} <span x-text="tableInfo().columns"></span>
                </span>
            </template>
        </p>

        <neura::editor.variants.native.table-actions close="tableMenuOpen = false" />
    </div>

    {{-- Link editor ------------------------------------------------------ --}}
    <span class="nk-menu-anchor is-link" data-menu-trigger aria-hidden="true"></span>
    <div
        x-show="linkOpen"
        x-cloak
        data-side="top"
        data-align="center"
        x-transition:enter="nk-menu-motion"
        x-transition:enter-start="nk-menu-from"
        x-transition:enter-end="nk-menu-to"
        x-transition:leave="nk-menu-leaving"
        x-transition:leave-start="nk-menu-to"
        x-transition:leave-end="nk-menu-out"
        class="nk-menu-motion absolute inset-x-0 bottom-12 z-30 mx-auto w-full max-w-md"
        x-on:click.outside="linkOpen = false"
    >
        <div class="nk-editor-dialog mx-auto w-full max-w-md">
            <label class="mb-1.5 block text-xs font-medium text-fg-muted" for="{{ $name ?? 'nk' }}-link">
                {{ $t('LinkShort', 'Link') }}
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
                >{{ $t('Apply', 'Apply') }}</button>
                <button
                    type="button"
                    class="nk-editor-btn nk-editor-btn-ghost"
                    x-on:click="removeLink()"
                >{{ $t('Remove', 'Remove') }}</button>
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
            <h3>{{ $t('PageSetupTitle', 'Page setup') }}</h3>

            <label class="mb-1 block text-xs text-fg-muted">{{ $t('PaperSize', 'Paper size') }}</label>
            <select class="nk-editor-field mb-3" x-model="pageSize">
                <option value="a4">A4 (210 × 297 mm)</option>
                <option value="letter">Letter (8.5 × 11 in)</option>
                <option value="legal">Legal (8.5 × 14 in)</option>
            </select>

            <label class="mb-1 block text-xs text-fg-muted">{{ $t('Orientation', 'Orientation') }}</label>
            <select class="nk-editor-field mb-3" x-model="orientation">
                <option value="portrait">{{ $t('Portrait', 'Portrait') }}</option>
                <option value="landscape">{{ $t('Landscape', 'Landscape') }}</option>
            </select>

            <label class="mb-1 block text-xs text-fg-muted">{{ $t('Columns', 'Columns') }}</label>
            <select class="nk-editor-field mb-3" x-model.number="columns">
                <option value="1">{{ $t('ColumnsOne', 'One column') }}</option>
                <option value="2">{{ $t('ColumnsTwo', 'Two columns') }}</option>
                <option value="3">{{ $t('ColumnsThree', 'Three columns') }}</option>
            </select>

            <label class="mb-3 flex cursor-pointer items-center gap-2 text-xs text-fg">
                <input type="checkbox" class="size-3.5 accent-primary" x-bind:checked="differentFirstPage" x-on:change="toggleFirstPage()">
                {{ $t('DifferentFirstPage', 'Different first page') }}
            </label>

            <label class="mb-1 block text-xs text-fg-muted">{{ $t('Margins', 'Margins (inches)') }}</label>
            <div class="mb-4 grid grid-cols-2 gap-2">
                @foreach(['top' => $t('Top', 'Top'), 'bottom' => $t('Bottom', 'Bottom'), 'left' => $t('Left', 'Left'), 'right' => $t('Right', 'Right')] as $side => $label)
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
                <button type="button" class="nk-editor-btn nk-editor-btn-ghost" x-on:click="showPageSetup = false">{{ $t('Cancel', 'Cancel') }}</button>
                <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="applyPageSetup()">{{ $t('Apply', 'Apply') }}</button>
            </div>
        </div>
    </div>

    {{-- Keyboard shortcuts: Ctrl+/ ----------------------------------------- --}}
    @php
        $shortcutGroups = [
            $t('KeysText', 'Text') => [
                [['Ctrl', 'B'], $t('Bold', 'Bold')],
                [['Ctrl', 'I'], $t('Italic', 'Italic')],
                [['Ctrl', 'U'], $t('Underline', 'Underline')],
                [['Ctrl', 'K'], $t('InsertLink', 'Insert link')],
                [['Ctrl', '\\'], $t('ClearFormatting', 'Clear formatting')],
                [['Ctrl', 'Alt', '0'], $t('StyleParagraph', 'Normal text')],
                [['Ctrl', 'Alt', '1…4'], $t('KeysHeadings', 'Heading 1 to 4')],
            ],
            $t('KeysParagraph', 'Paragraphs and lists') => [
                [['Ctrl', 'Shift', '7'], $t('NumberedList', 'Numbered list')],
                [['Ctrl', 'Shift', '8'], $t('BulletList', 'Bulleted list')],
                [['Ctrl', 'Shift', '9'], $t('Checklist', 'Checklist')],
                [['Tab'], $t('Indent', 'Increase indent')],
                [['Shift', 'Tab'], $t('Outdent', 'Decrease indent')],
            ],
            $t('KeysInsert', 'Insert') => [
                [['Ctrl', 'Enter'], $t('PageBreak', 'Page break')],
                [['Shift', 'Enter'], $t('LineBreak', 'Line break')],
                [['/'], $t('KeysSlash', 'Insert menu, at the start of a line')],
                [['@'], $t('Mention', 'Person or date')],
                [['Ctrl', 'Alt', 'F'], $t('Footnote', 'Footnote')],
                [['Ctrl', 'Alt', 'M'], $t('AddComment', 'Add comment')],
            ],
            $t('KeysTools', 'Edit and tools') => [
                [['Ctrl', 'Z'], $t('Undo', 'Undo')],
                [['Ctrl', 'Y'], $t('Redo', 'Redo')],
                [['Ctrl', 'F'], $t('Find', 'Find')],
                [['Ctrl', 'H'], $t('FindReplace', 'Find and replace')],
                [['Ctrl', 'Shift', 'C'], $t('WordCount', 'Word count')],
                [['Ctrl', 'Shift', 'S'], $t('VoiceTyping', 'Voice typing')],
                [['Ctrl', 'Alt', 'Shift', 'H'], $t('VersionHistory', 'Version history')],
                [['Ctrl', 'P'], $t('Print', 'Print')],
                [['Ctrl', '/'], $t('KeyboardShortcuts', 'Keyboard shortcuts')],
            ],
            $t('KeysReview', 'Review') => [
                [['Ctrl', 'Alt', 'Shift', 'Z'], $t('ModeEditing', 'Editing')],
                [['Ctrl', 'Alt', 'Shift', 'X'], $t('ModeSuggesting', 'Suggesting')],
                [['Ctrl', 'Alt', 'Shift', 'C'], $t('ModeViewing', 'Viewing')],
            ],
        ];
    @endphp
    <div
        x-show="shortcutsOpen"
        x-cloak
        x-transition.opacity
        class="nk-editor-backdrop"
        x-on:click.self="shortcutsOpen = false"
    >
        <div class="nk-editor-dialog nk-shortcuts" role="dialog" aria-label="{{ $t('KeyboardShortcuts', 'Keyboard shortcuts') }}" x-on:keydown.escape.stop="shortcutsOpen = false">
            <div class="nk-chars-head">
                <h3>{{ $t('KeyboardShortcuts', 'Keyboard shortcuts') }}</h3>
                <button type="button" class="nk-review-close" x-on:click="shortcutsOpen = false" aria-label="{{ $t('Close', 'Close') }}">×</button>
            </div>
            <input
                type="search"
                class="nk-editor-field nk-chars-search"
                x-model="shortcutsQuery"
                x-effect="shortcutsOpen && $nextTick(() => $el.focus())"
                placeholder="{{ $t('SearchShortcuts', 'Search shortcuts') }}"
                aria-label="{{ $t('SearchShortcuts', 'Search shortcuts') }}"
            >
            <div class="nk-shortcuts-list">
                @foreach($shortcutGroups as $group => $rows)
                    <section x-show="{{ collect($rows)->map(fn ($row) => 'shortcutShown('.\Illuminate\Support\Js::from($row[1].' '.implode(' ', $row[0])).')')->implode(' || ') }}">
                        <h4>{{ $group }}</h4>
                        @foreach($rows as [$keys, $label])
                            <div class="nk-shortcut" x-show="shortcutShown({{ \Illuminate\Support\Js::from($label.' '.implode(' ', $keys)) }})">
                                <span>{{ $label }}</span>
                                <span class="nk-shortcut-keys">
                                    @foreach($keys as $key)
                                        <kbd x-text="keyName({{ \Illuminate\Support\Js::from($key) }})">{{ $key }}</kbd>
                                    @endforeach
                                </span>
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Word count: Tools menu, the status bar or Ctrl+Shift+C --}}
    <div
        x-show="showWordCount"
        x-cloak
        x-transition.opacity
        class="nk-editor-backdrop"
        x-on:click.self="showWordCount = false"
    >
        <div class="nk-editor-dialog" role="dialog" aria-label="{{ $t('WordCount', 'Word count') }}" style="width: min(100% - 2rem, 21rem)">
            <h3>{{ $t('WordCount', 'Word count') }}</h3>
            <dl class="nk-wordcount">
                <dt>{{ $t('PagesLabel', 'Pages') }}</dt><dd x-text="formatNumber(wordStats.pages)"></dd>
                <dt>{{ $t('WordsLabel', 'Words') }}</dt><dd><span x-show="wordStats.hasSelection" x-text="formatNumber(wordStats.selectionWords) + ' / '"></span><span x-text="formatNumber(wordStats.words)"></span></dd>
                <dt>{{ $t('Characters', 'Characters') }}</dt><dd><span x-show="wordStats.hasSelection" x-text="formatNumber(wordStats.selectionCharacters) + ' / '"></span><span x-text="formatNumber(wordStats.characters)"></span></dd>
                <dt>{{ $t('CharactersNoSpaces', 'Characters excluding spaces') }}</dt><dd x-text="formatNumber(wordStats.charactersNoSpaces)"></dd>
                <dt>{{ $t('ReadingTime', 'Reading time') }}</dt><dd x-text="Math.max(1, Math.round(wordStats.words / 230)) + ' min'"></dd>
            </dl>
            <p class="mb-3 text-xs text-fg-muted" x-show="wordStats.hasSelection">{{ $t('SelectionOfDocument', 'Selection / whole document') }}</p>
            <div class="flex justify-end">
                <button type="button" class="nk-editor-btn nk-editor-btn-primary" x-on:click="showWordCount = false">{{ $t('Close', 'Close') }}</button>
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
