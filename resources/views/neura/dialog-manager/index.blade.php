<div
    x-data="{
        dialogs: [],
        loading: {},

        typeConfig: {
            info: {
                iconName: 'information-circle',
                iconColor: 'text-blue-500 dark:text-blue-400',
                iconBg: 'bg-blue-100 dark:bg-blue-900/30',
            },
            success: {
                iconName: 'check-circle',
                iconColor: 'text-green-500 dark:text-green-400',
                iconBg: 'bg-green-100 dark:bg-green-900/30',
            },
            warning: {
                iconName: 'exclamation-triangle',
                iconColor: 'text-amber-500 dark:text-amber-400',
                iconBg: 'bg-amber-100 dark:bg-amber-900/30',
            },
            danger: {
                iconName: 'exclamation-circle',
                iconColor: 'text-red-500 dark:text-red-400',
                iconBg: 'bg-red-100 dark:bg-red-900/30',
            },
        },

        showDialog(options) {
            const dialog = {
                id: Date.now() + Math.random(),
                type: options.type || 'info',
                title: options.title || '',
                message: options.message || '',
                confirmText: options.confirmText || window.t('confirm'),
                cancelText: options.cancelText || window.t('cancel'),
                showCancel: options.showCancel !== false,
                confirmVariant: options.confirmVariant || (options.type === 'danger' ? 'danger' : 'primary'),
                wireId: options.wireId || null,
                onConfirm: options.onConfirm || null,
                onConfirmParams: options.onConfirmParams || [],
                onCancel: options.onCancel || null,
                onCancelParams: options.onCancelParams || [],
                inputValue: options.inputValue || '',
                inputPlaceholder: options.inputPlaceholder || '',
                showInput: options.showInput || false,
                size: options.size || 'md',
            };

            this.dialogs.push(dialog);
            this.loading[dialog.id] = false;
            document.body.style.overflow = 'hidden';

            return dialog.id;
        },

        async confirm(id) {
            const dialog = this.dialogs.find(d => d.id === id);
            if (!dialog) return;

            // Set loading state
            this.loading[id] = true;

            try {
                if (dialog.onConfirm && dialog.wireId) {
                    const $wire = window.Livewire?.find(dialog.wireId);
                    if ($wire) {
                        const params = dialog.showInput 
                            ? [dialog.inputValue, ...dialog.onConfirmParams]
                            : dialog.onConfirmParams;
                        
                        await $wire.call(dialog.onConfirm, ...params);
                    }
                }

                window.dispatchEvent(new CustomEvent('dialog-confirmed', {
                    detail: { id, value: dialog.showInput ? dialog.inputValue : true }
                }));
            } catch (e) {
                console.error('Error executing dialog onConfirm callback:', e);
            } finally {
                this.loading[id] = false;
                this.close(id);
            }
        },

        async cancel(id) {
            const dialog = this.dialogs.find(d => d.id === id);
            if (!dialog) return;

            try {
                if (dialog.onCancel && dialog.wireId) {
                    const $wire = window.Livewire?.find(dialog.wireId);
                    if ($wire) {
                        await $wire.call(dialog.onCancel, ...dialog.onCancelParams);
                    }
                }

                window.dispatchEvent(new CustomEvent('dialog-cancelled', { detail: { id } }));
            } catch (e) {
                console.error('Error executing dialog onCancel callback:', e);
            } finally {
                this.close(id);
            }
        },

        close(id) {
            delete this.loading[id];
            this.dialogs = this.dialogs.filter(d => d.id !== id);
            if (this.dialogs.length === 0) {
                document.body.style.overflow = '';
            }
        },

        getConfig(type) {
            return this.typeConfig[type] || this.typeConfig.info;
        },

        getSizeClass(size) {
            return {
                'xs': 'max-w-xs',
                'sm': 'max-w-sm',
                'md': 'max-w-md',
                'lg': 'max-w-lg',
                'xl': 'max-w-xl',
            }[size] || 'max-w-md';
        }
    }"
    x-on:dialog.window="showDialog($event.detail)"
    class="relative z-9999"
