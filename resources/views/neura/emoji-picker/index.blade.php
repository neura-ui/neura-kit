@props([
    'value' => null,
    'name' => null,
    'disabled' => false,
    'label' => null,
    'hint' => null,
    'placeholder' => 'Type or pick an emoji…',
    'size' => neura_config('input', 'size'),
    'rounded' => neura_config('input', 'rounded'),
    'shadow' => neura_config('input', 'shadow'),
    'variant' => 'input', // input | button
    'closeOnSelect' => true,
    'for' => null,
    'popupAlign' => 'left',
    'popupPlacement' => 'bottom', // bottom | top
    'class' => '',
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;

    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $attrValue) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $attrValue;
            break;
        }
    }

    $sizeClasses = PackResolver::inputSize($size ?? 'md');
    $roundedClass = PackResolver::rounded($rounded ?? neura_config('input', 'rounded'));
    $shadowClass = PackResolver::shadow($shadow ?? neura_config('input', 'shadow'));
    $inputColors = PackResolver::inputColor('base');

    $popupRoundedClass = PackResolver::rounded(neura_config('popup', 'rounded'));
    $popupShadowClass = PackResolver::shadow(neura_config('popup', 'shadow'));

    $popupContainerClass = Arr::toCssClasses([
        'absolute z-[200] w-80 max-w-[min(20rem,calc(100vw-2rem))]',
        $popupAlign === 'right' ? 'right-0' : 'left-0',
        $popupPlacement === 'top' ? 'bottom-full mb-2' : 'top-full mt-2',
        'p-2.5',
        'bg-surface-raised backdrop-blur-xl border border-edge',
        $popupRoundedClass,
        $popupShadowClass,
        'text-fg',
    ]);

    $wireModelAttrs = $attributes->whereStartsWith('wire:model');
    $xModelAttrs = $attributes->whereStartsWith('x-model');
    $hasModel = $name || $wireModelAttrs->isNotEmpty() || $xModelAttrs->isNotEmpty();
    $isButton = $variant === 'button';

    $rootClasses = Arr::toCssClasses([
        'relative',
        $isButton ? 'inline-flex w-auto shrink-0' : 'block w-full',
        $class,
    ]);
@endphp

<div
    {{ $attributes->except(['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.defer', 'x-model', 'class'])->merge(['class' => $rootClasses]) }}
    data-nk-emoji-picker
    x-data="neuraEmojiPicker({
        initialValue: @js($value),
        disabled: @js((bool) $disabled),
        wireProperty: @js($wireModel),
        closeOnSelect: @js(filter_var($closeOnSelect, FILTER_VALIDATE_BOOLEAN)),
        for: @js($for),
    })"
