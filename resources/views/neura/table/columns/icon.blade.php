@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $iconMapping = $extraAttributes['mapping'] ?? [
        'windows' => 'computer-desktop',
        'mac' => 'device-phone-mobile',
        'linux' => 'server',
    ];
    
    $rawValue = $value ?? $extraAttributes['icon'] ?? null;
    
    if (empty($rawValue)) {
        $iconNameValue = 'information-circle';
    } else {
        $rawValue = is_string($rawValue) ? strtolower(trim($rawValue)) : $rawValue;
        $iconNameValue = $iconMapping[$rawValue] ?? $rawValue;
    }
    
    $iconVariantValue = $extraAttributes['variant'] ?? 'micro';
    $iconColorValue = $extraAttributes['color'] ?? null;
@endphp

@if(!empty($iconNameValue))
    <div class="flex items-center justify-center">
        <neura::icon
            name="{{ $iconNameValue }}"
            variant="{{ $iconVariantValue }}"
            @if($iconColorValue) class="text-{{ $iconColorValue }}-500" @endif
        />
    </div>
@endif
