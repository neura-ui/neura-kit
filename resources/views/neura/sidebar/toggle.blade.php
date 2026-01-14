@props([
    'tooltip'=>null
])

<neura::navlist.has-tooltip
    tooltip="toggle sidebar"
    :condition="true"
>
    <button
        {{ $attributes->class(
            "relative [:where(&)]:mx-auto inline-flex hover:dark:bg-white/5 hover:bg-neutral-800/5 p-1.5 rounded-box cursor-pointer"
        ) }}
        x-on:click="toggle()"
        data-slot="sidebar-toggle"
    >
        <neura::icon
            name="code-bracket-square"
        />
        <span class="absolute size-12 top-1/2 left-1/2 -translate-1/2 pointer-fine:hidden">
        </span>
    </button>
</neura::navlist.has-tooltip>
