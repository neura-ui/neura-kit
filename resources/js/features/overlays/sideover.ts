import { onAlpineInit } from '../../runtime/alpine';
export {};

const isBrowser =
    typeof window !== 'undefined' &&
    typeof document !== 'undefined';

if (!isBrowser) {
} else {
    type LivewireComponent = {
        call(method: string, ...args: unknown[]): void;
        on?(event: string, callback: (...args: any[]) => void): void;
    };

    type SideoverAttrs = Record<string, unknown>;

    type SideoverActivatePayload =
        | { id: string; sideoverAttributes?: SideoverAttrs }
        | { detail?: { id?: string; sideoverAttributes?: SideoverAttrs } }
        | string;

    type UiOpenDetail = { component?: string; attrs?: SideoverAttrs } | undefined;
    type UiCloseDetail = { force?: boolean; skipPreviousSideovers?: number; destroySkipped?: boolean } | undefined;

    type PushSide = 'left' | 'right';

    const NK_SIDEOVER_BOOT = ((window as any).__NK_SIDEOVER_BOOT__ ??= { booted: false });

    const SideoverManagerCache = {
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

            const el =
                this.element ??
                (this.element = document.querySelector<HTMLElement>('[x-data*="sideoverManager"]'));

            if (!el) return null;

            const wireId = this.wireId ?? (this.wireId = el.getAttribute('wire:id'));
            if (!wireId) return null;

            const lw = (window as any).Livewire;
            if (!lw?.find) return null;

            this.instance = lw.find(wireId) as LivewireComponent | null;
            return this.instance;
        },
    };

    const UI_OPEN_EVENT = 'nk-sideover-ui-open';
    const UI_CLOSE_EVENT = 'nk-sideover-ui-close';

    let _currentPushSide: PushSide | null = null;

    function getLayoutEl(): HTMLElement | null {
        return document.querySelector<HTMLElement>('[data-slot="layout"]')
            ?? document.querySelector<HTMLElement>('[data-slot="main"]')
            ?? document.querySelector<HTMLElement>('main');
    }

    function pushLayout(width: string, side: PushSide) {
        const layout = getLayoutEl();
        if (!layout) return;

        unpushLayoutImmediate(layout);

        _currentPushSide = side;
        if (side === 'left') {
            layout.style.marginLeft = width;
        } else {
            layout.style.marginRight = width;
        }
    }

    function unpushLayoutImmediate(layout: HTMLElement) {
        if (_currentPushSide === 'left') {
            layout.style.marginLeft = '0px';
        } else if (_currentPushSide === 'right') {
            layout.style.marginRight = '0px';
        }
    }

    function unpushLayout() {
        const layout = getLayoutEl();
        if (!layout || !_currentPushSide) return;

        unpushLayoutImmediate(layout);
        const side = _currentPushSide;

        setTimeout(() => {
            const prop = side === 'left' ? 'marginLeft' : 'marginRight';
            if (layout.style[prop] === '0px') {
                layout.style[prop] = '';
            }
            _currentPushSide = null;
        }, 350);
    }

    function getActivePanelInfo(activeId: string | null): { width: string; side: PushSide } {
        if (!activeId) return { width: '0px', side: 'right' };
        const panel = document.querySelector<HTMLElement>(`[data-sideover-id="${activeId}"]`);
        return {
            width: panel?.dataset.sideoverPushWidth ?? '24rem',
            side: (panel?.dataset.sideoverSide as PushSide) ?? 'right',
        };
    }

    if (!(window as any).NeuraKitSideover) {
        (window as any).NeuraKitSideover = {
            open(
                component: string,
                args: Record<string, unknown> = {},
                attrs: Record<string, unknown> = {}
            ) {
                window.dispatchEvent(new CustomEvent<UiOpenDetail>(UI_OPEN_EVENT, { detail: { component, attrs } }));
                SideoverManagerCache.get()?.call('openSideover', component, args, attrs);
            },

            openUi(component: string, attrs: Record<string, unknown> = {}) {
                window.dispatchEvent(new CustomEvent<UiOpenDetail>(UI_OPEN_EVENT, { detail: { component, attrs } }));
            },

            close(force = false, skip = 0, destroy = false) {
                window.dispatchEvent(
                    new CustomEvent<UiCloseDetail>(UI_CLOSE_EVENT, {
                        detail: { force, skipPreviousSideovers: skip, destroySkipped: destroy },
                    })
                );
                SideoverManagerCache.get()?.call('closeSideover', force, skip, destroy);
            },

            goBack() {
                this.close(true, 0, false);
            },
        };
    }

    if (!NK_SIDEOVER_BOOT.booted) {
        NK_SIDEOVER_BOOT.booted = true;

        document.addEventListener('livewire:navigated', () => {
            SideoverManagerCache.invalidate();
            unpushLayout();
        }, { passive: true });

        // The server destroys a closed sideover, and the manager's next render removes its panel
        // while it may still be sliding out. Such a panel is kept until its slide has ended.
        const PANEL_LEAVE_MS = 300;
        const keepSlidingPanels = () => {
            (window as any).Livewire?.hook?.('morph.removing', ({ el, skip }: { el: Element; skip: () => void }) => {
                if (!(el instanceof HTMLElement) || !el.dataset.sideoverId || !el.classList.contains('nk-sideover-leave')) return;

                skip();
                window.setTimeout(() => el.remove(), PANEL_LEAVE_MS);
            });
        };

        if ((window as any).Livewire?.hook) {
            keepSlidingPanels();
        } else {
            document.addEventListener('livewire:init', keepSlidingPanels, { once: true });
        }

        window.addEventListener(
            'sideover-close',
            (e: Event) => {
                const d = (e as CustomEvent)?.detail ?? {};
                (window as any).NeuraKitSideover?.close(
                    Boolean(d.force),
                    Number(d.skipPreviousSideovers ?? 0),
                    Boolean(d.destroySkipped)
                );
            },
            { passive: true }
        );

        onAlpineInit(() => {
            (window as any).Alpine.data('sideoverManager', () => ({
                show: false,
                activeComponent: null as string | null,
                showActiveComponent: false,
                isLoading: false,
                // Which way the stack moves, for the panels' slide: null outside the stack
                stackDirection: null as 'forward' | 'back' | null,

                _sideoverStack: [] as Array<{ id: string; attrs: SideoverAttrs | null }>,
                _attrs: null as { id: string; attrs: SideoverAttrs } | null,
                _cleanupId: 0,
                _cleanupTimeout: null as number | null,
                _loadingTimeout: null as number | null,
                _prevFocus: null as HTMLElement | null,
                _focusStack: [] as HTMLElement[],

                // Keep this above the panels' leave transition (--nk-duration-standard, 240ms)
                _teardownDelayMs: 260,
                _loadingDelayMs: 150,

                init() {
                    this.$watch('show', (open: boolean) => {
                        if (open) {
                            this._prevFocus = document.activeElement as HTMLElement;
                        } else {
                            unpushLayout();
                            clearTimeout(this._loadingTimeout!);
                            this.isLoading = false;
                            const prev = this._prevFocus;
                            this._prevFocus = null;
                            requestAnimationFrame(() => prev?.focus?.());
                        }
                    });

                    this.$watch('activeComponent', (id: string | null) => {
                        if (id && this.show) {
                            this.$nextTick(() => {
                                const info = getActivePanelInfo(id);
                                pushLayout(info.width, info.side);
                            });
                        }
                    });

                    const activate = (payload: SideoverActivatePayload) => {
                        const id =
                            (payload as any)?.detail?.id ??
                            (payload as any)?.id ??
                            (typeof payload === 'string' ? payload : null);

                        if (!id) return;

                        const attrs =
                            (payload as any)?.detail?.sideoverAttributes ??
                            (payload as any)?.sideoverAttributes ??
                            null;

                        clearTimeout(this._cleanupTimeout!);
                        clearTimeout(this._loadingTimeout!);

                        if (this.activeComponent && this.activeComponent !== id) {
                            this._sideoverStack.push({
                                id: this.activeComponent,
                                attrs: this._attrs?.attrs ?? null
                            });
                            this._focusStack.push(document.activeElement as HTMLElement);

                            // Switch at once: the next panel slides in over the current one
                            this.stackDirection = 'forward';
                            this.activeComponent = id;
                            this._attrs = attrs ? { id, attrs } : null;
                            this.showActiveComponent = true;
                            this.isLoading = false;
                            this.$nextTick(() => {
                                requestAnimationFrame(() => this.focusSideover());
                            });
                        } else {
                            // First panel (it slides in from its edge) or same panel
                            if (this.activeComponent !== id) {
                                this.stackDirection = null;
                            }

                            this.activeComponent = id;
                            this._attrs = attrs ? { id, attrs } : null;
                            this.isLoading = false;
                            requestAnimationFrame(() => {
                                this.showActiveComponent = true;
                                this.$nextTick(() => {
                                    requestAnimationFrame(() => this.focusSideover());
                                });
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
                        if (this._sideoverStack.length > 0) {
                            const previous = this._sideoverStack.pop()!;
                            const previousFocus = this._focusStack.pop();

                            // Switch at once: the current panel slides out and reveals the previous one
                            this.stackDirection = 'back';
                            this.activeComponent = previous.id;
                            this._attrs = previous.attrs ? { id: previous.id, attrs: previous.attrs } : null;
                            this.$nextTick(() => {
                                if (previousFocus) {
                                    requestAnimationFrame(() => previousFocus.focus?.());
                                } else {
                                    this.focusSideover();
                                }
                            });
                        } else {
                            this.setShow(false);
                        }
                    };

                    window.addEventListener(UI_OPEN_EVENT, uiOpen as any, { passive: true });
                    window.addEventListener(UI_CLOSE_EVENT, uiClose as any, { passive: true });

                    this.$wire?.on('activeSideoverComponentChanged', activate);
                    this.$wire?.on('openSideover', uiOpen);
                    this.$wire?.on('closeSideover', uiClose);
                },

                setShow(value: boolean) {
                    const id = ++this._cleanupId;
                    this.show = value;

                    if (!value) {
                        // The last panel slides back out to its edge, whatever the stack did before
                        this.stackDirection = null;
                        this.showActiveComponent = false;
                        this.isLoading = false;
                        clearTimeout(this._loadingTimeout!);
                        this._sideoverStack = [];
                        this._focusStack = [];

                        clearTimeout(this._cleanupTimeout!);
                        this._cleanupTimeout = window.setTimeout(() => {
                            if (this._cleanupId === id) {
                                this.activeComponent = null;
                                this._attrs = null;
                            }
                        }, this._teardownDelayMs);
                    }
                },

                focusSideover() {
                    const id = this.activeComponent;
                    if (!id) return;

                    const root =
                        (this.$refs?.[id] as HTMLElement | undefined) ??
                        (this.$el as HTMLElement | null)?.querySelector<HTMLElement>(`[data-sideover-id="${id}"]`);

                    if (!root) return;

                    const focusable = root.querySelector<HTMLElement>(
                        'button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])'
                    );

                    focusable?.focus();
                },

                getAttrs(): SideoverAttrs | null {
                    return this._attrs?.attrs ?? null;
                },

                closeSideoverOnClickAway() {
                    if (this.getAttrs()?.closeOnClickAway) {
                        (window as any).NeuraKitSideover?.close();
                    }
                },

                closeSideoverOnEscape() {
                    const a: any = this.getAttrs();
                    if (a?.closeOnEscape) {
                        (window as any).NeuraKitSideover?.close(Boolean(a.closeOnEscapeIsForceful));
                    }
                },
            }));
        });
    }
}
