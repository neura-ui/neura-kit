@props([
    'value' => null,
    'row' => null,
    'column' => null,
    'format' => null,
    'formatUsing' => null,
    'html' => null,
    'extraAttributes' => [],
    'variant' => 'ghost',
])

@php
    use Neura\Kit\Support\Table\Action;

    $rawActions = $extraAttributes['actions'] ?? [];

    $actions = collect($rawActions)->map(fn($action) =>
        $action instanceof Action ? $action->toArray() : $action
    );

    $resolve = fn($value, $default = true) => is_callable($value)
        ? rescue(fn() => $value($row), $default, false)
        : ($value ?? $default);

    $resolveParams = function($params) use ($row, &$resolveParams) {
        if (is_array($params)) {
            return collect($params)->map(function($param) use ($row, $resolveParams) {
                if (is_callable($param)) {
                    return rescue(fn() => $param($row), $param, false);
                }
                if (is_array($param)) {
                    return $resolveParams($param);
                }
                return $param;
            })->all();
        }
        if (is_callable($params)) {
            return rescue(fn() => $params($row), $params, false);
        }
        return $params;
    };

    $formatWireParam = function($param) {
        return match(true) {
            is_string($param) => "'" . addslashes($param) . "'",
            is_numeric($param) => $param,
            is_bool($param) => $param ? 'true' : 'false',
            is_null($param) => 'null',
            default => "'" . addslashes((string)$param) . "'"
        };
    };

    $buildWireClick = function($wireClick, $rawParams) use ($resolveParams, $formatWireParam) {
        $resolvedParams = $resolveParams($rawParams);
        $paramsArray = is_array($resolvedParams) ? $resolvedParams : [$resolvedParams];
        $formattedParams = collect($paramsArray)->map($formatWireParam)->join(', ');
        return $wireClick . '(' . $formattedParams . ')';
    };

    $renderedActions = [];
    foreach ($actions as $action) {
        if (!$resolve($action['visible'] ?? true)) continue;

        $params = $resolve($action['params'] ?? null, [$row->id ?? $row]);
        $params = is_array($params) ? $params : [$params];

        $tooltip = is_callable($action['tooltip'] ?? null)
            ? rescue(fn() => ($action['tooltip'])($row), null, false)
            : ($action['tooltip'] ?? null);

        $wireClickString = null;
        if (isset($action['wireClick'])) {
            $wireClickString = $buildWireClick(
                $action['wireClick'],
                $action['params'] ?? [$row->id ?? $row]
            );
        }

        $renderedActions[] = [
            'icon' => $action['icon'] ?? null,
            'label' => $action['label'] ?? null,
            'variant' => $action['variant'] ?? $variant,
            'size' => $action['size'] ?? 'sm',
            'tooltip' => $tooltip,
            'disabled' => !$resolve($action['enabled'] ?? true, true),
            'confirm' => $action['confirm'] ?? null,
            'route' => isset($action['route']) ? route($action['route'], $params) : null,
            'href' => isset($action['href'])
                ? (is_callable($action['href']) ? rescue(fn() => ($action['href'])($row), '#', false) : $action['href'])
                : null,
            'url' => $action['url'] ?? null,
            'wireClick' => $wireClickString,
            'dispatch' => $action['dispatch'] ?? null,
            'dispatchPayload' => isset($action['dispatch'])
                ? json_encode($resolveParams($action['dispatchParams'] ?? $params))
                : null,
        ];
    }
@endphp

@php
    $btnClass = 'inline-flex items-center justify-center rounded-md transition-colors duration-100 size-7 text-neutral-400 dark:text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-white/[0.06]';
    $disabledClass = 'pointer-events-none opacity-30';
@endphp

<div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-100">
    @foreach($renderedActions as $a)
        @php
            $classes = $btnClass . ($a['disabled'] ? ' ' . $disabledClass : '');
        @endphp

        @if($a['route'] || $a['href'] || $a['url'])
            @if($a['tooltip'])
                <neura::popover :onHover="true" variant="tooltip" size="xs">
                    <neura::popover.trigger>
                        <a href="{{ $a['route'] ?? $a['href'] ?? $a['url'] }}" class="{{ $classes }}">
                            @if($a['icon']) <neura::icon name="{{ $a['icon'] }}" class="size-3.5" /> @endif
                        </a>
                    </neura::popover.trigger>
                    <neura::popover.overlay class="whitespace-nowrap">
                        <div class="px-2 py-1 text-xs">{{ $a['tooltip'] }}</div>
                    </neura::popover.overlay>
                </neura::popover>
            @else
                <a href="{{ $a['route'] ?? $a['href'] ?? $a['url'] }}" class="{{ $classes }}">
                    @if($a['icon']) <neura::icon name="{{ $a['icon'] }}" class="size-3.5" /> @endif
                </a>
            @endif
        @elseif($a['wireClick'])
            @if($a['tooltip'])
                <neura::popover :onHover="true" variant="tooltip" size="xs">
                    <neura::popover.trigger>
                        <neura::button variant="{{ $a['variant'] }}" size="{{ $a['size'] }}" icon="{{ $a['icon'] }}" wire:click="{{ $a['wireClick'] }}" />
                    </neura::popover.trigger>
                    <neura::popover.overlay class="whitespace-nowrap">
                        <div class="px-2 py-1 text-xs">{{ $a['tooltip'] }}</div>
                    </neura::popover.overlay>
                </neura::popover>
            @else
                <neura::button variant="{{ $a['variant'] }}" size="{{ $a['size'] }}" icon="{{ $a['icon'] }}" wire:click="{{ $a['wireClick'] }}" />
            @endif
        @elseif($a['dispatch'])
            @if($a['tooltip'])
                <neura::popover :onHover="true" variant="tooltip" size="xs">
                    <neura::popover.trigger>
                        <neura::button variant="{{ $a['variant'] }}" size="{{ $a['size'] }}" icon="{{ $a['icon'] }}" x-on:click="$dispatch('{{ $a['dispatch'] }}', {{ $a['dispatchPayload'] }})" />
                    </neura::popover.trigger>
                    <neura::popover.overlay class="whitespace-nowrap">
                        <div class="px-2 py-1 text-xs">{{ $a['tooltip'] }}</div>
                    </neura::popover.overlay>
                </neura::popover>
            @else
                <neura::button variant="{{ $a['variant'] }}" size="{{ $a['size'] }}" icon="{{ $a['icon'] }}" x-on:click="$dispatch('{{ $a['dispatch'] }}', {{ $a['dispatchPayload'] }})" />
            @endif
        @endif
    @endforeach
</div>
