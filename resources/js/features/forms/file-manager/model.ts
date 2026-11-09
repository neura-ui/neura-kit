/**
 * The file manager's entries, worked out from the nodes an app hands in: what kind of
 * file each is, the group its type filter puts it in, its labels, the picture its card
 * previews, and its dates said the way people say them. No DOM and no local import, so it
 * runs in Node; the translation function comes in as `phrases`.
 */

export type FileManagerKind =
  | 'folder' | 'image' | 'video' | 'audio' | 'pdf' | 'sheet' | 'doc' | 'archive' | 'code' | 'text' | 'file';

/** What the type filter sorts files into. */
export type FileManagerGroup = 'folder' | 'image' | 'document' | 'video' | 'audio' | 'archive' | 'code';

export type FileManagerNode = {
  id: string;
  name: string;
  type?: 'folder' | 'file';
  size?: number;
  modified?: string | number;
  mime?: string;
  url?: string;
  thumbnail?: string;
  /** Length of a video or a sound, in seconds. */
  duration?: number;
  width?: number;
  height?: number;
  starred?: boolean;
  children?: FileManagerNode[];
  [key: string]: any;
};

/** The picture a file card shows: a picture, a video still, or a tile with the type's icon. */
export type EntryPreview = { kind: 'image' | 'video' | 'tile'; src: string | null; duration: string | null };

export type FileManagerEntry = FileManagerNode & {
  id: string;
  name: string;
  isFolder: boolean;
  kind: FileManagerKind;
  group: FileManagerGroup;
  extension: string;
  /** The type in words: "Folder", "PDF", "Spreadsheet"… */
  typeLabel: string;
  /** The short label on a tile: the extension, upper case. */
  badge: string;
  sizeLabel: string;
  modifiedLabel: string;
  /** The full date, for a tooltip and the details panel. */
  modifiedTitle: string;
  modifiedAt: number;
  count: number;
  preview: EntryPreview;
  /** Folder chain holding this entry — only filled by a whole-tree search. */
  pathIds?: string[];
  pathNames?: string[];
};

export type Phrases = { t(key: string, fallback: string, params?: Record<string, string>): string };

const KIND_BY_EXTENSION: Record<string, FileManagerKind> = {
  jpg: 'image', jpeg: 'image', png: 'image', gif: 'image', webp: 'image', avif: 'image',
  svg: 'image', bmp: 'image', heic: 'image', ico: 'image', tif: 'image', tiff: 'image',
  mp4: 'video', webm: 'video', mov: 'video', avi: 'video', mkv: 'video',
  mp3: 'audio', wav: 'audio', ogg: 'audio', flac: 'audio', m4a: 'audio',
  pdf: 'pdf',
  xls: 'sheet', xlsx: 'sheet', csv: 'sheet', numbers: 'sheet',
  doc: 'doc', docx: 'doc', rtf: 'doc', odt: 'doc', pages: 'doc',
  zip: 'archive', rar: 'archive', '7z': 'archive', tar: 'archive', gz: 'archive',
  js: 'code', ts: 'code', json: 'code', php: 'code', html: 'code', css: 'code',
  vue: 'code', blade: 'code', py: 'code', rb: 'code', go: 'code', sh: 'code',
  txt: 'text', md: 'text', log: 'text',
};

const GROUP_BY_KIND: Record<FileManagerKind, FileManagerGroup> = {
  folder: 'folder', image: 'image', video: 'video', audio: 'audio', archive: 'archive', code: 'code',
  pdf: 'document', sheet: 'document', doc: 'document', text: 'document', file: 'document',
};

/** The type in words, with its translation key. */
const TYPE_LABELS: Record<FileManagerKind, [string, string]> = {
  folder: ['fmTypeFolder', 'Folder'],
  image: ['fmTypeImage', 'Image'],
  video: ['fmTypeVideo', 'Video'],
  audio: ['fmTypeAudio', 'Audio'],
  pdf: ['fmTypePdf', 'PDF'],
  sheet: ['fmTypeSheet', 'Spreadsheet'],
  doc: ['fmTypeDoc', 'Document'],
  archive: ['fmTypeArchive', 'Archive'],
  code: ['fmTypeCode', 'Code'],
  text: ['fmTypeText', 'Text'],
  file: ['fmTypeFile', 'File'],
};

/** Icon name resolved against the Heroicons set used by <neura::icon>. */
export const ICONS: Record<FileManagerKind, string> = {
  folder: 'folder',
  image: 'photo',
  video: 'film',
  audio: 'musical-note',
  pdf: 'document-text',
  sheet: 'table-cells',
  doc: 'document-text',
  archive: 'archive-box',
  code: 'code-bracket',
  text: 'document-text',
  file: 'document',
};

export function formatSize(bytes?: number): string {
  if (bytes === undefined || bytes === null || bytes < 0) return '—';
  if (bytes === 0) return '0 B';

  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
  const value = bytes / Math.pow(1024, i);

  return `${value >= 100 || i === 0 ? Math.round(value) : Math.round(value * 10) / 10} ${units[i]}`;
}

/** A length in seconds as 0:42 or 1:02:05; null when there is none. */
export function formatDuration(seconds?: number): string | null {
  if (seconds === undefined || seconds === null || !Number.isFinite(seconds) || seconds < 0) return null;
  const total = Math.round(seconds);
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const rest = String(total % 60).padStart(2, '0');

  return hours ? `${hours}:${String(minutes).padStart(2, '0')}:${rest}` : `${minutes}:${rest}`;
}

