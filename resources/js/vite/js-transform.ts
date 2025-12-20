import { resolve } from 'path';
import { existsSync } from 'fs';
import type { TransformResult } from '../core/types';

export function transformJs(code: string): TransformResult | null {
  if (code.includes('neura-kit/resources/js/app.ts') || code.includes('neura-ui/neura-kit/resources/js/app.ts')) {
    return null;
  }

  const vendorPath = resolve(
    process.cwd(),
    'vendor/neura-ui/neura-kit/resources/js/app.ts'
  );

  const localPath = resolve(
    process.cwd(),
    'neura-kit/resources/js/app.ts'
  );

  let importPath: string;
  
  if (existsSync(localPath)) {
    importPath = '../../neura-kit/resources/js/app.ts';
  } else if (existsSync(vendorPath)) {
    importPath = '../../vendor/neura-ui/neura-kit/resources/js/app.ts';
  } else {
    importPath = 'neura-ui/neura-kit/resources/js/app.ts';
  }

  const bootstrap = "import './bootstrap'";
  const idx = code.indexOf(bootstrap);

  if (idx !== -1) {
    const pos = code.indexOf('\n', idx) + 1;
    return {
      code:
        code.slice(0, pos) +
        `import '${importPath}';\n` +
        code.slice(pos),
      map: null,
    };
  }

  return {
    code: `import '${importPath}';\n${code}`,
    map: null,
  };
}
