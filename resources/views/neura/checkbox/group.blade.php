@props([
    'variant' => 'default',
    'model' => $attributes->whereStartsWith(['wire:model', 'x-model'])
])
@php
    $classes = match($variant) {
        'pills' => 'flex gap-2 flex-wrap',
        'cards' => 'flex flex-col gap-2',
        default => [
            '[&>[data-slot=checkbox-wrapper]:not(:first-child)]:mt-3',
            '[&>[data-slot=checkbox-wrapper]:has([data-slot=checkbox-description])+[data-slot=checkbox-wrapper]]:mt-4'
        ]
    };
@endphp
<div
    x-data="{
        state: undefined,
        init() {
            this.$nextTick(() => {
                const modelBinding = this.$root._x_model;
                if (modelBinding) {
                    this.state = modelBinding.get() ?? [];
                }
            });

            this.$watch('state', (newValues) => {
                if (newValues === undefined) return;
                this.syncWithAlpineModel(newValues);
                this.syncWithLivewireModel(newValues);
            });

            // Listen for Livewire updates to the bound property
            const wireModelAttribute = this.findWireModelAttribute();
            if (this.$wire && wireModelAttribute) {
                const propertyPath = this.$root.getAttribute(wireModelAttribute);

                // Watch for changes from Livewire
                this.$wire.$watch(propertyPath, (value) => {
                    // Update local state when Livewire property changes
                    if (JSON.stringify(this.state) !== JSON.stringify(value)) {
                        this.state = Array.isArray(value) ? [...value] : [];
                    }
                });
            }
        },
        syncWithAlpineModel(values) {
            this.$root?._x_model?.set(values);
        },
        syncWithLivewireModel(values) {
            const wireModelAttribute = this.findWireModelAttribute();
            if (this.$wire && wireModelAttribute) {
                const propertyPath = this.$root.getAttribute(wireModelAttribute);
                const isLiveUpdate = wireModelAttribute.includes('.live');
                this.$wire.set(propertyPath, values, isLiveUpdate);
            }
        },
        findWireModelAttribute() {
            return this.$root.getAttributeNames()
                .find(attributeName => attributeName.startsWith('wire:model'));
        }
    }"
    {{ $attributes->class($classes) }}
    data-slot="checkbox-group"
>
    {{ $slot }}
</div>
