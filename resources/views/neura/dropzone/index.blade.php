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
        class="relative border-2 border-dashed rounded-xl p-8 transition-all duration-150 cursor-pointer"
        :class="{
            'border-red-500 dark:border-red-400 bg-red-50/50 dark:bg-red-950/30': hasError || invalid,
            'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950/50 shadow-sm': isDragging && !hasError && !invalid,
            'border-neutral-300 dark:border-neutral-700 hover:border-primary-400 dark:hover:border-primary-600 hover:bg-primary-50/50 dark:hover:bg-primary-950/30': !isDragging && !hasError && !invalid
        }">
        <input x-ref="fileInput" type="file" :accept="accept" :multiple="multiple"
            x-on:change="handleFileSelect" class="hidden"
            @if ($fieldName && !$hasWireModel) name="{{ $fieldName }}" @endif
            @if ($hasWireModel) {{ $attributes->wire('model') }} @endif />

        <div class="flex flex-col items-center justify-center text-center space-y-3">
            <div
                class="w-12 h-12 rounded-full flex items-center justify-center shadow-inner transition-colors"
                :class="{
                    'bg-red-100 dark:bg-red-900/30': hasError || invalid,
                    'bg-neutral-100 dark:bg-neutral-800': !hasError && !invalid
                }">
                <neura::icon name="arrow-up-tray" 
                    class="w-6 h-6 transition-colors"
                    x-bind:class="{
                        'text-red-500 dark:text-red-400': hasError || invalid,
                        'text-neutral-600 dark:text-neutral-400': !hasError && !invalid
                    }" />
            </div>

            <div>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    <span class="text-neutral-600 dark:text-neutral-400">{{ neura_trans('dragAndDrop') }}
                        {{ $multiple ? neura_trans('files') : neura_trans('aFile') }} {{ neura_trans('hereOr') }}</span>
                    <span class="text-neutral-900 dark:text-neutral-100 underline">{{ neura_trans('browse') }}</span>
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1">
                    {{ $accept === 'image/*' ? neura_trans('imagesOnly') : neura_trans('allFiles') }} ·
                    {{ neura_trans('max') }} {{ $maxSize }}MB
                    @if ($uploadUrl)
                        · upload chunk {{ $chunkSize }}MB
                    @endif
                </p>
            </div>
        </div>

        <div class="absolute inset-0 pointer-events-none" x-show="isDragging" x-transition>
            <div
                class="h-full w-full rounded-xl border-2 border-primary-400/60 dark:border-primary-500/70 bg-primary-50/40 dark:bg-primary-900/30">
            </div>
        </div>
    </div>

    @if ($preview)
        <div x-show="previews.length > 0" x-cloak class="mt-4 space-y-3">
            <template x-for="(preview, index) in previews" :key="preview.uuid">
                <div
                    class="relative border border-neutral-200 dark:border-neutral-800 rounded-lg p-3 bg-white dark:bg-neutral-950 shadow-sm">
                    <div class="flex items-center gap-3">
                        <template x-if="preview.type === 'image'">
                            <img :src="preview.url" :alt="preview.name"
                                class="w-12 h-12 rounded object-cover shrink-0 ring-1 ring-neutral-200 dark:ring-neutral-700" />
                        </template>

                        <template x-if="preview.type === 'file'">
                            <div
                                class="w-12 h-12 rounded bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center shrink-0">
                                <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                                    x-text="preview.extension"></span>
                            </div>
                        </template>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate"
                                x-text="preview.name"></p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-500" x-text="preview.size"></p>

                            <div class="mt-2 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] px-2 py-0.5 rounded-full"
                                        :class="{
                                            'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-200': preview
                                                .status === 'uploading',
                                            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200': preview
                                                .status === 'success',
                                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200': preview
                                                .status === 'error',
                                            'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200': preview
                                                .status === 'idle',
                                        }"
                                        x-text="preview.status === 'uploading' ? 'Upload…' : (preview.status === 'success' ? 'Terminé' : (preview.status === 'error' ? 'Erreur' : 'En attente'))">
                                    </span>
                                    <template x-if="preview.status === 'error'">
                                        <span class="text-xs text-red-500" x-text="preview.error"></span>
                                    </template>
                                </div>
                                <template x-if="preview.status === 'uploading' || preview.status === 'idle'">
                                    <div
                                        class="h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                                        <div class="h-full bg-primary-500 dark:bg-primary-400 transition-all duration-200"
                                            :style="`width: ${preview.progress || 0}%`"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        @if ($removable)
                            <button type="button" x-on:click.stop="removeFile(index)"
                                class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-md transition-colors shrink-0"
                                x-bind:title="window.t?.('removeFile') ?? 'Remove file'">
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
