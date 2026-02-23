@props([
    'value',
    'row' => null,
])

@php
    $type = $value instanceof \App\Enums\UserTypeEnum ? $value : \App\Enums\UserTypeEnum::tryFrom($value);
    $label = $type?->label() ?? $value;

    $colorClasses = match ($type) {
        \App\Enums\UserTypeEnum::SUPER_ADMIN => 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
        \App\Enums\UserTypeEnum::ADMIN => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
        \App\Enums\UserTypeEnum::OWNER => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
        \App\Enums\UserTypeEnum::MEMBER => 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400',
        \App\Enums\UserTypeEnum::VIEWER => 'bg-neutral-100 text-neutral-600 dark:bg-white/[0.06] dark:text-neutral-400',
        default => 'bg-neutral-100 text-neutral-600 dark:bg-white/[0.06] dark:text-neutral-400',
    };
@endphp

<div class="flex">
    <span @class([
        'inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium',
        $colorClasses,
    ])>
        {{ $label }}
    </span>
</div>
