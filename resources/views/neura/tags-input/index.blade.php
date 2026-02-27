@props([
    'disabled' => false,
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'placeholder' => neura_trans('addTags'),
    'suffix' => null,
    'maxTags' => null,
    'minTagLength' => 1,
    'maxTagLength' => 50,
    'allowDuplicates' => false,
    'allowedChars' => null,
    'blockedWords' => [],
    'splitKeys' => [' ', ',', ';'],
    'createOnBlur' => true,
    'createOnPaste' => true,
    'trimWhitespace' => true,
    'tagColor' => 'default',
    'tagVariant' => 'rounded',
    'showCounter' => true,
    'showClearAll' => true,
    'emptyMessage' => neura_trans('noTagsAdded'),
    'maxTagsMessage' => neura_trans('maximumTagsReached'),
    'duplicateMessage' => neura_trans('tagAlreadyExists'),
    'invalidMessage' => neura_trans('invalidTagFormat'),
    'ariaLabel' => neura_trans('tagsInput'),
    'ariaDescription' => null,
    'persist' => false,
    'persistKey' => null,
    'suggestions' => [],
    'allowCustom' => true,
    'sortTags' => false,
    'sortDirection' => 'asc',
])

@php
    use Illuminate\View\ComponentSlot;
    use Neura\Kit\Support\PackResolver;

    $inputColors = PackResolver::inputColor('base');
    $sizeClasses = PackResolver::inputSize($size ?? 'md');
    $roundedClass = PackResolver::rounded($rounded ?? 'lg');

    $invalid ??= $name && $errors->has($name);

    $inputClasses = [
        'z-10',
        'inline-block border w-full text-fg disabled:text-fg-muted placeholder-neutral-400 disabled:placeholder-neutral-400/70 dark:placeholder-neutral-500 dark:disabled:placeholder-neutral-600',
        'bg-surface disabled:bg-neutral-50 dark:disabled:bg-neutral-900/60',
        'disabled:cursor-not-allowed transition-colors duration-150',
        'shadow-sm disabled:shadow-none',
        'focus:ring-offset-0 focus:outline-none',
        $roundedClass,
        $inputColors['border'] => !$invalid,
        $inputColors['focus'] => !$invalid,
        $inputColors['invalid'] => $invalid,
        $sizeClasses,
    ];

    // Extract wire:model property name
    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $value;
            break;
        }
    }
@endphp

