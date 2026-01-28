import './types';

export {};

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    // Créer l'objet Clipboard
    window.Clipboard = {
        async copy(text: string): Promise<boolean> {
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
        },
    };

    // Listener Livewire (IMPORTANT)
    document.addEventListener('livewire:init', () => {
        window.Livewire?.on('clipboard:copy', (event: any) => {
            const text = event.text || event[0]?.text || event;

            if (text) {
                window.Clipboard.copy(text);
            }
        });
    });

    // Magic Alpine (optionnel mais utile)
    document.addEventListener('alpine:init', () => {
        window.Alpine?.magic('clipboard', () => {
            return (text: string) => window.Clipboard.copy(text);
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
