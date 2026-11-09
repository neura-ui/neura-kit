<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Starring files (off by default), and the narrow layout: a "+" button, a long press to
 * select, the selection's actions on top.
 */
class FileManagerCompactTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_stars_are_off_by_default()
    {
        $html = $this->render();

        $this->assertStringNotContainsString('toggleStar(', $html);
    }

    public function test_starrable_files_get_a_star_and_menu_entries()
    {
        $html = $this->render('starrable');

        $this->assertStringContainsString('toggleStar(entry)', $html);
        $this->assertStringContainsString(':aria-pressed="(!!entry.starred).toString()"', $html);
        $this->assertStringContainsString(neura_trans('addToFavorites'), $html);
        $this->assertStringContainsString(neura_trans('removeFromFavorites'), $html);
    }

    public function test_narrow_layout_has_an_add_button_and_long_press_selection()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-fab', $html);
        $this->assertStringContainsString('onPressStart($event, entry)', $html);
        $this->assertStringContainsString('data-fm-touch-bar', $html);
        $this->assertStringContainsString('searchExpanded', $html);
    }
}
