
@aware([
    'variant' => 'default'
])

@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'value' => null,
    'checked' => false,
    'indeterminate' => false,
    'disabled' => false,
    'invalid' => false,
    'size' => 'md',
    'variant' => 'default',
    'indicator' => true,
])

<div
    data-slot="checkbox-wrapper"
    x-data="{
        _checked: @js($checked),
        value: @js($value),
        _indeterminate: @js($indeterminate),

        toggle() {
            if (this._indeterminate) {
                this._indeterminate = false;
            }
            this._checked = !this._checked;
            this.syncHiddenInput();
            this.dispatchChangeEvent();
        },

        init() {
            this.$nextTick(() => {
                if (this.hasGroupState()) {
                    this._checked = this.state.includes(this.value);
                } else {
                    const modelValue = this.$root._x_model?.get();
                    if (modelValue !== undefined && modelValue !== null) {
                        this._checked = modelValue;
                    }
                }
            });

            this.$watch('_checked', (isChecked) => {
                if (this.hasGroupState()) {
                    this.syncWithGroupState(isChecked);
                } else {
                    this.syncWithModelBindings(isChecked);
                }
            });
        },

        hasGroupState() {
            return ![undefined, null].includes(this.state);
        },

        syncWithGroupState(isChecked) {
            if (isChecked && !this.state.includes(this.value)) {
                this.state.push(this.value);
            } else if (!isChecked && this.state.includes(this.value)) {
                this.state = this.state.filter((item) => item !== this.value);
            }
        },

        syncWithModelBindings(isChecked) {
            this.$root?._x_model?.set(isChecked);

            const wireModelAttribute = this.findWireModelAttribute();
            if (this.$wire && wireModelAttribute) {
                const propertyPath = this.$root.getAttribute(wireModelAttribute);
                const isLiveUpdate = wireModelAttribute.includes('.live');
                this.$wire.set(propertyPath, isChecked, isLiveUpdate);
            }
        },

        findWireModelAttribute() {
            return this.$root
                .getAttributeNames()
                .find(attributeName => attributeName.startsWith('wire:model'));
        },

        syncHiddenInput() {
            this.$refs.hiddenInput && (this.$refs.hiddenInput.checked = this._checked);
        },

        dispatchChangeEvent() {
            this.$refs.hiddenInput?.dispatchEvent(
                new Event('change', { bubbles: true })
            );
        },

        setIndeterminate(isIndeterminate) {
            this._indeterminate = isIndeterminate;
            if (isIndeterminate) {
                this._checked = false;
            }
        }
    }"
    {{ $attributes }}
>

    <input
        x-ref="hiddenInput"
        type="checkbox"
        @if($name) name="{{ $name }}" @endif
        @if($value !== null) value="{{ $value }}" @endif
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        hidden
        tabindex="-1"
        {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    />

    @switch($variant)
        @case('pills')
            <neura::checkbox.variant.pill>
                {{ $slot }}
            </neura::checkbox.variant.pill>
            @break

        @case('cards')
            <neura::checkbox.variant.card>
                {{ $slot }}
            </neura::checkbox.variant.card>
            @break

        @default
            <neura::checkbox.variant.default>
                {{ $slot }}
            </neura::checkbox.variant.default>
    @endswitch
</div>