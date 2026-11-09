@props([
    'title' => null,
    'subtitle' => null,
    'initials' => null,
    'src' => null,
    'color' => null,
    'circle' => null,
    'aside' => null,
])

{{-- An avatar, a title and a line under it, with an optional aside (a status badge). --}}
<div {{ $attributes->class(['nk-hover-card-header']) }}>
    @if ($initials || $src || $title)
        <neura::avatar :initials="$initials" :src="$src" :name="$initials || $src ? null : $title" :color="$color" :circle="$circle" size="md" />
    @endif
    <div class="nk-hover-card-titles">
        @if ($title)
            <span class="nk-hover-card-title">{{ $title }}</span>
        @endif
        @if ($subtitle)
            <span class="nk-hover-card-subtitle">{{ $subtitle }}</span>
        @endif
        {{ $slot }}
    </div>
    @if ($aside)
        <div class="nk-hover-card-aside">{{ $aside }}</div>
    @endif
</div>
