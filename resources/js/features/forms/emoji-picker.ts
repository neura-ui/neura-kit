import { ALL_EMOJIS, EMOJI_CATEGORIES, type EmojiCategory, type EmojiItem } from './emoji-data';

export type NeuraEmojiPickerOptions = {
    initialValue?: string | null;
    disabled?: boolean;
    wireProperty?: string | null;
    closeOnSelect?: boolean;
    recentLimit?: number;
    for?: string | null;
};

const RECENT_KEY = 'neura.emoji-picker.recent';

function loadRecent(limit: number): string[] {
    try {
        const raw = localStorage.getItem(RECENT_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.filter((e) => typeof e === 'string').slice(0, limit);
    } catch {
        return [];
    }
}

function saveRecent(emoji: string, limit: number): string[] {
    const next = [emoji, ...loadRecent(limit).filter((e) => e !== emoji)].slice(0, limit);
    try {
        localStorage.setItem(RECENT_KEY, JSON.stringify(next));
    } catch {
        // ignore quota / private mode
    }
    return next;
}

function insertAtCursor(el: HTMLInputElement | HTMLTextAreaElement, emoji: string): string {
    const start = el.selectionStart ?? el.value.length;
    const end = el.selectionEnd ?? el.value.length;
    const next = el.value.slice(0, start) + emoji + el.value.slice(end);
    el.value = next;
    const caret = start + emoji.length;
    el.setSelectionRange(caret, caret);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    return next;
}

export function neuraEmojiPicker({
    initialValue = null,
    disabled = false,
    wireProperty = null,
    closeOnSelect = true,
    recentLimit = 24,
    for: forId = null,
}: NeuraEmojiPickerOptions = {}) {
    return {
        isDisabled: disabled,
        wireProperty,
        closeOnSelect,
        forId,
        open: false,
        query: '',
        value: initialValue ?? '',
        category: 'smileys',
        recent: [] as string[],
        categories: EMOJI_CATEGORIES as EmojiCategory[],

        init() {
            this.recent = loadRecent(recentLimit);

            if (this.wireProperty && (this as any).$wire) {
                const wireValue = (this as any).$wire.get(this.wireProperty);
                if (typeof wireValue === 'string') {
                    this.value = wireValue;
                }
            } else {
                const hidden = (this as any).$refs?.hidden as HTMLInputElement | undefined;
                if (hidden?.value) {
                    this.value = hidden.value;
                }
            }

            if (this.wireProperty && (this as any).$wire) {
                (this as any).$watch(
                    () => (this as any).$wire.get(this.wireProperty),
                    (newValue: string | null) => {
                        if (typeof newValue === 'string' && newValue !== this.value) {
                            this.value = newValue;
                        }
                    }
                );
            }

            const handleClickOutside = (event: MouseEvent) => {
                if (!this.open) return;
                const target = event.target as Node | null;
                const el = (this as any).$el as HTMLElement | undefined;
                if (!el || !target) return;
                if (!el.contains(target)) {
                    this.open = false;
                }
            };

            document.addEventListener('click', handleClickOutside, true);
            (this as any).__cleanup = () => {
                document.removeEventListener('click', handleClickOutside, true);
            };
        },

        destroy() {
            (this as any).__cleanup?.();
        },

        toggle() {
            if (this.isDisabled) return;
            this.open = !this.open;
            if (this.open) {
                (this as any).$nextTick(() => {
                    ((this as any).$refs?.search as HTMLInputElement | undefined)?.focus();
                });
            }
        },

        setCategory(id: string) {
            this.category = id;
            this.query = '';
        },

        filteredEmojis(): EmojiItem[] {
            const q = this.query.trim().toLowerCase();
            if (q) {
                return ALL_EMOJIS.filter((item) => {
                    const hay = `${item.n} ${item.k ?? ''}`.toLowerCase();
                    return hay.includes(q) || item.e === q;
                }).slice(0, 96);
            }

            if (this.category === 'recent') {
                return this.recent.map((e) => ALL_EMOJIS.find((item) => item.e === e) ?? { e, n: e });
            }

            return this.categories.find((c) => c.id === this.category)?.emojis ?? [];
        },

        syncHidden() {
            const hidden = (this as any).$refs?.hidden as HTMLInputElement | undefined;
            if (hidden) {
                hidden.value = this.value;
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (this.wireProperty && (this as any).$wire) {
                (this as any).$wire.set(this.wireProperty, this.value);
            }
        },

        resolveTarget(): HTMLInputElement | HTMLTextAreaElement | null {
            if (this.forId) {
                const el = document.getElementById(this.forId);
                if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
                    return el;
                }
            }

            const input = (this as any).$refs?.input as HTMLInputElement | HTMLTextAreaElement | undefined;
            return input ?? null;
        },

        choose(emoji: string) {
            if (this.isDisabled || !emoji) return;

            this.recent = saveRecent(emoji, recentLimit);

            const target = this.resolveTarget();
            if (target) {
                const next = insertAtCursor(target, emoji);
                if (target === (this as any).$refs?.input) {
                    this.value = next;
                    this.syncHidden();
                }
            } else {
                this.value = emoji;
                this.syncHidden();
            }

            (this as any).$dispatch?.('emoji-select', { emoji });
            (this as any).$el?.dispatchEvent(
                new CustomEvent('emoji-select', { detail: { emoji }, bubbles: true })
            );

            if (this.closeOnSelect) {
                this.open = false;
            }

            (this as any).$nextTick(() => {
                target?.focus();
            });
        },

        onInput(event: Event) {
            const el = event.target as HTMLInputElement;
            this.value = el.value;
            this.syncHidden();
        },

        clear() {
            this.value = '';
            this.syncHidden();
            const input = (this as any).$refs?.input as HTMLInputElement | undefined;
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },
    };
}

if (typeof window !== 'undefined') {
    (window as any).neuraEmojiPicker = neuraEmojiPicker;
}
