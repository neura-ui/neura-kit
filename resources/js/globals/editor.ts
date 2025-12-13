import type { Editor as TiptapEditor } from '@tiptap/core';
import type { Extension, Mark, Node } from '@tiptap/core';
import './types';

type EditorModules = {
  Editor: typeof TiptapEditor;
  StarterKit: Extension;
  Link: Mark;
  Image: Node;
  Placeholder: Extension;
  TextAlign: Extension;
  Underline: Mark;
  Highlight: Mark;
};

let editorModules: EditorModules | null = null;

async function loadEditor(): Promise<EditorModules> {
  if (editorModules) return editorModules;

  const [
    { Editor },
    { default: StarterKit },
    { default: Link },
    { default: Image },
    { default: Placeholder },
    { default: TextAlign },
    { default: Underline },
    { default: Highlight },
  ] = await Promise.all([
    import('@tiptap/core'),
    import('@tiptap/starter-kit'),
    import('@tiptap/extension-link'),
    import('@tiptap/extension-image'),
    import('@tiptap/extension-placeholder'),
    import('@tiptap/extension-text-align'),
    import('@tiptap/extension-underline'),
    import('@tiptap/extension-highlight'),
  ]);

  editorModules = {
    Editor,
    StarterKit,
    Link: Link as Mark,
    Image: Image as Node,
    Placeholder,
    TextAlign,
    Underline: Underline as Mark,
    Highlight: Highlight as Mark,
  };
  return editorModules;
}

type JsonDoc = Record<string, unknown>;
type Mode = 'html' | 'json';

export type RichEditorConfig = {
  state: unknown;          // entangled Livewire state or local
  placeholder?: string;
  editable?: boolean;
  mode?: Mode;
  debounce?: number;
};

function makeEmptyDoc(): JsonDoc {
  return { type: 'doc', content: [{ type: 'paragraph' }] };
}

