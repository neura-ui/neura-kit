@props([
    'onHover' => false,
])

<div
    x-data="{
        open: false,
        toggle() {
            this.open = !this.open;
        },
        show() {
            this.open = true;
        },
        hide() {
            this.open = false;
        }
    }"
    x-on:click.away="hide()"
    x-on:keydown.escape="hide()"
    class="relative inline-block [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]"
>
    {{ $slot }}
</div>
