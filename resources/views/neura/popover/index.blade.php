@props([
    'onHover' => false,
    'position' => null,
    'offset' => null,
    'variant' => 'default',
    'size' => 'md',
])

<div
    @if ($variant === 'tooltip') x-id="['nk-tooltip']" @endif
    x-data="{
        open: false,
        instant: false,
        isTooltip: @js($variant === 'tooltip'),
        hoverTimeout: null,

        init() {
            this.$watch('open', (value) => {
                if (!this.isTooltip || !window.NeuraKitTooltipWarm) return;
                if (value) window.NeuraKitTooltipWarm.opened();
                else window.NeuraKitTooltipWarm.closed();
            });
        },
        toggle() {
            this.open = !this.open;
        },
        show() {
            clearTimeout(this.hoverTimeout);
            this.open = true;
        },
        hide() {
            clearTimeout(this.hoverTimeout);
            this.open = false;
        },
        hoverShow() {
            clearTimeout(this.hoverTimeout);
            if (!this.isTooltip) {
                this.hoverTimeout = setTimeout(() => { this.open = true }, 75);
                return;
            }
            const warm = !!window.NeuraKitTooltipWarm?.warm;
            this.hoverTimeout = setTimeout(() => {
                this.instant = !!window.NeuraKitTooltipWarm?.warm;
                this.open = true;
            }, warm ? 0 : 250);
        },
        hoverHide() {
            clearTimeout(this.hoverTimeout);
            if (this.isTooltip) {
                this.open = false;
                return;
            }
            this.hoverTimeout = setTimeout(() => { this.open = false }, 150);
        },
        focusShow() {
            if (!this.isTooltip) return;
            clearTimeout(this.hoverTimeout);
            this.instant = !!window.NeuraKitTooltipWarm?.warm;
            this.open = true;
        },
    }"
    x-on:click.away="hide()"
    x-on:keydown.escape="hide()"
    {{ $attributes->merge(['class' => 'relative inline-flex']) }}
>
    {{ $slot }}
</div>