function safeJsonParse(v: string): JsonDoc {
  try {
    const parsed = JSON.parse(v);
    return typeof parsed === 'object' && parsed ? (parsed as JsonDoc) : makeEmptyDoc();
  } catch {
    return makeEmptyDoc();
  }
}

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number) {
  let t: ReturnType<typeof setTimeout> | null = null;
  return (...args: Parameters<T>) => {
    if (t) clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

/**
 * Browser-only registration (prevents `document is not defined` on build)
 */
if (typeof window !== 'undefined') {
  document.addEventListener('alpine:init', () => {
    // Alpine is global when using Livewire/Alpine stack
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const AlpineAny = (window as any).Alpine as any;

    AlpineAny.data('richEditor', (cfg: RichEditorConfig) => {
      const placeholder = cfg.placeholder ?? 'Write something...';
      const editable = cfg.editable ?? true;
      const mode: Mode = cfg.mode ?? 'html';
      const debounceMs = cfg.debounce ?? 300;

      let editor: TiptapEditor | null = null;
      let initializing = false;

      return {
        // ---- reactive state (Livewire entangle can bind to this) ----
        state: cfg.state,
        mode,
        initialized: false,
        isSyncing: false,
        updatedAt: Date.now(),

        // ---- internals ----
        _pushState: null as null | ((value: unknown) => void),

        editor() {
          return editor;
        },

        normalize(v: unknown): string | JsonDoc {
          if (this.mode !== 'json') return (typeof v === 'string' ? v : '') || '';
          if (!v) return makeEmptyDoc();
          if (typeof v === 'string') return safeJsonParse(v);
          return typeof v === 'object' ? (v as JsonDoc) : makeEmptyDoc();
        },

        serialize(ed: TiptapEditor): unknown {
          return this.mode === 'json' ? ed.getJSON() : ed.getHTML();
        },

        // IMPORTANT: Alpine v3 instance APIs are on `this`
        async init() {
          if (this.initialized || this.editor() || initializing) return;

          initializing = true;

          try {
            await (this as unknown as AlpineThis).$nextTick();

            const host = (this as unknown as AlpineThis).$refs.editor as HTMLElement | null;
            if (!host) {
              initializing = false;
              return;
            }

            if (host.querySelector('.ProseMirror')) {
              initializing = false;
              return;
            }

            host.innerHTML = '';
            this.initialized = true;

          const {
            Editor,
            StarterKit,
            Link,
            Image,
            Placeholder,
            TextAlign,
            Underline,
            Highlight,
          } = await loadEditor();

          // Debounced push to Livewire/Alpine state
          this._pushState = debounce((value: unknown) => {
            this.state = value;
            (this as unknown as AlpineThis).$dispatch('input', value);
            this.isSyncing = false;
          }, debounceMs);

          editor = new Editor({
            element: host,
            editable,
            content: this.normalize(this.state),
            extensions: [
              StarterKit.configure({
                heading: { levels: [1, 2, 3] },
                history: { depth: 100 },
                link: false,
                underline: false,
              }),
              Link.configure({
                openOnClick: false,
                HTMLAttributes: { class: 'text-primary underline cursor-pointer' },
              }),
              Image.configure({ HTMLAttributes: { class: 'rounded-lg max-w-full h-auto' } }),
              Placeholder.configure({ placeholder }),
              TextAlign.configure({ types: ['heading', 'paragraph'] }),
              Underline,
              Highlight.configure({ multicolor: true }),
            ],
            editorProps: {
              attributes: {
                class: 'prose dark:prose-invert max-w-none focus:outline-none min-h-[150px] p-4',
              },
            },
            onUpdate: ({ editor: ed }) => {
              this.updatedAt = Date.now();
              this.isSyncing = true;

              const value = this.serialize(ed);
              this._pushState?.(value);
            },
            onSelectionUpdate: () => {
              this.updatedAt = Date.now();
            },
          });

          // Sync incoming changes (Livewire -> editor)
          (this as unknown as AlpineThis).$watch('state', (next: unknown) => {
            const ed = this.editor();
            if (!ed || this.isSyncing) return;

            const current =
              this.mode === 'json' ? JSON.stringify(ed.getJSON()) : ed.getHTML();
            const normalized = this.normalize(next);
            const incoming =
              this.mode === 'json' ? JSON.stringify(normalized) : (normalized as string);

            if (current === incoming) return;

            // Preserve selection when possible
            const { from, to } = ed.state.selection;
            ed.commands.setContent(normalized as any, false);
            try {
              ed.commands.setTextSelection({ from, to });
            } catch {
              // ignore selection restore errors
            }
          });

          // Livewire Navigate support (optional but saves you from ghost editors)
          window.addEventListener('livewire:navigating', () => this.destroy());
          } catch (error) {
            console.error('Failed to initialize editor:', error);
            this.initialized = false;
          } finally {
            initializing = false;
          }
        },

        destroy() {
          editor?.destroy();
          editor = null;
          initializing = false;
          this.initialized = false;
          this.isSyncing = false;
          this._pushState = null;
        },

        // ---- command helpers ----
        cmd(fn: (ed: TiptapEditor) => void) {
          const ed = this.editor();
          if (!ed || ed.isDestroyed || !this.initialized) return;

          try {
            fn(ed);
            this.updatedAt = Date.now();
          } catch (e) {
            console.warn('TipTap command failed:', e);
          }
        },

        isActive(type: string, opts: Record<string, unknown> = {}) {
          return this.editor()?.isActive(type, opts) ?? false;
        },

        canUndo() {
          return this.editor()?.can().undo() ?? false;
        },
        canRedo() {
          return this.editor()?.can().redo() ?? false;
        },

        toggleBold() { this.cmd((e) => e.chain().toggleBold().focus().run()); },
        toggleItalic() { this.cmd((e) => e.chain().toggleItalic().focus().run()); },
        toggleUnderline() { this.cmd((e) => e.chain().toggleUnderline().focus().run()); },
        toggleStrike() { this.cmd((e) => e.chain().toggleStrike().focus().run()); },
        toggleCode() { this.cmd((e) => e.chain().toggleCode().focus().run()); },

        setParagraph() { this.cmd((e) => e.chain().setParagraph().focus().run()); },
        toggleHeading(level: 1 | 2 | 3 | 4 | 5 | 6) {
          this.cmd((e) => e.chain().toggleHeading({ level }).focus().run());
        },

        toggleBulletList() { this.cmd((e) => e.chain().toggleBulletList().focus().run()); },
        toggleOrderedList() { this.cmd((e) => e.chain().toggleOrderedList().focus().run()); },
        toggleBlockquote() { this.cmd((e) => e.chain().toggleBlockquote().focus().run()); },
        setHorizontalRule() { this.cmd((e) => e.chain().setHorizontalRule().focus().run()); },

        setTextAlign(a: 'left' | 'center' | 'right' | 'justify') {
          this.cmd((e) => e.chain().setTextAlign(a).focus().run());
        },

        undo() { this.cmd((e) => e.commands.undo()); },
        redo() { this.cmd((e) => e.commands.redo()); },

        setLink() {
          const e = this.editor();
          if (!e) return;

          const current = (e.getAttributes('link') as { href?: string })?.href;
          const url = prompt('URL', current ?? '');

          if (url === null) return;

          if (!url) {
            e.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
          }

          e.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },

        addImage() {
          const url = prompt('Image URL');
          if (!url) return;
          this.editor()?.chain().focus().setImage({ src: url }).run();
        },
      };
    });
  });
}