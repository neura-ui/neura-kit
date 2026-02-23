@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'placeholder' => null,
    'defaultCountry' => 'US',
    'preferredCountries' => ['US', 'GB', 'FR', 'DE', 'CA'],
    'onlyCountries' => null,
    'excludeCountries' => null,
    'showFlags' => true,
    'showDialCode' => true,
    'searchable' => true,
    'searchPlaceholder' => null,
    'disabled' => false,
    'invalid' => null,
    'size' => 'md',
    'rounded' => 'lg',
    'autoFormat' => true,
    'validateOnBlur' => true,
])

@php
    use Neura\Kit\Support\PackResolver;
    
    $invalid ??= $name && $errors->has($name);
    $sizeClasses = PackResolver::inputSize($size ?? 'md');
    $roundedClass = PackResolver::rounded($rounded ?? 'lg');
    $inputColors = PackResolver::inputColor('base');
    
    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $value;
            break;
        }
    }
@endphp

<div
    data-nk-phone-input
    x-data="neuraPhoneInput({
        defaultCountry: @js($defaultCountry),
        preferredCountries: @js($preferredCountries),
        onlyCountries: @js($onlyCountries),
        excludeCountries: @js($excludeCountries),
        showFlags: @js($showFlags),
        showDialCode: @js($showDialCode),
        searchable: @js($searchable),
        disabled: @js($disabled),
        autoFormat: @js($autoFormat),
        validateOnBlur: @js($validateOnBlur),
        wireProperty: @js($wireModel),
    })"
    {{ $attributes->only(['class'])->merge(['class' => 'relative']) }}
    x-on:click.outside="closeDropdown()"
