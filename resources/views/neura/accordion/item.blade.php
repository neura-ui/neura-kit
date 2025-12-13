@aware([
    'reverse' => false
])

@props([
    'disabled' => false,
    'trigger' => null,
    'expanded' => false
])

<div role="region" x-data="{
    id: $id('accordion'),
    init() {
        if(@js($expanded)) {
            this.active = this.id;
        }
    },
    toggle() {
        this.isVisible = !this.isVisible;
    },
    get isVisible() {
        return this.active === this.id && !@js($disabled)
    },
    set isVisible(value) {
        this.active = value ? this.id : null
    },
}"
    {{ $attributes->class(
        Arr::toCssClasses([
            'dark:text-white text-neutral-800 not-last:border-b border-black/10 dark:border-white/10 text-start',
            'opacity-50' => $disabled,
        ]),
    ) }}>

    @if ($trigger)
        <neura::accordion.item.trigger>{{ $trigger }}</neura::accordion.item.trigger>
        <neura::accordion.item.content>{{ $slot->__toString() }}</neura::accordion.item.content>
    @else
        {{ $slot }}
    @endif

</div>
