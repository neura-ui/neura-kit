
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
        // ====================================================================
        // GROUP STATE MANAGEMENT
        // ====================================================================

        state: undefined,

        // ====================================================================
        // INITIALIZATION
        // ====================================================================

        init() {
            this.$nextTick(() => {
                // Check if there's a model binding on the group wrapper
                const modelBinding = this.$root._x_model;

                if (modelBinding) {
                    // Model binding exists: initialize with bound data or empty array
                    this.state = modelBinding.get() ?? [];
                }
                // No model binding: state remains undefined
                // Individual checkboxes will manage their own state
            });

            // ====================================================================
            // STATE SYNCHRONIZATION WATCHER
            // ====================================================================

            this.$watch('state', (newValues) => {
                // Skip sync if no model binding (prevents unnecessary operations)
                if (newValues === undefined) return;

                this.syncWithAlpineModel(newValues);
                this.syncWithLivewireModel(newValues);
            });
        },

        // ====================================================================
        // MODEL BINDING SYNCHRONIZATION
        // ====================================================================

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