<div
    x-data="{
        // core states
        state: [],
        newTag: '',
        focused: false,
        trimWhitespace: @js($trimWhitespace),
        wireProperty: @js($wireModel),
        isLiveWire: false,
        error: '',
        dragIndex: -1,
        isSyncing: false, // Prevent sync loops

        splitKeys: @js($splitKeys),

        // handle suggestion state
        suggestions: @js($suggestions),
        filteredSuggestions: [],
        showSuggestions: false,
        selectedSuggestionIndex: -1,

        // sorting
        sortTags: @js($sortTags),
        sortDirection: @js($sortDirection),

        // validation state
        maxTags: @js($maxTags),
        minTagLength: @js($minTagLength),
        maxTagLength: @js($maxTagLength),
        allowDuplicates: @js($allowDuplicates),
        allowedChars: @js($allowedChars),
        blockedWords: @js($blockedWords),
        allowCustom: @js($allowCustom),
        createOnBlur: @js($createOnBlur),
        createOnPaste: @js($createOnPaste),

        // error messages
        messages: {
            maxTags: @js($maxTagsMessage),
            duplicate: @js($duplicateMessage),
            invalid: @js($invalidMessage),
            empty: @js($emptyMessage)
        },

        init: function() {
            // Check if using wire:model.live
            if (this.wireProperty) {
                // Check the actual attribute on the element
                const wireModelAttr = Array.from(this.$root.attributes)
                    .find(attr => attr.name.startsWith('wire:model'));
                this.isLiveWire = wireModelAttr?.name.includes('.live') || false;
            }

            // Initialize state from Livewire/Alpine model
            this.$nextTick(() => {
                this.syncFromWire();
            });

            // Watch for changes from Livewire (Livewire -> Alpine)
            if (this.wireProperty && this.$wire) {
                this.$wire.$watch(this.wireProperty, (value) => {
                    if (this.isSyncing) return; // Prevent loop

                    this.isSyncing = true;
                    this.state = Array.isArray(value) ? value : (value ? [value] : []);

                    this.$nextTick(() => {
                        this.isSyncing = false;
                    });
                });
            }

            // Watch state changes and sync back to Livewire/Alpine (Alpine -> Livewire)
            this.$watch('state', (value) => {
                if (this.isSyncing) return; // Prevent loop

                // Apply sorting if enabled
                if (this.sortTags) {
                    const sorted = [...value].sort((a, b) => {
                        const result = a.localeCompare(b);
                        return this.sortDirection === 'desc' ? -result : result;
                    });

                    // Only update if actually different
                    if (JSON.stringify(sorted) !== JSON.stringify(value)) {
                        this.isSyncing = true;
                        this.state = sorted;
                        this.$nextTick(() => {
                            this.isSyncing = false;
                        });
                        return;
                    }
                }

                // Sync to Alpine x-model
                if (this.$root?._x_model) {
                    this.$root._x_model.set(value);
                }

                // Sync to Livewire wire:model
                if (this.wireProperty && this.$wire) {
                    this.isSyncing = true;

                    // Use $commit for .live modifier, set for regular
                    if (this.isLiveWire) {
                        this.$wire.$commit();
                    }
                    this.$wire.set(this.wireProperty, value);

                    this.$nextTick(() => {
                        this.isSyncing = false;
                    });
                }
            });
        },

        syncFromWire: function() {
            if (this.wireProperty && this.$wire) {
                const wireValue = this.$wire.get(this.wireProperty);
                this.state = Array.isArray(wireValue) ? wireValue : (wireValue ? [wireValue] : []);
            } else if (this.$root?._x_model) {
                const modelValue = this.$root._x_model.get();
                this.state = Array.isArray(modelValue) ? modelValue : (modelValue ? [modelValue] : []);
            }
        },

        addTag: function(tag) {
            if (!tag) return false;

            if (this.trimWhitespace) {
                tag = tag.trim();
                if (!tag) return false;
            }

            // check if we hit the maximum num allowed
            if (this.maxTags && this.state.length >= this.maxTags) {
                this.showError(this.messages.maxTags);
                return false;
            }

            // validate the new tag
            if (!this.validateTag(tag)) {
                this.showError(this.messages.invalid);
                return false;
            }

            // check for duplication
            if (!this.allowDuplicates) {
                const exists = this.state.some(t => t.toLowerCase() === tag.toLowerCase());
                if (exists) {
                    this.showError(this.messages.duplicate);
                    return false;
                }
            }

            // then we check if we're allowed to accept custom state if there is suggestion
            if (!this.allowCustom && !this.suggestions.includes(tag)) {
                this.showError(window.t('onlyPredefinedTagsAllowed'));
                return false;
            }

            // add new tag - use immutable update for better reactivity
            this.state = [...this.state, tag];
            this.newTag = '';
            this.clearError();
            return true;
        },

        // new state validations
        validateTag: function(tag) {
            // Length validation
            if (tag.length < this.minTagLength || tag.length > this.maxTagLength) {
                return false;
            }

            // Character validation
            if (this.allowedChars && !new RegExp(this.allowedChars).test(tag)) {
                return false;
            }

            // convenient to prevent blocked words
            if (this.blockedWords.some((word) => tag.toLowerCase() === word.toLowerCase())) {
                return false;
            }

            return true;
        },

        deleteTag: function(index) {
            if (typeof index === 'string') {
                // If called with tag value, find index
                index = this.state.findIndex(tag => tag === index);
            }
            if (index >= 0 && index < this.state.length) {
                this.state = this.state.filter((_, i) => i !== index);
                this.clearError();
            }
        },

        clearAllTags: function() {
            this.state = [];
            this.clearError();
        },

        showError: function(message) {
            this.error = message;
            setTimeout(() => this.clearError(), 3000);
        },

        clearError: function() {
            this.error = '';
        },

        selectSuggestion: function(index) {
            if (index >= 0 && index < this.filteredSuggestions.length) {
                this.addTag(this.filteredSuggestions[index]);
            }
        },

        hideSuggestions: function() {
            this.showSuggestions = false;
            this.selectedSuggestionIndex = -1;
        },

        updateSuggestions: function() {
            const query = this.newTag.toLowerCase().trim();

            if (!query || this.suggestions.length === 0) {
                this.filteredSuggestions = [];
                this.showSuggestions = false;
                return;
            }

            this.filteredSuggestions = this.suggestions
                .filter(s => s.toLowerCase().includes(query) && !this.state.includes(s))
                .slice(0, 5);

            this.showSuggestions = this.filteredSuggestions.length > 0;
            this.selectedSuggestionIndex = -1;
        },

        handleKeydown: function(event) {
            if (event.key === 'Enter' ) {
                event.preventDefault();

                if (this.selectedSuggestionIndex >= 0 && this.filteredSuggestions[this.selectedSuggestionIndex]) {
                    this.addTag(this.filteredSuggestions[this.selectedSuggestionIndex]);
                    this.hideSuggestions();
                } else if (this.newTag.trim()) {
                    this.addTag(this.newTag);
                }

            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (this.filteredSuggestions.length > 0) {
                    this.selectedSuggestionIndex = (this.selectedSuggestionIndex + 1) % this.filteredSuggestions.length;
                }

            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (this.filteredSuggestions.length > 0) {
                    this.selectedSuggestionIndex = (this.selectedSuggestionIndex - 1 + this.filteredSuggestions.length) % this.filteredSuggestions.length;
                }

            } else if (event.key === 'Backspace' && !this.newTag && this.state.length > 0) {
                this.deleteTag(this.state.length - 1);
            }
        },

        handlePaste: function(event) {
            if (!this.createOnPaste) return;

            this.$nextTick(() => {
                this.processSplitKeys(this.newTag);
            });
        },

        handleInput: function(event) {
            const inputValue = event.target.value;
            const lastChar = inputValue.slice(-1);

            // Check if the last character is a split key
            if (this.splitKeys.includes(lastChar)) {
                // Get the tag without the split key
                const tagToAdd = inputValue.slice(0, -1);

                if (tagToAdd.trim()) {
                    // Add the tag and clear the input
                    this.addTag(tagToAdd);
                    // Clear the input by updating newTag
                    this.newTag = '';
                    // Update the actual input value
                    event.target.value = '';
                } else {
                    // If there's no content before the split key, just remove it
                    this.newTag = '';
                    event.target.value = '';
                }
            } else {
                // Normal input handling
                this.newTag = inputValue;
                this.updateSuggestions();
            }
        },

        processSplitKeys: function(text) {
            const pattern = this.splitKeys
                .map(key => key.replace(/[/\\^$*+?.()|[\]{}]/g, '\\$&'))
                .join('|');

            const tags = text.split(new RegExp(pattern, 'g'));

            if (tags.length > 1) {
                this.newTag = '';
                tags.forEach(tag => {
                    if (tag.trim()) {
                        this.addTag(tag);
                    }
                });
            }
        },

        hasMaxTags: function() {
            return this.maxTags && this.state.length >= this.maxTags;
        },

        isEmpty: function() {
            return this.state.length === 0;
        },

        tagCount: function() {
            return this.state.length;
        },

        onDragStart: function(index){
            this.dragIndex = index;
        },

        onDrop: function(event, dropIndex){
            if(this.dragIndex === -1 || this.dragIndex === dropIndex) return;

            const updatedTags = [...this.state];
            const [movedTag] = updatedTags.splice(this.dragIndex, 1);
            updatedTags.splice(dropIndex, 0, movedTag);

            this.state = updatedTags;
            this.dragIndex = -1;
        }
    }"
    x-id="['tags-input']"
    x-modelable="state"
    class="contents"
