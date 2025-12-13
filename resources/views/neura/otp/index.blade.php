@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'length' => 4,
    'type'=>'text',
    'allowedPattern' => '[0-9]',
    'autofocus' => false
])

<div
    x-data="{
        state: '',
        inputs: [],
        length: @js($length),
        allowedPattern: @js($allowedPattern),
        autofocus: @js($autofocus),
        init() {
            $nextTick(() => {
                this.inputs = Array.from(this.$el.querySelectorAll('[data-slot=otp-input]'));

                this.inputs.forEach((input, index) => {
                    input.setAttribute('data-order', index);
                    input.setAttribute('aria-label', `${window.t('digit')} ${index + 1} ${window.t('of')} ${this.length}`);
                    input.disabled = true;
                });

                // handle the case wwhere the bounded external state has initial value
                this.syncExternalState();

                // focus the last empty box
                let lastEmptyInputBox = this.state.length;

                if(this.inputs[lastEmptyInputBox]){
                    this.inputs[lastEmptyInputBox].disabled = false;
                    if(this.autofocus){
                        this.inputs[lastEmptyInputBox].focus()
                    }
                }

            });
            this.$watch('state', (value) => {
                // Sync with Alpine state
                this.$root?._x_model?.set(value);

                // Sync with Livewire state
                let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'))

                if(this.$wire && wireModel){
                    let prop = this.$root.getAttribute(wireModel)
                    this.$wire.set(prop, value, wireModel?.includes('.live'));
                }
            });
        },
        syncExternalState(){
            const externalState = this.$root?._x_model?.get();

            if(externalState != null){
                const chars = String(externalState).slice(0, this.length).split('');

                this.state = chars.join('');

                chars.forEach((char, i) => {
                    if (this.inputs[i]) {
                        this.inputs[i].value = char;
                        this.inputs[i].disabled = false;
                    }
                });
            }
        },
        handleInput(el) {
            const index = parseInt(el.dataset.order);
            const value = el.value;

            const regex = new RegExp(`^${this.allowedPattern}$`);
            if (!regex.test(value)) {
                el.value = '';
                return;
            }

            this.syncState();

            const next = this.inputs[index + 1];
            if (next) {
                this.focusAndSelect(next);
            }
        },
        handlePaste(e) {
            const pasted = e.clipboardData.getData('text');

            const regex = new RegExp(this.allowedPattern);

            const validChars = Array.from(pasted).filter(char => regex.test(char));

            const startIndex = parseInt(e.target.dataset.order);

            validChars.forEach((char, offset) => this.enableAndFill(char, offset + startIndex));

            // Need to focus the input immediately after the last filled one,
            // but we defer it with $nextTick() to leet the current Js callstack complete,
            // This ensures all synchronous DOM property updatess (like input.value = char) are applied in the JS engine,
            // so the focus action sees the correct, updated state.
            $nextTick(() => {
                const nextIndex = startIndex + validChars.length;
                const next = this.inputs[nextIndex];

                if (next) {
                    this.focusAndSelect(next)
                }
            });

            this.syncState();
        },
        enableAndFill(char, index) {
            const input = this.inputs[index];
            if (!input) return;

            input.disabled = false;
            input.value = char;
        },
        focusAndSelect(el) {
            if (!el) return;
            el.disabled = false;
            requestAnimationFrame(() => {
                el.focus();
                el.select();
            });
        },
        handleBackspace(e) {
            const input = e.target;
            const index = parseInt(input.dataset.order);

            input.value = '';

            if(index !== 0){ // prevent disabling the first input
                input.disabled = true;
            }

            const prev = this.inputs[index - 1];
            if (prev) {
                requestAnimationFrame(() => prev.focus());
            }

            e.preventDefault();
            this.syncState();
        },
        syncState() {
            this.state = this.inputs.map(input => input.value || '').join('');
        },

}"
{{ $attributes->class('contents') }}
>
<div
    x-ref="inputsWrapper"
    role="group"
    x-bind:aria-label="window.t('oneTimePasswordInput')"
    {{ $attributes->class('text-start') }}
>
    <div
        @class([
            'flex rounded-box items-center ',
            '[:where(&>[data-slot=otp-input]:has(+[data-slot=separator]))]:rounded-r-box',
            '[:where(&>[data-slot=separator]+[data-slot=otp-input])]:rounded-l-box'
        ])
    >
        @if($slot->isNotEmpty())

            {{ $slot }}
        @else
            @foreach (range(1, $length) as $column)
                <neura::otp.input />
            @endforeach
        @endif
    </div>
</div>
</div>