import type { UserConfig } from 'vite';

const NEURA_JS = '/neura-kit/resources/js/';

type ManualChunks =
  | ((id: string) => string | void | undefined | null)
  | Record<string, string[]>
  | undefined;

type OutputOptions = {
  manualChunks?: ManualChunks;
  chunkFileNames?: string | ((chunk: { facadeModuleId: string | null }) => string);
  [key: string]: unknown;
};

/**
 * Collapse the kit's lazy features into a handful of named chunks.
 *
 * One dynamic `import()` per feature would otherwise emit a dozen tiny
 * files (context-menu at ~1.6 kB, etc.). Grouping by family keeps HTTP
 * overhead down while still isolating the two heavy engines.
 */
const FEATURE_CHUNKS: ReadonlyArray<{ name: string; match: (path: string) => boolean }> = [
  {
    name: 'neura-editor',
    match: (path) => path.includes('/features/editor/'),
  },
  {
    name: 'neura-flow',
    match: (path) => path.includes('/features/flow/'),
  },
  {
    name: 'neura-forms',
    match: (path) => path.includes('/features/forms/'),
  },
  {
    // modal / sideover / spotlight stay in the entry (eager via app.ts).
    name: 'neura-overlays',
    match: (path) =>
      path.includes('/features/overlays/command') ||
      path.includes('/features/overlays/context-menu'),
  },
  {
    name: 'neura-widgets',
    match: (path) =>
      path.includes('/features/data/') ||
      path.includes('/features/chart/') ||
      // orb is eager (preloader); keep other media features lazy if added later
      (path.includes('/features/media/') && !path.includes('/features/media/orb')) ||
      path.includes('/components/'),
  },
];

function resolveExistingChunk(id: string, existing: ManualChunks): string | undefined {
  if (typeof existing === 'function') {
    const result = existing(id);
    return typeof result === 'string' ? result : undefined;
  }

  if (!existing) return undefined;

  for (const [name, modules] of Object.entries(existing)) {
    if (modules.some((m) => id.includes(m))) return name;
  }

  return undefined;
}

function neuraFeatureChunk(id: string): string | undefined {
  const normalized = id.replace(/\\/g, '/');
  if (!normalized.includes(NEURA_JS)) return undefined;

  for (const { name, match } of FEATURE_CHUNKS) {
    if (match(normalized)) return name;
  }

  return undefined;
}

function ensureOutputOptions(config: UserConfig): OutputOptions {
  config.build ??= {};
  config.build.rollupOptions ??= {};

  const current = config.build.rollupOptions.output;
  const output: OutputOptions =
    current && !Array.isArray(current) ? (current as OutputOptions) : {};

  config.build.rollupOptions.output = output;
  return output;
}

export function configureOptimizeDeps(config: UserConfig): void {
  config.resolve ??= {};
  config.resolve.preserveSymlinks = true;

  const output = ensureOutputOptions(config);
  const existing = output.manualChunks;

  output.manualChunks = (id: string): string | undefined => {
    if (id.includes('node_modules')) {
      return resolveExistingChunk(id, existing);
    }

    return neuraFeatureChunk(id);
  };

  output.chunkFileNames ??= (chunk) => {
    // Prefer the manualChunks name when Rollup already labelled the chunk.
    if (chunk.name && chunk.name.startsWith('neura-')) {
      return `assets/${chunk.name}-[hash].js`;
    }

    return 'assets/[name]-[hash].js';
  };
}
