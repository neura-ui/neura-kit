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

<div
    x-data="{
        search: '',
        open: false,
        isTyping: false,

        // Manages visual highlighting of options (not real browser focus)
        // Real focus stays on input for accessibility, this just controls which option appears highlighted
        activeIndex: null,

        // Store all available options and currently visible/filtered options
        options:[],        // All options from DOM
        filteredOptions:[], // Subset based on search query

        isMultiple: @js($multiple),
        isDisabled: @js($disabled),
        isSearchable: @js($searchable),
        searchPlaceholder: @js($searchPlaceholder ?? neura_trans('search')),

        // Selected value(s) - array for multiple, single value for single select
        state: @js($multiple) ? [] : null,
        placeholder: @js($placeholder ?? neura_trans('select')),

        init() {
            this.$nextTick(() => {
                const wireModelAttr = this.$root?.getAttributeNames().find((n) => n.startsWith('wire:model'));

                // Build options array from DOM elements on component initialization
                this.filteredOptions = this.options = Array
                    .from(this.$el.querySelectorAll('[data-slot=option]:not([hidden])'))
                    .map((option) => ({
                        value: option.dataset.value,
                        label: option.dataset.label,
                        element: option
                    }));

                // Initialize state from wire:model or x-model binding
                if (this.$wire && wireModelAttr) {
                    const prop = this.$root.getAttribute(wireModelAttr);
                    const wireValue = this.$wire.get?.(prop);

                    if (this.isMultiple) {
                        this.state = Array.isArray(wireValue)
                            ? wireValue
                            : (wireValue ? [wireValue] : []);
                    } else {
                        this.state = wireValue ?? null;
                    }
                } else {
                    this.state = this.$root?._x_model?.get();

                    if (this.state === undefined || this.state === null) {
                        this.state = this.isMultiple ? [] : null;
                    }
                }
            });

            // Two-way data binding: sync internal state back to Alpine/Livewire
            this.$watch('state', (value) => {
                // Sync with Alpine.js x-model
                this.$root?._x_model?.set(value);

                // Sync with Livewire wire:model (if present)
                let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'))

                if(this.$wire && wireModel){
                    let prop = this.$root.getAttribute(wireModel)
                    this.$wire.set(prop, value, wireModel?.includes('.live'));
                }
            });

            // Filter options based on search input
            this.$watch('search', (val) => {
                if (val.trim() === '') {
                    // Empty search → show all options
                    this.filteredOptions = this.options;
                } else {
                    // Filter by search query - search in both value and label
                    const searchTerm = val.toLowerCase().trim();
                    this.filteredOptions = this.options.filter(option => {
                        const valueMatch = option.value?.toLowerCase().includes(searchTerm) ?? false;
                        const labelMatch = option.label?.toLowerCase().includes(searchTerm) ?? false;
                        return valueMatch || labelMatch;
                    });
                }
            })
        },

        // Check if given option is currently selected
        isSelected(option) {
            return this.isMultiple ? this.state?.includes(option) : this.state === option;
        },

        select(option) {
            this.isTyping = false;
            this.search = '';

            if (!this.isMultiple) {
                // Single select: set value and close
                this.open = false;
                this.state = option;
                return;
            }

            // Multiple select: toggle option in/out of array
            if(!Array.isArray(this.state)){
                console.error('Multiple select requires an array value. Please bind an array property using x-model or wire:model.');
            }

            const itemIndex = this.state.findIndex(item => item === option);

            if (itemIndex === -1) {
                this.state.push(option);    // Add to selection
            } else {
                this.state.splice(itemIndex, 1);  // Remove from selection
            }
        },

        // Reset component to initial state
        clear() {
            this.state = this.isMultiple ? [] : null;
            this.open = false;
        },

        // Determine if option should be visible (for search filtering)
        isItemShown(value) {
            if (!this.isSearchable || !this.isTyping || !this.search.trim()) return true;
            const option = this.options.find(opt => opt.value === value);
            if (!option) return false;
            const searchTerm = this.search.toLowerCase().trim();
            const valueStr = String(option.value || '').toLowerCase();
            const labelStr = String(option.label || '').toLowerCase();
            return valueStr.includes(searchTerm) || labelStr.includes(searchTerm);
        },

        // Clear search input
        clearSearch() {
            this.search = '';
            this.isTyping = false;
            this.$refs.searchControl?.focus();
        },

        // Close dropdown and reset all temporary states
        close() {
            this.open = false;
            this.search = '';
            this.isTyping = false;
            this.activeIndex = null;
        },

        // Toggle dropdown open/closed state
        toggle() {
            if (this.isDisabled) return;

            this.open = !this.open;

            // Auto-highlight first option when opening searchable select with no selection
            if((this.open && !this.hasSelection) && this.isSearchable){
                this.activeIndex = 0
            };
        },

        // Keyboard navigation handler - manages visual highlighting (not real focus)
        // Real browser focus stays on input for screen readers, this just moves the visual highlight
        handleKeydown(event) {
            // Navigate down through options (wraps to beginning)
            if (event.key === 'ArrowDown') {
                if (this.activeIndex === null || this.activeIndex >= this.filteredOptions.length - 1) {
                    this.activeIndex = 0;
                } else {
                    this.activeIndex++;
                }
            }

            // Navigate up through options (wraps to end)
            if (event.key === 'ArrowUp') {
                if (this.activeIndex === null || this.activeIndex <= 0) {
                    this.activeIndex = this.filteredOptions.length - 1;
                } else {
                    this.activeIndex--;
                }
            }

            // Select currently highlighted option
            if (event.key === 'Enter' && this.activeIndex !== null) {
                let option = this.filteredOptions[this.activeIndex];
                this.select(option.value);
            }

            // Jump to first option
            if (event.key === 'Home') {
                this.activeIndex = 0;
                return;
            }

            // Jump to last option
            if (event.key === 'End') {
                this.activeIndex = this.filteredOptions.length - 1;
                return;
            }
        },

        // Convert option value to its index in the filtered results array
        getFilteredIndex(value) {
            return this.filteredOptions.findIndex(option => option.value === value);
        },

        // Mouse hover handler - sync visual highlight with mouse position is like converting hover state to our *virtual* focus
        handleMouseEnter(value) {
            this.activeIndex = this.getFilteredIndex(value);
        },

        handleMouseLeave(el){
            // Only blur if searchable (input has focus)
            if(this.isSearchable){
                el.blur();
            }
            // Uncomment to clear highlight when mouse leaves (preference: keep activeIndex for better keyboard nav)
            // this.activeIndex = null;
        },

        // Check if option should appear visually highlighted
        isFocused(value) {
            return this.activeIndex !== null && this.getFilteredIndex(value) === this.activeIndex;
        },

        // Check if search returned any results
        get hasFilteredResults() {
            return this.filteredOptions.length > 0;
        },

        // Generate display text for the trigger button
        get label() {
            if (!this.hasSelection) return this.placeholder;

            if (!this.isMultiple) {
                // Single select: show the selected option's label
                const option = this.options.find(opt => opt.value === this.state);
                return option?.label ?? this.placeholder;
            }

            // Multiple select: show individual label or count
            if (this.state.length === 1) {
                const option = this.options.find(opt => opt.value === this.state[0]);
                return option?.label ?? this.state[0];
            }

            return ` ${this.state.length} ${window.t('itemsSelected')}`;
        },

        // Check if any option is currently selected
        get hasSelection() {
            return this.isMultiple ? this.state?.length > 0 : this.state !== null;
        },

        contains(str, substring){
            if (!str || !substring) return false;
            return str.toLowerCase().trim().includes(substring.toLowerCase().trim());
        },

        // Get search icon visibility
        get hasSearchValue() {
            return this.search && this.search.trim().length > 0;
        }
    }"
    {{ $attributes->class([
            'relative [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]',
            'dark:border-red-400! dark:shadow-red-400 text-red-400! placeholder:text-red-400!' => $invalid,
        ]),
     }}
>

    @if ($name)
        <input
            type="hidden"
            name="{{ $name }}"
            x-bind:value="isMultiple ? state.join(',') : state"
        />
    @endif

    <div>
        <neura::select.trigger/>

        <neura::select.options
            :searchPlaceholder="$searchPlaceholder"
        >
            {{ $slot }}
        </neura::select.options>
    </div>
</div>