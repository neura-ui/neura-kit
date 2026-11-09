<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Names change where they are: F2 or Rename turns a name into a field, and a new folder
 * starts as a row already being named — unless `inline-create` is off.
 */
class FileManagerRenameTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_new_folder_is_named_in_place_by_default()
    {
        $html = $this->render();

        $this->assertStringContainsString('x-on:click="startCreate()"', $html);
        $this->assertStringContainsString('inlineCreate: true', $html);
    }

    public function test_inline_create_off_keeps_the_old_event()
    {
        $html = $this->render(':inline-create="false"');

        $this->assertStringContainsString("\$dispatch('file-manager:new-folder', { path: [...path] })", $html);
        $this->assertStringNotContainsString('x-on:click="startCreate()"', $html);
    }

    public function test_the_name_field_says_what_is_wrong()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-name-input', $html);
        $this->assertStringContainsString(':aria-invalid="(editing && editing.error) ? \'true\' : \'false\'"', $html);
        $this->assertStringContainsString('x-text="nameErrorText()"', $html);
        $this->assertStringContainsString('commitName()', $html);
        $this->assertStringContainsString('cancelName()', $html);
    }
}
