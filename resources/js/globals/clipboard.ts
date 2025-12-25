import './types';

export {};

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    const Clipboard = {
        async copy(text: string): Promise<boolean> {
            try {
                if (navigator?.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    fallbackCopy(text);
                }

                return true;
            } catch (error) {
                console.error('[Clipboard]', error);

                return false;
            }
        },
    };

    window.Clipboard = Clipboard;

    document.addEventListener('livewire:init', () => {
        window.Livewire?.on(
            'clipboard:copy',
            ({ text }: { text: string }) => {
                Clipboard.copy(text);
            }
        );
    });
}

/* -------------------------------------------------------------------------- */
/* Fallback                                                                    */
/* -------------------------------------------------------------------------- */

function fallbackCopy(text: string) {
    const textarea = document.createElement('textarea');
    textarea.value = text;

    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';

    document.body.appendChild(textarea);
    textarea.select();

    document.execCommand('copy');
    document.body.removeChild(textarea);
}
