<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Neura\Kit\Tests\TestCase;

class EditorImageUploadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Config::set('neura-kit.editor.image_disk', 'public');
        Config::set('neura-kit.editor.image_path', 'editor/images');
        Config::set('neura-kit.editor.max_image_size', 10240);
    }

    /** @test */
    public function it_uploads_image_successfully()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        // Act
        $response = $this->postJson('/neura-kit/editor/upload-image', [
            'image' => $file,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'file' => ['url', 'width', 'height'],
                'url',
                'width',
                'height',
            ])
            ->assertJson([
                'success' => 1,
                'width' => 800,
                'height' => 600,
            ]);

        // Verify file was stored
        $files = Storage::disk('public')->files('editor/images');
        $this->assertNotEmpty($files);
    }

    /** @test */
    public function it_validates_required_image()
    {
        // Act
        $response = $this->postJson('/neura-kit/editor/upload-image', []);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => 0,
            ])
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function it_validates_file_is_image()
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Act
        $response = $this->postJson('/neura-kit/editor/upload-image', [
            'image' => $file,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => 0,
            ]);
    }

    /** @test */
    public function it_validates_image_mime_types()
    {
        // Arrange
        $file = UploadedFile::fake()->create('image.bmp', 100, 'image/bmp');

        // Act
        $response = $this->postJson('/neura-kit/editor/upload-image', [
            'image' => $file,
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_maximum_file_size()
    {
        // Arrange
        Config::set('neura-kit.editor.max_image_size', 100); // 100KB
        $file = UploadedFile::fake()->image('large.jpg')->size(200); // 200KB

        // Act
        $response = $this->postJson('/neura-kit/editor/upload-image', [
            'image' => $file,
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function it_accepts_valid_image_formats()
    {
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($formats as $format) {
            // Arrange
            $file = UploadedFile::fake()->image("test.{$format}");

            // Act
            $response = $this->postJson('/neura-kit/editor/upload-image', [
                'image' => $file,
            ]);

            // Assert
            $response->assertStatus(200)
                ->assertJson(['success' => 1]);
        }
    }

    /** @test */
    public function it_returns_image_dimensions()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg', 1920, 1080);

        // Act
        $response = $this->postJson('/neura-kit/editor/upload-image', [
            'image' => $file,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => 1,
                'width' => 1920,
                'height' => 1080,
            ]);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        // Arrange
        $file1 = UploadedFile::fake()->image('same.jpg');
        $file2 = UploadedFile::fake()->image('same.jpg');

        // Act
        $response1 = $this->postJson('/neura-kit/editor/upload-image', ['image' => $file1]);
        $response2 = $this->postJson('/neura-kit/editor/upload-image', ['image' => $file2]);

        // Assert
        $this->assertNotEquals(
            $response1->json('url'),
            $response2->json('url')
        );
    }

    /** @test */
    public function it_fetches_url_metadata()
    {
        // Act
        $response = $this->postJson('/neura-kit/editor/fetch-url', [
            'url' => 'https://example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'meta' => [
                    'title',
                    'description',
                    'image' => ['url'],
                ],
            ])
            ->assertJson([
                'success' => 1,
            ]);
    }

    /** @test */
    public function it_validates_url_format()
    {
        // Act
        $response = $this->postJson('/neura-kit/editor/fetch-url', [
            'url' => 'not-a-valid-url',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => 0,
            ]);
    }

    /** @test */
    public function it_extracts_domain_as_title()
    {
        // Act
        $response = $this->postJson('/neura-kit/editor/fetch-url', [
            'url' => 'https://github.com/laravel/laravel',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => 1,
                'meta' => [
                    'title' => 'github.com',
                ],
            ]);
    }
}
