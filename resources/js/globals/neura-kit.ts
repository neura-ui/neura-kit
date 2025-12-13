import './types';

interface ToastConfig {
  content: string;
  type: 'info' | 'success' | 'warning' | 'error';
  duration: number;
}

interface ModalConfig {
  name: string | null;
  args: Record<string, unknown>;
  attrs: Record<string, unknown>;
}

interface DialogConfig {
  type: 'info' | 'success' | 'warning' | 'danger';
  title: string;
  message: string;
  confirmText: string | null;
  cancelText: string | null;
  showCancel: boolean;
  confirmVariant: 'primary' | 'success' | 'danger';
  size: string;
  onConfirm: (() => void) | null;
  onCancel: (() => void) | null;
}

if (typeof window !== 'undefined') {
  window.NeuraKit = {
    _toast: { content: '', type: 'info', duration: 4000 },
    _modal: { name: null, args: {}, attrs: {} },
    _dialog: {
      type: 'info',
      title: '',
      message: '',
      confirmText: null,
      cancelText: null,
      showCancel: true,
      confirmVariant: 'primary',
      size: 'sm',
      onConfirm: null,
      onCancel: null,
    },

    toast(content?: string) {
      const t: ToastConfig = { ...this._toast, content: content || '' };
      return {
        duration: (ms: number) => {
          t.duration = ms;
          return window.NeuraKit;
        },
        success: (c?: string) => {
          if (c) t.content = c;
          t.type = 'success';
          if (t.content && window.NeuraKitToast?.show) {
            window.NeuraKitToast.show(t.content, t.type, t.duration);
          }
        },
        error: (c?: string) => {
          if (c) t.content = c;
          t.type = 'error';
          if (t.content && window.NeuraKitToast?.show) {
            window.NeuraKitToast.show(t.content, t.type, t.duration);
          }
        },
        warning: (c?: string) => {
          if (c) t.content = c;
          t.type = 'warning';
          if (t.content && window.NeuraKitToast?.show) {
            window.NeuraKitToast.show(t.content, t.type, t.duration);
          }
        },
        info: (c?: string) => {
          if (c) t.content = c;
          t.type = 'info';
          if (t.content && window.NeuraKitToast?.show) {
            window.NeuraKitToast.show(t.content, t.type, t.duration);
          }
        },
      };
    },

    modal(name?: string) {
      const m: ModalConfig = { name: name || null, args: {}, attrs: {} };
      return {
        with: (a: Record<string, unknown>) => {
          Object.assign(m.args, a);
          return window.NeuraKit;
        },
        attrs: (a: Record<string, unknown>) => {
          Object.assign(m.attrs, a);
          return window.NeuraKit;
        },
        maxWidth: (w: string) => {
          m.attrs.maxWidth = w;
          return window.NeuraKit;
        },
        open: (a?: Record<string, unknown>) => {
          if (a) Object.assign(m.args, a);
          if (m.name && window.NeuraKitModal?.open) {
            window.NeuraKitModal.open(m.name, m.args, m.attrs);
          }
        },
        close: (force?: boolean) => {
          window.NeuraKitModal?.close(force);
        },
      };
    },

    dialog(title?: string) {
      const d: DialogConfig = {
        ...this._dialog,
        title: title || '',
        confirmText: window.t?.('confirm') || 'Confirm',
        cancelText: window.t?.('cancel') || 'Cancel',
      };
      const self = {
        title: (t: string) => {
          d.title = t;
          return self;
        },
        message: (m: string) => {
          d.message = m;
          return self;
        },
        info: () => {
          d.type = 'info';
          d.confirmVariant = 'primary';
          return self;
        },
        success: () => {
          d.type = 'success';
          d.confirmVariant = 'success';
          return self;
        },
        warning: () => {
          d.type = 'warning';
          d.confirmVariant = 'primary';
          return self;
        },
        danger: () => {
          d.type = 'danger';
          d.confirmVariant = 'danger';
          return self;
        },
        confirmText: (t: string) => {
          d.confirmText = t;
          return self;
        },
        cancelText: (t: string) => {
          d.cancelText = t;
          return self;
        },
        hideCancel: () => {
          d.showCancel = false;
          return self;
        },
        size: (s: string) => {
          d.size = s;
          return self;
        },
        onConfirm: (fn: () => void) => {
          d.onConfirm = fn;
          return self;
        },
        onCancel: (fn: () => void) => {
          d.onCancel = fn;
          return self;
        },
        show: () => {
          if (d.title) {
            dispatchEvent(new CustomEvent<DialogConfig>('dialog', { detail: d }));
          }
        },
      };
      return self;
    },
  } as any;
}