>
    <template x-for="dialog in dialogs" :key="dialog.id">
        <div
            class="fixed inset-0 overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div
                class="fixed inset-0 bg-surface-overlay"
                x-on:click="cancel(dialog.id)"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative bg-surface-raised backdrop-blur-xl rounded-lg shadow-xl w-full border border-edge overflow-hidden"
                    :class="getSizeClass(dialog.size)"
                    x-on:click.stop
                    x-on:keydown.escape.window="cancel(dialog.id)"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="shrink-0 size-10 rounded-full flex items-center justify-center"
                                :class="getConfig(dialog.type).iconBg"
                            >
                                <template x-if="dialog.type === 'info'">
                                    <neura::icon name="information-circle" class="size-5 text-blue-500 dark:text-blue-400" />
                                </template>
                                <template x-if="dialog.type === 'success'">
                                    <neura::icon name="check-circle" class="size-5 text-green-500 dark:text-green-400" />
                                </template>
                                <template x-if="dialog.type === 'warning'">
                                    <neura::icon name="exclamation-triangle" class="size-5 text-amber-500 dark:text-amber-400" />
                                </template>
                                <template x-if="dialog.type === 'danger'">
                                    <neura::icon name="exclamation-circle" class="size-5 text-red-500 dark:text-red-400" />
                                </template>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h3
                                    class="text-lg font-semibold text-fg mb-2"
                                    x-text="dialog.title"
                                ></h3>
                                <p
                                    class="text-sm text-fg-secondary mb-6"
                                    x-show="dialog.message"
                                    x-text="dialog.message"
                                ></p>

                                <div x-show="dialog.showInput" class="mb-6">
                                    <input
                                        type="text"
                                        x-model="dialog.inputValue"
                                        :placeholder="dialog.inputPlaceholder"
                                        class="w-full px-3 py-2 text-sm bg-surface-raised border border-edge-hover rounded-lg text-fg placeholder-neutral-400 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent"
                                        x-on:keydown.enter="confirm(dialog.id)"
                                        :disabled="loading[dialog.id]"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <template x-if="dialog.showCancel">
                                <neura::button
                                    variant="soft"
                                    size="sm"
                                    x-on:click="cancel(dialog.id)"
                                    x-bind:disabled="loading[dialog.id]"
                                >
                                    <span x-text="dialog.cancelText"></span>
                                </neura::button>
                            </template>

                            <template x-if="dialog.confirmVariant === 'primary'">
                                <neura::button
                                    variant="primary"
                                    size="sm"
                                    x-on:click="confirm(dialog.id)"
                                    x-bind:disabled="loading[dialog.id]"
                                >
                                    <span x-show="loading[dialog.id]" class="absolute inset-0 flex items-center justify-center">
                                        <neura::icon.loading variant="mini" class="size-4" />
                                    </span>
                                    <span x-bind:class="loading[dialog.id] ? 'opacity-0' : ''" x-text="dialog.confirmText"></span>
                                </neura::button>
                            </template>

                            <template x-if="dialog.confirmVariant === 'danger'">
                                <neura::button
                                    variant="danger"
                                    size="sm"
                                    x-on:click="confirm(dialog.id)"
                                    x-bind:disabled="loading[dialog.id]"
                                >
                                    <span x-show="loading[dialog.id]" class="absolute inset-0 flex items-center justify-center">
                                        <neura::icon.loading variant="mini" class="size-4" />
                                    </span>
                                    <span x-bind:class="loading[dialog.id] ? 'opacity-0' : ''" x-text="dialog.confirmText"></span>
                                </neura::button>
                            </template>

                            <template x-if="dialog.confirmVariant === 'success'">
                                <neura::button
                                    variant="success"
                                    size="sm"
                                    x-on:click="confirm(dialog.id)"
                                    x-bind:disabled="loading[dialog.id]"
                                >
                                    <span x-show="loading[dialog.id]" class="absolute inset-0 flex items-center justify-center">
                                        <neura::icon.loading variant="mini" class="size-4" />
                                    </span>
                                    <span x-bind:class="loading[dialog.id] ? 'opacity-0' : ''" x-text="dialog.confirmText"></span>
                                </neura::button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
