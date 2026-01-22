<?php

namespace Neura\Kit\Tests\Unit\Services\Editor;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Neura\Kit\Services\Editor\ImageStorageService;
use Neura\Kit\Tests\TestCase;
use Psr\Log\LoggerInterface;

class ImageStorageServiceTest extends TestCase
{
    private ImageStorageService $service;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ImageStorageService($this->logger);

        // Setup fake storage
        Storage::fake('public');
        Storage::fake('s3');
    }

    /** @test */
    public function it_stores_image_successfully_on_public_disk()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        Config::set('neura-kit.editor.image_disk', 'public');
        Config::set('neura-kit.editor.image_path', 'editor/images');

        // Act
        $result = $this->service->store($file);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('height', $result);

        $this->assertStringContainsString('editor/images', $result['path']);
        $this->assertStringContainsString('test-image', $result['path']);
        $this->assertNotEmpty($result['url']);
        $this->assertEquals(800, $result['width']);
        $this->assertEquals(600, $result['height']);

        // Verify file was stored
        Storage::disk('public')->assertExists($result['path']);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        // Arrange
        $file1 = UploadedFile::fake()->image('same-name.jpg');
        $file2 = UploadedFile::fake()->image('same-name.jpg');

        // Act
        $result1 = $this->service->store($file1);
        $result2 = $this->service->store($file2);

        // Assert
        $this->assertNotEquals($result1['path'], $result2['path']);
    }

    /** @test */
    public function it_creates_directory_if_not_exists()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        $newPath = 'new/nested/path';
        Config::set('neura-kit.editor.image_path', $newPath);

        // Act
        $result = $this->service->store($file);

        // Assert
        $this->assertStringContainsString($newPath, $result['path']);
        Storage::disk('public')->assertExists($result['path']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_disk()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('not found'));

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not configured');

        // Act
        $this->service->store($file, 'invalid-disk');
    }

    /** @test */
    public function it_handles_images_without_dimensions()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.txt', 100); // Not an image

        // Act
        $result = $this->service->store($file);

        // Assert
        $this->assertNull($result['width']);
        $this->assertNull($result['height']);
    }

    /** @test */
    public function it_stores_on_s3_disk()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        Config::set('filesystems.disks.s3', [
            'driver' => 's3',
            'bucket' => 'test-bucket',
            'region' => 'us-east-1',
            'url' => 'https://test-bucket.s3.us-east-1.amazonaws.com',
        ]);

        // Act
        $result = $this->service->store($file, 's3');

        // Assert
        Storage::disk('s3')->assertExists($result['path']);
        // With Storage::fake(), URLs are generated as local paths
        // In real S3, the URL would contain s3 or bucket name
        $this->assertNotEmpty($result['url']);
        $this->assertNotEmpty($result['path']);
    }

    /** @test */
    public function it_sanitizes_filenames()
    {
        // Arrange
        $file = UploadedFile::fake()->image('Test Image With Spaces & Special!@#.jpg');

        // Act
        $result = $this->service->store($file);

        // Assert
        $filename = basename($result['path']);
        $this->assertStringContainsString('test-image-with-spaces-special', $filename);
        $this->assertStringNotContainsString(' ', $filename);
        $this->assertStringNotContainsString('&', $filename);
    }

    /** @test */
    public function it_logs_warning_for_local_disk()
    {
        // Arrange
        Storage::fake('local');
        $file = UploadedFile::fake()->image('test.jpg');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('local disk'));

        // Act
        $this->service->store($file, 'local');
    }
}
