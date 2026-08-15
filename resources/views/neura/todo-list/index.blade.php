@props([
    /**
     * string[] or list of { label, status?: pending|active|done }
     * @var list<string|array{label: string, status?: string}>|null
     */
    'items' => [],
    'title' => null,
    'current' => null,
    'autoPlay' => false,
    'startDelay' => 700,
    'stepMs' => 2250,
    'collapsed' => false,
    'done' => false,
])

@php
    use Illuminate\Support\Js;

    $normalized = collect($items ?? [])
        ->map(function ($item) {
            if (is_string($item)) {
                return ['label' => $item, 'status' => 'pending'];
            }
            $label = data_get($item, 'label');
            if (! filled($label)) {
                return null;
            }
            $status = data_get($item, 'status', 'pending');
            if (! in_array($status, ['pending', 'active', 'done'], true)) {
                $status = 'pending';
            }

            return ['label' => (string) $label, 'status' => $status];
        })
        ->filter()
        ->values()
        ->all();

    $heading = $title ?? neura_trans('todos');
    $config = [
        'items' => $normalized,
        'current' => $current,
        'autoPlay' => (bool) $autoPlay,
        'startDelay' => (int) $startDelay,
        'stepMs' => (int) $stepMs,
        'collapsed' => (bool) $collapsed,
        'done' => (bool) $done,
    ];
@endphp

<div
    {{ $attributes->class(['nk-todo'])->merge([
        'data-slot' => 'todo-list',
    ]) }}
    x-data="neuraTodoList({{ Js::from($config) }})"
    :class="collapsed && 'nk-todo-collapsed-card'"
>
    <button
        type="button"
        class="nk-todo-head"
        :aria-expanded="(!collapsed).toString()"
        aria-label="{{ neura_trans('toggleTodos') }}"
        @click="toggle()"
    >
        <span class="nk-todo-head-icon">
            <template x-if="allDone">
                <svg class="nk-todo-head-check" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                        fill="currentColor"
                    />
                </svg>
            </template>
            <template x-if="running">
                <span class="nk-todo-head-pie" :style="'--todo-pie:' + pct + '%'" aria-hidden="true">
                    <svg class="nk-todo-head-pie-ring" viewBox="0 0 24 24">
                        <circle
                            cx="12"
                            cy="12"
                            r="10.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-dasharray="2.2 4.4"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>
            </template>
            <template x-if="!started">
                <svg class="nk-todo-list-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                    <path
                        d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <svg class="nk-todo-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                <path
                    d="m19.5 8.25-7.5 7.5-7.5-7.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </span>
        <span class="nk-todo-title">{{ $heading }}</span>
        <span class="nk-todo-count">
            <span class="nk-roll-count" x-text="countLabel"></span>
        </span>
    </button>

    <div class="nk-todo-collapsible" :class="collapsed && 'nk-todo-collapsed'">
        <div class="nk-todo-inner">
            <ul class="nk-todo-list">
                <template x-for="(label, i) in items" :key="i">
                    <li
                        class="nk-todo-item"
                        :class="itemDone(i) ? 'nk-todo-done' : (itemActive(i) ? 'nk-todo-active' : '')"
                        :style="'--todo-i:' + i"
                    >
                        <span class="nk-todo-icon-wrap" aria-hidden="true">
                            <svg
                                class="nk-todo-icon"
                                :class="!itemDone(i) && !itemActive(i) && 'nk-todo-icon-on'"
                                viewBox="0 0 24 24"
                                width="16"
                                height="16"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-dasharray="1.8 3.6"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <svg
                                class="nk-todo-icon nk-todo-icon-strong"
                                :class="itemActive(i) && 'nk-todo-icon-on'"
                                viewBox="0 0 24 24"
                                width="16"
                                height="16"
                            >
                                <path
                                    d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <svg
                                class="nk-todo-icon"
                                :class="itemDone(i) && 'nk-todo-icon-on'"
                                viewBox="0 0 24 24"
                                width="16"
                                height="16"
                            >
                                <path
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <span class="nk-todo-label" :data-label="label" x-text="label"></span>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
