import './types';

export {};

/* =========================================================================
 | Clipboard Module
 |
 | Unified clipboard API that works across browsers, focus traps (modals,
 | sideovers) and secure/insecure contexts:
 |
 |   - window.Clipboard.copy(text)   → copy to clipboard
 |   - window.Clipboard.read()       → read from clipboard
 |   - window.copyToClipboard(text)  → shorthand
 |   - $clipboard('text')            → Alpine magic
 |   - x-clipboard="expr"            → Alpine directive (copies on click)
 |   - Livewire clipboard:copy event → server-driven copy
 |   - Livewire $this->js() driven  → works inside sideovers/modals
 |
 | Events dispatched on `document`:
 |   clipboard:copied  { text }        → after a successful copy
 |   clipboard:error   { text, error } → after a failed copy
 |========================================================================= */

if (typeof window !== 'undefined' && typeof document !== 'undefined') {

    /* =====================================================================
     | Core
     |===================================================================== */

    async function copyToClipboard(text: string): Promise<boolean> {
        if (!text && text !== '') return false;

        // 1 — Modern Clipboard API (secure contexts, most browsers)
        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                onCopySuccess(text);
                return true;
            }
        } catch {
            // Clipboard API denied — fall through to fallback
        }

        // 2 — execCommand fallback inside the active focus-trapped container
        //     so the focus trap does not steal focus away from the textarea
        const container = getActiveContainer();
        if (execCopyInContainer(text, container)) {
            onCopySuccess(text);
            return true;
        }

        // 3 — Retry in document.body if container was a dialog
        if (container !== document.body) {
            if (execCopyInContainer(text, document.body)) {
                onCopySuccess(text);
                return true;
            }
        }

        onCopyError(text);
        return false;
    }

    async function readFromClipboard(): Promise<string | null> {
        try {
            if (navigator?.clipboard?.readText) {
                return await navigator.clipboard.readText();
            }
        } catch {
            // Permission denied or unavailable
        }

        return null;
    }

    /* =====================================================================
     | Focus-trap-aware container detection
     |===================================================================== */

    function getActiveContainer(): Element {
        // Sideover / modal panels that are currently visible and have focus
        const candidates = document.querySelectorAll(
            '[role="dialog"][aria-modal="true"]',
        );

        for (let i = candidates.length - 1; i >= 0; i--) {
            const el = candidates[i] as HTMLElement;

            // Skip hidden panels (x-show=false, display:none, etc.)
            if (el.offsetParent === null && getComputedStyle(el).position !== 'fixed') continue;
            if (el.style.display === 'none') continue;
            if (el.hasAttribute('x-cloak')) continue;

            // Visible dialog found — use it so the textarea stays inside
            // the focus trap boundary
            return el;
        }

        return document.body;
    }

    /* =====================================================================
     | execCommand fallback (works in focus traps)
     |===================================================================== */

    function execCopyInContainer(text: string, container: Element): boolean {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.contentEditable = 'true';

        textarea.style.cssText = [
            'position:fixed',
            'top:0',
            'left:0',
            'width:2em',
            'height:2em',
            'padding:0',
            'border:none',
            'outline:none',
            'box-shadow:none',
            'background:transparent',
            'opacity:0',
            'z-index:2147483647',
            'pointer-events:none',
        ].join(';');

        container.appendChild(textarea);

        let success = false;

        try {
            if (isIOS()) {
                const range = document.createRange();
                range.selectNodeContents(textarea);

                const selection = window.getSelection();
                if (selection) {
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
                textarea.setSelectionRange(0, text.length);
            } else {
                textarea.focus({ preventScroll: true });
                textarea.select();
                textarea.setSelectionRange(0, text.length);
            }

            success = document.execCommand('copy');
        } catch {
            success = false;
        }

        textarea.remove();

        return success;
    }

    function isIOS(): boolean {
        return (
            /iPad|iPhone|iPod/.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
        );
    }

    /* =====================================================================
     | Feedback helpers
     |===================================================================== */

    function onCopySuccess(text: string): void {
        document.dispatchEvent(
            new CustomEvent('clipboard:copied', { detail: { text } }),
        );
    }

    function onCopyError(text: string, error?: unknown): void {
        document.dispatchEvent(
            new CustomEvent('clipboard:error', { detail: { text, error } }),
        );
    }

    /* =====================================================================
     | Global API
     |===================================================================== */

    (window as any).Clipboard = {
        copy: copyToClipboard,
        read: readFromClipboard,
    };

    (window as any).copyToClipboard = copyToClipboard;

    /* =====================================================================
     | Alpine integration
     |===================================================================== */

    let alpineRegistered = false;

    function registerAlpineClipboard(Alpine: any): void {
        if (alpineRegistered) return;
        alpineRegistered = true;

        Alpine.magic('clipboard', () => copyToClipboard);

        Alpine.directive(
            'clipboard',
            (el: HTMLElement, { expression }: any, { evaluate }: any) => {
                el.addEventListener('click', async () => {
                    const text = evaluate(expression);
                    if (text != null && String(text).length > 0) {
                        await copyToClipboard(String(text));
                    }
                });
            },
        );
    }

    if ((window as any).Alpine) {
        registerAlpineClipboard((window as any).Alpine);
    }

    document.addEventListener('alpine:init', () => {
        if ((window as any).Alpine) {
            registerAlpineClipboard((window as any).Alpine);
        }
    });

    /* =====================================================================
     | Livewire integration
     |===================================================================== */

    document.addEventListener('livewire:init', () => {
        window.Livewire?.on('clipboard:copy', (event: any) => {
            const text =
                event?.text ??
                event?.[0]?.text ??
                (typeof event === 'string' ? event : null);

            if (text) {
                copyToClipboard(text);
            }
        });
    });
}
