@props([
    'commands' => [],
    'placeholder' => neura_trans('search'),
    'shortcuts' => ['k'],
    'showResultsWithoutInput' => true,
])

@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Blade;

    $iconCache = [];

    $commands = collect($commands)->map(function ($command) use (&$iconCache) {
        $id = $command['id'] ?? (string) Str::uuid();
        $icon = $command['icon'] ?? null;

        if ($icon && ! isset($iconCache[$icon])) {
            try {
                $iconCache[$icon] = Blade::render(
                    '<neura::icon name="' . e($icon) . '" class="size-4 shrink-0"></neura::icon>'
                );
            } catch (\Throwable) {
                $iconCache[$icon] = '';
            }
        }

        return [
            'id'          => $id,
            'name'        => (string) ($command['name'] ?? ''),
            'description' => (string) ($command['description'] ?? ''),
            'iconHtml'    => $icon ? $iconCache[$icon] : '',
            'shortcut'    => $command['shortcut'] ?? null,
            'action'      => $command['action'] ?? null,
        ];
    })->values()->toArray();

    $shortcutBindings = collect($shortcuts)->map(
        fn ($key) => "@keydown.window.prevent.cmd.$key=\"toggleOpen()\" @keydown.window.prevent.ctrl.$key=\"toggleOpen()\""
    )->implode(' ');
@endphp

<div x-data="CommandSpotlight({
        placeholder: @js($placeholder),
        commands: @js($commands),
        showResultsWithoutInput: {{ $showResultsWithoutInput ? 'true' : 'false' }},
    })"
    x-init="init()"
    x-show="isOpen"
    x-cloak
    {!! $shortcutBindings !!}
    @keydown.window.escape="close()"
    @toggle-command.window="toggleOpen()"
    @command-close-all.window="close()"
    class="fixed inset-0 z-9999 flex items-start justify-center px-4 pt-20 sm:pt-28">
    <div x-show="isOpen" x-transition.opacity class="absolute inset-0 bg-neutral-900/40 dark:bg-black/60 backdrop-blur-sm" @click="close()"></div>

    <div
        x-show="isOpen"
        x-transition.scale.origin.top
        class="relative w-full max-w-[640px] rounded-xl overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-2xl">
        <div class="px-4 py-3 border-b border-neutral-100 dark:border-neutral-800">
            <neura::input
                x-ref="input"
                x-model="input"
                autocomplete="off"
                leftIcon="magnifying-glass"
                x-bind:placeholder="inputPlaceholder"
                @keydown.enter.prevent.stop="go()"
                @keydown.arrow-up.prevent="selectUp()"
                @keydown.arrow-down.prevent="selectDown()"
                class="border-0! shadow-none! bg-transparent! px-0 py-0 h-auto! focus:ring-0!"/>
        </div>

        <div x-show="filteredItems().length" class="max-h-96 overflow-y-auto py-2 px-1">
            <template x-for="(item, i) in filteredItems()" :key="item[0].item.id">
                <button
                    type="button"
                    @click="go(item[0].item.id)"
                    @mousemove="activeIndex = i"
                    :class="{
                        'bg-neutral-100 dark:bg-neutral-800': activeIndex === i,
                        'hover:bg-neutral-100 dark:hover:bg-neutral-800': activeIndex !== i
                    }"
                    class="w-full flex items-center gap-3 px-4 py-2.5 mb-1 rounded-md text-left text-sm transition">
                    <template x-if="item[0].item.iconHtml">
                        <span
                            class="text-neutral-500 dark:text-neutral-400"
                            x-html="item[0].item.iconHtml"
                        ></span>
                    </template>

                    <div class="flex-1 min-w-0">
                        <neura::text size="sm" class="font-medium truncate" x-text="item[0].item.name"></neura::text>
                        <neura::description class="truncate" x-text="item[0].item.description"></neura::description>
                    </div>

                    <template x-if="item[0].item.shortcut">
                        <div class="flex gap-1 ml-auto">
                            <template x-for="key in item[0].item.shortcut.split('+')">
                                <neura::kbd size="md" x-text="key.trim()"></neura::kbd>
                            </template>
                        </div>
                    </template>
                </button>
            </template>
        </div>

        <div x-show="!filteredItems().length && input.trim().length" class="px-4 py-12 text-center">
            <neura::text class="text-sm text-neutral-500 dark:text-neutral-400">
                {{ __('No results found') }}
            </neura::text>
        </div>
    </div>
</div>
