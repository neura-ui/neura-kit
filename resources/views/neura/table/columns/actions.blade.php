@props([
    'row' => null,
    'extraAttributes' => [],
    'variant' => 'ghost',
])

@php
    $actions = $extraAttributes['actions'] ?? [];
@endphp

<div class="flex items-center gap-2">
    @foreach($actions as $action)
        @php
            $params = null;
            if (isset($action['params'])) {
                if (is_callable($action['params'])) {
                    $params = $action['params']($row);
                } else {
                    $params = $action['params'];
                }
            } else {
                $params = [$row->id ?? $row];
            }
            
            if (!is_array($params)) {
                $params = [$params];
            }
        @endphp
        @if(isset($action['route']))
            <neura::button
                variant="{{ $action['variant'] ?? $variant }}"
                size="sm"
                icon="{{ $action['icon'] ?? null }}"
                as="a"
                href="{{ route($action['route'], $params) }}"
            >
                {{ $action['label'] ?? '' }}
            </neura::button>
        @elseif(isset($action['wireClick']))
            @php
                $wireClickParams = array_map(function($param) {
                    if (is_string($param)) {
                        return "'" . addslashes($param) . "'";
                    } elseif (is_numeric($param)) {
                        return $param;
                    } elseif (is_bool($param)) {
                        return $param ? 'true' : 'false';
                    } elseif (is_null($param)) {
                        return 'null';
                    } else {
                        return "'" . addslashes((string)$param) . "'";
                    }
                }, $params);
                $wireClickString = $action['wireClick'] . '(' . implode(', ', $wireClickParams) . ')';
            @endphp
            <neura::button
                variant="{{ $action['variant'] ?? $variant }}"
                size="sm"
                icon="{{ $action['icon'] ?? null }}"
                wire:click="{{ $wireClickString }}"
            >
                {{ $action['label'] ?? '' }}
            </neura::button>
        @endif
    @endforeach
</div>
