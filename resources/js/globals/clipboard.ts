import './types';

export {};

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    // Créer l'objet Clipboard
    window.Clipboard = {
        async copy(text: string): Promise<boolean> {
            try {
                if (navigator?.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                    console.log('✅ Copied via Clipboard API:', text);
                } else {
                    fallbackCopy(text);
                    console.log('✅ Copied via fallback:', text);
                }
                return true;
            } catch (error) {
                console.error('❌ [Clipboard] Copy failed:', error);
                return false;
            }
        },
    };

    // Listener Livewire (IMPORTANT)
    document.addEventListener('livewire:init', () => {
        console.log('📋 Clipboard listener initialized');

        window.Livewire?.on('clipboard:copy', (event: any) => {
            console.log('📋 Received clipboard:copy event:', event);
            const text = event.text || event[0]?.text || event;

            if (text) {
                window.Clipboard.copy(text);
            } else {
                console.error('❌ No text provided to clipboard:copy event');
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
/* Fallback                                                                    */
/* -------------------------------------------------------------------------- */

function fallbackCopy(text: string): void {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.top = '0';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        const successful = document.execCommand('copy');
        console.log(successful ? '✅ Fallback copy successful' : '❌ Fallback copy failed');
    } catch (error) {
        console.error('❌ [Clipboard Fallback] Error:', error);
    }

    document.body.removeChild(textarea);
}
