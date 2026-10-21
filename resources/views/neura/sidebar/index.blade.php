
@aware([
    'collapsable' => true,
])

@props([
    'stickyHeader' => true,
    'scrollable' => true,
    'collapsable' => true,
    'brand' => null,
    'scrollToActive' => false,
])

@php
    /*
     * A column that fills its grid row: the brand keeps its height and the
     * content takes the rest. Only the content scrolls, so the brand stays in
     * place without being sticky and never adds its height on top of the menu.
     * With sticky-header off, the brand scrolls away with the content instead.
     */
    $sidebarClasses = 'isolate [grid-area:sidebar] z-40 flex flex-col self-stretch min-h-0 bg-surface backdrop-blur-xl border-r border-separator transition-[width] duration-considered ease-standard overflow-x-visible';

    $hasBrand = filled($brand);
@endphp

<div
    {{ $attributes->merge(['class' => $sidebarClasses]) }}
    data-slot="sidebar"
    @if ($collapsable || $scrollToActive)
        x-data="{
            collapsable: @js($collapsable),
            scrollToActive: @js($scrollToActive),
            get isMobile() {
                const layoutEl = this.$el.closest('[data-slot=layout]');
                return layoutEl ? Alpine.$data(layoutEl)?.isMobile ?? false : false;
            },
            closeSidebar() {
                const layoutEl = this.$el.closest('[data-slot=layout]');
                if (layoutEl && Alpine.$data(layoutEl)?.closeSidebar) {
                    Alpine.$data(layoutEl).closeSidebar();
                }
            },
            init() {
                @if ($collapsable)
                    if(window.matchMedia('(pointer: coarse)').matches) {
                        $el.addEventListener('click', (event) => {
                            // Don't toggle if clicking brand area
                            if(event.target.closest('[data-slot=sidebar-brand]')) {
                                return;
                            }

                            // Don't toggle if clicking the toggle button
                            if(event.target.closest('[data-slot=sidebar-toggle]')) {
                                return;
                            }

                            // Don't toggle on mobile (uses overlay mode)
                            if($data.isMobile) {
                                return;
                            }

                            // Toggle collapse state
                            toggle();
                        });
                    }
                @endif

                @if ($scrollToActive)
                    this.$nextTick(() => {
                        const link = this.$el.querySelector('[data-active-link]');
                        if (!link) return;

                        // The nearest box that actually scrolls: the content
                        // column, or the app's own scroller inside it.
                        let scroller = link.parentElement;
                        while (scroller && this.$el.contains(scroller)) {
                            const overflow = getComputedStyle(scroller).overflowY;
                            if ((overflow === 'auto' || overflow === 'scroll') && scroller.scrollHeight > scroller.clientHeight) break;
                            scroller = scroller.parentElement;
                        }
                        if (!scroller || !this.$el.contains(scroller)) return;

                        const box = scroller.getBoundingClientRect();
                        const rect = link.getBoundingClientRect();

                        // Leave it alone when it is already fully visible.
                        if (rect.top >= box.top && rect.bottom <= box.bottom) return;

                        // Bring it near the top, with a little room above.
                        scroller.scrollTop = Math.max(0, scroller.scrollTop + rect.top - box.top - 20);
                    });
                @endif

                $el.addEventListener('click', (event) => {
                    if (event.target.closest('a') && this.isMobile && !event.target.closest('[x-ref=popoverTrigger]')) {
                        this.closeSidebar();
                    }
                });
            }
        }"
    @else
    x-init="
        if(window.matchMedia('(pointer: coarse)').matches) {
            $el.addEventListener('click', (event) => {
                // Don't toggle if clicking brand area
                if(event.target.closest('[data-slot=sidebar-brand]')) {
                    return;
                }

                // Don't toggle if clicking the toggle button
                if(event.target.closest('[data-slot=sidebar-toggle]')) {
                    return;
                }

                // Don't toggle on mobile (uses overlay mode)
                if($data.isMobile) {
                    return;
                }

                // Toggle collapse state
                toggle();
            });
        }

        $el.addEventListener('click', (event) => {
            if (event.target.closest('a') && $data.isMobile && $data.closeSidebar && !event.target.closest('[x-ref=popoverTrigger]')) {
                $data.closeSidebar();
            }
        });
    "
    @endif
>
    @if ($hasBrand && $stickyHeader)
        <neura::sidebar.brand :collapsable="$collapsable">{{ $brand }}</neura::sidebar.brand>
    @endif

    <div
        @class([
            'flex flex-col flex-1 min-h-0',
            'overflow-y-auto overflow-x-hidden' => $scrollable,
        ])
        data-slot="sidebar-content"
    >
        @if ($hasBrand && ! $stickyHeader)
            <neura::sidebar.brand :collapsable="$collapsable">{{ $brand }}</neura::sidebar.brand>
        @endif

        {{ $slot }}
    </div>
</div>
