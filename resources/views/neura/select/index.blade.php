@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'label' => null,
    'triggerLabel' => null,
    'placeholder' => null,
    'searchable' => false,
    'searchPlaceholder' => 'Search...',
    'multiple' => false,
    'clearable' => false,
    'disabled' => false,
    'icon' => null,
    'iconAfter' => 'chevron-up-down',
    'invalid' => null,
    'triggerClass' => null,
])

@php
    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $value;
            break;
        }
    }
@endphp

<div x-data="{
    search: '',
    open: false,
    isTyping: false,
    activeIndex: null,
    options: [],
    filteredOptions: [],
    optionsVersion: 0,
    isMultiple: @js($multiple),
    isDisabled: @js($disabled),
    isSearchable: @js($searchable),
    searchPlaceholder: @js($searchPlaceholder ?? ucfirst(neura_trans('search'))),
    placeholder: @js($placeholder ?? ucfirst(neura_trans('select'))),
    wireProperty: @js($wireModel),
    // FIX: Initialize internal state for x-model
    _internalState: @js($multiple ? [] : null),

    get state() {
        // Check if we have a wire:model property
        if (this.wireProperty && this.$wire) {
            const value = this.$wire.get(this.wireProperty);
            if (this.isMultiple) {
                return Array.isArray(value) ? value : (value ? [value] : []);
            }
            return value ?? null;
        }

        // FIX: Fall back to internal state for x-model binding
        return this._internalState;
    },

    set state(value) {
        // Set internal state
        this._internalState = value;

        // Also set wire property if it exists
        if (this.wireProperty && this.$wire) {
            this.$wire.set(this.wireProperty, value);
        }
    },

    init() {
        // Global click handler to close when clicking outside
        const handleClickOutside = (event) => {
            if (!this.open) return;
            
            const target = event.target;
            if (!target) return;
            
            // Check if click is inside this component
            if (!this.$el.contains(target)) {
                this.close();
            }
        };
        
        document.addEventListener('click', handleClickOutside, true);
        
        // Store cleanup function
        this.__cleanup = () => {
            document.removeEventListener('click', handleClickOutside, true);
        };
        
        // Initial options build with a small delay to ensure DOM is ready
        this.$nextTick(() => {
            setTimeout(() => {
                this.rebuildOptions();
            }, 50);
        });

        // Watch for Livewire wire model value changes
        if (this.wireProperty && this.$wire) {
            this.$watch(() => this.$wire.get(this.wireProperty), () => {
                this.$nextTick(() => {
                    this.rebuildOptions();
                });
            });
        }

        // Listen for Livewire updates and rebuild options immediately
        if (typeof Livewire !== 'undefined' && Livewire.hook) {
            Livewire.hook('commit', ({ component, commit, respond }) => {
                if (component.el === this.$root || component.el.contains(this.$el)) {
                    this.$nextTick(() => {
                        this.rebuildOptions();
                    });
                }
            });
        }

        // Watch for DOM changes (when Livewire updates the options list)
        this.$nextTick(() => {
            const optionsContainer = this.$el;

            const observer = new MutationObserver((mutations) => {
                // Check if options were added/removed
                const hasOptionChanges = mutations.some(mutation => {
                    const addedNodes = Array.from(mutation.addedNodes).some(node =>
                        node.nodeType === 1 && node.hasAttribute('data-slot') && node.getAttribute('data-slot') === 'option'
                    );
                    const removedNodes = Array.from(mutation.removedNodes).some(node =>
                        node.nodeType === 1 && node.hasAttribute('data-slot') && node.getAttribute('data-slot') === 'option'
                    );
                    return addedNodes || removedNodes;
                });

                if (hasOptionChanges) {
                    // Rebuild immediately when options change
                    this.$nextTick(() => {
                        this.rebuildOptions();
                    });
                }
            });

            observer.observe(optionsContainer, {
                childList: true,
                subtree: true
            });
        });

        // Filter options based on search
        this.$watch('search', (val) => {
            if (val.trim() === '') {
                this.filteredOptions = this.options;
            } else {
                const searchTerm = val.toLowerCase().trim();
                this.filteredOptions = this.options.filter(option => {
                    const valueMatch = option.value?.toLowerCase().includes(searchTerm) ?? false;
                    const labelMatch = option.label?.toLowerCase().includes(searchTerm) ?? false;
                    return valueMatch || labelMatch;
                });
            }
        });
    },

    rebuildOptions() {
        const newOptions = Array
            .from(this.$el.querySelectorAll('[data-slot=option]:not([hidden])'))
            .map((option) => ({
                value: option.dataset.value,
                label: option.dataset.label,
                element: option
            }));

        // Always update and increment version to trigger reactivity
        this.options = newOptions;
        this.filteredOptions = newOptions;
        this.optionsVersion++; // Force reactivity update
    },

    isSelected(value) {
        const currentState = this.state;

        if (this.isMultiple) {
            // For multiple select, check if the array includes this value
            return Array.isArray(currentState) && currentState.some(v => String(v) === String(value));
        }

        // For single select, do a string comparison to handle type coercion properly
        return String(currentState) === String(value);
    },

    select(value) {
        this.isTyping = false;
        this.search = '';

        if (!this.isMultiple) {
            this.open = false;
            this.state = value; // Store the value directly
            return;
        }

        const currentState = Array.isArray(this.state) ? [...this.state] : [];
        const itemIndex = currentState.findIndex(item => String(item) === String(value));

        if (itemIndex === -1) {
            this.state = [...currentState, value];
        } else {
            this.state = currentState.filter((_, index) => index !== itemIndex);
        }
    },

    clear() {
        this.state = this.isMultiple ? [] : null;
        this.open = false;
    },

    isItemShown(value) {
        if (!this.isSearchable || !this.isTyping || !this.search.trim()) return true;
        const option = this.options.find(opt => String(opt.value) === String(value));
        if (!option) return false;
        const searchTerm = this.search.toLowerCase().trim();
        const valueStr = String(option.value || '').toLowerCase();
        const labelStr = String(option.label || '').toLowerCase();
        return valueStr.includes(searchTerm) || labelStr.includes(searchTerm);
    },

    clearSearch() {
        this.search = '';
        this.isTyping = false;
        this.$refs.searchControl?.focus();
    },

    close() {
        this.open = false;
        this.search = '';
        this.isTyping = false;
        this.activeIndex = null;
    },

    toggle() {
        if (this.isDisabled) return;
        this.open = !this.open;
        if ((this.open && !this.hasSelection) && this.isSearchable) {
            this.activeIndex = 0
        };
    },

    handleKeydown(event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (this.activeIndex === null || this.activeIndex >= this.filteredOptions.length - 1) {
                this.activeIndex = 0;
            } else {
                this.activeIndex++;
            }
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (this.activeIndex === null || this.activeIndex <= 0) {
                this.activeIndex = this.filteredOptions.length - 1;
            } else {
                this.activeIndex--;
            }
        }

        if (event.key === 'Enter' && this.activeIndex !== null) {
            event.preventDefault();
            let option = this.filteredOptions[this.activeIndex];
            this.select(option.value);
        }

        if (event.key === 'Home') {
            event.preventDefault();
            this.activeIndex = 0;
            return;
        }

        if (event.key === 'End') {
            event.preventDefault();
            this.activeIndex = this.filteredOptions.length - 1;
            return;
        }
    },

    getFilteredIndex(value) {
        return this.filteredOptions.findIndex(option => String(option.value) === String(value));
    },

    handleMouseEnter(value) {
        this.activeIndex = this.getFilteredIndex(value);
    },

    handleMouseLeave(el) {
        if (this.isSearchable) {
            el.blur();
        }
    },

    isFocused(value) {
        return this.activeIndex !== null && this.getFilteredIndex(value) === this.activeIndex;
    },

    get hasFilteredResults() {
        return this.filteredOptions.length > 0;
    },

    get label() {
        // Force reactivity by referencing optionsVersion
        const _ = this.optionsVersion;
        const currentState = this.state;

        if (!this.hasSelection) {
            return this.placeholder;
        }

        if (!this.isMultiple) {
            // Convert both to strings for proper comparison
            const option = this.options.find(opt => String(opt.value) === String(currentState));
            return option?.label ?? this.placeholder;
        }

        if (Array.isArray(currentState) && currentState.length === 1) {
            const option = this.options.find(opt => String(opt.value) === String(currentState[0]));
            return option?.label ?? currentState[0];
        }

        return currentState && currentState.length > 0 ?
            `${currentState.length} ${window.t('itemsSelected')}` :
            this.placeholder;
    },

    get hasSelection() {
        const currentState = this.state;
        return this.isMultiple ?
            (Array.isArray(currentState) && currentState.length > 0) :
            (currentState !== null && currentState !== '' && currentState !== undefined);
    },

    contains(str, substring) {
        if (!str || !substring) return false;
        return str.toLowerCase().trim().includes(substring.toLowerCase().trim());
    },

    get hasSearchValue() {
        return this.search && this.search.trim().length > 0;
    }
}"
    {{ $attributes->class([
        'relative [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]',
        'dark:border-red-400! dark:shadow-red-400 text-red-400! placeholder:text-red-400!' => $invalid,
    ]) }}>

    @if ($name)
        <input type="hidden" name="{{ $name }}"
            x-bind:value="isMultiple ? (Array.isArray(state) ? state.join(',') : '') : (state ?? '')" />
    @endif

    <div>
        <neura::select.trigger />

        <neura::select.options :searchPlaceholder="$searchPlaceholder">
            {{ $slot }}
        </neura::select.options>
    </div>
</div>
