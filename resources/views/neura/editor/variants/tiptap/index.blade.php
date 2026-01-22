@props([
    'name' => null,
    'value' => null,
    'mode' => 'html',
    'placeholder' => 'Write something...',
    'disabled' => false,
    'debounce' => 300,
])

@php
    $wireModel = $attributes->wire('model');
    $entangle = $wireModel->value();
    $initialValue = $mode === 'json' && is_array($value) ? json_encode($value) : $value;
@endphp

<div
    @if($entangle)
        x-data="tiptapEditor({
            state: @entangle($entangle).live,
            placeholder: @js($placeholder),
            editable: @js(!$disabled),
            mode: @js($mode),
            debounce: @js($debounce)
        })"
    @else
        x-data="tiptapEditor({
            state: @js($initialValue),
            placeholder: @js($placeholder),
            editable: @js(!$disabled),
            mode: @js($mode),
            debounce: @js($debounce)
        })"
    @endif
    x-init="init()"
    {{ $attributes->whereDoesntStartWith(['wire:model', 'class']) }}
    class="relative w-full border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 rounded-lg shadow-sm overflow-hidden"
    wire:ignore
>
    <style>
        .ProseMirror h1 { font-size: 2em; font-weight: 700; margin: 0.67em 0; line-height: 1.2; }
        .ProseMirror h2 { font-size: 1.5em; font-weight: 600; margin: 0.75em 0; line-height: 1.3; }
        .ProseMirror h3 { font-size: 1.25em; font-weight: 600; margin: 0.83em 0; line-height: 1.4; }
        .ProseMirror ul { list-style-type: disc; padding-left: 1.5em; margin: 1em 0; }
        .ProseMirror ol { list-style-type: decimal; padding-left: 1.5em; margin: 1em 0; }
        .ProseMirror li { margin: 0.25em 0; }
        .ProseMirror li p { margin: 0; }
        .ProseMirror blockquote { border-left: 3px solid #d1d5db; padding-left: 1em; margin: 1em 0; color: #6b7280; font-style: italic; }
        .dark .ProseMirror blockquote { border-left-color: #4b5563; color: #9ca3af; }
        .ProseMirror hr { border: none; border-top: 1px solid #d1d5db; margin: 1.5em 0; }
        .dark .ProseMirror hr { border-top-color: #4b5563; }
        .ProseMirror code { background: #f3f4f6; padding: 0.15em 0.3em; border-radius: 0.25em; font-size: 0.875em; font-family: ui-monospace, monospace; }
        .dark .ProseMirror code { background: #374151; }
        .ProseMirror p.is-editor-empty:first-child::before { content: attr(data-placeholder); color: #9ca3af; pointer-events: none; float: left; height: 0; }
    </style>

    @unless($disabled)
        <neura::editor.variants.tiptap.toolbar />
    @endunless

    <div x-ref="editor" class="min-h-[150px] cursor-text"></div>
    
    @if($name)
        <input
            type="hidden"
            name="{{ $name }}"
            :value="mode === 'json' ? JSON.stringify(state) : state"
        >
    @endif
</div>
