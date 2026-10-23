<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The native map renders its configuration for the script: the looks it paints, the open
 * datasets it draws coarse to fine, and the markers and values it shows. No tile provider.
 */
class MapComponentTest extends TestCase
{
    public function test_a_map_offers_the_looks_it_may_switch_between()
    {
        $html = Blade::render('<x-neura::map map-style="atlas" :map-styles="[\'standard\', \'atlas\', \'dots\', \'nope\']" :controls="false" />');

        $config = $this->config($html);

        $this->assertSame('atlas', $config['style']);
        $this->assertSame(['atlas', 'standard', 'dots'], array_keys($config['styles']));
        $this->assertSame('#aad3df', $config['styles']['atlas']['water']);
        $this->assertSame('dots', $config['styles']['dots']['land']);

        // Every look carries its own strong ink, so city names never take the page's colour.
        $this->assertSame('#3a302a', $config['styles']['atlas']['ink']);
        $this->assertSame('var(--nk-fg)', $config['styles']['standard']['ink']);
        $this->assertSame(3, substr_count($html, 'role="radio"'));
    }

    public function test_the_land_and_lakes_come_from_open_data_coarse_to_fine()
    {
        $config = $this->config(Blade::render('<x-neura::map :controls="false" />'));

        $this->assertSame('standard', $config['style']);
        $this->assertCount(2, $config['land']);
        $this->assertStringEndsWith('ne_110m_admin_0_countries.geojson', $config['land'][0]['source']);
        $this->assertEquals(0, $config['land'][0]['from']);
        $this->assertStringEndsWith('ne_50m_admin_0_countries.geojson', $config['land'][1]['source']);
        $this->assertEquals(4, $config['land'][1]['from']);
        $this->assertStringEndsWith('ne_110m_lakes.geojson', $config['lakes'][0]['source']);
        $this->assertNull($config['overlay']);
        $this->assertSame('ISO_A2_EH', $config['valueKeys'][0]);

        foreach ([...$config['land'], ...$config['lakes']] as $level) {
            $this->assertStringNotContainsString('arcgisonline', $level['source']);
            $this->assertStringNotContainsString('cartocdn', $level['source']);
        }
    }

    public function test_the_values_and_markers_travel_in_an_attribute()
    {
        $html = Blade::render('<x-neura::map :values="$values" :markers="$markers" :controls="false" />', [
            'values' => ['FR' => 120, 'DE' => 80],
            'markers' => [['lat' => 48.85, 'lng' => 2.35, 'label' => 'Paris']],
        ]);

        preg_match('/data-map="([^"]*)"/', $html, $match);
        $data = json_decode(html_entity_decode($match[1]), true);

        $this->assertSame(['FR' => 120, 'DE' => 80], $data['values']);
        $this->assertSame('Paris', $data['markers'][0]['label']);
        $this->assertStringContainsString('Natural Earth', $html);
    }

    public function test_an_overlay_url_or_data_is_drawn_over_the_land_and_layers_can_be_left_out()
    {
        $config = $this->config(Blade::render('<x-neura::map geojson="https://example.test/regions.geojson" :lakes="false" :graticule="false" :controls="false" />'));

        $this->assertSame([['source' => 'https://example.test/regions.geojson', 'from' => 0]], $config['overlay']);
        $this->assertNull($config['lakes']);
        $this->assertFalse($config['graticule']);

        $html = Blade::render('<x-neura::map :geojson="$shape" :land="false" :lakes="false" :controls="false" />', [
            'shape' => ['type' => 'Feature', 'properties' => ['id' => 'A'], 'geometry' => ['type' => 'Point', 'coordinates' => [2, 48]]],
        ]);
        $config = $this->config($html);

        $this->assertSame('Feature', $config['overlay'][0]['source']['type']);
        $this->assertNull($config['land']);
        $this->assertStringNotContainsString('Natural Earth', $html);
    }

    public function test_a_focused_country_brings_its_cities_and_their_values()
    {
        $html = Blade::render('<x-neura::map country="fr" :city-values="$cityValues" :controls="false" />', [
            'cityValues' => ['Paris' => 120, 'Lyon' => 80],
        ]);
        $config = $this->config($html);

        $this->assertSame('FR', $config['country']);
        $this->assertTrue($config['zones']);
        $this->assertStringEndsWith('ne_50m_populated_places_simple.geojson', $config['cities'][0]['source']);
        $this->assertStringEndsWith('ne_10m_populated_places_simple.geojson', $config['cities'][1]['source']);
        $this->assertEquals(6, $config['cities'][1]['from']);

        preg_match('/data-map="([^"]*)"/', $html, $match);
        $data = json_decode(html_entity_decode($match[1]), true);

        $this->assertSame(['Paris' => 120, 'Lyon' => 80], $data['cityValues']);
    }

    public function test_without_a_country_no_city_is_drawn_unless_asked()
    {
        $this->assertNull($this->config(Blade::render('<x-neura::map :controls="false" />'))['cities']);
        $this->assertFalse($this->config(Blade::render('<x-neura::map :controls="false" />'))['zones']);
        $this->assertFalse($this->config(Blade::render('<x-neura::map country="FR" :zones="false" :controls="false" />'))['zones']);
        $this->assertNull($this->config(Blade::render('<x-neura::map country="FR" :cities="false" :controls="false" />'))['cities']);
        $this->assertCount(2, $this->config(Blade::render('<x-neura::map cities :controls="false" />'))['cities']);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $html): array
    {
        $this->assertMatchesRegularExpression('/x-data="neuraMap\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html);
        preg_match('/x-data="neuraMap\(JSON\.parse\(\'(.*?)\'\)\)"/s', $html, $match);

        return json_decode(json_decode('"'.html_entity_decode($match[1]).'"'), true);
    }
}
