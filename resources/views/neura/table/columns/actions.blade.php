@props([
    'row' => null,
    'extraAttributes' => [],
    'variant' => 'ghost',
])

@php
    use Neura\Kit\Support\Table\Action;

    $rawActions = $extraAttributes['actions'] ?? [];

    // Normalize actions once
    $actions = collect($rawActions)->map(fn($action) =>
        $action instanceof Action ? $action->toArray() : $action
    );

    // Helper function to resolve callable or static values
    $resolve = fn($value, $default = true) => is_callable($value)
        ? rescue(fn() => $value($row), $default, false)
        : ($value ?? $default);

    // Helper function to recursively resolve callables in nested structures
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

    // Helper function to format wire:click parameters
    $formatWireParam = function($param) {
        return match(true) {
            is_string($param) => "'" . addslashes($param) . "'",
            is_numeric($param) => $param,
            is_bool($param) => $param ? 'true' : 'false',
            is_null($param) => 'null',
            default => "'" . addslashes((string)$param) . "'"
        };
    };

    // Helper function to build wire:click string
    $buildWireClick = function($wireClick, $rawParams) use ($resolveParams, $formatWireParam, $row) {
        // Resolve all callables in params first
        $resolvedParams = $resolveParams($rawParams);

        // Ensure params is an array
        $paramsArray = is_array($resolvedParams) ? $resolvedParams : [$resolvedParams];

        // Format params for wire:click
        $formattedParams = collect($paramsArray)
            ->map($formatWireParam)
            ->join(', ');

        return $wireClick . '(' . $formattedParams . ')';
    };
@endphp

<div class="flex items-center gap-2">
    @foreach($actions as $action)
        @php
            // Check visibility
            if (!$resolve($action['visible'] ?? true)) {
                continue;
            }

            // Resolve parameters for routes/URLs
            $params = $resolve($action['params'] ?? null, [$row->id ?? $row]);
            $params = is_array($params) ? $params : [$params];

            // Extract action properties
            $tooltip = $action['tooltip'] ?? null;
            $icon = $action['icon'] ?? null;
            $actionVariant = $action['variant'] ?? $variant;
            $size = $action['size'] ?? 'sm';

            // Build wire:click string if needed
            $wireClickString = null;
            if (isset($action['wireClick'])) {
                $wireClickString = $buildWireClick(
                    $action['wireClick'],
                    $action['params'] ?? [$row->id ?? $row]
                );
            }
        @endphp

        @if($tooltip)
            <neura::popover :onHover="true">
                <neura::popover.trigger>
                    @if(isset($action['route']))
                        <neura::button
                            variant="{{ $actionVariant }}"
                            size="{{ $size }}"
                            icon="{{ $icon }}"
                            as="a"
                            href="{{ route($action['route'], $params) }}"
                        />
                    @elseif(isset($action['url']))
                        <neura::button
                            variant="{{ $actionVariant }}"
                            size="{{ $size }}"
                            icon="{{ $icon }}"
                            as="a"
                            href="{{ $action['url'] }}"
                        />
                    @elseif($wireClickString)
                        <neura::button
                            variant="{{ $actionVariant }}"
                            size="{{ $size }}"
                            icon="{{ $icon }}"
                            wire:click="{{ $wireClickString }}"
                        />
                    @endif
                </neura::popover.trigger>
                <neura::popover.overlay>
                    <div class="px-2 py-1 text-sm">
                        {{ $tooltip }}
                    </div>
                </neura::popover.overlay>
            </neura::popover>
        @else
            @if(isset($action['route']))
                <neura::button
                    variant="{{ $actionVariant }}"
                    size="{{ $size }}"
                    icon="{{ $icon }}"
                    as="a"
                    href="{{ route($action['route'], $params) }}"
                />
            @elseif(isset($action['url']))
                <neura::button
                    variant="{{ $actionVariant }}"
                    size="{{ $size }}"
                    icon="{{ $icon }}"
                    as="a"
                    href="{{ $action['url'] }}"
                />
            @elseif($wireClickString)
                <neura::button
                    variant="{{ $actionVariant }}"
                    size="{{ $size }}"
                    icon="{{ $icon }}"
                    wire:click="{{ $wireClickString }}"
                />
            @endif
        @endif
    @endforeach
</div>
