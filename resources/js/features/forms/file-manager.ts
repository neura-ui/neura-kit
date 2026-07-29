export type FileManagerKind =
  | 'folder' | 'image' | 'video' | 'audio' | 'pdf' | 'sheet' | 'doc' | 'archive' | 'code' | 'text' | 'file';

export type FileManagerNode = {
  id: string;
  name: string;
  type?: 'folder' | 'file';
  size?: number;
  modified?: string | number;
  mime?: string;
  url?: string;
  thumbnail?: string;
  children?: FileManagerNode[];
  [key: string]: any;
};

export type FileManagerEntry = FileManagerNode & {
  id: string;
  name: string;
  isFolder: boolean;
  kind: FileManagerKind;
  extension: string;
  sizeLabel: string;
  modifiedLabel: string;
  modifiedAt: number;
  count: number;
  /** Folder chain holding this entry — only filled by a whole-tree search. */
  pathIds?: string[];
  pathNames?: string[];
};

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
  path: [],
  locale: undefined,
};

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

function formatSize(bytes?: number): string {
  if (bytes === undefined || bytes === null || bytes < 0) return '—';
  if (bytes === 0) return '0 B';

  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
  const value = bytes / Math.pow(1024, i);

  return `${value >= 100 || i === 0 ? Math.round(value) : Math.round(value * 10) / 10} ${units[i]}`;
}

function toTimestamp(value?: string | number): number {
  if (value === undefined || value === null || value === '') return 0;
  if (typeof value === 'number') return value < 1e12 ? value * 1000 : value;

  const parsed = Date.parse(value);

  return Number.isNaN(parsed) ? 0 : parsed;
}

function formatDate(value: number, locale?: string): string {
  if (!value) return '—';

  const date = new Date(value);
  const now = Date.now();
  const diff = now - value;

  if (diff >= 0 && diff < 60_000) return t('justNow', 'Just now');

  const sameYear = new Date(now).getFullYear() === date.getFullYear();

  return date.toLocaleDateString(locale || undefined, {
    day: 'numeric',
    month: 'short',
    year: sameYear ? undefined : 'numeric',
  }) + ', ' + date.toLocaleTimeString(locale || undefined, { hour: '2-digit', minute: '2-digit' });
}

const KIND_BY_EXTENSION: Record<string, FileManagerKind> = {
  jpg: 'image', jpeg: 'image', png: 'image', gif: 'image', webp: 'image', avif: 'image',
  svg: 'image', bmp: 'image', heic: 'image', ico: 'image', tif: 'image', tiff: 'image',
  mp4: 'video', webm: 'video', mov: 'video', avi: 'video', mkv: 'video',
  mp3: 'audio', wav: 'audio', ogg: 'audio', flac: 'audio', m4a: 'audio',
  pdf: 'pdf',
  xls: 'sheet', xlsx: 'sheet', csv: 'sheet', numbers: 'sheet',
  doc: 'doc', docx: 'doc', rtf: 'doc', odt: 'doc', pages: 'doc',
  zip: 'archive', rar: 'archive', '7z': 'archive', tar: 'archive', gz: 'archive',
  js: 'code', ts: 'code', json: 'code', php: 'code', html: 'code', css: 'code',
  vue: 'code', blade: 'code', py: 'code', rb: 'code', go: 'code', sh: 'code',
  txt: 'text', md: 'text', log: 'text',
};

function extensionOf(name: string): string {
  const parts = name.split('.');

  return parts.length > 1 ? (parts.pop() || '').toLowerCase() : '';
}

function detectKind(node: FileManagerNode): FileManagerKind {
  if (node.type === 'folder' || Array.isArray(node.children)) return 'folder';

  const mime = (node.mime || '').toLowerCase();
  if (mime.startsWith('image/')) return 'image';
  if (mime.startsWith('video/')) return 'video';
  if (mime.startsWith('audio/')) return 'audio';
  if (mime === 'application/pdf') return 'pdf';

  return KIND_BY_EXTENSION[extensionOf(node.name)] ?? 'file';
}

