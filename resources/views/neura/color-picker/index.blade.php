@props([
    'value' => null,
    'name' => null,
    'disabled' => false,
    'label' => null,
    'hint' => null,
    'leftIcon' => null,
    'placeholder' => null,
    'size' => neura_config('input', 'size'),
    'rounded' => neura_config('input', 'rounded'),
    'popupVariant' => 'default',
    'popupSize' => 'md',
    'popupAlign' => 'left',
    'class' => '',
    'colors' => [],
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;

    // Palette Tailwind (subset, mais couvrant l'essentiel)
    $tailwind = [
        // slate
        ['token' => 'slate-50', 'hex' => '#f8fafc'], ['token' => 'slate-100', 'hex' => '#f1f5f9'], ['token' => 'slate-200', 'hex' => '#e2e8f0'], ['token' => 'slate-300', 'hex' => '#cbd5e1'], ['token' => 'slate-400', 'hex' => '#94a3b8'], ['token' => 'slate-500', 'hex' => '#64748b'], ['token' => 'slate-600', 'hex' => '#475569'], ['token' => 'slate-700', 'hex' => '#334155'], ['token' => 'slate-800', 'hex' => '#1e293b'], ['token' => 'slate-900', 'hex' => '#0f172a'],
        // zinc
        ['token' => 'zinc-50', 'hex' => '#fafafa'], ['token' => 'zinc-100', 'hex' => '#f4f4f5'], ['token' => 'zinc-200', 'hex' => '#e4e4e7'], ['token' => 'zinc-300', 'hex' => '#d4d4d8'], ['token' => 'zinc-400', 'hex' => '#a1a1aa'], ['token' => 'zinc-500', 'hex' => '#71717a'], ['token' => 'zinc-600', 'hex' => '#52525b'], ['token' => 'zinc-700', 'hex' => '#3f3f46'], ['token' => 'zinc-800', 'hex' => '#27272a'], ['token' => 'zinc-900', 'hex' => '#18181b'],
        // red
        ['token' => 'red-50', 'hex' => '#fef2f2'], ['token' => 'red-100', 'hex' => '#fee2e2'], ['token' => 'red-200', 'hex' => '#fecaca'], ['token' => 'red-300', 'hex' => '#fca5a5'], ['token' => 'red-400', 'hex' => '#f87171'], ['token' => 'red-500', 'hex' => '#ef4444'], ['token' => 'red-600', 'hex' => '#dc2626'], ['token' => 'red-700', 'hex' => '#b91c1c'], ['token' => 'red-800', 'hex' => '#991b1b'], ['token' => 'red-900', 'hex' => '#7f1d1d'],
        // orange
        ['token' => 'orange-50', 'hex' => '#fff7ed'], ['token' => 'orange-100', 'hex' => '#ffedd5'], ['token' => 'orange-200', 'hex' => '#fed7aa'], ['token' => 'orange-300', 'hex' => '#fdba74'], ['token' => 'orange-400', 'hex' => '#fb923c'], ['token' => 'orange-500', 'hex' => '#f97316'], ['token' => 'orange-600', 'hex' => '#ea580c'], ['token' => 'orange-700', 'hex' => '#c2410c'], ['token' => 'orange-800', 'hex' => '#9a3412'], ['token' => 'orange-900', 'hex' => '#7c2d12'],
        // amber
        ['token' => 'amber-50', 'hex' => '#fffbeb'], ['token' => 'amber-100', 'hex' => '#fef3c7'], ['token' => 'amber-200', 'hex' => '#fde68a'], ['token' => 'amber-300', 'hex' => '#fcd34d'], ['token' => 'amber-400', 'hex' => '#fbbf24'], ['token' => 'amber-500', 'hex' => '#f59e0b'], ['token' => 'amber-600', 'hex' => '#d97706'], ['token' => 'amber-700', 'hex' => '#b45309'], ['token' => 'amber-800', 'hex' => '#92400e'], ['token' => 'amber-900', 'hex' => '#78350f'],
        // yellow
        ['token' => 'yellow-50', 'hex' => '#fefce8'], ['token' => 'yellow-100', 'hex' => '#fef9c3'], ['token' => 'yellow-200', 'hex' => '#fef08a'], ['token' => 'yellow-300', 'hex' => '#fde047'], ['token' => 'yellow-400', 'hex' => '#facc15'], ['token' => 'yellow-500', 'hex' => '#eab308'], ['token' => 'yellow-600', 'hex' => '#ca8a04'], ['token' => 'yellow-700', 'hex' => '#a16207'], ['token' => 'yellow-800', 'hex' => '#854d0e'], ['token' => 'yellow-900', 'hex' => '#713f12'],
        // green
        ['token' => 'green-50', 'hex' => '#f0fdf4'], ['token' => 'green-100', 'hex' => '#dcfce7'], ['token' => 'green-200', 'hex' => '#bbf7d0'], ['token' => 'green-300', 'hex' => '#86efac'], ['token' => 'green-400', 'hex' => '#4ade80'], ['token' => 'green-500', 'hex' => '#22c55e'], ['token' => 'green-600', 'hex' => '#16a34a'], ['token' => 'green-700', 'hex' => '#15803d'], ['token' => 'green-800', 'hex' => '#166534'], ['token' => 'green-900', 'hex' => '#14532d'],
        // teal
        ['token' => 'teal-50', 'hex' => '#f0fdfa'], ['token' => 'teal-100', 'hex' => '#ccfbf1'], ['token' => 'teal-200', 'hex' => '#99f6e4'], ['token' => 'teal-300', 'hex' => '#5eead4'], ['token' => 'teal-400', 'hex' => '#2dd4bf'], ['token' => 'teal-500', 'hex' => '#14b8a6'], ['token' => 'teal-600', 'hex' => '#0d9488'], ['token' => 'teal-700', 'hex' => '#0f766e'], ['token' => 'teal-800', 'hex' => '#115e59'], ['token' => 'teal-900', 'hex' => '#134e4a'],
        // cyan
        ['token' => 'cyan-50', 'hex' => '#ecfeff'], ['token' => 'cyan-100', 'hex' => '#cffafe'], ['token' => 'cyan-200', 'hex' => '#a5f3fc'], ['token' => 'cyan-300', 'hex' => '#67e8f9'], ['token' => 'cyan-400', 'hex' => '#22d3ee'], ['token' => 'cyan-500', 'hex' => '#06b6d4'], ['token' => 'cyan-600', 'hex' => '#0891b2'], ['token' => 'cyan-700', 'hex' => '#0e7490'], ['token' => 'cyan-800', 'hex' => '#155e75'], ['token' => 'cyan-900', 'hex' => '#164e63'],
        // blue
        ['token' => 'blue-50', 'hex' => '#eff6ff'], ['token' => 'blue-100', 'hex' => '#dbeafe'], ['token' => 'blue-200', 'hex' => '#bfdbfe'], ['token' => 'blue-300', 'hex' => '#93c5fd'], ['token' => 'blue-400', 'hex' => '#60a5fa'], ['token' => 'blue-500', 'hex' => '#3b82f6'], ['token' => 'blue-600', 'hex' => '#2563eb'], ['token' => 'blue-700', 'hex' => '#1d4ed8'], ['token' => 'blue-800', 'hex' => '#1e40af'], ['token' => 'blue-900', 'hex' => '#1e3a8a'],
        // indigo
        ['token' => 'indigo-50', 'hex' => '#eef2ff'], ['token' => 'indigo-100', 'hex' => '#e0e7ff'], ['token' => 'indigo-200', 'hex' => '#c7d2fe'], ['token' => 'indigo-300', 'hex' => '#a5b4fc'], ['token' => 'indigo-400', 'hex' => '#818cf8'], ['token' => 'indigo-500', 'hex' => '#6366f1'], ['token' => 'indigo-600', 'hex' => '#4f46e5'], ['token' => 'indigo-700', 'hex' => '#4338ca'], ['token' => 'indigo-800', 'hex' => '#3730a3'], ['token' => 'indigo-900', 'hex' => '#312e81'],
        // violet
        ['token' => 'violet-50', 'hex' => '#f5f3ff'], ['token' => 'violet-100', 'hex' => '#ede9fe'], ['token' => 'violet-200', 'hex' => '#ddd6fe'], ['token' => 'violet-300', 'hex' => '#c4b5fd'], ['token' => 'violet-400', 'hex' => '#a78bfa'], ['token' => 'violet-500', 'hex' => '#8b5cf6'], ['token' => 'violet-600', 'hex' => '#7c3aed'], ['token' => 'violet-700', 'hex' => '#6d28d9'], ['token' => 'violet-800', 'hex' => '#5b21b6'], ['token' => 'violet-900', 'hex' => '#4c1d95'],
        // pink
        ['token' => 'pink-50', 'hex' => '#fdf2f8'], ['token' => 'pink-100', 'hex' => '#fce7f3'], ['token' => 'pink-200', 'hex' => '#fbcfe8'], ['token' => 'pink-300', 'hex' => '#f9a8d4'], ['token' => 'pink-400', 'hex' => '#f472b6'], ['token' => 'pink-500', 'hex' => '#ec4899'], ['token' => 'pink-600', 'hex' => '#db2777'], ['token' => 'pink-700', 'hex' => '#be185d'], ['token' => 'pink-800', 'hex' => '#9d174d'], ['token' => 'pink-900', 'hex' => '#831843'],
    ];

    $palette = count($colors) ? $colors : $tailwind;

    $sizeClasses = PackResolver::inputSize($size ?? 'md');
    $roundedClass = PackResolver::rounded($rounded ?? 'lg');
    $inputColors = PackResolver::inputColor('base');

    // Placeholder dynamique
    $defaultPlaceholder = 'tailwind (ex: red-500), hex (ex: #ef4444) ou rgb(239, 68, 68)';
    $inputPlaceholder = $placeholder ?? $defaultPlaceholder;

    // Popup classes (reprend le style neura::popup)
    $popupSizes = [
        'xs' => ['padding' => 'p-1',   'text' => 'text-xs',  'minWidth' => 'min-w-32', 'maxHeight' => 'max-h-64'],
        'sm' => ['padding' => 'p-1.5', 'text' => 'text-sm',  'minWidth' => 'min-w-48', 'maxHeight' => 'max-h-72'],
        'md' => ['padding' => 'p-1.5', 'text' => 'text-sm',  'minWidth' => 'min-w-56', 'maxHeight' => 'max-h-80'],
        'lg' => ['padding' => 'p-2',   'text' => 'text-base','minWidth' => 'min-w-64', 'maxHeight' => 'max-h-96'],
    ];
    $popupVariants = [
        'menu' => ['radius' => 'rounded-md', 'shadow' => 'shadow-sm', 'border' => 'border border-neutral-200/80 dark:border-neutral-800', 'bg' => 'bg-white dark:bg-neutral-950'],
        'compact' => ['radius' => 'rounded-lg', 'shadow' => 'shadow-md', 'border' => 'border border-neutral-200/80 dark:border-neutral-800', 'bg' => 'bg-white dark:bg-neutral-950'],
        'default' => ['radius' => 'rounded-lg', 'shadow' => 'shadow-lg shadow-black/5', 'border' => 'border border-neutral-200/80 dark:border-neutral-800', 'bg' => 'bg-white dark:bg-neutral-950'],
    ];
    $pv = $popupVariants[$popupVariant] ?? $popupVariants['default'];
    $ps = $popupSizes[$popupSize] ?? $popupSizes['md'];

    $popupContainerClass = Arr::toCssClasses([
        'absolute z-50 mt-2',
        $popupAlign === 'right' ? 'right-0' : 'left-0',
        $ps['padding'],
        $ps['minWidth'],
        $ps['maxHeight'],
        'overflow-y-auto',
        $pv['bg'], $pv['radius'], $pv['border'], $pv['shadow'],
        $ps['text'],
        'text-neutral-950 dark:text-neutral-50',
        'scrollbar-thin scrollbar-thumb-neutral-300 dark:scrollbar-thumb-neutral-700',
        'scrollbar-track-transparent',
    ]);
@endphp

<div class="w-full {{ $class }}"
    x-data="neuraColorPicker({ palette: @js($palette), initialValue: @js($value), disabled: @js($disabled) })"
    @click.away="open = false">

    <div class="relative">
        @php
            // Support pour wire:model et x-model
            $wireModel = $attributes->whereStartsWith('wire:model')->first();
            $xModel = $attributes->whereStartsWith('x-model')->first();
            $hasModel = $name || $wireModel || $xModel;
        @endphp
        @if ($hasModel)
            <input 
                type="hidden" 
                @if($name) name="{{ $name }}" @endif
                @if($wireModel) wire:model="{{ $wireModel }}" @endif
                @if($xModel) x-model="{{ $xModel }}" @endif
                x-ref="hidden" 
            />
        @endif

        @if ($label)
            <label class="mb-1 block text-sm font-medium text-neutral-900 dark:text-neutral-100">
                {{ $label }}
            </label>
        @endif

        <div @class([
            'isolate',
            'relative flex items-stretch w-full shadow-xs disabled:shadow-none transition-colors duration-200',
            $roundedClass,
        ])>
            <div
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
                    '[&:has([data-slot=input-actions])>[data-slot=control]]:pr-[4.5rem]',
                ])
                @style([
                    'grid-template-columns: 1fr 4.5rem' => blank($leftIcon),
                    'grid-template-columns: 2.3rem 1fr 4.5rem' => filled($leftIcon),
                ])
            >
                @if ($leftIcon)
                    <neura::icon
                        name="{{ $leftIcon }}"
                        class="text-neutral-500! dark:text-neutral-400! size-[1.15rem]!"
                        data-slot="left-icon"
                    />
                @endif

                <input
                    x-ref="displayInput"
                    type="text"
                    placeholder="{{ $inputPlaceholder }}"
                    autocomplete="off"
                    data-slot="control"
                    @disabled($disabled)
                    x-model="display"
                    x-on:input="applyInput($event.target.value)"
                    x-on:focus="if (!isDisabled) open = true"
                    x-on:click="if (!isDisabled) open = true"
                    x-on:keydown.escape="open = false"
                    @class([
                        'z-10',
                        'inline-block border w-full text-neutral-900 disabled:text-neutral-500 placeholder-neutral-400 disabled:placeholder-neutral-400/70 dark:text-neutral-100 dark:disabled:text-neutral-500 dark:placeholder-neutral-500 dark:disabled:placeholder-neutral-600',
                        'bg-white dark:bg-neutral-950 disabled:bg-neutral-50 dark:disabled:bg-neutral-900',
                        'disabled:cursor-not-allowed transition-colors duration-150',
                        'shadow-sm disabled:shadow-none',
                        $roundedClass,
                        'focus:ring-offset-0 focus:outline-none',
                        $inputColors['border'],
                        $inputColors['focus'],
                        $sizeClasses,
                    ])
                />

                <div class="flex items-center justify-end h-full gap-1.5 pr-1.5" data-slot="input-actions">
                    <div 
                        class="w-5 h-5 rounded-md border border-neutral-300 dark:border-neutral-700 shadow-sm shrink-0"
                        :style="{ backgroundColor: hex || 'transparent' }"
                        :title="hex || ''"
                        :class="{ 'opacity-50': !hex }"
                    ></div>
                    <button
                        type="button"
                        @click.stop="if (!isDisabled) open = !open"
                        class="text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200 transition-colors"
                        :disabled="$disabled"
                    >
                        <neura::icon 
                            name="swatch" 
                            class="text-neutral-500! dark:text-neutral-400! size-[1.15rem]! shrink-0" 
                            data-slot="input-option" 
                        />
                    </button>
                </div>
            </div>
        </div>

        @if ($hint)
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                {{ $hint }}
            </p>
        @endif

        <div
            x-show="open && !isDisabled"
            x-cloak
            class="{{ $popupContainerClass }} w-full"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
            @click.stop=""
            style="display:none;"
        >
            <div class="mb-2 pb-2 border-b border-neutral-200 dark:border-neutral-800">
                <div @class([
                    'isolate',
                    'relative flex items-stretch w-full shadow-xs transition-colors duration-200',
                    $roundedClass,
                ])>
                    <input
                        type="text"
                        x-model="query"
                        placeholder="Rechercher (token ou hex)…"
                        @keydown.escape="open = false"
                        @click.stop=""
                        autocomplete="off"
                        @class([
                            'z-10',
                            'inline-block border w-full text-neutral-900 placeholder-neutral-400 dark:text-neutral-100 dark:placeholder-neutral-500',
                            'bg-white dark:bg-neutral-950',
                            'transition-colors duration-150',
                            'shadow-sm',
                            $roundedClass,
                            'focus:ring-offset-0 focus:outline-none',
                            $inputColors['border'],
                            $inputColors['focus'],
                            'text-sm',
                            'px-2.5 py-2',
                        ])
                    />
                </div>
            </div>

            <div class="grid grid-cols-6 gap-2">
                <template x-for="(color, index) in filteredPalette()" :key="color.token || color.hex || index">
                    <button
                        type="button"
                        class="group flex flex-col items-center gap-1 rounded-md p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600"
                        x-on:click.stop="choose(color)"
                        :title="color.token + ' ' + color.hex"
                    >
                        <div class="size-7 rounded-md border border-neutral-300 dark:border-neutral-700 shadow-sm"
                            :style="{ backgroundColor: color.hex }"></div>
                        <span class="text-[10px] text-neutral-600 dark:text-neutral-300 font-mono truncate w-full text-center"
                            x-text="color.token"></span>
                    </button>
                </template>
            </div>

            <div class="mt-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
                <div class="flex items-center justify-between gap-2 px-1">
                    <div class="text-xs text-neutral-600 dark:text-neutral-300">
                        <span class="font-mono" x-text="hex ? hex.toUpperCase() : ''"></span>
                        <span class="mx-2 text-neutral-300 dark:text-neutral-700">·</span>
                        <span class="font-mono" x-text="rgb ? rgbToString(rgb) : ''"></span>
                    </div>
                    <button type="button"
                        class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
                        x-on:click="display=''; applyInput(''); open=false;">
                        Effacer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
