@props([
    'placeholder' => 'Type a message...',
    'name' => null,
    'rows' => 1,
    'maxRows' => 8,
    'inline' => false,
    'autofocus' => false,
    'disabled' => false,
])

@php
    $baseClasses = "w-full relative flex flex-col bg-surface-raised backdrop-blur-xl border border-edge rounded-xl shadow-sm transition-colors focus-within:border-edge-hover focus-within:ring-2 focus-within:ring-neutral-100 dark:focus-within:ring-white/5";
    
    if ($disabled) {
        $baseClasses .= " opacity-60 cursor-not-allowed pointer-events-none";
    }
    
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $xModel = $attributes->whereStartsWith('x-model')->first();
@endphp

<div {{ $attributes->except(['wire:model', 'x-model'])->merge(['class' => $baseClasses]) }}
    x-data="{
        rows: {{ $rows }},
        maxRows: {{ $maxRows }},
        resize() {
            this.$refs.textarea.style.height = 'auto';
            this.$refs.textarea.style.height = Math.min(this.$refs.textarea.scrollHeight, (this.maxRows * 24)) + 'px';
        }
    }"
    x-init="resize()"
>
    @if(isset($header))
        <div class="px-4 py-3 border-b border-separator">
            {{ $header }}
        </div>
    @endif

    <div @class([
        'flex gap-2 p-2',
        'flex-col items-stretch' => !$inline,
        'flex-row items-end' => $inline,
    ])>
        <textarea
            x-ref="textarea"
            @if($wireModel) wire:model="{{ $wireModel }}" @endif
            @if($xModel) x-model="{{ $xModel }}" @endif
            @input="resize()"
            @if($name) name="{{ $name }}" @endif
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @if($autofocus) autofocus @endif
            @if($disabled) disabled @endif
            @class([
                'bg-transparent border-0 outline-none px-2 py-1.5 text-fg placeholder:text-fg-disabled focus:ring-0 focus:outline-none sm:text-sm sm:leading-6 resize-none overflow-y-auto',
                'w-full' => !$inline,
                'flex-1 min-w-0' => $inline,
                'max-h-80' => !$inline,
            ])
            style="min-height: {{ $rows * 2.25 }}rem"
        ></textarea>

        @if(isset($actions))
            <div @class([
                'flex items-center gap-2',
                'w-full justify-between px-2 pb-1' => !$inline,
                'shrink-0 flex-shrink-0 pb-0.5' => $inline,
            ])>
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
