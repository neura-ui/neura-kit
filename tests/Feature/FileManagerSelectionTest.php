<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * A selection gets a floating bar of actions, can be moved to a folder picked from the
 * tree, copied or cut and pasted; the menu offers the same.
 */
class FileManagerSelectionTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_the_selection_floats_a_bar_of_actions()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-selection-bar', $html);
        $this->assertMatchesRegularExpression('/data-fm-selection-bar[^>]*role="toolbar"|role="toolbar"[^>]*data-fm-selection-bar/', $html);
        $this->assertStringContainsString("action('download')", $html);
        $this->assertStringContainsString('movePicker = true', $html);
        $this->assertStringContainsString('startRename(selectedEntries[0])', $html);
        $this->assertStringContainsString("action('delete')", $html);
    }

    public function test_the_bar_follows_what_the_file_manager_allows()
    {
        $html = $this->render(':downloadable="false" :deletable="false" :renamable="false"');

        $this->assertStringNotContainsString("action('download')", $html);
        $this->assertStringNotContainsString("action('delete')", $html);
        $this->assertStringNotContainsString('startRename(selectedEntries[0])', $html);
    }

    public function test_the_menu_offers_preview_copy_cut_and_paste()
    {
        $html = $this->render();

        $this->assertStringContainsString("runMenu('preview')", $html);
        $this->assertStringContainsString('copySelection()', $html);
        $this->assertStringContainsString('cutSelection()', $html);
        $this->assertStringContainsString('paste()', $html);
        $this->assertStringContainsString('x-show="clipboard"', $html);
    }

    public function test_moving_picks_a_folder_from_the_tree()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-move-picker', $html);
        $this->assertStringContainsString('moveTo(row.id)', $html);
        $this->assertStringContainsString('moveTo(null)', $html);
    }
}
