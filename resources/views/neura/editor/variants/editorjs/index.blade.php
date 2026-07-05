@props([
    'name' => null,
    'value' => null,
    'placeholder' => 'Write something...',
    'disabled' => false,
    'uploadUrl' => null,
    'uploadField' => 'image',
])

@php
    use Neura\Kit\Support\PackResolver;

    $wireModel = $attributes->wire('model');
    $entangle = $wireModel->value();
    $initialValue = is_array($value) ? json_encode($value) : $value;
    $uploadUrl = $uploadUrl ?? route('neura-kit.editor.upload-image');
    $editorRoundedClass = PackResolver::rounded(neura_config('rich-editor', 'rounded'));
    $editorShadowClass = PackResolver::shadow(neura_config('rich-editor', 'shadow'));
@endphp

<div
    data-nk-editor
    @if($entangle)
        x-data="editorjsEditor({
            state: @entangle($entangle).live,
            placeholder: @js($placeholder),
            editable: @js(!$disabled),
            uploadUrl: @js($uploadUrl),
            uploadField: @js($uploadField),
            uploadHeaders: {}
        })"
    @else
        x-data="editorjsEditor({
            state: @js($initialValue),
            placeholder: @js($placeholder),
            editable: @js(!$disabled),
            uploadUrl: @js($uploadUrl),
            uploadField: @js($uploadField),
            uploadHeaders: {}
        })"
    @endif
    x-init="init()"
    {{ $attributes->whereDoesntStartWith(['wire:model', 'class']) }}
    @class([
        'neura-editor-container relative w-full border border-edge bg-surface overflow-hidden transition-shadow duration-200',
        $editorRoundedClass,
        $editorShadowClass,
    ])
    wire:ignore
>
    <div x-ref="editor" class="neura-editor-content min-h-[200px] px-12 py-6"></div>
    
    @if($name)
        <input
            type="hidden"
            name="{{ $name }}"
            :value="JSON.stringify(state)"
        >
    @endif
</div>
