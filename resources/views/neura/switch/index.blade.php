@props([
    'align' => 'right',
    'label' => '',
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'description' => null,
    'disabled' => false,
    'maxWidth' => 'max-w-md',
    'checked' => false,
    'size' => 'md',
    'switchClass' => '',
    'thumbClass' => '',
    'iconOn' => null,
    'iconOff' => null,
    'onClass' => '',
    'offClass' => '',
    'thumbOnClass' => '',
    'thumbOffClass' => '',
])

@php
    use Neura\Kit\Support\PackResolver;

    $id = $name ?? (string) Str::uuid();

    $colors = PackResolver::inputColor('switch');

    $sizeConfig = match ($size) {
        'sm' => [
            'switch' => 'h-4 w-7',
            'thumb' => 'size-3',
            'activeTranslate' => 'translate-x-3',
            'iconSize' => 'size-2.5',
        ],
        'lg' => [
            'switch' => 'h-8 w-14',
            'thumb' => 'size-7',
            'activeTranslate' => 'translate-x-6',
            'iconSize' => 'size-6',
        ],
        default => [
            'switch' => 'h-6 w-11',
            'thumb' => 'size-5',
            'activeTranslate' => 'translate-x-5',
            'iconSize' => 'size-3',
        ],
    };

    $wrapperClasses = ['w-fit', $maxWidth];

    $containerClasses = ['flex items-center gap-x-3', $align === 'left' ? 'flex-row' : 'flex-row-reverse'];

    $switchBaseClasses = [
        'relative inline-flex flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-200 ease-in-out select-none',
        'focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:focus:ring-primary-400/20',
        'disabled:cursor-not-allowed disabled:opacity-50',
        $sizeConfig['switch'],
        $switchClass,
    ];

    $thumbBaseClasses = [
        'pointer-events-none inline-flex items-center justify-center transform rounded-full shadow-sm ring-0 transition duration-200 ease-in-out',
        $sizeConfig['thumb'],
        $thumbClass,
    ];
@endphp

<div {{ $attributes->class(Arr::toCssClasses($wrapperClasses)) }} x-data="{
    checked: @js($checked),
    toggle() {
        if (@js($disabled)) return;
        this.checked = !this.checked;
        this.$dispatch('input', this.checked);
    }
}" @click="toggle">

    <div class="{{ Arr::toCssClasses($containerClasses) }}">
        <button type="button" role="switch" aria-labelledby="{{ $id }}-label" @disabled($disabled)
            x-bind:aria-checked="checked" @click.stop="toggle" @class($switchBaseClasses)
            x-bind:class="checked ? '{{ $colors['trackActive'] }} {{ $onClass }}' : '{{ $colors['track'] }} {{ $offClass }}'">
            <span @class($thumbBaseClasses)
                x-bind:class="checked ? '{{ $sizeConfig['activeTranslate'] }} {{ $colors['thumbActive'] }} {{ $thumbOnClass }}' :
                    'translate-x-[0.05rem] {{ $colors['thumb'] }} {{ $thumbOffClass }}'">
                @if ($iconOn)
                    <neura::icon name="{{ $iconOn }}" x-show="checked"
                        class="{{ $sizeConfig['iconSize'] }} text-white" style="display:none" />
                @endif

                @if ($iconOff)
                    <neura::icon name="{{ $iconOff }}" x-show="!checked"
                        class="{{ $sizeConfig['iconSize'] }} text-neutral-400" style="display:none" />
                @endif
            </span>
        </button>

        {{-- label --}}
        @if ($label)
            <label id="{{ $id }}-label"
                class="block text-start flex-1 text-sm font-medium text-neutral-700 dark:text-neutral-300 cursor-pointer select-none"
                @if (!$disabled) @click.stop="toggle" @endif>
                {{ $label }}
            </label>
        @endif

        @if ($name)
            <input type="hidden" name="{{ $name }}" x-bind:value="checked ? '1' : '0'">
        @endif
    </div>

    @if ($description)
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400 text-start">
            {{ $description }}
        </p>
    @endif
</div>
