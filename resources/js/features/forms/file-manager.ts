import {
  decorate as decorateNode,
  formatSize,
  ICONS,
  type FileManagerEntry,
  type FileManagerKind,
  type FileManagerNode,
} from './file-manager/model';
import { byGroup, groupsPresent } from './file-manager/filters';
import { allFolderIds, folderRows, type TreeRow } from './file-manager/tree';
import { boxFrom, hits, type Box } from './file-manager/lasso';
import { nameError, stemRange, type NameError } from './file-manager/names';
import { applyUploadEvent, queueFiles, uploadSummary, type Upload } from './file-manager/uploads';

export type { FileManagerEntry, FileManagerKind, FileManagerNode } from './file-manager/model';

export type FileManagerSort = 'name' | 'size' | 'modified' | 'kind' | 'manual';
export type FileManagerDropPosition = 'before' | 'after' | 'inside';

const INTERNAL_DRAG_MIME = 'application/x-nk-file-manager';

export type FileManagerOptions = {
  items?: FileManagerNode[];
  view?: 'list' | 'grid';
  sort?: FileManagerSort;
  direction?: 'asc' | 'desc';
  /** Enable drag to reorder siblings and drop onto folders to move. */
  sortable?: boolean;
  selectable?: boolean;
  multiple?: boolean;
  searchable?: boolean;
  /** `local` filters in the browser, `remote` delegates to the host. */
  searchMode?: 'local' | 'remote';
  /** `folder` searches the open folder, `all` walks the whole tree. */
  searchScope?: 'folder' | 'all';
  /**
   * Custom matcher: a global name, a dotted path, or a function assigned at
   * runtime. Signature: `(entry, query, manager) => boolean`.
   */
  searchMatcher?: string | ((entry: FileManagerEntry, query: string, manager: any) => boolean) | null;
  /** Debounce before a remote search is emitted, in ms. */
  searchDelay?: number;
  details?: boolean;
  downloadable?: boolean;
  deletable?: boolean;
  renamable?: boolean;
  loading?: boolean;
  rootLabel?: string;
  path?: string[];
  locale?: string;
  /** Card size in the grid, 1 (small) to 5 (large). */
  thumbSize?: number;
  /** Chips that filter the open folder by type. */
  filters?: boolean;
  /** The side panel: shortcuts and the folder tree. */
  sidebar?: boolean;
  /** Side panel shortcuts the app fills; the first is "home". */
  shortcuts?: Array<{ id: string; label: string }>;
  /** "New folder" adds a row being named; off, it only emits `new-folder`. */
  inlineCreate?: boolean;
  /** Quick look: opening a file shows it over the file manager; off, it emits `open`. */
  preview?: boolean;
  /** Upload opens the browser's file picker; off, it emits `upload-request`. */
  uploadPicker?: boolean;
  /** Files can be starred (`starred` on a node), emitting `star`. */
  starrable?: boolean;
};

const defaults: Required<Omit<FileManagerOptions, 'items' | 'path' | 'locale'>> & {
  items: FileManagerNode[];
  path: string[];
  locale: string | undefined;
} = {
  items: [],
  view: 'list',
  sort: 'name',
  direction: 'asc',
  sortable: true,
  selectable: true,
  multiple: true,
  searchable: true,
  searchMode: 'local',
  searchScope: 'folder',
  searchMatcher: null,
  searchDelay: 300,
  details: true,
  downloadable: true,
  deletable: true,
  renamable: true,
  loading: false,
  rootLabel: 'Home',
  thumbSize: 3,
  filters: true,
  sidebar: false,
  shortcuts: [],
  inlineCreate: true,
  preview: true,
  uploadPicker: true,
  starrable: false,
  path: [],
  locale: undefined,
};

/** How long a finger rests on a file before selecting by touch starts, in ms. */
const LONG_PRESS_MS = 500;

/** What the app sends back about an upload. */
const UPLOAD_EVENTS = ['file-manager:progress', 'file-manager:uploaded', 'file-manager:failed'];

/** The most text the viewer reads from a file. */
const PREVIEW_TEXT_LIMIT = 512 * 1024;

/** The id of a folder row being named, before the app creates it. */
const NEW_ID = '__new__';

/** Chip labels per group, with their translation keys. */
const GROUP_LABELS: Record<string, [string, string]> = {
  folder: ['fmGroupFolder', 'Folders'],
  image: ['fmGroupImage', 'Images'],
  document: ['fmGroupDocument', 'Documents'],
  video: ['fmGroupVideo', 'Videos'],
  audio: ['fmGroupAudio', 'Audio'],
  archive: ['fmGroupArchive', 'Archives'],
  code: ['fmGroupCode', 'Code'],
};

/** Narrowest card in the grid for each size step, in px. */
const THUMB_WIDTHS = [120, 150, 180, 220, 280];

/** Breadcrumb entries kept visible before the trail collapses behind an ellipsis. */
const TRAIL_VISIBLE = 3;

function t(key: string, fallback: string, params?: Record<string, string>): string {
  const store = (window as any).NeuraKitTranslations;

  if (store?.translations?.[key]) return store.t(key, params ?? {});

  let out = fallback;
  if (params) {
    for (const [name, value] of Object.entries(params)) out = out.split(`{${name}}`).join(value);
  }

  return out;
}

function decorate(node: FileManagerNode, locale?: string): FileManagerEntry {
  return decorateNode(node, locale, Date.now(), { t });
}

