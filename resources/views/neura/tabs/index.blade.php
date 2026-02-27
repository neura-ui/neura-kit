@props([
    'defaultTab' => null,
    'variant' => 'line',
])

<div
    x-data="{
        activeTab: @entangle('activeTab').live ?? @js($defaultTab),
        tabs: [],
        init() {
            this.$nextTick(() => {
                this.tabs = Array.from(this.$el.querySelectorAll('[data-tab-trigger]')).map(el => el.dataset.tabTrigger);
                if (!this.activeTab && this.tabs.length > 0) {
                    this.activeTab = this.tabs[0];
                }
            });
        }
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}
    data-slot="tabs"
>
    {{ $slot }}
</div>
