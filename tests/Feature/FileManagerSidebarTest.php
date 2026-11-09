<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The side panel: shortcuts the app fills (Recent, Starred…) and the folder tree, off
 * by default so compact file managers stay as they were.
 */
class FileManagerSidebarTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_the_side_panel_is_off_by_default()
    {
        $this->assertStringNotContainsString('data-fm-sidebar', $this->render());
    }

    public function test_it_lists_the_shortcuts_and_the_folder_tree()
    {
        $html = $this->render('sidebar :shortcuts="[[\'id\' => \'home\', \'label\' => \'Accueil\', \'icon\' => \'home\'], [\'id\' => \'recent\', \'label\' => \'Récents\', \'icon\' => \'clock\']]"');

        $this->assertStringContainsString('data-fm-sidebar', $html);
        $this->assertStringContainsString("pickShortcut('recent')", $html);
        $this->assertStringContainsString('Récents', $html);
        $this->assertStringContainsString('x-for="row in treeRows"', $html);
        $this->assertStringContainsString('toggleFolder(row.id)', $html);
        $this->assertStringContainsString('openFolder(row.path)', $html);
        $this->assertStringContainsString('onTreeDrop($event, row)', $html);
    }

    public function test_without_shortcuts_a_single_home_entry_goes_back_to_the_top()
    {
        $html = $this->render('sidebar');

        $this->assertStringContainsString('goTo(null)', $html);
        $this->assertStringContainsString(neura_trans('home'), $html);
    }
}