export function toTimestamp(value?: string | number): number {
  if (value === undefined || value === null || value === '') return 0;
  if (typeof value === 'number') return value < 1e12 ? value * 1000 : value;

  const parsed = Date.parse(value);

  return Number.isNaN(parsed) ? 0 : parsed;
}

const sameDay = (a: Date, b: Date): boolean =>
  a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

/**
 * A date as people say it: now, 5 minutes ago, 2 hours ago (the same day), yesterday,
 * then the day and month — with the year when it is not this one.
 */
export function relativeDate(at: number, now: number, locale?: string): string {
  if (!at) return '—';

  const diff = now - at;
  const when = new Date(at);
  const today = new Date(now);
  const relative = new Intl.RelativeTimeFormat(locale || undefined, { numeric: 'auto' });

  if (diff >= -60_000 && diff < 60_000) return relative.format(0, 'second');
  if (diff > 0 && diff < 3_600_000) return relative.format(-Math.round(diff / 60_000), 'minute');
  if (diff > 0 && sameDay(when, today)) return relative.format(-Math.round(diff / 3_600_000), 'hour');

  const yesterday = new Date(now);
  yesterday.setDate(yesterday.getDate() - 1);
  if (sameDay(when, yesterday)) return relative.format(-1, 'day');

  return when.toLocaleDateString(locale || undefined, {
    day: 'numeric',
    month: 'short',
    year: when.getFullYear() === today.getFullYear() ? undefined : 'numeric',
  });
}

/** The whole date and time, for a tooltip. */
export function fullDate(at: number, locale?: string): string {
  if (!at) return '';

  return new Date(at).toLocaleString(locale || undefined, { dateStyle: 'long', timeStyle: 'short' });
}

/** The part after the last dot, lower case; none for a name like `.env`. */
export function extensionOf(name: string): string {
  const dot = name.lastIndexOf('.');

  return dot > 0 && dot < name.length - 1 ? name.slice(dot + 1).toLowerCase() : '';
}

export function detectKind(node: FileManagerNode): FileManagerKind {
  if (node.type === 'folder' || Array.isArray(node.children)) return 'folder';

  const mime = (node.mime || '').toLowerCase();
  if (mime.startsWith('image/')) return 'image';
  if (mime.startsWith('video/')) return 'video';
  if (mime.startsWith('audio/')) return 'audio';
  if (mime === 'application/pdf') return 'pdf';

  return KIND_BY_EXTENSION[extensionOf(node.name)] ?? 'file';
}

/**
 * An address the preview may load, or none: never one that runs script (`javascript:`,
 * `vbscript:`, an HTML `data:` page), which an `<iframe>` or a link would execute.
 */
export function safeUrl(url?: string | null): string | undefined {
  if (typeof url !== 'string') return undefined;
  const value = url.trim();
  if (!value) return undefined;

  // Browsers ignore whitespace and control characters inside a scheme ("java\tscript:").
  const scheme = value.replace(/[\u0000-\u0020]/g, '').toLowerCase();
  if (scheme.startsWith('javascript:') || scheme.startsWith('vbscript:')) return undefined;
  if (scheme.startsWith('data:') && !/^data:(image\/(png|jpe?g|gif|webp|avif|bmp)|video\/|audio\/|application\/pdf|text\/plain)/.test(scheme)) {
    return undefined;
  }

  return value;
}

/** The picture a card shows: the app's thumbnail, an image's own address, or a tile. */
function previewOf(node: FileManagerNode, kind: FileManagerKind): EntryPreview {
  if (kind === 'video') {
    return node.thumbnail
      ? { kind: 'video', src: node.thumbnail, duration: formatDuration(node.duration) }
      : { kind: 'tile', src: null, duration: formatDuration(node.duration) };
  }
  if (node.thumbnail) return { kind: 'image', src: node.thumbnail, duration: null };
  if (kind === 'image' && node.url) return { kind: 'image', src: node.url, duration: null };

  return { kind: 'tile', src: null, duration: null };
}

export function decorate(source: FileManagerNode, locale: string | undefined, now: number, phrases: Phrases): FileManagerEntry {
  const node = { ...source, url: safeUrl(source.url), thumbnail: safeUrl(source.thumbnail) };
  const kind = detectKind(node);
  const isFolder = kind === 'folder';
  const modifiedAt = toTimestamp(node.modified);
  const [labelKey, labelFallback] = TYPE_LABELS[kind];
  const extension = isFolder ? '' : extensionOf(node.name).toUpperCase();

  return {
    ...node,
    id: String(node.id ?? node.name),
    name: node.name,
    isFolder,
    kind,
    group: GROUP_BY_KIND[kind],
    extension,
    typeLabel: phrases.t(labelKey, labelFallback),
    badge: extension,
    sizeLabel: isFolder
      ? phrases.t('itemsCount', '{count} items', { count: String(node.children?.length ?? 0) })
      : formatSize(node.size),
    modifiedAt,
    modifiedLabel: relativeDate(modifiedAt, now, locale),
    modifiedTitle: fullDate(modifiedAt, locale),
    count: node.children?.length ?? 0,
    preview: isFolder ? { kind: 'tile', src: null, duration: null } : previewOf(node, kind),
  };
}
