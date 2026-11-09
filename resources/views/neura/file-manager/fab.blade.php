{{-- Narrow: one "+" for a new folder or an upload. --}}
@props([
    'creatable' => true,
    'uploadable' => true,
    'inlineCreate' => true,
    'uploadPicker' => true,
])

<div data-fm-fab class="nk-fm-fab" x-data="{ open: false }" x-show="!touchSelect" x-on:keydown.escape.stop="open = false" x-on:click.outside="open = false">
    <div class="absolute bottom-full end-0 mb-2 flex w-48 flex-col rounded-xl border border-edge bg-surface p-1 shadow-lg" x-show="open" x-cloak role="menu">
        @if ($creatable)
            <button type="button" role="menuitem" class="flex h-11 items-center gap-2.5 rounded-lg px-3 text-sm hover:bg-hover"
                x-on:click="open = false; {{ $inlineCreate ? 'startCreate()' : '$dispatch(\'file-manager:new-folder\', { path: [...path] })' }}">
                <neura::icon name="folder-plus" class="size-4" /> {{ neura_trans('newFolder') }}
            </button>
        @endif
        @if ($uploadable)
            <button type="button" role="menuitem" class="flex h-11 items-center gap-2.5 rounded-lg px-3 text-sm hover:bg-hover"
                x-on:click="open = false; {{ $uploadPicker ? 'pickFiles()' : '$dispatch(\'file-manager:upload-request\', { path: [...path] })' }}">
                <neura::icon name="arrow-up-tray" class="size-4" /> {{ neura_trans('upload') }}
            </button>
        @endif
    </div>
    <button type="button" class="nk-fm-fab-button" x-on:click="open = !open" :aria-expanded="open.toString()" aria-label="{{ neura_trans('add') }}">
        <neura::icon name="plus" class="size-6" />
    </button>
</div>
