@props([
    'commands' => [],
])

@php
    $commands = is_array($commands) ? $commands : json_decode($commands, true) ?? [];
@endphp

<neura::command.items>
    @foreach($commands as $command)
        @php
            $label = $command['label'] ?? '';
            $icon = $command['icon'] ?? null;
            $kbd = $command['kbd'] ?? null;
            $action = $command['action'] ?? null;
            $wireClick = $command['wire:click'] ?? null;
            $href = $command['href'] ?? null;
            
            $attributes = new \Illuminate\View\ComponentAttributeBag();
            if ($action) {
                $attributes = $attributes->merge(['x-on:click' => $action]);
            } elseif ($wireClick) {
                $attributes = $attributes->merge(['wire:click' => $wireClick]);
            } elseif ($href) {
                $attributes = $attributes->merge(['onclick' => "window.location.href='{$href}'"]);
            }
        @endphp
        
        <neura::command.item 
            :icon="$icon" 
            :kbd="$kbd"
            {{ $attributes }}
        >
            {{ $label }}
        </neura::command.item>
    @endforeach
</neura::command.items>

