@props([
    'collapsable' => true,
])

@php
    $layoutClasses = '[--sidebar-width:16rem] data-[collapsed]:[--sidebar-width:4rem] [--header-height:4rem] grid items-start h-screen overflow-hidden min-h-screen text-fg grid-cols-1 grid-rows-[1fr] [grid-template-areas:\'main\'] [&_[data-slot=sidebar]]:fixed [&_[data-slot=sidebar]]:inset-y-0 [&_[data-slot=sidebar]]:left-0 [&_[data-slot=sidebar]]:z-50 [&_[data-slot=sidebar]]:w-[var(--sidebar-width)] [&_[data-slot=sidebar]]:transition-transform [&_[data-slot=sidebar]]:duration-300 [&_[data-slot=sidebar]]:ease-in-out [&_[data-slot=sidebar]]:-translate-x-full data-[sidebar-open]:[&_[data-slot=sidebar]]:translate-x-0 md:grid-cols-[var(--sidebar-width)_1fr] md:data-[collapsed]:grid-cols-[var(--sidebar-width)_1fr] md:[grid-template-areas:\'sidebar_main\'] md:[&_[data-slot=sidebar]]:relative md:[&_[data-slot=sidebar]]:translate-x-0 md:[&_[data-slot=sidebar]]:z-auto md:[&_[data-slot=sidebar]]:overflow-visible md:data-[collapsed]:[&_[data-slot=sidebar]]:w-[var(--sidebar-width)] lg:grid-cols-[var(--sidebar-width)_1fr] lg:grid-rows-1 lg:[grid-template-areas:\'sidebar_main\'] lg:[&_[data-slot=sidebar]]:w-auto lg:grid-cols-[var(--sidebar-width)_1fr] data-[collapsed]:lg:grid-cols-[var(--sidebar-width)_1fr] data-[collapsed]:lg:[grid-template-areas:\'sidebar_main\'] data-[collapsed]:[&_[data-slot=sidebar]]:lg:w-[var(--sidebar-width)] data-[collapsed]:[&_[data-slot=sidebar]]:lg:overflow-visible';
@endphp

<div
    {{ $attributes->merge(['class' => $layoutClasses]) }}
    x-data="{
        collapsedSidebar: $persist(false),
        sidebarOpen: false,
        isMobile: false,
        isTablet: false,

        toggle() {
            if (this.isMobile) {
                this.sidebarOpen = !this.sidebarOpen;
            } else {
                this.collapsedSidebar = !this.collapsedSidebar;
            }
        },

        closeSidebar() {
            if (this.isMobile) {
                this.sidebarOpen = false;
            }
        },

        updateBreakpoints() {
            this.isMobile = window.matchMedia('(max-width: 767px)').matches;
            this.isTablet = window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches;

            if (!this.isMobile) {
                this.sidebarOpen = false;
            }
        },

        init() {
            if (this.$root.dataset.inTablet === 'true') {
                this.collapsedSidebar = true;
            }

            this.$watch('isMobile', (val) => {
                if (val) {
                    this.collapsedSidebar = false;
                }
            });

            this.updateBreakpoints();

            const mobileQuery = window.matchMedia('(max-width: 767px)');
            const tabletQuery = window.matchMedia('(min-width: 768px) and (max-width: 1023px)');

            mobileQuery.addEventListener('change', () => this.updateBreakpoints());
            tabletQuery.addEventListener('change', () => this.updateBreakpoints());
        }
    }"

    x-bind:data-in-mobile="isMobile"
    x-bind:data-in-tablet="isTablet"
    @if ($collapsable)
        x-bind:data-collapsed="collapsedSidebar"
    @endif
    x-bind:data-sidebar-open="sidebarOpen"
    data-slot="layout"
>
    {{ $slot }}

    <div
        x-show="isMobile && sidebarOpen"
        style="display: none;"
        x-transition:enter="transition-opacity duration-300"
        x-transition:leave="transition-opacity duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="closeSidebar()"
        class="fixed inset-0 bg-surface-overlay z-40 md:hidden"
    ></div>
</div>
