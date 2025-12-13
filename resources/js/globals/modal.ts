import './types';

type LivewireComponent = {
  call(method: string, ...args: unknown[]): void;
};

const ModalManagerCache = {
  element: null as HTMLElement | null,
  wireId: null as string | null,
  instance: null as LivewireComponent | null,

  invalidate(): void {
    this.element = null;
    this.wireId = null;
    this.instance = null;
  },

  get(): LivewireComponent | null {
    if (this.instance) return this.instance;

    if (!this.element) {
      this.element = document.querySelector('[x-data*="modalManager"]') as HTMLElement;
    }

    if (!this.element) return null;

    if (!this.wireId) {
      this.wireId = this.element.getAttribute('wire:id');
    }

    if (!this.wireId) return null;

    this.instance =
      typeof window.Livewire !== 'undefined' && this.wireId
        ? window.Livewire.find(this.wireId)
        : null;
    return this.instance;
  },
};

if (typeof document !== 'undefined') {
  document.addEventListener('livewire:navigated', () => ModalManagerCache.invalidate());
}

if (typeof window !== 'undefined') {
  window.NeuraKitModal = {
    open(
      component: string,
      args: Record<string, unknown> = {},
      modalAttributes: Record<string, unknown> = {}
    ): void {
      const manager = ModalManagerCache.get();
      if (manager) {
        manager.call('openModal', component, args, modalAttributes);
      }
    },

    close(force = false, skipPreviousModals = 0, destroySkipped = false): void {
      const manager = ModalManagerCache.get();
      if (manager) {
        manager.call('closeModal', force, skipPreviousModals, destroySkipped);
      }
    },
  };
}

if (typeof document !== 'undefined') {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('modalManager', () =>
    ({
      show: false,
      activeComponent: null as string | null,
      showActiveComponent: false,
      _attrs: null as { id: string; attrs: Record<string, unknown> } | null,
      _cleanupId: 0,
      _cleanupTimeout: null as ReturnType<typeof setTimeout> | null,
      _prevFocus: null as HTMLElement | null,
      _main: null as HTMLElement | null,
      $refs: {} as Record<string, HTMLElement | null>,
      $el: null as HTMLElement | null,
      $wire: undefined as {
        on: (event: string, handler: (payload?: unknown) => void) => void;
        components?: Record<string, { modalAttributes?: Record<string, unknown> }>;
      } | undefined,
      $watch: function (property: string, callback: (value: unknown) => void): void {
        // Implemented by Alpine
      },
      $nextTick: function (callback: () => void): void {
        // Implemented by Alpine
      },

    init(): void {
      this._main = document.querySelector('[data-slot="main"], main, [data-slot="layout"]') as HTMLElement;

      this.$watch('show', (value: unknown) => {
        const showValue = value as boolean;
        if (showValue) {
          this._prevFocus = document.activeElement as HTMLElement;
          document.body.style.overflow = 'hidden';
          this._main?.setAttribute('inert', '');
        } else {
          document.body.style.overflow = '';
          this._main?.removeAttribute('inert');
          if (this._prevFocus?.focus) {
            this.$nextTick(() => {
              this._prevFocus?.focus();
              this._prevFocus = null;
            });
          }
        }
      });

      const handleActive = (payload?: unknown): void => {
        const e = payload as Event | CustomEvent | { id?: string; modalAttributes?: Record<string, unknown> } | string;
        const id = (e as CustomEvent)?.detail ? ((e as CustomEvent).detail as { id?: string })?.id ?? (e as CustomEvent).detail : (e as { id?: string })?.id ?? (e as string);
        const attrs = (e as CustomEvent)?.detail ? ((e as CustomEvent).detail as { modalAttributes?: Record<string, unknown> })?.modalAttributes : (e as { modalAttributes?: Record<string, unknown> })?.modalAttributes ?? null;

        if (this._cleanupTimeout) {
          clearTimeout(this._cleanupTimeout);
          this._cleanupTimeout = null;
        }

        this.activeComponent = id as string;
        this._attrs = attrs ? { id: id as string, attrs } : null;
        this.showActiveComponent = true;

        if (this.show) {
          this.$nextTick(() => this.focusModal());
        } else {
          this.$nextTick(() => {
            if (this.activeComponent === id) {
              this.showActiveComponent = true;
            }
          });
        }
      };

      const handleOpen = (): void => {
        if (this._cleanupTimeout) {
          clearTimeout(this._cleanupTimeout);
          this._cleanupTimeout = null;
        }
        this.setShow(true);
        if (this.showActiveComponent) {
          this.$nextTick(() => this.focusModal());
        }
      };

      const handleClose = (): void => this.setShow(false);

      if (this.$wire?.on) {
        this.$wire.on('activeModalComponentChanged', handleActive);
        this.$wire.on('openModal', handleOpen);
        this.$wire.on('closeModal', handleClose);
      } else {
        window.addEventListener('activeModalComponentChanged', handleActive);
        window.addEventListener('openModal', handleOpen);
        window.addEventListener('closeModal', handleClose);
      }
    },

    focusModal(): void {
      const id = this.activeComponent;
      if (!id) return;

      const container =
        this.$refs[id] ||
        (this.$el?.querySelector(`[data-modal-id="${id}"]`) as HTMLElement | null);
      if (!container) return;

      const focusable = container.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      ) as HTMLElement;

      (focusable || container).focus();
    },

    getAttrs(): Record<string, unknown> | null {
      if (!this.activeComponent) return null;
      if (this._attrs?.id === this.activeComponent) return this._attrs.attrs;

      const comp = this.$wire?.components?.[this.activeComponent];
      if (!comp) return null;

      this._attrs = {
        id: this.activeComponent,
        attrs: comp.modalAttributes || {},
      };
      return this._attrs.attrs || {};
    },

    setShow(value: boolean): void {
      const cleanupId = ++this._cleanupId;
      this.show = value;

      if (!value) {
        this.showActiveComponent = false;
        if (this._cleanupTimeout) {
          clearTimeout(this._cleanupTimeout);
        }
        this._cleanupTimeout = setTimeout(() => {
          if (this._cleanupId === cleanupId && !this.show) {
            this.activeComponent = null;
            this._attrs = null;
          }
          this._cleanupTimeout = null;
        }, 200);
      } else {
        if (this._cleanupTimeout) {
          clearTimeout(this._cleanupTimeout);
          this._cleanupTimeout = null;
        }
      }
    },

    closeModalOnClickAway(): void {
      if (this.activeComponent && this.getAttrs()?.closeOnClickAway) {
        window.NeuraKitModal?.close(false, 0, false);
      }
    },

    closeModalOnEscape(): void {
      if (!this.activeComponent) return;

      const attrs = this.getAttrs();
      if (attrs?.closeOnEscape) {
        window.NeuraKitModal?.close(
          (attrs.closeOnEscapeIsForceful as boolean) || false,
          0,
          false
        );
      }
    },
    } as any)
  );
  });
}

