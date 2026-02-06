import './types';

export {};

/* =========================================================================
 | Enums
 |========================================================================= */

export enum SpotlightMode {
    Search = 'search',
    Command = 'command',
    Ai = 'ai',
}

export enum SpotlightActionType {
    Url = 'url',
    Command = 'command',
    Dispatch = 'dispatch',
    Wire = 'wire',
    Javascript = 'js',
    Copy = 'copy',
    Modal = 'modal',
}

export enum SpotlightGroup {
    General = 'general',
    Navigation = 'navigation',
    Commands = 'commands',
    Actions = 'actions',
    Settings = 'settings',
    Content = 'content',
    Users = 'users',
    Recent = 'recent',
    Favorites = 'favorites',
    Help = 'help',
}

/* =========================================================================
 | Interfaces
 |========================================================================= */

interface SpotlightOptions {
    mode?: SpotlightMode | string;
    placeholder?: string;
    query?: string;
}

interface SpotlightResult {
    id: string;
    title: string;
    description?: string;
    icon?: string;
    url?: string;
    action?: string;
    actionType?: string;
    params?: any[];
    group?: string;
    priority?: number;
    shortcut?: string;
    badge?: string;
    disabled?: boolean;
}

interface SpotlightConfig {
    defaultMode: string;
    debounceMs: number;
    maxResults: number;
    showModes: boolean;
    showFooter: boolean;
    modes: Array<{ value: string; label: string; icon: string; shortcut: string }>;
    groups: Array<{ value: string; label: string; icon: string; priority: number }>;
}

interface AlpineSpotlightData {
    isOpen: boolean;
    mode: string;
    query: string;
    results: SpotlightResult[];
    selectedIndex: number;
    isLoading: boolean;
    aiResponse: string;
    placeholder: string | null;
    config: SpotlightConfig;
}

/* =========================================================================
 | Icons
 |========================================================================= */

const iconMap: Record<string, string> = {
    'magnifying-glass': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>',
    'command-line': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>',
    'sparkles': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>',
    'arrow-right': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>',
    'bolt': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>',
    'cog-6-tooth': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>',
    'moon': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>',
    'sun': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>',
    'user': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>',
    'document-text': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>',
    'question-mark-circle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>',
    'star': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>',
    'clock': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
    'home': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
    'code-bracket': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>',
    'squares-2x2': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>',
    'arrow-right-circle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
    'arrow-top-right-on-square': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>',
};

/* =========================================================================
 | Mode Config
 |========================================================================= */

// Default placeholders (can be overridden by config from Livewire)
const defaultPlaceholders: Record<string, string> = {
    search: 'Search anything...',
    command: 'Type a command...',
    ai: 'Ask AI anything...',
};

/* =========================================================================
 | Global API
 |========================================================================= */

