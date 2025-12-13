import { existsSync, readFileSync } from 'fs';
import { resolve } from 'path';
import { pathToFileURL } from 'url';
import type { NeuraKitConfig } from './types';

const CONFIG_FILES = [
  'neura-kit.config.js',
  'neura-kit.config.mjs',
  'neura-kit.config.json',
] as const;

export async function loadNeuraConfig(): Promise<NeuraKitConfig> {
  for (const file of CONFIG_FILES) {
    const path = resolve(process.cwd(), file);
    if (!existsSync(path)) continue;

    try {
      if (file.endsWith('.json')) {
        const content = JSON.parse(readFileSync(path, 'utf8'));
        return content as NeuraKitConfig;
      }

      const mod = await import(pathToFileURL(path).href + `?t=${Date.now()}`);
      return (mod.default ?? mod) as NeuraKitConfig;
    } catch (error) {
      console.warn(`⚠️  NeuraKit: Error loading config from ${file}:`, error);
      continue;
    }
  }

  return {};
}
