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
    'uploadUrl' => null,
    'chunkSize' => 1,
    'uploadHeaders' => [],
    'concurrency' => 2,
])

@php
    $maxSizeBytes = $maxSize * 1024 * 1024;
    $chunkSizeBytes = $chunkSize * 1024 * 1024;

    $wireModelValue = $attributes->whereStartsWith('wire:model')->first();
    $hasWireModel = $wireModelValue !== null;

    $defaultUploadUrl = $uploadUrl ?? route('neura-kit.upload.chunks');

    $fieldName = $name ?? $wireModelValue;

    // Check for errors using Blade's $errors
    $hasFieldError = false;
    if ($fieldName) {
        $hasFieldError = $errors->has($fieldName)
            || $errors->has($fieldName . '.*')
            || collect($errors->keys())->contains(fn($key) => str_starts_with($key, $fieldName . '.'));
    }
    
    // Use explicit invalid prop if set, otherwise use detected errors
    $isInvalid = $invalid ?? $hasFieldError;
@endphp

<div
    data-nk-dropzone
    x-data="neuraDropzone({
        accept: @js($accept),
        maxSizeBytes: @js($maxSizeBytes),
        multiple: @js($multiple),
        chunkSize: @js($chunkSizeBytes),
        uploadUrl: @js($defaultUploadUrl),
        uploadHeaders: @js(array_merge(['X-CSRF-TOKEN' => csrf_token()], $uploadHeaders)),
        name: @js($fieldName),
        invalid: @js($isInvalid),
        wireModel: @js($wireModelValue),
        previewEnabled: @js((bool) $preview),
        removable: @js((bool) $removable),
        concurrency: @js((int) $concurrency),
    })"
    x-init="init()"
    {{ $attributes->except(['wire:model', 'wire:model.defer', 'wire:model.lazy', 'wire:model.live'])->class('w-full') }}
