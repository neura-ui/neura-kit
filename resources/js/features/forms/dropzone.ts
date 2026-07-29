export type DropzoneStatus = 'idle' | 'uploading' | 'success' | 'error';

export type DropzoneKind = 'image' | 'video' | 'audio' | 'pdf' | 'archive' | 'text' | 'file';

export type UploadResult = {
  uuid?: string;
  filename?: string;
  path?: string;
  size?: number;
  mime?: string;
  [key: string]: any;
};

export type DropzoneItem = {
  uuid: string;
  key: string;
  kind: DropzoneKind;
  type: 'image' | 'file';
  url: string | null;
  name: string;
  bytes: number;
  size: string;
  extension: string;
  progress: number;
  status: DropzoneStatus;
  error: string | null;
  server: UploadResult | null;
  remaining: string;
};

export type DropzoneRejection = {
  id: string;
  name: string;
  message: string;
};

export type DropzoneOptions = {
  accept?: string;
  maxSizeBytes?: number;
  maxFiles?: number | null;
  multiple?: boolean;
  chunkSize?: number;
  uploadUrl?: string | null;
  uploadHeaders?: Record<string, string>;
  name?: string | null;
  invalid?: boolean;
  wireModel?: string | null;
  wireModelLive?: boolean;
  previewEnabled?: boolean;
  removable?: boolean;
  disabled?: boolean;
  concurrency?: number;
  autoUpload?: boolean;
  notify?: boolean;
  maxRetries?: number;
};

const defaultOptions: Required<DropzoneOptions> = {
  accept: 'image/*',
  maxSizeBytes: 10 * 1024 * 1024,
  maxFiles: null,
  multiple: false,
  chunkSize: 1024 * 1024,
  uploadUrl: null,
  uploadHeaders: {},
  name: null,
  invalid: false,
  wireModel: null,
  wireModelLive: false,
  previewEnabled: true,
  removable: true,
  disabled: false,
  concurrency: 2,
  autoUpload: true,
  notify: false,
  maxRetries: 2,
};

/* -------------------------------------------------------------------------- */
/* Helpers                                                                     */
/* -------------------------------------------------------------------------- */

/**
 * Translate a key, falling back to the provided default when the bundle has no
 * entry for it. Placeholders use the `{name}` syntax.
 */
function t(key: string, fallback: string, params?: Record<string, string>): string {
  const store = (window as any).NeuraKitTranslations;

  if (store?.translations?.[key]) {
    return store.t(key, params ?? {});
  }

  const global = (window as any).t;
  if (typeof global === 'function') {
    try {
      const value = global(key, params ?? {});
      if (value && value !== key) return value;
    } catch {
      // ignore
    }
  }

  let out = fallback;
  if (params) {
    for (const [name, value] of Object.entries(params)) {
      out = out.split(`{${name}}`).join(value);
    }
  }

  return out;
}

function uid(): string {
  const c = globalThis.crypto as any;
  if (c?.randomUUID) return c.randomUUID();

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const r = (Math.random() * 16) | 0;
    const v = char === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes < 0) return '0 B';

  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
  const value = bytes / Math.pow(1024, i);

  return `${value >= 100 || i === 0 ? Math.round(value) : Math.round(value * 10) / 10} ${units[i]}`;
}