>
    <div {{ $attributes->merge(['class' => 'rounded-box w-full transition duration-75']) }}>

        @if($showCounter || $showClearAll)
            <div class="flex items-center justify-between p-2 ">
                @if($showCounter)
                    <span class="text-sm text-fg-muted">
                        <span x-text="tagCount()"></span>
                        @if($maxTags)
                            / {{ $maxTags }}
                        @endif
                        @if(empty($suffix))
                            {{ neura_trans('tags') }}
                        @else
                            {!! $suffix !!}
                        @endif
                    </span>
                @endif

                @if($showClearAll)
                    <button
                        type="button"
                        x-on:click="clearAllTags()"
                        x-bind:disabled="isEmpty()"
                        class="text-sm hover:opacity-70 transition-opacity text-neutral-400 dark:text-neutral-300 duration-300 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <neura::icon name="trash" class="size-5"/>
                    </button>
                @endif
            </div>
        @endif

        <input
            type="text"
            @class(Arr::toCssClasses($inputClasses))
            placeholder="{{ $placeholder }}"
            x-bind:disabled="hasMaxTags() || @js($disabled)"
            x-on:focus="focused = true"
            x-on:blur="focused = false; createOnBlur && newTag.trim() && addTag(newTag); hideSuggestions()"
            x-on:paste="handlePaste"
            x-model="newTag"
            x-on:input.stop="handleInput"
            x-on:change.stop
            x-bind:id="$id('tags-input')"
            x-ref="input"
            role="textbox"
            x-bind:aria-label="@js($ariaLabel)"
            x-bind:aria-describedby="error ? 'error-message' : ''"
            x-on:keydown="handleKeydown"
        >

        <div
            x-show="showSuggestions"
            x-transition
            x-ref="suggestions"
            x-anchor.bottom-start="$refs.input"
            class="absolute z-10 max-w-40 mt-1 bg-surface border border-edge rounded-md shadow-lg"
        >
            <template x-for="(suggestion, index) in filteredSuggestions" x-bind:key="index">
                <div
                    tabindex="0"
                    x-on:click.stop="addTag(suggestion); hideSuggestions()"
                    x-on:keydown.enter="addTag(suggestion); hideSuggestions()"
                    x-text="suggestion"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-hover"
                    :class="{ 'bg-white/5': selectedSuggestionIndex === index }"
                ></div>
            </template>
        </div>

        <div wire:ignore class="inline-block w-full">
            <template x-if="state?.length">
                <div class="flex w-full flex-wrap gap-1.5 p-2 border-t border-t-separator">
                    <template x-for="(tag, index) in state" :key="`${tag}-${index}`">
                        <neura::tags-input.tag
                            :$tagVariant
                            :$tagColor
                        />
                    </template>
                </div>
            </template>
        </div>

        <div x-show="error" x-transition class="p-2 text-sm text-red-600 dark:text-red-400" id="error-message">
            <span x-text="error"></span>
        </div>
    </div>
</div>
