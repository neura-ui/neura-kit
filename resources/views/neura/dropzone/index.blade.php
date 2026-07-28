@props([
    'name' => null,
    'accept' => 'image/*',
    'maxSize' => 10,
    'maxFiles' => null,
    'multiple' => false,
    'preview' => true,
    'previewMode' => null,
    'removable' => true,
    'label' => null,
    'description' => null,
    'text' => null,
    'hint' => null,
    'icon' => 'arrow-up-tray',
    'invalid' => null,
    'disabled' => false,
    'uploadUrl' => null,
    'chunkSize' => 1,
    'uploadHeaders' => [],
    'concurrency' => 2,
    'autoUpload' => true,
    'notify' => false,
    'size' => null,
    'rounded' => null,
    'shadow' => null,
])

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use Neura\Kit\Support\PackResolver;

    $sizes = PackResolver::dropzoneSize($size);
    $colors = PackResolver::dropzoneColor();

    $roundedClass = PackResolver::rounded($rounded ?: neura_config('dropzone', 'rounded'));
    $shadowClass = PackResolver::shadow($shadow ?: neura_config('dropzone', 'shadow'));

    $disabled = filled($disabled) && $disabled;
    $multiple = filled($multiple) && $multiple;

    $maxSizeBytes = (int) round(((float) $maxSize) * 1024 * 1024);
    $chunkSizeBytes = (int) round(((float) $chunkSize) * 1024 * 1024);
    $maxFiles = $multiple ? ($maxFiles !== null ? max(1, (int) $maxFiles) : null) : 1;

    // wire:model — keep the modifier so the model is pushed live only when asked to.
    $wireModelAttributes = $attributes->whereStartsWith('wire:model')->getAttributes();
    $wireModelKey = array_key_first($wireModelAttributes);
    $wireModelValue = $wireModelKey ? $wireModelAttributes[$wireModelKey] : null;
    $wireModelLive = $wireModelKey !== null && str_contains($wireModelKey, '.live');

    $resolvedUploadUrl = $uploadUrl ?? route('neura-kit.upload.chunks');
    $fieldName = $name ?? $wireModelValue;

    $hasFieldError = $fieldName
        && collect($errors->keys())->contains(
            fn ($key) => $key === $fieldName || str_starts_with($key, $fieldName.'.')
        );

    $isInvalid = $invalid ?? $hasFieldError;

    // Human readable summary of the accepted types.
    $acceptLabel = match (true) {
        blank($accept), $accept === '*', $accept === '*/*' => neura_trans('allFiles'),
        $accept === 'image/*' => neura_trans('imagesOnly'),
        default => collect(explode(',', (string) $accept))
            ->map(fn ($rule) => trim($rule))
            ->filter()
            ->map(fn ($rule) => match (true) {
                str_starts_with($rule, '.') => Str::upper(ltrim($rule, '.')),
                $rule === 'image/*' => neura_trans('images'),
                $rule === 'video/*' => neura_trans('videos'),
                $rule === 'audio/*' => neura_trans('audioFiles'),
                default => Str::upper(Str::afterLast($rule, '/')),
            })
            ->unique()
            ->take(6)
            ->implode(', '),
    };

    $hintText = $hint ?? collect([
        $acceptLabel,
        neura_trans('max').' '.$maxSize.' MB',
        $maxFiles && $multiple ? neura_trans('upToNFiles', ['count' => $maxFiles]) : null,
    ])->filter()->implode(' · ');

    // Images uploaded in bulk read much better as a gallery than as a list.
    $previewMode = $previewMode ?: ($multiple && str_starts_with((string) $accept, 'image/') ? 'grid' : 'list');

    $areaClasses = Arr::toCssClasses([
        'group/dz relative flex w-full flex-col items-center justify-center text-center',
        'border-2 border-dashed outline-none',
        'transition-[background-color,border-color,box-shadow,transform] duration-200 motion-reduce:transition-none',
        $roundedClass,
        $shadowClass,
        $sizes['area'],
        $colors['area']['base'] => ! $disabled,
        $colors['area']['interactive'] => ! $disabled,
        $colors['area']['focus'] => ! $disabled,
        $colors['area']['dragging'] => ! $disabled,
        $colors['area']['invalid'] => ! $disabled,
        $colors['area']['disabled'] => $disabled,
    ]);

    $tileClasses = Arr::toCssClasses([
        'flex shrink-0 items-center justify-center transition-all duration-200 motion-reduce:transition-none',
        $sizes['tile'],
        $colors['tile']['base'],
        $colors['tile']['hover'] => ! $disabled,
        $colors['tile']['dragging'] => ! $disabled,
        $colors['tile']['invalid'] => ! $disabled,
    ]);

    $previewProps = [
        'sizes' => $sizes,
        'colors' => $colors,
        'rounded' => $roundedClass,
        'removable' => $removable && ! $disabled,
    ];

    $uid = 'nk-dz-'.Str::random(8);
@endphp

<div
    data-nk-dropzone
    x-data="neuraDropzone({
        accept: @js($accept),
        maxSizeBytes: @js($maxSizeBytes),
        maxFiles: @js($maxFiles),
        multiple: @js($multiple),
        chunkSize: @js($chunkSizeBytes),
        uploadUrl: @js($resolvedUploadUrl),
        uploadHeaders: @js(array_merge(['X-CSRF-TOKEN' => csrf_token()], $uploadHeaders)),
        name: @js($fieldName),
        invalid: @js($isInvalid),
        wireModel: @js($wireModelValue),
        wireModelLive: @js($wireModelLive),
        previewEnabled: @js((bool) $preview),
        removable: @js((bool) $removable),
        disabled: @js($disabled),
        autoUpload: @js((bool) $autoUpload),
        notify: @js((bool) $notify),
        concurrency: @js((int) $concurrency),
    })"
    x-on:dragenter.prevent="handleDragEnter($event)"
    x-on:dragover.prevent="handleDragOver($event)"
    x-on:dragleave.prevent="handleDragLeave()"
    x-on:drop.prevent="handleDrop($event)"
    x-on:paste="handlePaste($event)"
    :aria-busy="isUploading"
    {{ $attributes->whereDoesntStartWith('wire:model')->class('w-full') }}
