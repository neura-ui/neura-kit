<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The list view: a badge per type, a Type column, dates said the way people say them
 * with the full date on hover, and a "…" button on each row for its actions.
 */
class FileManagerListTest extends TestCase
{
    private function render(): string
    {
        return html_entity_decode(Blade::render('<x-neura::file-manager />'));
    }

    public function test_rows_carry_a_type_badge_and_a_type_column()
    {
        $html = $this->render();

        $this->assertStringContainsString('nk-fm-badge', $html);
        $this->assertStringContainsString(':data-kind="entry.kind"', $html);
        $this->assertStringContainsString('x-text="entry.typeLabel"', $html);
        $this->assertStringContainsString("x-on:click=\"sortBy('kind')\"", $html);
    }

    public function test_dates_are_relative_with_the_full_date_on_hover()
    {
        $html = $this->render();

        $this->assertStringContainsString('x-text="entry.modifiedLabel"', $html);
        $this->assertStringContainsString(':title="entry.modifiedTitle"', $html);
    }

    public function test_each_row_has_a_more_actions_button()
    {
        $html = $this->render();

        $this->assertStringContainsString('openMenuAt(entry, $el)', $html);
        $this->assertStringContainsString(neura_trans('moreActions'), $html);
    }
}
