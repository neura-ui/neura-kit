@props([
    'disabled' => false,
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'placeholder' => neura_trans('addTags'),
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
    $inputClasses = [
        'input relative w-full border-none outline-none py-1.5 px-2 text-base text-neutral-800 transition bg-transparent duration-75 placeholder:text-neutral-400 focus:ring-0 disabled:text-neutral-500 dark:text-white dark:placeholder:text-neutral-500'
    ];
@endphp

<div
    x-data="{
        // core states
        state: [],
        newTag: '',
        focused: false,
        trimWhitespace: @js($trimWhitespace),

        error: '',
        dragIndex: -1,

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

            this.$watch('state', (value) => {
               if (this.sortTags) {
                    Alpine.nextTick(() => {
                        this.state = [...value].sort((a, b) => {
                            const result = a.localeCompare(b);
                            return this.sortDirection === 'desc' ? -result : result;
                        });
                    });
                }
            });
            Alpine.effect(()=> {
                this.$root?._x_model?.set(this.state);
            })
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

            // check for duplication - FIXED: this.message -> this.messages
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

            // add new tag and prevent any ui lags
            Alpine.mutateDom(() => {
                this.state.push(tag);
            });

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
                Alpine.mutateDom(() => {
                    this.state.splice(index, 1);
                });
                this.clearError();
            }
        },

        clearAllTags: function() {
             Alpine.mutateDom(() => {
                this.state = [];
            });
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

            Alpine.nextTick(() => {
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
                .map(key => key.replace(/[/\\\\^$*+?.()|[\\]{}]/g, '\\\\$&'))
                .join('|');

            const state = text.split(new RegExp(pattern, 'g'));

            if (state.length > 1) {
                this.newTag = '';
                state.forEach(tag => {
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

            Alpine.mutateDom(() => {
                const updatedTags = [...this.state];

                // Remove the dragged item from its original position
                const [movedTag] = updatedTags.splice(this.dragIndex, 1);

                // insert the moved h tag exactly to the new state array
                updatedTags.splice(dropIndex, 0, movedTag);

                this.state = updatedTags;
                this.dragIndex = -1;
            });
        }
    }"
    x-id="['tags-input']"
    x-modelable="state"
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->whereStartsWith('x-model') }}
    class="contents"
>
    <div {{ $attributes->class('rounded-box bg-white dark:bg-neutral-900 w-full shadow-sm border border-primary-200 transition duration-75 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/15 dark:border-primary-800 dark:focus-within:border-primary-400 dark:focus-within:ring-primary-400/15') }}>

        @if($showCounter || $showClearAll)
        <div class="flex items-center justify-between p-2 ">
            @if($showCounter)
            <span class="text-sm text-neutral-500 dark:text-neutral-400">
                <span x-text="tagCount"></span>
                @if($maxTags)
                / {{ $maxTags }}
                @endif
                {{ neura_trans('tags') }}
            </span>
            @endif

            @if($showClearAll)
                <button
                    type="button"
                    x-on:click="clearAllTags()"
                    x-bind:disabled="isEmpty"
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
            name="{{ $name }}"
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
            class="absolute z-10 max-w-40 mt-1 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 rounded-md shadow-lg"
        >
            <template x-for="(suggestion, index) in filteredSuggestions" x-bind:key="index">
                <div
                    tabindex="0"
                    x-on:click.stop="addTag(suggestion); hideSuggestions()"
                    x-on:keydown.enter="addTag(suggestion); hideSuggestions()"
                    x-text="suggestion"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-700"
                    :class="{
                        'bg-white/5 ': selectedSuggestionIndex === index
                    }"
                ></div>
            </template>
        </div>

        <div wire:ignore class="inline-block w-full">
            <template x-if="state?.length">
                <div class="flex w-full  flex-wrap gap-1.5 p-2 border-t border-t-neutral-200 dark:border-t-white/10">
                    <template x-for="(tag, index) in state" :key="`${tag}-${index}`">

                        <neura::tags-input.tag
                            :$tagVariant
                            :$tagColor
                        />
                    </template>
                </div>
            </template>
        </div>

        <div x-show="error" style="display: none;" x-transition class="p-2 text-sm text-red-600 dark:text-red-400" id="error-message">
            <span x-text="error"></span>
        </div>
    </div>
</div>
