
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
    $classes = [
        'isolate',
        '[grid-area:sidebar]',
        'z-40 dark:bg-neutral-950 bg-white lg:block',
        'border-r dark:border-white/5 border-black/5',
        'transition-[width] duration-500',
        'overflow-x-visible',
        '!overflow-y-auto' => $scrollable,
    ];
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="sidebar"
    style="z-index:99;"
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
                        const activeLink = this.$el.querySelector('[data-active-link]');
                        if (activeLink) {
                            const sidebar = this.$el;
                            const linkTop = activeLink.offsetTop;
                            const linkHeight = activeLink.offsetHeight;
                            const sidebarHeight = sidebar.clientHeight;
                            const sidebarScrollTop = sidebar.scrollTop;
                            
                            // Check if the active link is already visible in the viewport
                            const linkBottom = linkTop + linkHeight;
                            const viewportTop = sidebarScrollTop;
                            const viewportBottom = sidebarScrollTop + sidebarHeight;
                            
                            // Only scroll if the link is not fully visible
                            const isFullyVisible = linkTop >= viewportTop && linkBottom <= viewportBottom;
                            
                            if (!isFullyVisible) {
                                // Scroll directly to the item position, ensuring it's visible
                                // Position the item near the top with some padding
                                const scrollTop = linkTop - 20;
                                
                                sidebar.scrollTop = Math.max(0, scrollTop);
                            }
                        }
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
    @if(filled($brand))
        <div
            @class([

                "justify-between items-center group w-full
                [:not(:has([data-collapsed]_&))_&]:flex
                min-h-[var(--header-height)]
                [:not(:has([data-collapsed]_&))_&]:px-4
                mx-auto flex-shrink-0",

                'sticky z-10 top-0 dark:bg-neutral-950 bg-white' => $stickyHeader,
            ])
        >
            <div
                @class([
                    "[:not(:has([data-collapsed]_&))_&]:mx-auto grow
                    [:has([data-collapsed]_&)_&]:[&_[data-slot=brand-name]]:hidden",
                    "[:has([data-collapsed]_&)_&]:group-hover:hidden" => $collapsable
                ])
                data-slot="sidebar-brand"
            >
                {{ $brand }}
            </div>

            @if ($collapsable)
                <neura::sidebar.toggle
                    x-bind:data-collapsable="collapsable"
                    class="
                        [&:not([data-collapsable=true])]:hidden
                        in-[:has([data-collapsed]_&)_&]:group-hover:inline-flex
                        in-[:has([data-collapsed]_&)]:hidden
                        in-[:has([data-collapsed]_&)]:cursor-ew-resize
                    "
                />
            @endif
        </div>
    @endif

    <div
        @class([
            'flex flex-col min-h-[calc(100vh-var(--header-height))]',
            'z-0' => $stickyHeader,
        ])
    >
        {{ $slot }}
    </div>
</div>