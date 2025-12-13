@props([
    'variant' => 'dropdown',
    'darkIcon'=>'moon',
    'lightIcon'=>'sun',
    'systemIcon'=>'computer-desktop',
    'iconVariant' => "mini"
])

<div
    class='flex items-center'>
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
