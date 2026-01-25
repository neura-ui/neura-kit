type DropzoneStatus = 'idle' | 'uploading' | 'success' | 'error';

type UploadResult = {
  uuid?: string;
  filename?: string;
  path?: string;
  size?: number;
  mime?: string;
  [key: string]: any;
};

// Helper function to get translations reliably
function t(key: string, params?: Record<string, string>): string | undefined {
  const translations = (window as any).NeuraKitTranslations;
  
  // Check if translations are loaded and available
  if (translations && translations.translations && Object.keys(translations.translations).length > 0) {
    // Check if the translation key exists
    if (translations.translations[key]) {
      return translations.t(key, params || {});
    }
    // Translation doesn't exist, return undefined to allow fallback
    return undefined;
  }
  
  // Fallback to window.t if available
  if (typeof (window as any).t === 'function') {
    try {
      const result = (window as any).t(key, params || {});
      // If window.t returns something different from the key, it means translation exists
      if (result && result !== key) {
        return result;
      }
    } catch (e) {
      // Ignore errors
    }
  }
  
  // No translations loaded or translation doesn't exist, return undefined to allow fallback
  return undefined;
}

type DropzonePreview = {
  uuid: string;
  type: 'image' | 'file';
  url?: string;              // object URL for preview
  name: string;
  size: string;
  extension: string;
  progress: number;
  status: DropzoneStatus;
  error?: string | null;
  server?: UploadResult | null; // backend response
};

export type DropzoneOptions = {
  accept?: string;
  maxSizeBytes?: number;
  multiple?: boolean;
  chunkSize?: number;
  uploadUrl?: string | null;
  uploadHeaders?: Record<string, string>;
  name?: string | null;           // form field name
  invalid?: boolean;
  wireModel?: string | null;      // wire:model name (string from Blade)
  previewEnabled?: boolean;
  removable?: boolean;
  concurrency?: number;
};

const defaultOptions: Required<Omit<DropzoneOptions, 'wireModel'>> & { wireModel: string | null } = {
  accept: 'image/*',
  maxSizeBytes: 10 * 1024 * 1024,
  multiple: false,
  chunkSize: 1 * 1024 * 1024,
  uploadUrl: null,
  uploadHeaders: {},
  name: null,
  invalid: false,
  wireModel: null,
  previewEnabled: true,
  removable: true,
  concurrency: 2,
};

