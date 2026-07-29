@props([
    'name' => null,
    'value' => null,
    'mode' => 'html',
    'placeholder' => 'Start writing…',
    'disabled' => false,
    'debounce' => 300,
    'uploadUrl' => null,
    'uploadField' => 'image',
])

{{--
    Rich editor.

    A thin pass-through to the native editor. It used to dispatch between
    Tiptap, Editor.js and native variants; the other two are gone, so the only
    job left here is to keep `<neura::editor>` working as the public entry point
    while the implementation lives under `editor/variants/native`.

    Every prop the native editor understands — page size, header, footer, ruler,
    menubar and so on — passes straight through, so nothing has to be repeated.
--}}

<neura::editor.variants.native.index
    :name="$name"
    :value="$value"
    :mode="$mode"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :debounce="$debounce"
    :uploadUrl="$uploadUrl"
    :uploadField="$uploadField"
    {{ $attributes }}
/>
