@aware(['type' => 'text', 'name' => null, 'invalid' => false])

@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('base');

    $classes = [
        '[:where(&:first-child)]:rounded-l-box [:where(&:last-child)]:rounded-r-box',
        'text-center text-base max-w-12 w-full h-12',
        'bg-surface',
        'text-fg placeholder:text-neutral-400 dark:placeholder:text-neutral-500',
        $colors['border'] => !$invalid,
        $colors['focus'] => !$invalid,
        $colors['invalid'] => $invalid,
        'focus:outline-none',
        'transition-all duration-200',
        'shadow-sm disabled:shadow-none disabled:opacity-50 disabled:cursor-not-allowed',
        'border focus:z-10',
    ];
@endphp

<input
    {{ $attributes->merge([
            'name' => $name,
            'type' => $type,
        ])->class($classes) }}
    maxlength="1" required data-slot="otp-input" x-on:input="handleInput($el)" x-on:keydown.enter="handleInput($el)"
    x-on:paste="handlePaste($event)" x-on:keydown.backspace="handleBackspace($event)" autocomplete="one-time-code"
    x-on:keydown.right="$focus.within($refs.inputsWrapper).next()"
    x-on:keydown.up="$focus.within($refs.inputsWrapper).next()"
    x-on:keydown.left="$focus.within($refs.inputsWrapper).prev()"
    x-on:keydown.down="$focus.within($refs.inputsWrapper).prev()"
    x-on:focus="requestAnimationFrame(() => $el.select())" />
