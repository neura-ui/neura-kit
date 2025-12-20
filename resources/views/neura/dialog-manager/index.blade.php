<div
    x-data="{
        dialogs: [],

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
                onConfirm: options.onConfirm || null,
                onCancel: options.onCancel || null,
                inputValue: options.inputValue || '',
                inputPlaceholder: options.inputPlaceholder || '',
                showInput: options.showInput || false,
                size: options.size || 'md',
            };

            this.dialogs.push(dialog);
            document.body.style.overflow = 'hidden';

            return dialog.id;
        },

        confirm(id) {
            const dialog = this.dialogs.find(d => d.id === id);
            if (dialog) {
                if (dialog.onConfirm) {
                    dialog.onConfirm(dialog.showInput ? dialog.inputValue : true);
                }
                window.dispatchEvent(new CustomEvent('dialog-confirmed', {
                    detail: { id, value: dialog.showInput ? dialog.inputValue : true }
                }));
                this.close(id);
            }
        },

        cancel(id) {
            const dialog = this.dialogs.find(d => d.id === id);
            if (dialog) {
                if (dialog.onCancel) {
                    dialog.onCancel();
                }
                window.dispatchEvent(new CustomEvent('dialog-cancelled', { detail: { id } }));
                this.close(id);
            }
        },

        close(id) {
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
                class="fixed inset-0 bg-black/50 dark:bg-black/70"
                x-on:click="cancel(dialog.id)"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative bg-white dark:bg-neutral-900 rounded-lg shadow-xl w-full border border-neutral-200 dark:border-neutral-800 overflow-hidden"
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
                                class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2"
                                x-text="dialog.title"
                            ></h3>
                            <p
                                class="text-sm text-neutral-600 dark:text-neutral-400 mb-6"
                                x-show="dialog.message"
                                x-text="dialog.message"
                            ></p>

                            <div x-show="dialog.showInput" class="mb-6">
                                <input
                                    type="text"
                                    x-model="dialog.inputValue"
                                    :placeholder="dialog.inputPlaceholder"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-neutral-950 border border-neutral-300 dark:border-neutral-700 rounded-lg text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent"
                                    x-on:keydown.enter="confirm(dialog.id)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                    <button
                        x-show="dialog.showCancel"
                        x-on:click="cancel(dialog.id)"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 transition-colors"
                        x-text="dialog.cancelText"
                    ></button>

                    <button
                        x-on:click="confirm(dialog.id)"
                        type="button"
                        class="px-4 py-2 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 transition-colors"
                        :class="{
                            'bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-neutral-800 dark:hover:bg-neutral-200 focus:ring-neutral-900 dark:focus:ring-neutral-100': dialog.confirmVariant === 'primary',
                            'bg-red-600 dark:bg-red-700 text-white hover:bg-red-700 dark:hover:bg-red-600 focus:ring-red-600': dialog.confirmVariant === 'danger',
                            'bg-green-600 dark:bg-green-700 text-white hover:bg-green-700 dark:hover:bg-green-600 focus:ring-green-600': dialog.confirmVariant === 'success',
                        }"
                        x-text="dialog.confirmText"
                    ></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
