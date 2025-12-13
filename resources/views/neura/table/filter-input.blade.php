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
            size="sm"
        />
        @break

    @case('boolean')
        <neura::select
            wire:model.live="{{ $wireModel }}"
            placeholder="{{ __('Select') }} {{ $column->label }}"
            clearable
        >
            <neura::select.option value="">{{ __('All') }}</neura::select.option>
            <neura::select.option value="1">{{ neura_trans('yes') }}</neura::select.option>
            <neura::select.option value="0">{{ neura_trans('no') }}</neura::select.option>
        </neura::select>
        @break

    @default
        <neura::input
            wire:model.live.debounce.300ms="{{ $wireModel }}"
            placeholder="{{ neura_trans('filter') }} {{ $column->label }}"
            size="sm"
        />
@endswitch
