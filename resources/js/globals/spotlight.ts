import './types';

export {};

interface SpotlightOptions {
    mode?: 'search' | 'command' | 'ai';
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
    params?: any[];
    group?: string;
}

if (typeof window !== 'undefined') {
    /**
     * NeuraKit Spotlight API
     * Global interface for controlling the Spotlight component
     */
    (window as any).NeuraKitSpotlight = {
        /**
         * Open the spotlight
         */
        open(options: SpotlightOptions = {}) {
            window.dispatchEvent(new CustomEvent('spotlight:open', { 
                detail: options 
            }));
            
            // Also dispatch to Livewire if available
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:open', options);
            }
        },

        /**
         * Close the spotlight
         */
        close() {
            window.dispatchEvent(new CustomEvent('spotlight:close'));
            
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:close');
            }
        },

        /**
         * Toggle the spotlight
         */
        toggle(options: SpotlightOptions = {}) {
            window.dispatchEvent(new CustomEvent('spotlight:toggle', { 
                detail: options 
            }));
            
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:toggle', options);
            }
        },

        /**
         * Stream AI content
         */
        stream(content: string, append: boolean = true) {
            window.dispatchEvent(new CustomEvent('spotlight:stream', { 
                detail: { content, append } 
            }));
            
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:stream', { content, append });
            }
        },

        /**
         * Set results programmatically
         */
        setResults(results: SpotlightResult[]) {
            window.dispatchEvent(new CustomEvent('spotlight:set-results', { 
                detail: results 
            }));
            
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:set-results', { results });
            }
        },

        /**
         * Set loading state
         */
        setLoading(isLoading: boolean) {
            window.dispatchEvent(new CustomEvent('spotlight:set-loading', { 
                detail: isLoading 
            }));
            
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:set-loading', { isLoading });
            }
        },

        /**
         * Execute a command
         */
        execute(commandId: string, params: any[] = []) {
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:execute', { commandId, params });
            }
        },

        /**
         * Search
         */
        search(query: string) {
            if ((window as any).Livewire) {
                (window as any).Livewire.dispatch('spotlight:search', { query });
            }
        },
    };

    /**
     * Alpine.js component for Spotlight
     */
    function neuraSpotlight(config: any) {
        return {
            isOpen: config.isOpen || false,
            mode: config.mode || 'search',
            query: config.query || '',
            results: [],
            selectedIndex: config.selectedIndex || 0,
            isLoading: config.isLoading || false,
            aiResponse: '',
            placeholder: config.placeholder || null,

            init() {
                // Focus input when opened
                this.$watch('isOpen', (open: boolean) => {
                    if (open) {
                        this.$nextTick(() => {
                            (this.$refs.input as HTMLInputElement)?.focus();
                        });
                    }
                });

                // Listen for external events
                window.addEventListener('spotlight:open', (e: any) => {
                    this.open(e.detail || {});
                });

                window.addEventListener('spotlight:close', () => {
                    this.close();
                });

                window.addEventListener('spotlight:toggle', (e: any) => {
                    this.toggle(e.detail || {});
                });

                window.addEventListener('spotlight:stream', (e: any) => {
                    const { content, append } = e.detail;
                    if (append) {
                        this.aiResponse += content;
                    } else {
                        this.aiResponse = content;
                    }
                });

                window.addEventListener('spotlight:set-results', (e: any) => {
                    this.results = e.detail;
                });

                window.addEventListener('spotlight:set-loading', (e: any) => {
                    this.isLoading = e.detail;
                });

                // Listen for Livewire navigate event
                window.addEventListener('spotlight:navigate', ((e: CustomEvent) => {
                    const url = e.detail?.url;
                    if (url) {
                        if ((window as any).Livewire?.navigate) {
                            (window as any).Livewire.navigate(url);
                        } else {
                            window.location.href = url;
                        }
                    }
                }) as EventListener);
            },

            open(options: SpotlightOptions = {}) {
                const wasOpen = this.isOpen;
                const previousMode = this.mode;
                
                this.isOpen = true;
                
                // Change mode if explicitly provided
                if (options.mode) {
                    this.mode = options.mode;
                    // Sync with Livewire
                    (this as any).$wire?.setMode(options.mode);
                }
                
                if (options.placeholder) this.placeholder = options.placeholder;
                if (options.query !== undefined) this.query = options.query;
                
                // Reset if fresh open OR if mode changed
                if (!wasOpen || (options.mode && options.mode !== previousMode)) {
                    this.results = [];
                    this.aiResponse = '';
                    this.selectedIndex = 0;
                }
            },

            close() {
                this.isOpen = false;
                this.query = '';
                this.results = [];
                this.aiResponse = '';
            },

            toggle(options: SpotlightOptions = {}) {
                if (this.isOpen) {
                    // If already open with same mode (or no mode specified), just close
                    if (!options.mode || options.mode === this.mode) {
                        this.close();
                    } else {
                        // Different mode requested, switch to it
                        this.setMode(options.mode);
                        if (options.placeholder) this.placeholder = options.placeholder;
                        if (options.query) this.query = options.query;
                    }
                } else {
                    this.open(options);
                }
            },

            setMode(mode: string) {
                this.mode = mode;
                this.results = [];
                this.aiResponse = '';
                this.query = '';
                (this.$refs.input as HTMLInputElement)?.focus();
                
                // Sync with Livewire
                (this as any).$wire?.setMode(mode);
            },

            nextMode() {
                const modes = ['search', 'command', 'ai'];
                const currentIndex = modes.indexOf(this.mode);
                const nextIndex = (currentIndex + 1) % modes.length;
                this.setMode(modes[nextIndex]);
            },

            getPlaceholder() {
                if (this.placeholder) return this.placeholder;
                
                switch (this.mode) {
                    case 'search':
                        return 'Search anything...';
                    case 'command':
                        return 'Type a command...';
                    case 'ai':
                        return 'Ask AI anything...';
                    default:
                        return 'Search...';
                }
            },

            moveUp() {
                if (this.selectedIndex > 0) {
                    this.selectedIndex--;
                } else if (this.results.length > 0) {
                    this.selectedIndex = this.results.length - 1;
                }
                this.scrollToSelected();
                (this as any).$wire?.selectResult(this.selectedIndex);
            },

            moveDown() {
                if (this.selectedIndex < this.results.length - 1) {
                    this.selectedIndex++;
                } else {
                    this.selectedIndex = 0;
                }
                this.scrollToSelected();
                (this as any).$wire?.selectResult(this.selectedIndex);
            },

            scrollToSelected() {
                this.$nextTick(() => {
                    const list = this.$refs.resultsList as HTMLElement;
                    const selected = list?.querySelector('[aria-selected="true"]') as HTMLElement;
                    if (selected) {
                        selected.scrollIntoView({ block: 'nearest' });
                    }
                });
            },

            selectResult(index: number) {
                this.selectedIndex = index;
                (this as any).$wire?.selectResult(index);
            },

            executeSelected() {
                if (this.results.length > 0) {
                    const result = this.results[this.selectedIndex];
                    if (result) {
                        this.handleResult(result);
                    }
                } else if (this.mode === 'ai' && this.query) {
                    (this as any).$wire?.submitAiQuery();
                }
            },

            handleResult(result: SpotlightResult) {
                if (result.url) {
                    if ((window as any).Livewire?.navigate) {
                        (window as any).Livewire.navigate(result.url);
                    } else {
                        window.location.href = result.url;
                    }
                    this.close();
                    return;
                }

                // Let Livewire handle the rest
                (this as any).$wire?.handleResult(result);
            },

            getIconHtml(iconName: string): string {
                // Return a simple icon placeholder - actual icons should be rendered server-side
                return `<svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>`;
            },

            formatAiResponse(response: string): string {
                // Basic markdown-like formatting
                if (!response) return '';
                
                return response
                    .replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>')
                    .replace(/`([^`]+)`/g, '<code>$1</code>')
                    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
                    .replace(/\n/g, '<br>');
            },
        };
    }

    // Register Alpine component
    if ((window as any).Alpine) {
        (window as any).Alpine.data('neuraSpotlight', neuraSpotlight);
    }

    document.addEventListener('alpine:init', () => {
        if ((window as any).Alpine) {
            (window as any).Alpine.data('neuraSpotlight', neuraSpotlight);
        }
    });

    // Register keyboard shortcut
    document.addEventListener('keydown', (e: KeyboardEvent) => {
        // Cmd/Ctrl + K for search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            (window as any).NeuraKitSpotlight.toggle({ mode: 'search' });
        }

        // Cmd/Ctrl + P for command palette
        if ((e.metaKey || e.ctrlKey) && e.key === 'p') {
            e.preventDefault();
            (window as any).NeuraKitSpotlight.toggle({ mode: 'command' });
        }
    });
}
