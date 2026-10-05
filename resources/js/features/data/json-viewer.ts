import { onAlpineInit } from '../../runtime/alpine';

/**
 * JSON viewer.
 *
 * A collapsible tree for a JSON value: search opens the branches that hold a
 * match, long arrays page, and each row can copy its value or its path.
 */

type JsonType = 'object' | 'array' | 'string' | 'number' | 'boolean' | 'null';

interface JsonNode {
    key: string;
    path: string;
    type: JsonType;
    preview: string;
    title: string;
    meta: string;
    raw: unknown;
    children: JsonNode[];
}

interface JsonRow {
    id: string;
    path: string;
    key: string;
    type: JsonType;
    preview: string;
    title: string;
    meta: string;
    level: number;
    branch: boolean;
    open: boolean;
    more: boolean;
    pos: number;
    size: number;
}

interface Phrases {
    search: string;
    empty: string;
    more: string;
    copyValue: string;
    copyPath: string;
    copied: string;
    failed: string;
    keys: string;
    key: string;
    items: string;
    item: string;
}

interface JsonViewerConfig {
    data?: unknown;
    rootName?: string;
    defaultExpandDepth?: number;
    searchable?: boolean;
    pageSize?: number;
    copyable?: boolean;
    showPath?: boolean;
    maxHeight?: number;
    label?: string;
    phrases?: Partial<Phrases>;
}

const PHRASES: Phrases = {
    search: 'Search',
    empty: 'No matches',
    more: 'Show more',
    copyValue: 'Copy value',
    copyPath: 'Copy path',
    copied: 'Copied',
    failed: 'Copy failed',
    keys: 'keys',
    key: 'key',
    items: 'items',
    item: 'item',
};

function kindOf(value: unknown): JsonType {
    if (value === null) return 'null';
    if (Array.isArray(value)) return 'array';
    switch (typeof value) {
        case 'string':
            return 'string';
        case 'number':
            return 'number';
        case 'boolean':
            return 'boolean';
        default:
            return 'object';
    }
}

function previewOf(value: unknown, type: JsonType): { preview: string; title: string } {
    if (type === 'string') {
        const text = String(value);
        const shown = text.length > 40 ? `${text.slice(0, 40)}…` : text;
        return { preview: `"${shown}"`, title: text };
    }
    if (type === 'number') return { preview: String(value), title: String(value) };
    if (type === 'boolean') return { preview: value ? 'true' : 'false', title: value ? 'true' : 'false' };
    if (type === 'null') return { preview: 'null', title: 'null' };
    if (type === 'array') return { preview: '[…]', title: '' };
    return { preview: '{…}', title: '' };
}

function build(value: unknown, key: string, path: string, phrases: Phrases): JsonNode {
    const type = kindOf(value);
    const shown = previewOf(value, type);
    const children: JsonNode[] = [];

    if (type === 'array') {
        (value as unknown[]).forEach((item, index) => {
            children.push(build(item, String(index), `${path}[${index}]`, phrases));
        });
    } else if (type === 'object' && value && typeof value === 'object') {
        Object.entries(value as Record<string, unknown>).forEach(([childKey, child]) => {
            const childPath = path ? `${path}.${childKey}` : childKey;
            children.push(build(child, childKey, childPath, phrases));
        });
    }

    const count = children.length;
    const meta = type === 'array'
        ? `${count} ${count === 1 ? phrases.item : phrases.items}`
        : type === 'object'
            ? `${count} ${count === 1 ? phrases.key : phrases.keys}`
            : '';

    return { key, path, type, preview: shown.preview, title: shown.title, meta, raw: value, children };
}

function ancestors(path: string): string[] {
    const list: string[] = [];
    let current = path;
    while (current.includes('.') || current.includes('[')) {
        current = current.endsWith(']')
            ? current.slice(0, current.lastIndexOf('['))
            : current.slice(0, current.lastIndexOf('.'));
        if (current) list.push(current);
    }
    return list;
}

