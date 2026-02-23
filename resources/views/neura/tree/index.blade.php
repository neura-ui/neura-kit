@props([
    'items' => [],
    'variant' => 'default',
    'size' => 'md',
    'selectable' => false,
    'multiSelect' => false,
    'showIcons' => true,
    'expandAll' => false,
    'onlyFolders' => false,
    'wireModel' => null,
])

@php
    use Illuminate\Support\Str;
    
    $componentId = 'tree-' . Str::random(8);
    
    // Clean, modern sizes
    $sizeConfig = match ($size) {
        'sm' => [
            'text' => 'text-xs',
            'icon' => 'size-3.5',
            'padding' => 'px-1.5 py-0.5',
            'gap' => 'gap-1.5',
            'indent' => 12,
        ],
        'md' => [
            'text' => 'text-sm',
            'icon' => 'size-4',
            'padding' => 'px-2 py-1',
            'gap' => 'gap-2',
            'indent' => 16,
        ],
        'lg' => [
            'text' => 'text-base',
            'icon' => 'size-5',
            'padding' => 'px-2.5 py-1.5',
            'gap' => 'gap-2.5',
            'indent' => 20,
        ],
        default => [
            'text' => 'text-sm',
            'icon' => 'size-4',
            'padding' => 'px-2 py-1',
            'gap' => 'gap-2',
            'indent' => 16,
        ],
    };
    
    // Minimal, clean variants (shadcn/Notion style)
    $variantConfig = match ($variant) {
        'default' => [
            'item' => 'hover:bg-hover rounded-md transition-colors',
            'selected' => 'bg-active text-fg',
            'chevron' => 'text-fg-disabled hover:text-fg-secondary',
        ],
        'ghost' => [
            'item' => 'hover:bg-hover transition-colors',
            'selected' => 'bg-active text-fg font-medium',
            'chevron' => 'text-fg-disabled hover:text-fg-secondary',
        ],
        'bordered' => [
            'item' => 'hover:bg-hover border-l-2 border-transparent hover:border-edge-hover transition-all',
            'selected' => 'bg-active !border-neutral-900 dark:!border-neutral-100 text-fg',
            'chevron' => 'text-fg-disabled hover:text-fg-secondary',
        ],
        default => [
            'item' => 'hover:bg-hover rounded-md transition-colors',
            'selected' => 'bg-active text-fg',
            'chevron' => 'text-fg-disabled hover:text-fg-secondary',
        ],
    };
@endphp

<div 
    id="{{ $componentId }}"
    x-data="neuraTree({
        items: {{ json_encode($items) }},
        selectable: {{ $selectable ? 'true' : 'false' }},
        multiSelect: {{ $multiSelect ? 'true' : 'false' }},
        expandAll: {{ $expandAll ? 'true' : 'false' }},
        onlyFolders: {{ $onlyFolders ? 'true' : 'false' }},
        indent: {{ $sizeConfig['indent'] }},
        wireModel: {{ $wireModel ? "'" . $wireModel . "'" : 'null' }},
    })"
    x-init="init()"
    {{ $attributes->class(['w-full select-none']) }}
    role="tree"
    aria-label="File tree"
>
    <template x-for="item in visibleItems" :key="item.id">
        <div 
            class="relative {{ $sizeConfig['padding'] }} {{ $sizeConfig['gap'] }} {{ $variantConfig['item'] }} flex items-center group cursor-pointer"
            :class="{
                '{{ $variantConfig['selected'] }}': isSelected(item.id),
                'opacity-40': dragState.dragging && dragState.draggedId === item.id,
            }"
            :style="`padding-left: ${item.level * indent + 8}px`"
            @click="handleSelect(item, $event)"
            draggable="true"
            @dragstart="handleDragStart($event, item)"
            @dragend="handleDragEnd($event)"
            @dragover="handleDragOver($event, item)"
            @dragleave="handleDragLeave($event)"
            @drop="handleDrop($event, item)"
            role="treeitem"
            :aria-level="item.level + 1"
            :aria-expanded="item.type === 'folder' ? isExpanded(item.id) : undefined"
            :aria-selected="isSelected(item.id)"
        >
            {{-- Drop indicator --}}
            <div 
                x-show="dragState.dropTarget === item.id && dragState.dropPosition === 'before'"
                class="absolute -top-px left-0 right-0 h-0.5 bg-primary-500 rounded-full"
                x-transition
            ></div>
            
            {{-- Chevron for folders --}}
            <button
                x-show="item.type === 'folder' && hasChildren(item)"
                @click.stop="toggleExpand(item.id)"
                class="{{ $sizeConfig['icon'] }} {{ $variantConfig['chevron'] }} shrink-0 flex items-center justify-center rounded transition-all duration-200"
                :class="{ 'rotate-90': isExpanded(item.id) }"
                type="button"
                :aria-label="isExpanded(item.id) ? 'Collapse' : 'Expand'"
            >
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="item.type !== 'folder' || !hasChildren(item)" class="{{ $sizeConfig['icon'] }} shrink-0"></div>

            {{-- Icon --}}
            @if($showIcons)
                <div class="{{ $sizeConfig['icon'] }} shrink-0 text-fg-muted" :class="item.iconClass">
                    {{-- Folder icons (Notion/shadcn style) --}}
                    <template x-if="item.type === 'folder'">
                        <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path 
                                x-show="!isExpanded(item.id)"
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                            />
                            <path 
                                x-show="isExpanded(item.id)"
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"
                            />
                        </svg>
                    </template>
                    
                    {{-- File icons based on type --}}
                    <template x-if="item.type === 'file' || item.type === 'document'">
                        <template x-if="item.icon === 'image'">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </template>
                        <template x-if="item.icon === 'code'">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </template>
                        <template x-if="!item.icon || (item.icon !== 'image' && item.icon !== 'code')">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </template>
                    </template>
                </div>
            @endif

            {{-- Label --}}
            <span 
                class="{{ $sizeConfig['text'] }} truncate font-medium text-fg-secondary"
                :class="{
                    'text-fg': isSelected(item.id)
                }"
                x-text="item.label || item.name"
            ></span>

            {{-- Badge (Notion style) --}}
            <template x-if="item.badge">
                <span 
                    class="ml-auto text-xs px-1.5 py-0.5 rounded-md bg-surface-inset text-fg-secondary font-medium"
                    x-text="item.badge"
                ></span>
            </template>

            {{-- Drag handle (subtle) --}}
            <div 
                class="ml-auto opacity-0 group-hover:opacity-100 {{ $sizeConfig['icon'] }} text-neutral-400 cursor-grab active:cursor-grabbing transition-opacity"
            >
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                </svg>
            </div>

            {{-- Drop indicator after --}}
            <div 
                x-show="dragState.dropTarget === item.id && dragState.dropPosition === 'after'"
                class="absolute -bottom-px left-0 right-0 h-0.5 bg-primary-500 rounded-full"
                x-transition
            ></div>

            {{-- Drop zone for folders --}}
            <div 
                x-show="dragState.dropTarget === item.id && dragState.dropPosition === 'inside'"
                class="absolute inset-0 bg-primary-500/10 border-2 border-primary-500 border-dashed rounded-md pointer-events-none"
                x-transition
            ></div>
        </div>
    </template>
</div>
