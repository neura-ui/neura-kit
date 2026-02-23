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
        '[:where(&)]:min-w-44',
        'text-start',
        'bg-surface-raised',
        'border border-black/[0.06] dark:border-white/[0.08]',
        'ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
        'shadow-[0_4px_16px_-2px_rgb(0_0_0/0.08),0_2px_6px_-1px_rgb(0_0_0/0.04)] dark:shadow-[0_4px_16px_-2px_rgb(0_0_0/0.4),0_2px_6px_-1px_rgb(0_0_0/0.25)]',
        'rounded-(--dropdown-radius)',
        'p-(--dropdown-padding)',
        '[--dropdown-radius:var(--radius-box)]',
        '[--dropdown-padding:--spacing(1)]',
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
        {{ $attributes->only(['class'])->merge(['class' => 'flex items-center justify-between gap-2 w-full px-2.5 py-1.5 text-[13px] leading-snug transition-colors duration-100 text-start text-fg rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))] cursor-pointer ' . ($disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-hover focus:bg-hover')]) }}
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
        <neura::icon name="chevron-down" class="size-3.5 shrink-0 text-fg-muted" />
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