onAlpineInit(() => {
    window.Alpine.data('neuraJsonViewer', (config: JsonViewerConfig = {}) => ({
        rootName: config.rootName || 'root',
        searchable: config.searchable !== false,
        pageSize: Math.max(1, config.pageSize ?? 50),
        copyable: config.copyable !== false,
        showPath: config.showPath !== false,
        maxHeight: config.maxHeight ?? 420,
        label: config.label || 'JSON',
        phrases: { ...PHRASES, ...(config.phrases ?? {}) },
        query: '',
        selected: '',
        copied: '',
        status: '',
        hitCursor: 0,
        hits: [] as string[],
        open: {} as Record<string, boolean>,
        pages: {} as Record<string, number>,
        highlight: 'opacity: 0',
        root: null as JsonNode | null,
        lookup: {} as Record<string, JsonNode>,

        init(): void {
            const phrases = this.phrases;
            this.root = build(config.data ?? null, this.rootName, this.rootName, phrases);
            this.index(this.root);
            this.seedOpen(this.root, 0, config.defaultExpandDepth ?? 1);
            this.selected = this.root.path;
            this.$watch('query', () => {
                this.reindex();
                this.hitCursor = 0;
                if (this.hits.length) this.selected = this.hits[0];
                this.queueFrame();
            });
            this.queueFrame();
        },

        index(node: JsonNode): void {
            this.lookup[node.path] = node;
            node.children.forEach((child) => this.index(child));
        },

        seedOpen(node: JsonNode, depth: number, limit: number): void {
            if (depth < limit && node.children.length) this.open[node.path] = true;
            if (depth + 1 < limit) node.children.forEach((child) => this.seedOpen(child, depth + 1, limit));
        },

        textHits(node: JsonNode, query: string): boolean {
            if (node.key.toLowerCase().includes(query)) return true;
            if (node.type === 'object' || node.type === 'array') return false;
            return node.preview.toLowerCase().includes(query) || node.title.toLowerCase().includes(query);
        },

        reindex(): void {
            const query = this.query.trim().toLowerCase();
            const hits: string[] = [];
            if (query && this.root) {
                const walk = (node: JsonNode) => {
                    if (hits.length >= 2000) return;
                    if (this.textHits(node, query)) hits.push(node.path);
                    node.children.forEach(walk);
                };
                walk(this.root);
            }
            this.hits = hits;
        },

        hitInside(node: JsonNode): boolean {
            const dot = `${node.path}.`;
            const bracket = `${node.path}[`;
            return this.hits.some((path) => path.startsWith(dot) || path.startsWith(bracket));
        },

        isOpen(node: JsonNode): boolean {
            const query = this.query.trim();
            if (query && (this.hits.includes(node.path) || this.hitInside(node))) return true;
            return Boolean(this.open[node.path]);
        },

        get visible(): JsonRow[] {
            if (!this.root) return [];
            const rows: JsonRow[] = [];
            this.flatten(this.root, 1, false, rows);
            return rows;
        },

        flatten(node: JsonNode, level: number, parentMatched: boolean, rows: JsonRow[]): void {
            const query = this.query.trim().toLowerCase();
            const selfHit = Boolean(query) && this.hits.includes(node.path);
            const inside = Boolean(query) && this.hitInside(node);
            if (query && !selfHit && !inside && !parentMatched && node !== this.root) return;

            const branch = node.children.length > 0;
            const open = branch && this.isOpen(node);
            rows.push({
                id: `${node.path}`,
                path: node.path,
                key: node.key,
                type: node.type,
                preview: node.preview,
                title: node.title,
                meta: node.meta,
                level,
                branch,
                open,
                more: false,
                pos: 0,
                size: 0,
            });

            if (!open) return;

            const keyMatched = selfHit && (node.type === 'object' || node.type === 'array');
            const eligible = node.children.filter((child) => {
                if (!query || keyMatched || parentMatched) return true;
                return this.hits.includes(child.path) || this.hitInside(child);
            });
            const limit = (this.pages[node.path] || 1) * this.pageSize;
            const shown = eligible.slice(0, limit);
            shown.forEach((child, index) => {
                const before = rows.length;
                this.flatten(child, level + 1, keyMatched || parentMatched, rows);
                if (rows[before]) {
                    rows[before].pos = index + 1;
                    rows[before].size = shown.length;
                }
            });
            if (eligible.length > shown.length) {
                rows.push({
                    id: `${node.path}::more`,
                    path: `${node.path}::more`,
                    key: '',
                    type: 'null',
                    preview: '',
                    title: '',
                    meta: '',
                    level: level + 1,
                    branch: false,
                    open: false,
                    more: true,
                    pos: shown.length + 1,
                    size: shown.length + 1,
                });
            }
        },

        get matchLabel(): string {
            if (!this.query.trim()) return '';
            if (!this.hits.length) return this.phrases.empty;
            return `${this.hitCursor + 1} / ${this.hits.length}`;
        },

        get selectedNode(): JsonNode | null {
            return this.lookup[this.selected] ?? null;
        },

        get selectedType(): string {
            return this.selectedNode?.type ?? '';
        },

        select(row: JsonRow): void {
            if (row.more) {
                this.showMore(row);
                return;
            }
            this.selected = row.path;
            const at = this.hits.indexOf(row.path);
            if (at >= 0) this.hitCursor = at;
            (this.$refs.tree as HTMLElement | undefined)?.focus({ preventScroll: true });
            this.queueFrame();
        },

        toggle(row: JsonRow): void {
            if (!row.branch) return;
            const node = this.lookup[row.path];
            if (!node) return;
            this.open[row.path] = !this.isOpen(node);
            this.selected = row.path;
            (this.$refs.tree as HTMLElement | undefined)?.focus({ preventScroll: true });
            this.queueFrame();
        },

        showMore(row: JsonRow): void {
            const path = row.path.replace(/::more$/, '');
            this.pages[path] = (this.pages[path] || 1) + 1;
            this.selected = path;
            this.queueFrame();
        },

        queueFrame(): void {
            this.$nextTick(() => {
                this.placeHighlight();
                const tree = this.$refs.tree as HTMLElement | undefined;
                const row = tree?.querySelector('[aria-selected="true"]') as HTMLElement | null;
                if (!tree || !row) return;
                const top = row.offsetTop;
                const bottom = top + row.offsetHeight;
                if (top < tree.scrollTop) tree.scrollTop = top;
                else if (bottom > tree.scrollTop + tree.clientHeight) {
                    tree.scrollTop = bottom - tree.clientHeight;
                }
            });
        },

        placeHighlight(): void {
            const tree = this.$refs.tree as HTMLElement | undefined;
            const row = tree?.querySelector('[aria-selected="true"]') as HTMLElement | null;
            if (!tree || !row) {
                this.highlight = 'opacity: 0';
                return;
            }
            this.highlight = `transform: translateY(${row.offsetTop}px); height: ${row.offsetHeight}px; opacity: 1`;
        },

        async copy(row: JsonRow, kind: 'value' | 'path'): Promise<void> {
            const path = row.more ? row.path.replace(/::more$/, '') : row.path;
            const node = this.lookup[path];
            const text = kind === 'path'
                ? path
                : node?.type === 'string'
                    ? String(node.raw)
                    : JSON.stringify(node?.raw ?? null, null, 2);
            const clip = (window as unknown as { Clipboard?: { copy: (value: string) => Promise<boolean> } }).Clipboard;
            const ok = clip?.copy
                ? await clip.copy(text)
                : await navigator.clipboard.writeText(text).then(() => true, () => false);
            if (!ok) {
                this.status = this.phrases.failed;
                return;
            }
            this.copied = `${path}:${kind}`;
            this.status = this.phrases.copied;
            window.setTimeout(() => {
                if (this.copied === `${path}:${kind}`) this.copied = '';
            }, 1200);
        },

        copySelected(kind: 'value' | 'path'): void {
            const node = this.selectedNode;
            if (!node) return;
            this.copy({
                id: node.path,
                path: node.path,
                key: node.key,
                type: node.type,
                preview: node.preview,
                title: node.title,
                meta: node.meta,
                level: 1,
                branch: node.children.length > 0,
                open: false,
                more: false,
                pos: 1,
                size: 1,
            }, kind);
        },

        onKey(event: KeyboardEvent): void {
            const target = event.target as HTMLElement | null;
            if (target?.closest('input, textarea')) {
                this.onSearchKey(event);
                return;
            }
            if (event.key === '/') {
                event.preventDefault();
                (this.$refs.search as HTMLInputElement | undefined)?.focus();
                return;
            }
            this.onTreeKey(event);
        },

        onSearchKey(event: KeyboardEvent): void {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.stepHit(event.shiftKey ? -1 : 1);
            } else if (event.key === 'Escape') {
                this.query = '';
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                const first = this.visible[0];
                if (first) this.selected = first.path;
                (this.$refs.tree as HTMLElement | undefined)?.focus();
                this.queueFrame();
            }
        },

        stepHit(delta: number): void {
            if (!this.hits.length) return;
            this.hitCursor = (this.hitCursor + delta + this.hits.length) % this.hits.length;
            this.selected = this.hits[this.hitCursor];
            this.queueFrame();
        },

        onTreeKey(event: KeyboardEvent): void {
            const rows = this.visible;
            if (!rows.length) return;
            const index = Math.max(0, rows.findIndex((row) => row.path === this.selected));
            const current = rows[index];

            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'c') {
                if (window.getSelection()?.toString()) return;
                event.preventDefault();
                this.copySelected(event.shiftKey ? 'path' : 'value');
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.selected = rows[Math.min(rows.length - 1, index + 1)].path;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.selected = rows[Math.max(0, index - 1)].path;
            } else if (event.key === 'Home') {
                event.preventDefault();
                this.selected = rows[0].path;
            } else if (event.key === 'End') {
                event.preventDefault();
                this.selected = rows[rows.length - 1].path;
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                if (current?.branch && !current.open) this.toggle(current);
                else if (current?.branch && current.open) {
                    const next = rows[index + 1];
                    if (next && !next.more) this.selected = next.path;
                }
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                const node = current && !current.more ? this.lookup[current.path] : null;
                const forced = Boolean(node && this.query.trim() && (this.hits.includes(node.path) || this.hitInside(node)));
                if (current?.branch && current.open && !forced) this.toggle(current);
                else if (current) {
                    const parent = ancestors(current.more ? current.path.replace(/::more$/, '') : current.path)[0];
                    if (parent) this.selected = parent;
                }
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (current?.more) this.showMore(current);
                else if (current?.branch) this.toggle(current);
            } else {
                return;
            }
            this.queueFrame();
        },
    }));
});
