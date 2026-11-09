<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Quick look: a viewer over the file manager for pictures, videos, sounds, PDFs and text,
 * stepping from file to file — unless `preview` is off, then opening emits `open`.
 */
class FileManagerPreviewTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_the_viewer_is_a_modal_dialog_with_every_kind_of_content()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-preview', $html);
        $this->assertMatchesRegularExpression('/role="dialog"[^>]*aria-modal="true"[^>]*data-fm-preview|data-fm-preview[^>]*role="dialog"[^>]*aria-modal="true"/s', $html);
        $this->assertMatchesRegularExpression('/<img[^>]*:src="previewEntry\.url"/', $html);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('<audio', $html);
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('x-text="previewText"', $html);
        $this->assertStringContainsString(neura_trans('noPreview'), $html);
    }

    public function test_it_steps_through_the_files_and_lists_them()
    {
        $html = $this->render();

        $this->assertStringContainsString('previewStep(-1)', $html);
        $this->assertStringContainsString('previewStep(1)', $html);
        $this->assertStringContainsString('x-for="(item, index) in previewList"', $html);
        $this->assertStringContainsString('closePreview()', $html);
    }

    public function test_it_can_be_turned_off()
    {
        $html = $this->render(':preview="false"');

        $this->assertStringNotContainsString('data-fm-preview', $html);
        $this->assertStringContainsString('preview: false', $html);
    }
}
