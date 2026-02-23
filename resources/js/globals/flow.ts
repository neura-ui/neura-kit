import { node, flowEditor } from '@copyfactory/alpine-flow';

if (typeof window !== 'undefined') {
    const win = window as any;
    const NK_FLOW_BOOT = (win.__NK_FLOW_BOOT__ ??= { booted: false });

    if (!NK_FLOW_BOOT.booted) {
        NK_FLOW_BOOT.booted = true;

        const register = () => {
            const Alpine = win.Alpine;
            if (!Alpine) {
                console.warn('[neurakit/flow] Alpine not found on window.');
                return;
            }

            Alpine.plugin(node);
            Alpine.data('flowEditor', flowEditor);
        };

        document.addEventListener('alpine:init', register, { once: true });

        if (win.Alpine?.version) {
            register();
        }
    }
}

export { node, flowEditor };