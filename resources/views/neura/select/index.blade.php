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
    'value' => null,
    'size' => neura_config('select', 'size'),
    'maxVisible' => 2,
])

@php
    $defaultValue = $value ?? $attributes->get('value');

    // Plain Alpine state — Livewire/Alpine bind via x-modelable + wire:model / x-model on the root.
    // Avoid this.$wire.set(): in nested Livewire (modal-manager → modal), Alpine's $wire can
    // resolve to the parent manager and throw PublicPropertyNotFoundException.
    $initialState = $multiple
        ? (is_array($defaultValue) ? $defaultValue : ($defaultValue !== null ? [$defaultValue] : []))
        : $defaultValue;
@endphp

<div x-data="{
    search: '',
    open: false,
    isTyping: false,
    activeIndex: null,
    activePanel: null,
    anchorWidth: null,
    options: [],
    filteredOptions: [],
    optionsVersion: 0,
    isMultiple: @js($multiple),
    isDisabled: @js($disabled),
    isSearchable: @js($searchable),
    searchPlaceholder: @js($searchPlaceholder ?? ucfirst(neura_trans('search'))),
    placeholder: @js($placeholder ?? ucfirst(neura_trans('select'))),
    state: @js($initialState),
    valueReady: false,
    rollDir: 1,
    chipCount: 0,
    maxVisible: @js(max(1, (int) $maxVisible)),

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

        // Keep the popup width matched to the control (input + actions) while open
        const handleResize = () => {
            if (this.open) this.syncAnchorWidth();
        };
        window.addEventListener('resize', handleResize);

        // Store cleanup function
        this.__cleanup = () => {
            document.removeEventListener('click', handleClickOutside, true);
            window.removeEventListener('resize', handleResize);
        };
        
        // Initial options build with a small delay to ensure DOM is ready
        this.$nextTick(() => {
            setTimeout(() => {
                this.rebuildOptions();
            }, 50);
        });

        // Rebuild when bound state changes (wire:model / x-model via x-modelable)
        this.$watch('state', (next, prev) => {
            this.rollDir = this.valueDirection(prev, next);
            this.$nextTick(() => {
                this.rebuildOptions();
            });
        });

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
        this.syncValueLabel(this.valueReady);
        this.valueReady = true;
    },

    valueDirection(prev, next) {
        const indexOf = (value) => this.options.findIndex((option) => String(option.value) === String(value));

        if (this.isMultiple) {
            const prevLen = Array.isArray(prev) ? prev.length : 0;
            const nextLen = Array.isArray(next) ? next.length : 0;
            return nextLen >= prevLen ? 1 : -1;
        }

        if (prev == null || prev === '') return 1;
        if (next == null || next === '') return -1;

        const prevIndex = indexOf(prev);
        const nextIndex = indexOf(next);
        if (prevIndex < 0 || nextIndex < 0 || prevIndex === nextIndex) return 1;
        return nextIndex > prevIndex ? 1 : -1;
    },

    valueChip(text) {
        const chip = document.createElement('span');
        chip.className = 'nk-select-chip is-current';
        chip.textContent = text;
        return chip;
    },

    syncValueLabel(animate) {
        if (this.isMultiple) {
            this.syncMultiChips(animate);
            return;
        }

        const reel = this.$refs.valueReel;
        if (!reel) return;

        const next = String(this.label ?? '');
        const chips = Array.from(reel.querySelectorAll('.nk-select-chip'));
        const current = chips.find((chip) => chip.classList.contains('is-current')) || chips[0] || null;
        chips.forEach((chip) => {
            if (chip !== current) chip.remove();
        });

        if (current && current.textContent === next) return;

        if (!animate || !current) {
            reel.replaceChildren(this.valueChip(next));
            return;
        }

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const dir = this.rollDir || 1;
        const outgoing = current;
        const incoming = this.valueChip(next);
        outgoing.classList.remove('is-current');
        reel.append(incoming);

        const duration = reduced ? 120 : 220;
        const easing = 'cubic-bezier(0.16, 1, 0.3, 1)';
        const distance = reduced ? 0 : 12;
        const blur = reduced ? 'blur(0px)' : 'blur(4px)';

        outgoing.animate([
            { transform: 'translateY(0px)', opacity: 1, filter: 'blur(0px)' },
            { transform: 'translateY(' + (-dir * distance) + 'px)', opacity: 0, filter: blur },
        ], { duration, easing, fill: 'forwards' }).finished.then(() => outgoing.remove()).catch(() => outgoing.remove());

        incoming.animate([
            { transform: 'translateY(' + (dir * distance) + 'px)', opacity: 0, filter: blur },
            { transform: 'translateY(0px)', opacity: 1, filter: 'blur(0px)' },
        ], { duration, easing, fill: 'both' });
    },

    selectedItems() {
        const values = Array.isArray(this.state) ? this.state.map((value) => String(value)) : [];
        const known = new Set();
        const items = [];

        this.options.forEach((option) => {
            const value = String(option.value);
            if (!values.includes(value) || known.has(value)) return;
            known.add(value);
            items.push({ value, label: option.label || value });
        });

        values.forEach((value) => {
            if (known.has(value)) return;
            items.push({ value, label: value });
        });

        return items;
    },

    makeChip(item) {
        const slot = document.createElement('span');
        slot.className = 'nk-multi-slot';
        slot.dataset.value = item.value;
        const chip = document.createElement('span');
        chip.className = 'nk-multi-chip';
        const label = document.createElement('span');
        label.className = 'nk-multi-label';
        label.textContent = item.label;
        chip.append(label);
        slot.append(chip);
        return slot;
    },

    makeOverflow(text) {
        const slot = document.createElement('span');
        slot.className = 'nk-multi-slot nk-multi-more';
        const chip = document.createElement('span');
        chip.className = 'nk-multi-chip';
        const count = document.createElement('span');
        count.className = 'nk-multi-count';
        const current = document.createElement('span');
        current.className = 'is-current';
        current.textContent = text;
        count.append(current);
        chip.append(count);
        slot.append(chip);
        return slot;
    },

    chipWidth(chip) {
        const probe = chip.cloneNode(true);
        const anchor = this.$refs.selectTrigger?.querySelector('[data-slot=select-control]') || document.body;
        probe.style.position = 'absolute';
        probe.style.left = '-9999px';
        probe.style.top = '0';
        probe.style.visibility = 'hidden';
        probe.style.width = 'max-content';
        probe.style.maxWidth = '9rem';
        anchor.append(probe);
        const width = Math.ceil(probe.getBoundingClientRect().width);
        probe.remove();
        return Math.max(width, 1);
    },

    presentChip(slot, live) {
        const chip = slot.querySelector('.nk-multi-chip');
        const target = this.chipWidth(chip) + 'px';
        slot.style.width = target;
        if (!live) return;

        slot.animate([
            { width: '0px' },
            { width: target },
        ], { duration: 420, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'both' });

        chip.animate([
            { opacity: 0, transform: 'scale(0.9)', filter: 'blur(4px)' },
            { opacity: 1, transform: 'none', filter: 'blur(0px)' },
        ], { duration: 220, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'both' });
    },

    dismissChip(slot, live) {
        if (slot.dataset.leaving) return;
        slot.dataset.leaving = '1';
        if (!live) {
            slot.remove();
            return;
        }

        const chip = slot.querySelector('.nk-multi-chip');
        const from = slot.getBoundingClientRect().width || this.chipWidth(chip);
        chip?.animate([
            { opacity: 1, transform: 'none', filter: 'blur(0px)' },
            { opacity: 0, transform: 'scale(0.9)', filter: 'blur(4px)' },
        ], { duration: 180, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'forwards' });

        const motion = slot.animate([
            { width: from + 'px', marginLeft: getComputedStyle(slot).marginLeft },
            { width: '0px', marginLeft: '0px' },
        ], { duration: 320, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'forwards' });
        motion.finished.then(() => slot.remove()).catch(() => slot.remove());
    },

    rollCount(reel, text, dir, live) {
        const current = reel.querySelector('.is-current');
        if (current && current.textContent === text) return;
        const incoming = document.createElement('span');
        incoming.className = 'is-current';
        incoming.textContent = text;

        if (!live || !current) {
            reel.replaceChildren(incoming);
            return;
        }

        current.classList.remove('is-current');
        reel.append(incoming);
        const distance = 10;
        const duration = 220;
        const easing = 'cubic-bezier(0.16, 1, 0.3, 1)';
        current.animate([
            { transform: 'translateY(0px)', opacity: 1, filter: 'blur(0px)' },
            { transform: 'translateY(' + (-dir * distance) + 'px)', opacity: 0, filter: 'blur(3px)' },
        ], { duration, easing, fill: 'forwards' }).finished.then(() => current.remove()).catch(() => current.remove());
        incoming.animate([
            { transform: 'translateY(' + (dir * distance) + 'px)', opacity: 0, filter: 'blur(3px)' },
            { transform: 'translateY(0px)', opacity: 1, filter: 'blur(0px)' },
        ], { duration, easing, fill: 'both' });
    },

    syncOverflow(host, count, live) {
        let slot = host.querySelector('.nk-multi-more');
        if (count <= 0) {
            if (slot) this.dismissChip(slot, live);
            return;
        }

        const text = '+' + count;
        if (!slot || slot.dataset.leaving) {
            if (slot) slot.remove();
            slot = this.makeOverflow(text);
            host.append(slot);
            this.presentChip(slot, live);
            return;
        }

        const current = slot.querySelector('.is-current');
        const prev = Number(String(current?.textContent || '0').replace('+', '')) || 0;
        if (current && current.textContent === text) return;
        this.rollCount(slot.querySelector('.nk-multi-count'), text, count >= prev ? 1 : -1, live);
        const nextWidth = this.chipWidth(slot.querySelector('.nk-multi-chip')) + 'px';
        if (live) {
            slot.animate([
                { width: (slot.getBoundingClientRect().width || 0) + 'px' },
                { width: nextWidth },
            ], { duration: 280, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'both' });
        }
        slot.style.width = nextWidth;
    },

    syncMultiChips(animate) {
        if (!this.$refs.multiChips) return;
        if (this.selectedItems().length) this.chipCount = this.selectedItems().length;

        this.$nextTick(() => {
            const host = this.$refs.multiChips;
            if (!host) return;
            this.paintMultiChips(animate);
            if (this.selectedItems().length) return;

            const slots = Array.from(host.querySelectorAll('.nk-multi-slot'));
            if (!animate || !slots.length) {
                host.replaceChildren();
                this.chipCount = 0;
                return;
            }

            Promise.all(slots.map((slot) => {
                const motion = slot.getAnimations()[0];
                return motion ? motion.finished.catch(() => {}) : Promise.resolve();
            })).then(() => {
                if (!this.selectedItems().length) this.chipCount = 0;
            });
        });
    },

    paintMultiChips(animate) {
        const host = this.$refs.multiChips;
        if (!host) return;

        const items = this.selectedItems();
        const visible = items.slice(0, this.maxVisible);
        const overflow = Math.max(0, items.length - visible.length);
        const live = !!animate;
        const keep = new Set(visible.map((item) => item.value));

        host.querySelectorAll('.nk-multi-slot:not(.nk-multi-more)').forEach((slot) => {
            if (!keep.has(slot.dataset.value)) this.dismissChip(slot, live);
        });

        visible.forEach((item) => {
            let slot = Array.from(host.querySelectorAll('.nk-multi-slot:not(.nk-multi-more)')).find((node) => node.dataset.value === item.value) || null;
            if (slot?.dataset.leaving) {
                slot.remove();
                slot = null;
            }
            if (!slot) {
                slot = this.makeChip(item);
                host.insertBefore(slot, host.querySelector('.nk-multi-more'));
                this.presentChip(slot, live);
                return;
            }
            const label = slot.querySelector('.nk-multi-label');
            if (label && label.textContent !== item.label) label.textContent = item.label;
            host.insertBefore(slot, host.querySelector('.nk-multi-more'));
        });

        this.syncOverflow(host, overflow, live);
        if (items.length) this.chipCount = items.length;
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
        if (!this.isMultiple) {
            this.isTyping = false;
            this.search = '';
            this.open = false;
            this.activePanel = null;
            this.state = value; // Store the value directly
            return;
        }

        // Keep the current search in multiple mode so the user can keep picking
        // from the filtered list without retyping.
        const currentState = Array.isArray(this.state) ? [...this.state] : [];
        const itemIndex = currentState.findIndex(item => String(item) === String(value));

        if (itemIndex === -1) {
            this.state = [...currentState, value];
        } else {
            this.state = currentState.filter((_, index) => index !== itemIndex);
        }
    },

    syncAnchorWidth() {
        // Measure the whole control (input + action buttons) so the dropdown
        // opens at exactly that width, aligned to both edges.
        this.anchorWidth = this.$refs.selectAnchor?.offsetWidth ?? null;
    },

    openPanel(name) {
        if (this.isDisabled) return;

        this.open = true;
        this.syncAnchorWidth();
        this.activePanel = name;
        this.search = '';
        this.isTyping = false;
        this.activeIndex = null;

        this.$nextTick(() => {
            const panel = this.$el.querySelector(`[data-panel='${name}']`);
            const focusable = panel?.querySelector(
                'input:not([type=hidden]):not([disabled]), textarea:not([disabled]), select:not([disabled]), button:not([disabled]):not([data-slot=select-panel-back])'
            );
            focusable?.focus({ preventScroll: true });
        });
    },

    backPanel() {
        this.activePanel = null;

        this.$nextTick(() => {
            if (this.isSearchable) {
                this.$refs.searchControl?.focus({ preventScroll: true });
            }
            this.scrollSelectionIntoView();
        });
    },

    scrollSelectionIntoView() {
        const selected = this.$el.querySelector('[data-slot=option][data-selected]');
        selected?.scrollIntoView({ block: 'nearest' });
    },

    // Arrow-key navigation that jumps from option to option, skipping
    // any focusable suffix actions inside the rows.
    focusOption(direction, event) {
        const items = Array
            .from(this.$el.querySelectorAll('[data-slot=option]'))
            .filter(el => el.offsetParent !== null);

        if (!items.length) return;

        const current = event.target.closest('[data-slot=option]');
        const index = items.indexOf(current);

        let next;
        if (index === -1) {
            next = direction > 0 ? items[0] : items[items.length - 1];
        } else {
            next = items[(index + direction + items.length) % items.length];
        }

        next.focus({ preventScroll: true });
        next.scrollIntoView({ block: 'nearest' });
        this.activeIndex = this.getFilteredIndex(next.dataset.value);
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
        this.activePanel = null;
    },

    toggle() {
        if (this.isDisabled) return;

        if (this.open) {
            this.close();
            return;
        }

        this.open = true;
        this.syncAnchorWidth();

        if (!this.hasSelection && this.isSearchable) {
            this.activeIndex = 0;
        }

        this.$nextTick(() => {
            if (this.isSearchable) {
                this.$refs.searchControl?.focus({ preventScroll: true });
            }

            // Start keyboard navigation from the current selection
            if (!this.isMultiple && this.hasSelection) {
                const index = this.getFilteredIndex(this.state);
                if (index >= 0) this.activeIndex = index;
            }

            this.scrollSelectionIntoView();
        });
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
            if (option) this.select(option.value);
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

        const names = this.selectedItems().map((item) => item.label);
        return names.length ? names.join(', ') : this.placeholder;
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
    x-modelable="state"
    x-on:keydown.escape="open && (activePanel ? backPanel() : close())"
    x-bind:style="'--select-popup-w: ' + (anchorWidth ? anchorWidth + 'px' : '100%')"
    x-bind:class="open ? 'z-50' : ''"
    {{ $attributes->whereDoesntStartWith(['value', 'name', 'label', 'triggerLabel', 'placeholder', 'searchable', 'searchPlaceholder', 'multiple', 'clearable', 'disabled', 'icon', 'iconAfter', 'invalid', 'triggerClass', 'maxVisible'])->class([
        'relative [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]',
        'dark:border-red-400! dark:shadow-red-400 text-red-400! placeholder:text-red-400!' => $invalid,
    ]) }}>

    @if ($name)
        <input type="hidden" name="{{ $name }}"
            x-bind:value="isMultiple ? (Array.isArray(state) ? state.join(',') : '') : (state ?? '')" />
    @endif

    <div>
        <div class="relative" x-ref="selectAnchor">
            @if (isset($action) && $action->isNotEmpty())
                <div class="flex items-stretch gap-1.5">
                    <div class="relative flex-1 min-w-0">
                        <neura::select.trigger />
                    </div>

                    <div data-slot="select-actions" {{ $action->attributes->merge(['class' => 'flex items-stretch gap-1.5 shrink-0']) }}>
                        {{ $action }}
                    </div>
                </div>
            @else
                <neura::select.trigger />
            @endif
        </div>

        <neura::select.options :searchPlaceholder="$searchPlaceholder">
            {{ $slot }}

            @isset($panels)
                <x-slot:panels>{{ $panels }}</x-slot:panels>
            @endisset
        </neura::select.options>
    </div>
</div>
