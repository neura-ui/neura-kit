<div class="flex flex-wrap items-center gap-1 p-2 border-b border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 sticky top-0 z-10">
    <div class="flex items-center gap-1 mr-2">
        <button
            type="button"
            x-on:click.prevent="undo()"
            class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
            :disabled="!canUndo()"
        >
            <neura::icon name="arrow-uturn-left" class="size-4" />
            <span class="sr-only">Undo</span>
        </button>
        <button
            type="button"
            x-on:click.prevent="redo()"
            class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
            :disabled="!canRedo()"
        >
            <neura::icon name="arrow-uturn-right" class="size-4" />
            <span class="sr-only">Redo</span>
        </button>
    </div>

    <div class="w-px h-5 bg-neutral-200 dark:border-neutral-700 mx-1"></div>

    <button
        type="button"
        x-on:click.prevent="toggleBold()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('bold') }"
    >
        <neura::icon name="bold" class="size-4" />
        <span class="sr-only">Bold</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleItalic()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('italic') }"
    >
        <neura::icon name="italic" class="size-4" />
        <span class="sr-only">Italic</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleUnderline()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('underline') }"
    >
        <neura::icon name="underline" class="size-4" />
        <span class="sr-only">Underline</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleStrike()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('strike') }"
    >
        <neura::icon name="strikethrough" class="size-4" />
        <span class="sr-only">Strike</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleCode()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('code') }"
    >
        <neura::icon name="code-bracket" class="size-4" />
        <span class="sr-only">Code</span>
    </button>

    <div class="w-px h-5 bg-neutral-200 dark:border-neutral-700 mx-1"></div>

    <button
        type="button"
        x-on:click.prevent="toggleHeading(1)"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('heading', { level: 1 }) }"
    >
        <neura::icon name="h1" class="size-4" />
        <span class="sr-only">Heading 1</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleHeading(2)"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('heading', { level: 2 }) }"
    >
        <neura::icon name="h2" class="size-4" />
        <span class="sr-only">Heading 2</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleHeading(3)"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('heading', { level: 3 }) }"
    >
        <neura::icon name="h3" class="size-4" />
        <span class="sr-only">Heading 3</span>
    </button>

    <div class="w-px h-5 bg-neutral-200 dark:border-neutral-700 mx-1"></div>

    <button
        type="button"
        x-on:click.prevent="toggleBulletList()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('bulletList') }"
    >
        <neura::icon name="list-bullet" class="size-4" />
        <span class="sr-only">Bullet List</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="toggleOrderedList()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('orderedList') }"
    >
        <neura::icon name="numbered-list" class="size-4" />
        <span class="sr-only">Ordered List</span>
    </button>

    <div class="w-px h-5 bg-neutral-200 dark:border-neutral-700 mx-1"></div>

    <button
        type="button"
        x-on:click.prevent="toggleBlockquote()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('blockquote') }"
    >
        <neura::icon name="chat-bubble-bottom-center-text" class="size-4" />
        <span class="sr-only">Blockquote</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="setHorizontalRule()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
    >
        <neura::icon name="minus" class="size-4" />
        <span class="sr-only">Horizontal Rule</span>
    </button>

    <div class="w-px h-5 bg-neutral-200 dark:border-neutral-700 mx-1"></div>

    <button
        type="button"
        x-on:click.prevent="setTextAlign('left')"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive({ textAlign: 'left' }) }"
    >
        <neura::icon name="bars-3-bottom-left" class="size-4" />
        <span class="sr-only">Align Left</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="setTextAlign('center')"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive({ textAlign: 'center' }) }"
    >
        <neura::icon name="bars-3" class="size-4" />
        <span class="sr-only">Align Center</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="setTextAlign('right')"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive({ textAlign: 'right' }) }"
    >
        <neura::icon name="bars-3-bottom-right" class="size-4" />
        <span class="sr-only">Align Right</span>
    </button>

    <div class="w-px h-5 bg-neutral-200 dark:border-neutral-700 mx-1"></div>

    <button
        type="button"
        x-on:click.prevent="setLink()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
        x-bind:class="{ 'text-primary bg-primary/10': isActive('link') }"
    >
        <neura::icon name="link" class="size-4" />
        <span class="sr-only">Link</span>
    </button>
    <button
        type="button"
        x-on:click.prevent="addImage()"
        class="p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none text-neutral-500 dark:text-neutral-400"
    >
        <neura::icon name="photo" class="size-4" />
        <span class="sr-only">Image</span>
    </button>
</div>
