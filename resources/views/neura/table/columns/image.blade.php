@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $src = $value ?? $extraAttributes['src'] ?? '';
    $alt = $extraAttributes['alt'] ?? '';
    $width = $extraAttributes['width'] ?? 32;
    $height = $extraAttributes['height'] ?? 32;
    $rounded = $extraAttributes['rounded'] ?? true;
    $type = $extraAttributes['type'] ?? null;
    $nameKey = $extraAttributes['nameKey'] ?? null;
    $name = null;

    if ($nameKey && $row) {
        $name = data_get($row, $nameKey);
    } elseif (! empty($extraAttributes['name'])) {
        $name = $extraAttributes['name'];
    }

    if ($alt === '' && is_string($name)) {
        $alt = $name;
    }

    $isAvatar = $type === 'avatar';
@endphp

@if ($isAvatar)
    <div class="flex items-center gap-2.5 min-w-0">
        <neura::avatar
            :src="filled($src) ? $src : null"
            :name="$name"
            :alt="$alt"
            size="sm"
            circle
            color="auto"
        />
        @if (filled($name))
            <span class="truncate text-[13px] text-neutral-900 dark:text-neutral-100">{{ $name }}</span>
        @endif
    </div>
@else
    <div class="flex items-center">
        @if ($src)
            <img
                src="{{ $src }}"
                alt="{{ $alt }}"
                width="{{ $width }}"
                height="{{ $height }}"
                loading="lazy"
                @class([
                    'object-cover',
                    'rounded-full' => $rounded === 'full',
                    'rounded-md' => $rounded === true && $rounded !== 'full',
                ])
            />
        @else
            <div
                class="bg-neutral-100 dark:bg-white/[0.06] flex items-center justify-center text-neutral-400 dark:text-neutral-500"
                style="width: {{ $width }}px; height: {{ $height }}px;"
                @class([
                    'rounded-full' => $rounded === 'full',
                    'rounded-md' => $rounded === true && $rounded !== 'full',
                ])
            >
                <neura::icon name="photo" class="size-4" />
            </div>
        @endif
    </div>
@endif
