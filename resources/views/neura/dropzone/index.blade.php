@props([
    'name' => null,
    'accept' => 'image/*',
    'maxSize' => 10,
    'multiple' => false,
    'preview' => true,
    'removable' => true,
    'label' => null,
    'description' => null,
    'invalid' => null,
])

@php
    $invalid ??= $name && $errors->has($name);
    $maxSizeBytes = $maxSize * 1024 * 1024;
@endphp

<div
    x-data="{
        files: [],
        isDragging: false,
        previews: [],
        maxSize: @js($maxSizeBytes),
        multiple: @js($multiple),
        accept: @js($accept),

        init() {
            // Watch for Livewire file uploads
            this.$watch('files', (value) => {
                if (value.length > 0) {
                    this.generatePreviews();
                }
            });
        },

        handleDragOver(e) {
            e.preventDefault();
            this.isDragging = true;
        },

        handleDragLeave(e) {
            e.preventDefault();
            this.isDragging = false;
        },

        handleDrop(e) {
            e.preventDefault();
            this.isDragging = false;

            const droppedFiles = Array.from(e.dataTransfer.files);
            this.processFiles(droppedFiles);
        },

        handleFileSelect(e) {
            const selectedFiles = Array.from(e.target.files);
            this.processFiles(selectedFiles);
        },

        processFiles(fileList) {
            // Validate file size
            const validFiles = fileList.filter(file => {
                if (file.size > this.maxSize) {
                    this.$dispatch('notify', {
                        type: 'error',
                        content: window.t('fileExceedsMaxSize', { fileName: file.name, maxSize: @js($maxSize) }),
                        duration: 5000
                    });
                    return false;
                }
                return true;
            });

            if (!this.multiple && validFiles.length > 0) {
                this.files = [validFiles[0]];
            } else {
                this.files = [...this.files, ...validFiles];
            }

            this.generatePreviews();
        },

        generatePreviews() {
            this.previews = [];

            this.files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previews[index] = {
                            type: 'image',
                            url: e.target.result,
                            name: file.name,
                            size: this.formatFileSize(file.size)
                        };
                    };
                    reader.readAsDataURL(file);
                } else {
                    this.previews[index] = {
                        type: 'file',
                        name: file.name,
                        size: this.formatFileSize(file.size),
                        extension: file.name.split('.').pop().toUpperCase()
                    };
                }
            });
        },

        removeFile(index) {
            this.files.splice(index, 1);
            this.previews.splice(index, 1);

            // Reset input
            const input = this.$refs.fileInput;
            if (input) {
                input.value = '';
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        triggerFileInput() {
            this.$refs.fileInput.click();
        }
    }"
    {{ $attributes->class('w-full') }}
>
    @if($label)
        <neura::label :text="$label" class="mb-2" />
    @endif

    @if($description)
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-3">{{ $description }}</p>
    @endif

    <!-- Dropzone Area -->
    <div
        x-on:dragover="handleDragOver"
        x-on:dragleave="handleDragLeave"
        x-on:drop="handleDrop"
        x-on:click="triggerFileInput"
        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950/50' : 'border-primary-200 dark:border-primary-800'"
        class="relative border-2 border-dashed rounded-lg p-8 transition-all duration-150 cursor-pointer hover:border-primary-400 dark:hover:border-primary-600 hover:bg-primary-50/50 dark:hover:bg-primary-950/30"
        :class="{ 'border-danger-500 dark:border-danger-400': @js($invalid) }"
    >
        <input
            x-ref="fileInput"
            type="file"
            :accept="accept"
            :multiple="multiple"
            x-on:change="handleFileSelect"
            class="hidden"
            @if($name) name="{{ $name }}" @endif
            @if($attributes->has('wire:model')) wire:model="{{ $attributes->get('wire:model') }}" @endif
        />

        <div class="flex flex-col items-center justify-center text-center space-y-3">
            <!-- Upload Icon -->
            <div class="w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center">
                <neura::icon name="arrow-up-tray" class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>

            <!-- Text -->
            <div>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    <span class="text-neutral-600 dark:text-neutral-400">{{ neura_trans('dragAndDrop') }} {{ $multiple ? neura_trans('files') : neura_trans('aFile') }} {{ neura_trans('hereOr') }}</span>
                    <span class="text-neutral-900 dark:text-neutral-100 underline">{{ neura_trans('browse') }}</span>
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1">
                    {{ $accept === 'image/*' ? neura_trans('imagesOnly') : neura_trans('allFiles') }} · {{ neura_trans('max') }} {{ $maxSize }}MB
                </p>
            </div>
        </div>
    </div>

    <!-- Preview Area -->
    @if($preview)
        <div x-show="previews.length > 0" x-cloak class="mt-4 space-y-2">
            <template x-for="(preview, index) in previews" :key="index">
                <div class="relative border border-neutral-200 dark:border-neutral-800 rounded-lg p-3 bg-white dark:bg-neutral-950 shadow-sm">
                    <div class="flex items-center gap-3">
                        <!-- Image Preview -->
                        <template x-if="preview.type === 'image'">
                            <img
                                :src="preview.url"
                                :alt="preview.name"
                                class="w-12 h-12 rounded object-cover shrink-0 ring-1 ring-neutral-200 dark:ring-neutral-700"
                            />
                        </template>

                        <!-- File Icon -->
                        <template x-if="preview.type === 'file'">
                            <div class="w-12 h-12 rounded bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center shrink-0">
                                <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300" x-text="preview.extension"></span>
                            </div>
                        </template>

                        <!-- File Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate" x-text="preview.name"></p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-500" x-text="preview.size"></p>
                        </div>

                        <!-- Remove Button -->
                        @if($removable)
                            <button
                                type="button"
                                x-on:click.stop="removeFile(index)"
                                class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-md transition-colors shrink-0"
                                x-bind:title="window.t('removeFile')"
                            >
                                <neura::icon name="x-mark" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    @endif

    <!-- Error Messages -->
    @if($name)
        <neura::error :name="$name" />
    @endif
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
