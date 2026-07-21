@props([
    'position' => 'bottom-center',
    'teleport' => 'body',
    'portal' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $disabled = $attributes->has('disabled') && !in_array($attributes->get('disabled'), [false, 0, '0', 'false', ''], true);
    $roundedClass = PackResolver::rounded(neura_config('dropdown', 'rounded'));
    $shadowClass = PackResolver::shadow(neura_config('dropdown', 'shadow'));

    $defaultPanelClasses = [
        'isolate',
        'z-50',
        'grid',
        '[:where(&)]:max-w-96',
        '[:where(&)]:min-w-44',
        'text-start',
        'bg-surface-raised',
        'border border-black/[0.06] dark:border-white/[0.08]',
        'ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
        $shadowClass,
        $roundedClass,
        'p-(--dropdown-padding)',
        '[--dropdown-padding:--spacing(1)]',
    ];
@endphp

<div {{ $attributes }}>
    <div
        x-data="{
            open: false,
            disabled: false,

            init() {
                this.$watch('disabled', (isDisabled) => {
                    if (isDisabled && this.open) {
                        this.close();
                    }
                });
            },

            toggle() {
                if (this.disabled) return;
                this.open = !this.open;
            },

            close(focusAfter) {
                if (!this.open) return;

                this.open = false;
                focusAfter?.focus();
            },

            isOpen() {
                return this.open;
            },

            handleFocusInOut(event) {
                if (this.disabled || !this.open) return;

                const panel = this.$refs.panel;
                const button = this.$refs.button;
                if (!panel || !button) return;

                const target = event.target;
                if (panel.contains(target) || button.contains(target)) return;

                const lastFocused = document.activeElement;

                if (
                    lastFocused &&
                    !button.contains(lastFocused) &&
                    !panel.contains(lastFocused)
                ) {
                    this.close(button);
                }
            }
        }"
        x-effect="
            disabled = @js($disabled);
            if (disabled && open) this.close();
        "
        x-on:keydown.escape.prevent.stop="this.close($refs.button)"
        x-on:focusin.window="handleFocusInOut($event)"
        x-id="['dropdown-panel']"
        class="relative"
    >
        {{-- BUTTON --}}
        <div
            x-ref="button"
            x-bind:aria-disabled="disabled.toString()"
            x-bind:tabindex="disabled ? -1 : 0"
            x-on:click.prevent="toggle()"
            x-on:keydown.enter.prevent="toggle()"
            x-on:keydown.space.prevent="toggle()"
            {{ $button->attributes->class('flex items-center rounded-field') }}
        >
            {{ $button }}
        </div>

        @if($portal)
            <template x-teleport="{{ $teleport }}">
                @endif

                {{-- PANEL --}}
                <div
                    x-show="open"
                    x-ref="panel"
                    x-anchor.{{ $position }}.offset.6="$refs.button"
                    x-on:keydown.down.prevent.stop="$focus.next()"
                    x-on:keydown.up.prevent.stop="$focus.prev()"
                    x-on:keydown.home.prevent.stop="$focus.first()"
                    x-on:keydown.end.prevent.stop="$focus.last()"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    x-on:click.away="this.close($refs.button)"
                    x-bind:id="$id('dropdown-panel')"
                    style="display: none; backdrop-filter: blur(64px); -webkit-backdrop-filter: blur(64px); z-index: 9999"
                    role="menu"
                    {{ $menu->attributes->merge(['class' => Arr::toCssClasses($defaultPanelClasses)]) }}
                >
                    {{ $menu }}
                </div>

                @if($portal)
            </template>
        @endif
    </div>
</div>
