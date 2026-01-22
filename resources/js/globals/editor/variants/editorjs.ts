import type { OutputData, EditorConfig } from '@editorjs/editorjs';
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

        normalize(v: unknown): OutputData {
          if (!v) {
            return {
              time: Date.now(),
              blocks: [],
              version: '2.28.0',
            };
          }

          if (typeof v === 'string') {
            try {
              const parsed = JSON.parse(v);
              return this.isValidOutputData(parsed) ? parsed : this.getEmptyData();
            } catch {
              return this.getEmptyData();
            }
          }

          if (typeof v === 'object' && v !== null) {
            return this.isValidOutputData(v) ? (v as OutputData) : this.getEmptyData();
          }

          return this.getEmptyData();
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
                  uploader: {
                    async uploadByFile(file: File) {
                      const formData = new FormData();
                      formData.append(uploadField, file);

                      const response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                          ...uploadHeaders,
                        },
                        body: formData,
                      });

                      if (!response.ok) {
                        const error = await response.json().catch(() => ({ message: 'Upload failed' }));
                        throw new Error(error.message || 'Upload failed');
                      }

                      const result = await response.json();

                      return {
                        success: result.success ? 1 : 0,
                        file: {
                          url: result.url || result.data?.url,
                          width: result.width || result.data?.width,
                          height: result.height || result.data?.height,
                        },
                      };
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

            editor = new EditorJSClass({
              holder: host,
              readOnly: !editable,
              placeholder,
              data: initialData,
              tools,
              onChange: async () => {
                this.updatedAt = Date.now();
                this.isSyncing = true;

                try {
                  const value = await this.serialize(editor!);
                  this._pushState?.(value);
                } catch (error) {
                  console.error('Failed to save Editor.js content:', error);
                  this.isSyncing = false;
                }
              },
            });

            await editor.isReady;

            // Sync incoming changes (Livewire -> editor)
            (this as unknown as AlpineThis).$watch('state', async (next: unknown) => {
              const ed = this.editorInstance();
              if (!ed || this.isSyncing) return;

              try {
                const current = JSON.stringify(await ed.save());
                const normalized = this.normalize(next);
                const incoming = JSON.stringify(normalized);

                if (current === incoming) return;

                await ed.render(normalized);
              } catch (error) {
                console.error('Failed to sync Editor.js content:', error);
              }
            });

            // Livewire Navigate support
            window.addEventListener('livewire:navigating', () => this.destroy());
          } catch (error) {
            console.error('Failed to initialize Editor.js:', error);
            this.initialized = false;
          } finally {
            initializing = false;
          }
        },

        async destroy() {
          if (editor) {
            try {
              await editor.destroy();
            } catch (error) {
              console.warn('Error destroying Editor.js:', error);
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
