@props([
    'placeholder' => 'Type a message…',
    'name' => null,
    'id' => null,
    'rows' => 1,
    'maxRows' => 8,
    'inline' => false,
    'autofocus' => false,
    'disabled' => false,
    'submitOnEnter' => true,
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;

    $composerRoundedClass = PackResolver::rounded(neura_config('composer', 'rounded'));
    $composerShadowClass = PackResolver::shadow(neura_config('composer', 'shadow'));
    $inputColors = PackResolver::inputColor('base');

    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $xModel = $attributes->whereStartsWith('x-model')->first();

    $hasHeader = isset($header);
    $hasTools = isset($tools);
    $hasSubmit = isset($submit);
    $hasActions = isset($actions);
    $hasStructuredToolbar = $hasTools || $hasSubmit;
    $hasToolbar = $hasStructuredToolbar || $hasActions;
    $submitOnEnter = filter_var($submitOnEnter, FILTER_VALIDATE_BOOLEAN);

    $baseClasses = Arr::toCssClasses([
        'group/composer relative flex w-full flex-col',
        'bg-surface',
        'border',
        $inputColors['border'],
        $composerRoundedClass,
        $composerShadowClass,
        'transition-colors duration-150',
        'outline-none ring-offset-0',
        'focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/25',
        'dark:focus-within:border-primary-400 dark:focus-within:ring-primary-400/25',
        $disabled ? 'pointer-events-none cursor-not-allowed opacity-60' : null,
    ]);

    $toolSlotClasses = Arr::toCssClasses([
        'flex items-center gap-0.5',
        // Quiet icon row — don't stretch children
        '[&_[data-nk-emoji-picker]]:shrink-0',
        '[&_button]:size-8 [&_button]:shrink-0 [&_button]:rounded-lg',
        '[&_button]:text-fg-muted [&_button]:hover:text-fg [&_button]:hover:bg-hover',
        '[&_button]:transition-colors [&_button]:duration-150',
        '[&_button]:focus-visible:outline-none [&_button]:focus-visible:ring-2 [&_button]:focus-visible:ring-primary/25',
    ]);

    $submitSlotClasses = Arr::toCssClasses([
        'flex items-center',
        '[&_button:disabled]:opacity-40 [&_button:disabled]:grayscale',
    ]);
@endphp

<div
    {{ $attributes->except(['wire:model', 'x-model'])->merge(['class' => $baseClasses]) }}
    data-slot="composer"
    data-nk-composer
    x-data="{
        rows: {{ (int) $rows }},
        maxRows: {{ (int) $maxRows }},
        empty: true,
        lineHeight: 24,
        resize() {
            const el = this.$refs.textarea;
            if (!el) return;
            el.style.height = 'auto';
            const max = this.maxRows * this.lineHeight;
            el.style.height = Math.min(el.scrollHeight, max) + 'px';
            this.empty = !(el.value || '').trim();
        },
        onKeydown(event) {
            @if($submitOnEnter)
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                if (!this.empty) this.$dispatch('composer-submit');
            }
            @endif
        },
    }"
    x-init="
        $nextTick(() => {
            const styles = window.getComputedStyle($refs.textarea);
            const lh = parseFloat(styles.lineHeight);
            if (!Number.isNaN(lh)) lineHeight = lh;
            resize();
        });
    "
>
    @if ($hasHeader)
        <div
            data-slot="composer-header"
            class="border-b border-separator px-3.5 py-2.5"
        >
            {{ $header }}
        </div>
    @endif

    <div
        data-slot="composer-body"
        @class([
            'flex min-w-0',
            'flex-col' => ! $inline,
            'flex-row items-end gap-2 px-2 py-2' => $inline,
        ])
    >
        <div @class([
            'relative min-w-0',
            'w-full' => ! $inline,
            'flex-1' => $inline,
        ])>
            <label class="sr-only" @if($id) for="{{ $id }}" @endif>
                {{ $placeholder }}
            </label>
            <textarea
                x-ref="textarea"
                @if($id) id="{{ $id }}" @endif
                data-slot="composer-input"
                @if($wireModel) wire:model="{{ $wireModel }}" @endif
                @if($xModel) x-model="{{ $xModel }}" @endif
                @input="resize()"
                @keydown="onKeydown($event)"
                @if($name) name="{{ $name }}" @endif
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                @if($autofocus) autofocus @endif
                @if($disabled) disabled @endif
                @class([
                    'block w-full resize-none overflow-y-auto bg-transparent',
                    'border-0 text-[15px] leading-6 text-fg',
                    'placeholder:text-fg-muted',
                    'outline-none focus:outline-none focus:ring-0',
                    'disabled:cursor-not-allowed',
                    'max-h-72',
                    $inline ? 'px-2.5 py-2' : 'px-4 pt-3.5 pb-2',
                ])
                style="min-height: {{ max(1, (int) $rows) * 1.5 }}rem"
            ></textarea>
        </div>

        @if ($inline && $hasToolbar)
            <div data-slot="composer-toolbar" class="flex shrink-0 items-center gap-1.5 pr-0.5 pb-0.5">
                @if ($hasStructuredToolbar)
                    @if ($hasTools)
                        <div data-slot="composer-tools" class="{{ $toolSlotClasses }}">
                            {{ $tools }}
                        </div>
                    @endif
                    @if ($hasSubmit)
                        <div data-slot="composer-submit" class="{{ $submitSlotClasses }}">
                            {{ $submit }}
                        </div>
                    @endif
                @else
                    {{ $actions }}
                @endif
            </div>
        @endif
    </div>

    @if (! $inline && $hasToolbar)
        <div
            data-slot="composer-footer"
            class="flex items-center justify-between gap-3 px-2.5 pb-2.5 pt-0.5 sm:px-3 sm:pb-3"
        >
            @if ($hasStructuredToolbar)
                <div data-slot="composer-tools" class="{{ $toolSlotClasses }}">
                    @if ($hasTools)
                        {{ $tools }}
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if ($submitOnEnter)
                        <p
                            class="hidden select-none text-[11px] leading-none text-fg-disabled sm:block"
                            x-show="!empty"
                            x-transition.opacity.duration.150ms
                            x-cloak
                        >
                            <span class="text-fg-muted">↵</span> send · <span class="text-fg-muted">⇧↵</span> line
                        </p>
                    @endif

                    @if ($hasSubmit)
                        <div data-slot="composer-submit" class="{{ $submitSlotClasses }}">
                            {{ $submit }}
                        </div>
                    @endif
                </div>
            @else
                <div data-slot="composer-actions" class="flex w-full items-center justify-between gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
</div>
