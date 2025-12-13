import type { TransformResult } from '../core/types';

function extractThemeContent(themeCSS: string): string {
  const match = themeCSS.match(/@theme\s*\{([\s\S]*?)\}/);
  return match ? match[1].trim() : '';
}

export function transformCss(
  code: string,
  themeCSS: string
): TransformResult | null {
  let modified = false;
  let output = code;

  const tailwindImport = "@import 'tailwindcss'";
  const idx = code.indexOf(tailwindImport);

  if (idx !== -1) {
    const themeContent = extractThemeContent(themeCSS);
    const existingThemeMatch = output.match(/@theme\s*\{([\s\S]*?)\}/);

    if (existingThemeMatch) {
      const existingContent = existingThemeMatch[1];
      const lines = themeContent.split('\n').filter(line => line.trim());
      const newLines: string[] = [];

      for (const line of lines) {
        const varMatch = line.match(/--([\w-]+):/);
        if (varMatch && !existingContent.includes(`--${varMatch[1]}:`)) {
          newLines.push(line);
        }
      }

      if (newLines.length > 0) {
        const insertContent = '\n  ' + newLines.join('\n  ');
        const themeEnd = output.indexOf('}', existingThemeMatch.index!);
        output = output.slice(0, themeEnd) + insertContent + '\n' + output.slice(themeEnd);
        modified = true;
      }
    } else {
      const insertAt = output.indexOf('\n', idx) + 1;
      const inject = `\n/* NeuraKit Theme */\n${themeCSS}\n`;
    output = output.slice(0, insertAt) + inject + output.slice(insertAt);
    modified = true;
    }

    if (!output.includes('neura-kit/resources/css/app.css')) {
      const themeEnd = output.indexOf('@theme');
      if (themeEnd !== -1) {
        const blockEnd = output.indexOf('}', themeEnd) + 1;
        output = output.slice(0, blockEnd) + `\n@import '../../vendor/neura/neura-kit/resources/css/app.css';\n` + output.slice(blockEnd);
        modified = true;
      }
    }
  }

  if (!output.includes('neura-kit/resources/views')) {
    const lastSource = output.lastIndexOf('@source');
    if (lastSource !== -1) {
      const pos = output.indexOf('\n', lastSource) + 1;
      output =
        output.slice(0, pos) +
        `@source '../../vendor/neura/neura-kit/resources/views/**/*.blade.php';\n` +
        output.slice(pos);
      modified = true;
    }
  }

  if (!output.includes('@custom-variant dark')) {
    output += `\n@custom-variant dark (&:where(.dark, .dark *));\n`;
    modified = true;
  }

  return modified ? { code: output, map: null } : null;
}
  