function uid(): string {
  if (typeof crypto !== 'undefined' && (crypto as any).randomUUID) return (crypto as any).randomUUID();
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

function formatFileSize(bytes: number) {
  if (!bytes) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.min(sizes.length - 1, Math.floor(Math.log(bytes) / Math.log(k)));
  return `${Math.round((bytes / Math.pow(k, i)) * 100) / 100} ${sizes[i]}`;
}

function getExtension(name: string): string {
  const part = name.split('.').pop()?.trim() || '';
  return part ? part.toUpperCase() : 'FILE';
}

/**
 * Robust accept check supporting:
 * - image/*, application/pdf
 * - .png, .jpg
 * - comma-separated rules
 */
function matchesAccept(file: File, accept: string): boolean {
  if (!accept || accept === '*/*') return true;

  const rules = accept.split(',').map((r) => r.trim()).filter(Boolean);
  if (rules.length === 0) return true;

  const fileType = (file.type || '').toLowerCase();
  const fileName = file.name.toLowerCase();

  return rules.some((rule) => {
    const r = rule.toLowerCase();

    // extension rule: ".png"
    if (r.startsWith('.')) return fileName.endsWith(r);

    // wildcard mime: "image/*"
    if (r.endsWith('/*')) {
      const prefix = r.slice(0, r.length - 1); // keep trailing slash
      return fileType.startsWith(prefix);
    }

    // exact mime
    return fileType === r;
  });
}

/**
 * Extract a human-readable error message from HTTP response
 */
function extractErrorMessage(responseText: string, statusCode: number): string {
  try {
    const json = JSON.parse(responseText);
    if (json.message) return json.message;
    if (json.error) return json.error;
    if (json.errors && typeof json.errors === 'object') {
      const firstError = Object.values(json.errors)[0];
      if (Array.isArray(firstError)) return String(firstError[0]);
      return String(firstError);
    }
  } catch {
    // ignore
  }

  const text = (responseText || '').trim();

  if (text.startsWith('<')) {
    const titleMatch = text.match(/<title>(.*?)<\/title>/i);
    if (titleMatch?.[1]) return titleMatch[1].trim();

    const h1Match = text.match(/<h1[^>]*>(.*?)<\/h1>/i);
    if (h1Match?.[1]) return h1Match[1].replace(/<[^>]*>/g, '').trim();

    const statusMessages: Record<number, string> = {
      400: t('badRequest') || 'Bad Request',
      401: t('unauthorized') || 'Unauthorized',
      403: t('forbidden') || 'Forbidden',
      404: t('notFound') || 'Not Found',
      413: t('fileTooLarge') || 'File too large - File size exceeds the allowed limit',
      422: t('invalidData') || 'Invalid Data',
      429: t('tooManyRequests') || 'Too Many Requests',
      500: t('serverError') || 'Server Error',
      502: t('badGateway') || 'Bad Gateway',
      503: t('serviceUnavailable') || 'Service Unavailable',
      504: t('gatewayTimeout') || 'Gateway Timeout',
    };

    return statusMessages[statusCode] || t('httpError', { code: String(statusCode) }) || `HTTP Error ${statusCode}`;
  }

  if (text && text.length < 240) return text;

  return t('uploadError', { code: String(statusCode) }) || `Upload error (${statusCode})`;
}

async function uploadInChunks(
  file: File,
  {
    chunkSize,
    uploadUrl,
    headers,
    field,
    onProgress,
    signal,
    retryPerChunk = 2,
  }: {
    chunkSize: number;
    uploadUrl: string;
    headers: Record<string, string>;
    field?: string | null;
    onProgress: (percent: number) => void;
    signal?: AbortSignal;
    retryPerChunk?: number;
  },
): Promise<UploadResult> {
  const totalChunks = Math.ceil(file.size / chunkSize);
  const fileUuid = uid();
  let lastResponse: any = null;

  const throwIfAborted = () => {
    if (signal?.aborted) throw new Error(t('uploadCancelled') || 'Upload cancelled');
  };

  for (let index = 0; index < totalChunks; index++) {
    throwIfAborted();

    const start = index * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    const blob = file.slice(start, end);

    const formData = new FormData();
    formData.append('chunk', blob, file.name);
    formData.append('fileName', file.name);
    formData.append('fileSize', String(file.size));
    formData.append('chunkIndex', String(index));
    formData.append('totalChunks', String(totalChunks));
    formData.append('uuid', fileUuid);
    if (field) formData.append('field', field);

    // Retry loop per chunk
    let attempt = 0;
    while (true) {
      throwIfAborted();
      try {
        await new Promise<void>((resolve, reject) => {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', uploadUrl, true);

          Object.entries(headers || {}).forEach(([k, v]) => {
            try {
              xhr.setRequestHeader(k, v);
            } catch {
              // ignore invalid header
            }
          });

          if (signal) {
            const abortHandler = () => {
              try { xhr.abort(); } catch {}
            };
            signal.addEventListener('abort', abortHandler, { once: true });
          }

          xhr.upload.onprogress = (evt) => {
            if (!evt.lengthComputable) return;
            const chunkPercent = evt.total > 0 ? evt.loaded / evt.total : 0;
            const overall = ((index + chunkPercent) / totalChunks) * 100;
            onProgress(Math.round(overall));
          };

          xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
              const overall = ((index + 1) / totalChunks) * 100;
              onProgress(Math.round(overall));

              try {
                lastResponse = JSON.parse(xhr.responseText);
              } catch {
                lastResponse = { success: true };
              }
              resolve();
              return;
            }

            reject(new Error(extractErrorMessage(xhr.responseText, xhr.status)));
          };

          xhr.onerror = () => reject(new Error(t('networkError') || 'Network error - Unable to connect to server'));
          xhr.onabort = () => reject(new Error(t('uploadCancelled') || 'Upload cancelled'));

          xhr.send(formData);
        });

        break; // success, exit retry loop
      } catch (e: any) {
        attempt++;
        if (attempt > retryPerChunk) throw e;
        // small backoff
        await new Promise((r) => setTimeout(r, 250 * attempt));
      }
    }
  }

  // Many backends return data on final chunk: { data: { uuid, filename, path, ... } }
  // Normalize:
  if (lastResponse?.data && typeof lastResponse.data === 'object') return lastResponse.data;
  return lastResponse ?? { success: true };
}

