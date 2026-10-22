@props([
    'column',
    'wireModel' => null,
])

@php
    $filterType = $column->filterType ?? 'text';
    $wireModel = $wireModel ?? "filters.{$column->key}";
@endphp

@switch($filterType)
    @case('select')
        @if($column->filterOptions)
            <neura::select
                wire:model.live="{{ $wireModel }}"
                placeholder="{{ neura_trans('select') }} {{ $column->label }}"
                clearable
                size="md"
            >
                <neura::select.option value="">{{ neura_trans('all') }}</neura::select.option>
                @foreach($column->filterOptions as $value => $label)
                    <neura::select.option value="{{ $value }}">{{ $label }}</neura::select.option>
                @endforeach
            </neura::select>
        @endif
        @break

    @case('date')
        <neura::input
            type="date"
            wire:model.live="{{ $wireModel }}"
            placeholder="{{ neura_trans('filter') }} {{ $column->label }}"
            size="md"
        />
        @break

    @case('boolean')
        <neura::select
            wire:model.live="{{ $wireModel }}"
            placeholder="{{ neura_trans('select') }} {{ $column->label }}"
            clearable
            size="md"
        >
            <neura::select.option value="">{{ neura_trans('all') }}</neura::select.option>
            @foreach($column->filterOptions ?: ['1' => neura_trans('yes'), '0' => neura_trans('no')] as $value => $label)
                <neura::select.option value="{{ $value }}">{{ $label }}</neura::select.option>
            @endforeach
        </neura::select>
        @break

    @default
        <neura::input
            wire:model.live.debounce.300ms="{{ $wireModel }}"
            placeholder="{{ neura_trans('filter') }} {{ $column->label }}"
            size="md"
        />
@endswitch
