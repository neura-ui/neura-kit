@props([
    'name' => null,
    'value' => null,
    'variant' => null,
    'mode' => 'html',
    'placeholder' => 'Write something...',
    'disabled' => false,
    'debounce' => 300,
    'uploadUrl' => null,
    'uploadField' => 'image',
])

@php
    // Determine variant: use prop, config default, or fallback to 'tiptap'
    $variant = $variant ?? config('neura-kit.editor.default_variant', 'tiptap');
    
    // Validate variant
    if (!in_array($variant, ['tiptap', 'editorjs'])) {
        $variant = 'tiptap';
    }
    
    // Editor.js only supports JSON mode
    if ($variant === 'editorjs') {
        $mode = 'json';
    }
    
    $wireModel = $attributes->wire('model');
    $entangle = $wireModel->value();
    $initialValue = $mode === 'json' && is_array($value) ? json_encode($value) : $value;
@endphp

@if($variant === 'tiptap')
    <neura::editor.variants.tiptap.index
        :name="$name"
        :value="$value"
        :mode="$mode"
        :placeholder="$placeholder"
        :disabled="$disabled"
        :debounce="$debounce"
        {{ $attributes->whereDoesntStartWith(['wire:model', 'variant', 'mode', 'placeholder', 'disabled', 'debounce', 'uploadUrl', 'uploadField']) }}
    />
@elseif($variant === 'editorjs')
    <neura::editor.variants.editorjs.index
        :name="$name"
        :value="$value"
        :placeholder="$placeholder"
        :disabled="$disabled"
        :uploadUrl="$uploadUrl"
        :uploadField="$uploadField"
        {{ $attributes->whereDoesntStartWith(['wire:model', 'variant', 'mode', 'placeholder', 'disabled', 'debounce', 'uploadUrl', 'uploadField']) }}
    />
@endif
