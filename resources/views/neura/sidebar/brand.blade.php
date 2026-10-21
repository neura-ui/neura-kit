@props([
    'collapsable' => true,
])

{{--
    The sidebar's brand row. It has no background of its own: it shows the
    sidebar's. In the collapsed rail the name is hidden and the mark is
    centred over the icons; hovering the row swaps the mark for the expand
    button, in the same place.
--}}
<div
    {{ $attributes->class([
        'group/brand flex shrink-0 items-center justify-between gap-2 w-full min-h-(--header-height) px-4',
        'in-data-collapsed:justify-center in-data-collapsed:px-0',
    ]) }}
    data-slot="sidebar-header"
>
    <div
        @class([
            'grow min-w-0 max-w-full',
            'in-data-collapsed:flex in-data-collapsed:grow-0 in-data-collapsed:justify-center',
            'in-data-collapsed:[&_[data-slot=brand-name]]:hidden',
            'in-data-collapsed:group-hover/brand:hidden' => $collapsable,
        ])
        data-slot="sidebar-brand"
    >
        {{ $slot }}
    </div>

    @if ($collapsable)
        <neura::sidebar.toggle
            class="opacity-0 group-hover/brand:opacity-100 focus-visible:opacity-100 transition-opacity duration-fast ease-standard in-data-collapsed:hidden in-data-collapsed:group-hover/brand:inline-flex"
        />
    @endif
</div>
