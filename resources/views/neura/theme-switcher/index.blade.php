@props([
    'variant' => 'dropdown',
    'darkIcon' => 'moon',
    'lightIcon' => 'sun',
    'systemIcon' => 'computer-desktop',
    'iconVariant' => 'mini',
    /**
     * View Transition animation: circle | blur-circle | qr-scan | polygon |
     * polygon-gradient | gif | none. Defaults to circle.
     * Ignored for the pullcord variant (the cord is the interaction).
     */
    'animation' => 'circle',
    /** PullCord: accessible name for the knob. */
    'ariaLabel' => null,
    /** PullCord: skip the drop-in entrance. */
    'noEntrance' => false,
    /** PullCord physics overrides. */
    'gravity' => null,
    'damping' => null,
    'iterations' => null,
    'stretchMax' => null,
    /** PullCord: absolute instead of fixed (for contained previews). */
    'embedded' => false,
])

@if ($variant === 'pullcord')
    <neura::theme-switcher.variants.pullcord
        :aria-label="$ariaLabel"
        :no-entrance="$noEntrance"
        :gravity="$gravity"
        :damping="$damping"
        :iterations="$iterations"
        :stretch-max="$stretchMax"
        :embedded="$embedded"
    />
@else
    <div
        class="flex items-center"
        data-nk-theme-animation="{{ $animation }}"
    >
        <label class="sr-only">
            Theme
        </label>

        <div x-data>
            @if ($variant === 'dropdown')
                <neura::theme-switcher.variants.dropdown/>
            @elseif($variant === 'stacked')
                <neura::theme-switcher.variants.stacked/>
            @elseif($variant === 'inline')
                <neura::theme-switcher.variants.inline/>
            @endif
        </div>
    </div>
@endif
