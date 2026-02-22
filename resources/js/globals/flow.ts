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

            // 1. Plugin MUST be registered before Alpine.data()
            Alpine.plugin(node);

            // 2. Only then register the data component
            Alpine.data('flowEditor', flowEditor);
        };

        // 'alpine:init' fires before Alpine processes the DOM; register plugin and data then.
        document.addEventListener('alpine:init', register, { once: true });

        // Fallback: if Alpine already exists and is past initializing
        if (win.Alpine?.version) {
            register();
        }
    }
}

export { node, flowEditor };