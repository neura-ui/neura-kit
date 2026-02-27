@aware(['required' => false])

@props([
    'text' => null
])


<div {{ $attributes->merge(['class' => 'text-sm [:where(&)]:text-start font-base select-none [:where(&)]:text-neutral-900 [:where(&)]:dark:text-white']) }} data-slot="label">
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        {{ $text }}
    @endif

    @if(isset($required) && $required)
        <span class="text-red-600 dark:text-red-500 text-xs px-1 py-1" aria-hidden="true">
            *
        </span>
    @endif
</div>
