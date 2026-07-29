/**
 * `nativeEditor` — the kit's rich editor.
 *
 * It ships no third-party editor: the engine under `../native/` owns the
 * schema, commands, history, serialization and page flow. This file is the
 * Alpine surface that binds it to the toolbar, to `wire:model`, and to the
 * browser's input events.
 */


import * as blocks from '../native/blocks';
import { closestBlock } from '../native/dom';
import { currentRange, restore as restoreSelection, save as saveSelection } from '../native/selection';
import { handleKeydown, handlePaste, insertFragment, type InputContext } from '../native/input';
import { History } from '../native/history';
import {
  DEFAULT_MARGINS,
  PageFlow,
  type Margins,
  type Orientation,
  type PageSize,
} from '../native/pagination';
import {
  activeMarks,
  clearMarks,
  hasMark,
  markValue,
  toggleMark,
} from '../native/marks';
import { sanitizeHtml } from '../native/sanitize';
import { fromValue, toHtml, toJson } from '../native/serialize';
import { onAlpineInit } from '../../../runtime/alpine';
import {
  FONT_FAMILIES,
  FONT_SIZES,
  STYLE_MENU,
  BLOCKS,
  type Alignment,
  type BlockName,
  type MarkName,
} from '../native/schema';

type Mode = 'html' | 'json';

export interface NativeEditorConfig {
  state: unknown;
  placeholder?: string;
  editable?: boolean;
  mode?: Mode;
  debounce?: number;
  pageSize?: PageSize;
  orientation?: Orientation;
  margins?: Partial<Margins>;
  zoom?: number;
  paginated?: boolean;
  uploadUrl?: string | null;
  uploadField?: string;
  documentTitle?: string;
  header?: string | null;
  footer?: string | null;
  exportUrl?: string | null;
  importUrl?: string | null;
  exportFormats?: string[];
  importFormats?: string[];
}

function debounce<T extends (...args: never[]) => void>(fn: T, ms: number) {
  let timer: ReturnType<typeof setTimeout> | null = null;
  return (...args: Parameters<T>) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}

const ZOOM_LEVELS = [50, 75, 90, 100, 125, 150, 200];

