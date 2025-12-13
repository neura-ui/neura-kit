@aware(['type' => 'text','name'=> null])

@php
$classes = [
    '[:where(&:first-child)]:rounded-l-box [:where(&:last-child)]:rounded-r-box',
    'text-center text-base max-w-12 w-full h-12',
    'bg-white dark:bg-neutral-900',
    'text-neutral-900 dark:text-neutral-100 placeholder:text-neutral-400 dark:placeholder:text-neutral-500',
    'border border-primary-200 dark:border-primary-700',
    'focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400/20',
    'transition duration-300 ease-in-out',
    'shadow-sm'
];
@endphp

<input
    {{ $attributes
        ->merge([
            'name' => $name,
            'type' => $type,
        ])
        ->class($classes) }}

    maxlength="1"
    required
    data-slot="otp-input"
    x-on:input="handleInput($el)"
    x-on:keydown.enter="handleInput($el)"
    x-on:paste="handlePaste($event)"
    x-on:keydown.backspace="handleBackspace($event)"

    autocomplete="one-time-code"
    x-on:keydown.right="$focus.within($refs.inputsWrapper).next()"
    x-on:keydown.up="$focus.within($refs.inputsWrapper).next()"
    x-on:keydown.left="$focus.within($refs.inputsWrapper).prev()"
    x-on:keydown.down="$focus.within($refs.inputsWrapper).prev()"

    x-on:focus="requestAnimationFrame(() => $el.select())"
/>