function formatDuration(seconds: number): string {
  if (!isFinite(seconds) || seconds < 0) return '';
  if (seconds < 60) return `${Math.max(1, Math.round(seconds))}s`;

  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ${Math.round(seconds % 60)}s`;

  return `${Math.floor(minutes / 60)}h ${minutes % 60}m`;
}

function getExtension(name: string): string {
  const parts = name.split('.');
  if (parts.length < 2) return '';

  return (parts.pop() || '').trim().slice(0, 5).toUpperCase();
}

const EXTENSION_MIME: Record<string, string> = {
  jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', gif: 'image/gif',
  webp: 'image/webp', avif: 'image/avif', svg: 'image/svg+xml', heic: 'image/heic',
  heif: 'image/heif', bmp: 'image/bmp', ico: 'image/x-icon', tif: 'image/tiff', tiff: 'image/tiff',
  mp4: 'video/mp4', webm: 'video/webm', mov: 'video/quicktime', avi: 'video/x-msvideo', mkv: 'video/x-matroska',
  mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg', m4a: 'audio/mp4', flac: 'audio/flac',
  pdf: 'application/pdf', csv: 'text/csv', txt: 'text/plain', md: 'text/markdown',
  json: 'application/json', xml: 'application/xml',
  zip: 'application/zip', rar: 'application/vnd.rar', '7z': 'application/x-7z-compressed',
  gz: 'application/gzip', tar: 'application/x-tar',
};

const ARCHIVE_EXTENSIONS = ['zip', 'rar', '7z', 'gz', 'tar', 'bz2', 'xz'];

function mimeOf(file: File): string {
  const type = (file.type || '').toLowerCase();
  if (type) return type;

  const ext = (file.name.split('.').pop() || '').toLowerCase();

  return EXTENSION_MIME[ext] || '';
}

function detectKind(file: File): DropzoneKind {
  const mime = mimeOf(file);
  const ext = (file.name.split('.').pop() || '').toLowerCase();

  if (mime.startsWith('image/')) return 'image';
  if (mime.startsWith('video/')) return 'video';
  if (mime.startsWith('audio/')) return 'audio';
  if (mime === 'application/pdf') return 'pdf';
  if (ARCHIVE_EXTENSIONS.includes(ext)) return 'archive';
  if (mime.startsWith('text/') || mime === 'application/json' || mime === 'application/xml') return 'text';

  return 'file';
}

/**
 * Accept check supporting `image/*`, `application/pdf`, `.png` and any
 * comma separated combination of those. Files reported without a MIME type
 * (common for `.heic`, `.md`, …) fall back to an extension lookup.
 */
function matchesAccept(file: File, accept: string): boolean {
  if (!accept || accept === '*' || accept === '*/*') return true;

  const rules = accept.split(',').map((rule) => rule.trim().toLowerCase()).filter(Boolean);
  if (rules.length === 0) return true;

  const mime = mimeOf(file);
  const name = file.name.toLowerCase();

  return rules.some((rule) => {
    if (rule.startsWith('.')) return name.endsWith(rule);
    if (rule.endsWith('/*')) return mime.startsWith(rule.slice(0, -1));

    return mime === rule;
  });
}

class UploadError extends Error {
  constructor(message: string, readonly status = 0, readonly retryable = true) {
    super(message);
    this.name = 'UploadError';
  }
}

function abortError(): UploadError {
  return new UploadError(t('uploadCancelled', 'Upload cancelled'), 0, false);
}

const STATUS_MESSAGES: Record<number, [string, string]> = {
  400: ['badRequest', 'Bad request'],
  401: ['unauthorized', 'Unauthorized'],
  403: ['forbidden', 'Forbidden'],
  404: ['notFound', 'Not found'],
  413: ['fileTooLarge', 'File too large'],
  419: ['sessionExpired', 'Session expired, please reload the page'],
  422: ['invalidData', 'Invalid data'],
  429: ['tooManyRequests', 'Too many requests'],
  500: ['serverError', 'Server error'],
  502: ['badGateway', 'Bad gateway'],
  503: ['serviceUnavailable', 'Service unavailable'],
  504: ['gatewayTimeout', 'Gateway timeout'],
};

function extractErrorMessage(responseText: string, status: number): string {
  try {
    const json = JSON.parse(responseText);
    if (json.message) return String(json.message);
    if (json.error) return String(json.error);
    if (json.errors && typeof json.errors === 'object') {
      const first = Object.values(json.errors)[0];
      return String(Array.isArray(first) ? first[0] : first);
    }
  } catch {
    // not json
  }

  const text = (responseText || '').trim();

  if (text && !text.startsWith('<') && text.length < 240) return text;

  if (text.startsWith('<')) {
    const title = text.match(/<title>(.*?)<\/title>/i)?.[1];
    if (title) return title.trim();
  }

  const known = STATUS_MESSAGES[status];
  if (known) return t(known[0], known[1]);

  return t('uploadError', 'Upload error ({code})', { code: String(status) });
}

function isRetryableStatus(status: number): boolean {
  return status === 0 || status === 408 || status === 429 || status >= 500;
}

function delay(ms: number, signal: AbortSignal): Promise<void> {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      signal.removeEventListener('abort', onAbort);
      resolve();
    }, ms);

    const onAbort = () => {
      clearTimeout(timer);
      reject(abortError());
    };

    signal.addEventListener('abort', onAbort, { once: true });
  });
}

