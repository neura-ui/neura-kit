<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Support\PackResolver;
use Neura\Kit\Tests\TestCase;

/**
 * The flow hands its nodes, edges and looks to the script in one config: the default node
 * shape and card style (from config('neura-kit.flow') unless the props say otherwise), the
 * size of every shape and the class of every style (from the packs), and the icons its
 * nodes name, rendered once by the kit.
 */
class FlowComponentTest extends TestCase
{
    public function test_nodes_default_to_the_configured_shape_and_style()
    {
        $config = $this->config(Blade::render('<x-neura::flow />'));

        // Horizontal cards stay the default look; the n8n tiles are `shape="node"`.
        $this->assertSame('card', $config['shape']);
        $this->assertSame('elevated', $config['variant']);
        $this->assertSame(['width' => 96, 'height' => 96], $config['shapes']['node']);
        $this->assertSame(['width' => 208, 'height' => 96], $config['shapes']['agent']);
        $this->assertSame(240, $config['shapes']['table']['width']);
        $this->assertSame('flow-v-soft', $config['variants']['soft']);
    }

    public function test_props_override_the_configured_shape_and_style()
    {
        $config = $this->config(Blade::render('<x-neura::flow shape="node" variant="soft" />'));

        $this->assertSame('node', $config['shape']);
        $this->assertSame('soft', $config['variant']);
    }

    public function test_an_unknown_shape_or_style_falls_back_to_the_default()
    {
        $config = $this->config(Blade::render('<x-neura::flow shape="blob" variant="neon" />'));

        $this->assertSame('card', $config['shape']);
        $this->assertSame('elevated', $config['variant']);
    }

    public function test_config_sets_the_defaults()
    {
        config()->set('neura-kit.flow.default.shape', 'node');
        config()->set('neura-kit.flow.default.variant', 'outline');

        $config = $this->config(Blade::render('<x-neura::flow />'));

        $this->assertSame('node', $config['shape']);
        $this->assertSame('outline', $config['variant']);
        $this->assertSame('node', PackResolver::flowShape(null));
        $this->assertSame('outline', PackResolver::flowVariant(null));
    }

    public function test_the_icons_nodes_name_are_rendered_once_for_the_script()
    {
        // Neura's own icons (heroicons are registered by the app, not in these tests).
        $html = Blade::render('<x-neura::flow :nodes="$nodes" :icons="[\'settings\']" />', [
            'nodes' => [
                ['id' => '1', 'data' => ['label' => 'Send', 'icon' => 'mail']],
                ['id' => '2', 'data' => ['label' => 'Glyph', 'icon' => '▶']],
                ['id' => '3', 'data' => ['label' => 'Missing', 'icon' => 'definitely-not-an-icon']],
            ],
        ]);

        $this->assertMatchesRegularExpression('/data-flow-icon="mail"[^>]*>\s*<svg/', $html);
        $this->assertMatchesRegularExpression('/data-flow-icon="settings"[^>]*>\s*<svg/', $html);
        $this->assertStringNotContainsString('data-flow-icon="▶"', $html);
        $this->assertStringNotContainsString('data-flow-icon="definitely-not-an-icon"', $html);
    }

    public function test_the_nodes_and_edges_reach_the_script()
    {
        $config = $this->config(Blade::render('<x-neura::flow :nodes="$nodes" :edges="$edges" :node-actions="false" />', [
            'nodes' => [['id' => 'a', 'type' => 'trigger'], ['id' => 'b', 'type' => 'agent']],
            'edges' => [['source' => 'a', 'target' => 'b', 'label' => '3 items']],
        ]));

        $this->assertSame(['a', 'b'], array_column($config['nodes'], 'id'));
        $this->assertSame('3 items', $config['edges'][0]['label']);
        $this->assertFalse($config['nodeActions']);
        $this->assertTrue($config['trace']);
        $this->assertFalse($this->config(Blade::render('<x-neura::flow :trace="false" />'))['trace']);
    }

    public function test_the_classic_theme_is_the_default()
    {
        $html = Blade::render('<x-neura::flow />');
        $config = $this->config($html);

        $this->assertSame('classic', $config['theme']);
        $this->assertSame('bezier', $config['edgeType']);
        $this->assertStringContainsString('flow-t-classic', $html);
    }

    public function test_the_signal_theme_brings_its_capsules_and_circuit_traces()
    {
        $html = Blade::render('<x-neura::flow theme="signal" />');
        $config = $this->config($html);

        $this->assertSame('signal', $config['theme']);
        $this->assertSame('smoothstep', $config['edgeType']);
        // Signal's own look is the capsule, whatever the config's default shape.
        $this->assertSame('node', $config['shape']);
        $this->assertSame('card', $this->config(Blade::render('<x-neura::flow theme="signal" shape="card" />'))['shape']);
        $this->assertSame(['width' => 160, 'height' => 44], $config['shapes']['node']);
        $this->assertSame(['width' => 40, 'height' => 40], $config['shapes']['sub']);
        $this->assertSame(['width' => 232, 'height' => 96], $config['shapes']['agent']);
        $this->assertStringContainsString('flow-t-signal', $html);
    }

    public function test_an_explicit_edge_type_wins_over_the_theme()
    {
        $this->assertSame('bezier', $this->config(Blade::render('<x-neura::flow theme="signal" edge-type="bezier" />'))['edgeType']);
    }

    public function test_an_unknown_theme_falls_back_and_config_sets_the_default()
    {
        $this->assertSame('classic', $this->config(Blade::render('<x-neura::flow theme="neon" />'))['theme']);

        config()->set('neura-kit.flow.default.theme', 'signal');

        $this->assertSame('signal', $this->config(Blade::render('<x-neura::flow />'))['theme']);
        $this->assertSame('signal', PackResolver::flowTheme(null));
    }

    private function config(string $html): array
    {
        $this->assertMatchesRegularExpression('/x-data="neuraFlow\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html);
        preg_match('/x-data="neuraFlow\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html, $match);

        return json_decode(json_decode('"'.html_entity_decode($match[1]).'"'), true);
    }
}
