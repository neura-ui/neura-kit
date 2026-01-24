export type ColorToken = {
    token?: string;
    hex: string;
};

export type NeuraColorPickerOptions = {
    palette?: ColorToken[];
    initialValue?: string | null;
    disabled?: boolean;
};

// Types Alpine pour éviter les erreurs TypeScript
interface AlpineRefs {
    hidden?: HTMLInputElement | null;
    displayInput?: HTMLInputElement | null;
}

interface AlpineContext {
    $refs: AlpineRefs;
    $el: HTMLElement;
    $nextTick: (callback?: () => void) => Promise<void>;
}

export function neuraColorPicker({
    palette = [],
    initialValue = null,
    disabled = false,
}: NeuraColorPickerOptions = {}) {
    const safePalette = Array.isArray(palette) ? palette : [];

    return {
        // Types Alpine (seront injectés par Alpine.js)
        $refs: {} as AlpineRefs,
        $nextTick: (() => Promise.resolve()) as AlpineContext['$nextTick'],
        palette: safePalette,
        isDisabled: disabled,
        open: false,
        query: '',
        token: null as string | null,
        hex: null as string | null,
        rgb: null as { r: number; g: number; b: number } | null,
        display: '',

        init() {
            if (initialValue) {
                this.applyInput(String(initialValue));
            }
            // S'assurer que la valeur initiale est toujours en hex
            this.ensureHexValue();
            
            // Global click handler to close when clicking outside
            const handleClickOutside = (event: MouseEvent) => {
                if (!this.open) return;
                
                const target = event.target as HTMLElement;
                if (!target) return;
                
                const el = (this as any).$el as HTMLElement | undefined;
                if (!el) return;
                
                // Check if click is inside this component
                if (!el.contains(target)) {
                    this.open = false;
                }
            };
            
            document.addEventListener('click', handleClickOutside, true);
            
            // Cleanup on destroy
            (this as any).__cleanup = () => {
                document.removeEventListener('click', handleClickOutside, true);
            };
        },

        // Force la conversion en hex si on a un token ou rgb mais pas de hex
        ensureHexValue() {
            if (!this.hex && this.token) {
                const hit = this.findByToken(this.token);
                if (hit) {
                    this.hex = hit.hex;
                    this.rgb = this.hexToRgb(hit.hex);
                }
            }
            if (!this.hex && this.rgb) {
                this.hex = this.rgbToHex(this.rgb.r, this.rgb.g, this.rgb.b);
            }
        },

        // Méthode publique pour récupérer la valeur hex (pour Livewire/Alpine)
        getHex() {
            this.ensureHexValue();
            return this.hex || '';
        },

        clamp(n: number, min: number, max: number) {
            return Math.min(max, Math.max(min, n));
        },
        hex2(n: number) {
            return n.toString(16).padStart(2, '0');
        },
        rgbToHex(r: number, g: number, b: number) {
            return `#${this.hex2(r)}${this.hex2(g)}${this.hex2(b)}`;
        },
        hexToRgb(hex?: string | null) {
            if (!hex) return null;
            const h = hex.replace('#', '').trim();
            if (![3, 6].includes(h.length)) return null;
            const full = h.length === 3 ? h.split('').map((ch) => ch + ch).join('') : h;
            const n = parseInt(full, 16);
            if (Number.isNaN(n)) return null;
            return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
        },
        rgbToString(rgb?: { r: number; g: number; b: number } | null) {
            return rgb ? `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})` : '';
        },

        normalizeToken(str?: string | null) {
            if (!str) return null;
            let s = String(str).trim().toLowerCase();
            s = s.replace(/^bg-/, '').replace(/^text-/, '').replace(/^border-/, '');
            const m = s.match(/^([a-z]+)-(\d{2,3})$/);
            if (!m) return null;
            return `${m[1]}-${m[2]}`;
        },
        findByToken(token?: string | null) {
            if (!token) return null;
            return this.palette.find((c: ColorToken) => (c.token || '').toLowerCase() === token.toLowerCase()) || null;
        },
        parseHex(str?: string | null) {
            const s = String(str ?? '').trim();
            const m = s.match(/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/);
            if (!m) return null;
            let h = m[1].toLowerCase();
            if (h.length === 3) h = h.split('').map((ch) => ch + ch).join('');
            return `#${h}`;
        },
        parseRgb(str?: string | null) {
            const s = String(str ?? '').trim().toLowerCase();
            const m = s.match(/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*([0-9.]+))?\s*\)$/);
            if (!m) return null;
            const r = this.clamp(parseInt(m[1], 10), 0, 255);
            const g = this.clamp(parseInt(m[2], 10), 0, 255);
            const b = this.clamp(parseInt(m[3], 10), 0, 255);
            return { r, g, b };
        },

        applyToken(token?: string | null) {
            const hit = this.findByToken(token);
            if (!hit) return false;
            this.token = hit.token ?? null;
            this.hex = hit.hex;
            this.rgb = this.hexToRgb(hit.hex);
            this.display = hit.token || '';
            this.syncHidden();
            return true;
        },
        applyHex(hex?: string | null) {
            const rgb = this.hexToRgb(hex);
            if (!rgb || !hex) return false;
            this.token = null;
            this.hex = hex;
            this.rgb = rgb;
            this.display = hex.toUpperCase();
            this.syncHidden();
            return true;
        },
        applyRgb(rgb?: { r: number; g: number; b: number } | null) {
            if (!rgb) return false;
            const hex = this.rgbToHex(rgb.r, rgb.g, rgb.b);
            this.token = null;
            this.hex = hex;
            this.rgb = rgb;
            this.display = this.rgbToString(rgb);
            this.syncHidden();
            return true;
        },

        applyInput(raw?: string | null) {
            if (this.isDisabled) return;
            const str = String(raw ?? '').trim();
            if (!str) {
                this.token = null;
                this.hex = null;
                this.rgb = null;
                this.display = '';
                this.syncHidden();
                return;
            }
            // Essayer token d'abord
            const token = this.normalizeToken(str);
            if (token && this.applyToken(token)) {
                this.ensureHexValue();
                this.syncHidden();
                return;
            }
            // Essayer hex
            const hex = this.parseHex(str);
            if (hex && this.applyHex(hex)) {
                this.syncHidden();
                return;
            }
            // Essayer rgb
            const rgb = this.parseRgb(str);
            if (rgb && this.applyRgb(rgb)) {
                this.syncHidden();
                return;
            }
            // Valeur invalide : on garde l'affichage mais on vide hex
            this.display = str;
            this.token = null;
            this.hex = null;
            this.rgb = null;
            this.syncHidden();
        },

        syncHidden() {
            // S'assurer qu'on a toujours une valeur hex avant de synchroniser
            this.ensureHexValue();
            const hidden = (this as any).$refs?.hidden as HTMLInputElement | null | undefined;
            if (hidden) {
                // Toujours envoyer hex (ou chaîne vide si invalide)
                hidden.value = this.hex || '';
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },

        filteredPalette() {
            if (!Array.isArray(this.palette)) return [];
            const q = (this.query || '').toLowerCase().trim();
            if (!q) return this.palette;
            return this.palette.filter((c: ColorToken) => {
                const t = String(c.token || '').toLowerCase();
                const h = String(c.hex || '').toLowerCase();
                return t.includes(q) || h.includes(q);
            });
        },

        choose(color?: ColorToken | null) {
            if (!color || this.isDisabled) return;
            this.applyToken(color.token);
            this.ensureHexValue();
            this.syncHidden();
            this.query = '';
            // Close menu
            this.open = false;
        },
    };
}

if (typeof window !== 'undefined') {
    (window as any).neuraColorPicker = neuraColorPicker;
}