>
    @if ($hasModel)
        <input
            type="hidden"
            @if($name) name="{{ $name }}" @endif
            {{ $wireModelAttrs }}
            {{ $xModelAttrs }}
            x-ref="hidden"
        />
    @endif

    @if ($label && ! $isButton)
        <label class="mb-1 block text-sm font-medium text-fg">
            {{ $label }}
        </label>
    @endif

    @if ($isButton)
        <button
            type="button"
            @click.stop="toggle()"
            @disabled($disabled)
            class="inline-flex size-8 items-center justify-center rounded-md text-fg-muted transition-colors hover:bg-hover hover:text-fg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-50"
            :aria-expanded="open"
            aria-label="{{ __('Pick an emoji') }}"
            title="{{ __('Emoji') }}"
        >
            <neura::icon name="face-smile" class="size-4" />
        </button>
    @else
        <div @class([
            'isolate relative flex items-stretch w-full transition-colors duration-200',
            $roundedClass,
        ])>
            <div
                @class([
                    'w-full grid isolate',
                    '[&>[data-slot=input-actions]]:col-start-2',
                    '[&>[data-slot=input-actions]]:row-start-1',
                    '[&>[data-slot=input-actions]]:place-self-center',
                    '[&>[data-slot=input-actions]]:z-10',
                    '[&>[data-slot=control]]:col-start-1',
                    '[&>[data-slot=control]]:row-start-1',
                    '[&>[data-slot=control]]:col-span-2',
                    '[&:has([data-slot=input-actions])>[data-slot=control]]:pr-10',
                ])
                style="grid-template-columns: 1fr 2.5rem"
            >
                <input
                    x-ref="input"
                    type="text"
                    placeholder="{{ $placeholder }}"
                    autocomplete="off"
                    data-slot="control"
                    @disabled($disabled)
                    :value="value"
                    x-on:input="onInput($event)"
                    x-on:keydown.escape="open = false"
                    @class([
                        'z-10 inline-block border w-full text-fg disabled:text-fg-muted',
                        'placeholder-neutral-400 dark:placeholder-neutral-500',
                        'bg-surface disabled:bg-neutral-50 dark:disabled:bg-neutral-900/60',
                        'disabled:cursor-not-allowed transition-colors duration-150',
                        $shadowClass,
                        'disabled:shadow-none',
                        $roundedClass,
                        'focus:ring-offset-0 focus:outline-none',
                        $inputColors['border'],
                        $inputColors['focus'],
                        $sizeClasses,
                    ])
                />

                <div class="flex items-center justify-end h-full pr-1.5" data-slot="input-actions">
                    <button
                        type="button"
                        @click.stop="toggle()"
                        @disabled($disabled)
                        class="inline-flex size-7 items-center justify-center rounded-md text-fg-muted transition-colors hover:bg-hover hover:text-fg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-50"
                        :aria-expanded="open"
                        aria-label="{{ __('Pick an emoji') }}"
                    >
                        <neura::icon name="face-smile" class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($hint && ! $isButton)
        <p class="mt-1 text-xs text-fg-muted">{{ $hint }}</p>
    @endif

    <div
        x-show="open && !isDisabled"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
        @click.stop=""
        @class([$popupContainerClass])
        style="display:none;"
        role="dialog"
        aria-label="{{ __('Emoji picker') }}"
    >
        <div class="mb-2">
            <input
                type="text"
                x-ref="search"
                x-model="query"
                placeholder="{{ __('Search emoji…') }}"
                @keydown.escape.stop="open = false"
                autocomplete="off"
                @class([
                    'w-full border bg-surface text-fg placeholder-neutral-400 dark:placeholder-neutral-500',
                    'transition-colors duration-150 focus:ring-offset-0 focus:outline-none',
                    $roundedClass,
                    $inputColors['border'],
                    $inputColors['focus'],
                    'text-sm px-2.5 py-2',
                ])
            />
        </div>

        <div class="mb-2 flex items-center gap-0.5 overflow-x-auto pb-1 border-b border-separator">
            <button
                type="button"
                x-show="recent.length > 0"
                @click="setCategory('recent')"
                :class="category === 'recent' && !query ? 'bg-active text-fg' : 'text-fg-muted hover:text-fg hover:bg-hover'"
                class="shrink-0 size-8 rounded-md text-base transition-colors"
                title="{{ __('Recent') }}"
            >🕒</button>
            <template x-for="cat in categories" :key="cat.id">
                <button
                    type="button"
                    @click="setCategory(cat.id)"
                    :class="category === cat.id && !query ? 'bg-active text-fg' : 'text-fg-muted hover:text-fg hover:bg-hover'"
                    class="shrink-0 size-8 rounded-md text-base transition-colors"
                    :title="cat.label"
                    x-text="cat.icon"
                ></button>
            </template>
        </div>

        <div class="max-h-56 overflow-y-auto overscroll-contain">
            <div
                x-show="filteredEmojis().length === 0"
                class="py-8 text-center text-sm text-fg-muted"
            >
                {{ __('No emoji found') }}
            </div>

            <div class="grid grid-cols-8 gap-0.5">
                <template x-for="(item, index) in filteredEmojis()" :key="item.e + '-' + index">
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-md text-xl leading-none transition-colors hover:bg-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25"
                        :title="item.n"
                        @click="choose(item.e)"
                        x-text="item.e"
                    ></button>
                </template>
            </div>
        </div>

        @unless ($isButton)
            <div class="mt-2 flex items-center justify-between gap-2 border-t border-separator pt-2 px-0.5">
                <span class="text-xs text-fg-muted truncate" x-text="value || '{{ __('No selection') }}'"></span>
                <button
                    type="button"
                    class="text-xs text-fg-muted hover:text-fg transition-colors"
                    @click="clear()"
                >
                    {{ __('Clear') }}
                </button>
            </div>
        @endunless
    </div>
</div>
