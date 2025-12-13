<neura::input.options.button
    x-data="{
        revealed: false,
        toggleReveal() {
            const input = $el.closest('[data-slot=input-actions]').parentElement.querySelector('input[data-control-id=input]');
            if (!input) return;

            this.revealed = !this.revealed;
            input.type = this.revealed ? 'text' : 'password';
        }
    }"
    x-on:click="toggleReveal()"
    x-bind:data-slot-revealed="revealed"
    x-bind:aria-label="revealed ? window.t('hidePassword') : window.t('showPassword')"
    x-bind:title="revealed ? window.t('hidePassword') : window.t('showPassword')"
>
    <neura::icon
        name="eye-slash"
        class="hidden [[data-slot-revealed]>&]:inline-flex"
        aria-hidden="true"
    />
    <neura::icon
        name="eye"
        class="inline-flex [[data-slot-revealed]>&]:hidden"
        aria-hidden="true"
    />
</neura::input.options.button>