if (typeof window !== 'undefined') {
  onAlpineInit(() => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const Alpine = (window as any).Alpine as any;

    Alpine.data('nativeEditor', (config: NativeEditorConfig) => {
      const mode: Mode = config.mode ?? 'html';
      const editable = config.editable ?? true;
      const debounceMs = config.debounce ?? 300;
      const paginated = config.paginated ?? true;

      let flow: PageFlow | null = null;
      let history: History | null = null;
      let root: HTMLElement | null = null;
      let observer: MutationObserver | null = null;
      let resizeObserver: ResizeObserver | null = null;
      let pushState: ((value: unknown) => void) | null = null;
      let observerPaused = false;

      /**
       * Set synchronously on the first `init()`.
       *
       * Alpine invokes a data object's `init()` itself *and* the template calls
       * `x-init="init()"`, so it runs twice. The reactive `initialized` flag is
       * only set after an `await`, which both calls sail past — leaving two
       * MutationObservers and two sets of key handlers bound, so every Enter
       * split twice and every paste inserted twice.
       */
      let booted = false;

      /**
       * Run a mutation without the observer reacting to it.
       *
       * Reflow rewrites the DOM, which would queue records that re-enter the
       * change pipeline and reflow again, forever. Draining the queue with
       * `takeRecords` before unpausing is what breaks that cycle — a plain
       * boolean flag is not enough, because observer callbacks are delivered as
       * microtasks *after* the synchronous work has already cleared the flag.
       */
      const withoutObserver = <T,>(fn: () => T): T => {
        const wasPaused = observerPaused;
        observerPaused = true;
        try {
          return fn();
        } finally {
          if (!wasPaused) {
            observer?.takeRecords();
            observerPaused = false;
          }
        }
      };

      return {
        // ---- reactive state -------------------------------------------
        state: config.state,
        mode,
        editable,
        paginated,
        initialized: false,
        isSyncing: false,

        /** Bumped on every change so toolbar expressions re-evaluate. */
        tick: 0,

        pageCount: 1,
        activePage: 1,
        words: 0,
        characters: 0,
        zoom: config.zoom ?? 100,
        pageSize: (config.pageSize ?? 'a4') as PageSize,
        orientation: (config.orientation ?? 'portrait') as Orientation,
        margins: { ...DEFAULT_MARGINS, ...(config.margins ?? {}) } as Margins,
        documentTitle: config.documentTitle ?? 'Untitled document',

        // Dialog state, driven by the toolbar.
        linkOpen: false,
        linkUrl: '',
        linkText: '',
        showPageSetup: false,
        showOutline: false,
        outline: [] as Array<{ id: string; text: string; level: number }>,

        // Static option lists the Blade toolbar renders.
        fontFamilies: FONT_FAMILIES,
        fontSizes: FONT_SIZES,
        zoomLevels: ZOOM_LEVELS,
        styleMenu: STYLE_MENU.map((name) => ({ name, label: BLOCKS[name].label })),

        // ---- lifecycle -------------------------------------------------
        async init() {
          if (booted) return;
          booted = true;

          await this.$nextTick();

          root = this.$refs.pages as HTMLElement | null;
          if (!root) return;

          this.initialized = true;

          flow = new PageFlow(
            root,
            {
              size: this.pageSize,
              orientation: this.orientation,
              margins: this.margins,
            },
            (count: number) => {
              this.pageCount = count;
            }
          );

          flow.applyGeometry();
          flow.load(fromValue(this.state, this.mode));

          history = new History({ html: toHtml(root), selection: null });

          pushState = debounce((value: unknown) => {
            this.state = value;
            this.$dispatch('input', value);
            this.isSyncing = false;
          }, debounceMs);

          this.bindEvents();
          this.refreshStats();
          this.syncHeaderFooter();

          // Livewire may send the value back down; don't fight the user's typing.
          this.$watch('state', (next: unknown) => {
            if (!root || this.isSyncing) return;
            const current = this.mode === 'json' ? JSON.stringify(toJson(root)) : toHtml(root);
            const incoming =
              this.mode === 'json'
                ? JSON.stringify(typeof next === 'string' ? JSON.parse(next || '{}') : next)
                : String(next ?? '');
            if (current === incoming) return;
            withoutObserver(() => {
              flow?.load(fromValue(next, this.mode));
              this.afterChange('command');
            });
          });

          window.addEventListener('livewire:navigating', this.destroy.bind(this));
        },

        bindEvents() {
          if (!root) return;

          root.addEventListener('keydown', (event: KeyboardEvent) => {
            if (!this.editable) {
              event.preventDefault();
              return;
            }

            // While a header or footer is being edited the caret is inside a
            // nested editable island. Those keystrokes bubble up to here, and
            // running block commands against them would rewrite the document
            // body instead of the header.
            if (this.hfMode) {
              if (event.key === 'Escape') this.exitHeaderFooter();
              return;
            }

            handleKeydown(event, this.context());
          });

          root.addEventListener('paste', (event: ClipboardEvent) => {
            if (!this.editable) return;
            handlePaste(event, this.context());
          });

          root.addEventListener('contextmenu', (event: MouseEvent) => {
            this.openTableMenu(event);
          });

          /**
           * Clicking back into the page leaves header/footer editing.
           *
           * Without this the editor is a trap: entering a header makes the body
           * read-only, and the only way out was the Escape key. Clicking where
           * you want to write is the obvious gesture, so it has to be the one
           * that works.
           */
          const host = (root.closest('.nk-native') as HTMLElement | null) ?? root;
          host.addEventListener('mousedown', (event: MouseEvent) => {
            if (!this.hfMode) return;

            const target = event.target as HTMLElement | null;
            if (target?.closest('[data-nk-header], [data-nk-footer]')) return;
            // Toolbar and menu clicks are actions on the header, not an exit.
            if (target?.closest('.nk-native-chrome, .nk-ruler, .nk-native-statusbar')) return;

            const { clientX, clientY } = event;
            this.exitHeaderFooter();

            // The body only becomes editable again as `exitHeaderFooter` runs,
            // so the caret can only be placed on the next frame.
            requestAnimationFrame(() => {
              if (!root) return;
              const range = document.caretRangeFromPoint?.(clientX, clientY);
              if (!range || !root.contains(range.commonAncestorContainer)) return;
              const selection = window.getSelection();
              selection?.removeAllRanges();
              selection?.addRange(range);
              root.focus({ preventScroll: true });
            });
          });

          root.addEventListener('drop', (event: DragEvent) => {
            const files = Array.from(event.dataTransfer?.files ?? []);
            const images = files.filter((file) => file.type.startsWith('image/'));
            if (images.length === 0) return;
            event.preventDefault();
            void Promise.all(images.map((file) => this.uploadImage(file)));
          });

          // Typing goes through the browser; observe the result rather than
          // trying to intercept every input event and IME composition.
          observer = new MutationObserver((records) => {
            if (observerPaused || records.length === 0) return;
            this.afterChange('typing');
          });
          observer.observe(root, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: false,
          });

          // The editor may be mounted inside a collapsed panel or a tab that is
          // not shown yet. Pages cannot be measured at zero height, so reflow
          // again the moment the sheets actually get laid out.
          let hadHeight = (root.firstElementChild as HTMLElement | null)?.clientHeight ?? 0;
          resizeObserver = new ResizeObserver(() => {
            const height = (root?.firstElementChild as HTMLElement | null)?.clientHeight ?? 0;
            if (height > 0 && hadHeight === 0) {
              withoutObserver(() => flow?.reflow());
              this.pageCount = flow?.pageCount ?? 1;
            }
            hadHeight = height;
          });
          resizeObserver.observe(root);

          const syncToolbar = () => {
            this.tick += 1;
            this.activePage = flow?.currentPage() ?? 1;
          };
          document.addEventListener('selectionchange', syncToolbar);
          this._detachSelection = () =>
            document.removeEventListener('selectionchange', syncToolbar);
        },

        _detachSelection: null as null | (() => void),

        destroy() {
          observer?.disconnect();
          observer = null;
          resizeObserver?.disconnect();
          resizeObserver = null;
          flow?.cancel();
          flow = null;
          history = null;
          this._detachSelection?.();
          this._detachSelection = null;
          this.initialized = false;
          booted = false;
        },

        /** Context handed to the input layer so it can call back in. */
        context(): InputContext {
          return {
            root: root!,
            changed: (kind = 'command') => this.afterChange(kind),
            promptLink: () => this.openLinkDialog(),
            undo: () => this.undo(),
            redo: () => this.redo(),
          };
        },

        // ---- change pipeline -------------------------------------------
        /**
         * Single funnel for every edit: reflow the pages, record history, push
         * the new value out and refresh the toolbar.
         */
        afterChange(kind: 'typing' | 'command' = 'command') {
          if (!root) return;

          withoutObserver(() => {
            if (this.paginated) flow?.reflow();
          });

          const html = toHtml(root);
          history?.record({ html, selection: saveSelection(root) }, kind);

          this.isSyncing = true;
          pushState?.(this.mode === 'json' ? toJson(root) : html);

          this.tick += 1;
          this.pageCount = flow?.pageCount ?? 1;
          this.activePage = flow?.currentPage() ?? 1;
          this.refreshStats();
          this.watchImages();
          this.syncHeaderFooter();
        },

        /**
         * Re-paginate once an image has actually loaded.
         *
         * A pending image measures as zero-height, so the page it sits on looks
         * far emptier than it will be. Nothing about the document changes when
         * the bytes arrive — same elements, same text — so the flow engine's
         * change signature would not notice and the layout would stay wrong
         * until the next keystroke.
         */
        watchImages() {
          if (!root) return;

          root.querySelectorAll<HTMLImageElement>('[data-nk-content] img').forEach((img) => {
            if (img.dataset.nkWatched) return;
            img.dataset.nkWatched = '1';
            if (img.complete) return;

            const settle = () => {
              withoutObserver(() => {
                flow?.invalidate();
                flow?.reflow();
              });
              this.pageCount = flow?.pageCount ?? 1;
              this.tick += 1;
            };

            img.addEventListener('load', settle, { once: true });
            img.addEventListener('error', settle, { once: true });
          });
        },

        refreshStats() {
          if (!root) return;

          // Only the page bodies count. Reading the whole root would fold in
          // the page footers and any other chrome, so an empty document would
          // report a word count.
          const text = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-content]'))
            .map((body) => body.textContent ?? '')
            .join('\n');

          this.characters = text.replace(/​/g, '').length;
          this.words = text.trim().split(/\s+/).filter(Boolean).length;
          this.outline = this.buildOutline();

          // Drives the CSS placeholder — an "empty" document is `<p><br></p>`,
          // which no CSS selector can recognise on its own.
          const first = root.querySelector<HTMLElement>('[data-nk-content]');
          if (first) {
            const blank = this.characters === 0 && !root.querySelector('img, hr, table');
            if (blank) first.setAttribute('data-nk-empty', '');
            else first.removeAttribute('data-nk-empty');
          }
        },

        buildOutline() {
          if (!root) return [];
          return Array.from(
            root.querySelectorAll<HTMLElement>('[data-nk-content] h1, [data-nk-content] h2, [data-nk-content] h3, [data-nk-content] h4')
          )
            .filter((heading) => (heading.textContent ?? '').trim().length > 0)
            .map((heading) => ({
              id: heading.getAttribute('data-nk-id') ?? '',
              text: (heading.textContent ?? '').trim(),
              level: Number(heading.tagName.slice(1)),
            }));
        },

        /** Run a command against the live selection, then settle the document. */
        run(fn: (root: HTMLElement, range: Range) => void) {
          if (!root || !this.editable) return;

          // The command's own edits must not re-enter through the observer —
          // `afterChange` below already accounts for them.
          withoutObserver(() => {
            // Commands that retype a block (paragraph → heading) replace the
            // element, which leaves the live selection pointing into a detached
            // node — every command after that would then find no range and do
            // nothing. The logical snapshot survives because the replacement
            // carries the same block id.
            const before = saveSelection(root!);
            const range = currentRange(root!);
            if (!range) {
              // No caret in the editor — put one at the top so the click still works.
              const first = root!.querySelector<HTMLElement>('[data-nk-content] > *');
              if (!first) return;
              const fresh = document.createRange();
              fresh.selectNodeContents(first);
              fresh.collapse(true);
              const selection = window.getSelection();
              selection?.removeAllRanges();
              selection?.addRange(fresh);
              fn(root!, fresh);
            } else {
              fn(root!, range);
            }

            // Replacing an element does not clear the selection — the browser
            // re-points it at the *parent* of the node that went away, so the
            // caret ends up on the page body rather than in any block. Checking
            // for a null range therefore misses it; checking that the caret is
            // still inside a block is what actually detects the loss. Commands
            // that move the caret on purpose land inside a block and are left
            // alone.
            const after = currentRange(root!);
            if (!after || !closestBlock(after.startContainer, root!)) {
              restoreSelection(root!, before);
            }

            this.afterChange('command');
          });

          this.focus();
        },

        /**
         * Return focus to the page surface after a toolbar click.
         *
         * Focusing a contenteditable resets its selection to the start, so the
         * caret is snapshotted and put back. Without that, the block the user
         * had selected is forgotten the moment a command runs and every
         * follow-up command quietly applies to nothing.
         */
        focus() {
          if (!root) return;

          // A caret already inside the pages is the state we want; calling
          // `focus()` on a contenteditable would collapse it to the very start,
          // so the block the user was working on gets forgotten and every
          // follow-up command applies to nothing.
          if (currentRange(root)) return;

          root.focus({ preventScroll: true });
        },

        // ---- history ---------------------------------------------------
        undo() {
          const entry = history?.undo();
          if (!entry || !root) return;
          this.applySnapshot(entry.html, entry.selection);
        },

        redo() {
          const entry = history?.redo();
          if (!entry || !root) return;
          this.applySnapshot(entry.html, entry.selection);
        },

        applySnapshot(html: string, selection: ReturnType<typeof saveSelection>) {
          if (!root || !flow) return;

          withoutObserver(() => {
            flow!.load(sanitizeHtml(html));
            restoreSelection(root!, selection);
          });

          this.isSyncing = true;
          pushState?.(this.mode === 'json' ? toJson(root) : toHtml(root));
          this.tick += 1;
          this.pageCount = flow.pageCount;
          this.refreshStats();
        },

        canUndo() {
          this.tick;
          return history?.canUndo ?? false;
        },

        canRedo() {
          this.tick;
          return history?.canRedo ?? false;
        },

        // ---- queries (all touch `tick` so Alpine re-evaluates) ----------
        isActive(mark: MarkName, value?: string): boolean {
          this.tick;
          if (!root) return false;
          const range = currentRange(root);
          return range ? hasMark(root, range, mark, value) : false;
        },

        activeMarkList(): MarkName[] {
          this.tick;
          if (!root) return [];
          const range = currentRange(root);
          return range ? Array.from(activeMarks(root, range)) : [];
        },

        currentStyle(): BlockName {
          this.tick;
          if (!root) return 'paragraph';
          const range = currentRange(root);
          return (range && blocks.currentBlockName(root, range)) || 'paragraph';
        },

        currentStyleLabel(): string {
          return BLOCKS[this.currentStyle()]?.label ?? 'Normal text';
        },

        currentAlign(): Alignment {
          this.tick;
          if (!root) return 'left';
          const range = currentRange(root);
          return range ? blocks.currentAlignment(root, range) : 'left';
        },

        currentSpacing(): number {
          this.tick;
          if (!root) return 1.15;
          const range = currentRange(root);
          return range ? blocks.currentLineSpacing(root, range) : 1.15;
        },

        currentFont(): string {
          this.tick;
          if (!root) return '';
          const range = currentRange(root);
          return (range && markValue(root, range, 'fontFamily')) || FONT_FAMILIES[0].value;
        },

        currentFontSize(): number {
          this.tick;
          if (!root) return 11;
          const range = currentRange(root);
          const value = range && markValue(root, range, 'fontSize');
          return value ? Math.round(parseFloat(value) / (4 / 3)) : 11;
        },

        isList(kind: 'ul' | 'ol'): boolean {
          this.tick;
          if (!root) return false;
          const range = currentRange(root);
          return range ? blocks.inList(root, range, kind) : false;
        },

        // ---- formatting commands ---------------------------------------
        toggle(mark: MarkName, value?: string) {
          this.run((editor, range) => toggleMark(editor, range, mark, value));
        },

        setStyle(name: BlockName) {
          this.run((editor, range) => blocks.setBlockType(editor, range, name));
        },

        setFont(value: string) {
          this.run((editor, range) => toggleMark(editor, range, 'fontFamily', value));
        },

        /** Sizes are shown in points; CSS wants pixels. */
        setFontSize(points: number) {
          const px = `${Math.round(points * (4 / 3))}px`;
          this.run((editor, range) => toggleMark(editor, range, 'fontSize', px));
        },

        bumpFontSize(delta: number) {
          const index = FONT_SIZES.indexOf(this.currentFontSize());
          const next = FONT_SIZES[Math.max(0, Math.min(FONT_SIZES.length - 1, index + delta))];
          if (next) this.setFontSize(next);
        },

        setColor(value: string) {
          this.run((editor, range) => toggleMark(editor, range, 'color', value));
        },

        setHighlight(value: string) {
          this.run((editor, range) => toggleMark(editor, range, 'highlight', value));
        },

        setAlign(align: Alignment) {
          this.run((editor, range) => blocks.setAlignment(editor, range, align));
        },

        setSpacing(spacing: number) {
          this.run((editor, range) => blocks.setLineSpacing(editor, range, spacing));
        },

        toggleBulletList() {
          this.run((editor, range) => blocks.toggleList(editor, range, 'ul'));
        },

        toggleOrderedList() {
          this.run((editor, range) => blocks.toggleList(editor, range, 'ol'));
        },

        indent() {
          this.run((editor, range) => blocks.indent(editor, range));
        },

        outdent() {
          this.run((editor, range) => blocks.outdent(editor, range));
        },

        clearFormatting() {
          this.run((editor, range) => {
            clearMarks(editor, range);
            blocks.clearBlockFormatting(editor, range);
          });
        },

        insertRule() {
          this.run((editor, range) => blocks.insertHorizontalRule(editor, range));
        },

        insertTable(rows = 3, cols = 3) {
          this.run((editor, range) => blocks.insertTable(editor, range, rows, cols));
        },

        // ---- tables -----------------------------------------------------
        /** Caret position within a table, or null — drives the table menu. */
        tableInfo() {
          this.tick;
          if (!root) return null;
          const range = currentRange(root);
          if (!range) return null;
          const context = blocks.tableContext(root, range);
          if (!context) return null;
          return {
            rowIndex: context.rowIndex,
            cellIndex: context.cellIndex,
            rows: context.rows,
            columns: context.columns,
          };
        },

        inTable(): boolean {
          return this.tableInfo() !== null;
        },

        insertRow(where: 'above' | 'below') {
          this.run((editor, range) => blocks.insertTableRow(editor, range, where));
        },

        insertColumn(where: 'left' | 'right') {
          this.run((editor, range) => blocks.insertTableColumn(editor, range, where));
        },

        deleteRow() {
          this.run((editor, range) => blocks.deleteTableRow(editor, range));
        },

        deleteColumn() {
          this.run((editor, range) => blocks.deleteTableColumn(editor, range));
        },

        deleteTable() {
          this.run((editor, range) => blocks.deleteTable(editor, range));
        },

        toggleHeaderRow() {
          this.run((editor, range) => blocks.toggleTableHeaderRow(editor, range));
        },

        /** Grid picker state for the "insert table" popover. */
        gridRows: 0,
        gridCols: 0,

        setGrid(rows: number, cols: number) {
          this.gridRows = rows;
          this.gridCols = cols;
        },

        // ---- insert-table dialog ----------------------------------------
        showTableDialog: false,
        tableDialogRows: 3,
        tableDialogCols: 3,

        openTableDialog() {
          this.tableDialogRows = 3;
          this.tableDialogCols = 3;
          this.showTableDialog = true;
        },

        confirmTableDialog() {
          // Clamp here rather than trusting the inputs: a pasted or typed value
          // like 500 would build a table with a quarter of a million cells.
          const rows = Math.max(1, Math.min(50, Math.round(Number(this.tableDialogRows) || 1)));
          const cols = Math.max(1, Math.min(20, Math.round(Number(this.tableDialogCols) || 1)));

          this.showTableDialog = false;
          this.insertTable(rows, cols);
        },

        // ---- table context menu ----------------------------------------
        tableMenuOpen: false,
        tableMenuX: 0,
        tableMenuY: 0,

        openTableMenu(event: MouseEvent) {
          if (!root || !this.editable) return;

          const target = event.target as HTMLElement | null;
          if (!target?.closest('td, th')) return;

          // Put the caret where the user right-clicked, so the row/column the
          // menu acts on is the one they pointed at rather than wherever the
          // caret happened to be.
          const point = document.caretRangeFromPoint?.(event.clientX, event.clientY);
          if (point) {
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(point);
          }

          event.preventDefault();

          const host = root.closest<HTMLElement>('.nk-native') ?? root;
          const box = host.getBoundingClientRect();
          this.tableMenuX = event.clientX - box.left;
          this.tableMenuY = event.clientY - box.top;
          this.tableMenuOpen = true;
          this.tick += 1;
        },

        runTableAction(action: () => void) {
          this.tableMenuOpen = false;
          action();
        },

        insertPageBreak() {
          this.run((editor, range) => blocks.insertPageBreak(editor, range));
        },

        // ---- links ------------------------------------------------------
        openLinkDialog() {
          if (!root) return;
          const range = currentRange(root);
          this.linkUrl = (range && markValue(root, range, 'link')) || '';
          this.linkText = range ? range.toString() : '';
          this.linkOpen = true;
        },

        applyLink() {
          const url = this.linkUrl.trim();
          this.linkOpen = false;
          if (!url) return this.removeLink();

          const href = /^[a-z][a-z0-9+.-]*:/i.test(url) || url.startsWith('/') ? url : `https://${url}`;
          this.run((editor, range) => toggleMark(editor, range, 'link', href));
        },

        removeLink() {
          this.linkOpen = false;
          this.run((editor, range) => {
            if (hasMark(editor, range, 'link')) toggleMark(editor, range, 'link');
          });
        },

        // ---- images -----------------------------------------------------
        pickImage() {
          const input = document.createElement('input');
          input.type = 'file';
          input.accept = 'image/*';
          input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (file) void this.uploadImage(file);
          });
          input.click();
        },

        /** Read a file as a data URL, for inlining when no endpoint is available. */
        readAsDataUrl(file: File): Promise<string> {
          return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result));
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
          });
        },

        /**
         * Put an image in the document, uploading it first when an endpoint is
         * configured.
         *
         * The image is *always* inserted. An upload can fail for reasons that
         * have nothing to do with the user — a missing route, an expired CSRF
         * token, an error page where JSON was expected — and dropping their
         * image on the floor is the worst possible response. On failure the
         * file is inlined as a data URL and the problem is reported, so the
         * work is never lost.
         */
        async uploadImage(file: File) {
          const url = config.uploadUrl;

          const inline = async (reason?: string) => {
            if (reason) {
              console.warn(`[neura native editor] image upload failed (${reason}); embedding inline instead.`);
              this.$dispatch('editor-upload-failed', { file: file.name, reason });
            }
            const dataUrl = await this.readAsDataUrl(file);
            this.run((editor, range) => blocks.insertImage(editor, range, dataUrl, file.name));
          };

          if (!url) return inline();

          const token = document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content');

          const form = new FormData();
          form.append(config.uploadField ?? 'image', file);

          try {
            const response = await fetch(url, {
              method: 'POST',
              body: form,
              credentials: 'same-origin',
              headers: token ? { 'X-CSRF-TOKEN': token, Accept: 'application/json' } : { Accept: 'application/json' },
            });

            if (!response.ok) return inline(`HTTP ${response.status}`);

            // A redirect to a login page or a Laravel error view returns HTML;
            // handing that to `json()` throws a parse error that reads like a
            // bug in the editor rather than a misconfigured endpoint.
            const type = response.headers.get('content-type') ?? '';
            if (!type.includes('json')) return inline('endpoint did not return JSON');

            const payload = await response.json();
            const src = payload?.file?.url ?? payload?.url;
            if (!src) return inline('response contained no image URL');

            this.run((editor, range) => blocks.insertImage(editor, range, src, file.name));
          } catch (error) {
            await inline(error instanceof Error ? error.message : 'network error');
          }
        },

        // ---- header and footer ------------------------------------------
        /**
         * One header and one footer for the whole document, mirrored onto every
         * sheet.
         *
         * They live outside `[data-nk-content]`, so the flow engine never sees
         * them, they cost no page space beyond the margin they sit in, and they
         * are excluded from the word count and from the saved body HTML.
         */
        headerHtml: (config.header as string | null) ?? '',
        footerHtml: (config.footer as string | null) ?? '<span data-nk-field="page"></span>',

        /** Which region is being edited right now, if any. */
        hfMode: null as null | 'header' | 'footer',

        /** Copy the stored header/footer onto every page and fill auto fields. */
        syncHeaderFooter() {
          if (!root) return;
          withoutObserver(() => this.writeHeaderFooter());
        },

        writeHeaderFooter() {
          if (!root) return;

          const host = root.closest<HTMLElement>('.nk-native') ?? root;
          const total = flow?.pageCount ?? 1;

          host.querySelectorAll<HTMLElement>('[data-nk-header], [data-nk-footer]').forEach((region) => {
            // Never overwrite the region the user is typing into.
            if (region.getAttribute('contenteditable') === 'true') return;

            const html = region.hasAttribute('data-nk-header') ? this.headerHtml : this.footerHtml;
            if (region.innerHTML !== html) region.innerHTML = html;

            const page = region.closest<HTMLElement>('[data-nk-page]');
            const index = page ? Number(page.getAttribute('data-nk-page') || 1) : 1;
            this.fillFields(region, index, total);
          });
        },

        /** Replace the auto-field placeholders with their current values. */
        fillFields(region: HTMLElement, page: number, pages: number) {
          region.querySelectorAll<HTMLElement>('[data-nk-field]').forEach((field) => {
            const value = (() => {
              switch (field.getAttribute('data-nk-field')) {
                case 'page': return String(page);
                case 'pages': return String(pages);
                case 'title': return this.documentTitle;
                case 'date': return new Date().toLocaleDateString();
                default: return null;
              }
            })();

            // Only write when it actually differs: assigning `textContent`
            // replaces the child nodes even for an identical string, and those
            // mutations would wake the change pipeline and loop straight back
            // into here.
            if (value !== null && field.textContent !== value) field.textContent = value;
          });
        },

        /**
         * Enter header or footer editing.
         *
         * The body is made read-only while editing so the caret cannot slip
         * back into the document — the same trade Google Docs makes when it
         * dims the page behind the header.
         */
        editHeaderFooter(which: 'header' | 'footer') {
          if (!root || !this.editable) return;

          this.exitHeaderFooter();

          const first = root.querySelector<HTMLElement>('[data-nk-page]');
          const region = first?.querySelector<HTMLElement>(
            which === 'header' ? '[data-nk-header]' : '[data-nk-footer]'
          );
          if (!region) return;

          this.hfMode = which;
          root.setAttribute('contenteditable', 'false');
          region.setAttribute('contenteditable', 'true');
          (root.closest('.nk-native') as HTMLElement | null)?.setAttribute('data-nk-hf-editing', which);

          region.focus({ preventScroll: true });

          const range = document.createRange();
          range.selectNodeContents(region);
          range.collapse(false);
          const selection = window.getSelection();
          selection?.removeAllRanges();
          selection?.addRange(range);

          this.tick += 1;
        },

        /** Leave header/footer editing, storing what was typed. */
        exitHeaderFooter() {
          if (!root || !this.hfMode) return;

          const region = root.querySelector<HTMLElement>('[contenteditable="true"][data-nk-header], [contenteditable="true"][data-nk-footer]');

          if (region) {
            // Strip the resolved field values back to empty placeholders, so a
            // page number stored from page 1 does not read "1" on every sheet.
            const clone = region.cloneNode(true) as HTMLElement;
            clone.querySelectorAll('[data-nk-field]').forEach((field) => {
              field.textContent = '';
            });

            if (this.hfMode === 'header') this.headerHtml = clone.innerHTML;
            else this.footerHtml = clone.innerHTML;

            region.removeAttribute('contenteditable');
          }

          this.hfMode = null;
          if (this.editable) root.setAttribute('contenteditable', 'true');
          (root.closest('.nk-native') as HTMLElement | null)?.removeAttribute('data-nk-hf-editing');

          this.syncHeaderFooter();
          this.$dispatch('header-footer-change', { header: this.headerHtml, footer: this.footerHtml });
          this.tick += 1;
        },

        /** Insert an auto field at the caret while editing a header/footer. */
        insertField(kind: 'page' | 'pages' | 'title' | 'date') {
          if (!this.hfMode) {
            // Not editing yet — open the footer, which is where page numbers go.
            this.editHeaderFooter(kind === 'title' ? 'header' : 'footer');
          }

          const selection = window.getSelection();
          if (!selection || selection.rangeCount === 0) return;

          const field = document.createElement('span');
          field.setAttribute('data-nk-field', kind);
          field.textContent = kind === 'title' ? this.documentTitle : '#';

          const range = selection.getRangeAt(0);
          range.insertNode(field);
          range.setStartAfter(field);
          range.collapse(true);
          selection.removeAllRanges();
          selection.addRange(range);

          this.tick += 1;
        },

        // ---- page setup and view ---------------------------------------
        setZoom(value: number) {
          this.zoom = value;
        },

        zoomStyle() {
          return `transform: scale(${this.zoom / 100});`;
        },

        applyPageSetup() {
          this.showPageSetup = false;
          flow?.setOptions({
            size: this.pageSize,
            orientation: this.orientation,
            margins: this.margins,
          });
          this.pageCount = flow?.pageCount ?? 1;
        },

        setMargin(side: keyof Margins, inches: number) {
          this.margins = { ...this.margins, [side]: Math.round(inches * 96) };
        },

        marginInches(side: keyof Margins) {
          return Number((this.margins[side] / 96).toFixed(2));
        },

        // ---- ruler ------------------------------------------------------
        /** Page width in CSS pixels — the ruler's coordinate space. */
        pageWidth(): number {
          this.tick;
          return flow?.geometry.width ?? 794;
        },

        /**
         * Tick marks, one per inch, with a number on each.
         *
         * Positions are relative to the left edge of the *page*, not the text
         * column, so the marks stay put when the margins move.
         */
        rulerTicks(): Array<{ x: number; label: number | null; major: boolean }> {
          const width = this.pageWidth();
          const ticks: Array<{ x: number; label: number | null; major: boolean }> = [];

          // Half-inch resolution: whole inches get a number, halves a short mark.
          for (let x = 0; x <= width; x += 48) {
            const inches = x / 96;
            const whole = Number.isInteger(inches);
            ticks.push({ x, label: whole && inches > 0 ? inches : null, major: whole });
          }

          return ticks;
        },

        /** First-line indent of the paragraph at the caret, in pixels. */
        firstLineIndent(): number {
          this.tick;
          if (!root) return 0;
          const range = currentRange(root);
          if (!range) return 0;
          const block = closestBlock(range.startContainer, root);
          // Read the inline style rather than a bookkeeping attribute: this way
          // an indent that arrived with imported HTML shows on the ruler too.
          return parseFloat(block?.style.textIndent ?? '') || 0;
        },

        /**
         * Drag a ruler marker.
         *
         * Geometry is refreshed continuously so the page tracks the pointer,
         * but the full reflow is deferred to pointer-up: re-paginating a long
         * document on every mouse move would make the drag crawl.
         */
        startRulerDrag(kind: 'left' | 'right' | 'firstLine', event: PointerEvent) {
          if (!this.editable) return;
          event.preventDefault();

          const ruler = this.$refs.ruler as HTMLElement | null;
          if (!ruler) return;

          const box = ruler.getBoundingClientRect();
          const scale = this.zoom / 100;
          const width = this.pageWidth();
          const minColumn = 96; // never let the text column collapse

          const caretBlocks = (() => {
            if (kind !== 'firstLine' || !root) return [];
            const range = currentRange(root);
            return range ? [closestBlock(range.startContainer, root)].filter(Boolean) : [];
          })() as HTMLElement[];

          const move = (moveEvent: PointerEvent) => {
            // The ruler is zoomed, so its rect is in scaled pixels; divide back
            // out to get the CSS pixel the pointer is actually over.
            const x = (moveEvent.clientX - box.left) / scale;

            if (kind === 'left') {
              const max = width - this.margins.right - minColumn;
              this.margins = { ...this.margins, left: Math.max(0, Math.min(max, Math.round(x))) };
              flow?.applyGeometry();
            } else if (kind === 'right') {
              const max = width - this.margins.left - minColumn;
              const fromRight = width - x;
              this.margins = { ...this.margins, right: Math.max(0, Math.min(max, Math.round(fromRight))) };
              flow?.applyGeometry();
            } else {
              const indent = Math.max(0, Math.min(288, Math.round(x - this.margins.left)));
              for (const block of caretBlocks) {
                block.style.textIndent = indent === 0 ? '' : `${indent}px`;
                if (!block.getAttribute('style')) block.removeAttribute('style');
              }
              this.tick += 1;
            }
          };

          const finish = () => {
            window.removeEventListener('pointermove', move);
            if (kind === 'firstLine') this.afterChange('command');
            else this.applyPageSetup();
          };

          window.addEventListener('pointermove', move);
          window.addEventListener('pointerup', finish, { once: true });
          window.addEventListener('pointercancel', finish, { once: true });
        },

        togglePagination() {
          this.paginated = !this.paginated;
          if (this.paginated) flow?.reflow();
        },

        scrollToHeading(id: string) {
          root?.querySelector(`[data-nk-id="${CSS.escape(id)}"]`)?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
          });
        },

        print() {
          window.print();
        },

        // ---- Paperdoc conversion ----------------------------------------
        busy: false,
        conversionError: '' as string,

        /** Formats offered in File → Download, filled from the server config. */
        exportFormats: (config.exportFormats as string[] | undefined) ?? [],

        /** Whether the conversion endpoints exist — Paperdoc is optional. */
        exportAvailable: !!config.exportUrl,
        importAvailable: !!config.importUrl,

        csrfToken() {
          return (
            document
              .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
              ?.getAttribute('content') ?? ''
          );
        },

        /**
         * Render the document server-side and download it.
         *
         * The current page setup travels with the HTML so the produced file has
         * the same paper, orientation and margins as the sheets on screen —
         * otherwise a document written for A4 landscape would come back as
         * portrait Letter.
         */
        async exportAs(format: string) {
          const url = config.exportUrl;
          if (!url || this.busy) return;

          this.busy = true;
          this.conversionError = '';

          try {
            const response = await fetch(url, {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken(),
                Accept: 'application/octet-stream, application/json',
              },
              body: JSON.stringify({
                html: this.mode === 'json' ? toHtml(root!) : String(this.value()),
                format,
                title: this.documentTitle,
                page: {
                  size: this.pageSize,
                  orientation: this.orientation,
                  margins: this.margins,
                },
              }),
            });

            if (!response.ok) {
              const type = response.headers.get('content-type') ?? '';
              const message = type.includes('json')
                ? (await response.json())?.message
                : `Export failed (HTTP ${response.status}).`;
              this.conversionError = message || 'Export failed.';
              return;
            }

            const blob = await response.blob();
            const href = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = href;
            link.download = `${this.documentTitle || 'document'}.${format}`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            // Revoking immediately can cancel the download in some browsers.
            setTimeout(() => URL.revokeObjectURL(href), 10_000);
          } catch (error) {
            this.conversionError = error instanceof Error ? error.message : 'Export failed.';
          } finally {
            this.busy = false;
          }
        },

        /** Pick a document and replace the editor's contents with it. */
        importDocument() {
          const url = config.importUrl;
          if (!url || this.busy) return;

          const input = document.createElement('input');
          input.type = 'file';
          input.accept = ((config.importFormats as string[] | undefined) ?? [])
            .map((format) => `.${format}`)
            .join(',');

          input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;

            this.busy = true;
            this.conversionError = '';

            try {
              const form = new FormData();
              form.append('document', file);

              const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                body: form,
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), Accept: 'application/json' },
              });

              const type = response.headers.get('content-type') ?? '';
              if (!type.includes('json')) {
                this.conversionError = `Import failed (HTTP ${response.status}).`;
                return;
              }

              const payload = await response.json();

              if (!response.ok) {
                this.conversionError = payload?.message ?? 'Import failed.';
                return;
              }

              // `setValue` sanitizes, so nothing from the uploaded file reaches
              // the document without passing the schema allowlist first.
              this.setValue(payload.html ?? '');
              if (payload.title) this.documentTitle = payload.title;
            } catch (error) {
              this.conversionError = error instanceof Error ? error.message : 'Import failed.';
            } finally {
              this.busy = false;
            }
          });

          input.click();
        },

        /** Current value, for a host that wants to read it imperatively. */
        value() {
          if (!root) return this.mode === 'json' ? { type: 'doc', content: [] } : '';
          return this.mode === 'json' ? toJson(root) : toHtml(root);
        },

        /** Replace the whole document. */
        setValue(next: unknown) {
          if (!flow || !root) return;
          withoutObserver(() => {
            flow!.load(fromValue(next, this.mode));
            history?.reset({ html: toHtml(root!), selection: null });
            this.afterChange('command');
          });
        },

        insertHtml(html: string) {
          if (!root) return;
          const range = currentRange(root);
          if (!range) return;
          insertFragment(this.context(), range, sanitizeHtml(html));
        },
      };
    });
  });
}
