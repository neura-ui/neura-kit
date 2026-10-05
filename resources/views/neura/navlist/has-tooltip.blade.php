@props(['tooltip' => null, 'condition' => false])

@if ($condition)

<div
    x-data="{
        tooltipEl: null,
        showTimer: null,

        showTooltip() {
            if (this.tooltipEl || this.showTimer) return;
            if (!this.$data.collapsedSidebar) return;

            const warm = !!window.NeuraKitTooltipWarm?.warm;
            this.showTimer = setTimeout(() => {
                this.showTimer = null;
                this.mountTooltip();
            }, warm ? 0 : 250);
        },

        mountTooltip() {
            if (this.tooltipEl || !this.$data.collapsedSidebar) return;

            const rect = this.$el.getBoundingClientRect();
            const instant = !!window.NeuraKitTooltipWarm?.warm;
            const tip = document.createElement('div');
            tip.className = 'nk-tooltip nk-menu-motion nk-menu-from fixed';
            tip.dataset.side = 'right';
            tip.dataset.align = 'center';
            if (instant) tip.dataset.instant = '';
            tip.setAttribute('role', 'tooltip');
            tip.textContent = @js($tooltip);
            document.body.appendChild(tip);

            const tipRect = tip.getBoundingClientRect();
            let left = rect.right + 8;
            if (left + tipRect.width > window.innerWidth) {
                tip.dataset.side = 'left';
                left = rect.left - tipRect.width - 8;
            }

            tip.style.left = `${left}px`;
            tip.style.top = `${rect.top + rect.height / 2}px`;
            tip.style.transform = 'translateY(-50%)';
            this.tooltipEl = tip;
            window.NeuraKitTooltipWarm?.opened();

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    if (!this.tooltipEl) return;
                    this.tooltipEl.classList.remove('nk-menu-from');
                    this.tooltipEl.classList.add('nk-menu-to');
                });
            });
        },

        hideTooltip() {
            clearTimeout(this.showTimer);
            this.showTimer = null;
            if (!this.tooltipEl) return;

            const tip = this.tooltipEl;
            this.tooltipEl = null;
            tip.classList.remove('nk-menu-to', 'nk-menu-from');
            tip.classList.add('nk-menu-leaving', 'nk-menu-out');
            window.NeuraKitTooltipWarm?.closed();
            setTimeout(() => tip.remove(), 100);
        }
    }"
    x-on:mouseenter="showTooltip()"
    x-on:mouseleave="hideTooltip()"
    x-on:destroy="hideTooltip()"
    class="inline-flex"
>
    {{ $slot }}
</div>
@else
    {{ $slot }}
@endif