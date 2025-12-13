import './types';

type ToastType = 'success' | 'error' | 'warning' | 'info';

interface ToastDetail {
  content: string;
  type: ToastType;
  duration: number;
}

if (typeof window !== 'undefined') {
  window.NeuraKitToast = {
    success(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'success', duration },
        })
      );
    },

    error(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'error', duration },
        })
      );
    },

    warning(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'warning', duration },
        })
      );
    },

    info(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'info', duration },
        })
      );
    },

    show(content: string, type: ToastType = 'info', duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type, duration },
        })
      );
    },
  };
}
