@props([
    'label' => '',
    'required' => false,
    'error' => '',
    'direction' => 'vertical',
    'disabled' => false,
    'variant' => 'default',
    'labelClass' => '',
    'indicator' => true,
    'wrapperClass' => '',
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
])

@php
    $componentId = $id ?? 'radio-group-' . uniqid();

    $labelClasses = ['text-fg font-semibold mb-4 inline-block', $labelClass];

    $variantClass = [
        'space-y-2' => $direction === 'vertical' && !str_contains($wrapperClass, 'grid'),
        'flex gap-1 items-stretch' => $direction === 'horizontal',
        'bg-neutral-100 dark:bg-white/[0.04] rounded-box w-fit p-1' => $variant === 'segmented',
        $wrapperClass,
    ];
@endphp

<div data-slot="group-controller" x-data="{
    state: null,
    init() {
        this.$nextTick(() => {
            this.state = this.$root?._x_model?.get();
        });

        this.$watch('state', (value) => {
            // Sync with Alpine state
            this.$root?._x_model?.set(value);

            // Sync with Livewire state
            let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'))

            if (this.$wire && wireModel) {
                let prop = this.$root.getAttribute(wireModel)
                this.$wire.set(prop, value, wireModel?.includes('.live'));
            }
        });

    },
}" {{ $attributes->merge(['class' => 'w-full text-start']) }}>
    @if ($label)
        <label id="{{ $componentId }}-label" @class($labelClasses)>
            {{ $label }}
        </label>
    @endif

    <div role="radiogroup" @class(Arr::toCssClasses($variantClass))
        @if ($label) aria-labelledby="{{ $componentId }}-label" @endif>
        {{ $slot }}
    </div>

    @if ($error && filled($error))
        <p class="text-sm text-danger-500 mt-2 font-medium">
            {{ $error }}
        </p>
    @endif
</div>
