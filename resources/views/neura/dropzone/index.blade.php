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
    'chunkSize' => 1, // en MB
    'uploadHeaders' => [],
])

@php
    $maxSizeBytes = $maxSize * 1024 * 1024;
    $chunkSizeBytes = $chunkSize * 1024 * 1024;
    
    // Get wire:model value using Livewire's attribute helpers
    $wireModelValue = $attributes->whereStartsWith('wire:model')->first();
    $hasWireModel = $wireModelValue !== null;
    
    // Utiliser la route intégrée par défaut si uploadUrl n'est pas fourni et qu'on n'utilise pas wire:model
    $defaultUploadUrl = !$hasWireModel && $uploadUrl === null ? route('neura-kit.upload.chunks') : $uploadUrl;
    
    // Get field name from wire:model or name prop
    $fieldName = $name ?? $wireModelValue;
    
    // Check for validation errors - handle both direct and array errors (documents, documents.*, documents.0, etc.)
    if ($invalid === null && $fieldName) {
        $invalid = $errors->has($fieldName) || $errors->has($fieldName . '.*') || collect($errors->keys())->contains(fn($key) => str_starts_with($key, $fieldName . '.'));
    }
    $invalid = $invalid ?? false;
@endphp

<div x-data="neuraDropzone({
    accept: @js($accept),
    maxSizeBytes: @js($maxSizeBytes),
    multiple: @js($multiple),
    chunkSize: @js($chunkSizeBytes),
    uploadUrl: @js($defaultUploadUrl),
    uploadHeaders: @js(array_merge(['X-CSRF-TOKEN' => csrf_token()], $uploadHeaders)),
    name: @js($fieldName),
    invalid: @js($invalid),
})" {{ $attributes->class('w-full') }}>
    @if ($label)
        <neura::label :text="$label" class="mb-2" />
    @endif

    @if ($description)
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-3">{{ $description }}</p>
    @endif

    <div x-on:dragover="handleDragOver" x-on:dragleave="handleDragLeave" x-on:drop="handleDrop"
        x-on:click="triggerFileInput"
        class="relative rounded-xl p-8 transition-all duration-200 cursor-pointer group"
        :class="{
            'border-2 border-dashed border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-950/40 ring-4 ring-red-100 dark:ring-red-900/30': hasError || invalid,
            'border-2 border-dashed border-primary-400 dark:border-primary-500 bg-primary-50/80 dark:bg-primary-950/60 ring-4 ring-primary-100 dark:ring-primary-900/40 scale-[1.01]': isDragging && !hasError && !invalid,
            'border-2 border-dashed border-neutral-200 dark:border-neutral-800 hover:border-neutral-300 dark:hover:border-neutral-700 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50': !isDragging && !hasError && !invalid
        }">
        <input x-ref="fileInput" type="file" :accept="accept" :multiple="multiple"
            x-on:change="handleFileSelect" class="hidden"
            @if ($fieldName && !$hasWireModel) name="{{ $fieldName }}" @endif
            @if ($hasWireModel) {{ $attributes->wire('model') }} @endif />

        <div class="flex flex-col items-center justify-center text-center space-y-4">
            <div
                class="w-14 h-14 rounded-2xl flex items-center justify-center transition-all duration-200"
                :class="{
                    'bg-red-100 dark:bg-red-900/50 ring-2 ring-red-200 dark:ring-red-800': hasError || invalid,
                    'bg-primary-100 dark:bg-primary-900/50 ring-2 ring-primary-200 dark:ring-primary-800 scale-110': isDragging && !hasError && !invalid,
                    'bg-neutral-100 dark:bg-neutral-800/80 group-hover:bg-neutral-200/80 dark:group-hover:bg-neutral-700/80 group-hover:scale-105': !isDragging && !hasError && !invalid
                }">
                <neura::icon name="arrow-up-tray" 
                    class="w-6 h-6 transition-all duration-200"
                    x-bind:class="{
                        'text-red-500 dark:text-red-400': hasError || invalid,
                        'text-primary-500 dark:text-primary-400 scale-110': isDragging && !hasError && !invalid,
                        'text-neutral-500 dark:text-neutral-400 group-hover:text-neutral-700 dark:group-hover:text-neutral-300': !isDragging && !hasError && !invalid
                    }" />
            </div>

            <div class="space-y-1">
                <p class="text-sm font-medium transition-colors"
                    :class="{
                        'text-red-700 dark:text-red-300': hasError || invalid,
                        'text-primary-700 dark:text-primary-300': isDragging && !hasError && !invalid,
                        'text-neutral-700 dark:text-neutral-300': !isDragging && !hasError && !invalid
                    }">
                    <span x-show="!isDragging">
                        {{ neura_trans('dragAndDrop') }}
                        {{ $multiple ? neura_trans('files') : neura_trans('aFile') }} {{ neura_trans('hereOr') }}
                        <span class="text-primary-600 dark:text-primary-400 underline underline-offset-2 hover:text-primary-700 dark:hover:text-primary-300">{{ neura_trans('browse') }}</span>
                    </span>
                    <span x-show="isDragging" x-cloak class="text-primary-600 dark:text-primary-400">
                        {{ __('Drop files here') }}
                    </span>
                </p>
                <p class="text-xs transition-colors"
                    :class="{
                        'text-red-500 dark:text-red-400': hasError || invalid,
                        'text-neutral-500 dark:text-neutral-500': !hasError && !invalid
                    }">
                    {{ $accept === 'image/*' ? neura_trans('imagesOnly') : neura_trans('allFiles') }} ·
                    {{ neura_trans('max') }} {{ $maxSize }}MB
                </p>
            </div>
        </div>

    </div>

    @if ($preview)
        <div x-show="previews.length > 0" x-cloak class="mt-4 space-y-2">
            <template x-for="(preview, index) in previews" :key="preview.uuid">
                <div
                    class="relative rounded-xl p-3 transition-all duration-200"
                    :class="{
                        'bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800': preview.status === 'error',
                        'bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800': preview.status === 'success',
                        'bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800': preview.status !== 'error' && preview.status !== 'success',
                    }">
                    <div class="flex items-center gap-3">
                        <template x-if="preview.type === 'image'">
                            <img :src="preview.url" :alt="preview.name"
                                class="w-11 h-11 rounded-lg object-cover shrink-0 ring-1 ring-neutral-200 dark:ring-neutral-700" />
                        </template>

                        <template x-if="preview.type === 'file'">
                            <div
                                class="w-11 h-11 rounded-lg flex items-center justify-center shrink-0"
                                :class="{
                                    'bg-red-100 dark:bg-red-900/50': preview.status === 'error',
                                    'bg-green-100 dark:bg-green-900/50': preview.status === 'success',
                                    'bg-neutral-100 dark:bg-neutral-800': preview.status !== 'error' && preview.status !== 'success',
                                }">
                                <span class="text-[10px] font-bold uppercase"
                                    :class="{
                                        'text-red-600 dark:text-red-400': preview.status === 'error',
                                        'text-green-600 dark:text-green-400': preview.status === 'success',
                                        'text-neutral-600 dark:text-neutral-400': preview.status !== 'error' && preview.status !== 'success',
                                    }"
                                    x-text="preview.extension"></span>
                            </div>
                        </template>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate"
                                x-text="preview.name"></p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <p class="text-xs text-neutral-500 dark:text-neutral-500" x-text="preview.size"></p>
                                <span class="text-neutral-300 dark:text-neutral-700">·</span>
                                <span class="text-xs font-medium"
                                    :class="{
                                        'text-primary-600 dark:text-primary-400': preview.status === 'uploading',
                                        'text-green-600 dark:text-green-400': preview.status === 'success',
                                        'text-red-600 dark:text-red-400': preview.status === 'error',
                                        'text-neutral-500 dark:text-neutral-500': preview.status === 'idle',
                                    }"
                                    x-text="preview.status === 'uploading' ? 'Uploading...' : (preview.status === 'success' ? 'Complete' : (preview.status === 'error' ? 'Failed' : 'Pending'))">
                                </span>
                            </div>

                            <template x-if="preview.status === 'uploading' || preview.status === 'idle'">
                                <div class="mt-2 h-1.5 w-full rounded-full bg-neutral-200 dark:bg-neutral-700 overflow-hidden">
                                    <div class="h-full bg-primary-500 dark:bg-primary-400 rounded-full transition-all duration-300 ease-out"
                                        :style="`width: ${preview.progress || 0}%`"></div>
                                </div>
                            </template>
                            
                            <template x-if="preview.status === 'error' && preview.error">
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400 truncate" x-text="preview.error"></p>
                            </template>
                        </div>

                        @if ($removable)
                            <button type="button" x-on:click.stop="removeFile(index)"
                                class="p-1.5 rounded-lg transition-colors shrink-0"
                                :class="{
                                    'text-red-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/50': preview.status === 'error',
                                    'text-green-400 hover:text-green-600 hover:bg-green-100 dark:hover:bg-green-900/50': preview.status === 'success',
                                    'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-200 dark:hover:bg-neutral-800': preview.status !== 'error' && preview.status !== 'success',
                                }"
                                x-bind:title="'Remove file'">
                                <neura::icon name="x-mark" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    @endif

    @if ($fieldName)
        <neura::error :name="$fieldName" />
    @endif
</div>
