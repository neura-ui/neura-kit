@props([
    'name' => null,
    'value' => null,
    'placeholder' => 'Write something...',
    'disabled' => false,
    'uploadUrl' => null,
    'uploadField' => 'image',
])

@php
    $wireModel = $attributes->wire('model');
    $entangle = $wireModel->value();
    $initialValue = is_array($value) ? json_encode($value) : $value;
    $uploadUrl = $uploadUrl ?? route('neura-kit.editor.upload-image');
@endphp

<div
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
    class="relative w-full border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 rounded-lg shadow-sm overflow-hidden px-8"
    wire:ignore
>
    <style>
        /* Editor.js Core Styles */
        .codex-editor {
            min-height: 200px;
        }
        .codex-editor__redactor {
            padding: 1rem;
            min-height: 150px;
        }
        .codex-editor .ce-block__content {
            max-width: 100%;
        }
        .codex-editor .ce-toolbar__content {
            max-width: 100%;
        }
        .codex-editor .ce-inline-toolbar {
            max-width: 100%;
        }
        .codex-editor .ce-conversion-toolbar {
            max-width: 100%;
        }
        .codex-editor .ce-settings {
            max-width: 100%;
        }
        .codex-editor .ce-popover {
            max-width: 100%;
        }
        
        /* Toolbox Styles - CRITICAL for visibility */
        .ce-toolbox {
            position: absolute;
            left: 0;
            z-index: 10;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
        }
        
        .ce-toolbox__button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 4px;
            cursor: pointer;
            background: transparent;
            border: 1px solid rgba(201, 201, 204, 0.48);
            transition: background-color 0.1s ease;
        }
        
        .ce-toolbox__button:hover {
            background: rgba(232, 232, 235, 0.49);
        }
        
        .ce-toolbox__button svg {
            width: 20px;
            height: 20px;
        }
        
        /* Plus icon visibility */
        .ce-toolbar__plus {
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            cursor: pointer;
            opacity: 1 !important;
        }
        
        .ce-toolbar__plus svg {
            width: 16px;
            height: 16px;
        }
        
        /* Ensure toolbar is visible */
        .ce-toolbar {
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .ce-toolbar--opened {
            display: block !important;
        }
        
        /* Dark mode support */
        .dark .codex-editor {
            color: #e5e7eb;
        }
        .dark .ce-block__content {
            color: #e5e7eb;
        }
        .dark .ce-paragraph {
            color: #e5e7eb;
        }
        .dark .ce-header {
            color: #e5e7eb;
        }
        .dark .ce-toolbox__button {
            border-color: rgba(255, 255, 255, 0.2);
            color: #e5e7eb;
        }
        .dark .ce-toolbox__button:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .dark .ce-toolbar__plus {
            color: #e5e7eb;
        }
        .dark .ce-popover {
            background: #1f2937;
            border-color: rgba(255, 255, 255, 0.1);
            color: #e5e7eb;
        }
        .dark .ce-popover-item {
            color: #e5e7eb;
        }
        .dark .ce-popover-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>

    <div x-ref="editor" class="min-h-[150px]"></div>
    
    @if($name)
        <input
            type="hidden"
            name="{{ $name }}"
            :value="JSON.stringify(state)"
        >
    @endif
</div>
