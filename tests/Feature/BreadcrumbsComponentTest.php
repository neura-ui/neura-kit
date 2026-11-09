<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Neura\Kit\NeuraKitConfig;
use Neura\Kit\Tests\TestCase;

/**
 * The breadcrumbs are a nav list whose last item is the current page; the middle of a long
 * trail folds into "…", and a responsive trail keeps a back link for narrow screens. Look,
 * size and folding come from config('neura-kit.breadcrumbs').
 */
class BreadcrumbsComponentTest extends TestCase
{
    private function trail(string $props = '', int $count = 4): string
    {
        $labels = ['Home', 'Advertisers', 'Demo Advertiser', 'Campaigns', 'Car Insurance', 'Ads', 'Variant B'];
        $items = '';
        foreach (array_slice($labels, 0, $count) as $i => $label) {
            $href = $i < $count - 1 ? ' href="/'.$i.'"' : '';
            $items .= "<x-neura::breadcrumbs.item{$href}>{$label}</x-neura::breadcrumbs.item>\n";
        }

        return Blade::render("<x-neura::breadcrumbs {$props}>\n{$items}</x-neura::breadcrumbs>");
    }

    public function test_a_trail_is_a_nav_list_ending_on_the_current_page()
    {
        $html = $this->trail(':responsive="false"', 3);

        $this->assertStringContainsString('<nav', $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertSame(3, substr_count($html, '<li data-bc-item'));
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/<span\s+data-bc-target aria-current="page"[^>]*>\s*<span class="nk-bc-label">Advertisers|Demo Advertiser/s', $html);
        $this->assertStringContainsString('data-bc-index="2"', $html);
        // Nothing to fold and no script asked for: plain markup.
        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('data-bc-more', $html);
    }

    public function test_an_item_can_be_the_current_page_itself()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::breadcrumbs :responsive="false">
                <x-neura::breadcrumbs.item href="/a">A</x-neura::breadcrumbs.item>
                <x-neura::breadcrumbs.item href="/b" current>B</x-neura::breadcrumbs.item>
                <x-neura::breadcrumbs.item href="/c">C</x-neura::breadcrumbs.item>
            </x-neura::breadcrumbs>
        BLADE);

        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/href="\/b"\s+data-bc-target\s+aria-current="page"/', $html);
    }

    public function test_max_items_folds_the_middle_into_a_menu()
    {
        $html = $this->trail(':max-items="4"', 7);

        $this->assertStringContainsString('x-data="neuraBreadcrumbs(', $html);
        $this->assertSame(4, substr_count($html, 'data-bc-folded hidden'));
        $this->assertStringContainsString('data-bc-more', $html);
        // "…" comes right after the first item, its menu lists the four folded levels.
        $this->assertLessThan(strpos($html, 'data-bc-index="1"'), strpos($html, 'data-bc-more'));
        $this->assertGreaterThan(strpos($html, 'data-bc-index="0"'), strpos($html, 'data-bc-more'));
        $this->assertSame(4, substr_count($html, 'x-show="hidden.includes('));
        $this->assertStringContainsString('href="/1" class="nk-bc-option"', $html);
    }

    public function test_a_short_trail_is_not_folded()
    {
        $html = $this->trail(':max-items="4" :responsive="false"', 4);

        $this->assertStringNotContainsString('data-bc-folded', $html);
        $this->assertStringNotContainsString('data-bc-more', $html);
    }

    public function test_a_responsive_trail_has_a_back_link_to_the_parent()
    {
        $html = $this->trail('', 5);

        $this->assertStringContainsString('data-responsive', $html);
        $this->assertMatchesRegularExpression('/<a href="\/3" class="nk-bc-back" data-bc-back>.*Campaigns/s', $html);
        // The "…" item waits, hidden, for the script to fold the trail.
        $this->assertMatchesRegularExpression('/data-bc-more data-sep="chevron"\s+hidden/', $html);
    }

    public function test_variant_separator_colour_and_truncation()
    {
        $html = $this->trail('variant="steps" separator="slash" color="indigo" tinted truncate="10rem" :responsive="false"', 3);

        $this->assertStringContainsString('data-variant="steps"', $html);
        $this->assertSame(3, substr_count($html, 'data-sep="slash"'));
        $this->assertStringContainsString('--nk-bc-tone: var(--color-indigo-500, var(--color-indigo))', $html);
        $this->assertStringContainsString('data-tinted', $html);
        $this->assertStringContainsString('--nk-bc-truncate: 10rem', $html);
        // Cut labels get a tooltip that opens only when they are cut.
        $this->assertStringContainsString('neuraTooltip(', $html);
        $this->assertStringContainsString('\\u0022overflow\\u0022:true', $html);
    }

    public function test_an_icon_separator_and_the_old_item_api()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::breadcrumbs separator="minus" :responsive="false">
                <x-neura::breadcrumbs.item href="/" icon="home">Home</x-neura::breadcrumbs.item>
                <x-neura::breadcrumbs.item separator="slash" href="/p" class="font-mono">Projects</x-neura::breadcrumbs.item>
                <x-neura::breadcrumbs.item>Atlas</x-neura::breadcrumbs.item>
            </x-neura::breadcrumbs>
        BLADE);

        $this->assertSame(2, substr_count($html, 'data-sep="custom"'));
        $this->assertSame(1, substr_count($html, 'data-sep="slash"'));
        $this->assertStringContainsString('class="nk-bc-sep"', $html);
        $this->assertMatchesRegularExpression('/href="\/p"\s+data-bc-target\s+class="font-mono nk-bc-link"|class="nk-bc-link font-mono"/', $html);
    }

    public function test_an_item_switches_to_its_neighbours()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::breadcrumbs :responsive="false">
                <x-neura::breadcrumbs.item href="/c/1" dot="emerald" :options="[
                    ['label' => 'Car Insurance', 'href' => '/c/1', 'dot' => 'emerald'],
                    ['label' => 'Solar Panels', 'href' => '/c/2', 'meta' => 'Paused'],
                ]">Car Insurance</x-neura::breadcrumbs.item>
                <x-neura::breadcrumbs.item>Ads</x-neura::breadcrumbs.item>
            </x-neura::breadcrumbs>
        BLADE);

        $this->assertStringContainsString('class="nk-bc-switch"', $html);
        $this->assertStringContainsString('aria-label="Switch Car Insurance"', $html);
        $this->assertSame(2, substr_count($html, 'class="nk-bc-option"'));
        $this->assertMatchesRegularExpression('/href="\/c\/1"\s+class="nk-bc-option"\s+aria-current="true"/', $html);
        $this->assertStringContainsString('Paused', $html);
        // Two options need no search field.
        $this->assertStringNotContainsString('nk-bc-search', $html);
        // The trail still reads its items: only the last one is the current page.
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_config_sets_the_defaults()
    {
        Config::set('neura-kit.breadcrumbs', NeuraKitConfig::breadcrumbs(['default' => [
            'variant' => 'pills', 'separator' => 'dot', 'size' => 'lg', 'color' => 'rose', 'rounded' => 'sm', 'maxItems' => 3, 'responsive' => false,
        ]]));
        app()->setLocale('fr');
        $html = $this->trail('', 5);

        $this->assertStringContainsString('data-variant="pills"', $html);
        $this->assertStringContainsString('data-sep="dot"', $html);
        $this->assertStringContainsString('text-base', $html);
        $this->assertStringContainsString('--nk-bc-chip: 2.125rem', $html);
        $this->assertStringContainsString('--nk-bc-radius: var(--radius-sm)', $html);
        $this->assertStringContainsString('--nk-bc-tone: var(--color-rose-500, var(--color-rose))', $html);
        $this->assertSame(2, substr_count($html, 'data-bc-folded hidden'));
        $this->assertStringContainsString('aria-label="Fil d’Ariane"', html_entity_decode($html));
        $this->assertStringNotContainsString('nk-bc-back', $html);
    }
}
