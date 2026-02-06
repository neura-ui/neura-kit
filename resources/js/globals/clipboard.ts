import './types';

export {};

/* =========================================================================
 | Clipboard Module
 |
 | Provides a unified clipboard API that works across browsers:
 |   - window.Clipboard.copy(text)   → copy to clipboard
 |   - window.Clipboard.read()       → read from clipboard
 |   - window.copyToClipboard(text)  → shorthand
 |   - $clipboard('text')            → Alpine magic
 |   - x-clipboard="expr"            → Alpine directive (copies on click)
 |   - Livewire clipboard:copy event → server-driven copy
 |
 | Events dispatched on `document`:
 |   clipboard:copied  { text }     → after a successful copy
 |   clipboard:error   { text, error } → after a failed copy
 |========================================================================= */

if (typeof window !== 'undefined' && typeof document !== 'undefined') {

    /* =====================================================================
     | Core
     |===================================================================== */

    /**
     * Copy text to the clipboard using the best available method.
     *
     * 1. Modern Clipboard API (most browsers)
     * 2. Textarea + execCommand fallback (Safari / older browsers / iOS)
     */
    async function copyToClipboard(text: string): Promise<boolean> {
        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                onCopySuccess(text);
                return true;
            }
        } catch {
            // Clipboard API failed — fall through to fallback
        }

        const ok = fallbackCopy(text);

        if (ok) {
            onCopySuccess(text);
        } else {
            onCopyError(text);
        }

        return ok;
    }

    /**
     * Read text from the clipboard (requires user permission).
     * Returns `null` when the operation is denied or unavailable.
     */
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
     | Fallback (Safari / iOS / older browsers)
     |===================================================================== */

    function fallbackCopy(text: string): boolean {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.cssText =
            'position:fixed;top:0;left:0;width:2em;height:2em;' +
            'padding:0;border:none;outline:none;box-shadow:none;' +
            'background:transparent;opacity:0;z-index:-1;';

        // Required for iOS
        textarea.contentEditable = 'true';
        textarea.readOnly = false;

        document.body.appendChild(textarea);

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
                textarea.focus();
                textarea.select();
                textarea.setSelectionRange(0, text.length);
            }

            success = document.execCommand('copy');
        } catch {
            success = false;
        }

        document.body.removeChild(textarea);

        return success;
    }

    function isIOS(): boolean {
        return (
            /iPad|iPhone|iPod/.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
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

        // Magic: $clipboard('text to copy')
        Alpine.magic('clipboard', () => copyToClipboard);

        // Directive: <button x-clipboard="expression">Copy</button>
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

    // Alpine already loaded
    if ((window as any).Alpine) {
        registerAlpineClipboard((window as any).Alpine);
    }

    // Deferred Alpine loading
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
