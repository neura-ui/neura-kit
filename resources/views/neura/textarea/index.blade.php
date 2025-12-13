@props([
    'disabled' => false,
    'resize' => 'vertical',
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'rows' => null,
    'invalid' => null,
    ])
@php
    use Neura\Kit\Support\PackResolver;

    $inputColors = PackResolver::inputColor('base');
    $rows ??= 3;

    $initialHeight = (($rows) * 1.5) + 0.75;

    $classes = [
        'inline-block px-3 py-2 w-full text-sm text-neutral-900 disabled:text-neutral-500 placeholder-neutral-400 disabled:placeholder-neutral-400/70 dark:text-neutral-100 dark:disabled:text-neutral-500 dark:placeholder-neutral-500 dark:disabled:placeholder-neutral-600',
        'bg-white dark:bg-neutral-950 disabled:bg-neutral-50 dark:disabled:bg-neutral-900',
        'disabled:cursor-not-allowed transition-colors duration-150',
        'shadow-sm disabled:shadow-none border rounded-lg',
        'focus:ring-offset-0 focus:outline-none',
        $inputColors['border'] => !$invalid,
        $inputColors['focus'] => !$invalid,
        $inputColors['invalid'] => $invalid,
        match ($resize) {
            'none' => 'resize-none',
            'both' => 'resize',
            'horizontal' => 'resize-x',
            'vertical' => 'resize-y',
        },
    ];
@endphp

<textarea
    x-data="{
        initialHeight: @js($initialHeight) + 'rem',
        height: @js($initialHeight) + 'rem',
        name: @js($name),
        state: '',
        resize() {
            if (!this.$el) return;
            this.$el.style.height = 'auto';
            let newHeight = this.$el.scrollHeight + 'px';

            if (this.$el.scrollHeight < parseFloat(this.initialHeight)) {
                this.$el.style.height = this.initialHeight;
            } else {
                this.$el.style.height = newHeight;
            }
        }
    }"
    x-init="
        $nextTick(() => {
            // Initialize state from x-model or wire:model binding
            this.state = this.$root?._x_model?.get();
        })

        // Two-way data binding: sync internal state back to Alpine/Livewire
        $watch('state', (value) => {
            // Sync with Alpine.js x-model
            this.$root?._x_model?.set(value);

            // Sync with Livewire wire:model (if present)
            let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'))

            if(this.$wire && wireModel){
                let prop = this.$root.getAttribute(wireModel)
                this.$wire.set(prop, value, wireModel?.includes('.live'));
            }
        });

        if(!this.$el) return;

        // give our textarea initial height based on the provided rows
        this.$el.style.height = this.initialHeight;

        const observer = new ResizeObserver(() => {
            this.resize();
        });

        observer.observe(this.$el);
    "
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
    @disabled($disabled)
    @if ($invalid) aria-invalid="true" data-slot="invalid" @endif
    data-slot="textarea"
    x-intersect.once="resize()"
    rows={{ $rows }}
    x-on:input.stop="resize()"
    x-on:resize.window="resize()"
    x-on:keydown="resize()"
></textarea>
