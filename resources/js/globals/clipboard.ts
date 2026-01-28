import './types';

export {};

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    /**
     * Safe clipboard copy function that works everywhere
     */
    async function copyToClipboard(text: string): Promise<boolean> {
        // Try modern Clipboard API first
        if (navigator?.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (error) {
                // Clipboard API failed (common on Safari/Mac without user gesture)
                // Fall through to fallback
            }
        }

        // Fallback for Safari/Mac and older browsers
        return fallbackCopy(text);
    }

    // Expose globally for direct usage: window.Clipboard.copy('text')
    window.Clipboard = {
        copy: copyToClipboard,
    };

    // Also expose as a simple global function: copyToClipboard('text')
    (window as any).copyToClipboard = copyToClipboard;

    /**
     * Register Alpine magic and directive
     * Use: $clipboard('text to copy') or x-clipboard="textVariable"
     */
    function registerAlpineClipboard(Alpine: any) {
        // Magic: $clipboard('text')
        Alpine.magic('clipboard', () => copyToClipboard);

        // Directive: x-clipboard="text" (copies on click)
        Alpine.directive('clipboard', (el: HTMLElement, { expression }: any, { evaluate }: any) => {
            el.addEventListener('click', async () => {
                const text = evaluate(expression);
                if (text) {
                    await copyToClipboard(String(text));
                }
            });
        });
    }

    // Register with Alpine - try multiple approaches for reliability
    if ((window as any).Alpine) {
        // Alpine already loaded
        registerAlpineClipboard((window as any).Alpine);
    }

    // Also listen for alpine:init (for deferred loading)
    document.addEventListener('alpine:init', () => {
        if ((window as any).Alpine) {
            registerAlpineClipboard((window as any).Alpine);
        }
    });

    // Listener Livewire
    document.addEventListener('livewire:init', () => {
        window.Livewire?.on('clipboard:copy', (event: any) => {
            const text = event?.text || event?.[0]?.text || (typeof event === 'string' ? event : null);

            if (text) {
                copyToClipboard(text);
            }
        });
    });
}

/* -------------------------------------------------------------------------- */
/* Fallback for Safari/Mac and browsers without Clipboard API support          */
/* -------------------------------------------------------------------------- */

function fallbackCopy(text: string): boolean {
    // Method 1: Use textarea (works best on Safari/Mac)
    const textarea = document.createElement('textarea');
    textarea.value = text;
    
    // Make it invisible but still focusable
    textarea.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 2em;
        height: 2em;
        padding: 0;
        border: none;
        outline: none;
        box-shadow: none;
        background: transparent;
        opacity: 0;
        z-index: -1;
    `;
    
    // Required for iOS
    textarea.contentEditable = 'true';
    textarea.readOnly = false;
    
    document.body.appendChild(textarea);
    
    let success = false;
    
    try {
        // iOS specific handling
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
    } catch (error) {
        success = false;
    }
    
    document.body.removeChild(textarea);
    
    // Restore focus to previous element if possible
    if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }
    
    return success;
}

function isIOS(): boolean {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) || 
           (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}
