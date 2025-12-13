@php

$colorClasses = match($tagColor){
    default=> "text-neutral-900 dark:text-neutral-50 bg-neutral-900/15 dark:bg-white/5 border-black/10 dark:border-white/10",
    'red' => 'text-red-700 dark:text-red-200 bg-red-400/15 dark:bg-red-400/5 border-red-400 dark:border-red-400/90',
    'orange' => 'text-orange-700 dark:text-orange-200 bg-orange-400/15 dark:bg-orange-400/5 border-orange-400 dark:border-orange-400/90',
    'amber' => 'text-amber-700 dark:text-amber-200 bg-amber-400/15 dark:bg-amber-400/5 border-amber-400 dark:border-amber-400/90',
    'yellow' => 'text-yellow-800 dark:text-yellow-200 bg-yellow-400/15 dark:bg-yellow-400/5 border-yellow-400 dark:border-yellow-400/90',
    'lime' => 'text-lime-800 dark:text-lime-200 bg-lime-400/15 dark:bg-lime-400/5 border-lime-400 dark:border-lime-400/90',
    'green' => 'text-green-800 dark:text-green-200 bg-green-400/15 dark:bg-green-400/5 border-green-400 dark:border-green-400/90',
    'emerald' => 'text-emerald-800 dark:text-emerald-200 bg-emerald-400/15 dark:bg-emerald-400/5 border-emerald-400 dark:border-emerald-400/90',
    'teal' => 'text-teal-800 dark:text-teal-200 bg-teal-400/15 dark:bg-teal-400/5 border-teal-400 dark:border-teal-400/90',
    'cyan' => 'text-cyan-800 dark:text-cyan-200 bg-cyan-400/15 dark:bg-cyan-400/5 border-cyan-400 dark:border-cyan-400/90',
    'sky' => 'text-sky-800 dark:text-sky-200 bg-sky-400/15 dark:bg-sky-400/5 border-sky-400 dark:border-sky-400/90',
    'blue' => 'text-blue-800 dark:text-blue-200 bg-blue-400/15 dark:bg-blue-400/5 border-blue-400 dark:border-blue-400/90',
    'indigo' => 'text-indigo-700 dark:text-indigo-200 bg-indigo-400/15 dark:bg-indigo-400/5 border-indigo-400 dark:border-indigo-400/90',
    'violet' => 'text-violet-700 dark:text-violet-200 bg-violet-400/15 dark:bg-violet-400/5 border-violet-400 dark:border-violet-400/90',
    'purple' => 'text-purple-700 dark:text-purple-200 bg-purple-400/15 dark:bg-purple-400/5 border-purple-400 dark:border-purple-400/90',
    'fuchsia' => 'text-fuchsia-700 dark:text-fuchsia-200 bg-fuchsia-400/15 dark:bg-fuchsia-400/5 border-fuchsia-400 dark:border-fuchsia-400/90',
    'pink' => 'text-pink-700 dark:text-pink-200 bg-pink-400/15 dark:bg-pink-400/5 border-pink-400 dark:border-pink-400/90',
    'rose' => 'text-rose-700 dark:text-rose-200 bg-rose-400/15 dark:bg-rose-400/5 border-rose-400 dark:border-rose-400/90',
};

$variantClasses = match($tagVariant){
    'rounded'=> ' rounded-field  border',
    'pill'=> 'rounded-full border '
};

$classes = [
    'px-2.5 py-0.5 text-xs font-medium',
    $variantClasses,
    $colorClasses
];

@endphp

<div
    @class(Arr::toCssClasses($classes))
    draggable="true"
    x-on:dragstart="onDragStart(index)"
    x-on:dragover.prevent=""
    x-bind:class="{ 'opacity-50': dragIndex === index }"
    x-on:drop="onDrop(event,index)"
>
    <span x-text="tag" class="select-none text-start"></span>
    <button
        type="button"
        x-on:click="deleteTag(index)"
        class="ml-1 cursor-pointer text-current hover:text-red-500 transition"
    >
        &times;
    </button>
</div>