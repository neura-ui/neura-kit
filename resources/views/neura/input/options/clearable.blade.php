<neura::input.options.button
    x-on:click="
        const input = $el.closest('[data-slot=input-actions]').parentElement.querySelector('input[data-control-id=input]');
        if (input) {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    "
>
    <neura::icon name="x-mark" />
</neura::input.options.button>