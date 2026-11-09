<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Upload opens the browser's file picker and a panel follows each file; the app reports
 * progress back. `upload-picker` off keeps the old `upload-request` event.
 */
class FileManagerUploadsTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_upload_opens_the_file_picker_by_default()
    {
        $html = $this->render();

        $this->assertStringContainsString('x-on:click="pickFiles()"', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*type="file"[^>]*multiple/', $html);
        $this->assertStringContainsString('startUploads(', $html);
    }

    public function test_upload_picker_off_keeps_the_old_event()
    {
        $html = $this->render(':upload-picker="false"');

        $this->assertStringContainsString("\$dispatch('file-manager:upload-request', { path: [...path] })", $html);
        $this->assertStringNotContainsString('x-on:click="pickFiles()"', $html);
    }

    public function test_the_panel_follows_each_file_and_speaks_up()
    {
        $html = $this->render();

        $this->assertStringContainsString('data-fm-uploads', $html);
        $this->assertMatchesRegularExpression('/data-fm-uploads[^>]*aria-live="polite"|aria-live="polite"[^>]*data-fm-uploads/s', $html);
        $this->assertStringContainsString('x-for="upload in uploads"', $html);
        $this->assertStringContainsString('cancelUpload(upload.id)', $html);
        $this->assertStringContainsString('retryUpload(upload.id)', $html);
    }
}