>
    @if ($label)
        <neura::label :text="$label" class="mb-2" />
    @endif

    @if ($description)
        <p class="text-sm text-fg-secondary mb-3">{{ $description }}</p>
    @endif

    <div
        x-on:dragover.prevent="handleDragOver($event)"
        x-on:dragleave.prevent="handleDragLeave($event)"
        x-on:drop.prevent="handleDrop($event)"
        x-on:click="triggerFileInput()"
        x-on:keydown.enter.prevent="triggerFileInput()"
        tabindex="0"
        @class([
            'relative rounded-xl p-8 transition-all duration-200 cursor-pointer group focus:outline-none border-2 border-dashed',
            'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-950/40 ring-4 ring-red-100 dark:ring-red-900/30' => $isInvalid,
            'border-edge hover:border-edge-hover hover:bg-hover' => !$isInvalid,
        ])
        :class="{
            'border-red-400! dark:border-red-500! bg-red-50! dark:bg-red-950/40! ring-4! ring-red-100! dark:ring-red-900/30!': hasError,
            'border-primary-400! dark:border-primary-500! bg-primary-50/80! dark:bg-primary-950/60! ring-4! ring-primary-100! dark:ring-primary-900/40! scale-[1.01]!': isDragging && !hasError,
        }"
    >
        <input
            x-ref="fileInput"
            type="file"
            :accept="accept"
            :multiple="multiple"
            x-on:change.prevent.stop="handleFileSelect($event)"
            x-on:click.stop
            class="hidden"
        />

        <div x-ref="hiddenFields"></div>

        <div class="flex flex-col items-center justify-center text-center space-y-4">
            <div
                @class([
                    'w-14 h-14 rounded-2xl flex items-center justify-center transition-all duration-200',
                    'bg-red-100 dark:bg-red-900/50 ring-2 ring-red-200 dark:ring-red-800' => $isInvalid,
                    'bg-surface-inset group-hover:bg-active group-hover:scale-105' => !$isInvalid,
                ])
                :class="{
                    'bg-red-100! dark:bg-red-900/50! ring-2! ring-red-200! dark:ring-red-800!': hasError,
                    'bg-primary-100! dark:bg-primary-900/50! ring-2! ring-primary-200! dark:ring-primary-800! scale-110!': isDragging && !hasError,
                }"
            >
                <neura::icon
                    name="arrow-up-tray"
                    @class([
                        'w-6 h-6 transition-all duration-200',
                        'text-red-500 dark:text-red-400' => $isInvalid,
                        'text-fg-muted group-hover:text-fg-secondary' => !$isInvalid,
                    ])
                    x-bind:class="{
                        'text-red-500! dark:text-red-400!': hasError,
                        'text-primary-500! dark:text-primary-400! scale-110!': isDragging && !hasError,
                    }"
                />
            </div>

            <div class="space-y-1">
                <p
                    @class([
                        'text-sm font-medium transition-colors',
                        'text-red-700 dark:text-red-300' => $isInvalid,
                        'text-fg-secondary' => !$isInvalid,
                    ])
                    :class="{
                        'text-red-700! dark:text-red-300!': hasError,
                        'text-primary-700! dark:text-primary-300!': isDragging && !hasError,
                    }"
                >
                    <span x-show="!isDragging">
                        {{ neura_trans('dragAndDrop') }}
                        {{ $multiple ? neura_trans('files') : neura_trans('aFile') }} {{ neura_trans('hereOr') }}
                        <span class="text-primary-600 dark:text-primary-400 underline underline-offset-2 hover:text-primary-700 dark:hover:text-primary-300">{{ neura_trans('browse') }}</span>
                    </span>
                    <span x-show="isDragging" x-cloak class="text-primary-600 dark:text-primary-400">
                        {{ neura_trans('dropFilesHere') }}
                    </span>
                </p>

                <p
                    @class([
                        'text-xs transition-colors',
                        'text-red-500 dark:text-red-400' => $isInvalid,
                        'text-fg-muted' => !$isInvalid,
                    ])
                    :class="{
                        'text-red-500! dark:text-red-400!': hasError,
                    }"
                >
                    {{ $accept === 'image/*' ? neura_trans('imagesOnly') : neura_trans('allFiles') }} ·
                    {{ neura_trans('max') }} {{ $maxSize }}MB
                </p>
            </div>
        </div>
    </div>

    @if ($preview)
        <div x-show="previews.length > 0" x-cloak class="mt-4 space-y-2">
            <template x-for="preview in previews" :key="preview.uuid">
                <div
                    class="relative rounded-xl p-3 transition-all duration-200"
                    :class="{
                        'bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800': preview.status === 'error',
                        'bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800': preview.status === 'success',
                        'bg-surface-inset border border-edge': preview.status !== 'error' && preview.status !== 'success',
                    }"
                >
                    <div class="flex items-center gap-3">
                        <template x-if="preview.type === 'image' && preview.url">
                            <img
                                :src="preview.url"
                                :alt="preview.name"
                                class="w-11 h-11 rounded-lg object-cover shrink-0 ring-1 ring-edge"
                            />
                        </template>

                        <template x-if="preview.type !== 'image' || !preview.url">
                            <div
                                class="w-11 h-11 rounded-lg flex items-center justify-center shrink-0"
                                :class="{
                                    'bg-red-100 dark:bg-red-900/50': preview.status === 'error',
                                    'bg-green-100 dark:bg-green-900/50': preview.status === 'success',
                                    'bg-surface-inset': preview.status !== 'error' && preview.status !== 'success',
                                }"
                            >
                                <span
                                    class="text-[10px] font-bold uppercase"
                                    :class="{
                                        'text-red-600 dark:text-red-400': preview.status === 'error',
                                        'text-green-600 dark:text-green-400': preview.status === 'success',
                                        'text-fg-secondary': preview.status !== 'error' && preview.status !== 'success',
                                    }"
                                    x-text="preview.extension"
                                ></span>
                            </div>
                        </template>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-fg truncate" x-text="preview.name"></p>

                            <div class="flex items-center gap-2 mt-0.5">
                                <p class="text-xs text-fg-muted" x-text="preview.size"></p>
                                <span class="text-fg-disabled">·</span>

                                <span
                                    class="text-xs font-medium"
                                    :class="{
                                        'text-primary-600 dark:text-primary-400': preview.status === 'uploading',
                                        'text-green-600 dark:text-green-400': preview.status === 'success',
                                        'text-red-600 dark:text-red-400': preview.status === 'error',
                                        'text-fg-muted': preview.status === 'idle',
                                    }"
                                    x-text="statusLabel(preview)"
                                ></span>
                            </div>

                            <template x-if="preview.status === 'uploading' || preview.status === 'idle'">
                                <div class="mt-2 h-1.5 w-full rounded-full bg-surface-inset overflow-hidden">
                                    <div
                                        class="h-full bg-primary-500 dark:bg-primary-400 rounded-full transition-all duration-300 ease-out"
                                        :style="`width: ${preview.progress || 0}%`"
                                    ></div>
                                </div>
                            </template>

                            <template x-if="preview.status === 'error' && preview.error">
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400 truncate" x-text="preview.error"></p>
                            </template>
                        </div>

                        @if ($removable)
                            <button
                                type="button"
                                x-on:click.stop="removeByUuid(preview.uuid)"
                                class="p-1.5 rounded-lg transition-colors shrink-0"
                                :class="{
                                    'text-red-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/50': preview.status === 'error',
                                    'text-green-400 hover:text-green-600 hover:bg-green-100 dark:hover:bg-green-900/50': preview.status === 'success',
                                    'text-fg-disabled hover:text-fg-secondary hover:bg-active': preview.status !== 'error' && preview.status !== 'success',
                                }"
                                x-bind:title="window.t?.('removeFile') || '{{ neura_trans('removeFile') }}'"
                            >
                                <neura::icon name="x-mark" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
