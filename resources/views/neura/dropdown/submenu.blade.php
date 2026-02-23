@props([
    'label',
    'icon' => null,
    'iconVariant' => 'mini',
    'disabled' => false,
    'position' => 'right-start',
])

@php
    $panelClasses = [
        'isolate',
        'z-[60]',
        'grid',
        '[:where(&)]:max-w-96',
        '[:where(&)]:min-w-40',
        'text-start',
        'bg-surface-raised',
        'border border-edge',
        'shadow-lg',
        'rounded-(--dropdown-radius)',
        'p-(--dropdown-padding)',
        '[--dropdown-radius:var(--radius-box)]',
        '[--dropdown-padding:--spacing(.75)]',
    ];
@endphp

<div 
    x-data="{ 
        isOpen: false,
        openTimeout: null,
        closeTimeout: null,
        
        open() {
            clearTimeout(this.closeTimeout);
            this.openTimeout = setTimeout(() => { this.isOpen = true; }, 50);
        },
        
        close() {
            clearTimeout(this.openTimeout);
            this.closeTimeout = setTimeout(() => { this.isOpen = false; }, 100);
        },
        
        keepOpen() {
            clearTimeout(this.closeTimeout);
        }
    }" 
    class="relative"
    @mouseenter="open()"
    @mouseleave="close()"
>
    {{-- Trigger Item --}}
    <div
        x-ref="trigger"
        @focus="isOpen = true"
        @keydown.right.prevent.stop="
            isOpen = true;
            $nextTick(() => {
                const el = $refs.panel;
                if (el) $focus.focus($focus.within(el).getFirst());
            });
        "
        {{ $attributes->only(['class'])->merge(['class' => 'flex items-center justify-between gap-2 w-full px-3 py-1.5 text-sm transition-colors duration-200 text-start text-neutral-800 dark:text-white rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))] cursor-pointer ' . ($disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-hover focus:bg-hover')]) }}
        tabindex="{{ $disabled ? '-1' : '0' }}"
        role="menuitem"
        aria-haspopup="true"
        :aria-expanded="isOpen"
    >
        <span class="flex items-center gap-2">
            @if (filled($icon))
                <neura::icon :name="$icon" :variant="$iconVariant" class="shrink-0 size-4" />
            @endif
            <span>{{ $label }}</span>
        </span>
        
        {{-- Chevron Arrow --}}
        <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </div>

    {{-- Submenu Panel --}}
    <div
        x-show="isOpen"
        x-ref="panel"
        x-anchor.{{ $position }}.offset.4="$refs.trigger"
        @mouseenter="keepOpen()"
        @keydown.left.prevent.stop="isOpen = false; $focus.focus($refs.trigger)"
        @keydown.escape.prevent.stop="isOpen = false; $focus.focus($refs.trigger)"
        @keydown.down.prevent.stop="$focus.next()"
        @keydown.up.prevent.stop="$focus.prev()"
        @keydown.home.prevent.stop="$focus.first()"
        @keydown.end.prevent.stop="$focus.last()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-x-1"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-1"
        style="display: none; backdrop-filter: blur(64px); -webkit-backdrop-filter: blur(64px);"
        role="menu"
        class="{{ Arr::toCssClasses($panelClasses) }}"
    >
        {{ $slot }}
    </div>
</div>