type ChunkOptions = {
  chunkSize: number;
  uploadUrl: string;
  headers: Record<string, string>;
  field?: string | null;
  signal: AbortSignal;
  maxRetries: number;
  onProgress: (loadedBytes: number) => void;
};

function sendChunk(body: FormData, options: ChunkOptions, onLoaded: (bytes: number) => void): Promise<any> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const onAbort = () => xhr.abort();

    xhr.open('POST', options.uploadUrl, true);
    xhr.responseType = 'text';

    for (const [key, value] of Object.entries(options.headers || {})) {
      try {
        xhr.setRequestHeader(key, value);
      } catch {
        // invalid header, skip
      }
    }

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) onLoaded(event.loaded);
    };

    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          resolve(JSON.parse(xhr.responseText));
        } catch {
          resolve({ success: true });
        }
        return;
      }

      reject(new UploadError(
        extractErrorMessage(xhr.responseText, xhr.status),
        xhr.status,
        isRetryableStatus(xhr.status),
      ));
    };

    xhr.onerror = () => reject(new UploadError(t('networkError', 'Network error - Unable to connect to server')));
    xhr.onabort = () => reject(abortError());
    xhr.onloadend = () => options.signal.removeEventListener('abort', onAbort);

    options.signal.addEventListener('abort', onAbort, { once: true });
    xhr.send(body);
  });
}

/**
 * Upload a file as a sequence of chunks, retrying transient failures only.
 * Progress is reported in bytes so the caller can derive speed and ETA.
 */
async function uploadInChunks(file: File, options: ChunkOptions): Promise<UploadResult> {
  const chunkSize = Math.max(1, options.chunkSize);
  const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));
  const fileUuid = uid();

  let lastResponse: any = null;
  let uploaded = 0;

  for (let index = 0; index < totalChunks; index++) {
    if (options.signal.aborted) throw abortError();

    const start = index * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    const base = uploaded;

    const body = new FormData();
    body.append('chunk', file.slice(start, end), file.name);
    body.append('fileName', file.name);
    body.append('fileSize', String(file.size));
    body.append('chunkIndex', String(index));
    body.append('totalChunks', String(totalChunks));
    body.append('uuid', fileUuid);
    if (options.field) body.append('field', options.field);

    let attempt = 0;

    for (;;) {
      try {
        lastResponse = await sendChunk(body, options, (loaded) => {
          options.onProgress(Math.min(file.size, base + loaded));
        });

        uploaded = end;
        options.onProgress(uploaded);
        break;
      } catch (error: any) {
        if (options.signal.aborted) throw abortError();

        const retryable = error instanceof UploadError ? error.retryable : true;
        if (!retryable || ++attempt > options.maxRetries) throw error;

        options.onProgress(base);
        await delay(Math.min(4000, 300 * 2 ** (attempt - 1)) + Math.random() * 200, options.signal);
      }
    }
  }

  if (lastResponse?.data && typeof lastResponse.data === 'object') return lastResponse.data;

  return lastResponse ?? { success: true };
}

function plain<T>(value: T): T {
  if (value === null || typeof value !== 'object') return value;

  try {
    return JSON.parse(JSON.stringify(value));
  } catch {
    return value;
  }
}

/* -------------------------------------------------------------------------- */
/* Component                                                                   */
/* -------------------------------------------------------------------------- */

