@props([
    'icon' => null,
    'panel' => null,
    'label' => null,
    'iconVariant' => 'outline',
])

@php
    use Neura\Kit\Support\PackResolver;

    $selectColors = PackResolver::inputColor('select');
    $roundedClass = PackResolver::rounded(neura_config('select', 'rounded'));
    $shadowClass = PackResolver::shadow(neura_config('select', 'shadow'));

    $displayIcon = $icon ?? ($slot->isEmpty() ? 'plus' : null);
@endphp

<button type="button" data-slot="select-action"
    @if ($panel)
        x-on:click.stop="open && activePanel === @js($panel) ? close() : openPanel(@js($panel))"
        x-bind:aria-expanded="open && activePanel === @js($panel)"
        x-bind:class="open && activePanel === @js($panel) ? 'bg-hover text-fg' : ''"
    @endif
    x-bind:disabled="isDisabled"
    @if (filled($label)) aria-label="{{ $label }}" title="{{ $label }}" @endif
    {{ $attributes->merge(['class' => 'border bg-surface text-fg-muted hover:text-fg hover:bg-hover px-3 flex items-center justify-center gap-1.5 text-sm shrink-0 cursor-pointer transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed focus:ring-offset-0 focus:outline-none ' . $shadowClass . ' ' . $roundedClass . ' ' . $selectColors['border'] . ' ' . $selectColors['focus']]) }}>
    @if (filled($displayIcon))
        <neura::icon :name="$displayIcon" :variant="$iconVariant" class="size-[1.10rem]!" />
    @endif

    {{ $slot }}
</button>
