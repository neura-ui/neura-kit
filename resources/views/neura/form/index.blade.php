@props([
    'method' => 'POST',
    'action' => null,
])

@php
    $spacing = $attributes->get('spacing', 'space-y-6');

    $classes = [
        $spacing,
    ];
@endphp

<form
    @if($action) action="{{ $action }}" @endif
    method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
    {{ $attributes->except(['spacing'])->merge(['class' => Arr::toCssClasses($classes)]) }}
>
    @if(strtoupper($method) !== 'GET')
        @csrf
    @endif

    @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
        @method($method)
    @endif

    {{ $slot }}
</form>
