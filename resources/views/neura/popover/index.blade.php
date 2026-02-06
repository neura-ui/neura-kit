@props([
    'onHover' => false,
    'position' => 'bottom',
    'offset' => 3,
    'variant' => 'default',
    'size' => 'md',
])

<div
    x-data="{
        open: false,
        hoverTimeout: null,

        toggle() {
            this.open = !this.open;
        },
        show() {
            clearTimeout(this.hoverTimeout);
            this.open = true;
        },
        hide() {
            this.open = false;
        },
        hoverShow() {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = setTimeout(() => { this.open = true }, 75);
        },
        hoverHide() {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = setTimeout(() => { this.open = false }, 150);
        },
    }"
    x-on:click.away="hide()"
    x-on:keydown.escape="hide()"
    {{ $attributes->merge(['class' => 'relative inline-flex']) }}
>
    {{ $slot }}
</div>
