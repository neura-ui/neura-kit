import type { UserConfig } from 'vite';

const OPTIONAL_DEPS = [
  'chart.js',
  'lottie-web',
  '@tiptap/core',
  '@tiptap/starter-kit',
  '@tiptap/extension-link',
  '@tiptap/extension-image',
  '@tiptap/extension-placeholder',
  '@tiptap/extension-text-align',
  '@tiptap/extension-underline',
  '@tiptap/extension-highlight',
] as const;

const VENDOR_CHUNKS: Record<string, string[]> = {
  'vendor-editor-tiptap': [
    '@tiptap/core',
    '@tiptap/starter-kit',
    '@tiptap/extension-link',
    '@tiptap/extension-image',
    '@tiptap/extension-placeholder',
    '@tiptap/extension-text-align',
    '@tiptap/extension-underline',
    '@tiptap/extension-highlight',
    '@tiptap/pm',
    'prosemirror-',
  ],
  'vendor-editor-editorjs': [
    '@editorjs/',
  ],
  'vendor-chart': ['chart.js'],
  'vendor-lottie': ['lottie-web'],
  'vendor-highlight': ['highlight.js'],
  'vendor-flow': ['@copyfactory/alpine-flow', 'elkjs'],
};

type ManualChunksFunction = (id: string) => string | undefined;

export function configureOptimizeDeps(config: UserConfig): void {
  config.resolve ??= {};
  config.resolve.preserveSymlinks = true;

  config.optimizeDeps ??= {};
  const include = new Set<string>(config.optimizeDeps.include ?? []);
  OPTIONAL_DEPS.forEach((dep) => include.add(dep));
  config.optimizeDeps.include = [...include];

  config.build ??= {};
  config.build.rollupOptions ??= {};
  const outputOptions =
    config.build.rollupOptions.output &&
    !Array.isArray(config.build.rollupOptions.output)
      ? config.build.rollupOptions.output
      : {};

  const existing = outputOptions.manualChunks as
    | ManualChunksFunction
    | Record<string, string[]>
    | undefined;

  outputOptions.manualChunks = (id: string): string | undefined => {
    if (id.includes('node_modules')) {
      if (typeof existing === 'function') {
        const result = existing(id);
        if (result) return result;
      }

      if (existing && typeof existing === 'object') {
        for (const [name, modules] of Object.entries(existing)) {
          if (Array.isArray(modules) && modules.some((m) => id.includes(m))) {
            return name;
          }
        }
      }

      for (const [chunkName, patterns] of Object.entries(VENDOR_CHUNKS)) {
        if (patterns.some((p) => id.includes(p))) {
          return chunkName;
        }
      }
    }

    return undefined;
  };

  config.build.rollupOptions.output = outputOptions;
}
  