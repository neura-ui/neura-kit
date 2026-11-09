/**
 * Where versions are kept.
 *
 * By default in the browser (IndexedDB), under a key for the document — enough
 * to go back in time on this device without any server. With `versions-url`
 * the app keeps them instead, through a small JSON API:
 *
 *   GET    {url}?key=…        → [{ id, time, name, authors }]
 *   GET    {url}/{id}?key=…   → { id, time, name, authors, html }
 *   POST   {url}              { key, time, name, authors, html } → { id, … }
 *   PUT    {url}/{id}         { key, time, authors, html }   (the last version, updated)
 *   PATCH  {url}/{id}         { key, name }                  (a version named)
 */

import { prune, type VersionMeta } from './versioning';

export interface Version extends VersionMeta {
  html: string;
}

export interface VersionStore {
  /** Newest first, without the content. */
  list(): Promise<VersionMeta[]>;
  get(id: string): Promise<Version | null>;
  add(version: Omit<Version, 'id'>): Promise<VersionMeta>;
  update(id: string, patch: Partial<Omit<Version, 'id'>>): Promise<void>;
}

let counter = 0;
const newId = (): string => `v-${Date.now().toString(36)}${(counter++).toString(36)}`;
const meta = ({ id, time, name, authors }: Version): VersionMeta => ({ id, time, name, authors });
const newestFirst = <T extends VersionMeta>(versions: T[]): T[] => versions.slice().sort((a, b) => Date.parse(b.time) - Date.parse(a.time));

const DB = 'neura-editor-history';
const TABLE = 'documents';

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB, 1);
    request.onupgradeneeded = () => request.result.createObjectStore(TABLE, { keyPath: 'key' });
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/** Versions in this browser; in memory only where IndexedDB is not available (private mode). */
export function browserStore(key: string, limit: number): VersionStore {
  let memory: Version[] = [];
  let db: Promise<IDBDatabase | null> | null = null;
  const database = () => (db ??= typeof indexedDB === 'undefined' ? Promise.resolve(null) : openDb().catch(() => null));

  const read = async (): Promise<Version[]> => {
    const handle = await database();
    if (!handle) return memory;
    return new Promise((resolve) => {
      const request = handle.transaction(TABLE, 'readonly').objectStore(TABLE).get(key);
      request.onsuccess = () => resolve(Array.isArray(request.result?.versions) ? request.result.versions : []);
      request.onerror = () => resolve(memory);
    });
  };

  const write = async (versions: Version[]): Promise<void> => {
    memory = prune(newestFirst(versions), limit);
    const handle = await database();
    if (!handle) return;
    await new Promise<void>((resolve) => {
      const transaction = handle.transaction(TABLE, 'readwrite');
      transaction.objectStore(TABLE).put({ key, versions: memory });
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => resolve();
      transaction.onabort = () => resolve();
    });
  };

  // One write at a time, so two quick snapshots never read the same list.
  let queue: Promise<unknown> = Promise.resolve();
  const serial = <T>(work: () => Promise<T>): Promise<T> => {
    const next = queue.then(work, work);
    queue = next.catch(() => undefined);
    return next;
  };

  return {
    list: () => serial(async () => newestFirst(await read()).map(meta)),
    get: (id) => serial(async () => (await read()).find((version) => version.id === id) ?? null),
    add: (version) =>
      serial(async () => {
        const created = { ...version, id: newId() };
        await write([created, ...(await read())]);
        return meta(created);
      }),
    update: (id, patch) =>
      serial(async () => {
        await write((await read()).map((version) => (version.id === id ? { ...version, ...patch } : version)));
      }),
  };
}

/** Versions kept by the app, at `url`. */
export function remoteStore(url: string, key: string, token: () => string | null): VersionStore {
  const at = (id?: string) => `${url.replace(/\/$/, '')}${id ? `/${encodeURIComponent(id)}` : ''}`;
  const call = async (method: string, target: string, body?: unknown): Promise<unknown> => {
    const csrf = token();
    const response = await fetch(method === 'GET' ? `${target}${target.includes('?') ? '&' : '?'}key=${encodeURIComponent(key)}` : target, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: body ? JSON.stringify({ key, ...body }) : undefined,
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const type = response.headers.get('content-type') ?? '';
    return type.includes('json') ? response.json() : null;
  };
  const clean = (value: unknown): VersionMeta | null => {
    if (!value || typeof value !== 'object') return null;
    const raw = value as Record<string, unknown>;
    if (raw.id === undefined || raw.id === null) return null;
    return {
      id: String(raw.id),
      time: typeof raw.time === 'string' ? raw.time : new Date().toISOString(),
      name: typeof raw.name === 'string' ? raw.name : '',
      authors: Array.isArray(raw.authors) ? raw.authors.filter((name): name is string => typeof name === 'string') : [],
    };
  };

  return {
    list: async () => {
      const payload = await call('GET', at());
      const list = Array.isArray(payload) ? payload : Array.isArray((payload as { data?: unknown })?.data) ? (payload as { data: unknown[] }).data : [];
      return newestFirst(list.map(clean).filter((version): version is VersionMeta => version !== null));
    },
    get: async (id) => {
      const payload = (await call('GET', at(id))) as Record<string, unknown> | null;
      const head = clean(payload);
      return head && typeof payload?.html === 'string' ? { ...head, html: payload.html } : null;
    },
    add: async (version) => clean(await call('POST', at(), version)) ?? meta({ ...version, id: newId() }),
    update: async (id, patch) => {
      await call(patch.name !== undefined && patch.html === undefined ? 'PATCH' : 'PUT', at(id), patch);
    },
  };
}
