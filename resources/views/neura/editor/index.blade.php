@props([
    'name' => null,
    'value' => null,
    'mode' => 'html',
    'placeholder' => null,
    'disabled' => false,
    'debounce' => 300,
    'uploadUrl' => null,
    'uploadField' => 'image',
    'pageSize' => null,
    'orientation' => null,
    'zoom' => 100,
    'paginated' => true,
    'menubar' => true,
    'toolbar' => true,
    'ruler' => true,
    'statusbar' => true,
    'documentTitle' => null,
    'header' => null,
    'footer' => null,
    'firstHeader' => null,
    'firstFooter' => null,
    'differentFirstPage' => false,
    'columns' => 1,
    'mentions' => [],
    'mentionsUrl' => null,
    'author' => null,
    'history' => null,
    'historyKey' => null,
    'versionsUrl' => null,
])

{{--
    Rich editor.

    A thin pass-through to the native editor. It used to dispatch between
    Tiptap, Editor.js and native variants; the other two are gone, so the only
    job left here is to keep `<neura::editor>` working as the public entry point
    while the implementation lives under `editor/variants/native`.

    Every prop the native editor understands — page size, header, footer, ruler,
    menubar and so on — is declared here and handed down by name: attributes
    forwarded as a bag keep their kebab-case keys (document-title), which the
    inner component's camelCase props never pick up.
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
    :pageSize="$pageSize"
    :orientation="$orientation"
    :zoom="$zoom"
    :paginated="$paginated"
    :menubar="$menubar"
    :toolbar="$toolbar"
    :ruler="$ruler"
    :statusbar="$statusbar"
    :documentTitle="$documentTitle"
    :header="$header"
    :footer="$footer"
    :firstHeader="$firstHeader"
    :firstFooter="$firstFooter"
    :differentFirstPage="$differentFirstPage"
    :columns="$columns"
    :mentions="$mentions"
    :mentionsUrl="$mentionsUrl"
    :author="$author"
    :history="$history"
    :historyKey="$historyKey"
    :versionsUrl="$versionsUrl"
    {{ $attributes }}
/>
