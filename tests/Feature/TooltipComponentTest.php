<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Neura\Kit\NeuraKitConfig;
use Neura\Kit\Tests\TestCase;

/**
 * The tooltip and the hover card open on hover and focus; their side, delay, corners and
 * look come from config('neura-kit.tooltip') and config('neura-kit.hover-card').
 */
class TooltipComponentTest extends TestCase
{
    public function test_a_tooltip_wraps_its_trigger_with_keys_and_an_arrow()
    {
        $html = Blade::render('<x-neura::tooltip content="Run" kbd="⌘ ↵"><button>Go</button></x-neura::tooltip>');

        $this->assertStringContainsString('x-data="neuraTooltip(', $html);
        $this->assertStringContainsString('role="tooltip"', $html);
        $this->assertStringContainsString('x-anchor.top.offset.8="$root"', $html);
        $this->assertStringContainsString('<kbd>⌘</kbd>', $html);
        $this->assertStringContainsString('<kbd>↵</kbd>', $html);
        $this->assertStringContainsString('nk-tooltip-arrow', $html);
        $this->assertStringContainsString('data-variant="dark"', $html);
        $this->assertStringContainsString('<button>Go</button>', $html);
    }

    public function test_props_choose_the_side_and_look()
    {
        $html = Blade::render('<x-neura::tooltip content="Hi" position="right-start" variant="light" :arrow="false"><span>?</span></x-neura::tooltip>');

        $this->assertStringContainsString('x-anchor.right-start.offset.8', $html);
        $this->assertStringContainsString('data-side="right"', $html);
        $this->assertStringContainsString('data-align="start"', $html);
        $this->assertStringContainsString('data-variant="light"', $html);
        $this->assertStringNotContainsString('nk-tooltip-arrow', $html);
    }

    public function test_config_sets_the_tooltip_defaults()
    {
        Config::set('neura-kit.tooltip', NeuraKitConfig::tooltip(['default' => ['variant' => 'light', 'position' => 'bottom', 'arrow' => false, 'delay' => 600, 'rounded' => 'sm']]));
        $html = Blade::render('<x-neura::tooltip content="Hi"><span>?</span></x-neura::tooltip>');

        $this->assertStringContainsString('x-anchor.bottom.offset.8', $html);
        $this->assertStringContainsString('data-variant="light"', $html);
        $this->assertStringContainsString('--nk-tooltip-radius: var(--radius-sm)', $html);
        $this->assertStringContainsString('\u0022delay\u0022:600', $html);
        $this->assertStringNotContainsString('nk-tooltip-arrow', $html);
    }

    public function test_a_hover_card_moves_its_card_to_the_body()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::hover-card>
                <x-slot:trigger><a href="#a">Demo Advertiser</a></x-slot:trigger>
                <x-neura::hover-card.header title="Demo Advertiser" subtitle="Advertiser" initials="DA" />
                <x-neura::hover-card.stats :columns="2">
                    <x-neura::hover-card.stat label="Leads" value="3 140" />
                </x-neura::hover-card.stats>
            </x-neura::hover-card>
        BLADE);

        $this->assertStringContainsString('x-data="neuraHoverCard(', $html);
        $this->assertStringContainsString('x-teleport="body"', $html);
        $this->assertStringContainsString('x-anchor.bottom-start.offset.8="$refs.trigger"', $html);
        $this->assertStringContainsString('<a href="#a">Demo Advertiser</a>', $html);
        $this->assertStringContainsString('nk-hover-card-title', $html);
        $this->assertStringContainsString('--nk-hover-card-columns: 2', $html);
        $this->assertStringContainsString('w-76', $html);
        $this->assertStringContainsString('shadow-lg', $html);
    }

    public function test_config_sets_the_hover_card_defaults()
    {
        Config::set('neura-kit.hover-card', NeuraKitConfig::hoverCard(['default' => ['size' => 'lg', 'rounded' => '2xl', 'shadow' => 'xl', 'position' => 'top', 'delay' => 500]]));
        $html = Blade::render(<<<'BLADE'
            <x-neura::hover-card>
                <x-slot:trigger>x</x-slot:trigger>
                Card
            </x-neura::hover-card>
        BLADE);

        $this->assertStringContainsString('x-anchor.top.offset.8', $html);
        $this->assertStringContainsString('w-96', $html);
        $this->assertStringContainsString('rounded-2xl', $html);
        $this->assertStringContainsString('shadow-xl', $html);
        $this->assertStringContainsString('\u0022delay\u0022:500', $html);
    }
}
