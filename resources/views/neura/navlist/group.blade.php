@props([
    'collapsable' => false,
    'variant' => 'default',
    'size' => 'md',
    'label' => null,
    'icon' => null,
])

@php
    use Illuminate\View\ComponentSlot;

    $groupId = 'navlist-group-' . \Illuminate\Support\Str::random(8);
    $hasLabel = $label instanceof ComponentSlot
        ? $label->isNotEmpty()
        : filled($label);

    $rootClass = match ($variant) {
        'separated' => 'flex flex-col gap-y-1 mt-4 first:mt-0',
        default => 'flex flex-col gap-y-1 mt-3 first:mt-0',
    };
@endphp

<div
    {{ $attributes->merge(['class' => $rootClass]) }}
    data-slot="navlist-group"
    data-variant="{{ $variant }}"
    role="group"
    @if ($hasLabel) aria-labelledby="{{ $groupId }}" @endif
    x-data="{ expanded: true, expand() { this.expanded = !this.expanded } }"
>
    @switch($variant)
        @case('compact')
            <neura::navlist.group.variant.compact
                :collapsable="$collapsable"
                :icon="$icon"
                :size="$size"
                :group-id="$groupId"
            >
                @if ($hasLabel)
                    <x-slot:label>{{ $label }}</x-slot:label>
                @endif
                {{ $slot }}
            </neura::navlist.group.variant.compact>
            @break

        @case('separated')
            <neura::navlist.group.variant.separated
                :collapsable="$collapsable"
                :icon="$icon"
                :size="$size"
                :group-id="$groupId"
            >
                @if ($hasLabel)
                    <x-slot:label>{{ $label }}</x-slot:label>
                @endif
                {{ $slot }}
            </neura::navlist.group.variant.separated>
            @break

        @case('card')
            <neura::navlist.group.variant.card
                :collapsable="$collapsable"
                :icon="$icon"
                :size="$size"
                :group-id="$groupId"
            >
                @if ($hasLabel)
                    <x-slot:label>{{ $label }}</x-slot:label>
                @endif
                {{ $slot }}
            </neura::navlist.group.variant.card>
            @break

        @default
            <neura::navlist.group.variant.default
                :collapsable="$collapsable"
                :icon="$icon"
                :size="$size"
                :group-id="$groupId"
            >
                @if ($hasLabel)
                    <x-slot:label>{{ $label }}</x-slot:label>
                @endif
                {{ $slot }}
            </neura::navlist.group.variant.default>
    @endswitch
</div>
