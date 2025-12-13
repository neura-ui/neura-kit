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

  outputOptions.manualChunks ??= undefined;

  const existing = outputOptions.manualChunks as
    | ManualChunksFunction
    | Record<string, string[]>
    | undefined;

  outputOptions.manualChunks = (id: string): string | undefined => {
    if (!id.includes('node_modules')) return undefined;

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

    return undefined;
  };

  config.build.rollupOptions.output = outputOptions;
}
  