>
    {{-- Server rendered validation state, watched by the component so Livewire
         re-renders keep the visual state in sync without polling. --}}
    <span x-ref="validity" data-invalid="{{ $isInvalid ? '1' : '0' }}" hidden></span>

    @if ($label)
        <neura::label :text="$label" class="mb-2" />
    @endif

    @if ($description)
        <p class="{{ $sizes['meta'] }} text-fg-secondary mb-3">{{ $description }}</p>
    @endif

    <label data-slot="dropzone-area" :data-state="state" class="{{ $areaClasses }}">
        <input
            x-ref="fileInput"
            type="file"
            class="sr-only"
            accept="{{ $accept }}"
            @if ($multiple) multiple @endif
            @if ($disabled) disabled @endif
            aria-label="{{ $label ?? neura_trans('selectFiles') }}"
            aria-describedby="{{ $uid }}-hint"
            x-on:change="handleFileSelect($event)"
            x-on:click.stop
        />

        <span class="{{ $tileClasses }}" aria-hidden="true">
            <neura::icon
                :name="$icon"
                class="{{ $sizes['glyph'] }} transition-transform duration-200 group-data-[state=dragging]/dz:-translate-y-0.5 motion-reduce:transition-none"
            />
        </span>

        <span class="flex flex-col gap-1">
            <span class="{{ $sizes['text'] }} font-medium transition-colors {{ $colors['text']['title'] }}">
                <span x-show="!isDragging">
                    {{ $text ?? neura_trans('dragAndDrop').' '.($multiple ? neura_trans('files') : neura_trans('aFile')).' '.neura_trans('hereOr') }}
                    @unless ($text)
                        <span class="{{ $colors['text']['action'] }} underline underline-offset-2">{{ neura_trans('browse') }}</span>
                    @endunless
                </span>
                <span x-show="isDragging" x-cloak class="{{ $colors['text']['action'] }}">
                    {{ neura_trans('dropToUpload') }}
                </span>
            </span>

            <span id="{{ $uid }}-hint" class="{{ $sizes['hint'] }} {{ $colors['text']['hint'] }}">{{ $hintText }}</span>
        </span>
    </label>

    {{-- Hidden inputs mirroring the uploaded payloads for classic form submits. --}}
    <div x-ref="hiddenFields" hidden></div>

    {{-- Files rejected client side: shown in place instead of a transient toast. --}}
    <ul
        x-show="rejections.length > 0"
        x-cloak
        class="mt-3 space-y-1.5"
        data-slot="dropzone-rejections"
    >
        <template x-for="rejection in rejections" :key="rejection.id">
            <li class="flex items-start gap-2 rounded-lg border px-3 py-2 {{ $sizes['meta'] }} {{ $colors['rejection'] }}">
                <neura::icon name="exclamation-circle" class="size-4 shrink-0 mt-px" />
                <span class="flex-1 text-start" x-text="rejection.message"></span>
                <button
                    type="button"
                    class="shrink-0 rounded transition-opacity opacity-60 hover:opacity-100"
                    x-on:click="dismissRejection(rejection.id)"
                    :aria-label="@js(neura_trans('close'))"
                >
                    <neura::icon name="x-mark" class="size-3.5" />
                </button>
            </li>
        </template>
    </ul>

    @if ($preview)
        <div x-show="previews.length > 0" x-cloak class="mt-3" data-slot="dropzone-previews">
            @if ($multiple)
                <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="{{ $sizes['meta'] }} text-fg-muted">
                        <span x-text="previews.length"></span>
                        <span x-show="previews.length > 1">{{ neura_trans('files') }}</span>
                        <span x-show="previews.length === 1">{{ neura_trans('aFile') }}</span>
                        <span aria-hidden="true"> · </span>
                        <span x-text="totalSize"></span>
                        <template x-if="isUploading">
                            <span><span aria-hidden="true"> · </span><span x-text="overallProgress + '%'"></span></span>
                        </template>
                    </p>

                    @if ($removable && ! $disabled)
                        <button
                            type="button"
                            class="{{ $sizes['meta'] }} rounded px-1 font-medium text-fg-muted transition-colors hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                            x-on:click="clearAll()"
                        >
                            {{ neura_trans('clearAll') }}
                        </button>
                    @endif
                </div>
            @endif

            @if ($previewMode === 'grid')
                <ul class="grid {{ $sizes['grid'] }}" role="list">
                    <template x-for="item in previews" :key="item.uuid">
                        <neura::dropzone.tile :sizes="$previewProps['sizes']" :colors="$previewProps['colors']" :rounded="$previewProps['rounded']" :removable="$previewProps['removable']" />
                    </template>
                </ul>
            @else
                <ul class="space-y-2" role="list">
                    <template x-for="item in previews" :key="item.uuid">
                        <neura::dropzone.item :sizes="$previewProps['sizes']" :colors="$previewProps['colors']" :rounded="$previewProps['rounded']" :removable="$previewProps['removable']" />
                    </template>
                </ul>
            @endif
        </div>
    @endif

    <span class="sr-only" aria-live="polite" x-text="announcement"></span>
</div>
