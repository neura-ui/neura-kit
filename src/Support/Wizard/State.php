<?php

namespace Neura\Kit\Support\Wizard;

class State
{
    /**
     * Normalize a steps array into a predictable shape.
     *
     * Accepts plain strings (`'Account'`) or arrays keyed by
     * `label`, `description` and `icon`, so every variant can render
     * the same structure instead of stringifying objects.
     *
     * @param  array<int, mixed>  $steps
     * @return array<int, array{label: string, description: string|null, icon: string|null}>
     */
    public static function normalize(array $steps): array
    {
        return array_values(array_map(function ($step): array {
            if (is_array($step)) {
                return [
                    'label' => (string) ($step['label'] ?? $step[0] ?? ''),
                    'description' => isset($step['description']) ? (string) $step['description'] : null,
                    'icon' => isset($step['icon']) ? (string) $step['icon'] : null,
                ];
            }

            return [
                'label' => (string) $step,
                'description' => null,
                'icon' => null,
            ];
        }, $steps));
    }

    /**
     * Size tokens for the stepper variant.
     *
     * Connectors are centred with flexbox rather than magic offsets,
     * so only intrinsic sizes live here.
     *
     * @return array<string, string>
     */
    public static function sizes(?string $size): array
    {
        return match ($size) {
            'sm' => [
                'circle' => 'size-7 text-xs',
                'icon' => 'size-3.5',
                'label' => 'text-xs',
                'description' => 'text-[10px]',
                'trackGap' => 'gap-2',
                'verticalPad' => 'pb-6',
            ],
            'lg' => [
                'circle' => 'size-12 text-base',
                'icon' => 'size-5',
                'label' => 'text-base',
                'description' => 'text-xs',
                'trackGap' => 'gap-4',
                'verticalPad' => 'pb-10',
            ],
            default => [
                'circle' => 'size-9 text-sm',
                'icon' => 'size-4',
                'label' => 'text-sm',
                'description' => 'text-[11px]',
                'trackGap' => 'gap-3',
                'verticalPad' => 'pb-8',
            ],
        };
    }

    /**
     * Stable key used to share "furthest step reached" between the wizard
     * parts, which each own an independent Alpine scope.
     */
    public static function scope(?string $id, ?string $stepProperty): string
    {
        return $id ?: ($stepProperty ?: 'wizard');
    }
}
