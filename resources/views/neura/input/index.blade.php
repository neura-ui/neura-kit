@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'prefix' => null,
    'suffix' => null,
    'leftIcon' => null,
    'rightIcon' => null,
    'prefixIcon' => null,
    'suffixIcon' => null,
    'clearable' => null,
    'copyable' => null,
    'revealable' => null,
    'invalid' => null,
    'type' => 'text',
    'mask' => null,
    'size' => neura_config('input', 'size'),
    'rounded' => neura_config('input', 'rounded'),
    'kbd' => null,
    'as' => null,
    'bindScopeToParent' => false
])

@php
    use Illuminate\View\ComponentSlot;
    use Neura\Kit\Support\PackResolver;

    $invalid ??= $name && $errors->has($name);

    $sizeClasses = PackResolver::inputSize($size ?? 'md');
    $roundedClass = PackResolver::rounded($rounded ?? 'lg');

    $classes = [

        'isolate',

        'relative flex items-stretch w-full shadow-xs disabled:shadow-none transition-colors duration-200',

        $roundedClass,

        '[&:has([data-slot=input-prefix])_input]:rounded-l-none',

        '[&:has([data-slot=input-suffix])_input]:rounded-r-none',

        '[&:has([data-slot=input-prefix]):has([data-slot=input-suffix])_input]:rounded-none',
    ];

    $iconCount = count(array_filter([$clearable, $copyable, $revealable, $rightIcon]));

    $inputAttributes = $attributes->except(['class']);
@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }}>

    @if (filled($prefix) || filled($prefixIcon))
        <neura::input.extra-slot data-slot="input-prefix">
            @if ($prefix instanceof ComponentSlot)
                {{ $prefix }}
            @elseif ($prefixIcon)
                <neura::icon name="{{ $prefixIcon }}"/>
            @endif
        </neura::input.extra-slot>
    @endif

    <div
        @unless($bindScopeToParent)

            x-data
        @endunless

        @class([

            'w-full grid isolate',

            '[&:not(:has([data-slot=left-icon]))>[data-slot=input-actions]]:col-start-2',

            '[&:has([data-slot=left-icon])>[data-slot=input-actions]]:col-start-3',

            '[&>[data-slot=input-actions]]:row-start-1',
            '[&>[data-slot=input-actions]]:place-self-center',
            '[&>[data-slot=input-actions]]:z-10',

            '[&>[data-slot=control]]:col-start-1',
            '[&>[data-slot=control]]:row-start-1',
            '[&>[data-slot=control]]:col-span-3',

            '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:col-start-1',
            '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:row-start-1',
            '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:place-self-center',

            '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:!z-20',

            '[&:has([data-slot=left-icon])>[data-slot=control]]:pl-[2.2rem]',

            '[&:has([data-slot=input-actions]):has([data-slot=input-option])>[data-slot=control]]:pr-[1.9rem]',

            '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[3.8rem]',

            '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[5.7rem]',

            '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[7.6rem]',
        ])

        @style([

            '--icon-count: '. $iconCount,
            '--icon-width: 2rem',

            'grid-template-columns: 1fr calc(var(--icon-width) * var(--icon-count))' => blank($leftIcon),

            'grid-template-columns: 2.3rem 1fr calc(var(--icon-width) * var(--icon-count))' => filled($leftIcon),
        ])
    >
        @if($leftIcon)
            <neura::icon
                name="{{ $leftIcon }}"
                class="text-neutral-500! dark:text-neutral-400! size-[1.15rem]!"
                data-slot="left-icon"
            />
        @endif

        @php
            $inputColors = PackResolver::inputColor('base');
        @endphp
        <input
            @class([
                'z-10',
                'inline-block border w-full text-neutral-900 disabled:text-neutral-500 placeholder-neutral-400 disabled:placeholder-neutral-400/70 dark:text-neutral-100 dark:disabled:text-neutral-500 dark:placeholder-neutral-500 dark:disabled:placeholder-neutral-600',
                'bg-white dark:bg-neutral-950 disabled:bg-neutral-50 dark:disabled:bg-neutral-900',
                'disabled:cursor-not-allowed transition-colors duration-150',
                'shadow-sm disabled:shadow-none',
                $roundedClass,
                'focus:ring-offset-0 focus:outline-none',
                $inputColors['border'] => !$invalid,
                $inputColors['focus'] => !$invalid,
                $inputColors['invalid'] => $invalid,
                $sizeClasses,
            ])
            name="{{ $name }}"
            type="{{ $type }}"
            data-slot="control"
            {{ $inputAttributes }}
            data-control-id="input"
            @if($invalid) invalid @endif
        />
        <div class="flex items-center justify-center h-full mr-1" data-slot="input-actions">
            @if ($copyable)
                <neura::input.options.copyable/>
            @endif
            @if ($clearable)
                <neura::input.options.clearable/>
            @endif
            @if ($revealable)
                <neura::input.options.revealable/>
            @endif

            @if ($rightIcon)
                <neura::icon
                    name="{{ $rightIcon }}"
                    class="text-neutral-500! dark:text-neutral-400!"
                    data-slot="input-option"
                />
            @endif
        </div>
    </div>

    @if (filled($suffix) || filled($suffixIcon))
        <neura::input.extra-slot data-slot="input-suffix">
            @if ($suffix instanceof ComponentSlot)
                {{ $suffix }}
            @elseif ($suffixIcon)
                <neura::icon name="{{ $suffixIcon }}"/>
            @endif
        </neura::input.extra-slot>
    @endif
</div>
