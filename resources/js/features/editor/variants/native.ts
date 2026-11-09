/**
 * `nativeEditor` — the kit's rich editor.
 *
 * It ships no third-party editor: the engine under `../native/` owns the
 * schema, commands, history, serialization and page flow. This file is the
 * Alpine surface that binds it to the toolbar, to `wire:model`, and to the
 * browser's input events.
 */


import * as blocks from '../native/blocks';
import * as images from '../native/images';
import * as tables from '../native/tables';
import * as chips from '../native/chips';
import * as contents from '../native/contents';
import * as footnotes from '../native/footnotes';
import * as review from '../native/review';
import { renderVersion } from '../native/compare';
import { browserStore, remoteStore, type VersionStore } from '../native/versionstore';
import { authorColor, dayGroups, planSnapshot, type VersionMeta } from '../native/versioning';
import { dragColumns, dragInsets, dragWidth, NO_CROP, turnAngle, turnInsets, type CropHandle, type Insets } from '../native/sizing';
import { blocksInRange, closestBlock } from '../native/dom';
import { FORMAT_MARKS } from '../native/suggestions';
import { assignIds, currentRange, restore as restoreSelection, save as saveSelection } from '../native/selection';
import { breakParagraph, handleKeydown, handlePaste, insertFragment, insertLineBreak, type InputContext } from '../native/input';
import { CHARACTERS, findCharacters, GROUPS, type CharacterEntry, type CharacterGroup } from '../native/characters';
import { joinDictation, parseDictation } from '../native/dictation';
import { History } from '../native/history';
import { commentRuns, mentionQuery, mentionsIn, putMention, type CommentMention, type ReviewComment } from '../native/threads';
import {
  DEFAULT_MARGINS,
  PageFlow,
  type Margins,
  type Orientation,
  type PageSize,
} from '../native/pagination';
import {
  activeMarks,
  applyMark,
  clearMarks,
  hasMark,
  markValue,
  removeMark,
  toggleMark,
} from '../native/marks';
import { blockId, setCaret } from '../native/selection';
import { compile, replacementFor } from '../native/search';
import { clearMatches, findRanges, paintMatches, rangeIn, runOf, wordAt } from '../native/find';
import { COMMANDS, filterCommands, filterPeople, fold, initials, slashQuery, type Person } from '../native/commands';
import { applyCase } from '../native/casing';
import type { CaseMode } from '../native/textcase';
import { sanitizeHtml } from '../native/sanitize';
import { fromValue, toHtml, toJson, type JsonDoc } from '../native/serialize';
import { onAlpineInit } from '../../../runtime/alpine';
import './menubar';
import {
  FONT_FAMILIES,
  FONT_SIZES,
  MARKS,
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
  /** The first page's own header and footer, used when `differentFirstPage` is on. */
  firstHeader?: string | null;
  firstFooter?: string | null;
  differentFirstPage?: boolean;
  /** Text columns on each page, 1 to 3. */
  columns?: number;
  exportUrl?: string | null;
  importUrl?: string | null;
  exportFormats?: string[];
  importFormats?: string[];
  /** BCP 47 locale for dates and numbers. */
  locale?: string;
  /** Words the script shows: style names, "/" commands, find results… (EN / FR from Blade). */
  phrases?: Phrases;
  /** People "@" offers, and an endpoint to search more (`?q=`, JSON list). */
  mentions?: Person[];
  mentionsUrl?: string | null;
  /** Who writes comments and suggestions. */
  author?: { name?: string | null; avatar?: string | null } | null;
  /**
   * Version history: where versions are kept (this browser, or `url`), under
   * which `key`, how long a stretch of editing makes one version (`interval`,
   * seconds), how long a pause records it (`idle`, seconds), how many unnamed
   * versions stay (`limit`).
   */
  history?: { enabled?: boolean; key?: string | null; url?: string | null; interval?: number; idle?: number; limit?: number } | null;
}

type EditMode = 'editing' | 'suggesting' | 'viewing';

/** A person or a date offered by the "@" menu. */
interface MentionItem {
  id: string;
  group: 'people' | 'dates';
  label: string;
  sub: string;
  keywords: string[];
  icon: string;
  initials: string;
  avatar: string;
}

const CALENDAR_ICON = 'M2.5 3.5h11v10h-11zM2.5 6.5h11M5.5 2v3M10.5 2v3';

type NaturalParser = typeof import('../../forms/natural-date/parse');
let naturalParser: Promise<NaturalParser> | null = null;

interface Phrases {
  styles?: Partial<Record<BlockName, string>>;
  commands?: Record<string, string>;
  marks?: Record<string, string>;
  aligns?: Record<string, string>;
  groups?: Record<string, string>;
  [key: string]: unknown;
}

/** Character offset of a range's start inside a block's text. */
function offsetIn(block: HTMLElement, range: Range): number {
  const probe = block.ownerDocument!.createRange();
  probe.selectNodeContents(block);
  try {
    probe.setEnd(range.startContainer, range.startOffset);
  } catch {
    return 0;
  }
  return probe.toString().length;
}

function debounce<T extends (...args: never[]) => void>(fn: T, ms: number) {
  let timer: ReturnType<typeof setTimeout> | null = null;
  return (...args: Parameters<T>) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}

const ZOOM_LEVELS = [50, 75, 90, 100, 125, 150, 200];

