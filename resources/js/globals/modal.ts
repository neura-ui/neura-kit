import './types';
export {}; // top-level ONLY

/* -------------------------------------------------------------------------- */
/* Environment guard                                                          */
/* -------------------------------------------------------------------------- */
const isBrowser =
    typeof window !== 'undefined' &&
    typeof document !== 'undefined';

if (!isBrowser) {
    // SSR / Node: do nothing
} else {
    /* ------------------------------------------------------------------------ */
    /* Types                                                                    */
    /* ------------------------------------------------------------------------ */
    type LivewireComponent = {
        call(method: string, ...args: unknown[]): void;
        on?(event: string, callback: (...args: any[]) => void): void;
    };

    type ModalAttrs = Record<string, unknown>;

    type ModalActivatePayload =
        | { id: string; modalAttributes?: ModalAttrs }
        | { detail?: { id?: string; modalAttributes?: ModalAttrs } }
        | string;

    type UiOpenDetail = { component?: string; attrs?: ModalAttrs } | undefined;
    type UiCloseDetail = { force?: boolean; skipPreviousModals?: number; destroySkipped?: boolean } | undefined;

    /* ------------------------------------------------------------------------ */
    /* Boot guard                                                                */
    /* ------------------------------------------------------------------------ */
    const NK_BOOT = ((window as any).__NK_MODAL_BOOT__ ??= { booted: false });

    /* ------------------------------------------------------------------------ */
    /* ModalManager cache (fast path)                                            */
    /* ------------------------------------------------------------------------ */
    const ModalManagerCache = {
        element: null as HTMLElement | null,
        wireId: null as string | null,
        instance: null as LivewireComponent | null,

        invalidate() {
            this.element = null;
            this.wireId = null;
            this.instance = null;
        },

        get(): LivewireComponent | null {
            if (this.instance) return this.instance;

            // Find the modal manager root once
            const el =
                this.element ??
                (this.element = document.querySelector<HTMLElement>('[x-data*="modalManager"]'));

            if (!el) return null;

            const wireId = this.wireId ?? (this.wireId = el.getAttribute('wire:id'));
            if (!wireId) return null;

            const lw = (window as any).Livewire;
            if (!lw?.find) return null;

            this.instance = lw.find(wireId) as LivewireComponent | null;
            return this.instance;
        },
    };

    /* ------------------------------------------------------------------------ */
    /* Global UI events (instant open/close, no network)                         */
    /* ------------------------------------------------------------------------ */
    const UI_OPEN_EVENT = 'nk-modal-ui-open';
    const UI_CLOSE_EVENT = 'nk-modal-ui-close';

    /* ------------------------------------------------------------------------ */
    /* Global API (stable surface)                                               */
    /* ------------------------------------------------------------------------ */
    if (!(window as any).NeuraKitModal) {
        (window as any).NeuraKitModal = {
            /**
             * Open modal - primarily used for JS-initiated opens.
             * For PHP-initiated opens via ModalCall, the dispatch happens server-side.
             * 
             * This method:
             * 1) Opens UI immediately (no round-trip)
             * 2) Syncs Livewire state via call (for JS-only usage)
             */
            open(
                component: string,
                args: Record<string, unknown> = {},
                attrs: Record<string, unknown> = {}
            ) {
                // Instant UI open with loading state
                window.dispatchEvent(new CustomEvent<UiOpenDetail>(UI_OPEN_EVENT, { detail: { component, attrs } }));

                // Livewire sync
                ModalManagerCache.get()?.call('openModal', component, args, attrs);
            },

            /**
             * Open UI only (no Livewire call) - used when PHP handles the dispatch.
             */
            openUi(component: string, attrs: Record<string, unknown> = {}) {
                window.dispatchEvent(new CustomEvent<UiOpenDetail>(UI_OPEN_EVENT, { detail: { component, attrs } }));
            },

            /**
             * Close modal:
             * 1) Close UI immediately (no round-trip)
             * 2) Sync Livewire state
             */
            close(force = false, skip = 0, destroy = false) {
                // Instant UI close
                window.dispatchEvent(
                    new CustomEvent<UiCloseDetail>(UI_CLOSE_EVENT, {
                        detail: { force, skipPreviousModals: skip, destroySkipped: destroy },
                    })
                );

                // Livewire sync
                ModalManagerCache.get()?.call('closeModal', force, skip, destroy);
            },

            /**
             * Go back to the previous modal in the stack.
             * Destroys the current modal and shows the previous one.
             */
            goBack() {
                this.close(true, 0, false);
            },
        };
    }

    /* ------------------------------------------------------------------------ */
    /* Boot-once listeners                                                       */
    /* ------------------------------------------------------------------------ */
    if (!NK_BOOT.booted) {
        NK_BOOT.booted = true;

        // Livewire navigation can replace DOM: invalidate cached wire:id/element
        document.addEventListener('livewire:navigated', () => ModalManagerCache.invalidate(), { passive: true });

        // If you dispatch "modal-close" from anywhere, keep it supported
        window.addEventListener(
            'modal-close',
            (e: Event) => {
                const d = (e as CustomEvent)?.detail ?? {};
                (window as any).NeuraKitModal?.close(
                    Boolean(d.force),
                    Number(d.skipPreviousModals ?? 0),
                    Boolean(d.destroySkipped)
                );
            },
            { passive: true }
        );

        /* ---------------------------------------------------------------------- */
        /* Alpine modal manager with stack support                                  */
        /* ---------------------------------------------------------------------- */
        document.addEventListener('alpine:init', () => {
            (window as any).Alpine.data('modalManager', () => ({
                show: false,
                activeComponent: null as string | null,
                showActiveComponent: false,
                isLoading: false,
                isTransitioning: false,

                // Stack to track modal history
                _modalStack: [] as Array<{ id: string; attrs: ModalAttrs | null }>,
                _attrs: null as { id: string; attrs: ModalAttrs } | null,
                _cleanupId: 0,
                _cleanupTimeout: null as number | null,
                _loadingTimeout: null as number | null,
                _transitionTimeout: null as number | null,
                _prevFocus: null as HTMLElement | null,
                _focusStack: [] as HTMLElement[],
                _main: document.querySelector<HTMLElement>('[data-slot="main"], main, [data-slot="layout"]'),

                // Keep this aligned to your CSS transition duration (ms)
                _teardownDelayMs: 200,
                _loadingDelayMs: 150,
                _transitionDelayMs: 180, // Time to wait for fade-out before showing next modal

                init() {
                    // Toggle inert + scroll lock + focus restore
                    this.$watch('show', (open: boolean) => {
                        if (open) {
                            this._prevFocus = document.activeElement as HTMLElement;
                            document.body.style.overflow = 'hidden';
                            this._main?.setAttribute('inert', '');
                        } else {
                            document.body.style.overflow = '';
                            this._main?.removeAttribute('inert');

                            // Clear loading state
                            clearTimeout(this._loadingTimeout!);
                            this.isLoading = false;

                            // Restore focus on next frame
                            const prev = this._prevFocus;
                            this._prevFocus = null;
                            requestAnimationFrame(() => prev?.focus?.());
                        }
                    });

                    const activate = (payload: ModalActivatePayload) => {
                        const id =
                            (payload as any)?.detail?.id ??
                            (payload as any)?.id ??
                            (typeof payload === 'string' ? payload : null);

                        if (!id) return;

                        const attrs =
                            (payload as any)?.detail?.modalAttributes ??
                            (payload as any)?.modalAttributes ??
                            null;

                        clearTimeout(this._cleanupTimeout!);
                        clearTimeout(this._loadingTimeout!);
                        clearTimeout(this._transitionTimeout!);

                        // If there's already an active modal, animate transition
                        if (this.activeComponent && this.activeComponent !== id) {
                            this._modalStack.push({
                                id: this.activeComponent,
                                attrs: this._attrs?.attrs ?? null
                            });
                            // Save current focus for this modal
                            this._focusStack.push(document.activeElement as HTMLElement);

                            // Start transition: hide current modal first
                            this.isTransitioning = true;

                            // After fade-out, switch to new modal
                            this._transitionTimeout = window.setTimeout(() => {
                                this.activeComponent = id;
                                this._attrs = attrs ? { id, attrs } : null;
                                this.isTransitioning = false;
                                this.showActiveComponent = true;
                                this.isLoading = false;

                                // Focus after transition complete
                                this.$nextTick(() => {
                                    requestAnimationFrame(() => this.focusModal());
                                });
                            }, this._transitionDelayMs);
                        } else {
                            // First modal or same modal - no transition needed
                            this.activeComponent = id;
                            this._attrs = attrs ? { id, attrs } : null;
                            this.showActiveComponent = true;
                            this.isLoading = false;

                            // Focus after Livewire/Alpine rendered
                            this.$nextTick(() => {
                                requestAnimationFrame(() => this.focusModal());
                            });
                        }
                    };

                    const uiOpen = (_e?: CustomEvent<UiOpenDetail>) => {
                        clearTimeout(this._cleanupTimeout!);
                        clearTimeout(this._loadingTimeout!);

                        this.show = true;

                        this._loadingTimeout = window.setTimeout(() => {
                            if (this.show && !this.showActiveComponent) {
                                this.isLoading = true;
                            }
                        }, this._loadingDelayMs);
                    };

                    const uiClose = (_e?: CustomEvent<UiCloseDetail>) => {
                        clearTimeout(this._transitionTimeout!);

                        // Check if there are modals in the stack to go back to
                        if (this._modalStack.length > 0) {
                            const previousModal = this._modalStack.pop()!;
                            const previousFocus = this._focusStack.pop();
                            
                            // Start transition: hide current modal first
                            this.isTransitioning = true;

                            // After fade-out, switch to previous modal
                            this._transitionTimeout = window.setTimeout(() => {
                                this.activeComponent = previousModal.id;
                                this._attrs = previousModal.attrs ? { id: previousModal.id, attrs: previousModal.attrs } : null;
                                this.isTransitioning = false;

                                // Restore focus to previous modal
                                this.$nextTick(() => {
                                    if (previousFocus) {
                                        requestAnimationFrame(() => previousFocus.focus?.());
                                    } else {
                                        this.focusModal();
                                    }
                                });
                            }, this._transitionDelayMs);
                        } else {
                            // No more modals in stack, close completely
                            this.setShow(false);
                        }
                    };

                    // Instant UI open/close (no network)
                    window.addEventListener(UI_OPEN_EVENT, uiOpen as any, { passive: true });
                    window.addEventListener(UI_CLOSE_EVENT, uiClose as any, { passive: true });

                    // Livewire-driven events (sync + stack)
                    this.$wire?.on('activeModalComponentChanged', activate);
                    this.$wire?.on('openModal', uiOpen);
                    this.$wire?.on('closeModal', uiClose);
                },

                setShow(value: boolean) {
                    const id = ++this._cleanupId;
                    this.show = value;

                    if (!value) {
                        this.showActiveComponent = false;
                        this.isLoading = false;
                        this.isTransitioning = false;
                        clearTimeout(this._loadingTimeout!);
                        clearTimeout(this._transitionTimeout!);

                        // Clear the stack when completely closing
                        this._modalStack = [];
                        this._focusStack = [];

                        // Delay teardown to allow exit transitions
                        clearTimeout(this._cleanupTimeout!);
                        this._cleanupTimeout = window.setTimeout(() => {
                            if (this._cleanupId === id) {
                                this.activeComponent = null;
                                this._attrs = null;
                            }
                        }, this._teardownDelayMs);
                    }
                },

                focusModal() {
                    const id = this.activeComponent;
                    if (!id) return;

                    const root =
                        (this.$refs?.[id] as HTMLElement | undefined) ??
                        (this.$el as HTMLElement | null)?.querySelector<HTMLElement>(`[data-modal-id="${id}"]`);

                    if (!root) return;

                    const focusable = root.querySelector<HTMLElement>(
                        'button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])'
                    );

                    focusable?.focus();
                },

                getAttrs(): ModalAttrs | null {
                    return this._attrs?.attrs ?? null;
                },

                closeModalOnClickAway() {
                    if (this.getAttrs()?.closeOnClickAway) {
                        (window as any).NeuraKitModal?.close();
                    }
                },

                closeModalOnEscape() {
                    const a: any = this.getAttrs();
                    if (a?.closeOnEscape) {
                        (window as any).NeuraKitModal?.close(Boolean(a.closeOnEscapeIsForceful));
                    }
                },
            }));
        });
    }
}