export function neuraFileManager(options: FileManagerOptions = {}) {
  const config = { ...defaults, ...options };
  // The files being sent, kept out of Alpine's reactivity, for Retry.
  const uploadFiles = new Map<string, File>();
  let onUploadEvent: ((event: Event) => void) | null = null;

  return {
    // State
    tree: config.items as FileManagerNode[],
    path: [...config.path] as string[],
    view: config.view as 'list' | 'grid',
    thumbSize: Math.max(1, Math.min(5, Math.round(config.thumbSize))),
    kindFilter: null as string | null,
    searchFocus: false,
    sidebar: config.sidebar,
    shortcuts: config.shortcuts as Array<{ id: string; label: string }>,
    sidebarOpen: false,
    expanded: [] as string[],
    activeShortcut: (config.shortcuts[0]?.id ?? null) as string | null,
    /** The tree the side panel draws: the home items, kept while another shortcut shows. */
    treeSource: config.items as FileManagerNode[],
    treeDrop: null as string | null,
    inlineCreate: config.inlineCreate,
    /** The name being typed: a rename, or a new folder (`isNew`). */
    editing: null as null | { id: string; draft: string; error: NameError; isNew: boolean; original: string },
    /** Copied or cut entries waiting for Paste. */
    clipboard: null as null | { mode: 'copy' | 'cut'; ids: string[]; items: FileManagerNode[] },
    movePicker: false,
    /** Selecting by touch (a long press) — the selection's actions then sit on top. */
    touchSelect: false,
    /** The lasso being drawn, in the list's own coordinates. */
    lasso: null as Box | null,
    _lassoJust: false,
    preview: config.preview,
    /** The file shown in the viewer, by id. */
    previewing: null as string | null,
    previewZoom: 1,
    previewPan: { x: 0, y: 0 },
    previewText: null as string | null,
    previewLoading: false,
    previewFailed: false,
    _previewAbort: null as AbortController | null,
    uploadPicker: config.uploadPicker,
    uploads: [] as Upload[],
    uploadsOpen: false,
    uploadsCollapsed: false,
    starrable: config.starrable,
    searchExpanded: false,
    _press: null as null | { timer: number; x: number; y: number },
    _pressFired: false,
    _lastPointer: 'mouse' as string,
    /** A folder created here, shown until the app hands its items back. */
    pendingCreate: null as null | { name: string; tree: FileManagerNode[]; path: string },
    sort: config.sort as FileManagerSort,
    direction: config.direction as 'asc' | 'desc',
    search: '',
    selected: [] as string[],
    focusId: null as string | null,
    detailsOpen: false,
    dropping: false,
    searching: false,
    loading: config.loading,
    announcement: '',
    menu: { open: false, ready: false, x: 0, y: 0, id: null as string | null },
    drag: {
      active: false,
      ids: [] as string[],
      target: null as string | null,
      position: null as FileManagerDropPosition | null,
    },

    // Configuration
    sortable: config.sortable,
    filters: config.filters,
    selectable: config.selectable,
    multiple: config.multiple && config.selectable,
    searchable: config.searchable,
    searchMode: config.searchMode,
    searchScope: config.searchScope,
    searchMatcher: config.searchMatcher,
    searchDelay: config.searchDelay,
    details: config.details,
    downloadable: config.downloadable,
    deletable: config.deletable,
    renamable: config.renamable,
    rootLabel: config.rootLabel,
    locale: config.locale,

    _dragDepth: 0,
    _lastIndex: -1,
    _searchTimer: 0 as any,

    init() {
      // Selecting by touch ends with the selection.
      (this as any).$watch('selected', (ids: string[]) => {
        if (ids.length === 0) this.touchSelect = false;
      });

      // The app reports on uploads with window events; an instance only hears its own ids.
      onUploadEvent = (event: Event) => {
        const detail = (event as CustomEvent).detail ?? {};
        const type = event.type === 'file-manager:progress' ? 'progress' : event.type === 'file-manager:uploaded' ? 'uploaded' : 'failed';
        const next = applyUploadEvent(this.uploads, { type, id: String(detail.id ?? ''), progress: detail.progress, message: detail.message });
        if (next === this.uploads) return;
        this.uploads = next;
        const upload = next.find((item) => item.id === String(detail.id));
        if (upload && type === 'uploaded') this.announce(t('uploadedName', '{name} uploaded', { name: upload.name }));
        if (upload && type === 'failed') this.announce(t('failedName', '{name} failed', { name: upload.name }));
      };
      for (const name of UPLOAD_EVENTS) window.addEventListener(name, onUploadEvent);

      // A remote search is the host's job: debounce, then hand over the query.
      (this as any).$watch('search', (value: string) => {
        if (this.searchMode !== 'remote') return;

        clearTimeout(this._searchTimer);
        this.searching = true;

        this._searchTimer = setTimeout(() => {
          this.searching = false;
          this.emit('search', {
            query: value.trim(),
            scope: this.searchScope,
            path: [...this.path],
          });
        }, this.searchDelay);
      });
    },

    /* ---------------------------------------------------------------- */
    /* Naming in place                                                   */
    /* ---------------------------------------------------------------- */

    /** The new folder's row: while it is named, then until the app's items come back. */
    get draftFolder(): FileManagerNode | null {
      if (this.isGlobalSearch) return null;
      if (this.editing?.isNew) return { id: NEW_ID, name: this.editing.draft || ' ', type: 'folder', children: [] };
      const pending = this.pendingCreate;
      if (pending && pending.tree === this.tree && pending.path === this.path.join('/')) {
        return { id: NEW_ID, name: pending.name, type: 'folder', children: [] };
      }

      return null;
    },

    isEditing(id: string): boolean {
      return this.editing?.id === id;
    },

    /** Turn an entry's name into a field, its stem selected. */
    startRename(entry: FileManagerEntry) {
      if (!this.renamable || entry.id === NEW_ID) return;

      this.closeMenu();
      this.editing = { id: entry.id, draft: entry.name, error: null, isNew: false, original: entry.name };
      this.focusNameField(stemRange(entry.name));
    },

    /** A new folder row at the top, its name selected, ready to type over. */
    startCreate() {
      this.closeMenu();
      // A type filter could hide the new folder: back to everything.
      this.kindFilter = null;

      const base = t('newFolderName', 'New folder');
      const taken = this.currentNodes.map((node) => node.name.toLowerCase());
      let name = base;
      for (let n = 2; taken.includes(name.toLowerCase()); n++) name = `${base} ${n}`;

      this.clearSelection();
      this.editing = { id: NEW_ID, draft: name, error: null, isNew: true, original: '' };
      this.focusNameField([0, name.length]);
    },

    focusNameField(range: [number, number]) {
      (this as any).$nextTick(() => {
        const input = (this as any).$root?.querySelector?.(`[data-entry="${CSS.escape(this.editing?.id ?? '')}"] [data-fm-name-input]`) as HTMLInputElement | null;
        input?.focus();
        input?.setSelectionRange(range[0], range[1]);
        input?.closest('[data-entry]')?.scrollIntoView({ block: 'nearest' });
      });
    },

    nameErrorText(): string {
      const error = this.editing?.error;
      if (error === 'empty') return t('nameEmpty', 'Type a name');
      if (error === 'slash') return t('nameSlash', 'A name cannot hold / or \\');
      if (error === 'taken') return t('nameTaken', 'Something here already has this name');

      return '';
    },

    /** Enter: check the name, then rename or create — or say what is wrong and stay. */
    commitName() {
      const editing = this.editing;
      if (!editing) return;

      const name = editing.draft.trim();
      const siblings = this.currentNodes.filter((node) => String(node.id) !== editing.id).map((node) => node.name);
      const error = nameError(name, siblings, editing.isNew ? undefined : editing.original);
      if (error) {
        this.editing = { ...editing, error };
        return;
      }

      this.editing = null;

      if (editing.isNew) {
        this.pendingCreate = { name, tree: this.tree, path: this.path.join('/') };
        this.emit('new-folder', { path: [...this.path], name });
        this.announce(t('folderCreated', '{name} created', { name }));
        return;
      }

      if (name === editing.original) return;

      const node = this.findNode(this.tree, editing.id);
      if (!node) return;
      const before = this.raw(decorate(node, this.locale));
      node.name = name;
      this.tree = [...this.tree];
      if (this.pendingCreate) this.pendingCreate = { ...this.pendingCreate, tree: this.tree };
      this.emit('rename', { items: [before], name, path: [...this.path] });
      this.announce(t('renamedTo', 'Renamed to {name}', { name }));
    },

    cancelName() {
      this.editing = null;
    },

    /** Is the side panel showing the home items (first shortcut, or no shortcuts)? */
    get atHome(): boolean {
      return this.activeShortcut === null || this.activeShortcut === this.shortcuts[0]?.id;
    },

    /** Rows of the side panel's folder tree. */
    get treeRows(): TreeRow[] {
      return folderRows(this.atHome ? this.tree : this.treeSource, new Set(this.expanded));
    },

    isOpenFolder(row: TreeRow): boolean {
      return this.atHome && row.path.join('/') === this.path.join('/');
    },

    toggleFolder(id: string) {
      this.expanded = this.expanded.includes(id)
        ? this.expanded.filter((other) => other !== id)
        : [...this.expanded, id];
    },

    /** Open a folder from the tree: back to the home items first if a shortcut was showing. */
    openFolder(path: string[]) {
      if (!this.atHome) this.pickShortcut(this.shortcuts[0].id);
      this.path = [...path];
      this.sidebarOpen = false;
      this.afterNavigate();
    },

    pickShortcut(id: string) {
      this.sidebarOpen = false;
      if (this.activeShortcut === id && this.path.length === 0) return;

      // Leaving home: keep its tree for the panel while the app shows other items.
      if (this.atHome) this.treeSource = this.tree;
      this.activeShortcut = id;
      this.path = [];
      this.emit('shortcut', { id });
      this.afterNavigate();
    },

    onTreeDragOver(event: DragEvent, row: TreeRow) {
      if (!this.drag.active || !this.atHome) return;
      if (this.drag.ids.some((id) => id === row.id || this.isSelfOrDescendant(id, row.id))) return;
      event.preventDefault();
      if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
      this.treeDrop = row.id;
    },

    onTreeDrop(event: DragEvent, row: TreeRow) {
      event.preventDefault();
      const ids = this.drag.active ? [...this.drag.ids] : [];
      this.treeDrop = null;
      this.onItemDragEnd();
      if (ids.length) this.moveInto(ids, row.id);
    },

    /**
     * Move entries of the open folder into any folder of the tree (null: the top).
     * Never into themselves or one of their own folders.
     */
    moveInto(ids: string[], folderId: string | null) {
      if (folderId !== null && ids.some((id) => id === folderId || this.isSelfOrDescendant(id, folderId))) return;
      if (folderId !== null && !this.findNode(this.tree, folderId)) return;

      // Taken from wherever they are in the tree; those already in the target stay put.
      const moving: FileManagerNode[] = [];
      const strip = (nodes: FileManagerNode[], parent: string | null): FileManagerNode[] =>
        nodes.filter((node) => {
          if (parent !== folderId && ids.includes(String(node.id))) {
            moving.push(node);
            return false;
          }
          if (Array.isArray(node.children)) node.children = strip(node.children, String(node.id));
          return true;
        });

      const next = strip(this.tree, null);
      if (moving.length === 0) return;

      const target = folderId === null ? null : this.findNode(next, folderId);
      if (target) target.children = [...(target.children ?? []), ...moving];
      this.tree = target ? [...next] : [...next, ...moving];

      this.announce(t('itemsMoved', '{count} moved', { count: String(moving.length) }));
      this.emit('move', {
        items: moving,
        target: target ? this.raw(decorate(target, this.locale)) : null,
        position: 'inside',
        path: [...this.path],
      });
      this.selected = [];
      this.emitSelection();
    },

    /* ---------------------------------------------------------------- */
    /* Move to, copy, cut, paste                                          */
    /* ---------------------------------------------------------------- */

    /** Every folder, unfolded, for the "Move to" list. */
    get moveRows(): TreeRow[] {
      return folderRows(this.tree, new Set(allFolderIds(this.tree)));
    },

    canMoveTo(folderId: string): boolean {
      if (folderId === (this.path[this.path.length - 1] ?? null)) return false;

      return !this.selected.some((id) => id === folderId || this.isSelfOrDescendant(id, folderId));
    },

    moveTo(folderId: string | null) {
      this.movePicker = false;
      this.moveInto([...this.selected], folderId);
    },

    copySelection() {
      if (this.selected.length === 0) return;
      this.clipboard = { mode: 'copy', ids: [...this.selected], items: this.selectedEntries.map((entry) => this.raw(entry)) };
      this.announce(t('itemsCopied', '{count} copied', { count: String(this.selected.length) }));
    },

    cutSelection() {
      if (this.selected.length === 0) return;
      this.clipboard = { mode: 'cut', ids: [...this.selected], items: this.selectedEntries.map((entry) => this.raw(entry)) };
      this.announce(t('itemsCut', '{count} cut — paste them in another folder', { count: String(this.selected.length) }));
    },

    isCut(id: string): boolean {
      return this.clipboard?.mode === 'cut' && this.clipboard.ids.includes(id);
    },

    /** Into the open folder: a copy is the app's to make; a cut moves at once. */
    paste() {
      const clipboard = this.clipboard;
      if (!clipboard) return;

      const folderId = this.path[this.path.length - 1] ?? null;
      if (clipboard.mode === 'cut') {
        this.clipboard = null;
        this.moveInto(clipboard.ids, folderId);
        return;
      }

      const folder = folderId === null ? null : this.findNode(this.tree, folderId);
      this.emit('copy', {
        items: clipboard.items,
        target: folder ? this.raw(decorate(folder, this.locale)) : null,
        path: [...this.path],
      });
    },

    /* ---------------------------------------------------------------- */
    /* Stars and touch                                                    */
    /* ---------------------------------------------------------------- */

    toggleStar(entry: FileManagerEntry) {
      if (!this.starrable) return;
      const node = this.findNode(this.tree, entry.id);
      if (!node) return;

      node.starred = !node.starred;
      this.tree = [...this.tree];
      this.emit('star', { item: this.raw(decorate(node, this.locale)), starred: node.starred });
      this.announce(node.starred ? t('addedToFavorites', 'Added to favourites') : t('removedFromFavorites', 'Removed from favourites'));
    },

    /**
     * A finger held on a file (half a second, without moving) starts selecting by touch:
     * then each tap ticks or unticks. Scrolling or moving cancels it.
     */
    onPressStart(event: PointerEvent, entry: FileManagerEntry) {
      this._lastPointer = event.pointerType;
      if (event.pointerType !== 'touch' || !this.selectable || entry.id === NEW_ID) return;

      const target = event.currentTarget as HTMLElement;
      const cancel = () => {
        if (this._press) window.clearTimeout(this._press.timer);
        this._press = null;
        target.removeEventListener('pointermove', move);
        target.removeEventListener('pointerup', cancel);
        target.removeEventListener('pointercancel', cancel);
      };
      const move = (moveEvent: PointerEvent) => {
        if (this._press && Math.hypot(moveEvent.clientX - this._press.x, moveEvent.clientY - this._press.y) > 8) cancel();
      };

      this._press = {
        x: event.clientX,
        y: event.clientY,
        timer: window.setTimeout(() => {
          cancel();
          this._pressFired = true;
          this.touchSelect = true;
          if (!this.isSelected(entry.id)) this.selected = this.multiple ? [...this.selected, entry.id] : [entry.id];
          this.focusId = entry.id;
          this.emitSelection();
          navigator.vibrate?.(10);
        }, LONG_PRESS_MS),
      };
      target.addEventListener('pointermove', move);
      target.addEventListener('pointerup', cancel);
      target.addEventListener('pointercancel', cancel);
    },

    /* ---------------------------------------------------------------- */
    /* Uploads                                                            */
    /* ---------------------------------------------------------------- */

    pickFiles() {
      ((this as any).$refs.filePicker as HTMLInputElement | undefined)?.click();
    },

    /** Files picked or dropped: listed in the panel, handed to the app with their ids. */
    startUploads(files: File[]) {
      if (files.length === 0) return;

      const queued = queueFiles(files, () => `fm-${Math.random().toString(36).slice(2, 10)}`);
      queued.forEach((upload, index) => uploadFiles.set(upload.id, files[index]));
      this.uploads = [...this.uploads.filter((upload) => upload.status !== 'cancelled'), ...queued];
      this.uploadsOpen = true;
      this.uploadsCollapsed = false;

      this.announce(t('filesAdded', '{count} file(s) added', { count: String(files.length) }));
      this.emit('upload', {
        files,
        path: [...this.path],
        uploads: queued.map((upload) => ({ id: upload.id, name: upload.name, size: upload.size, file: uploadFiles.get(upload.id) })),
      });
    },

    cancelUpload(id: string) {
      this.uploads = applyUploadEvent(this.uploads, { type: 'cancel', id });
      this.emit('upload-cancel', { id });
    },

    retryUpload(id: string) {
      const next = applyUploadEvent(this.uploads, { type: 'retry', id });
      if (next === this.uploads) return;
      this.uploads = next;
      this.emit('upload-retry', { id, file: uploadFiles.get(id) ?? null });
    },

    get uploadSummaryNow(): { active: number; done: number; failed: number } {
      return uploadSummary(this.uploads);
    },

    get uploadTitle(): string {
      const summary = this.uploadSummaryNow;
      return summary.active > 0
        ? t('uploadingCount', 'Uploading {count} files', { count: String(summary.active) })
        : t('uploadedCount', '{count} files uploaded', { count: String(summary.done) });
    },

    /** Only once nothing is still going. */
    closeUploads() {
      if (this.uploadSummaryNow.active > 0) return;
      for (const upload of this.uploads) uploadFiles.delete(upload.id);
      this.uploads = [];
      this.uploadsOpen = false;
    },

    /* ---------------------------------------------------------------- */
    /* Quick look                                                         */
    /* ---------------------------------------------------------------- */

    /** The files the viewer steps through: the list's files, in its order. */
    get previewList(): FileManagerEntry[] {
      return this.entries.filter((entry) => !entry.isFolder && entry.id !== NEW_ID);
    },

    get previewEntry(): FileManagerEntry | null {
      return this.previewing ? this.previewList.find((entry) => entry.id === this.previewing) ?? null : null;
    },

    /** How the viewer shows the file. */
    get previewMode(): 'image' | 'thumbnail' | 'video' | 'audio' | 'pdf' | 'text' | 'loading' | 'none' {
      const entry = this.previewEntry;
      if (!entry || this.previewFailed) return 'none';
      if (entry.kind === 'image') return entry.url ? 'image' : entry.thumbnail ? 'thumbnail' : 'none';
      if (!entry.url) return entry.thumbnail ? 'thumbnail' : 'none';
      if (entry.kind === 'video') return 'video';
      if (entry.kind === 'audio') return 'audio';
      if (entry.kind === 'pdf') return 'pdf';
      if (entry.kind === 'text' || entry.kind === 'code') return this.previewLoading ? 'loading' : this.previewText !== null ? 'text' : 'none';

      return 'none';
    },

    previewIndex(): number {
      return this.previewList.findIndex((entry) => entry.id === this.previewing);
    },

    previewPosition(): string {
      return t('positionOf', '{index} of {total}', { index: String(this.previewIndex() + 1), total: String(this.previewList.length) });
    },

    previewMeta(): string {
      const entry = this.previewEntry;
      if (!entry) return '';
      const size = entry.width && entry.height ? `${entry.width} × ${entry.height}` : null;

      return [entry.typeLabel, entry.sizeLabel, size, entry.modifiedLabel].filter(Boolean).join(' · ');
    },

    openPreview(entry: FileManagerEntry) {
      if (entry.isFolder || entry.id === NEW_ID) return;

      const opening = this.previewing === null;
      this.previewing = entry.id;
      this.previewZoom = 1;
      this.previewPan = { x: 0, y: 0 };
      this.previewFailed = false;
      this.previewText = null;
      this.focusId = entry.id;
      this.loadPreviewText(entry);
      this.emit('preview', { item: this.raw(entry), path: [...this.path] });

      if (opening) (this as any).$nextTick(() => ((this as any).$refs.preview as HTMLElement | undefined)?.focus());
    },

    closePreview() {
      const id = this.previewing;
      this._previewAbort?.abort();
      this.previewing = null;
      this.previewText = null;

      // Focus goes back to the list, on the file it came from (the list carries the focus;
      // its rows are marked by `focusId`).
      if (id) this.focusId = id;
      (this as any).$nextTick(() => {
        const root = (this as any).$root as HTMLElement;
        root.querySelector<HTMLElement>('[role="listbox"]')?.focus({ preventScroll: true });
      });
    },

    previewStep(delta: 1 | -1) {
      const next = this.previewList[this.previewIndex() + delta];
      if (next) this.openPreview(next);
    },

    /** Text and code are read, at most 512 KB of them. */
    async loadPreviewText(entry: FileManagerEntry) {
      this._previewAbort?.abort();
      if (!(entry.kind === 'text' || entry.kind === 'code') || !entry.url) return;

      const abort = new AbortController();
      this._previewAbort = abort;
      this.previewLoading = true;

      try {
        const response = await fetch(entry.url, { signal: abort.signal, credentials: 'same-origin' });
        if (!response.ok || !response.body) throw new Error(String(response.status));

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let text = '';
        let bytes = 0;
        let cut = false;
        for (;;) {
          const { done, value } = await reader.read();
          if (done) break;
          bytes += value.length;
          text += decoder.decode(value, { stream: true });
          if (bytes >= PREVIEW_TEXT_LIMIT) {
            cut = true;
            await reader.cancel();
            break;
          }
        }
        if (this.previewing !== entry.id) return;
        this.previewText = cut ? `${text.slice(0, PREVIEW_TEXT_LIMIT)}\n…` : text;
      } catch {
        if (this.previewing === entry.id && !abort.signal.aborted) this.previewFailed = true;
      } finally {
        if (this._previewAbort === abort) this.previewLoading = false;
      }
    },

    zoomPreview(direction: 1 | -1) {
      this.previewZoom = Math.min(5, Math.max(1, Math.round(this.previewZoom * (direction === 1 ? 1.25 : 0.8) * 100) / 100));
      if (this.previewZoom === 1) this.previewPan = { x: 0, y: 0 };
    },

    fitPreview() {
      this.previewZoom = 1;
      this.previewPan = { x: 0, y: 0 };
    },

    onPreviewWheel(event: WheelEvent) {
      if (this.previewMode !== 'image' || !(event.ctrlKey || event.metaKey)) return;
      event.preventDefault();
      this.zoomPreview(event.deltaY < 0 ? 1 : -1);
    },

    /** Drag a zoomed picture around. */
    startPreviewPan(event: PointerEvent) {
      if (this.previewZoom <= 1) return;
      event.preventDefault();
      const target = event.currentTarget as HTMLElement;
      const start = { x: event.clientX - this.previewPan.x, y: event.clientY - this.previewPan.y };
      const move = (moveEvent: PointerEvent) => {
        this.previewPan = { x: moveEvent.clientX - start.x, y: moveEvent.clientY - start.y };
      };
      const end = () => {
        target.removeEventListener('pointermove', move);
        target.removeEventListener('pointerup', end);
        target.removeEventListener('pointercancel', end);
      };
      target.setPointerCapture?.(event.pointerId);
      target.addEventListener('pointermove', move);
      target.addEventListener('pointerup', end);
      target.addEventListener('pointercancel', end);
    },

    /** Keys in the viewer: arrows step, Escape or Space close, + and − zoom, Tab stays inside. */
    onPreviewKey(event: KeyboardEvent) {
      const dialog = event.currentTarget as HTMLElement;
      const onButton = (event.target as HTMLElement)?.closest?.('button, video, audio');

      if (event.key === 'Escape' || (event.key === ' ' && !onButton)) {
        event.preventDefault();
        this.closePreview();
      } else if (event.key === 'ArrowRight' && !onButton) {
        event.preventDefault();
        this.previewStep(1);
      } else if (event.key === 'ArrowLeft' && !onButton) {
        event.preventDefault();
        this.previewStep(-1);
      } else if ((event.key === '+' || event.key === '=') && this.previewMode === 'image') {
        this.zoomPreview(1);
      } else if (event.key === '-' && this.previewMode === 'image') {
        this.zoomPreview(-1);
      } else if (event.key === 'Tab') {
        const focusable = Array.from(dialog.querySelectorAll<HTMLElement>('button:not([disabled]), [href], video, audio, iframe, [tabindex]:not([tabindex="-1"])'))
          .filter((element) => element.offsetParent !== null);
        if (focusable.length === 0) return event.preventDefault();
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    },

    /* ---------------------------------------------------------------- */
    /* Lasso                                                              */
    /* ---------------------------------------------------------------- */

    /**
     * A mouse drag from an empty spot of the list draws a box; what it touches is
     * selected (Ctrl/⌘ adds to the selection). Never by touch, so fingers scroll.
     */
    startLasso(event: PointerEvent) {
      if (event.pointerType !== 'mouse' || event.button !== 0 || !this.multiple) return;
      const target = event.target as HTMLElement;
      if (target.closest('[data-entry], button, input, a, label, [data-fm-selection-bar]')) return;

      const list = event.currentTarget as HTMLElement;
      const origin = list.getBoundingClientRect();
      const at = (x: number, y: number) => ({ x: x - origin.left + list.scrollLeft, y: y - origin.top + list.scrollTop });

      // Measured once, at the start: every entry's box in the list's own coordinates.
      const boxes = Array.from(list.querySelectorAll<HTMLElement>('[data-entry]'))
        .filter((element) => element.offsetParent !== null && element.dataset.entry !== NEW_ID)
        .map((element) => {
          const rect = element.getBoundingClientRect();
          const corner = at(rect.left, rect.top);
          return { id: element.dataset.entry!, left: corner.x, top: corner.y, right: corner.x + rect.width, bottom: corner.y + rect.height };
        });

      const start = at(event.clientX, event.clientY);
      const before = event.ctrlKey || event.metaKey ? [...this.selected] : [];
      let moved = false;

      const move = (moveEvent: PointerEvent) => {
        const now = at(moveEvent.clientX, moveEvent.clientY);
        if (!moved && Math.hypot(now.x - start.x, now.y - start.y) < 4) return;
        moved = true;
        this.lasso = boxFrom(start, now);
        this.selected = [...new Set([...before, ...hits(this.lasso, boxes)])];
      };
      const end = () => {
        list.removeEventListener('pointermove', move);
        list.removeEventListener('pointerup', end);
        list.removeEventListener('pointercancel', end);
        this.lasso = null;
        if (moved) {
          this._lassoJust = true;
          this.emitSelection();
        }
      };

      list.setPointerCapture?.(event.pointerId);
      list.addEventListener('pointermove', move);
      list.addEventListener('pointerup', end);
      list.addEventListener('pointercancel', end);
    },

    /** A click on an empty spot clears the selection — not the click ending a lasso. */
    onBodyClick() {
      if (this._lassoJust) {
        this._lassoJust = false;
        return;
      }
      this.clearSelection();
    },

    destroy() {
      if (onUploadEvent) for (const name of UPLOAD_EVENTS) window.removeEventListener(name, onUploadEvent);
      clearTimeout(this._searchTimer);
    },

    /* ---------------------------------------------------------------- */
    /* Derived state                                                     */
    /* ---------------------------------------------------------------- */

    /** Folder nodes matching the current path, from the root down. */
    get trail(): Array<{ id: string | null; name: string }> {
      const shortcut = this.atHome ? null : this.shortcuts.find((item) => item.id === this.activeShortcut);
      const crumbs: Array<{ id: string | null; name: string }> = [
        { id: null, name: shortcut?.label ?? this.rootLabel },
      ];

      let level = this.tree;

      for (const id of this.path) {
        const folder = level.find((node) => String(node.id) === id);
        if (!folder) break;

        crumbs.push({ id: String(folder.id), name: folder.name });
        level = folder.children ?? [];
      }

      return crumbs;
    },

    /**
     * Breadcrumb as displayed: a deep path collapses its middle behind an
     * ellipsis so the toolbar never pushes the actions off screen.
     */
    get visibleTrail(): Array<{ id: string | null; name: string; ellipsis?: boolean }> {
      const trail = this.trail;
      if (trail.length <= TRAIL_VISIBLE + 1) return trail;

      return [
        trail[0],
        { id: trail[trail.length - TRAIL_VISIBLE - 1].id, name: '…', ellipsis: true },
        ...trail.slice(-TRAIL_VISIBLE),
      ];
    },

    /** Raw nodes of the folder currently open. */
    get currentNodes(): FileManagerNode[] {
      let level = this.tree;

      for (const id of this.path) {
        const folder = level.find((node) => String(node.id) === id);
        if (!folder) return [];
        level = folder.children ?? [];
      }

      return level;
    },

    /** The query, normalised once for every matcher call. */
    get query(): string {
      return this.search.trim().toLowerCase();
    },

    /**
     * Does an entry match the current query?
     *
     * `searchMatcher` names a global function `(entry, query, manager) => boolean`,
     * so an app can search on tags, owners, full text… without forking the
     * component. Falls back to a name match.
     */
    matches(entry: FileManagerEntry, query: string): boolean {
      if (!query) return true;

      const custom = this.resolveMatcher();
      if (custom) return !!custom(entry, query, this);

      return entry.name.toLowerCase().includes(query);
    },

    /**
     * Resolve `searchMatcher` to a function. Accepts a bare global name
     * (`"searchFiles"`) or a dotted path (`"App.search.files"`) so an app can
     * namespace its helpers instead of polluting `window`.
     */
    resolveMatcher(): ((entry: FileManagerEntry, query: string, manager: any) => boolean) | null {
      if (typeof this.searchMatcher === 'function') return this.searchMatcher as any;
      if (!this.searchMatcher) return null;

      const found = String(this.searchMatcher)
        .split('.')
        .reduce<any>((scope, key) => (scope == null ? undefined : scope[key]), window as any);

      return typeof found === 'function' ? found : null;
    },

    /** Every node of the tree, each carrying the folder path that holds it. */
    _flatten(nodes: FileManagerNode[], trail: Array<{ id: string; name: string }>, out: FileManagerEntry[]) {
      for (const node of nodes) {
        const entry = decorate(node, this.locale);
        entry.pathIds = trail.map((crumb) => crumb.id);
        entry.pathNames = trail.map((crumb) => crumb.name);
        out.push(entry);

        if (Array.isArray(node.children) && node.children.length > 0) {
          this._flatten(node.children, [...trail, { id: String(node.id), name: node.name }], out);
        }
      }
    },

    /** True when the query is being answered from the whole tree. */
    get isGlobalSearch(): boolean {
      return this.searchScope === 'all' && this.query.length > 0 && this.searchMode !== 'remote';
    },

    /** The entries the body shows: searched, sorted, then kept to the picked type. */
    get entries(): FileManagerEntry[] {
      return byGroup(this.baseEntries, this.kindFilter);
    },

    /** Chips offered: the groups present, when there are at least two. */
    get filterGroups(): string[] {
      if (!this.filters) return [];
      const groups = groupsPresent(this.baseEntries);

      return groups.length > 1 ? groups : [];
    },

    groupLabel(group: string): string {
      const [key, fallback] = GROUP_LABELS[group] ?? ['fmGroupDocument', group];

      return t(key, fallback);
    },

    /** The kind whose hue a group's chip shows. */
    groupKind(group: string): string {
      return group === 'document' ? 'doc' : group;
    },

    setKindFilter(group: string | null) {
      if (this.kindFilter === group) return;

      this.kindFilter = group;
      this.clearSelection();
      this.emit('filter', { kind: group });
    },

    /** Decorated, searched and sorted entries, before the type filter. */
    get baseEntries(): FileManagerEntry[] {
      const query = this.query;
      let list: FileManagerEntry[];

      if (this.isGlobalSearch) {
        const all: FileManagerEntry[] = [];
        this._flatten(this.tree, [], all);
        list = all.filter((entry) => this.matches(entry, query));
      } else {
        list = this.currentNodes.map((node) => decorate(node, this.locale));
        const draft = this.draftFolder;
        if (draft) list = [decorate(draft, this.locale), ...list];

        // In remote mode the host already returned the matching items.
        if (this.searchMode !== 'remote') {
          list = list.filter((entry) => this.matches(entry, query));
        }
      }

      const factor = this.direction === 'desc' ? -1 : 1;

      // Manual order mirrors the underlying tree siblings — used after a drag reorder.
      if (this.sort === 'manual') {
        return list;
      }

      return list.sort((a, b) => {
        // A folder being named stays on top until it exists.
        if (a.id === NEW_ID || b.id === NEW_ID) return a.id === NEW_ID ? -1 : 1;

        // Folders always lead, whatever the sort is.
        if (a.isFolder !== b.isFolder) return a.isFolder ? -1 : 1;

        let comparison = 0;
        if (this.sort === 'size') comparison = (a.size ?? 0) - (b.size ?? 0);
        else if (this.sort === 'modified') comparison = a.modifiedAt - b.modifiedAt;
        else if (this.sort === 'kind') comparison = a.kind.localeCompare(b.kind);

        if (comparison === 0) {
          comparison = a.name.localeCompare(b.name, this.locale || undefined, { numeric: true, sensitivity: 'base' });
          return comparison * (this.sort === 'name' ? factor : 1);
        }

        return comparison * factor;
      });
    },

    /** Drag is available when enabled and the list is not a filtered/global search. */
    get canSortEntries(): boolean {
      return this.sortable && !this.isFiltered && this.searchMode !== 'remote';
    },

    dropHint(entryId: string): FileManagerDropPosition | null {
      if (!this.drag.active || this.drag.target !== entryId) return null;

      return this.drag.position;
    },

    isDragged(entryId: string): boolean {
      return this.drag.active && this.drag.ids.includes(entryId);
    },

    /** Folders as the grid lays them out: their own section, first. */
    get folderEntries(): FileManagerEntry[] {
      return this.entries.filter((entry) => entry.isFolder);
    },

    get fileEntries(): FileManagerEntry[] {
      return this.entries.filter((entry) => !entry.isFolder);
    },

    /** Narrowest card for the current size step, in px. */
    get thumbWidth(): number {
      return THUMB_WIDTHS[this.thumbSize - 1] ?? THUMB_WIDTHS[2];
    },

    setThumbSize(size: number) {
      const next = Math.max(1, Math.min(5, Math.round(Number(size) || 3)));
      this.thumbSize = next;
      this.emit('thumb-size', { size: next });
    },

    get isEmpty(): boolean {
      return this.entries.length === 0;
    },

    get isFiltered(): boolean {
      return this.search.trim().length > 0;
    },

    get selectedEntries(): FileManagerEntry[] {
      return this.entries.filter((entry) => this.selected.includes(entry.id));
    },

    get selectionSize(): string {
      const total = this.selectedEntries.reduce((sum, entry) => sum + (entry.size ?? 0), 0);

      return formatSize(total);
    },

    get allSelected(): boolean {
      return this.entries.length > 0 && this.selected.length === this.entries.length;
    },

    get someSelected(): boolean {
      return this.selected.length > 0 && !this.allSelected;
    },

    get focused(): FileManagerEntry | null {
      return this.entries.find((entry) => entry.id === this.focusId) ?? null;
    },

    /** Item shown in the details panel: the focused one, or the lone selection. */
    get detailed(): FileManagerEntry | null {
      if (this.focused) return this.focused;
      if (this.selectedEntries.length === 1) return this.selectedEntries[0];

      return null;
    },

    get summary(): string {
      const folders = this.entries.filter((entry) => entry.isFolder).length;
      const files = this.entries.length - folders;
      const bytes = this.entries.reduce((sum, entry) => sum + (entry.isFolder ? 0 : entry.size ?? 0), 0);

      return [
        folders ? t('foldersCount', '{count} folders', { count: String(folders) }) : null,
        t('filesCount', '{count} files', { count: String(files) }),
        bytes ? formatSize(bytes) : null,
      ].filter(Boolean).join(' · ');
    },

    icon(entry: FileManagerEntry): string {
      return ICONS[entry.kind] ?? ICONS.file;
    },

    /** Arrow reflecting the active sort, so the header states the direction. */
    sortIcon(column: string): 'asc' | 'desc' | null {
      if (this.sort !== column) return null;

      return this.direction === 'asc' ? 'asc' : 'desc';
    },

    /* ---------------------------------------------------------------- */
    /* Context menu                                                      */
    /* ---------------------------------------------------------------- */

    get menuEntry(): FileManagerEntry | null {
      return this.entries.find((entry) => entry.id === this.menu.id) ?? null;
    },

    /** Right click acts on the clicked row, keeping an existing multi-selection. */
    openMenu(event: MouseEvent, entry?: FileManagerEntry) {
      event.preventDefault();

      if (entry) {
        if (!this.isSelected(entry.id)) this.select(entry);
        else this.focusId = entry.id;
      }

      // `$el` is the element the expression is bound to — the row when the menu
      // is opened from a row — so resolve the component root from the event.
      const root = this.rootElement(event);
      const bounds = root.getBoundingClientRect();

      // Anchor on the cursor, but stay invisible until measured.
      this.menu = {
        open: true,
        ready: false,
        x: event.clientX - bounds.left,
        y: event.clientY - bounds.top,
        id: entry?.id ?? null,
      };

      // Flip (never clamp) so the menu stays attached to the pointer instead of
      // sliding across the component and over the toolbar. The measurement waits
      // a frame: right after $nextTick the `x-if` branches are still swapping and
      // the menu reports a taller, transient box.
      (this as any).$nextTick(() => {
        requestAnimationFrame(() => {
          const el = root.querySelector('[data-slot="file-manager-menu"]') as HTMLElement | null;
          if (!el) {
            this.menu = { ...this.menu, ready: true };
            return;
          }

          const menu = el.getBoundingClientRect();
          const gutter = 8;
          let { x, y } = this.menu;

          if (x + menu.width > bounds.width - gutter) x = Math.max(gutter, x - menu.width);
          if (y + menu.height > bounds.height - gutter) y = Math.max(gutter, y - menu.height);

          this.menu = { ...this.menu, x, y, ready: true };
        });
      });
    },

    /** The menu of an entry, opened under one of its buttons ("…" on a row or a card). */
    openMenuAt(entry: FileManagerEntry, anchor: HTMLElement) {
      const box = anchor.getBoundingClientRect();
      const event = { preventDefault() {}, clientX: box.left, clientY: box.bottom + 4, target: anchor };

      this.openMenu(event as unknown as MouseEvent, entry);
    },

    /** The component root, whichever element the caller was bound to. */
    rootElement(event?: Event): HTMLElement {
      const fromEvent = (event?.target as HTMLElement | undefined)?.closest?.('[data-nk-file-manager]');

      return (fromEvent as HTMLElement) ?? ((this as any).$el as HTMLElement).closest('[data-nk-file-manager]') ?? (this as any).$el;
    },

    closeMenu() {
      if (this.menu.open) this.menu = { ...this.menu, open: false, ready: false };
    },

    runMenu(name: string) {
      const entry = this.menuEntry;
      this.closeMenu();

      if (name === 'open' && entry) {
        this.open(entry);
        return;
      }

      if (name === 'preview' && entry) {
        this.open(entry);
        return;
      }

      if (name === 'rename' && entry && this.selected.length <= 1) {
        this.startRename(entry);
        return;
      }

      this.action(name, this.selected.length > 1 ? undefined : entry ?? undefined);
    },

    /* ---------------------------------------------------------------- */
    /* Navigation                                                        */
    /* ---------------------------------------------------------------- */

    open(entry: FileManagerEntry) {
      if (entry.id === NEW_ID) return;

      // A whole-tree result lives somewhere else: move there first.
      if (entry.pathIds && entry.pathIds.join('/') !== this.path.join('/')) {
        this.path = [...entry.pathIds];
      }

      if (entry.isFolder) {
        this.enter(entry.id);
        return;
      }

      if (this.preview) {
        this.openPreview(entry);
        return;
      }

      this.emit('open', { item: this.raw(entry), path: [...this.path] });
    },

    /** Jump to the folder holding a whole-tree result, keeping it selected. */
    reveal(entry: FileManagerEntry) {
      if (!entry.pathIds) return;

      this.path = [...entry.pathIds];
      this.search = '';
      this.selected = [entry.id];
      this.focusId = entry.id;
      this.emitSelection();
    },

    setScope(scope: 'folder' | 'all') {
      if (this.searchScope === scope) return;

      this.searchScope = scope;
      this.clearSelection();
      this.emit('search-scope', { scope, query: this.query });
    },

    enter(id: string) {
      this.path = [...this.path, id];
      this.afterNavigate();
    },

    goTo(id: string | null) {
      if (id === null) {
        this.path = [];
      } else {
        const index = this.path.indexOf(id);
        this.path = index === -1 ? this.path : this.path.slice(0, index + 1);
      }

      this.afterNavigate();
    },

    goUp() {
      if (this.path.length === 0) return;

      this.path = this.path.slice(0, -1);
      this.afterNavigate();
    },

    afterNavigate() {
      this.closeMenu();
      this.clearSelection();
      this.search = '';
      this.kindFilter = null;
      // The open folder's parents unfold in the side panel.
      const parents = this.path.slice(0, -1).filter((id) => !this.expanded.includes(id));
      if (parents.length) this.expanded = [...this.expanded, ...parents];
      this.focusId = null;
      this._lastIndex = -1;

      const current = this.trail[this.trail.length - 1];
      this.announce(t('openedFolder', 'Opened {name}', { name: current?.name ?? this.rootLabel }));
      this.emit('navigate', { path: [...this.path], folder: current?.id ?? null });
    },

    /* ---------------------------------------------------------------- */
    /* Selection                                                         */
    /* ---------------------------------------------------------------- */

    isSelected(id: string): boolean {
      return this.selected.includes(id);
    },

    select(entry: FileManagerEntry, event?: MouseEvent | KeyboardEvent) {
      if (entry.id === NEW_ID) return;
      // The tap that ends a long press has already selected.
      if (this._pressFired) {
        this._pressFired = false;
        return;
      }
      // By touch: a tap ticks while selecting, and opens otherwise.
      if (event instanceof MouseEvent && this._lastPointer === 'touch') {
        if (this.touchSelect) return this.toggle(entry);
        return this.open(entry);
      }
      this.focusId = entry.id;

      if (!this.selectable) {
        this.emit('select', { items: [] });
        return;
      }

      const index = this.entries.findIndex((candidate) => candidate.id === entry.id);
      const additive = !!(event && (event.metaKey || event.ctrlKey));
      const ranged = !!(event && event.shiftKey);

      if (this.multiple && ranged && this._lastIndex !== -1) {
        const [from, to] = this._lastIndex < index ? [this._lastIndex, index] : [index, this._lastIndex];
        this.selected = this.entries.slice(from, to + 1).map((candidate) => candidate.id);
      } else if (this.multiple && additive) {
        this.selected = this.isSelected(entry.id)
          ? this.selected.filter((id) => id !== entry.id)
          : [...this.selected, entry.id];
        this._lastIndex = index;
      } else {
        this.selected = [entry.id];
        this._lastIndex = index;
      }

      this.emitSelection();
    },

    toggle(entry: FileManagerEntry) {
      if (!this.selectable) return;

      this.selected = this.isSelected(entry.id)
        ? this.selected.filter((id) => id !== entry.id)
        : (this.multiple ? [...this.selected, entry.id] : [entry.id]);

      this.focusId = entry.id;
      this._lastIndex = this.entries.findIndex((candidate) => candidate.id === entry.id);
      this.emitSelection();
    },

    toggleAll() {
      if (!this.multiple) return;

      this.selected = this.allSelected ? [] : this.entries.map((entry) => entry.id);
      this.emitSelection();
    },

    clearSelection() {
      if (this.selected.length === 0) return;

      this.selected = [];
      this.emitSelection();
    },

    emitSelection() {
      this.announce(this.selected.length
        ? t('itemsSelected', '{count} selected', { count: String(this.selected.length) })
        : t('selectionCleared', 'Selection cleared'));

      this.emit('select', { items: this.selectedEntries.map((entry) => this.raw(entry)) });
    },

    /* ---------------------------------------------------------------- */
    /* Keyboard                                                          */
    /* ---------------------------------------------------------------- */

    onKeydown(event: KeyboardEvent) {
      // The grid shows folders, then files, each in its own section: keys follow that order.
      const grid = this.view === 'grid';
      const entries = grid ? [...this.folderEntries, ...this.fileEntries] : this.entries;
      if (entries.length === 0) return;

      const current = entries.findIndex((entry) => entry.id === this.focusId);
      const folders = this.folderEntries.length;

      const move = (delta: number) => {
        event.preventDefault();
        const next = current === -1
          ? 0
          : Math.min(entries.length - 1, Math.max(0, current + delta));
        this.focusEntry(entries[next], event.shiftKey);
      };

      /** Up or down a row of the section the focus is in; past its edge, into the other one. */
      const vertical = (direction: 1 | -1) => {
        if (!grid) return move(direction);
        event.preventDefault();
        if (current === -1) return this.focusEntry(entries[0], event.shiftKey);
        const inFolders = current < folders;
        const start = inFolders ? 0 : folders;
        const end = inFolders ? folders : entries.length;
        const target = current + direction * this.columnCount(inFolders ? 'folderGrid' : 'grid');
        const next = target >= start && target < end
          ? target
          : direction === 1
            ? (inFolders && entries.length > folders ? folders : end - 1)
            : (!inFolders && folders > 0 ? folders - 1 : start);
        this.focusEntry(entries[next], event.shiftKey);
      };

      if ((event.ctrlKey || event.metaKey) && !event.altKey) {
        const letter = event.key.toLowerCase();
        if (letter === 'c' && this.selected.length) {
          event.preventDefault();
          return this.copySelection();
        }
        if (letter === 'x' && this.selected.length) {
          event.preventDefault();
          return this.cutSelection();
        }
        if (letter === 'v' && this.clipboard) {
          event.preventDefault();
          return this.paste();
        }
      }

      switch (event.key) {
        case 'ArrowDown': return vertical(1);
        case 'ArrowUp': return vertical(-1);
        case 'ArrowRight': if (this.view === 'grid') return move(1); break;
        case 'ArrowLeft': if (this.view === 'grid') return move(-1); break;
        case 'Home': return move(-entries.length);
        case 'End': return move(entries.length);
        case 'Enter':
          if (this.focused) {
            event.preventDefault();
            this.open(this.focused);
          }
          return;
        case 'Backspace':
          event.preventDefault();
          this.goUp();
          return;
        case ' ':
          if (this.focused) {
            event.preventDefault();
            // Quick look on a file (Finder, Drive); Ctrl/⌘+Space, or a folder, ticks it.
            if (this.preview && !this.focused.isFolder && !(event.ctrlKey || event.metaKey)) this.openPreview(this.focused);
            else this.toggle(this.focused);
          }
          return;
        case 'Escape':
          if (this.menu.open) {
            this.closeMenu();
            return;
          }
          if (this.clipboard?.mode === 'cut') this.clipboard = null;
          this.clearSelection();
          return;
        case 'a':
          if (event.metaKey || event.ctrlKey) {
            event.preventDefault();
            this.selected = entries.map((entry) => entry.id);
            this.emitSelection();
          }
          return;
        case 'Delete':
          if (this.selected.length > 0) {
            event.preventDefault();
            this.action('delete');
          }
          return;
        case 'F2':
          if (this.focused && this.selected.length <= 1) {
            event.preventDefault();
            this.startRename(this.focused);
          }
          return;
      }
    },

    columnCount(ref: 'grid' | 'folderGrid' = 'grid'): number {
      const grid = (this as any).$refs?.[ref] as HTMLElement | undefined;
      if (!grid) return 1;

      const styles = getComputedStyle(grid).gridTemplateColumns;

      return Math.max(1, styles.split(' ').filter(Boolean).length);
    },

    focusEntry(entry: FileManagerEntry, extend = false) {
      if (!entry) return;

      this.focusId = entry.id;

      if (this.selectable) {
        if (extend && this.multiple) {
          if (!this.isSelected(entry.id)) this.selected = [...this.selected, entry.id];
        } else {
          this.selected = [entry.id];
          this._lastIndex = this.entries.findIndex((candidate) => candidate.id === entry.id);
        }
        this.emitSelection();
      }

      (this as any).$nextTick(() => {
        const node = this.rootElement().querySelector(`[data-entry="${CSS.escape(entry.id)}"]`) as HTMLElement | null;
        node?.scrollIntoView({ block: 'nearest' });
      });
    },

    /* ---------------------------------------------------------------- */
    /* Sorting & view                                                    */
    /* ---------------------------------------------------------------- */

    sortBy(column: FileManagerSort) {
      if (this.sort === column) {
        if (column === 'manual') return;
        this.direction = this.direction === 'asc' ? 'desc' : 'asc';
      } else {
        this.sort = column;
        this.direction = 'asc';
      }

      this.emit('sort', { sort: this.sort, direction: this.direction });
    },

    setView(view: 'list' | 'grid') {
      if (this.view === view) return;

      this.view = view;
      this.emit('view', { view });
    },

    /* ---------------------------------------------------------------- */
    /* Drag & drop (reorder + move into folder)                          */
    /* ---------------------------------------------------------------- */

    onItemDragStart(event: DragEvent, entry: FileManagerEntry) {
      if (!this.canSortEntries) {
        event.preventDefault();
        return;
      }

      const ids = this.isSelected(entry.id) && this.selected.length > 1
        ? [...this.selected]
        : [entry.id];

      this.drag = { active: true, ids, target: null, position: null };
      this.focusId = entry.id;

      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData(INTERNAL_DRAG_MIME, ids.join(','));
        event.dataTransfer.setData('text/plain', ids.join(','));
      }
    },

    onItemDragEnd() {
      this.drag = { active: false, ids: [], target: null, position: null };
    },

    onItemDragOver(event: DragEvent, entry: FileManagerEntry) {
      if (!this.drag.active || this.drag.ids.includes(entry.id)) return;

      event.preventDefault();
      event.stopPropagation();

      if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';

      const target = event.currentTarget as HTMLElement;
      const rect = target.getBoundingClientRect();
      const xRatio = (event.clientX - rect.left) / Math.max(rect.width, 1);
      const yRatio = (event.clientY - rect.top) / Math.max(rect.height, 1);

      let position: FileManagerDropPosition;

      if (this.view === 'grid') {
        // Icon view: dropping on a folder means "move inside" (Finder / Explorer).
        // Only a thin horizontal edge reorders as a sibling.
        if (entry.isFolder) {
          if (xRatio < 0.12) position = 'before';
          else if (xRatio > 0.88) position = 'after';
          else position = 'inside';
        } else {
          position = xRatio < 0.5 ? 'before' : 'after';
        }
      } else if (entry.isFolder) {
        if (yRatio < 0.25) position = 'before';
        else if (yRatio > 0.75) position = 'after';
        else position = 'inside';
      } else {
        position = yRatio < 0.5 ? 'before' : 'after';
      }

      // Never nest a folder inside itself or one of its descendants.
      if (position === 'inside' && this.drag.ids.some((id) => this.isSelfOrDescendant(id, entry.id))) {
        position = this.view === 'grid'
          ? (xRatio < 0.5 ? 'before' : 'after')
          : (yRatio < 0.5 ? 'before' : 'after');
      }

      if (this.drag.target !== entry.id || this.drag.position !== position) {
        this.drag = { ...this.drag, target: entry.id, position };
      }
    },

    onItemDragLeave(event: DragEvent, entry: FileManagerEntry) {
      const target = event.currentTarget as HTMLElement;
      if (target.contains(event.relatedTarget as Node)) return;
      if (this.drag.target === entry.id) {
        this.drag = { ...this.drag, target: null, position: null };
      }
    },

    onItemDrop(event: DragEvent, entry: FileManagerEntry) {
      event.preventDefault();
      event.stopPropagation();

      if (!this.drag.active || this.drag.ids.includes(entry.id) || !this.drag.position) {
        this.onItemDragEnd();
        return;
      }

      const ids = [...this.drag.ids];
      const position = this.drag.position;
      const targetId = entry.id;

      this.applyMove(ids, targetId, position);
      this.onItemDragEnd();
    },

    /**
     * Move `ids` relative to `targetId` inside the open folder (or into it).
     * Switches to manual sort on sibling reorder so the new order sticks.
     */
    applyMove(ids: string[], targetId: string, position: FileManagerDropPosition) {
      const siblings = this.currentNodes;
      const moving = ids
        .map((id) => siblings.find((node) => String(node.id) === id))
        .filter(Boolean) as FileManagerNode[];

      if (moving.length === 0) return;

      // Guard: cannot move a folder into itself / descendant (cross-folder path).
      if (position === 'inside' && ids.some((id) => this.isSelfOrDescendant(id, targetId))) {
        return;
      }

      const movingIds = new Set(moving.map((node) => String(node.id)));
      const remainder = siblings.filter((node) => !movingIds.has(String(node.id)));
      const targetIndex = remainder.findIndex((node) => String(node.id) === targetId);
      const target = siblings.find((node) => String(node.id) === targetId);

      if (position === 'inside' && target && (target.type === 'folder' || Array.isArray(target.children))) {
        // Detach from current folder, then append inside the target.
        this.replaceCurrentNodes(remainder);
        if (!target.children) target.children = [];
        target.children = [...target.children, ...moving];
        this.tree = [...this.tree];

        this.announce(t('itemsMoved', '{count} moved', { count: String(moving.length) }));
        this.emit('move', {
          items: moving,
          target: this.raw(decorate(target, this.locale)),
          position: 'inside',
          path: [...this.path],
        });
        this.selected = moving.map((node) => String(node.id));
        this.emitSelection();
        return;
      }

      if (targetIndex === -1) return;

      const insertAt = position === 'after' ? targetIndex + 1 : targetIndex;
      const next = [
        ...remainder.slice(0, insertAt),
        ...moving,
        ...remainder.slice(insertAt),
      ];

      this.replaceCurrentNodes(next);

      if (this.sort !== 'manual') {
        this.sort = 'manual';
        this.emit('sort', { sort: this.sort, direction: this.direction });
      }

      this.announce(t('itemsReordered', '{count} reordered', { count: String(moving.length) }));
      this.emit('reorder', {
        items: moving,
        order: next.map((node) => String(node.id)),
        target: target ? this.raw(decorate(target, this.locale)) : null,
        position,
        path: [...this.path],
      });
      this.selected = moving.map((node) => String(node.id));
      this.emitSelection();
    },

    /** Write the open folder's children list back into the tree. */
    replaceCurrentNodes(nodes: FileManagerNode[]) {
      if (this.path.length === 0) {
        this.tree = nodes;
        return;
      }

      let level = this.tree;
      for (let i = 0; i < this.path.length; i++) {
        const folder = level.find((node) => String(node.id) === this.path[i]);
        if (!folder) return;

        if (i === this.path.length - 1) {
          folder.children = nodes;
          // Bump the root array so Alpine re-evaluates nested getters.
          this.tree = [...this.tree];
          return;
        }

        level = folder.children ?? [];
      }
    },

    /** True when `candidateId` is `folderId` or lives under it. */
    isSelfOrDescendant(folderId: string, candidateId: string): boolean {
      if (folderId === candidateId) return true;

      const folder = this.findNode(this.tree, folderId);
      if (!folder?.children) return false;

      const stack = [...folder.children];
      while (stack.length) {
        const node = stack.pop()!;
        if (String(node.id) === candidateId) return true;
        if (node.children?.length) stack.push(...node.children);
      }

      return false;
    },

    findNode(nodes: FileManagerNode[], id: string): FileManagerNode | null {
      for (const node of nodes) {
        if (String(node.id) === id) return node;
        if (node.children?.length) {
          const found = this.findNode(node.children, id);
          if (found) return found;
        }
      }

      return null;
    },

    /* ---------------------------------------------------------------- */
    /* Actions & drop target                                             */
    /* ---------------------------------------------------------------- */

    raw(entry: FileManagerEntry): FileManagerNode {
      const {
        isFolder, kind, extension, sizeLabel, modifiedLabel, modifiedAt, count, pathIds, pathNames, ...node
      } = entry;

      return node as FileManagerNode;
    },

    action(name: string, entry?: FileManagerEntry) {
      const items = entry
        ? [this.raw(entry)]
        : this.selectedEntries.map((selected) => this.raw(selected));

      this.emit(name, { items, path: [...this.path] });
    },

    handleDragEnter(event: DragEvent) {
      // Internal sortable drags must not open the upload overlay.
      if (this.drag.active) return;
      if (event.dataTransfer?.types?.includes(INTERNAL_DRAG_MIME)) return;
      if (!Array.prototype.includes.call(event.dataTransfer?.types ?? [], 'Files')) return;

      this._dragDepth++;
      this.dropping = true;
    },

    handleDragLeave() {
      this._dragDepth = Math.max(0, this._dragDepth - 1);
      if (this._dragDepth === 0) this.dropping = false;
    },

    handleDrop(event: DragEvent) {
      this._dragDepth = 0;
      this.dropping = false;

      if (this.drag.active || event.dataTransfer?.types?.includes(INTERNAL_DRAG_MIME)) {
        return;
      }

      const files = Array.from(event.dataTransfer?.files ?? []);
      if (files.length === 0) return;

      this.startUploads(files);
    },

    emit(name: string, detail: Record<string, unknown>) {
      (this as any).$dispatch?.(`file-manager:${name}`, detail);
    },

    announce(message: string) {
      this.announcement = message;
    },
  };
}

if (typeof window !== 'undefined') {
  (window as any).neuraFileManager = neuraFileManager;
}
