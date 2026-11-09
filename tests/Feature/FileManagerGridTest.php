<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The grid: folders first as compact cards, files as cards with a preview — a picture,
 * a video still with its length, or a tinted tile — and a slider for the card size.
 */
class FileManagerGridTest extends TestCase
{
    private function render(string $props = ''): string
    {
        return html_entity_decode(Blade::render("<x-neura::file-manager {$props} />"));
    }

    public function test_thumb_size_reaches_the_script_kept_between_one_and_five()
    {
        $this->assertStringContainsString('thumbSize: 5', $this->render('thumb-size="5"'));
        $this->assertStringContainsString('thumbSize: 5', $this->render('thumb-size="9"'));
        $this->assertStringContainsString('thumbSize: 1', $this->render('thumb-size="0"'));
        $this->assertStringContainsString('thumbSize: 3', $this->render());
    }

    public function test_the_size_slider_shows_in_grid_view()
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<input[^>]*type="range"[^>]*min="1"[^>]*max="5"/', $html);
        $this->assertStringContainsString('x-model.number="thumbSize"', $html);
        $this->assertStringContainsString("x-show=\"view === 'grid'\"", $html);
    }

    public function test_folders_and_files_are_laid_out_apart()
    {
        $html = $this->render();

        $this->assertStringContainsString('x-for="entry in folderEntries"', $html);
        $this->assertStringContainsString('x-for="entry in fileEntries"', $html);
    }

    public function test_file_cards_preview_pictures_lazily_and_say_how_long_a_video_is()
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<img[^>]*:src="entry\.preview\.src"[^>]*loading="lazy"/', $html);
        $this->assertStringContainsString('x-text="entry.preview.duration"', $html);
        $this->assertStringContainsString('x-text="entry.badge"', $html);
    }
}