if (typeof window !== 'undefined') {
    const getComponent = () => {
        const el = document.querySelector('[wire\\:key="neura-spotlight-manager"]');
        return el ? (window as any).Livewire?.find(el.getAttribute('wire:id')) : null;
    };

    (window as any).NeuraKitSpotlight = {
        open: (options: SpotlightOptions = {}) => getComponent()?.call('open', options),
        close: () => getComponent()?.call('close'),
        toggle: (options: SpotlightOptions = {}) => getComponent()?.call('toggle', options),
        setMode: (mode: string) => getComponent()?.call('setMode', mode),
        stream: (content: string, append = true) => getComponent()?.call('streamAiResponse', content, append),
        setResults: (results: SpotlightResult[]) => getComponent()?.set('results', results),
        setLoading: (loading: boolean) => getComponent()?.set('isLoading', loading),
        execute: (id: string, params: any[] = []) => getComponent()?.call('executeCommand', { commandId: id, params }),
        search: (query: string) => getComponent()?.call('search', { query }),
        isOpen: () => getComponent()?.get('isOpen') ?? false,
        getModes: () => Object.values(SpotlightMode),
    };

    /* =========================================================================
     | Alpine Component
     |========================================================================= */

    function neuraSpotlight(initialData: Partial<AlpineSpotlightData>) {
        return {
            // State (synced via @entangle)
            isOpen: initialData.isOpen || false,
            mode: initialData.mode || 'search',
            query: initialData.query || '',
            results: initialData.results || [],
            selectedIndex: initialData.selectedIndex || 0,
            isLoading: initialData.isLoading || false,
            aiResponse: initialData.aiResponse || '',
            placeholder: initialData.placeholder || null,
            config: initialData.config || {},

            init() {
                // Focus input when opened
                this.$watch('isOpen', (open: boolean) => {
                    if (open) {
                        this.$nextTick(() => (this.$refs.input as HTMLInputElement)?.focus());
                    } else {
                        this.aiResponse = '';
                        this.placeholder = null;
                    }
                });

                // Keep selection in bounds
                this.$watch('results', () => {
                    if (this.selectedIndex >= this.results.length) {
                        this.selectedIndex = Math.max(0, this.results.length - 1);
                    }
                });

                // Livewire event listeners
                (window as any).Livewire?.on('theme:toggle', () => {
                    document.documentElement.classList.toggle('dark');
                });
            },

            // Helpers
            getPlaceholder(): string {
                if (this.placeholder) return this.placeholder;
                // Try to get from config (translated), fallback to defaults
                const configPlaceholder = this.config?.modes?.find((m: any) => m.value === this.mode)?.placeholder;
                return configPlaceholder || defaultPlaceholders[this.mode] || 'Search...';
            },

            getIconSvg(name: string): string {
                return iconMap[name] || iconMap['arrow-right'];
            },

            formatAiResponse(text: string): string {
                if (!text) return '';
                
                let formatted = text;
                
                // Code blocks with syntax highlighting class
                formatted = formatted.replace(
                    /```(\w*)\n([\s\S]*?)```/g, 
                    '<pre class="not-prose bg-neutral-100 dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-neutral-800 p-4 rounded-lg overflow-x-auto my-3 text-xs font-mono leading-relaxed shadow-sm"><code class="language-$1">$2</code></pre>'
                );
                
                // Inline code
                formatted = formatted.replace(
                    /`([^`]+)`/g, 
                    '<code class="not-prose">$1</code>'
                );
                
                // Bold text
                formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                
                // Italic text
                formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
                
                // Links
                formatted = formatted.replace(
                    /\[([^\]]+)\]\(([^)]+)\)/g, 
                    '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
                );
                
                // Headings (must be before paragraph processing)
                formatted = formatted.replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold mt-3 mb-2">$1</h3>');
                formatted = formatted.replace(/^## (.+)$/gm, '<h2 class="text-lg font-semibold mt-4 mb-2">$1</h2>');
                formatted = formatted.replace(/^# (.+)$/gm, '<h1 class="text-xl font-bold mt-4 mb-3">$1</h1>');
                
                // Lists (before paragraph processing)
                // Unordered lists
                formatted = formatted.replace(/^- (.+)$/gm, '<li class="ml-4">$1</li>');
                formatted = formatted.replace(/(<li class="ml-4">.*<\/li>\n?)+/g, '<ul class="list-disc list-outside my-2 space-y-1">$&</ul>');
                
                // Split into paragraphs by double newlines
                const parts = formatted.split(/\n\n+/);
                formatted = parts
                    .map(part => {
                        // Don't wrap headings, lists, or code blocks in p tags
                        if (part.match(/^<(h[1-3]|ul|ol|pre|div)/)) {
                            return part;
                        }
                        // Replace single newlines with br in paragraphs
                        const withBreaks = part.replace(/\n/g, '<br>');
                        return `<p class="my-2 leading-relaxed">${withBreaks}</p>`;
                    })
                    .join('');
                
                // Clean up
                formatted = formatted.replace(/<p class="my-2 leading-relaxed"><\/p>/g, '');
                formatted = formatted.replace(/<p class="my-2 leading-relaxed">\s*<\/p>/g, '');
                
                return formatted;
            },

            // Navigation
            moveUp() {
                if (this.results.length === 0) return;
                this.selectedIndex = this.selectedIndex > 0 ? this.selectedIndex - 1 : this.results.length - 1;
                this.scrollToSelected();
            },

            moveDown() {
                if (this.results.length === 0) return;
                this.selectedIndex = this.selectedIndex < this.results.length - 1 ? this.selectedIndex + 1 : 0;
                this.scrollToSelected();
            },

            scrollToSelected() {
                this.$nextTick(() => {
                    const selected = (this.$refs.resultsList as HTMLElement)?.querySelector('[aria-selected="true"]') as HTMLElement;
                    selected?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                });
            },

            selectResult(index: number) {
                this.selectedIndex = index;
            },

            executeSelected() {
                if (this.mode === 'ai') {
                    if (this.query.trim()) {
                        (this as any).$wire.submitAiQuery();
                    }
                    return;
                }

                const selected = this.results[this.selectedIndex];
                if (selected && !selected.disabled) {
                    (this as any).$wire.handleResult(selected);
                }
            },
        };
    }

    // Register
    document.addEventListener('alpine:init', () => {
        (window as any).Alpine.data('neuraSpotlight', neuraSpotlight);
    });

    if ((window as any).Alpine) {
        (window as any).Alpine.data('neuraSpotlight', neuraSpotlight);
    }
}
