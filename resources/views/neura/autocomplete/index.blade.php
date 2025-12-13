@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'label' => null,
    'placeholder' => neura_trans('typeToSearch'),
    'clearable' => false,
    'disabled' => false,
    'invalid' => null,
    'leftIcon' => null,
    'rightIcon' => null,
    'size' => null,
    'minChars' => 1,
    'debounce' => 300,
])

@php
    $invalid ??= $name && $errors->has($name);
    $inputId = $attributes->get('id');
    $displayName = $name
        ? $name . '_display'
        : ($attributes->get('name') ?? 'autocomplete_search');
@endphp

<div x-data="{
        search: '',
        open: false,
        selectedValue: null,
        selectedLabel: null,
        activeIndex: null,
        options: [],
        filteredOptions: [],
        isDisabled: @json($disabled),
        minChars: @json($minChars),
        debounceTimer: null,
        hasTyped: false,

        init() {
            const loadOptions = () => {
                const allOptions = this.$el.querySelectorAll('[data-slot=option]');

                this.options = Array.from(allOptions)
                    .filter(option => option.dataset.value && option.dataset.label)
                    .map((option) => ({
                        value: option.dataset.value,
                        label: option.dataset.label,
                        element: option
                    }));

                this.filteredOptions = this.options;

                const initialValue = this.$root?._x_model?.get();
                if (initialValue) {
                    const option = this.options.find(opt => opt.value == initialValue);
                    if (option) {
                        this.selectedValue = option.value;
                        this.selectedLabel = option.label;
                        this.search = option.label;
                    }
                }
            };

            this.$nextTick(() => {
                loadOptions();
            });

            this.$watch('selectedValue', (value) => {
                this.$root?._x_model?.set(value);

                let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'));
                if(this.$wire && wireModel){
                    let prop = this.$root.getAttribute(wireModel);
                    this.$wire.set(prop, value, wireModel?.includes('.live'));
                }
            });
        },

        handleInput(event) {
            if (this.isDisabled) return;
            
            this.search = event.target.value;
            this.hasTyped = true; // Mark that user has started typing
            this.open = this.search.length > 0; // Only open if there's text
            this.activeIndex = null;

            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.filterOptions();
            }, @json($debounce));
        },

        filterOptions() {
            if (this.search.length < this.minChars) {
                this.filteredOptions = this.options;
                this.open = false; // Close if below minimum chars
                return;
            }

            const searchTerm = this.search.toLowerCase().trim();
            this.filteredOptions = this.options.filter(option =>
                option.label.toLowerCase().includes(searchTerm) ||
                option.value.toLowerCase().includes(searchTerm)
            );
            
            // Keep open only if we have results and user is typing
            this.open = this.filteredOptions.length > 0 && this.search.length > 0;
        },

        select(option) {
            this.selectedValue = option.value;
            this.selectedLabel = option.label;
            this.search = option.label;
            this.open = false; // Close dropdown after selection
            this.activeIndex = null;
            this.hasTyped = false; // Reset typing flag
            this.$nextTick(() => {
                this.$refs.autocompleteInput?.querySelector('input')?.blur();
            });
        },

        clear() {
            this.search = '';
            this.selectedValue = null;
            this.selectedLabel = null;
            this.open = false;
            this.activeIndex = null;
            this.hasTyped = false;
            this.filteredOptions = this.options;
        },

        handleKeydown(event) {
            if (this.isDisabled) return;
            
            if (!this.open || this.filteredOptions.length === 0) {
                if (event.key === 'ArrowDown' && this.search.length > 0) {
                    event.preventDefault();
                    this.open = true;
                    this.filterOptions();
                }
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (this.activeIndex === null || this.activeIndex >= this.filteredOptions.length - 1) {
                    this.activeIndex = 0;
                } else {
                    this.activeIndex++;
                }
                this.scrollToActive();
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (this.activeIndex === null || this.activeIndex <= 0) {
                    this.activeIndex = this.filteredOptions.length - 1;
                } else {
                    this.activeIndex--;
                }
                this.scrollToActive();
            }

            if (event.key === 'Enter' && this.activeIndex !== null) {
                event.preventDefault();
                const option = this.filteredOptions[this.activeIndex];
                this.select(option);
            }

            if (event.key === 'Escape') {
                this.open = false;
            }
        },

        scrollToActive() {
            this.$nextTick(() => {
                const activeElement = this.$el.querySelector(`[data-option-index='${this.activeIndex}']`);
                if (activeElement) {
                    activeElement.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        },

        isFocused(index) {
            return this.activeIndex === index;
        },

        handleFocus() {
            if (this.isDisabled) return;
            
            // Only open dropdown if user has typed something
            if (this.hasTyped && this.search.length >= this.minChars) {
                this.open = true;
                this.filterOptions();
            }
            // Don't show options on focus if field is empty or no typing yet
        },

        handleBlur() {
            if (this.isDisabled) return;
            
            setTimeout(() => {
                if (!this.selectedLabel || this.search !== this.selectedLabel) {
                    if (this.selectedLabel) {
                        this.search = this.selectedLabel;
                    } else {
                        this.search = '';
                    }
                }
                this.open = false;
            }, 250);
        }
    }"
    {{ $attributes->class([
        'relative',
        'dark:border-red-400! dark:shadow-red-400 text-red-400! placeholder:text-red-400!' => $invalid,
    ]) }}
>
    @if ($name)
        <input
            type="hidden"
            name="{{ $name }}"
            x-bind:value="selectedValue"
        />
    @endif

    <div x-ref="autocompleteInput" class="relative">
        @php
            $leftIcon = filled($leftIcon) ? $leftIcon : null;
            $rightIcon = filled($rightIcon) ? $rightIcon : null;
            $size = filled($size) ? $size : null;
            $disabled = filled($disabled) && $disabled;
            $inputId = filled($inputId) ? $inputId : null;
            $clearable = filled($clearable) ? $clearable : null;
            $invalid = filled($invalid) ? $invalid : null;
            $displayName = filled($displayName) ? $displayName : null;
            $placeholder = filled($placeholder) ? $placeholder : null;
        @endphp
        
        @if($disabled)
        <neura::input
            invalid="{{ $invalid }}"
            placeholder="{{ $placeholder }}"
            size="{{ $size }}"
            leftIcon="{{ $leftIcon }}"
            rightIcon="{{ $rightIcon }}"
            inputId="{{ $inputId }}"
            clearable="{{ $clearable }}"
            displayName="{{ $displayName }}"
            disabled
            x-model="search"
            x-on:input="handleInput($event)"
            x-on:keydown="handleKeydown($event)"
            x-on:focus="handleFocus"
            x-on:blur="handleBlur"
            autocomplete="off"
        />
        @else
        <neura::input
            invalid="{{ $invalid }}"
            placeholder="{{ $placeholder }}"
            size="{{ $size }}"
            leftIcon="{{ $leftIcon }}"
            rightIcon="{{ $rightIcon }}"
            inputId="{{ $inputId }}"
            clearable="{{ $clearable }}"
            displayName="{{ $displayName }}"
            x-model="search"
            x-on:input="handleInput($event)"
            x-on:keydown="handleKeydown($event)"
            x-on:focus="handleFocus"
            x-on:blur="handleBlur"
            autocomplete="off"
        />
        @endif
        @if(filled($clearable))
            <button
                type="button"
                x-on:click.stop="clear()"
                x-show="!isDisabled && (selectedValue || search.length > 0)"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 transition-colors z-20"
            >
                <neura::icon name="x-mark" class="size-4" />
            </button>
        @endif
    </div>

    <neura::autocomplete.options>
        {{ $slot }}
    </neura::autocomplete.options>
</div>