export function neuraDropzone(options: DropzoneOptions = {}) {
  const config = { ...defaultOptions, ...options };

  return {
    // Public state
    previews: [] as DropzoneItem[],
    rejections: [] as DropzoneRejection[],
    announcement: '',
    serverInvalid: config.invalid,

    // Configuration (exposed so custom markup can read it)
    accept: config.accept,
    maxSize: config.maxSizeBytes,
    maxFiles: config.maxFiles,
    multiple: config.multiple,
    chunkSize: config.chunkSize,
    uploadUrl: config.uploadUrl,
    uploadHeaders: config.uploadHeaders,
    fieldName: config.name,
    wireModel: config.wireModel,
    wireModelLive: config.wireModelLive,
    previewEnabled: config.previewEnabled,
    removable: config.removable,
    disabled: config.disabled,
    autoUpload: config.autoUpload,
    notify: config.notify,
    concurrency: Math.max(1, config.concurrency || 1),
    maxRetries: Math.max(0, config.maxRetries),

    // Internals
    _files: new Map<string, File>(),
    _controllers: new Map<string, AbortController>(),
    _queue: [] as string[],
    _running: 0,
    _dragDepth: 0,
    _observer: null as MutationObserver | null,
    _syncScheduled: false,

    /* ---------------------------------------------------------------- */
    /* Derived state                                                     */
    /* ---------------------------------------------------------------- */

    get isDragging(): boolean {
      return this._dragDepth > 0;
    },

    get invalid(): boolean {
      return this.serverInvalid;
    },

    get hasError(): boolean {
      return this.serverInvalid
        || this.rejections.length > 0
        || this.previews.some((item) => item.status === 'error');
    },

    /** Single attribute driving every visual state of the drop area. */
    get state(): 'disabled' | 'dragging' | 'invalid' | 'idle' {
      if (this.disabled) return 'disabled';
      if (this.isDragging) return 'dragging';
      if (this.hasError) return 'invalid';

      return 'idle';
    },

    get isUploading(): boolean {
      return this.previews.some((item) => item.status === 'uploading');
    },

    get isFull(): boolean {
      if (this.disabled) return true;
      if (!this.multiple) return false;

      return this.maxFiles !== null && this.previews.length >= this.maxFiles;
    },

    get completedCount(): number {
      return this.previews.filter((item) => item.status === 'success').length;
    },

    /** Weighted progress across every file currently in the queue. */
    get overallProgress(): number {
      const items = this.previews.filter((item) => item.status !== 'error');
      if (items.length === 0) return 0;

      const total = items.reduce((sum, item) => sum + Math.max(1, item.bytes), 0);
      const done = items.reduce((sum, item) => sum + Math.max(1, item.bytes) * (item.progress / 100), 0);

      return Math.round((done / total) * 100);
    },

    get totalBytes(): number {
      return this.previews.reduce((sum, item) => sum + item.bytes, 0);
    },

    get totalSize(): string {
      return formatFileSize(this.totalBytes);
    },

    /* ---------------------------------------------------------------- */
    /* Lifecycle                                                         */
    /* ---------------------------------------------------------------- */

    init() {
      const flag = (this as any).$refs?.validity as HTMLElement | undefined;
      if (!flag) return;

      // Server rendered validation state. Livewire (or any morph based
      // library) rewrites this attribute on re-render, so observing it keeps
      // the component in sync without polling or patching $wire.
      this.serverInvalid = flag.dataset.invalid === '1';

      this._observer = new MutationObserver(() => {
        const next = flag.dataset.invalid === '1';
        if (next !== this.serverInvalid) this.serverInvalid = next;
      });

      this._observer.observe(flag, { attributes: true, attributeFilter: ['data-invalid'] });
    },

    destroy() {
      this._observer?.disconnect();
      this._observer = null;
      this.cancelAll();
      this.revokeAll();
      this._files.clear();
      this._queue = [];
    },

    /* ---------------------------------------------------------------- */
    /* Input events                                                      */
    /* ---------------------------------------------------------------- */

    handleDragEnter(event: DragEvent) {
      if (this.disabled) return;

      if (!this._hasFiles(event)) return;
      this._dragDepth++;
    },

    handleDragOver(event: DragEvent) {
      if (this.disabled) return;

      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = this.isFull ? 'none' : 'copy';
      }

      // Some browsers drop `dragenter` when moving fast between children.
      if (this._dragDepth === 0 && this._hasFiles(event)) this._dragDepth = 1;
    },

    handleDragLeave() {
      if (this._dragDepth > 0) this._dragDepth--;
    },

    handleDrop(event: DragEvent) {
      this._dragDepth = 0;
      if (this.disabled) return;

      this.addFiles(Array.from(event.dataTransfer?.files || []));
    },

    handleFileSelect(event: Event) {
      const input = event.target as HTMLInputElement;
      const files = Array.from(input.files || []);

      if (files.length > 0) this.addFiles(files);

      // Reset so picking the same file again still fires `change`.
      input.value = '';
    },

    handlePaste(event: ClipboardEvent) {
      if (this.disabled) return;

      const files = Array.from(event.clipboardData?.files || []);
      if (files.length === 0) return;

      event.preventDefault();
      this.addFiles(files);
    },

    _hasFiles(event: DragEvent): boolean {
      const types = event.dataTransfer?.types;

      return !types || Array.prototype.includes.call(types, 'Files');
    },

    /* ---------------------------------------------------------------- */
    /* Files                                                             */
    /* ---------------------------------------------------------------- */

    addFiles(incoming: File[]) {
      if (this.disabled || incoming.length === 0) return;

      this.rejections = [];

      const accepted: File[] = [];
      const existingKeys = new Set(this.previews.map((item) => item.key));

      let slots = this.multiple
        ? (this.maxFiles === null ? Infinity : this.maxFiles - this.previews.length)
        : 1;

      for (const file of incoming) {
        if (slots <= 0) {
          this.reject(file.name, t('maxFilesReached', 'Maximum of {max} files reached', {
            max: String(this.maxFiles ?? 1),
          }));
          continue;
        }

        if (file.size === 0) {
          this.reject(file.name, t('emptyFile', 'This file is empty'));
          continue;
        }

        if (file.size > this.maxSize) {
          this.reject(file.name, t('fileExceedsMaxSize', 'File {fileName} exceeds maximum size of {maxSize}MB', {
            fileName: file.name,
            maxSize: String(Math.round(this.maxSize / 1024 / 1024)),
          }));
          continue;
        }

        if (!matchesAccept(file, this.accept)) {
          this.reject(file.name, t('invalidFileType', 'Invalid file type: {fileName}', { fileName: file.name }));
          continue;
        }

        const key = `${file.name}:${file.size}:${file.lastModified}`;
        if (existingKeys.has(key)) {
          this.reject(file.name, t('duplicateFile', 'This file has already been added'));
          continue;
        }

        existingKeys.add(key);
        accepted.push(file);
        slots--;
      }

      if (accepted.length === 0) return;

      // Single mode always replaces the current selection.
      if (!this.multiple) this.clearAll({ silent: true });

      for (const file of accepted) {
        const id = uid();
        const kind = detectKind(file);

        this._files.set(id, file);

        this.previews.push({
          uuid: id,
          key: `${file.name}:${file.size}:${file.lastModified}`,
          kind,
          type: kind === 'image' ? 'image' : 'file',
          url: this.previewEnabled && kind === 'image' ? URL.createObjectURL(file) : null,
          name: file.name,
          bytes: file.size,
          size: formatFileSize(file.size),
          extension: getExtension(file.name) || kind.toUpperCase(),
          progress: this.uploadUrl ? 0 : 100,
          status: this.uploadUrl ? 'idle' : 'success',
          error: null,
          server: null,
          remaining: '',
        });
      }

      this.announce(t('filesAdded', '{count} file(s) added', { count: String(accepted.length) }));
      (this as any).$dispatch?.('dropzone:change', { files: accepted, count: this.previews.length });

      if (!this.uploadUrl) {
        this.syncModel();
        return;
      }

      if (this.autoUpload) this.startQueue();
    },

    reject(name: string, message: string) {
      this.rejections.push({ id: uid(), name, message });

      if (this.notify) {
        (this as any).$dispatch?.('notify', { type: 'error', content: message, duration: 5000 });
      }

      (this as any).$dispatch?.('dropzone:rejected', { name, message });
    },

    dismissRejection(id: string) {
      this.rejections = this.rejections.filter((rejection) => rejection.id !== id);
    },

    dismissRejections() {
      this.rejections = [];
    },

    find(uuid: string): DropzoneItem | undefined {
      return this.previews.find((item) => item.uuid === uuid);
    },

    removeByUuid(uuid: string) {
      const item = this.find(uuid);
      if (!item) return;

      this.cancel(uuid, { silent: true });

      if (item.url) {
        try { URL.revokeObjectURL(item.url); } catch { /* already revoked */ }
      }

      this._files.delete(uuid);
      this._queue = this._queue.filter((queued) => queued !== uuid);
      this.previews = this.previews.filter((preview) => preview.uuid !== uuid);

      this.announce(t('fileRemoved', '{name} removed', { name: item.name }));
      (this as any).$dispatch?.('dropzone:removed', { uuid, name: item.name });

      this.syncModel();
    },

    clearAll(options: { silent?: boolean } = {}) {
      this.cancelAll();
      this.revokeAll();

      this._files.clear();
      this._queue = [];
      this.previews = [];
      this.rejections = [];

      if (!options.silent) {
        this.announce(t('allFilesRemoved', 'All files removed'));
        this.syncModel();
        (this as any).$dispatch?.('dropzone:cleared');
      }
    },

    revokeAll() {
      for (const item of this.previews) {
        if (item.url) {
          try { URL.revokeObjectURL(item.url); } catch { /* already revoked */ }
        }
      }
    },

    /* ---------------------------------------------------------------- */
    /* Upload queue                                                      */
    /* ---------------------------------------------------------------- */

    startQueue() {
      for (const item of this.previews) {
        if (item.status === 'idle' && !this._queue.includes(item.uuid)) {
          this._queue.push(item.uuid);
        }
      }

      this.pumpQueue();
    },

    pumpQueue() {
      while (this._running < this.concurrency && this._queue.length > 0) {
        const uuid = this._queue.shift() as string;
        const file = this._files.get(uuid);

        if (!file || !this.find(uuid)) continue;

        this._running++;

        this.uploadOne(uuid, file).finally(() => {
          this._running--;
          queueMicrotask(() => this.pumpQueue());
        });
      }
    },

    async uploadOne(uuid: string, file: File) {
      if (!this.uploadUrl) return;

      const controller = new AbortController();
      this._controllers.set(uuid, controller);

      const item = this.find(uuid);
      if (!item) return;

      item.status = 'uploading';
      item.progress = 0;
      item.error = null;

      const startedAt = Date.now();
      let lastTick = 0;

      try {
        const result = await uploadInChunks(file, {
          chunkSize: this.chunkSize,
          uploadUrl: this.uploadUrl,
          headers: this.uploadHeaders,
          field: this.fieldName,
          signal: controller.signal,
          maxRetries: this.maxRetries,
          onProgress: (loaded) => {
            const current = this.find(uuid);
            if (!current) return;

            const percent = Math.min(100, Math.round((loaded / Math.max(1, file.size)) * 100));
            if (percent !== current.progress) current.progress = percent;

            const now = Date.now();
            if (now - lastTick < 500) return;
            lastTick = now;

            const elapsed = (now - startedAt) / 1000;
            const speed = elapsed > 0 ? loaded / elapsed : 0;
            current.remaining = speed > 0 && loaded < file.size
              ? formatDuration((file.size - loaded) / speed)
              : '';
          },
        });

        const done = this.find(uuid);
        if (!done) return;

        done.progress = 100;
        done.remaining = '';
        done.server = result;
        done.status = 'success';

        this.announce(t('fileUploaded', '{name} uploaded', { name: done.name }));
        (this as any).$dispatch?.('upload:success', { file, uuid, data: result });

        this.syncModel();
      } catch (error: any) {
        const failed = this.find(uuid);

        if (failed) {
          failed.status = 'error';
          failed.progress = 0;
          failed.remaining = '';
          failed.error = error?.message || t('uploadFailed', 'Upload failed');
        }

        (this as any).$dispatch?.('upload:error', { file, uuid, error });

        // A failed file must not stay in the bound model.
        this.syncModel();
      } finally {
        this._controllers.delete(uuid);
      }
    },

    /** Upload every pending file — useful when `autoUpload` is disabled. */
    uploadAll() {
      if (!this.uploadUrl) return;
      this.startQueue();
    },

    retry(uuid: string) {
      const item = this.find(uuid);
      if (!item || item.status === 'uploading') return;

      item.status = 'idle';
      item.error = null;
      item.progress = 0;

      this.startQueue();
    },

    cancel(uuid: string, options: { silent?: boolean } = {}) {
      const controller = this._controllers.get(uuid);
      if (controller) {
        try { controller.abort(); } catch { /* already aborted */ }
        this._controllers.delete(uuid);
      }

      this._queue = this._queue.filter((queued) => queued !== uuid);

      if (options.silent) return;

      const item = this.find(uuid);
      if (item && item.status === 'uploading') {
        item.status = 'error';
        item.progress = 0;
        item.remaining = '';
        item.error = t('uploadCancelled', 'Upload cancelled');
      }
    },

    cancelAll() {
      for (const [, controller] of this._controllers) {
        try { controller.abort(); } catch { /* already aborted */ }
      }

      this._controllers.clear();
      this._queue = [];
    },

    /* ---------------------------------------------------------------- */
    /* Output                                                            */
    /* ---------------------------------------------------------------- */

    /**
     * Push the current selection to Livewire and to the hidden inputs used by
     * classic form submits. Always derived from the full list so removals and
     * concurrent uploads can never desynchronise the bound value.
     */
    syncModel() {
      if (this._syncScheduled) return;
      this._syncScheduled = true;

      queueMicrotask(() => {
        this._syncScheduled = false;
        this.syncHiddenInputs();

        const wire = (this as any).$wire;
        if (!this.wireModel || typeof wire?.set !== 'function') return;

        const uploaded = this.previews
          .filter((item) => item.status === 'success' && item.server)
          .map((item) => plain(item.server));

        const value = this.multiple ? uploaded : (uploaded[0] ?? null);

        wire.set(this.wireModel, value, this.wireModelLive);
      });
    },

    syncHiddenInputs() {
      const container = (this as any).$refs?.hiddenFields as HTMLElement | undefined;
      if (!container || !this.fieldName) return;

      const uploaded = this.previews.filter((item) => item.status === 'success' && item.server);

      container.replaceChildren();

      if (uploaded.length === 0) return;

      const fragment = document.createDocumentFragment();

      for (const item of uploaded) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = this.multiple ? `${this.fieldName}[]` : this.fieldName;
        input.value = JSON.stringify(plain(item.server));
        fragment.appendChild(input);

        if (!this.multiple) break;
      }

      container.appendChild(fragment);
    },

    /* ---------------------------------------------------------------- */
    /* Presentation helpers                                              */
    /* ---------------------------------------------------------------- */

    statusLabel(item: DropzoneItem): string {
      if (item.status === 'uploading') {
        return item.remaining
          ? t('uploadingWithEta', 'Uploading… {time} left', { time: item.remaining })
          : t('uploading', 'Uploading…');
      }

      if (item.status === 'success') return t('complete', 'Complete');
      if (item.status === 'error') return t('failed', 'Failed');

      return t('pending', 'Pending');
    },

    announce(message: string) {
      this.announcement = message;
    },
  };
}

if (typeof window !== 'undefined') {
  (window as any).neuraDropzone = neuraDropzone;
}
