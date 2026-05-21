import type { OutputData, EditorConfig } from '@editorjs/editorjs';
import { patchEditorNotifier } from '../notifier-bridge';
import '../../types';

// Type for Editor.js instance
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type EditorJSInstance = any;

// Optional tools - dynamically imported if available
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type EditorJSTool = any;

async function loadEditorJSTools() {
  // Dynamic imports to avoid SSR issues
  const [
    editorjsModule,
    headerModule,
    listModule,
    quoteModule,
    codeModule,
    imageModule,
    inlineCodeModule,
  ] = await Promise.all([
    import('@editorjs/editorjs'),
    import('@editorjs/header'),
    import('@editorjs/list'),
    import('@editorjs/quote'),
    import('@editorjs/code'),
    import('@editorjs/image'),
    import('@editorjs/inline-code'),
  ]);

  // Handle different export patterns
  const EditorJS = editorjsModule.default || editorjsModule;
  const Header = headerModule.default || headerModule;
  const List = listModule.default || listModule;
  const Quote = quoteModule.default || quoteModule;
  const Code = codeModule.default || codeModule;
  const Image = imageModule.default || imageModule;
  const InlineCode = inlineCodeModule.default || inlineCodeModule;

  return { EditorJS, Header, List, Quote, Code, Image, InlineCode };
}

async function loadOptionalTools() {
  const tools: Record<string, EditorJSTool> = {};

  try {
    // @ts-ignore - Optional dependency
    const LinkTool = await import('@editorjs/link');
    tools.linkTool = LinkTool.default || LinkTool;
  } catch {
    // LinkTool not available
  }

  try {
    // @ts-ignore - Optional dependency
    const Marker = await import('@editorjs/marker');
    tools.marker = Marker.default || Marker;
  } catch {
    // Marker not available
  }

  try {
    // @ts-ignore - Optional dependency
    const Delimiter = await import('@editorjs/delimiter');
    tools.delimiter = Delimiter.default || Delimiter;
  } catch {
    // Delimiter not available
  }

  try {
    // @ts-ignore - Optional dependency
    const Table = await import('@editorjs/table');
    tools.table = Table.default || Table;
  } catch {
    // Table not available
  }

  return tools;
}

export type EditorJSEditorConfig = {
  state: unknown;          // entangled Livewire state or local
  placeholder?: string;
  editable?: boolean;
  uploadUrl?: string;
  uploadField?: string;
  uploadHeaders?: Record<string, string>;
};

let editorInstance: EditorJSInstance | null = null;

/**
 * Browser-only registration (prevents `document is not defined` on build)
 */
