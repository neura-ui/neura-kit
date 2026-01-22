@aware([
    'variant' => 'default',
])
@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'value' => null,
    'mode' => 'value',
    'disabled' => false,
    'indeterminate' => false,
    'size' => 'md',
    'variant' => 'default',
])
<div data-slot="checkbox-wrapper" x-data="{
    value: @js($value),
    _checked: false,
    _indeterminate: @js($indeterminate),
    get groupState() {
        let el = this.$el.closest('[data-slot=checkbox-group]');
        if (!el) return undefined;
        return Alpine.$data(el)?.state;
    },
    init() {
        this.$nextTick(() => {
            if (this.hasGroupState()) {
                this._checked = this.groupState.includes(this.value);
            } else {
                const modelValue = this.$root._x_model?.get();
                if (this.value !== null) {
                    this._checked = modelValue === this.value;
                } else {
                    this._checked = Boolean(modelValue);
                }
            }
        });

        this.$watch('groupState', (newState) => {
            if (Array.isArray(newState)) {
                this._checked = newState.includes(this.value);
            }
        });

        this.$watch('_checked', (checked) => {
            if (this.hasGroupState()) {
                let state = this.groupState;

                if (checked) {
                    // Add if not present
                    if (!state.includes(this.value)) {
                        state.push(this.value);
                    }
                } else {
                    const newState = state.filter(v => v !== this.value);

                    let parent = this.$el.closest('[data-slot=checkbox-group]');
                    if (parent && parent._x_dataStack) {
                        parent._x_dataStack[0].state = newState;
                    }
                }
                return;
            }

            let newValue = this.value !== null ?
                (checked ? this.value : null) :
                checked;
            this.$root?._x_model?.set(newValue);
            const wireModel = this.findWireModelAttribute();
            if (this.$wire && wireModel) {
                const path = this.$root.getAttribute(wireModel);
                this.$wire.set(path, newValue, wireModel.includes('.live'));
            }
        });
    },
    toggle() {
        this._indeterminate = false;
        this._checked = !this._checked;
    },
    hasGroupState() {
        return Array.isArray(this.groupState);
    },
    findWireModelAttribute() {
        return this.$root.getAttributeNames().find(name => name.startsWith('wire:model'));
    }
}" @click="toggle" {{ $attributes }}>
    <input type="checkbox" tabindex="-1" hidden @if ($name) name="{{ $name }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
        {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }} />
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
