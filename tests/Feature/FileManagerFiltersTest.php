<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Chips filter the open folder by type, and the search says where it looks in words.
 */
class FileManagerFiltersTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_type_chips_are_on_by_default()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-filters', $html);
        $this->assertStringContainsString('x-for="group in filterGroups"', $html);
        $this->assertStringContainsString('setKindFilter(null)', $html);
        $this->assertStringContainsString(":aria-pressed=\"(kindFilter === null).toString()\"", $html);
    }

    public function test_type_chips_can_be_turned_off()
    {
        $this->assertStringNotContainsString('data-fm-filters', $this->render(':filters="false"'));
        $this->assertStringContainsString('filters: false', $this->render(':filters="false"'));
    }

    public function test_the_search_scope_is_said_in_words()
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<button[^>]*setScope\(\'folder\'\)[^>]*>\s*'.preg_quote(neura_trans('inThisFolder'), '/').'\s*<\/button>/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*setScope\(\'all\'\)[^>]*>\s*'.preg_quote(neura_trans('everywhere'), '/').'\s*<\/button>/', $html);
    }
}
