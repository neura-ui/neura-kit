<script>
    (function () {
        try {
            const layout = document.querySelector('[data-slot="layout"]');
            if (!layout) return;

        @if($collapsable)
            if (localStorage.getItem('_x_collapsedSidebar') === 'true') {
                    layout.setAttribute('data-collapsed', 'true');
            }
        @endif

            const mqMobile = window.matchMedia('(max-width: 767px)');
            const mqTablet = window.matchMedia('(min-width: 768px) and (max-width: 1023px)');

            if (mqMobile.matches) {
                layout.setAttribute('data-in-mobile', 'true');
            }
            if (mqTablet.matches) {
                layout.setAttribute('data-in-tablet', 'true');
            }
    } catch (e) {
            console.warn('Init layout failed:', e);
    }
    })();
</script>