export function neuraDropzone(options: DropzoneOptions = {}) {
  const config = { ...defaultOptions, ...options };

  return {
    // State
    previews: [] as DropzonePreview[],
    isDragging: false,
    _invalid: config.invalid,
    accept: config.accept,
    maxSize: config.maxSizeBytes,
    multiple: config.multiple,
    chunkSize: config.chunkSize,
    uploadUrl: config.uploadUrl,
    uploadHeaders: config.uploadHeaders,
    fieldName: config.name,
    wireModel: config.wireModel,
    previewEnabled: config.previewEnabled,
    removable: config.removable,
    concurrency: Math.max(1, config.concurrency || 1),

    // Internal maps
    _files: new Map<string, File>(),
    _abort: new Map<string, AbortController>(),
    _queueRunning: 0,
    _queue: [] as string[],
    
    // FIXED: Add validation sync state management
    _validationSyncScheduled: false,
    _lastErrorKeys: [] as string[],

    get invalid() {
      // Return the computed _invalid state which is kept in sync by the sync handlers
      return this._invalid;
    },

    get hasError() {
      return this.invalid || this.previews.some((p) => p.status === 'error');
    },

    // FIXED: Improved validation sync with debouncing and proper state detection
    _scheduleValidationSync() {
      if (this._validationSyncScheduled) return;
      
      this._validationSyncScheduled = true;
      
      // Use microtask queue for immediate but non-blocking updates
      queueMicrotask(() => {
        this._performValidationSync();
        this._validationSyncScheduled = false;
      });
    },

    _performValidationSync() {
      const self = this as any;
      const wire = self.$wire;
      
      // If no wireModel and no fieldName, this is a static dropzone (e.g., :invalid="true")
      // Don't override manual :invalid prop
      if (!this.wireModel && !this.fieldName) {
        return;
      }
      
      // If $wire doesn't exist yet, skip
      if (!wire) {
        return;
      }
      
      // Check for errors - Livewire 3 uses $errors property
      let hasErrors = false;
      try {
        let errorKeys: string[] = [];
        
        // Method 1: wire.$errors (Livewire 3 style - this is a MessageBag proxy)
        if (wire.$errors) {
          // In Livewire 3, $errors is a proxy that has methods like has(), get(), etc.
          // Check if our field has errors using has() method
          const fieldToCheck = this.wireModel || this.fieldName;
          if (fieldToCheck) {
            // Try using has() method
            if (typeof wire.$errors.has === 'function') {
              if (wire.$errors.has(fieldToCheck) || 
                  wire.$errors.has(fieldToCheck + '.*') ||
                  wire.$errors.has(fieldToCheck + '.0')) {
                hasErrors = true;
              }
            }
            // Also try accessing as object
            if (!hasErrors && typeof wire.$errors === 'object') {
              const errObj = wire.$errors;
              if (errObj[fieldToCheck] || errObj[fieldToCheck + '.*']) {
                hasErrors = true;
              }
            }
          }
        }
        
        // Method 2: wire.errors (Livewire 2 style or fallback)
        if (!hasErrors && wire.errors && typeof wire.errors === 'object') {
          errorKeys = Object.keys(wire.errors);
          if (errorKeys.length > 0) {
            hasErrors = this._hasRelevantErrors(errorKeys);
          }
        }
        
      } catch (e) {
        // Ignore errors - don't call methods that might not exist
      }
      
      // Update based on Livewire errors
      const wasInvalid = this._invalid;
      this._invalid = hasErrors;
      
      // If state changed, force multiple reactive updates to ensure UI updates
      if (wasInvalid !== hasErrors) {
        // Touch multiple reactive properties to force Alpine to re-evaluate
        this.previews = [...this.previews];
        this.isDragging = !!this.isDragging;
        
        // Force Alpine to re-evaluate on next tick
        const self = this as any;
        if (self.$nextTick) {
          self.$nextTick(() => {
            // Re-touch to ensure reactivity
            this.previews = [...this.previews];
          });
        }
        
        // Also use requestAnimationFrame for good measure
        requestAnimationFrame(() => {
          this.previews = [...this.previews];
        });
      }
    },

    _hasRelevantErrors(errorKeys: string[]): boolean {
      if (this.fieldName) {
        if (errorKeys.some((key: string) => key === this.fieldName || key.startsWith(this.fieldName + '.'))) {
          return true;
        }
      }
      
      if (this.wireModel) {
        if (errorKeys.some((key: string) => key === this.wireModel || key.startsWith(this.wireModel + '.'))) {
          return true;
        }
      }
      
      return false;
    },

    init() {
      const w = window as any;
      const self = this as any;
      const rootElement = self.$el;

      // FIXED: Livewire 3 with all hook variants
      if (w.Livewire?.hook) {
        try {
          w.Livewire.hook('commit', () => {
            this._scheduleValidationSync();
          });
        } catch {}
        
        try {
          w.Livewire.hook('commit.response', () => {
            this._scheduleValidationSync();
          });
        } catch {}
        
        try {
          w.Livewire.hook('morph.updated', () => {
            this._scheduleValidationSync();
          });
        } catch {}

        try {
          w.Livewire.hook('effect', () => {
            this._scheduleValidationSync();
          });
        } catch {}
      }

      // Listen to all Livewire events
      const handleLivewireUpdate = () => {
        this._scheduleValidationSync();
      };

      // Livewire 3 events
      document.addEventListener('livewire:update', handleLivewireUpdate);
      document.addEventListener('livewire:updated', handleLivewireUpdate);
      document.addEventListener('livewire:navigated', handleLivewireUpdate);
      document.addEventListener('livewire:init', handleLivewireUpdate);
      document.addEventListener('livewire:initialized', handleLivewireUpdate);
      
      // Use Livewire.on() for more reliable event listening in Livewire 3
      if (w.Livewire?.on) {
        try {
          w.Livewire.on('commit', handleLivewireUpdate);
        } catch {}
        try {
          w.Livewire.on('response', handleLivewireUpdate);
        } catch {}
      }
      
      // Listen to the component's Livewire instance directly if available
      if (self.$wire) {
        // Watch for any property changes
        try {
          const originalSet = self.$wire.$set;
          if (originalSet) {
            self.$wire.$set = function(...args: any[]) {
              const result = originalSet.apply(this, args);
              handleLivewireUpdate();
              return result;
            };
          }
        } catch {}
      }

      // Watch form events
      if (rootElement) {
        const form = rootElement.closest('form') || rootElement;
        
        const handleFormEvent = () => {
          this._scheduleValidationSync();
          requestAnimationFrame(() => this._scheduleValidationSync());
        };

        form.addEventListener('submit', handleFormEvent, true);
        form.addEventListener('click', (e: Event) => {
          const target = e.target as HTMLElement;
          if (target.tagName === 'BUTTON' || target.closest('button')) {
            handleFormEvent();
          }
        }, true);
      }

      // Periodic check (100ms) as fallback for reliability - more aggressive for better UX
      const intervalId = setInterval(() => {
        this._performValidationSync();
      }, 100);

      // Store interval ID for cleanup if needed
      (this as any)._validationInterval = intervalId;

      // Initial check
      queueMicrotask(() => this._performValidationSync());
      requestAnimationFrame(() => this._performValidationSync());
    },

    statusLabel(preview: DropzonePreview) {
      if (preview.status === 'uploading') return t('uploading') || 'Uploading...';
      if (preview.status === 'success') return t('complete') || 'Complete';
      if (preview.status === 'error') return t('failed') || 'Failed';
      return t('pending') || 'Pending';
    },

    triggerFileInput() {
      const input = (this as any).$refs?.fileInput as HTMLInputElement | undefined;
      if (!input) return;

      // Ensure it works consistently (some browsers require it in the same tick)
      requestAnimationFrame(() => input.click());
    },

    handleDragOver(_e: DragEvent) {
      this.isDragging = true;
    },

    handleDragLeave(_e: DragEvent) {
      this.isDragging = false;
    },

    handleDrop(e: DragEvent) {
      this.isDragging = false;
      const dropped = Array.from(e.dataTransfer?.files || []);
      this.addFiles(dropped);
    },

    handleFileSelect(e: Event) {
      e.preventDefault();
      e.stopPropagation();
      
      const target = e.target as HTMLInputElement;
      const selected = Array.from(target.files || []);
      
      if (selected.length > 0) {
        this.addFiles(selected);
      }

      // reset input so selecting same file again triggers change
      target.value = '';
    },

    addFiles(fileList: File[]) {
      const dispatch = (this as any).$dispatch as ((name: string, detail?: any) => void) | undefined;

      const valid: File[] = [];
      for (const file of fileList) {
        if (file.size > this.maxSize) {
          dispatch?.('notify', {
            type: 'error',
            content:
              t('fileExceedsMaxSize', {
                fileName: file.name,
                maxSize: Math.round(this.maxSize / 1024 / 1024).toString(),
              }) || `File ${file.name} exceeds maximum size`,
            duration: 5000,
          });
          continue;
        }

        if (!matchesAccept(file, this.accept)) {
          dispatch?.('notify', {
            type: 'error',
            content: t('invalidFileType', { fileName: file.name }) || `Invalid file type: ${file.name}`,
            duration: 5000,
          });
          continue;
        }

        valid.push(file);
      }

      if (!this.multiple && valid.length > 0) {
        // clean existing (revoke URLs)
        this.clearAll();
        valid.splice(1);
      }

      for (const file of valid) {
        const id = uid();
        this._files.set(id, file);

        const isImage = file.type?.startsWith('image/');
        const url = this.previewEnabled && isImage ? URL.createObjectURL(file) : undefined;

        const preview: DropzonePreview = {
          uuid: id,
          type: isImage ? 'image' : 'file',
          url,
          name: file.name,
          size: formatFileSize(file.size),
          extension: getExtension(file.name),
          progress: this.uploadUrl ? 0 : 100,
          status: this.uploadUrl ? 'idle' : 'success',
          error: null,
          server: null,
        };

        this.previews = [...this.previews, preview];
      }

      if (this.uploadUrl) this.startQueue();
    },

    startQueue() {
      // enqueue idle items
      for (const p of this.previews) {
        if (p.status === 'idle' && !this._queue.includes(p.uuid)) this._queue.push(p.uuid);
      }
      this.pumpQueue();
    },

    async pumpQueue() {
      while (this._queueRunning < this.concurrency && this._queue.length > 0) {
        const uuid = this._queue.shift()!;
        const file = this._files.get(uuid);
        if (!file) continue;

        this._queueRunning++;
        this.uploadOne(uuid, file)
          .catch(() => void 0)
          .finally(() => {
            this._queueRunning--;
            // Continue pumping in case new files were added
            queueMicrotask(() => this.pumpQueue());
          });
      }
    },

    async uploadOne(uuid: string, file: File) {
      if (!this.uploadUrl) return;

      const dispatch = (this as any).$dispatch as ((name: string, detail?: any) => void) | undefined;
      const controller = new AbortController();
      this._abort.set(uuid, controller);

      this.setStatus(uuid, 'uploading');
      this.setProgress(uuid, 0);

      try {
        const result = await uploadInChunks(file, {
          chunkSize: this.chunkSize,
          uploadUrl: this.uploadUrl,
          headers: this.uploadHeaders,
          field: this.fieldName,
          onProgress: (p) => this.setProgress(uuid, p),
          signal: controller.signal,
        });

        this.setProgress(uuid, 100);
        this.setServerResult(uuid, result);
        this.setStatus(uuid, 'success');

        // 1) Dispatch event for host listeners
        dispatch?.('upload:success', {
          file,
          uuid,
          data: result,
        });

        // 2) If wireModel was provided, set it directly (single or array)
        const wire = (this as any).$wire;
        if (this.wireModel && wire?.set) {
          if (this.multiple) {
            const current = wire.get?.(this.wireModel) ?? [];
            const next = Array.isArray(current) ? [...current, result] : [result];
            wire.set(this.wireModel, next);
          } else {
            wire.set(this.wireModel, result);
          }
        }

        // 3) For classic form submit (non-Livewire), write hidden inputs
        this.syncHiddenInputs();
      } catch (e: any) {
        this.setStatus(uuid, 'error', e?.message || t('uploadFailed') || 'Upload failed');
        dispatch?.('upload:error', { file, uuid, error: e });
      } finally {
        this._abort.delete(uuid);
      }
    },

    setProgress(uuid: string, value: number) {
      const v = Math.min(100, Math.max(0, value));
      this.previews = this.previews.map((p) => (p.uuid === uuid ? { ...p, progress: v } : p));
    },

    setStatus(uuid: string, status: DropzoneStatus, error?: string | null) {
      this.previews = this.previews.map((p) =>
        p.uuid === uuid ? { ...p, status, error: error ?? (status === 'error' ? p.error : null) } : p,
      );
    },

    setServerResult(uuid: string, server: UploadResult) {
      this.previews = this.previews.map((p) => (p.uuid === uuid ? { ...p, server } : p));
    },

    removeByUuid(uuid: string) {
      // Abort in-flight upload
      const ctrl = this._abort.get(uuid);
      if (ctrl) {
        try { ctrl.abort(); } catch {}
        this._abort.delete(uuid);
      }

      // Revoke object URL
      const p = this.previews.find((x) => x.uuid === uuid);
      if (p?.url && p.url.startsWith('blob:')) {
        try { URL.revokeObjectURL(p.url); } catch {}
      }

      this._files.delete(uuid);
      this._queue = this._queue.filter((x) => x !== uuid);
      this.previews = this.previews.filter((x) => x.uuid !== uuid);

      this.syncHiddenInputs();
    },

    clearAll() {
      // Abort all
      for (const [, ctrl] of this._abort) {
        try { ctrl.abort(); } catch {}
      }
      this._abort.clear();

      // Revoke all URLs
      for (const p of this.previews) {
        if (p.url && p.url.startsWith('blob:')) {
          try { URL.revokeObjectURL(p.url); } catch {}
        }
      }

      this._files.clear();
      this._queue = [];
      this.previews = [];

      this.syncHiddenInputs();
    },

    syncHiddenInputs() {
      const container = (this as any).$refs?.hiddenFields as HTMLElement | undefined;
      if (!container) return;

      container.innerHTML = '';

      if (!this.fieldName) return;

      const successful = this.previews.filter((p) => p.status === 'success' && p.server);
      if (successful.length === 0) return;

      if (this.multiple) {
        for (const p of successful) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = `${this.fieldName}[]`;
          input.value = JSON.stringify(p.server);
          container.appendChild(input);
        }
      } else {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = this.fieldName;
        input.value = JSON.stringify(successful[0].server);
        container.appendChild(input);
      }
    },
  };
}

if (typeof window !== 'undefined') {
  (window as any).neuraDropzone = neuraDropzone;
}