/** The boxes a comment is written in: a new thread, a reply, or a comment being changed. */
type CommentField = 'draft' | 'reply' | 'edit';

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

      const phrases: Phrases = config.phrases ?? {};
      const say = (key: string, fallback: string, values: Record<string, string | number> = {}): string => {
        let text = typeof phrases[key] === 'string' ? (phrases[key] as string) : fallback;
        for (const [name, value] of Object.entries(values)) text = text.replace(`{${name}}`, String(value));
        return text;
      };

      // Find and replace: the live matches, kept out of Alpine's reactivity.
      let matches: Range[] = [];
      // The "/" menu: where the slash was typed.
      let slashBlockId = '';
      let slashAt = 0;
      // Paint format: the marks picked up.
      let painted: Array<{ name: MarkName; value: string | null }> = [];
      // Cells picked by dragging across a table or Shift+clicking: the corners.
      let cellAnchor: HTMLElement | null = null;
      let cellFocus: HTMLElement | null = null;
      // The image picked by a click.
      let picked: HTMLElement | null = null;
      // While cropping: the original, ready to draw, and the crop the picture had.
      let cropPicture: HTMLImageElement | null = null;
      let cropFrom: Insets = NO_CROP;
      let cropUrl: string | null = null;
      let imageNoticeTimer: number | undefined;
      // The table of contents and the chip picked by a click.
      let pickedToc: HTMLElement | null = null;
      let pickedChip: HTMLElement | null = null;
      // "@": people found by the search endpoint for the current query.
      let remotePeople: Person[] = [];
      let remoteAbort: AbortController | null = null;
      let remoteTimer: ReturnType<typeof setTimeout> | null = null;
      // Comments: the box being typed in, and the people picked from its "@" list.
      let commentBox: HTMLTextAreaElement | null = null;
      const pickedPeople = new Map<string, CommentMention>();
      // Version history: the store, the content of the newest version, the pending snapshot.
      const historyOn = config.history?.enabled ?? true;
      const historyInterval = Math.max(10, config.history?.interval ?? 300) * 1000;
      const historyIdle = Math.max(1, config.history?.idle ?? 20) * 1000;
      let versionStore: VersionStore | null = null;
      let newestHtml: string | null = null;
      let snapshotTimer: ReturnType<typeof setTimeout> | null = null;
      let snapshots: Promise<unknown> = Promise.resolve();
      const versionHtml = new Map<string, string>();
      // Special characters: where the caret was when the panel opened.
      let charsCaret: ReturnType<typeof saveSelection> = null;
      // Voice typing: the browser's recogniser.
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const Recognition: any = typeof window !== 'undefined' ? ((window as any).SpeechRecognition ?? (window as any).webkitSpeechRecognition) : undefined;
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      let recognition: any = null;
      const isMac = typeof navigator !== 'undefined' && /mac|iphone|ipad/i.test(navigator.platform || navigator.userAgent);

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
        /** `fit` scales the page to the canvas width; `manual` keeps a fixed %. */
        zoomMode: 'fit' as 'fit' | 'manual',
        pageSize: (config.pageSize ?? 'a4') as PageSize,
        orientation: (config.orientation ?? 'portrait') as Orientation,
        margins: { ...DEFAULT_MARGINS, ...(config.margins ?? {}) } as Margins,
        columns: Math.max(1, Math.min(3, Math.round(config.columns ?? 1))),
        documentTitle: config.documentTitle ?? say('untitled', 'Untitled document'),

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
        styleMenu: STYLE_MENU.map((name) => ({ name, label: phrases.styles?.[name] ?? BLOCKS[name].label })),
        say,

        // Find and replace.
        findOpen: false,
        findReplace: false,
        findQuery: '',
        replaceText: '',
        findCase: false,
        findWhole: false,
        findRegex: false,
        findIndex: 0,
        findCount: 0,
        findError: '',
        findNotice: '',

        // The "/" menu, and the "@" menu that shares it.
        slashTrigger: '/' as '/' | '@',
        mentionTick: 0,
        queryDate: null as null | { iso: string; label: string },
        slashOpen: false,
        slashText: '',
        slashIndex: 0,
        slashX: 0,
        slashY: 0,
        commandItems: COMMANDS.map((command) => ({
          ...command,
          label: phrases.commands?.[command.id] ?? command.id,
        })),

        // Paint format.
        painterOn: false,
        painterSticky: false,

        // Tables: how many cells are picked.
        cellCount: 0,

        // The picked image: where its frame and toolbar go, and how it is set.
        imageOpen: false,
        imageBox: { left: 0, top: 0, width: 0, height: 0 },
        imageBar: { left: 0, top: 0 },
        imageWrap: 'inline' as images.Wrap,
        imageWidth: null as number | null,
        imageHasCaption: false,
        imageEdited: false,
        imageBorder: null as images.ImageBorder | null,
        imageBorderColor: null as string | null,
        imageBorderOpen: false,
        imageNotice: '',
        // Dragging it elsewhere: the line where it would land.
        imageDragging: false,
        dropLine: null as null | { left: number; top: number; width: number },
        // Cropping it: the whole picture's box, and what is taken off each side.
        cropOpen: false,
        cropBox: { left: 0, top: 0, width: 0, height: 0 },
        cropInsets: [0, 0, 0, 0] as Insets,
        cropSrc: '',
        altOpen: false,
        altText: '',

        // A picked chip: its card.
        chipOpen: false,
        chipCard: { kind: 'person', label: '', email: '', initials: '', date: '', long: '' },
        chipBox: { left: 0, top: 0 },

        // A picked table of contents: its toolbar.
        tocOpen: false,
        tocBar: { left: 0, top: 0 },
        tocStyle: 'pages' as contents.TocStyle,

        // The footnote being written, by id.
        noteMode: null as string | null,

        // Review: comments and suggested changes.
        editMode: (editable ? 'editing' : 'viewing') as EditMode,
        /** Comments can be added, answered and settled, whatever the mode, when the editor is not disabled. */
        canReview: editable,
        threads: [] as review.Thread[],
        suggestions: [] as review.Suggestion[],
        reviewOrder: {} as Record<string, number>,
        reviewOpen: false,
        showResolved: false,
        activeReview: '',
        draftId: '',
        draftText: '',
        replyText: '',
        // A comment being changed, and the "@" list open under a comment box.
        editingComment: null as null | { thread: string; index: number },
        editText: '',
        commentMention: null as null | { field: CommentField; at: number; query: string },
        commentMentionIndex: 0,
        commentFab: { show: false, left: 0, top: 0 },

        // Version history.
        historyAvailable: historyOn,
        historyOpen: false,
        versions: [] as VersionMeta[],
        selectedVersion: '',
        historyDiff: true,
        historyNamed: false,
        historyBusy: false,
        historyNotice: '',
        renamingVersion: '',
        versionDraft: '',

        // Special characters.
        charsOpen: false,
        charsQuery: '',
        charsGroup: 'emoji' as CharacterGroup | 'recent',
        charsRecent: [] as string[],
        charsGroups: GROUPS,

        // Voice typing.
        voiceOpen: false,
        voiceListening: false,
        voiceSupported: Boolean(Recognition),
        voiceLang: (config.locale || 'fr-FR').replace('_', '-'),
        voiceInterim: '',
        voiceError: '',

        // Keyboard shortcuts.
        shortcutsOpen: false,
        shortcutsQuery: '',

        // Word count.
        showWordCount: false,
        wordStats: { pages: 1, words: 0, characters: 0, charactersNoSpaces: 0, selectionWords: 0, selectionCharacters: 0, hasSelection: false },

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
              columns: this.columns,
            },
            (count: number) => {
              this.pageCount = count;
            }
          );

          // Footnotes take their room at the foot of the page they are on.
          flow.layoutFoot = (page, body) =>
            footnotes.layoutPage(page, body, this.noteMode, say('noteHint', 'Footnote'));

          flow.applyGeometry();
          flow.load(this.readValue(this.state));
          tables.paintTables(root);
          this.decorateFigures();
          // A table of contents loaded from JSON arrives empty.
          if (this.prepare()) withoutObserver(() => flow?.reflow());
          contents.paintTocPages(root);
          this.refreshReview();

          history = new History({ html: this.snapshotHtml(), selection: null });

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
            const current = this.mode === 'json' ? JSON.stringify(this.serialize()) : String(this.serialize());
            const incoming =
              this.mode === 'json'
                ? JSON.stringify(typeof next === 'string' ? JSON.parse(next || '{}') : next)
                : String(next ?? '');
            if (current === incoming) return;
            withoutObserver(() => {
              flow?.load(this.readValue(next));
              this.afterChange('command');
            });
          });

          window.addEventListener('livewire:navigating', this.destroy.bind(this));
          void this.startHistory();
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

            // Writing a footnote: plain text, one paragraph.
            if (this.noteMode) {
              this.noteKey(event);
              return;
            }

            if (picked && this.imageKey(event)) return;
            if (pickedToc && this.tocKey(event)) return;
            if (this.chipOpen && event.key === 'Escape') {
              this.closeChip();
              return;
            }
            if (this.cellCount > 1 && this.cellKey(event)) return;
            if (this.slashOpen && this.slashKey(event)) return;
            if (this.painterOn && event.key === 'Escape') {
              this.stopPainter();
              return;
            }
            if (event.key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey) this.maybeOpenSlash('/');
            // "@" may need AltGr (Ctrl+Alt), so modifiers do not rule it out.
            if (event.key === '@') this.maybeOpenSlash('@');

            // Suggesting: typing and deleting go through `beforeinput`, where
            // they become suggestions; the shortcuts that rewrite text as you
            // type (Markdown-style autoformat) stay off.
            if (this.editMode === 'suggesting' && this.suggestKey(event)) return;

            handleKeydown(event, this.context());
          });

          // A press in the box of a checklist item ticks it, without moving the caret.
          root.addEventListener('mousedown', (event: MouseEvent) => {
            if (!this.editable || this.hfMode || event.button !== 0) return;
            const target = event.target as HTMLElement | null;
            const item = target?.closest?.('li') as HTMLElement | null;
            if (!item || item !== target || !blocks.isChecklist(item.parentElement)) return;
            const box = item.getBoundingClientRect();
            const style = getComputedStyle(item);
            const pad = parseFloat(style.paddingLeft) || 0;
            const inBox = style.direction === 'rtl' ? event.clientX > box.right - pad : event.clientX < box.left + pad;
            if (!inBox) return;
            event.preventDefault();
            withoutObserver(() => {
              blocks.toggleChecked(root!, item);
              this.afterChange('command');
            });
          });

          // Images are picked whole; cells are picked by dragging across them;
          // a column border is dragged to resize.
          root.addEventListener('mousedown', (event: MouseEvent) => this.pointerDown(event));
          root.addEventListener('mousemove', (event: MouseEvent) => {
            if (event.buttons || !this.editable || this.hfMode) return;
            const cell = (event.target as HTMLElement | null)?.closest?.('td, th') as HTMLElement | null;
            const onBorder = Boolean(cell && root!.contains(cell) && tables.boundaryAt(cell, event.clientX) !== null);
            root!.toggleAttribute('data-nk-col-resize', onBorder);
          });

          // Paint format lands on the text picked after it was armed.
          root.addEventListener('mouseup', () => {
            if (this.painterOn) setTimeout(() => this.applyPainter(), 0);
          });

          // Suggesting: what the browser is about to type or delete becomes a suggestion.
          root.addEventListener('beforeinput', (event: InputEvent) => this.suggestInput(event));
          root.addEventListener('compositionend', (event: CompositionEvent) => {
            if (this.editMode !== 'suggesting' || !root || this.noteMode || this.hfMode) return;
            withoutObserver(() => {
              review.markComposed(root!, event.data ?? '', this.authorName());
              this.afterChange('typing');
            });
          });
          root.addEventListener('cut', (event: ClipboardEvent) => {
            if (this.editMode !== 'suggesting' || !root || this.noteMode || this.hfMode) return;
            const range = currentRange(root);
            if (!range || range.collapsed || closestBlock(range.startContainer, root) !== closestBlock(range.endContainer, root)) return;
            event.preventDefault();
            event.clipboardData?.setData('text/plain', range.toString());
            withoutObserver(() => {
              review.suggestDeletion(root!, range.cloneRange(), this.authorName(), 'backward');
              this.afterChange('command');
            });
          });

          root.addEventListener('paste', (event: ClipboardEvent) => {
            if (!this.editable) return;
            // A picture on the clipboard — a screenshot, an image copied from a
            // page or another app — goes in like a dropped one, uploaded.
            const pictures = this.noteMode || this.hfMode ? [] : this.clipboardPictures(event.clipboardData);
            if (pictures.length) {
              event.preventDefault();
              void Promise.all(pictures.map((file) => this.uploadImage(file, '')));
              return;
            }
            if (this.editMode === 'suggesting' && !this.noteMode && !this.hfMode) {
              // A suggestion is text: what is pasted comes in as one.
              event.preventDefault();
              const text = (event.clipboardData?.getData('text/plain') ?? '').replace(/\s*\n\s*/g, ' ');
              const range = currentRange(root!);
              if (!range || !text) return;
              withoutObserver(() => {
                review.suggestInsertion(root!, range, text, this.authorName());
                this.afterChange('command');
              });
              return;
            }
            if (this.noteMode) {
              // A note is plain text.
              event.preventDefault();
              const text = (event.clipboardData?.getData('text/plain') ?? '').replace(/\s+/g, ' ');
              const range = window.getSelection()?.rangeCount ? window.getSelection()!.getRangeAt(0) : null;
              if (!range || !text) return;
              range.deleteContents();
              const node = document.createTextNode(text);
              range.insertNode(node);
              range.setStartAfter(node);
              range.collapse(true);
              window.getSelection()?.removeAllRanges();
              window.getSelection()?.addRange(range);
              return;
            }
            handlePaste(event, this.context());
          });

          root.addEventListener('contextmenu', (event: MouseEvent) => {
            this.openTableMenu(event);
          });

          // A double-click in a header or footer edits it, on whichever page:
          // pages after the first are cloned, with no handlers of their own.
          root.addEventListener('dblclick', (event: MouseEvent) => {
            const region = (event.target as HTMLElement | null)?.closest?.('[data-nk-header], [data-nk-footer]');
            if (!region || region.getAttribute('contenteditable') === 'true') return;
            this.editHeaderFooter(region.hasAttribute('data-nk-header') ? 'header' : 'footer', event);
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

          // Shortcuts that also work from the find panel and the menus.
          host.addEventListener('keydown', (event: KeyboardEvent) => {
            const mod = event.ctrlKey || event.metaKey;
            const key = event.key.toLowerCase();
            if (mod && event.altKey && event.shiftKey && key === 'h') {
              event.preventDefault();
              void this.openHistory();
            } else if (mod && event.altKey && event.shiftKey && ['z', 'x', 'c'].includes(key)) {
              // Google Docs' keys for Editing, Suggesting and Viewing.
              event.preventDefault();
              this.setEditMode(key === 'z' ? 'editing' : key === 'x' ? 'suggesting' : 'viewing');
            } else if (mod && !event.altKey && event.key === '/') {
              // "/" may need Shift (AZERTY), so it is not ruled out.
              event.preventDefault();
              this.shortcutsOpen = !this.shortcutsOpen;
              this.shortcutsQuery = '';
            } else if (mod && event.shiftKey && !event.altKey && key === 's') {
              event.preventDefault();
              this.toggleVoice();
            } else if (mod && event.altKey && !event.shiftKey && key === 'm') {
              event.preventDefault();
              this.startComment();
            } else if (mod && event.altKey && !event.shiftKey && key === 'f') {
              event.preventDefault();
              this.insertFootnote();
            } else if (mod && !event.shiftKey && !event.altKey && key === 'f') {
              event.preventDefault();
              this.openFind(false);
            } else if (mod && !event.shiftKey && key === 'h') {
              event.preventDefault();
              this.openFind(true);
            } else if (mod && event.shiftKey && !event.altKey && key === 'c') {
              event.preventDefault();
              this.openWordCount();
            } else if (event.key === 'Escape' && this.painterOn) {
              this.stopPainter();
            }
          });

          host.addEventListener('mousedown', (event: MouseEvent) => {
            if (!this.hfMode && !this.noteMode) return;

            const target = event.target as HTMLElement | null;
            if (this.hfMode && target?.closest('[data-nk-header], [data-nk-footer]')) return;
            // A click in the note being written, or on another note or marker, stays with notes.
            if (this.noteMode && target?.closest('[data-nk-note], [data-footnote]')) return;
            // Toolbar and menu clicks are actions on the header, not an exit.
            if (target?.closest('.nk-native-chrome, .nk-ruler, .nk-native-statusbar')) return;

            const { clientX, clientY } = event;
            if (this.noteMode) this.exitNote(false);
            else this.exitHeaderFooter();

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
          // again the moment the sheets actually get laid out. Also watch the
          // canvas so fit-zoom can track narrow / mobile viewports.
          let hadHeight = (root.firstElementChild as HTMLElement | null)?.clientHeight ?? 0;
          const canvas = (this as any).$el?.querySelector?.(
            '.nk-native-canvas',
          ) as HTMLElement | null;

          resizeObserver = new ResizeObserver(() => {
            const height = (root?.firstElementChild as HTMLElement | null)?.clientHeight ?? 0;
            if (height > 0 && hadHeight === 0) {
              withoutObserver(() => flow?.reflow());
              this.pageCount = flow?.pageCount ?? 1;
            }
            hadHeight = height;
            this.fitZoom();
          });
          resizeObserver.observe(root);
          if (canvas) resizeObserver.observe(canvas);

          // The image frame floats over the pages, so it follows them.
          canvas?.addEventListener('scroll', () => picked && this.placeImage(), { passive: true });
          this.$watch('zoom', () => picked && this.$nextTick(() => this.placeImage()));

          this.fitZoom();

          // Panels tied to the text follow it as the pages scroll; the right-click
          // menu closes, as a context menu does.
          canvas?.addEventListener('scroll', () => {
            if (pickedToc) this.placeToc();
            if (pickedChip) this.placeChip();
            if (this.tableMenuOpen) this.tableMenuOpen = false;
            if (this.slashOpen) this.placeSlash();
            if (this.commentFab.show) this.placeCommentFab();
          }, { passive: true });

          const syncToolbar = () => {
            this.syncReview();
            // The caret moving elsewhere in the pages lets go of the picked image;
            // focus going to its alt-text field does not.
            const anchor = window.getSelection()?.anchorNode ?? null;
            if (picked && anchor && root?.contains(anchor) && !picked.contains(anchor)) this.deselectImage();
            this.tick += 1;
            this.activePage = flow?.currentPage() ?? 1;
            if (this.slashOpen) this.updateSlash();
          };
          document.addEventListener('selectionchange', syncToolbar);
          this._detachSelection = () =>
            document.removeEventListener('selectionchange', syncToolbar);
        },

        _detachSelection: null as null | (() => void),

        destroy() {
          if (snapshotTimer) clearTimeout(snapshotTimer);
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
            suggesting: this.editMode === 'suggesting' && !this.noteMode && !this.hfMode ? { author: this.authorName() } : null,
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
            // Pasted lists and tables arrive without ids on their items and cells.
            assignIds(root!);
            this.prepare();
            if (this.paginated) flow?.reflow();
            else footnotes.layoutAll(root!, this.noteMode, say('noteHint', 'Footnote'));
            contents.paintTocPages(root!);
            this.keepNoteFocus();
          });

          const html = this.snapshotHtml();
          history?.record({ html, selection: saveSelection(root) }, kind);

          this.isSyncing = true;
          pushState?.(this.serialize());
          this.refreshReview();
          this.scheduleSnapshot();

          this.tick += 1;
          this.pageCount = flow?.pageCount ?? 1;
          this.activePage = flow?.currentPage() ?? 1;
          tables.paintTables(root);
          this.decorateFigures();
          this.refreshStats();
          this.watchImages();
          if (picked) this.$nextTick(() => this.placeImage());
          if (pickedToc) this.$nextTick(() => (pickedToc?.isConnected ? this.placeToc() : this.deselectToc()));
          if (pickedChip) this.$nextTick(() => (pickedChip?.isConnected ? this.placeChip() : this.closeChip()));
          this.syncHeaderFooter();
          if (this.findOpen) this.runFind(true);
          if (this.slashOpen) this.updateSlash();
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

            // A spinner over the figure until its picture arrives.
            const figure = img.closest('figure');
            figure?.setAttribute('data-nk-loading', '');

            const settle = () => {
              figure?.removeAttribute('data-nk-loading');
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

        /**
         * Generated content in step with the document, before it is laid out:
         * notes typed in the page feet go onto their markers, markers are
         * numbered, tables of contents follow the headings, and chips and
         * markers are units. True when a table of contents changed.
         */
        prepare(): boolean {
          if (!root) return false;
          footnotes.collect(root);
          footnotes.renumber(root);
          chips.decorateAtoms(root);
          // Each break says what it is, in the margin above where the text goes on.
          root.querySelectorAll<HTMLElement>('[data-nk-content] [data-nk-break]').forEach((marker) => {
            const kind = marker.getAttribute('data-nk-break');
            const label = kind === 'odd' ? say('breakOdd', 'Next odd page') : kind === 'even' ? say('breakEven', 'Next even page') : kind === 'column' ? say('breakColumn', 'Column break') : say('breakPage', 'Page break');
            if (marker.getAttribute('data-nk-label') !== label) marker.setAttribute('data-nk-label', label);
          });
          review.decorateAnchors(root, this.threads, this.draftId || null, this.activeReview || null);
          return contents.syncTocs(root, say('tocHint', 'Add headings (Format › Paragraph styles) and they will appear here.'));
        },

        /** Empty captions say what they are for; the hint is editor bookkeeping, never saved. */
        decorateFigures() {
          root?.querySelectorAll<HTMLElement>('figure[data-border-color]').forEach(images.paintFigure);
          root?.querySelectorAll<HTMLElement>('figcaption').forEach((caption) => {
            if (!caption.hasAttribute('data-nk-hint')) caption.setAttribute('data-nk-hint', say('captionHint', 'Add a caption'));
            caption.toggleAttribute('data-nk-empty', (caption.textContent ?? '').trim() === '');
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

          // A table header repeated at the top of a page is counted once.
          const repeated = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-content] [data-nk-repeat]'))
            .map((row) => row.textContent ?? '')
            .join('\n');
          const count = (value: string) => value.trim().split(/\s+/).filter(Boolean).length;

          this.characters = text.replace(/\u200B/g, '').length - repeated.replace(/\u200B/g, '').length;
          this.words = count(text) - count(repeated);
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

          this.deselectImage();
          this.deselectToc();
          this.closeChip();
          this.clearCells();
          withoutObserver(() => {
            const fragment = sanitizeHtml(html);
            this.threads = review.takeThreads(fragment);
            flow!.load(fragment);
            tables.paintTables(root!);
            this.decorateFigures();
            if (this.prepare()) flow!.reflow();
            contents.paintTocPages(root!);
            restoreSelection(root!, selection);
          });

          this.isSyncing = true;
          pushState?.(this.serialize());
          this.tick += 1;
          this.pageCount = flow.pageCount;
          this.refreshStats();
          this.refreshReview();
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
          const name = this.currentStyle();
          return phrases.styles?.[name] ?? BLOCKS[name]?.label ?? 'Normal text';
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

        isChecklist(): boolean {
          this.tick;
          if (!root) return false;
          const range = currentRange(root);
          return range ? blocks.inChecklist(root, range) : false;
        },

        // ---- formatting commands ---------------------------------------
        toggle(mark: MarkName, value?: string) {
          this.markCommand(mark, value);
        },

        /** Whether edits are suggestions right now: Suggesting, in the body. */
        suggestingNow(): boolean {
          return this.editMode === 'suggesting' && !this.noteMode && !this.hfMode;
        },

        /**
         * A mark on the selection. While suggesting, a change to selected text
         * is a suggestion: made, and undone if rejected. Links apply at once.
         */
        markCommand(mark: MarkName, value?: string) {
          this.run((editor, range) => {
            const apply = () => toggleMark(editor, range, mark, value);
            if (this.suggestingNow() && !range.collapsed && FORMAT_MARKS.includes(mark)) {
              review.suggestFormat(editor, range, mark, Boolean(MARKS[mark].style), apply, this.authorName());
            } else {
              apply();
            }
          });
        },

        /** A change to blocks' look; while suggesting, a suggestion each block remembers its look for. */
        styleCommand(apply: (editor: HTMLElement, range: Range) => void) {
          this.run((editor, range) => {
            if (this.suggestingNow()) review.suggestStyle(editor, blocksInRange(range, editor), () => apply(editor, range), this.authorName());
            else apply(editor, range);
          });
        },

        setStyle(name: BlockName) {
          this.styleCommand((editor, range) => blocks.setBlockType(editor, range, name));
        },

        setFont(value: string) {
          this.markCommand('fontFamily', value);
        },

        /** Sizes are shown in points; CSS wants pixels. */
        setFontSize(points: number) {
          const px = `${Math.round(points * (4 / 3))}px`;
          this.markCommand('fontSize', px);
        },

        bumpFontSize(delta: number) {
          const index = FONT_SIZES.indexOf(this.currentFontSize());
          const next = FONT_SIZES[Math.max(0, Math.min(FONT_SIZES.length - 1, index + delta))];
          if (next) this.setFontSize(next);
        },

        setColor(value: string) {
          this.markCommand('color', value);
        },

        setHighlight(value: string) {
          this.markCommand('highlight', value);
        },

        setAlign(align: Alignment) {
          this.styleCommand((editor, range) => blocks.setAlignment(editor, range, align));
        },

        setSpacing(spacing: number) {
          this.styleCommand((editor, range) => blocks.setLineSpacing(editor, range, spacing));
        },

        toggleBulletList() {
          this.run((editor, range) => blocks.toggleList(editor, range, 'ul'));
        },

        toggleOrderedList() {
          this.run((editor, range) => blocks.toggleList(editor, range, 'ol'));
        },

        toggleChecklist() {
          this.run((editor, range) => blocks.toggleChecklist(editor, range));
        },

        /** lowercase, UPPERCASE, Title Case or Sentence case over the selection. */
        setCase(mode: CaseMode) {
          this.run((editor, range) => applyCase(editor, range, mode));
        },

        // ---- paint format ------------------------------------------------
        /** Pick up the formatting at the caret; double-click keeps it for several pastes. */
        togglePainter(sticky = false) {
          if (!root) return;
          if (this.painterOn && !sticky) {
            this.stopPainter();
            return;
          }
          const range = currentRange(root);
          if (!range) return;
          painted = Array.from(activeMarks(root, range))
            .filter((name) => name !== 'link')
            .map((name) => ({ name, value: markValue(root!, range, name) }));
          this.painterOn = true;
          this.painterSticky = sticky;
          root.closest('.nk-native')?.setAttribute('data-nk-painting', '');
        },

        stopPainter() {
          this.painterOn = false;
          this.painterSticky = false;
          root?.closest('.nk-native')?.removeAttribute('data-nk-painting');
        },

        applyPainter() {
          if (!root || !this.painterOn) return;
          const range = currentRange(root);
          if (!range) return;
          if (range.collapsed) {
            const word = wordAt(range);
            if (!word) return;
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(word);
          }
          const marks = painted.slice();
          this.run((editor, target) => {
            for (const name of Object.keys(MARKS) as MarkName[]) {
              if (name !== 'link') removeMark(editor, target, name);
            }
            for (const mark of marks) applyMark(editor, target, mark.name, mark.value ?? undefined);
          });
          if (!this.painterSticky) this.stopPainter();
        },

        // ---- word count --------------------------------------------------
        openWordCount() {
          if (!root) return;
          const text = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-content]'))
            .map((body) => body.textContent ?? '')
            .join('\n')
            .replace(/\u200B/g, '');
          const selection = window.getSelection();
          const range = currentRange(root);
          const picked = range && !range.collapsed ? (selection?.toString() ?? '') : '';
          const count = (value: string) => value.trim().split(/\s+/).filter(Boolean).length;
          this.wordStats = {
            pages: flow?.pageCount ?? 1,
            words: count(text),
            characters: text.length,
            charactersNoSpaces: text.replace(/\s/g, '').length,
            selectionWords: count(picked),
            selectionCharacters: picked.length,
            hasSelection: picked.length > 0,
          };
          this.showWordCount = true;
        },

        formatNumber(value: number): string {
          try {
            return new Intl.NumberFormat(config.locale || undefined).format(value);
          } catch {
            return String(value);
          }
        },

        // ---- find and replace --------------------------------------------
        openFind(replace = false) {
          if (!root) return;
          const range = currentRange(root);
          const picked = range && !range.collapsed ? range.toString() : '';
          if (picked && picked.length <= 80 && !/\n/.test(picked)) this.findQuery = picked;
          this.findOpen = true;
          if (replace) this.findReplace = true;
          this.findNotice = '';
          this.runFind(false);
          this.$nextTick(() => {
            const input = this.$refs.findInput as HTMLInputElement | undefined;
            input?.focus();
            input?.select();
          });
        },

        /** Close the panel, leaving the current match selected, as a word processor does. */
        closeFind() {
          const range = matches[this.findIndex];
          this.findOpen = false;
          this.findNotice = '';
          matches = [];
          clearMatches();
          root?.focus({ preventScroll: true });
          if (range && root) {
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
          }
        },

        findOptions() {
          return { matchCase: this.findCase, wholeWord: this.findWhole, regex: this.findRegex };
        },

        /** Search again after the query, an option or the document changed. */
        runFind(keepIndex = true) {
          if (!root || !this.findOpen) return;
          const compiled = compile(this.findQuery, this.findOptions());
          this.findError = compiled.error ?? '';
          matches = compiled.pattern ? findRanges(root, compiled.pattern) : [];
          this.findCount = matches.length;
          if (!keepIndex) {
            // Start from the caret, as a word processor does.
            const caret = currentRange(root);
            const after = caret
              ? matches.findIndex((match) => match.compareBoundaryPoints(Range.START_TO_START, caret) >= 0)
              : 0;
            this.findIndex = after > 0 ? after : 0;
          }
          this.findIndex = matches.length ? Math.min(this.findIndex, matches.length - 1) : 0;
          paintMatches(matches, matches.length ? this.findIndex : -1);
          if (!keepIndex) this.revealMatch();
        },

        findStep(delta: number) {
          if (!matches.length) return;
          this.findIndex = (this.findIndex + delta + matches.length) % matches.length;
          paintMatches(matches, this.findIndex);
          this.revealMatch();
        },

        revealMatch() {
          const range = matches[this.findIndex];
          const canvas = root?.closest('.nk-native')?.querySelector<HTMLElement>('.nk-native-canvas');
          if (!range || !canvas) return;
          const rect = range.getBoundingClientRect();
          const box = canvas.getBoundingClientRect();
          if (rect.top < box.top + 48 || rect.bottom > box.bottom - 48) {
            canvas.scrollBy({ top: rect.top - box.top - box.height / 3, behavior: 'smooth' });
          }
        },

        findLabel(): string {
          if (!this.findQuery) return '';
          if (this.findError) return say('findInvalid', 'Invalid expression');
          if (!this.findCount) return say('findNone', 'No results');
          return say('findCount', '{current} of {total}', { current: this.findIndex + 1, total: this.findCount });
        },

        replaceOne() {
          const range = matches[this.findIndex];
          if (!root || !range || !this.editable) return;
          const text = replacementFor(range.toString(), this.findQuery, this.replaceText, this.findOptions());
          withoutObserver(() => {
            range.deleteContents();
            if (text) range.insertNode(document.createTextNode(text));
            closestBlock(range.startContainer, root!)?.normalize();
            this.afterChange('command');
          });
          this.findNotice = say('findReplaced', '{count} replaced', { count: 1 });
          this.runFind(true);
          this.revealMatch();
        },

        replaceAll() {
          if (!root || !matches.length || !this.editable) return;
          const count = matches.length;
          withoutObserver(() => {
            // From the last match back, so earlier ones keep their place.
            for (const range of matches.slice().reverse()) {
              const text = replacementFor(range.toString(), this.findQuery, this.replaceText, this.findOptions());
              range.deleteContents();
              if (text) range.insertNode(document.createTextNode(text));
            }
            root!.querySelectorAll<HTMLElement>('[data-nk-content]').forEach((body) => body.normalize());
            this.afterChange('command');
          });
          this.findNotice = say('findReplaced', '{count} replaced', { count });
          this.runFind(false);
        },

        // ---- "/" menu ----------------------------------------------------
        slashItems() {
          const items: Array<{ id: string; group: string; label: string; icon: string; sub?: string; initials?: string; avatar?: string }> =
            this.slashTrigger === '@' ? this.mentionItems() : filterCommands(this.commandItems, this.slashText);
          return items.map((item, index) => ({
            ...item,
            index,
            heading: index === 0 || items[index - 1].group !== item.group ? (phrases.groups?.[item.group] ?? item.group) : '',
          }));
        },

        /** A "/" (or "@") typed at the start of a block or after a space opens the menu. */
        maybeOpenSlash(trigger: '/' | '@' = '/') {
          if (!root || !this.editable) return;
          const range = currentRange(root);
          if (!range || !range.collapsed) return;
          const block = closestBlock(range.startContainer, root);
          if (!block || block.tagName.toLowerCase() === 'pre') return;
          const at = offsetIn(block, range);
          const before = (block.textContent ?? '').charAt(at - 1);
          if (at > 0 && !/\s/.test(before)) return;
          // The browser inserts the slash after this handler; open once it is there.
          requestAnimationFrame(() => {
            slashBlockId = blockId(block);
            slashAt = at;
            this.slashTrigger = trigger;
            this.queryDate = null;
            remotePeople = [];
            this.slashText = '';
            this.slashIndex = 0;
            this.slashOpen = true;
            this.placeSlash();
            this.updateSlash();
          });
        },

        placeSlash() {
          const host = root?.closest<HTMLElement>('.nk-native');
          const range = root && currentRange(root);
          if (!host || !range) return;
          let rect = range.getBoundingClientRect();
          if (!rect.height) rect = (closestBlock(range.startContainer, root!) ?? root!).getBoundingClientRect();
          const box = host.getBoundingClientRect();
          const menuHeight = 320;
          const below = rect.bottom - box.top + 6;
          this.slashX = Math.max(8, Math.min(rect.left - box.left, box.width - 296));
          this.slashY = below + menuHeight > box.height && rect.top - box.top > menuHeight ? rect.top - box.top - menuHeight - 6 : below;
        },

        updateSlash() {
          if (!root || !this.slashOpen) return;
          const range = currentRange(root);
          const block = range ? closestBlock(range.startContainer, root) : null;
          if (!range || !block || block.getAttribute('data-nk-id') !== slashBlockId) {
            this.closeSlash();
            return;
          }
          const query = slashQuery(block.textContent ?? '', slashAt, offsetIn(block, range), this.slashTrigger);
          if (query === null) {
            this.closeSlash();
            return;
          }
          if (this.slashTrigger === '@' && query !== this.slashText) this.searchMentions(query);
          this.slashText = query;
          const count = this.slashItems().length;
          if (!count && /\s$/.test(query)) this.closeSlash();
          else this.slashIndex = Math.min(this.slashIndex, Math.max(0, count - 1));
        },

        closeSlash() {
          this.slashOpen = false;
          this.slashText = '';
        },

        /** Arrow keys, Enter, Tab and Escape while the menu is open. */
        slashKey(event: KeyboardEvent): boolean {
          const items = this.slashItems();
          if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (!items.length) return false;
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            this.slashIndex = (this.slashIndex + step + items.length) % items.length;
            this.$nextTick(() => {
              (this.$refs.slashMenu as HTMLElement | undefined)
                ?.querySelector('[aria-selected="true"]')
                ?.scrollIntoView({ block: 'nearest' });
            });
            return true;
          }
          if ((event.key === 'Enter' || event.key === 'Tab') && items[this.slashIndex]) {
            event.preventDefault();
            this.runSlash(items[this.slashIndex].id);
            return true;
          }
          // No one matches what was typed: Enter is just Enter.
          if (event.key === 'Enter' && !items.length) {
            this.closeSlash();
            return false;
          }
          if (event.key === 'Escape') {
            event.preventDefault();
            this.closeSlash();
            return true;
          }
          return false;
        },

        /** Remove the typed "/query" (or "@query"), then run the command or set the chip. */
        runSlash(id: string) {
          if (!root) return;
          const block = root.querySelector<HTMLElement>(`[data-nk-id="${CSS.escape(slashBlockId)}"]`);
          const range = currentRange(root);
          const mention = this.slashTrigger === '@' ? this.mentionItems().find((item) => item.id === id) : null;
          this.closeSlash();
          if (block && range) {
            withoutObserver(() => {
              const caret = offsetIn(block, range);
              const typed = rangeIn(runOf(block), slashAt, Math.max(slashAt + 1, caret));
              typed?.deleteContents();
              if (!(block.textContent ?? '').length && !block.querySelector('br, img')) {
                block.appendChild(document.createElement('br'));
              }
              setCaret(root!, block, slashAt);
              this.afterChange('command');
            });
          }
          if (mention) this.insertMention(mention);
          else this.runCommand(id);
        },

        // ---- "@": people and dates as chips -----------------------------
        /** Everyone and every date the "@" query keeps, people first. */
        mentionItems(): MentionItem[] {
          this.mentionTick;
          const query = this.slashText;
          const people: MentionItem[] = filterPeople(this.knownPeople(), query).map((person) => ({
            id: `person:${person.id}`,
            group: 'people',
            label: person.name,
            sub: person.email ?? '',
            keywords: [],
            icon: '',
            initials: initials(person.name),
            avatar: person.avatar ?? '',
          }));

          const day = (offset: number) => {
            const date = new Date();
            date.setDate(date.getDate() + offset);
            return date;
          };
          const named = [
            { key: 'today', label: say('chipToday', 'Today'), date: day(0), keywords: ['today', 'aujourd hui', 'date'] },
            { key: 'tomorrow', label: say('chipTomorrow', 'Tomorrow'), date: day(1), keywords: ['tomorrow', 'demain'] },
            { key: 'yesterday', label: say('chipYesterday', 'Yesterday'), date: day(-1), keywords: ['yesterday', 'hier'] },
          ];
          const q = fold(query.trim());
          const dates: MentionItem[] = named
            .filter((entry) => !q || fold(entry.label).startsWith(q) || entry.keywords.some((word) => word.startsWith(q)))
            .map((entry) => ({
              id: `date:${chips.isoDate(entry.date)}`,
              group: 'dates',
              label: entry.label,
              sub: this.formatDate(entry.date),
              keywords: [],
              icon: CALENDAR_ICON,
              initials: '',
              avatar: '',
            }));
          if (this.queryDate && !dates.some((item) => item.id === `date:${this.queryDate!.iso}`)) {
            dates.unshift({ id: `date:${this.queryDate.iso}`, group: 'dates', label: this.queryDate.label, sub: '', keywords: [], icon: CALENDAR_ICON, initials: '', avatar: '' });
          }

          return [...people, ...dates];
        },

        /** The people given to the editor and those its search found, once each. */
        knownPeople(): Person[] {
          const known = new Map<string, Person>();
          for (const person of [...(config.mentions ?? []), ...remotePeople]) {
            if (person && person.name && !known.has(String(person.id))) known.set(String(person.id), person);
          }
          return [...known.values()];
        },

        formatDate(date: Date, style: 'long' | 'full' = 'long'): string {
          try {
            return new Intl.DateTimeFormat(config.locale || undefined, style === 'full'
              ? { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
              : { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
          } catch {
            return date.toLocaleDateString();
          }
        },

        /**
         * Look further than the given people (the search endpoint) and read the
         * query as a date ("demain", "24 décembre", "next friday").
         */
        searchMentions(query: string) {
          naturalParser ??= import('../../forms/natural-date/parse');
          void naturalParser.then((parser) => {
            if (this.slashText !== query) return;
            const result = query.trim().length > 1 ? parser.parseNaturalDate(query, { locale: config.locale }) : null;
            const iso = result && result.kind === 'single' ? result.dates[0] : null;
            const date = iso ? chips.fromIso(iso) : null;
            this.queryDate = iso && date ? { iso, label: this.formatDate(date) } : null;
          }).catch(() => {});

          this.searchPeople(query);
        },

        /** Ask the search endpoint for people matching `query`; the lists follow when it answers. */
        searchPeople(query: string) {
          const url = config.mentionsUrl;
          if (!url) return;
          if (remoteTimer) clearTimeout(remoteTimer);
          remoteTimer = setTimeout(async () => {
            remoteAbort?.abort();
            remoteAbort = new AbortController();
            try {
              const response = await fetch(`${url}${url.includes('?') ? '&' : '?'}q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: remoteAbort.signal,
              });
              const payload = await response.json();
              const list = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
              remotePeople = list.filter((person: Person) => person && person.name).slice(0, 20);
              this.mentionTick += 1;
            } catch {
              // An aborted or failed search just leaves the given people.
            }
          }, 180);
        },

        /** Open the "@" menu from a command: type the "@" and listen. */
        startMention() {
          this.insertText('@');
          if (!root) return;
          const range = currentRange(root);
          const block = range ? closestBlock(range.startContainer, root) : null;
          if (!range || !block) return;
          slashBlockId = blockId(block);
          slashAt = offsetIn(block, range) - 1;
          this.slashTrigger = '@';
          this.queryDate = null;
          remotePeople = [];
          this.slashText = '';
          this.slashIndex = 0;
          this.slashOpen = true;
          this.placeSlash();
        },

        insertMention(item: MentionItem) {
          const [kind, value] = [item.id.slice(0, item.id.indexOf(':')), item.id.slice(item.id.indexOf(':') + 1)];
          if (kind === 'date') {
            const date = chips.fromIso(value);
            if (!date) return;
            this.run((_editor, range) => chips.insertChip(range, chips.dateChip(document, value, this.formatDate(date))));
            return;
          }
          const person = { id: value, name: item.label, email: item.sub || undefined };
          this.run((_editor, range) => chips.insertChip(range, chips.personChip(document, person)));
          this.$dispatch('editor-mention', { id: value, name: item.label, email: item.sub || null });
        },

        /** A chip's card: who or when, with what can be done with it. */
        openChip(chip: HTMLElement) {
          const info = chips.readChip(chip);
          const date = info.kind === 'date' ? chips.fromIso(info.date) : null;
          pickedChip = chip;
          this.chipCard = {
            kind: info.kind,
            label: info.label,
            email: info.email,
            initials: initials(info.label),
            date: info.date,
            long: date ? this.formatDate(date, 'full') : '',
          };
          this.chipOpen = true;
          this.$nextTick(() => this.placeChip());
        },

        placeChip() {
          const stage = root?.closest<HTMLElement>('.nk-native-stage');
          if (!pickedChip || !stage) return;
          const area = stage.getBoundingClientRect();
          const box = pickedChip.getBoundingClientRect();
          const card = this.$refs.chipCard as HTMLElement | undefined;
          const width = card?.offsetWidth || 280;
          const height = card?.offsetHeight || 120;
          const below = box.bottom - area.top + 6 + height < area.height;
          this.chipBox = {
            left: Math.max(8, Math.min(box.left - area.left, area.width - width - 8)),
            top: below ? box.bottom - area.top + 6 : Math.max(8, box.top - area.top - height - 6),
          };
        },

        closeChip() {
          pickedChip = null;
          this.chipOpen = false;
        },

        setChipDate(iso: string) {
          const chip = pickedChip;
          const date = chips.fromIso(iso);
          if (!chip || !date || !this.editable) return;
          withoutObserver(() => {
            chips.setChipDate(chip, iso, this.formatDate(date));
            this.afterChange('command');
          });
          this.openChip(chip);
        },

        removeChip() {
          const chip = pickedChip;
          if (!chip || !root || !this.editable) return;
          this.closeChip();
          withoutObserver(() => {
            // The space the chip brought goes with it.
            const next = chip.nextSibling;
            if (next?.nodeType === Node.TEXT_NODE && next.nodeValue?.startsWith('\u00a0')) next.nodeValue = next.nodeValue.slice(1);
            const block = closestBlock(chip, root!);
            chip.remove();
            if (block && !(block.textContent ?? '').length && !block.querySelector('br, img')) block.appendChild(document.createElement('br'));
            this.afterChange('command');
          });
        },

        // ---- table of contents ------------------------------------------
        insertToc(style: contents.TocStyle = 'pages') {
          this.run((editor, range) => {
            const toc = contents.makeToc(document, style);
            setCaret(editor, blocks.placeObject(editor, range, toc), 0);
          });
        },

        /** Pick a table of contents: its toolbar shows, the caret waits below it. */
        selectToc(toc: HTMLElement) {
          if (!root) return;
          this.deselectToc();
          pickedToc = toc;
          toc.setAttribute('data-nk-picked', '');
          this.tocStyle = contents.styleOf(toc);
          root.focus({ preventScroll: true });
          withoutObserver(() => setCaret(root!, images.blockAfter(toc), 0));
          this.tocOpen = true;
          this.$nextTick(() => this.placeToc());
        },

        deselectToc() {
          pickedToc?.removeAttribute('data-nk-picked');
          pickedToc = null;
          this.tocOpen = false;
        },

        placeToc() {
          const stage = root?.closest<HTMLElement>('.nk-native-stage');
          if (!pickedToc || !stage) return;
          const area = stage.getBoundingClientRect();
          const box = pickedToc.getBoundingClientRect();
          const bar = this.$refs.tocBar as HTMLElement | undefined;
          const height = bar?.offsetHeight || 36;
          this.tocBar = {
            left: Math.max(8, box.left - area.left),
            top: box.top - area.top - height - 6 > 4 ? box.top - area.top - height - 6 : box.bottom - area.top + 6,
          };
        },

        /** Keys while a table of contents is picked. True when handled. */
        tocKey(event: KeyboardEvent): boolean {
          if (['Shift', 'Control', 'Meta', 'Alt'].includes(event.key)) return true;
          if (event.key === 'Delete' || event.key === 'Backspace') {
            event.preventDefault();
            this.deleteToc();
            return true;
          }
          this.deselectToc();
          return event.key === 'Escape';
        },

        setTocStyle(style: contents.TocStyle) {
          const toc = pickedToc;
          if (!toc || !this.editable) return;
          withoutObserver(() => {
            toc.setAttribute('data-toc', style);
            this.afterChange('command');
          });
          this.tocStyle = style;
        },

        deleteToc() {
          const toc = pickedToc;
          if (!toc || !root || !this.editable) return;
          this.deselectToc();
          withoutObserver(() => {
            const landing = images.blockAfter(toc);
            toc.remove();
            setCaret(root!, landing, 0);
            this.afterChange('command');
          });
        },

        /** Follow a table of contents entry to its heading. */
        jumpToHeading(anchor: string) {
          if (!root) return;
          const heading = contents.headingFor(root, anchor);
          if (!heading) return;
          heading.scrollIntoView({ block: 'start', behavior: 'smooth' });
          root.focus({ preventScroll: true });
          setCaret(root, heading, 0);
        },

        // ---- special characters -----------------------------------------
        /** The panel opens beside the text; the caret it was opened at is kept. */
        openCharacters() {
          if (!root) return;
          charsCaret = saveSelection(root);
          try {
            const stored = JSON.parse(window.localStorage.getItem('neura-editor:recent-characters') ?? '[]');
            this.charsRecent = Array.isArray(stored) ? stored.filter((char) => typeof char === 'string').slice(0, 24) : [];
          } catch {
            this.charsRecent = [];
          }
          if (this.charsRecent.length && this.charsGroup === 'emoji') this.charsGroup = 'recent';
          this.charsQuery = '';
          this.charsOpen = true;
          this.$nextTick(() => (this.$refs.charsSearch as HTMLInputElement | undefined)?.focus());
        },

        characterList(): CharacterEntry[] {
          if (!this.charsQuery.trim() && this.charsGroup === 'recent') {
            return this.charsRecent
              .map((char) => CHARACTERS.find((entry) => entry.char === char))
              .filter((entry): entry is CharacterEntry => Boolean(entry));
          }
          return findCharacters(this.charsQuery, this.charsQuery.trim() ? null : (this.charsGroup as CharacterGroup));
        },

        /** Put a character at the caret; the panel stays open for the next one. */
        insertCharacter(char: string) {
          if (!root) return;
          root.focus({ preventScroll: true });
          restoreSelection(root, charsCaret);
          this.insertText(char);
          charsCaret = saveSelection(root);
          this.charsRecent = [char, ...this.charsRecent.filter((recent) => recent !== char)].slice(0, 24);
          try {
            window.localStorage.setItem('neura-editor:recent-characters', JSON.stringify(this.charsRecent));
          } catch {
            // A private window keeps no recent characters; nothing else changes.
          }
        },

        /** A character as its key shows it: spaces are drawn so they can be seen. */
        characterFace(char: string): string {
          return char.trim() === '' ? '␣' : char;
        },

        // ---- voice typing -------------------------------------------------
        toggleVoice() {
          if (this.voiceOpen && this.voiceListening) {
            this.closeVoice();
            return;
          }
          this.voiceOpen = true;
          this.startVoice();
        },

        /** Listen until stopped: the recogniser rests after a silence and is started again. */
        startVoice() {
          this.voiceError = '';
          if (!Recognition) {
            this.voiceError = say('voiceUnsupported', 'Voice typing is not available in this browser. Use Chrome, Edge or Safari.');
            return;
          }
          if (!root || !this.editable) return;
          if (!currentRange(root)) this.focus();

          recognition?.abort?.();
          recognition = new Recognition();
          recognition.lang = this.voiceLang;
          recognition.continuous = true;
          recognition.interimResults = true;
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          recognition.onresult = (event: any) => {
            let interim = '';
            for (let index = event.resultIndex; index < event.results.length; index += 1) {
              const result = event.results[index];
              if (result.isFinal) this.dictate(result[0].transcript);
              else interim += result[0].transcript;
            }
            this.voiceInterim = interim;
          };
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          recognition.onerror = (event: any) => {
            if (event.error === 'no-speech' || event.error === 'aborted') return;
            this.voiceListening = false;
            this.voiceError = event.error === 'not-allowed' || event.error === 'service-not-allowed'
              ? say('voiceDenied', 'The microphone is not allowed for this page.')
              : say('voiceFailed', 'Voice typing stopped ({reason}).', { reason: String(event.error) });
          };
          recognition.onend = () => {
            if (this.voiceListening) {
              try {
                recognition.start();
              } catch {
                this.voiceListening = false;
              }
            }
          };
          try {
            recognition.start();
            this.voiceListening = true;
          } catch (error) {
            this.voiceError = String(error);
          }
        },

        stopVoice() {
          this.voiceListening = false;
          this.voiceInterim = '';
          recognition?.stop?.();
        },

        closeVoice() {
          this.stopVoice();
          this.voiceOpen = false;
        },

        /** What to say for punctuation, in the language being dictated. */
        voiceHint(): string {
          const french = this.voiceLang.toLowerCase().startsWith('fr');
          return say('voiceListening', 'Listening… say {comma}, {line}…', {
            comma: french ? '« virgule »' : '“comma”',
            line: french ? '« à la ligne »' : '“new line”',
          });
        },

        setVoiceLang(lang: string) {
          this.voiceLang = lang;
          if (this.voiceListening) {
            this.stopVoice();
            this.startVoice();
          }
        },

        /** What was heard, put in at the caret: said punctuation, new lines and paragraphs included. */
        dictate(transcript: string) {
          if (!root || !this.editable) return;
          for (const piece of parseDictation(transcript, this.voiceLang)) {
            if ('command' in piece) {
              if (piece.command === 'line') this.insertBreak('line');
              else this.run((editor, range) => breakParagraph(editor, range));
              continue;
            }
            const range = currentRange(root);
            this.insertText(joinDictation(range ? this.lineBeforeCaret(range) : '', piece.text, this.voiceLang));
          }
        },

        /** The text of the caret's line up to it: after the last line break, if any. */
        lineBeforeCaret(range: Range): string {
          const block = root ? closestBlock(range.startContainer, root) : null;
          if (!block) return '';
          const probe = document.createRange();
          probe.selectNodeContents(block);
          probe.setEnd(range.startContainer, range.startOffset);
          let line = '';
          const walker = document.createTreeWalker(probe.cloneContents(), NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT);
          for (let node = walker.nextNode(); node; node = walker.nextNode()) {
            if (node.nodeType === Node.ELEMENT_NODE && (node as Element).tagName === 'BR') line = '';
            else if (node.nodeType === Node.TEXT_NODE) line += node.nodeValue ?? '';
          }
          return line;
        },

        // ---- keyboard shortcuts -------------------------------------------
        /** A key as this computer names it: ⌘ ⌥ ⇧ on a Mac. */
        keyName(key: string): string {
          if (!isMac) return key;
          return ({ Ctrl: '⌘', Alt: '⌥', Shift: '⇧', Enter: '↩', Backspace: '⌫' } as Record<string, string>)[key] ?? key;
        },

        shortcutShown(text: string): boolean {
          const query = fold(this.shortcutsQuery.trim());
          return !query || fold(text).includes(query);
        },

        // ---- version history --------------------------------------------
        /**
         * Open the store and catch up: a document seen for the first time gets
         * its first version, so there is always something to go back to.
         */
        async startHistory() {
          if (!historyOn || !root) return;
          const editors = Array.from(document.querySelectorAll('[data-nk-editor]'));
          const key = config.history?.key || `${location.pathname}#${editors.indexOf((this as any).$el)}`;
          versionStore = config.history?.url
            ? remoteStore(config.history.url, key, () => this.csrfToken() || null)
            : browserStore(key, Math.max(1, config.history?.limit ?? 100));

          // The newest version is kept when the page is left or hidden.
          const flush = () => {
            if (snapshotTimer) void this.snapshot();
          };
          window.addEventListener('pagehide', flush);
          document.addEventListener('visibilitychange', () => document.visibilityState === 'hidden' && flush());

          try {
            this.versions = await versionStore.list();
            const newest = this.versions[0];
            newestHtml = newest ? ((await this.versionContent(newest.id)) ?? null) : null;
            if (!newest && editable && (this.characters > 0 || root.querySelector('img, table, hr, nav'))) await this.snapshot();
          } catch (error) {
            this.historyFailed(error);
          }
        },

        /** A snapshot once editing pauses. */
        scheduleSnapshot() {
          if (!versionStore || !this.editable) return;
          if (snapshotTimer) clearTimeout(snapshotTimer);
          snapshotTimer = setTimeout(() => void this.snapshot(), historyIdle);
        },

        /**
         * Record the document: a new version, or the newest one brought up to
         * date when it is recent and unnamed. `separate` always makes a new one
         * when anything changed (before and after a restore).
         */
        snapshot(separate = false): Promise<VersionMeta | null> {
          if (snapshotTimer) clearTimeout(snapshotTimer);
          snapshotTimer = null;
          const run = async (): Promise<VersionMeta | null> => {
            if (!versionStore || !root) return null;
            const html = this.snapshotHtml();
            const changed = html !== newestHtml;
            const newest = this.versions[0] ?? null;
            const plan = separate && changed ? 'add' : planSnapshot(newest, changed, Date.now(), historyInterval);
            if (plan === 'skip') return newest;

            const time = new Date().toISOString();
            const author = this.authorName();
            try {
              let id: string;
              if (plan === 'add') {
                id = (await versionStore.add({ time, name: '', authors: [author], html })).id;
              } else {
                id = newest!.id;
                await versionStore.update(id, { time, authors: [...new Set([...newest!.authors, author])], html });
              }
              newestHtml = html;
              versionHtml.set(id, html);
              this.versions = await versionStore.list();
              const version = this.versions.find((candidate) => candidate.id === id) ?? null;
              this.$dispatch('editor-version', { action: plan, version });
              return version;
            } catch (error) {
              this.historyFailed(error);
              return null;
            }
          };
          const next = snapshots.then(run, run);
          snapshots = next.catch(() => null);
          return next;
        },

        historyFailed(error: unknown) {
          const reason = error instanceof Error ? error.message : String(error);
          console.warn(`[neura native editor] version history: ${reason}`);
          this.$dispatch('editor-history-failed', { reason });
        },

        async versionContent(id: string): Promise<string | null> {
          if (versionHtml.has(id)) return versionHtml.get(id)!;
          const version = await versionStore?.get(id);
          if (version) versionHtml.set(id, version.html);
          return version?.html ?? null;
        },

        /** Open the history: the document is recorded first, so the newest version is what is on screen. */
        async openHistory() {
          if (!versionStore || this.historyOpen) return;
          if (this.noteMode) this.exitNote(false);
          if (this.hfMode) this.exitHeaderFooter();
          this.closeSlash();
          this.findOpen = false;
          this.deselectImage();
          this.closeChip();
          this.historyBusy = true;
          this.historyOpen = true;
          this.historyNotice = '';
          await this.snapshot();
          try {
            this.versions = await versionStore.list();
          } catch (error) {
            this.historyFailed(error);
          }
          this.historyBusy = false;
          if (this.versions[0]) await this.selectVersion(this.versions[0].id);
        },

        closeHistory() {
          this.historyOpen = false;
          this.renamingVersion = '';
          this.$nextTick(() => this.focus());
        },

        /** Show a version, marked against the one before it. */
        async selectVersion(id: string) {
          this.selectedVersion = id;
          this.renamingVersion = this.renamingVersion === id ? id : '';
          await this.renderSelected();
        },

        async renderSelected() {
          const index = this.versions.findIndex((version) => version.id === this.selectedVersion);
          const target = this.$refs.historyDoc as HTMLElement | undefined;
          if (index < 0 || !target) return;
          this.historyBusy = true;
          try {
            const html = await this.versionContent(this.versions[index].id);
            const older = this.versions[index + 1];
            const previous = this.historyDiff && older ? await this.versionContent(older.id) : null;
            if (this.selectedVersion === this.versions[index].id) renderVersion(target, html ?? '', previous);
          } catch (error) {
            this.historyFailed(error);
          }
          this.historyBusy = false;
        },

        toggleHistoryDiff() {
          this.historyDiff = !this.historyDiff;
          void this.renderSelected();
        },

        /** Make the shown version the document again; what was there stays a version of its own. */
        async restoreVersion() {
          const version = this.versions.find((candidate) => candidate.id === this.selectedVersion);
          if (!root || !version || version.id === this.versions[0]?.id || !editable) return;
          const html = await this.versionContent(version.id);
          if (html === null) return;

          await this.snapshot(true);
          this.closeHistory();
          this.deselectToc();
          this.clearCells();
          withoutObserver(() => {
            const fragment = sanitizeHtml(html);
            this.threads = review.takeThreads(fragment);
            flow!.load(fragment);
            tables.paintTables(root!);
            this.decorateFigures();
            if (this.prepare()) flow!.reflow();
            contents.paintTocPages(root!);
            this.afterChange('command');
          });
          const restored = await this.snapshot(true);
          const name = version.name || this.versionTime(version.time);
          this.historyNotice = say('versionRestored', '“{name}” is the current version again.', { name });
          setTimeout(() => (this.historyNotice = ''), 4000);
          this.$dispatch('editor-version', { action: 'restore', version: restored, from: version });
        },

        renameVersion(id: string) {
          if (this.renamingVersion === id) {
            this.renamingVersion = '';
            return;
          }
          this.renamingVersion = id;
          this.versionDraft = this.versions.find((version) => version.id === id)?.name ?? '';
          this.$nextTick(() => (this.$refs.historyList as HTMLElement | undefined)?.querySelector<HTMLInputElement>('[data-version-name]')?.focus());
        },

        async saveVersionName(id: string) {
          if (!versionStore) return;
          const name = this.versionDraft.trim().slice(0, 120);
          this.renamingVersion = '';
          try {
            await versionStore.update(id, { name });
            this.versions = await versionStore.list();
            this.$dispatch('editor-version', { action: 'rename', version: this.versions.find((version) => version.id === id) ?? null });
          } catch (error) {
            this.historyFailed(error);
          }
        },

        /** Name what is on screen: it is recorded, then its name asked for in the history. */
        async nameCurrentVersion() {
          await this.openHistory();
          const newest = this.versions[0];
          if (newest) this.renameVersion(newest.id);
        },

        /** The versions to list, by day, as the filter asks. */
        versionGroups() {
          const shown = this.historyNamed ? this.versions.filter((version) => version.name) : this.versions;
          return dayGroups(shown, new Date(), { today: say('chipToday', 'Today'), yesterday: say('chipYesterday', 'Yesterday') }, (date) => {
            try {
              return new Intl.DateTimeFormat(config.locale || undefined, { weekday: 'long', day: 'numeric', month: 'long' }).format(date);
            } catch {
              return date.toDateString();
            }
          });
        },

        versionTime(iso: string): string {
          try {
            return new Intl.DateTimeFormat(config.locale || undefined, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
          } catch {
            return new Date(iso).toLocaleString();
          }
        },

        versionTitle(version: VersionMeta | undefined): string {
          return version ? version.name || this.versionTime(version.time) : '';
        },

        /** Under the shown version's title: when, who, and what it is compared with. */
        versionSubtitle(): string {
          const index = this.versions.findIndex((version) => version.id === this.selectedVersion);
          const version = this.versions[index];
          if (!version) return '';
          const parts = [version.name ? this.versionTime(version.time) : '', version.authors.join(', ')].filter(Boolean);
          if (this.historyDiff && this.versions[index + 1]) parts.push(say('comparedWithPrevious', 'compared with the previous version'));
          return parts.join(' · ');
        },

        authorColor(name: string): string {
          return authorColor(name);
        },

        // ---- review: value ----------------------------------------------
        /** The document read from a value, its comment threads taken aside. */
        readValue(value: unknown): DocumentFragment {
          const fragment = fromValue(value, this.mode);
          let threads = review.takeThreads(fragment);
          if (this.mode === 'json') {
            let parsed: unknown = value;
            if (typeof value === 'string') {
              try {
                parsed = value.trim() ? JSON.parse(value) : null;
              } catch {
                parsed = null;
              }
            }
            if (parsed && typeof parsed === 'object' && 'comments' in parsed) {
              threads = review.validThreads((parsed as { comments: unknown }).comments);
            }
          }
          this.threads = threads;
          return fragment;
        },

        /** The value pushed out: the document, with its comment threads. */
        serialize(): unknown {
          if (!root) return this.mode === 'json' ? { type: 'doc', content: [] } : '';
          if (this.mode === 'json') {
            const doc = toJson(root) as JsonDoc & { comments?: review.Thread[] };
            if (this.threads.length) doc.comments = JSON.parse(JSON.stringify(this.threads));
            return doc;
          }
          return this.snapshotHtml();
        },

        /** HTML with the threads, as history keeps it: undo brings comments back too. */
        snapshotHtml(): string {
          if (!root) return '';
          return toHtml(root) + review.threadsHtml(document, JSON.parse(JSON.stringify(this.threads)));
        },

        initialsOf(name: string): string {
          return initials(name || '?');
        },

        authorName(): string {
          return (config.author?.name ?? '').trim() || say('you', 'You');
        },

        // ---- review: panel ----------------------------------------------
        /** Suggestions and the order of everything, after the document changed. */
        refreshReview() {
          if (!root) return;
          this.suggestions = review.suggestionsIn(root);
          this.reviewOrder = Object.fromEntries(review.orderOf(root));
        },

        /** Comments and suggestions, in the order they appear in the text. */
        reviewItems() {
          const order = this.reviewOrder;
          const items = [
            ...this.threads
              .filter((thread) => this.showResolved || !thread.resolved || thread.id === this.activeReview)
              .map((thread) => ({ key: thread.id, kind: 'thread', thread, suggestion: null as review.Suggestion | null, at: order[thread.id] ?? 1e9, orphan: !(thread.id in order) })),
            ...this.suggestions.map((suggestion) => ({ key: suggestion.id, kind: 'suggestion', thread: null as review.Thread | null, suggestion, at: order[suggestion.id] ?? 1e9, orphan: false })),
          ];
          return items.sort((a, b) => a.at - b.at);
        },

        openCount(): number {
          return this.threads.filter((thread) => !thread.resolved).length + this.suggestions.length;
        },

        toggleReview(open?: boolean) {
          this.reviewOpen = open ?? !this.reviewOpen;
          this.$nextTick(() => this.fitZoom());
        },

        /** The comment or suggestion whose card is open, marked in the text too. */
        setActive(id: string) {
          this.activeReview = id;
          if (root) review.markActive(root, id || null);
        },

        /** Bring a comment or a suggestion forward: its card opens, its text shows. */
        focusReview(id: string, scroll = true) {
          if (!root) return;
          if (this.activeReview !== id) {
            this.replyText = '';
            if (this.editingComment && this.editingComment.thread !== id) this.cancelEdit();
          }
          this.activeReview = id;
          review.markActive(root, id);
          if (!this.reviewOpen) this.toggleReview(true);
          if (scroll) {
            const target = root.querySelector(`[data-nk-content] :is([data-comment="${CSS.escape(id)}"], [data-suggest="${CSS.escape(id)}"])`);
            target?.scrollIntoView({ block: 'center', behavior: 'smooth' });
          }
          this.$nextTick(() => {
            (this.$refs.reviewList as HTMLElement | undefined)
              ?.querySelector(`[data-review="${CSS.escape(id)}"]`)
              ?.scrollIntoView({ block: 'nearest' });
          });
        },

        /** The caret in commented or suggested text opens its card; a selection offers a comment. */
        syncReview() {
          if (!root) return;
          const selection = window.getSelection();
          const anchor = selection?.anchorNode ?? null;
          if (!anchor || !root.contains(anchor)) return;

          const at = review.reviewAt(anchor);
          // A resolved thread stays out of the way unless resolved ones are shown.
          const thread = at.comment ? this.threads.find((candidate) => candidate.id === at.comment) : null;
          const id = (thread && (!thread.resolved || this.showResolved) ? thread.id : null) ?? at.suggestion;
          if (id && id !== this.activeReview) {
            this.activeReview = id;
            this.replyText = '';
            review.markActive(root, id);
            if (this.reviewOpen) this.focusReview(id, false);
          }

          this.placeCommentFab();
        },

        /** The "add a comment" button beside a selection, in the page's margin. */
        placeCommentFab() {
          const range = root ? currentRange(root) : null;
          const stage = root?.closest<HTMLElement>('.nk-native-stage');
          const body = range && !range.collapsed ? (closestBlock(range.startContainer, root!)?.closest<HTMLElement>('[data-nk-content]') ?? null) : null;
          if (!range || !stage || !body || !editable || this.noteMode || this.hfMode || range.toString().trim() === '') {
            if (this.commentFab.show) this.commentFab = { show: false, left: 0, top: 0 };
            return;
          }
          const area = stage.getBoundingClientRect();
          const page = (body.closest('[data-nk-page]') as HTMLElement | null)?.getBoundingClientRect() ?? body.getBoundingClientRect();
          const rect = range.getBoundingClientRect();
          const room = area.right - page.right;
          this.commentFab = {
            show: true,
            left: room > 52 ? page.right - area.left + 10 : page.right - area.left - 44,
            top: Math.max(4, rect.top - area.top - 4),
          };
        },

        // ---- review: comments -------------------------------------------
        /** Comment on the selection (or the word at the caret): a draft card opens. */
        startComment() {
          if (!root || !editable || this.noteMode || this.hfMode) return;
          let range = currentRange(root);
          if (!range) return;
          if (range.collapsed) {
            const word = wordAt(range);
            if (!word) return;
            range = word;
          }
          if (this.draftId) this.cancelDraft();

          const id = review.newCommentId();
          const target = range;
          withoutObserver(() => {
            if (!review.addAnchor(root!, target, id).length) return;
            this.draftId = id;
            this.afterChange('command');
          });
          if (!this.draftId) return;
          this.draftText = '';
          this.commentFab = { show: false, left: 0, top: 0 };
          window.getSelection()?.removeAllRanges();
          this.focusReview(id, false);
          this.$nextTick(() => (this.$refs.draftInput as HTMLTextAreaElement | undefined)?.focus());
        },

        saveDraft() {
          const text = this.draftText.trim();
          if (!root || !this.draftId || !text) return;
          const comment = this.newComment(text);
          const thread: review.Thread = {
            id: this.draftId,
            resolved: false,
            quote: review.quoteOf(root, this.draftId),
            comments: [comment],
          };
          this.threads = [...this.threads, thread];
          this.draftId = '';
          this.draftText = '';
          withoutObserver(() => this.afterChange('command'));
          this.focusReview(thread.id, false);
          this.$dispatch('editor-comment', { action: 'add', thread: JSON.parse(JSON.stringify(thread)) });
          this.tellMentioned(comment.mentions ?? [], thread.id, text);
        },

        cancelDraft() {
          if (!root || !this.draftId) return;
          const id = this.draftId;
          this.draftId = '';
          this.draftText = '';
          withoutObserver(() => {
            review.removeAnchors(root!, id);
            this.afterChange('command');
          });
        },

        /** Change a thread and record it, so undo brings it back. */
        updateThread(id: string, change: (thread: review.Thread) => review.Thread | null, action: string) {
          const current = this.threads.find((thread) => thread.id === id);
          if (!current) return;
          const next = change(JSON.parse(JSON.stringify(current)));
          this.threads = next ? this.threads.map((thread) => (thread.id === id ? next : thread)) : this.threads.filter((thread) => thread.id !== id);
          withoutObserver(() => {
            if (!next) review.removeAnchors(root!, id);
            this.afterChange('command');
          });
          this.$dispatch('editor-comment', { action, thread: next ?? current });
        },

        reply(id: string) {
          const text = this.replyText.trim();
          if (!text) return;
          this.replyText = '';
          this.commentMention = null;
          const comment = this.newComment(text);
          this.updateThread(id, (thread) => ({ ...thread, comments: [...thread.comments, comment] }), 'reply');
          this.tellMentioned(comment.mentions ?? [], id, text);
        },

        /** A comment by the person writing, with whoever its text mentions. */
        newComment(text: string): ReviewComment {
          const comment: ReviewComment = { author: this.authorName(), time: new Date().toISOString(), text };
          const mentions = mentionsIn(text, [...pickedPeople.values()]);
          if (mentions.length) comment.mentions = mentions;
          return comment;
        },

        /** Each person mentioned is announced, for the app to tell them. */
        tellMentioned(people: CommentMention[], thread: string, text: string) {
          for (const person of people) {
            this.$dispatch('editor-mention', { id: person.id, name: person.name, email: person.email, comment: { thread, text } });
          }
        },

        /** Only one's own comments can be changed or deleted. */
        isMine(comment: ReviewComment): boolean {
          return this.canReview && comment.author === this.authorName();
        },

        isEditing(thread: string, index: number): boolean {
          return this.editingComment?.thread === thread && this.editingComment.index === index;
        },

        startEdit(id: string, index: number) {
          const comment = this.threads.find((thread) => thread.id === id)?.comments[index];
          if (!comment) return;
          for (const person of comment.mentions ?? []) pickedPeople.set(person.id, person);
          this.editingComment = { thread: id, index };
          this.editText = comment.text;
          this.commentMention = null;
          this.$nextTick(() => {
            const box = (this.$refs.reviewList as HTMLElement | undefined)?.querySelector<HTMLTextAreaElement>(`[data-review="${CSS.escape(id)}"] .nk-review-edit textarea`);
            box?.focus();
            box?.setSelectionRange(box.value.length, box.value.length);
          });
        },

        saveEdit() {
          const editing = this.editingComment;
          const text = this.editText.trim();
          if (!editing || !text) return;
          const before = this.threads.find((thread) => thread.id === editing.thread)?.comments[editing.index];
          if (!before) return this.cancelEdit();
          this.cancelEdit();
          if (text === before.text) return;

          const mentions = mentionsIn(text, [...(before.mentions ?? []), ...pickedPeople.values()]);
          const comment: ReviewComment = { ...before, text, edited: new Date().toISOString() };
          if (mentions.length) comment.mentions = mentions;
          else delete comment.mentions;
          this.updateThread(editing.thread, (thread) => ({ ...thread, comments: thread.comments.map((other, index) => (index === editing.index ? comment : other)) }), 'edit');
          // Only those mentioned for the first time are told.
          this.tellMentioned(mentions.filter((person) => !(before.mentions ?? []).some((other) => other.id === person.id)), editing.thread, text);
        },

        cancelEdit() {
          this.editingComment = null;
          this.editText = '';
          this.commentMention = null;
        },

        /** Delete a reply; deleting the first comment deletes the thread. */
        deleteComment(id: string, index: number) {
          if (index === 0) return this.deleteThread(id);
          this.updateThread(id, (thread) => ({ ...thread, comments: thread.comments.filter((_, at) => at !== index) }), 'remove');
        },

        /** A comment's text in runs, the people it mentions apart. */
        commentRuns(comment: ReviewComment) {
          return commentRuns(comment.text, comment.mentions);
        },

        // ---- review: "@" in a comment -----------------------------------
        commentText(field: CommentField): string {
          return field === 'draft' ? this.draftText : field === 'reply' ? this.replyText : this.editText;
        },

        setCommentText(field: CommentField, text: string) {
          if (field === 'draft') this.draftText = text;
          else if (field === 'reply') this.replyText = text;
          else this.editText = text;
        },

        /** Typing in a comment box: an "@" lists the people to mention. */
        commentInput(event: Event, field: CommentField) {
          const box = event.target as HTMLTextAreaElement;
          commentBox = box;
          const found = mentionQuery(box.value, box.selectionStart ?? box.value.length);
          if (!found) {
            this.commentMention = null;
            return;
          }
          const changed = this.commentMention?.field !== field || this.commentMention.query !== found.query;
          this.commentMention = { field, ...found };
          if (changed) {
            this.commentMentionIndex = 0;
            this.searchPeople(found.query);
          }
        },

        /** The people the "@" being typed in a comment keeps. */
        commentPeople(): Person[] {
          this.mentionTick;
          return this.commentMention ? filterPeople(this.knownPeople(), this.commentMention.query) : [];
        },

        pickCommentPerson(person: Person) {
          const mention = this.commentMention;
          if (!mention) return;
          const box = commentBox;
          const text = this.commentText(mention.field);
          const caret = box && box.isConnected ? (box.selectionStart ?? text.length) : mention.at + 1 + mention.query.length;
          const next = putMention(text, mention.at, caret, person.name);
          this.setCommentText(mention.field, next.text);
          pickedPeople.set(String(person.id), { id: String(person.id), name: person.name, email: person.email ?? '' });
          this.commentMention = null;
          this.$nextTick(() => {
            box?.focus();
            box?.setSelectionRange(next.caret, next.caret);
          });
        },

        /** Keys in a comment box: the "@" list first, then Ctrl+Enter to send and Escape to drop. */
        commentKeys(event: KeyboardEvent, field: CommentField, id = '') {
          const people = this.commentMention?.field === field ? this.commentPeople() : [];
          if (people.length && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            this.commentMentionIndex = (this.commentMentionIndex + step + people.length) % people.length;
            return;
          }
          if (people.length && (event.key === 'Enter' || event.key === 'Tab') && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
            event.preventDefault();
            this.pickCommentPerson(people[Math.min(this.commentMentionIndex, people.length - 1)]);
            return;
          }
          if (event.key === 'Escape' && this.commentMention) {
            event.preventDefault();
            event.stopPropagation();
            this.commentMention = null;
            return;
          }
          if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            if (field === 'draft') this.saveDraft();
            else if (field === 'reply') this.reply(id);
            else this.saveEdit();
          } else if (event.key === 'Escape') {
            event.stopPropagation();
            if (field === 'draft') this.cancelDraft();
            else if (field === 'edit') this.cancelEdit();
            else this.replyText = '';
          }
        },

        resolveThread(id: string, resolved = true) {
          this.updateThread(id, (thread) => ({ ...thread, resolved }), resolved ? 'resolve' : 'reopen');
          if (resolved && this.activeReview === id && !this.showResolved) this.setActive('');
        },

        deleteThread(id: string) {
          this.updateThread(id, () => null, 'delete');
          if (this.activeReview === id) this.setActive('');
        },

        /** "5 min ago", "yesterday", or the date, in the editor's language. */
        when(iso: string): string {
          const time = Date.parse(iso);
          if (Number.isNaN(time)) return '';
          const seconds = Math.round((time - Date.now()) / 1000);
          const steps: Array<[Intl.RelativeTimeFormatUnit, number]> = [['second', 60], ['minute', 60], ['hour', 24], ['day', 7]];
          let value = seconds;
          try {
            const format = new Intl.RelativeTimeFormat(config.locale || undefined, { numeric: 'auto' });
            if (Math.abs(value) < 45) return format.format(0, 'second');
            for (const [unit, size] of steps) {
              if (Math.abs(value) < size) return format.format(value, unit);
              value = Math.round(value / size);
            }
            return this.formatDate(new Date(time));
          } catch {
            return new Date(time).toLocaleString();
          }
        },

        // ---- review: suggestions ----------------------------------------
        setEditMode(mode: EditMode) {
          if (!root) return;
          if (!editable) mode = 'viewing';
          if (this.noteMode) this.exitNote(false);
          if (this.hfMode) this.exitHeaderFooter();
          this.closeSlash();
          this.editMode = mode;
          this.editable = editable && mode !== 'viewing';
          root.setAttribute('contenteditable', this.editable ? 'true' : 'false');
          root.toggleAttribute('data-nk-suggesting', mode === 'suggesting');
          (root.closest('.nk-native') as HTMLElement | null)?.setAttribute('data-nk-mode', mode);
          this.$dispatch('editor-mode', { mode });
          this.tick += 1;
        },

        /** Keys in Suggesting mode. True when the input layer must leave them alone. */
        suggestKey(event: KeyboardEvent): boolean {
          if (!root) return false;
          const mod = event.ctrlKey || event.metaKey;
          const range = currentRange(root);
          if (!range) return false;

          if (event.key === 'Backspace' || event.key === 'Delete') {
            // Joining two blocks is not suggested: the input layer does it as usual.
            const block = closestBlock(range.startContainer, root);
            if (!block || closestBlock(range.endContainer, root) !== block) return false;
            if (range.collapsed) {
              const at = offsetIn(block, range);
              if (event.key === 'Backspace' ? at === 0 : at >= (block.textContent ?? '').length) return false;
            }
            // Within the block, the browser computes what goes (a letter, a word,
            // an emoji) and `beforeinput` turns it into a suggestion.
            return true;
          }

          // Typed characters arrive in `beforeinput`; autoformat stays out of it.
          return event.key.length === 1 && (!mod || (event.ctrlKey && event.altKey));
        },

        /** What the browser is about to type or delete, as a suggestion. */
        suggestInput(event: InputEvent) {
          if (this.editMode !== 'suggesting' || !root || this.noteMode || this.hfMode) return;
          const type = event.inputType;
          if (!type.startsWith('insert') && !type.startsWith('delete')) return;
          if (type === 'insertCompositionText' || type === 'insertParagraph' || type === 'insertLineBreak') return;

          const target = event.getTargetRanges?.()[0];
          const range = target ? document.createRange() : currentRange(root);
          if (target && range) {
            range.setStart(target.startContainer, target.startOffset);
            range.setEnd(target.endContainer, target.endOffset);
          }
          if (!range || !root.contains(range.commonAncestorContainer)) return;
          // Across blocks the input layer joins them, untracked.
          if (closestBlock(range.startContainer, root) !== closestBlock(range.endContainer, root)) return;

          event.preventDefault();
          const author = this.authorName();
          withoutObserver(() => {
            if (type.startsWith('insert')) {
              const text = (event.data ?? event.dataTransfer?.getData('text/plain') ?? '').replace(/\s*\n\s*/g, ' ');
              if (!text) return;
              review.suggestInsertion(root!, range, text, author);
            } else {
              if (range.collapsed) return;
              review.suggestDeletion(root!, range, author, type.includes('Forward') ? 'forward' : 'backward');
            }
            this.afterChange('typing');
          });
        },

        /** What a formatting, style, new-paragraph or join suggestion does, in words. */
        suggestionLabel(suggestion: review.Suggestion): string {
          if (suggestion.kind === 'split') return say('suggestSplit', 'New paragraph');
          if (suggestion.kind === 'join') return say('suggestJoin', 'Join paragraphs');
          if (suggestion.kind === 'format') {
            const changes = (suggestion.changes ?? []).map((change) => {
              const name = phrases.marks?.[change.mark] ?? change.mark;
              if (change.after === false || change.after === null) return say('suggestOff', 'no {mark}', { mark: name.toLowerCase() });
              if (typeof change.after !== 'string') return name;
              const rgb = change.after.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
              const value =
                change.mark === 'fontSize' && change.after.endsWith('px') ? `${Math.round(parseFloat(change.after) / (4 / 3))} pt`
                : rgb ? `#${rgb.slice(1, 4).map((part) => Number(part).toString(16).padStart(2, '0')).join('')}`
                : change.mark === 'fontFamily' ? change.after.split(',')[0].replace(/["']/g, '')
                : change.after;
              return `${name} ${value}`;
            });
            return say('suggestFormat', 'Format: {changes}', { changes: changes.join(', ') });
          }
          const before = suggestion.before;
          const look = suggestion.look;
          if (!before || !look) return '';
          const parts: string[] = [];
          if (look.name !== before.name) parts.push(phrases.styles?.[look.name as BlockName] ?? look.name);
          if (look.align !== before.align) parts.push(phrases.aligns?.[look.align || 'left'] ?? look.align);
          if (look.spacing !== before.spacing) parts.push(say('suggestSpacing', 'line spacing {value}', { value: look.spacing || '1.15' }));
          if (look.indent !== before.indent) parts.push(say('suggestIndent', 'indent'));
          return say('suggestParagraph', 'Paragraph: {changes}', { changes: parts.join(', ') });
        },

        settleSuggestion(id: string, accept: boolean) {
          if (!root || !editable) return;
          withoutObserver(() => {
            review.settle(root!, id, accept);
            this.afterChange('command');
          });
          if (this.activeReview === id) this.setActive('');
        },

        settleAll(accept: boolean) {
          if (!root || !editable) return;
          withoutObserver(() => {
            for (const suggestion of review.suggestionsIn(root!)) review.settle(root!, suggestion.id, accept);
            this.afterChange('command');
          });
          this.setActive('');
        },

        // ---- footnotes --------------------------------------------------
        /** A footnote at the caret; the caret goes to its note, at the foot of the page. */
        insertFootnote() {
          if (!root || !this.editable || this.hfMode) return;
          if (this.noteMode) this.exitNote(false);
          let id = '';
          this.run((_editor, range) => {
            id = footnotes.insertMarker(range);
          });
          if (id) this.editNote(id);
        },

        /** Write in a footnote: the body steps back, the note is editable. */
        editNote(id: string, point?: { x: number; y: number }) {
          if (!root || !this.editable) return;
          if (this.noteMode && this.noteMode !== id) this.exitNote(false);
          this.noteMode = id;
          withoutObserver(() => {
            root!.setAttribute('contenteditable', 'false');
            footnotes.layoutAll(root!, id, say('noteHint', 'Footnote'));
          });
          const text = footnotes.noteText(root, id);
          if (!text) return;
          (root.closest('.nk-native') as HTMLElement | null)?.setAttribute('data-nk-note-editing', '');
          text.focus({ preventScroll: true });
          text.scrollIntoView({ block: 'nearest' });

          const range = (point && document.caretRangeFromPoint?.(point.x, point.y)) || null;
          const caret = range && text.contains(range.startContainer) ? range : document.createRange();
          if (caret !== range) {
            caret.selectNodeContents(text);
            caret.collapse(false);
          }
          const selection = window.getSelection();
          selection?.removeAllRanges();
          selection?.addRange(caret);
          this.tick += 1;
        },

        /**
         * A reflow may rebuild the note being written (its page was cut while
         * it grew); the caret was put back in it, the focus must follow.
         */
        keepNoteFocus() {
          if (!root || !this.noteMode) return;
          const text = footnotes.noteText(root, this.noteMode);
          if (!text || document.activeElement === text) return;
          const saved = saveSelection(root);
          text.focus({ preventScroll: true });
          restoreSelection(root, saved);
        },

        /** Stop writing the note; with `toMarker`, the caret goes back after its marker. */
        exitNote(toMarker: boolean) {
          if (!root || !this.noteMode) return;
          const id = this.noteMode;
          this.noteMode = null;
          (root.closest('.nk-native') as HTMLElement | null)?.removeAttribute('data-nk-note-editing');
          if (this.editable) root.setAttribute('contenteditable', 'true');
          withoutObserver(() => {
            footnotes.collect(root!);
            // The note stops being editable even when nothing moves (no reflow runs then).
            footnotes.layoutAll(root!, null, say('noteHint', 'Footnote'));
            this.afterChange('command');
          });

          const marker = footnotes.markerFor(root, id);
          if (toMarker && marker) {
            root.focus({ preventScroll: true });
            const range = document.createRange();
            range.setStartAfter(marker);
            range.collapse(true);
            window.getSelection()?.removeAllRanges();
            window.getSelection()?.addRange(range);
          }
        },

        /** Keys while writing a note. */
        noteKey(event: KeyboardEvent) {
          const mod = event.ctrlKey || event.metaKey;
          const key = event.key.toLowerCase();
          if (event.key === 'Escape') {
            event.preventDefault();
            this.exitNote(true);
          } else if (event.key === 'Enter') {
            event.preventDefault();
          } else if (mod && (key === 'z' || key === 'y')) {
            event.preventDefault();
            if (key === 'y' || event.shiftKey) this.redo();
            else this.undo();
          } else if (mod && ['b', 'i', 'u', 'k'].includes(key)) {
            // A note is plain text.
            event.preventDefault();
          }
        },

        runCommand(id: string) {
          const styles: Record<string, BlockName> = {
            paragraph: 'paragraph', title: 'title', subtitle: 'subtitle',
            heading1: 'heading1', heading2: 'heading2', heading3: 'heading3',
            quote: 'blockquote', codeblock: 'codeblock',
          };
          if (styles[id]) return this.setStyle(styles[id]);
          switch (id) {
            case 'bulletList': return this.toggleBulletList();
            case 'orderedList': return this.toggleOrderedList();
            case 'checklist': return this.toggleChecklist();
            case 'table': return this.openTableDialog();
            case 'image': return this.pickImage();
            case 'link': return this.openLinkDialog();
            case 'divider': return this.insertRule();
            case 'pageBreak': return this.insertPageBreak();
            case 'date': return this.insertText(this.today());
            case 'toc': return this.insertToc('pages');
            case 'footnote': return this.insertFootnote();
            case 'mention': return this.startMention();
            case 'special': return this.openCharacters();
          }
        },

        today(): string {
          try {
            return new Intl.DateTimeFormat(config.locale || undefined, { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
          } catch {
            return new Date().toLocaleDateString();
          }
        },

        insertText(text: string) {
          // Suggesting: what is put in is a suggestion like what is typed.
          if (this.editMode === 'suggesting' && root) {
            const range = currentRange(root);
            if (!range || !this.editable) return;
            withoutObserver(() => {
              review.suggestInsertion(root!, range, text, this.authorName());
              this.afterChange('typing');
            });
            return;
          }
          this.run((editor, range) => {
            range.deleteContents();
            const node = document.createTextNode(text);
            // An empty line's placeholder goes: the text is the line now.
            const block = closestBlock(range.startContainer, editor);
            if (block && !(block.textContent ?? '').length && !block.querySelector('img, [data-chip], [data-footnote]')) {
              block.replaceChildren(node);
            } else {
              range.insertNode(node);
            }
            range.setStartAfter(node);
            range.collapse(true);
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
          });
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
            column: context.column,
            rows: context.rows,
            columns: context.columns,
            merged: context.rowspan > 1 || context.colspan > 1,
            border: (context.table.getAttribute('data-border') ?? 'thin') as tables.BorderWeight,
            borderColor: context.table.getAttribute('data-border-color'),
            sized: tables.columnWidths(context.table) !== null,
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
        /** Below the pointer, or above it when there is no room below. */
        tableMenuSide: 'bottom' as 'bottom' | 'top',

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
          this.tableMenuAnchor = { x: event.clientX - box.left, y: event.clientY - box.top };

          // Placed before it shows: the opening motion grows it out of the
          // pointer, so it must already stand where it will stay.
          const menu = this.$refs.tableMenu as HTMLElement | undefined;
          if (menu) {
            const inline = menu.style.cssText;
            menu.style.display = 'block';
            menu.style.visibility = 'hidden';
            this.placeTableMenu({ width: menu.offsetWidth, height: menu.offsetHeight });
            menu.style.cssText = inline;
          }
          this.tableMenuOpen = true;
          this.tick += 1;
        },

        tableMenuAnchor: { x: 0, y: 0 },

        /**
         * At the pointer: below it, or above it when below is too short; to its
         * left near the right edge. Run again when a section of the menu unfolds.
         */
        placeTableMenu(size?: { width: number; height: number }) {
          const menu = this.$refs.tableMenu as HTMLElement | undefined;
          const host = root?.closest<HTMLElement>('.nk-native');
          if (!menu || !host) return;
          const { x, y } = this.tableMenuAnchor;
          const area = { width: host.clientWidth, height: host.clientHeight };
          const width = size?.width ?? menu.offsetWidth;
          const height = size?.height ?? menu.offsetHeight;
          const below = area.height - y - 8;
          const above = y - 8;
          const up = height > below && above > below;
          this.tableMenuSide = up ? 'top' : 'bottom';
          this.tableMenuY = up ? Math.max(8, y - height) : Math.max(8, Math.min(y, area.height - height - 8));
          this.tableMenuX = x + width + 8 > area.width ? Math.max(8, x - width) : x;
        },

        runTableAction(action: () => void) {
          this.tableMenuOpen = false;
          action();
        },

        // ---- picked cells -----------------------------------------------
        /**
         * Mouse down in the pages: pick an image, start a column drag, or start
         * picking cells.
         */
        pointerDown(event: MouseEvent) {
          if (!root || !this.editable || this.hfMode || event.button !== 0) return;
          const target = event.target as HTMLElement | null;

          // Footnotes: a marker or a note opens the note for writing.
          const note = target?.closest?.('[data-nk-note]') as HTMLElement | null;
          if (note) {
            const id = note.getAttribute('data-nk-note') ?? '';
            if (this.noteMode === id && target?.closest('[data-nk-note-text]')) return;
            event.preventDefault();
            this.editNote(id, { x: event.clientX, y: event.clientY });
            return;
          }
          const marker = target?.closest?.('[data-nk-content] [data-footnote]') as HTMLElement | null;
          if (marker) {
            event.preventDefault();
            this.editNote(marker.getAttribute('data-footnote') ?? '');
            return;
          }
          // Anywhere else, the host's handler ends the note and puts the caret there.
          if (this.noteMode) return;

          if (pickedToc) this.deselectToc();
          if (this.chipOpen) this.closeChip();

          const chip = target?.closest?.('[data-nk-content] [data-chip]') as HTMLElement | null;
          if (chip) {
            event.preventDefault();
            this.openChip(chip);
            return;
          }

          const toc = target?.closest?.('[data-nk-content] nav[data-toc]') as HTMLElement | null;
          if (toc) {
            event.preventDefault();
            const link = target?.closest('a[href^="#"]');
            if (link) this.jumpToHeading(link.getAttribute('href')!.slice(1));
            else this.selectToc(toc);
            return;
          }

          if (target?.tagName === 'IMG') {
            const figure = images.figureOf(target, root);
            if (figure) {
              event.preventDefault();
              if (this.cropOpen) return;
              this.selectImage(figure);
              if (event.detail === 2) this.startCrop();
              else this.trackImageDrag(figure, event);
              return;
            }
          }
          if (picked) this.deselectImage();

          const cell = target?.closest?.('td, th') as HTMLElement | null;
          if (!cell || !root.contains(cell)) {
            this.clearCells();
            return;
          }

          const boundary = tables.boundaryAt(cell, event.clientX);
          if (boundary !== null) {
            event.preventDefault();
            this.clearCells();
            this.dragColumn(cell, boundary, event);
            return;
          }

          // Shift+click picks every cell from the caret's to this one.
          if (event.shiftKey) {
            const range = currentRange(root);
            const from = cellAnchor ?? (range ? blocks.tableContext(root, range)?.cell : null);
            if (from && from !== cell && from.closest('table') === cell.closest('table')) {
              event.preventDefault();
              this.selectCells(from, cell);
              return;
            }
          }

          this.clearCells();
          this.trackCells(cell);
        },

        /** While the button is held, every cell between the first and the one under the pointer is picked. */
        trackCells(anchor: HTMLElement) {
          const move = (event: MouseEvent) => {
            const under = document.elementFromPoint(event.clientX, event.clientY) as HTMLElement | null;
            const over = under?.closest?.('td, th') as HTMLElement | null;
            if (!over || over.closest('table') !== anchor.closest('table')) return;
            if (over === anchor) this.clearCells();
            else if (over !== cellFocus) this.selectCells(anchor, over);
          };
          const up = () => {
            document.removeEventListener('mousemove', move);
            document.removeEventListener('mouseup', up);
            // The cells stand in for the text selection the drag made.
            if (cellAnchor && root) setTimeout(() => cellAnchor && root && setCaret(root, cellAnchor, 0), 0);
          };
          document.addEventListener('mousemove', move);
          document.addEventListener('mouseup', up);
        },

        selectCells(from: HTMLElement, to: HTMLElement) {
          cellAnchor = from;
          cellFocus = to;
          this.markCells(tables.cellsBetween(from, to));
        },

        markCells(cells: HTMLElement[]) {
          if (!root) return;
          root.querySelectorAll('[data-nk-cell-picked]').forEach((cell) => cell.removeAttribute('data-nk-cell-picked'));
          for (const cell of cells) cell.setAttribute('data-nk-cell-picked', '');
          root.toggleAttribute('data-nk-cells', cells.length > 1);
          this.cellCount = cells.length;
        },

        clearCells() {
          if (!cellAnchor && this.cellCount === 0) return;
          cellAnchor = null;
          cellFocus = null;
          this.markCells([]);
        },

        /** The picked cells, or the caret's cell. */
        pickedCells(): HTMLElement[] {
          if (!root) return [];
          const cells = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-cell-picked]'));
          if (cells.length) return cells;
          const range = currentRange(root);
          const context = range ? blocks.tableContext(root, range) : null;
          return context ? [context.cell] : [];
        },

        /** Keys while several cells are picked. True when handled. */
        cellKey(event: KeyboardEvent): boolean {
          if (['Shift', 'Control', 'Meta', 'Alt'].includes(event.key)) return true;
          if (event.key === 'Delete' || event.key === 'Backspace') {
            event.preventDefault();
            this.clearPickedCells();
            return true;
          }
          if (event.key === 'Escape') {
            this.clearCells();
            return true;
          }
          if (!event.ctrlKey && !event.metaKey) this.clearCells();
          return false;
        },

        /** Two cells to merge between: the picked corners, or a text selection across cells. */
        mergePair(): [HTMLElement, HTMLElement] | null {
          if (cellAnchor && cellFocus) return [cellAnchor, cellFocus];
          if (!root) return null;
          const range = currentRange(root);
          if (!range || range.collapsed) return null;
          const cellIn = (node: Node) =>
            (node.nodeType === Node.ELEMENT_NODE ? (node as HTMLElement) : node.parentElement)?.closest<HTMLElement>('td, th') ?? null;
          const a = cellIn(range.startContainer);
          const b = cellIn(range.endContainer);
          return a && b && a !== b && a.closest('table') === b.closest('table') ? [a, b] : null;
        },

        canMerge(): boolean {
          this.tick;
          this.cellCount;
          return this.mergePair() !== null;
        },

        canUnmerge(): boolean {
          return this.tableInfo()?.merged ?? false;
        },

        mergeCells() {
          const pair = this.mergePair();
          if (!pair) return;
          this.clearCells();
          this.run((editor) => {
            tables.mergeCells(editor, pair[0], pair[1]);
          });
        },

        unmergeCells() {
          this.run((editor, range) => {
            const context = blocks.tableContext(editor, range);
            if (context) tables.splitCell(editor, context.cell);
          });
        },

        clearPickedCells() {
          const cells = this.pickedCells();
          if (cells.length) this.run(() => tables.clearCells(cells));
        },

        setCellFill(color: string | null) {
          const cells = this.pickedCells();
          if (cells.length) this.run(() => tables.setCellFill(cells, color));
        },

        /** The table at the caret, for the border and width commands. */
        caretTable(): HTMLElement | null {
          if (!root) return null;
          const range = currentRange(root);
          return range ? (blocks.tableContext(root, range)?.table ?? null) : null;
        },

        setTableBorder(weight: tables.BorderWeight) {
          const table = this.caretTable();
          if (table) this.run(() => tables.setTableBorder(table, weight));
        },

        setTableBorderColor(color: string | null) {
          const table = this.caretTable();
          if (!table) return;
          const weight = (table.getAttribute('data-border') ?? 'thin') as tables.BorderWeight;
          this.run(() => tables.setTableBorder(table, weight === 'none' ? 'thin' : weight, color));
        },

        /** Every column the same width again. */
        distributeColumns() {
          const table = this.caretTable();
          if (table) this.run(() => tables.setColumnWidths(table, null));
        },

        /** Drag a column border; the two columns beside it share the change. */
        dragColumn(cell: HTMLElement, boundary: number, event: MouseEvent) {
          const table = cell.closest<HTMLElement>('table');
          if (!root || !table) return;

          const start = tables.measureColumns(table);
          const startX = event.clientX;
          root.setAttribute('data-nk-col-dragging', '');

          const move = (moveEvent: MouseEvent) => {
            withoutObserver(() => tables.setColumnWidths(table, dragColumns(start, boundary, moveEvent.clientX - startX)));
          };
          const up = () => {
            document.removeEventListener('mousemove', move);
            document.removeEventListener('mouseup', up);
            root?.removeAttribute('data-nk-col-dragging');
            this.afterChange('command');
          };
          document.addEventListener('mousemove', move);
          document.addEventListener('mouseup', up);
        },

        // ---- picked image -----------------------------------------------
        /**
         * Pick an image: a frame with handles and a toolbar over it.
         *
         * The caret is parked inside its figure, so alignment applies to the
         * image and a toolbar click finds a caret to work from; a key other
         * than Delete lets go and carries on below it.
         */
        selectImage(figure: HTMLElement) {
          if (!root) return;
          this.clearCells();
          if (picked && picked !== figure) picked.removeAttribute('data-nk-picked');
          picked = figure;
          figure.setAttribute('data-nk-picked', '');
          root.setAttribute('data-nk-image-picked', '');

          root.focus({ preventScroll: true });
          const image = images.imageOf(figure);
          if (image) {
            const range = document.createRange();
            range.setStartAfter(image);
            range.collapse(true);
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
          }

          this.syncImage();
          this.imageOpen = true;
          this.$nextTick(() => this.placeImage());
        },

        deselectImage() {
          this.closeCrop();
          picked?.removeAttribute('data-nk-picked');
          picked = null;
          root?.removeAttribute('data-nk-image-picked');
          this.imageOpen = false;
          this.altOpen = false;
          this.imageBorderOpen = false;
          this.imageNotice = '';
        },

        syncImage() {
          if (!picked) return;
          const image = images.imageOf(picked);
          this.imageWrap = images.wrapOf(picked);
          this.imageWidth = images.widthOf(picked);
          this.imageHasCaption = images.captionOf(picked) !== null;
          this.imageEdited = Boolean(image && images.isEdited(image));
          this.imageBorder = images.borderOf(picked);
          this.imageBorderColor = picked.getAttribute('data-border-color');
        },

        /** Put the frame on the image and its toolbar under it (over it near the bottom). */
        placeImage() {
          if (!root || !picked) return;
          const image = images.imageOf(picked);
          const stage = root.closest<HTMLElement>('.nk-native-stage');
          if (!image || !image.isConnected || !stage) return this.deselectImage();

          const area = stage.getBoundingClientRect();
          const box = image.getBoundingClientRect();
          this.imageBox = { left: box.left - area.left, top: box.top - area.top, width: box.width, height: box.height };

          const bar = this.$refs.imageBar as HTMLElement | undefined;
          const barWidth = bar?.offsetWidth || 320;
          const barHeight = bar?.offsetHeight || 36;
          const below = box.bottom - area.top + 8 + barHeight < area.height;
          this.imageBar = {
            left: Math.max(8, Math.min(box.left - area.left, area.width - barWidth - 8)),
            top: below ? box.bottom - area.top + 8 : Math.max(8, box.top - area.top - barHeight - 8),
          };

          // Cropping: the whole picture, laid where the part now shown sits in it.
          if (this.cropOpen) {
            const [top, right, bottom, left] = cropFrom;
            const width = box.width / (1 - left - right);
            const height = box.height / (1 - top - bottom);
            this.cropBox = {
              left: box.left - area.left - left * width,
              top: box.top - area.top - top * height,
              width,
              height,
            };
          }
        },

        /** Keys while an image is picked. True when handled. */
        imageKey(event: KeyboardEvent): boolean {
          const figure = picked;
          if (!root || !figure) return false;
          if (['Shift', 'Control', 'Meta', 'Alt'].includes(event.key)) return true;

          if (this.cropOpen) {
            event.preventDefault();
            if (event.key === 'Enter') void this.applyCrop();
            else if (event.key === 'Escape') this.closeCrop();
            return true;
          }

          if (event.key === 'Delete' || event.key === 'Backspace') {
            event.preventDefault();
            this.deleteImage();
            return true;
          }

          this.deselectImage();
          if (event.ctrlKey || event.metaKey) return false;

          withoutObserver(() => setCaret(root!, images.blockAfter(figure), 0));
          if (event.key === 'Escape' || event.key.startsWith('Arrow')) {
            event.preventDefault();
            return true;
          }
          return false;
        },

        /** Run an edit on the picked image and settle the document. */
        imageCommand(edit: (figure: HTMLElement) => void) {
          if (!picked || !this.editable) return;
          const figure = picked;
          withoutObserver(() => {
            edit(figure);
            this.afterChange('command');
          });
          this.syncImage();
        },

        setImageWrap(wrap: images.Wrap) {
          this.imageCommand((figure) => images.setWrap(figure, wrap));
        },

        setImageWidth(percent: number | null) {
          this.imageCommand((figure) => images.setWidth(figure, percent));
        },

        toggleImageCaption() {
          if (!picked || !root) return;
          let caption = null as HTMLElement | null;
          this.imageCommand((figure) => {
            caption = images.toggleCaption(figure);
          });
          if (caption) {
            this.deselectImage();
            setCaret(root, caption, 0);
          }
        },

        openAltText() {
          if (!picked) return;
          this.altText = images.imageOf(picked)?.getAttribute('alt') ?? '';
          this.altOpen = true;
        },

        applyAltText() {
          const text = this.altText;
          this.altOpen = false;
          this.imageCommand((figure) => images.setAlt(figure, text));
        },

        replaceImage() {
          const figure = picked;
          if (!figure) return;
          this.chooseFile(async (file) => {
            figure.setAttribute('data-nk-uploading', '');
            const src = await this.resolveImage(file);
            figure.removeAttribute('data-nk-uploading');
            if (!figure.isConnected) return;
            withoutObserver(() => {
              images.setSource(figure, src, file.name);
              this.afterChange('command');
            });
          });
        },

        deleteImage() {
          const figure = picked;
          if (!root || !figure) return;
          this.deselectImage();
          withoutObserver(() => {
            images.removeFigure(root!, figure);
            this.afterChange('command');
          });
        },

        /** Drag a corner of the picked image: its width follows, as a share of the page. */
        resizeImage(event: PointerEvent, side: 'left' | 'right') {
          const figure = picked;
          const image = figure ? images.imageOf(figure) : null;
          if (!figure || !image || !this.editable) return;

          const handle = event.currentTarget as HTMLElement;
          handle.setPointerCapture?.(event.pointerId);

          const current = images.widthOf(figure);
          const figureWidth = figure.getBoundingClientRect().width;
          const parentWidth = current ? figureWidth / (current / 100) : figureWidth;
          const startWidth = image.getBoundingClientRect().width;
          const startX = event.clientX;

          const move = (moveEvent: PointerEvent) => {
            images.setWidth(figure, dragWidth(startWidth, moveEvent.clientX - startX, parentWidth, side));
            this.imageWidth = images.widthOf(figure);
            this.placeImage();
          };
          const up = () => {
            handle.removeEventListener('pointermove', move);
            handle.removeEventListener('pointerup', up);
            handle.removeEventListener('pointercancel', up);
            withoutObserver(() => this.afterChange('command'));
          };
          handle.addEventListener('pointermove', move);
          handle.addEventListener('pointerup', up);
          handle.addEventListener('pointercancel', up);
        },

        /**
         * Hold the button on an image and drag: a line shows where it would
         * land, between two blocks; letting go moves it there. Escape stays put.
         */
        trackImageDrag(figure: HTMLElement, start: MouseEvent) {
          if (!root || !this.editable) return;
          const stage = root.closest<HTMLElement>('.nk-native-stage');
          let dragging = false;
          let slot: images.DropSlot | null = null;

          const move = (event: MouseEvent) => {
            if (!dragging) {
              if (Math.hypot(event.clientX - start.clientX, event.clientY - start.clientY) < 6) return;
              dragging = true;
              this.imageDragging = true;
              this.imageBorderOpen = false;
              this.altOpen = false;
              root!.setAttribute('data-nk-image-dragging', '');
            }
            slot = images.dropSlot(root!, figure, event.clientX, event.clientY);
            const area = stage?.getBoundingClientRect();
            this.dropLine = slot && area ? { left: slot.line.left - area.left, top: slot.line.top - area.top, width: slot.line.width } : null;
          };
          const finish = (drop: boolean) => {
            window.removeEventListener('mousemove', move);
            window.removeEventListener('mouseup', up);
            window.removeEventListener('keydown', key, true);
            root?.removeAttribute('data-nk-image-dragging');
            this.imageDragging = false;
            this.dropLine = null;
            if (!drop || !dragging || !slot || !figure.isConnected) return;
            const to = slot;
            withoutObserver(() => {
              images.moveFigure(root!, figure, to);
              this.afterChange('command');
            });
            this.$nextTick(() => {
              if (!figure.isConnected) return;
              this.selectImage(figure);
              figure.scrollIntoView({ block: 'nearest' });
            });
          };
          const up = () => finish(true);
          const key = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') return;
            event.preventDefault();
            event.stopPropagation();
            finish(false);
          };
          window.addEventListener('mousemove', move);
          window.addEventListener('mouseup', up);
          window.addEventListener('keydown', key, true);
        },

        /** Say something about the picked image for a few seconds, in its toolbar. */
        noteImage(message: string) {
          this.imageNotice = message;
          window.clearTimeout(imageNoticeTimer);
          imageNoticeTimer = window.setTimeout(() => (this.imageNotice = ''), 6000);
        },

        /**
         * Crop the picked image: the whole picture shows, the part kept is
         * framed with handles. Enter or Done keeps it, Escape leaves it as it was.
         */
        async startCrop() {
          const figure = picked;
          const image = figure ? images.imageOf(figure) : null;
          if (!figure || !image || !this.editable || this.cropOpen || figure.hasAttribute('data-nk-uploading')) return;

          const edit = images.editOf(image);
          const picture = await images.loadPicture(images.originalOf(image));
          if (!picture) return this.noteImage(say('imageLocked', 'This image comes from another site, which does not let it be edited here.'));
          if (picked !== figure) return;

          // Turned, the whole picture is drawn turned, so it lines up with what shows.
          let src = picture.src;
          if (edit.angle) {
            const blob = await images.drawPicture(picture, { angle: edit.angle, insets: NO_CROP }, 'image/png');
            if (!blob) return this.noteImage(say('imageLocked', 'This image comes from another site, which does not let it be edited here.'));
            cropUrl = URL.createObjectURL(blob);
            src = cropUrl;
          }

          cropPicture = picture;
          cropFrom = edit.insets;
          this.cropInsets = [...edit.insets] as Insets;
          this.cropSrc = src;
          this.cropOpen = true;
          this.imageBorderOpen = false;
          this.altOpen = false;
          this.$nextTick(() => this.placeImage());
        },

        closeCrop() {
          if (cropUrl) URL.revokeObjectURL(cropUrl);
          cropUrl = null;
          cropPicture = null;
          this.cropOpen = false;
          this.cropSrc = '';
        },

        /** Drag a crop handle, or the kept part itself. */
        dragCrop(event: PointerEvent, handle: CropHandle) {
          if (!this.cropOpen) return;
          const target = event.currentTarget as HTMLElement;
          target.setPointerCapture?.(event.pointerId);
          const start = [...this.cropInsets] as Insets;
          const { width, height } = this.cropBox;

          const move = (moveEvent: PointerEvent) => {
            this.cropInsets = dragInsets(start, handle, (moveEvent.clientX - event.clientX) / width, (moveEvent.clientY - event.clientY) / height);
          };
          const up = () => {
            target.removeEventListener('pointermove', move);
            target.removeEventListener('pointerup', up);
            target.removeEventListener('pointercancel', up);
          };
          target.addEventListener('pointermove', move);
          target.addEventListener('pointerup', up);
          target.addEventListener('pointercancel', up);
        },

        async applyCrop() {
          const figure = picked;
          const image = figure ? images.imageOf(figure) : null;
          if (!figure || !image || !this.cropOpen) return;
          const edit = { angle: images.editOf(image).angle, insets: [...this.cropInsets] as Insets };
          const picture = cropPicture;
          this.closeCrop();
          await this.editPicture(figure, edit, picture);
        },

        /** A quarter turn, clockwise (1) or back (-1); a crop turns with the picture. */
        async rotateImage(turn: 1 | -1) {
          const figure = picked;
          const image = figure ? images.imageOf(figure) : null;
          if (!figure || !image || !this.editable || figure.hasAttribute('data-nk-uploading')) return;
          const edit = images.editOf(image);
          await this.editPicture(figure, { angle: turnAngle(edit.angle, turn), insets: turnInsets(edit.insets, turn) });
        },

        /** Back to the picture as it was put in. */
        resetImage() {
          const figure = picked;
          const image = figure ? images.imageOf(figure) : null;
          if (!figure || !image || !images.isEdited(image)) return;
          const original = images.originalOf(image);
          this.imageCommand((target) => images.setPicture(target, original, original, { angle: 0, insets: NO_CROP }));
          this.$nextTick(() => this.placeImage());
        },

        /**
         * Draw the picture again from its original, send it like an upload,
         * and show it. Undo brings back the one before.
         */
        async editPicture(figure: HTMLElement, edit: images.PictureEdit, loaded: HTMLImageElement | null = null) {
          const image = images.imageOf(figure);
          if (!image || !this.editable) return;
          const original = images.originalOf(image);

          if (edit.angle === 0 && edit.insets.every((inset) => inset === 0)) {
            withoutObserver(() => {
              images.setPicture(figure, original, original, edit);
              this.afterChange('command');
            });
          } else {
            figure.setAttribute('data-nk-uploading', '');
            const picture = loaded ?? (await images.loadPicture(original));
            const type = images.pictureType(original);
            const blob = picture ? await images.drawPicture(picture, edit, type) : null;
            if (!blob) {
              figure.removeAttribute('data-nk-uploading');
              return this.noteImage(say('imageLocked', 'This image comes from another site, which does not let it be edited here.'));
            }
            const name = `${(image.getAttribute('alt') || 'image').replace(/\.[a-z0-9]+$/i, '').slice(0, 60)}.${type === 'image/jpeg' ? 'jpg' : 'png'}`;
            const src = await this.resolveImage(new File([blob], name, { type }));
            figure.removeAttribute('data-nk-uploading');
            if (!figure.isConnected) return;
            withoutObserver(() => {
              images.setPicture(figure, src, original, edit);
              this.afterChange('command');
            });
          }

          if (picked === figure) {
            this.syncImage();
            this.$nextTick(() => this.placeImage());
          }
        },

        setImageBorder(weight: images.ImageBorder | null) {
          this.imageCommand((figure) => images.setBorder(figure, weight));
        },

        setImageBorderColor(color: string | null) {
          // A colour on its own would show nothing: it comes with a thin border.
          this.imageCommand((figure) => images.setBorder(figure, images.borderOf(figure) ?? 'thin', color));
        },

        /** A break: the next page, the next odd or even page, or a new line in the paragraph. */
        insertBreak(kind: blocks.BreakKind | 'line' = 'page') {
          if (kind === 'line') {
            this.run((_editor, range) => {
              range.deleteContents();
              insertLineBreak(range);
            });
            return;
          }
          this.run((editor, range) => blocks.insertPageBreak(editor, range, kind));
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
          this.chooseFile((file) => void this.uploadImage(file));
        },

        chooseFile(then: (file: File) => void) {
          const input = document.createElement('input');
          input.type = 'file';
          input.accept = 'image/*';
          input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (file) then(file);
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
        /**
         * The image shows at once, from the file itself, under a spinner while
         * it is sent; then it takes the address it was uploaded to.
         */
        async uploadImage(file: File, alt = file.name) {
          const preview = URL.createObjectURL(file);
          let placed: HTMLElement | null = null;
          this.run((editor, range) => {
            placed = blocks.insertImage(editor, range, preview, alt);
            placed.setAttribute('data-nk-uploading', '');
          });

          const src = await this.resolveImage(file);
          const figure = placed as HTMLElement | null;
          history?.replaceText(preview, src);
          if (!figure || !figure.isConnected) return;
          withoutObserver(() => {
            figure.removeAttribute('data-nk-uploading');
            images.setSource(figure, src);
            this.afterChange('command');
          });
        },

        /**
         * The pictures a paste carries, or none when it carries text as well:
         * cells copied from a spreadsheet come with a picture of themselves,
         * and the text is what was meant. A picture copied from a page comes
         * with its HTML too; the picture itself is taken, so it is kept here
         * rather than linked from the other site.
         */
        clipboardPictures(data: DataTransfer | null): File[] {
          if (!data) return [];
          const files = Array.from(data.items ?? [])
            .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
            .map((item) => item.getAsFile())
            .filter((file): file is File => file !== null);
          if (!files.length || data.getData('text/plain').trim() !== '') return [];
          // A pasted screenshot is called "image.png": named after the time instead.
          const stamp = new Date().toISOString().slice(0, 19).replace(/[T:]/g, '-');
          return files.map((file, index) => {
            const extension = file.type.split('/')[1]?.replace('jpeg', 'jpg').replace(/[^a-z0-9]/g, '') || 'png';
            return new File([file], `pasted-${stamp}${files.length > 1 ? `-${index + 1}` : ''}.${extension}`, { type: file.type });
          });
        },

        /** Where an image will be served from: the upload endpoint's URL, or the file inlined. */
        async resolveImage(file: File): Promise<string> {
          const url = config.uploadUrl;

          const inline = async (reason?: string): Promise<string> => {
            if (reason) {
              console.warn(`[neura native editor] image upload failed (${reason}); embedding inline instead.`);
              this.$dispatch('editor-upload-failed', { file: file.name, reason });
            }
            return this.readAsDataUrl(file);
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
            return String(src);
          } catch (error) {
            return inline(error instanceof Error ? error.message : 'network error');
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
        // The first page may have its own (a title page often has none).
        differentFirstPage: Boolean(config.differentFirstPage),
        firstHeaderHtml: (config.firstHeader as string | null) ?? '',
        firstFooterHtml: (config.firstFooter as string | null) ?? '',
        /** Whether the region being edited is the first page's own. */
        hfFirst: false,

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

            const page = region.closest<HTMLElement>('[data-nk-page]');
            const index = page ? Number(page.getAttribute('data-nk-page') || 1) : 1;
            const html = this.headerFooterHtml(region.hasAttribute('data-nk-header') ? 'header' : 'footer', this.differentFirstPage && index === 1);
            if (region.innerHTML !== html) region.innerHTML = html;
            this.fillFields(region, index, total);
          });
        },

        headerFooterHtml(which: 'header' | 'footer', first: boolean): string {
          if (which === 'header') return first ? this.firstHeaderHtml : this.headerHtml;
          return first ? this.firstFooterHtml : this.footerHtml;
        },

        /** Replace the auto-field placeholders with their current values. */
        fillFields(region: HTMLElement, page: number, pages: number) {
          region.querySelectorAll<HTMLElement>('[data-nk-field]').forEach((field) => {
            const value = (() => {
              switch (field.getAttribute('data-nk-field')) {
                case 'page': return String(page);
                case 'pages': return String(pages);
                case 'title': return this.documentTitle;
                case 'date': return new Date().toLocaleDateString(config.locale || undefined);
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
        editHeaderFooter(which: 'header' | 'footer', event?: Event) {
          if (!root || !this.editable) return;

          this.exitHeaderFooter();

          // The page double-clicked, or the first: with a different first page,
          // its region is the first page's own, any other the one of the rest.
          const clicked = (event?.target as HTMLElement | null)?.closest?.<HTMLElement>('[data-nk-page]');
          const page = clicked && root.contains(clicked) ? clicked : root.querySelector<HTMLElement>('[data-nk-page]');
          const region = page?.querySelector<HTMLElement>(
            which === 'header' ? '[data-nk-header]' : '[data-nk-footer]'
          );
          if (!region) return;

          this.hfFirst = this.differentFirstPage && page!.getAttribute('data-nk-page') === '1';
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

            this.storeHeaderFooter(this.hfMode, this.hfFirst, clone.innerHTML);
            region.removeAttribute('contenteditable');
          }

          this.hfMode = null;
          this.hfFirst = false;
          if (this.editable) root.setAttribute('contenteditable', 'true');
          (root.closest('.nk-native') as HTMLElement | null)?.removeAttribute('data-nk-hf-editing');

          this.syncHeaderFooter();
          this.dispatchHeaderFooter();
          this.tick += 1;
        },

        storeHeaderFooter(which: 'header' | 'footer', first: boolean, html: string) {
          if (which === 'header') {
            if (first) this.firstHeaderHtml = html;
            else this.headerHtml = html;
          } else if (first) this.firstFooterHtml = html;
          else this.footerHtml = html;
        },

        dispatchHeaderFooter() {
          this.$dispatch('header-footer-change', {
            header: this.headerHtml,
            footer: this.footerHtml,
            differentFirstPage: this.differentFirstPage,
            firstHeader: this.firstHeaderHtml,
            firstFooter: this.firstFooterHtml,
          });
        },

        /**
         * A first page with its own header and footer, or the same as the rest.
         * While page 1's region is being edited, it switches to the other one
         * and editing carries on there.
         */
        toggleFirstPage() {
          const editing = this.hfMode;
          const region = root?.querySelector<HTMLElement>('[contenteditable="true"][data-nk-header], [contenteditable="true"][data-nk-footer]') ?? null;
          const onFirst = Boolean(region?.closest('[data-nk-page="1"]'));

          if (editing && region && onFirst) {
            const clone = region.cloneNode(true) as HTMLElement;
            clone.querySelectorAll('[data-nk-field]').forEach((field) => (field.textContent = ''));
            this.storeHeaderFooter(editing, this.hfFirst, clone.innerHTML);
          }

          this.differentFirstPage = !this.differentFirstPage;

          if (editing && region && onFirst) {
            this.hfFirst = this.differentFirstPage;
            region.innerHTML = this.headerFooterHtml(editing, this.hfFirst);
            this.fillFields(region, 1, flow?.pageCount ?? 1);
            region.focus({ preventScroll: true });
          }
          this.syncHeaderFooter();
          this.dispatchHeaderFooter();
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
        /**
         * Scale the A4 (or letter, …) sheet so its width fits the canvas.
         *
         * Fixed page boxes (e.g. 794px for A4) otherwise force horizontal
         * scrolling on phones and narrow docs columns. Fit mode is the default;
         * picking a % from the status bar locks to that value.
         */
        fitZoom() {
          if (this.zoomMode !== 'fit') return;

          const canvas = (this as any).$el?.querySelector?.(
            '.nk-native-canvas',
          ) as HTMLElement | null;
          if (!canvas) return;

          const pageWidth = flow?.geometry.width ?? 794;
          // Match .nk-native-canvas horizontal breathing room (see CSS).
          const gutter = canvas.clientWidth < 640 ? 16 : 48;
          const available = canvas.clientWidth - gutter;
          if (available <= 0 || pageWidth <= 0) return;

          const next = Math.min(100, Math.max(35, Math.floor((available / pageWidth) * 100)));
          if (next !== this.zoom) this.zoom = next;
        },

        setZoom(value: number | 'fit') {
          if (value === 'fit') {
            this.zoomMode = 'fit';
            this.fitZoom();
            return;
          }

          this.zoomMode = 'manual';
          this.zoom = value;
        },

        zoomStyle() {
          return `transform: scale(${this.zoom / 100});`;
        },

        applyPageSetup() {
          this.showPageSetup = false;
          this.columns = Math.max(1, Math.min(3, Math.round(Number(this.columns) || 1)));
          flow?.setOptions({
            size: this.pageSize,
            orientation: this.orientation,
            margins: this.margins,
            columns: this.columns,
          });
          this.pageCount = flow?.pageCount ?? 1;
          this.fitZoom();
          this.syncHeaderFooter();
          this.$dispatch('page-setup-change', {
            size: this.pageSize,
            orientation: this.orientation,
            margins: { ...this.margins },
            columns: this.columns,
          });
        },

        /** Text in one, two or three columns on every page (Format › Columns). */
        setColumns(count: number) {
          this.columns = count;
          this.applyPageSetup();
        },

        /** Number the caret's list another way (Format › Numbered list style). */
        setNumbering(numbering: blocks.Numbering) {
          this.run((editor, range) => blocks.setNumbering(editor, range, numbering));
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
                html: toHtml(root!),
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
                : `${say('exportFailed', 'Export failed.')} (HTTP ${response.status})`;
              this.conversionError = message || say('exportFailed', 'Export failed.');
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
            this.conversionError = error instanceof Error ? error.message : say('exportFailed', 'Export failed.');
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
                this.conversionError = `${say('importFailed', 'Import failed.')} (HTTP ${response.status})`;
                return;
              }

              const payload = await response.json();

              if (!response.ok) {
                this.conversionError = payload?.message ?? say('importFailed', 'Import failed.');
                return;
              }

              // `setValue` sanitizes, so nothing from the uploaded file reaches
              // the document without passing the schema allowlist first.
              this.setValue(payload.html ?? '');
              if (payload.title) this.documentTitle = payload.title;
            } catch (error) {
              this.conversionError = error instanceof Error ? error.message : say('importFailed', 'Import failed.');
            } finally {
              this.busy = false;
            }
          });

          input.click();
        },

        /** Current value, for a host that wants to read it imperatively. */
        value() {
          if (!root) return this.mode === 'json' ? { type: 'doc', content: [] } : '';
          return this.serialize();
        },

        /** Replace the whole document. */
        setValue(next: unknown) {
          if (!flow || !root) return;
          withoutObserver(() => {
            flow!.load(this.readValue(next));
            history?.reset({ html: this.snapshotHtml(), selection: null });
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