if (typeof window !== 'undefined') {
  document.addEventListener('alpine:init', () => {
    // Alpine is global when using Livewire/Alpine stack
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const AlpineAny = (window as any).Alpine as any;

    AlpineAny.data('editorjsEditor', (cfg: EditorJSEditorConfig) => {
      const placeholder = cfg.placeholder ?? 'Write something...';
      const editable = cfg.editable ?? true;
      const uploadUrl = cfg.uploadUrl ?? '/neura-kit/editor/upload-image';
      const uploadField = cfg.uploadField ?? 'image';
      const uploadHeaders = cfg.uploadHeaders ?? {};

      let editor: EditorJSInstance | null = null;
      let initializing = false;
      let currentUploadController: AbortController | null = null;

      // Helper function to perform the actual upload with timeout
      const performUpload = async (
        file: File,
        url: string,
        field: string,
        headers: Record<string, string>
      ): Promise<{ success: number; file: { url: string; width?: number; height?: number } }> => {
        // Cancel any previous upload in progress
        if (currentUploadController) {
          currentUploadController.abort();
          currentUploadController = null;
        }

        // Create fresh FormData for each upload
        const formData = new FormData();
        formData.append(field, file);

        // Get fresh CSRF token for each upload
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Create new AbortController for this upload
        const controller = new AbortController();
        currentUploadController = controller;
        const timeoutId = setTimeout(() => {
          controller.abort();
          currentUploadController = null;
        }, 60000); // 60s timeout

        try {
          // Add cache-busting parameter to URL to avoid cached responses
          const uploadUrlWithCache = url + (url.includes('?') ? '&' : '?') + '_t=' + Date.now();

          const response = await fetch(uploadUrlWithCache, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken || '',
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
              // Don't set Content-Type, let browser set it with boundary for FormData
              ...headers,
            },
            body: formData,
            credentials: 'same-origin',
            signal: controller.signal,
            cache: 'no-store', // Prevent caching
          });

          clearTimeout(timeoutId);
          currentUploadController = null;

          if (!response.ok) {
            let errorMessage = `Upload failed with status ${response.status}`;
            try {
              const errorData = await response.json();
              errorMessage = errorData.message || errorData.error || errorMessage;
            } catch {
              // If response is not JSON, try text
              try {
                const text = await response.text();
                if (text) errorMessage = text.substring(0, 200);
              } catch {
                // Ignore
              }
            }
            throw new Error(errorMessage);
          }


          const result = await response.json();

          if (!result.success) {
            throw new Error(result.message || 'Upload failed');
          }

          // Extract URL from different possible response structures
          const imageUrl = result.file?.url || result.url || result.data?.url;
          if (!imageUrl) {
            throw new Error('No image URL in response');
          }

          return {
            success: 1,
            file: {
              url: imageUrl,
              width: result.file?.width || result.width || result.data?.width,
              height: result.file?.height || result.height || result.data?.height,
            },
          };
        } catch (error) {
          clearTimeout(timeoutId);
          currentUploadController = null;
          
          if (error instanceof Error) {
            if (error.name === 'AbortError') {
              // Check if it was aborted by us (timeout) or by user (cancellation)
              if (controller.signal.aborted) {
                throw new Error('Upload timeout: The request took too long to complete');
              }
              throw new Error('Upload was cancelled');
            }
            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
              throw new Error('Network error: Please check your internet connection');
            }
          }
          
          throw error;
        }
      };

      return {
        // ---- reactive state (Livewire entangle can bind to this) ----
        state: cfg.state,
        initialized: false,
        isSyncing: false,
        updatedAt: Date.now(),

        // ---- internals ----
        _pushState: null as null | ((value: unknown) => void | Promise<void>),

        editorInstance() {
          return editor;
        },

        sanitizeImageBlocks(blocks: unknown[]): unknown[] {
          if (!Array.isArray(blocks)) {
            return [];
          }

          return blocks.filter((block) => {
            if (!block || typeof block !== 'object') {
              return false;
            }

            const typed = block as { type?: string; data?: Record<string, unknown> };

            if (typed.type !== 'image') {
              return true;
            }

            const file = typed.data?.file;

            if (file && typeof file === 'object' && file !== null) {
              const url = (file as { url?: unknown }).url;
              return typeof url === 'string' && url.trim() !== '';
            }

            const legacyUrl = typed.data?.url;
            return typeof legacyUrl === 'string' && legacyUrl.trim() !== '';
          });
        },

        normalize(v: unknown): OutputData {
          const empty = this.getEmptyData();

          if (!v) {
            return empty;
          }

          let candidate: unknown = v;

          if (typeof v === 'string') {
            try {
              candidate = JSON.parse(v);
            } catch {
              return empty;
            }
          }

          if (!this.isValidOutputData(candidate)) {
            return empty;
          }

          const data = candidate as OutputData;

          return {
            ...data,
            blocks: this.sanitizeImageBlocks(data.blocks) as OutputData['blocks'],
          };
        },

        getEmptyData(): OutputData {
          return {
            time: Date.now(),
            blocks: [],
            version: '2.28.0',
          };
        },

        isValidOutputData(data: unknown): data is OutputData {
          if (!data || typeof data !== 'object') return false;
          const d = data as any;
          return (
            typeof d.time === 'number' &&
            Array.isArray(d.blocks) &&
            typeof d.version === 'string'
          );
        },

        serialize(ed: any): Promise<OutputData> {
          return ed.save();
        },

        // IMPORTANT: Alpine v3 instance APIs are on `this`
        async init() {
          if (this.initialized || this.editorInstance() || initializing) return;

          initializing = true;

          try {
            await (this as unknown as AlpineThis).$nextTick();

            const host = (this as unknown as AlpineThis).$refs.editor as HTMLElement | null;
            if (!host) {
              initializing = false;
              return;
            }

            if (host.querySelector('.codex-editor')) {
              initializing = false;
              return;
            }

            host.innerHTML = '';
            this.initialized = true;

            const initialData = this.normalize(this.state);

            // Debounced push to Livewire/Alpine state
            this._pushState = async (value: unknown) => {
              this.state = value;
              (this as unknown as AlpineThis).$dispatch('input', value);
              this.isSyncing = false;
            };

            // Load Editor.js and all tools dynamically
            const { EditorJS: EditorJSClass, Header, List, Quote, Code, Image, InlineCode } = await loadEditorJSTools();
            const optionalTools = await loadOptionalTools();

            // Build tools object
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const tools: any = {
              header: {
                class: Header,
                config: {
                  levels: [1, 2, 3, 4],
                  defaultLevel: 2,
                },
              },
              list: {
                class: List,
                inlineToolbar: true,
              },
              quote: {
                class: Quote,
                inlineToolbar: true,
              },
              code: Code,
              image: {
                class: Image,
                config: {
                  // Enable caption field
                  captionPlaceholder: 'Enter image caption',
                  // Button text
                  buttonContent: 'Select an Image',
                  uploader: {
                    async uploadByFile(file: File) {
                      // Set upload flag to prevent sync conflicts
                      isUploading = true;

                      try {
                        // Cancel any previous upload
                        if (currentUploadController) {
                          currentUploadController.abort();
                          currentUploadController = null;
                        }

                        // Validate file size (10MB max)
                        const maxSize = 10 * 1024 * 1024;
                        if (file.size > maxSize) {
                          throw new Error('File size exceeds 10MB limit');
                        }

                        // Validate file type
                        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        if (!validTypes.includes(file.type)) {
                          throw new Error('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed');
                        }

                        // Validate file is not empty
                        if (file.size === 0) {
                          throw new Error('File is empty');
                        }

                        // Retry logic with exponential backoff
                        const maxRetries = 3;
                        let lastError: Error | null = null;

                        for (let attempt = 0; attempt < maxRetries; attempt++) {
                          try {
                            if (attempt > 0) {
                              // Exponential backoff: 1s, 2s, 4s
                              const delay = Math.pow(2, attempt - 1) * 1000;
                              await new Promise(resolve => setTimeout(resolve, delay));
                            }

                            const result = await performUpload(file, uploadUrl, uploadField, uploadHeaders);
                            
                            // Clear upload controller after success
                            currentUploadController = null;
                            
                            // Return in Editor.js expected format immediately
                            const uploadResult = {
                              success: 1,
                              file: {
                                url: result.file.url,
                                width: result.file.width,
                                height: result.file.height,
                              },
                            };
                            
                            // Wait a bit before allowing sync again to let Editor.js insert the block
                            // Use requestAnimationFrame to ensure Editor.js has time to process
                            requestAnimationFrame(() => {
                              setTimeout(() => {
                                isUploading = false;
                              }, 1000); // Give Editor.js 1 second to insert the block
                            });
                            
                            return uploadResult;
                          } catch (error) {
                            lastError = error as Error;

                            // Don't retry on validation errors
                            if (error instanceof Error && (
                              error.message.includes('exceeds') ||
                              error.message.includes('Invalid file type') ||
                              error.message.includes('No image URL') ||
                              error.message.includes('empty') ||
                              error.message.includes('cancelled')
                            )) {
                              currentUploadController = null;
                              throw error;
                            }

                            // Don't retry on 4xx errors (client errors)
                            if (error instanceof Error && error.message.includes('status 4')) {
                              currentUploadController = null;
                              throw error;
                            }

                            // Continue to next retry for network/server errors
                            if (attempt === maxRetries - 1) {
                              currentUploadController = null;
                              throw new Error(`Upload failed after ${maxRetries} attempts: ${lastError?.message || 'Unknown error'}`);
                            }
                          }
                        }

                        currentUploadController = null;
                        isUploading = false;
                        throw lastError || new Error('Upload failed');
                      } catch (error) {
                        isUploading = false;
                        throw error;
                      }
                    },
                  },
                },
              },
              inlineCode: InlineCode,
            };

            // Add optional tools if available
            if (optionalTools.linkTool) {
              tools.linkTool = {
                class: optionalTools.linkTool,
                config: {
                  endpoint: '/neura-kit/editor/fetch-url',
                },
              };
            }

            if (optionalTools.marker) {
              tools.marker = optionalTools.marker;
            }

            if (optionalTools.delimiter) {
              tools.delimiter = optionalTools.delimiter;
            }

            if (optionalTools.table) {
              tools.table = {
                class: optionalTools.table,
                inlineToolbar: true,
                config: {
                  rows: 2,
                  cols: 2,
                },
              };
            }

            // Track if upload is in progress to prevent sync conflicts
            let isUploading = false;

            editor = new EditorJSClass({
              holder: host,
              readOnly: !editable,
              placeholder,
              data: initialData,
              tools,
              onChange: async () => {
                // Skip sync if upload is in progress
                if (isUploading) {
                  return;
                }

                this.updatedAt = Date.now();
                this.isSyncing = true;

                try {
                  const value = await this.serialize(editor!);
                  this._pushState?.(value);
                } catch (error) {
                  this.isSyncing = false;
                }
              },
              onReady: () => {
                // Editor is ready
              },
            });

            await editor.isReady;

            patchEditorNotifier(editor);

            // Sync incoming changes (Livewire -> editor)
            (this as unknown as AlpineThis).$watch('state', async (next: unknown) => {
              const ed = this.editorInstance();
              if (!ed || this.isSyncing || isUploading) {
                return;
              }

              try {
                // Get current editor state
                let currentData;
                try {
                  currentData = await ed.save();
                } catch (error) {
                  return;
                }

                const current = JSON.stringify(currentData);
                const normalized = this.normalize(next);
                
                // Validate normalized data before using it
                if (!this.isValidOutputData(normalized)) {
                  return;
                }
                
                const incoming = JSON.stringify(normalized);

                if (current === incoming) return;

                // Use clear + render instead of direct render to avoid block index errors
                await ed.clear();
                await ed.render(normalized);
              } catch (error) {
                // Try to recover by reinitializing with empty data
                try {
                  await ed.clear();
                  await ed.render(this.getEmptyData());
                } catch (recoveryError) {
                  // Ignore recovery errors
                }
              }
            });

            // Livewire Navigate support
            window.addEventListener('livewire:navigating', () => this.destroy());
          } catch (error) {
            this.initialized = false;
          } finally {
            initializing = false;
          }
        },

        async destroy() {
          // Cancel any ongoing upload
          if (currentUploadController) {
            currentUploadController.abort();
            currentUploadController = null;
          }

          if (editor) {
            try {
              await editor.destroy();
            } catch (error) {
              // Ignore destruction errors
            }
          }
          editor = null;
          initializing = false;
          this.initialized = false;
          this.isSyncing = false;
          this._pushState = null;
        },
      };
    });
  });
}
