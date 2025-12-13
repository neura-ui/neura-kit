@props([
    'value',
    'row' => null,
])

@php
    $type = $value instanceof \App\Enums\UserTypeEnum ? $value : \App\Enums\UserTypeEnum::tryFrom($value);
    $label = $type?->label() ?? $value;
@endphp

<div class="flex">
    <div @class([
        'text-white rounded-xl px-2 py-1 uppercase font-bold text-xs',
        'bg-purple-500 dark:bg-purple-600' => $type === \App\Enums\UserTypeEnum::SUPER_ADMIN,
        'bg-blue-500 dark:bg-blue-600' => $type === \App\Enums\UserTypeEnum::ADMIN,
        'bg-neutral-500 dark:bg-neutral-600' => $type === \App\Enums\UserTypeEnum::USER,
        'bg-green-500 dark:bg-green-600' => $type === \App\Enums\UserTypeEnum::OWNER,
        'bg-teal-500 dark:bg-teal-600' => $type === \App\Enums\UserTypeEnum::MEMBER,
        'bg-neutral-500 dark:bg-neutral-600' => $type === \App\Enums\UserTypeEnum::VIEWER,
    ])>
        {{ $label }}
    </div>
</div>

