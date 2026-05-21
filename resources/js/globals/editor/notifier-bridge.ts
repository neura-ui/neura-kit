import '../types';

type ToastType = 'success' | 'error' | 'warning' | 'info';

type EditorNotifierStyle = 'success' | 'error' | 'warning' | 'info' | 'default';

interface EditorNotifierOptions {
  message: string;
  style?: EditorNotifierStyle;
  time?: number;
}

interface EditorWithNotifier {
  notifier?: {
    show: (options: EditorNotifierOptions | string) => void;
  };
}

function mapNotifierStyle(style: EditorNotifierStyle | undefined): ToastType {
  switch (style) {
    case 'success':
      return 'success';
    case 'error':
      return 'error';
    case 'warning':
      return 'warning';
    case 'info':
      return 'info';
    default:
      return 'info';
  }
}

/**
 * Show a toast using the Neura Kit notification system.
 */
export function notifyEditor(
  type: ToastType,
  content: string,
  duration = 5000
): void {
  if (!content) {
    return;
  }

  if (typeof window !== 'undefined' && window.NeuraKitToast?.show) {
    window.NeuraKitToast.show(content, type, duration);
    return;
  }

  if (typeof window !== 'undefined') {
    window.dispatchEvent(
      new CustomEvent('notify', {
        detail: { content, type, duration },
      })
    );
  }
}

/**
 * Route Editor.js native notifier calls to Neura Kit toasts.
 */
export function patchEditorNotifier(editor: EditorWithNotifier): void {
  if (!editor?.notifier?.show) {
    return;
  }

  editor.notifier.show = (options: EditorNotifierOptions | string) => {
    const message = typeof options === 'string' ? options : options.message;

    if (!message) {
      return;
    }

    const style = typeof options === 'string' ? 'default' : options.style;
    const duration = typeof options === 'string' ? 5000 : options.time ?? 5000;
    const type = mapNotifierStyle(style);
    const content =
      type === 'error' ? editorUploadErrorMessage(new Error(message)) : message;

    notifyEditor(type, content, duration);
  };
}

/**
 * User-facing upload error message (translated when possible).
 */
export function editorUploadErrorMessage(error: unknown): string {
  const fallback =
    typeof window !== 'undefined' && window.t
      ? window.t('uploadFailed')
      : 'Upload failed';

  if (!(error instanceof Error) || !error.message) {
    return fallback;
  }

  const message = error.message;

  if (message.includes('exceeds') || message.includes('10MB')) {
    return typeof window !== 'undefined' && window.t
      ? window.t('fileTooLarge')
      : message;
  }

  if (message.includes('Invalid file type')) {
    return typeof window !== 'undefined' && window.t
      ? window.t('invalidFileType')
      : message;
  }

  if (message.includes('Network error')) {
    return typeof window !== 'undefined' && window.t
      ? window.t('networkError')
      : message;
  }

  if (
    message.includes('status 401') ||
    message.includes('Unauthorized') ||
    message.includes('Unauthenticated')
  ) {
    return typeof window !== 'undefined' && window.t
      ? window.t('unauthorized')
      : message;
  }

  if (message.includes('status 413') || message.includes('too large')) {
    return typeof window !== 'undefined' && window.t
      ? window.t('fileTooLarge')
      : message;
  }

  return message.length > 240 ? fallback : message;
}