>
    {{-- Hidden input for form submission --}}
    @if ($name)
        <input 
            type="hidden" 
            name="{{ $name }}"
            x-bind:value="fullNumber"
        />
    @endif

    {{-- Main input container --}}
    <div @class([
        'isolate relative flex items-stretch w-full shadow-xs disabled:shadow-none transition-colors duration-200',
        $roundedClass,
    ])>
        {{-- Country selector button --}}
        <button
            type="button"
            x-on:click="toggleDropdown()"
            :disabled="isDisabled"
            @class([
                'flex items-center gap-1.5 px-3 border border-r-0 bg-surface',
                'hover:bg-hover transition-colors duration-150',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-0',
                'disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-neutral-50 dark:disabled:bg-neutral-900/60',
                $inputColors['border'] => !$invalid,
                $inputColors['focus'] => !$invalid,
                $inputColors['invalid'] => $invalid,
                match($rounded) {
                    'none' => 'rounded-l-none',
                    'sm' => 'rounded-l-sm',
                    'md' => 'rounded-l-md',
                    'lg' => 'rounded-l-lg',
                    'xl' => 'rounded-l-xl',
                    'full' => 'rounded-l-full',
                    default => 'rounded-l-lg',
                },
            ])
        >
            @if($showFlags)
                <span 
                    class="text-lg leading-none"
                    x-text="selectedCountry?.flag ?? '🏳️'"
                ></span>
            @endif
            
            @if($showDialCode)
                <span 
                    class="text-sm font-medium text-neutral-700 dark:text-neutral-300 whitespace-nowrap"
                    x-text="selectedCountry ? '+' + selectedCountry.dialCode : ''"
                ></span>
            @endif
            
            <svg class="w-4 h-4 text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Phone number input --}}
        <input
            type="tel"
            x-ref="phoneInput"
            x-model="nationalNumber"
            x-on:input="handleInput($event)"
            x-on:blur="handleBlur()"
            x-on:keydown.enter.prevent
            :disabled="isDisabled"
            :placeholder="placeholder"
            inputmode="tel"
            autocomplete="tel"
            @class([
                'z-10',
                'flex-1 inline-block border w-full text-fg disabled:text-fg-muted',
                'placeholder-neutral-400 disabled:placeholder-neutral-400/70',
                'dark:placeholder-neutral-500 dark:disabled:placeholder-neutral-600',
                'bg-surface disabled:bg-neutral-50 dark:disabled:bg-neutral-900/60',
                'disabled:cursor-not-allowed transition-colors duration-150',
                'shadow-sm disabled:shadow-none',
                'focus:ring-offset-0 focus:outline-none',
                $inputColors['border'] => !$invalid,
                $inputColors['focus'] => !$invalid,
                $inputColors['invalid'] => $invalid,
                $sizeClasses,
                match($rounded) {
                    'none' => 'rounded-r-none',
                    'sm' => 'rounded-r-sm',
                    'md' => 'rounded-r-md',
                    'lg' => 'rounded-r-lg',
                    'xl' => 'rounded-r-xl',
                    'full' => 'rounded-r-full',
                    default => 'rounded-r-lg',
                },
            ])
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.lazy']) }}
            placeholder="{{ $placeholder ?? __('Phone number') }}"
        />
    </div>

    {{-- Validation message --}}
    <div 
        x-show="validationMessage && touched"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-1 text-xs"
        :class="isValid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
    >
        <span x-text="validationMessage"></span>
    </div>

    {{-- Country dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        class="absolute z-50 mt-1 w-72 max-h-80 overflow-hidden bg-surface border border-edge rounded-lg shadow-lg"
    >
        {{-- Search input --}}
        @if($searchable)
            <div class="p-2 border-b border-separator">
                <input
                    type="text"
                    x-ref="searchInput"
                    x-model="search"
                    x-on:keydown.escape="closeDropdown()"
                    x-on:keydown.arrow-down.prevent="focusNext()"
                    x-on:keydown.arrow-up.prevent="focusPrev()"
                    x-on:keydown.enter.prevent="selectFocused()"
                    class="w-full px-3 py-2 text-sm bg-surface-inset border border-edge rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-fg placeholder-neutral-400 dark:placeholder-neutral-500"
                    placeholder="{{ $searchPlaceholder ?? __('Search countries...') }}"
                />
            </div>
        @endif

        {{-- Country list --}}
        <div class="overflow-y-auto max-h-60" x-ref="countryList">
            {{-- Preferred countries --}}
            <template x-if="preferredCountriesList.length > 0 && !search">
                <div>
                    <template x-for="country in preferredCountriesList" :key="'pref-' + country.code">
                        <button
                            type="button"
                            x-on:click="selectCountry(country)"
                            x-on:mouseenter="focusedIndex = getCountryIndex(country.code)"
                            :class="{
                                'bg-primary-50 dark:bg-primary-950': focusedIndex === getCountryIndex(country.code),
                                'bg-primary-100 dark:bg-primary-900': selectedCountry?.code === country.code
                            }"
                            class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-hover transition-colors duration-150"
                        >
                            <span class="text-lg" x-text="country.flag"></span>
                            <span class="flex-1 text-sm text-fg" x-text="country.name"></span>
                            <span class="text-sm text-fg-muted" x-text="'+' + country.dialCode"></span>
                        </button>
                    </template>
                    <div class="border-b border-separator my-1"></div>
                </div>
            </template>

            {{-- All countries (filtered) --}}
            <template x-for="(country, index) in filteredCountries" :key="country.code">
                <button
                    type="button"
                    x-on:click="selectCountry(country)"
                    x-on:mouseenter="focusedIndex = index + (search ? 0 : preferredCountriesList.length)"
                    :class="{
                        'bg-primary-50 dark:bg-primary-950': focusedIndex === index + (search ? 0 : preferredCountriesList.length),
                        'bg-primary-100 dark:bg-primary-900': selectedCountry?.code === country.code
                    }"
                    class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-hover transition-colors duration-150"
                >
                    <span class="text-lg" x-text="country.flag"></span>
                    <span class="flex-1 text-sm text-fg" x-text="country.name"></span>
                    <span class="text-sm text-fg-muted" x-text="'+' + country.dialCode"></span>
                </button>
            </template>

            {{-- No results --}}
            <div
                x-show="filteredCountries.length === 0"
                class="px-3 py-4 text-center text-sm text-fg-muted"
            >
                {{ __('No countries found') }}
            </div>
        </div>
    </div>
</div>