/** Icon name resolved against the Heroicons set used by <neura::icon>. */
const ICONS: Record<FileManagerKind, string> = {
  folder: 'folder',
  image: 'photo',
  video: 'film',
  audio: 'musical-note',
  pdf: 'document-text',
  sheet: 'table-cells',
  doc: 'document-text',
  archive: 'archive-box',
  code: 'code-bracket',
  text: 'document-text',
  file: 'document',
};

function decorate(node: FileManagerNode, locale?: string): FileManagerEntry {
  const kind = detectKind(node);
  const isFolder = kind === 'folder';
  const modifiedAt = toTimestamp(node.modified);

  return {
    ...node,
    id: String(node.id ?? node.name),
    name: node.name,
    isFolder,
    kind,
    extension: isFolder ? '' : extensionOf(node.name).toUpperCase(),
    sizeLabel: isFolder
      ? t('itemsCount', '{count} items', { count: String(node.children?.length ?? 0) })
      : formatSize(node.size),
    modifiedAt,
    modifiedLabel: formatDate(modifiedAt, locale),
    count: node.children?.length ?? 0,
  };
}

export function neuraFileManager(options: FileManagerOptions = {}) {
  const config = { ...defaults, ...options };

  return {
    // State
    tree: config.items as FileManagerNode[],
    path: [...config.path] as string[],
    view: config.view as 'list' | 'grid',
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

    destroy() {
      clearTimeout(this._searchTimer);
    },

    /* ---------------------------------------------------------------- */
    /* Derived state                                                     */
    /* ---------------------------------------------------------------- */

    /** Folder nodes matching the current path, from the root down. */
    get trail(): Array<{ id: string | null; name: string }> {
      const crumbs: Array<{ id: string | null; name: string }> = [
        { id: null, name: this.rootLabel },
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

    /** Decorated, filtered and sorted entries shown in the body. */
    get entries(): FileManagerEntry[] {
      const query = this.query;
      let list: FileManagerEntry[];

      if (this.isGlobalSearch) {
        const all: FileManagerEntry[] = [];
        this._flatten(this.tree, [], all);
        list = all.filter((entry) => this.matches(entry, query));
      } else {
        list = this.currentNodes.map((node) => decorate(node, this.locale));

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

      this.action(name, this.selected.length > 1 ? undefined : entry ?? undefined);
    },

    /* ---------------------------------------------------------------- */
    /* Navigation                                                        */
    /* ---------------------------------------------------------------- */

    open(entry: FileManagerEntry) {
      // A whole-tree result lives somewhere else: move there first.
      if (entry.pathIds && entry.pathIds.join('/') !== this.path.join('/')) {
        this.path = [...entry.pathIds];
      }

      if (entry.isFolder) {
        this.enter(entry.id);
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
      const entries = this.entries;
      if (entries.length === 0) return;

      const columns = this.view === 'grid' ? this.columnCount() : 1;
      const current = entries.findIndex((entry) => entry.id === this.focusId);

      const move = (delta: number) => {
        event.preventDefault();
        const next = current === -1
          ? 0
          : Math.min(entries.length - 1, Math.max(0, current + delta));
        this.focusEntry(entries[next], event.shiftKey);
      };

      switch (event.key) {
        case 'ArrowDown': return move(columns);
        case 'ArrowUp': return move(-columns);
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
            this.toggle(this.focused);
          }
          return;
        case 'Escape':
          if (this.menu.open) {
            this.closeMenu();
            return;
          }
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
      }
    },

    columnCount(): number {
      const grid = (this as any).$refs?.grid as HTMLElement | undefined;
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

      this.announce(t('filesAdded', '{count} file(s) added', { count: String(files.length) }));
      this.emit('upload', { files, path: [...this.path] });
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
