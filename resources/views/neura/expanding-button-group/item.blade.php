@props([
    'id',
    'label',
    'icon' => null,
    'method' => null,
    'doneLabel' => null,
    'color' => null,
    'disabled' => false,
])

@include('neura::expanding-button-group.button', [
    'id' => $id,
    'label' => $label,
    'icon' => $icon,
    'method' => $method,
    'doneLabel' => $doneLabel,
    'color' => $color,
    'disabled' => $disabled,
    'expanded' => false,
])
