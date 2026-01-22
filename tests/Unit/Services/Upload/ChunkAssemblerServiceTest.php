<?php

namespace Neura\Kit\Tests\Unit\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Neura\Kit\Services\Upload\ChunkAssemblerService;
use Neura\Kit\Tests\TestCase;
use Psr\Log\LoggerInterface;

class ChunkAssemblerServiceTest extends TestCase
{
    private ChunkAssemblerService $service;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ChunkAssemblerService($this->logger);

        Storage::fake('local');
    }

    /** @test */
    public function it_assembles_chunks_successfully()
    {
        // Arrange
        $uuid = Str::uuid()->toString();
        $chunkDir = "livewire-tmp/chunks/{$uuid}";
        $totalChunks = 3;

        // Create fake chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            Storage::disk('local')->put(
                "{$chunkDir}/chunk_{$i}",
                "chunk-content-{$i}"
            );
        }

        // Act
        $result = $this->service->assemble(
            'local',
            $chunkDir,
            'test-file.txt',
            $totalChunks,
            $uuid
        );

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('test-file.txt', $result['filename']);
        $this->assertStringContainsString('livewire-tmp', $result['path']);
        $this->assertGreaterThan(0, $result['size']);
        $this->assertNotEmpty($result['mime']);

        // Verify chunks were cleaned up
        $this->assertFalse(Storage::disk('local')->exists($chunkDir));

        // Verify final file exists
        $this->assertTrue(Storage::disk('local')->exists($result['path']));
        $this->assertTrue(Storage::disk('local')->exists($result['path'] . '.meta'));
    }

    /** @test */
    public function it_throws_exception_when_chunk_is_missing()
    {
        // Arrange
        $uuid = Str::uuid()->toString();
        $chunkDir = "livewire-tmp/chunks/{$uuid}";
        $totalChunks = 3;

        // Create only 2 chunks (missing chunk_2)
        Storage::disk('local')->put("{$chunkDir}/chunk_0", "content-0");
        Storage::disk('local')->put("{$chunkDir}/chunk_1", "content-1");

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Chunk 2 is missing');

        // Act
        $this->service->assemble('local', $chunkDir, 'test.txt', $totalChunks, $uuid);
    }

    /** @test */
    public function it_cleans_up_chunks_after_assembly()
    {
        // Arrange
        $uuid = Str::uuid()->toString();
        $chunkDir = "livewire-tmp/chunks/{$uuid}";

        Storage::disk('local')->put("{$chunkDir}/chunk_0", "content");

        // Act
        $this->service->assemble('local', $chunkDir, 'test.txt', 1, $uuid);

        // Assert
        $this->assertFalse(Storage::disk('local')->exists($chunkDir));
    }

    /** @test */
    public function it_cleans_up_on_error()
    {
        // Arrange
        $uuid = Str::uuid()->toString();
        $chunkDir = "livewire-tmp/chunks/{$uuid}";

        Storage::disk('local')->put("{$chunkDir}/chunk_0", "content");

        try {
            // Act - missing chunk will cause error
            $this->service->assemble('local', $chunkDir, 'test.txt', 2, $uuid);
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Assert - chunks should be cleaned up
        $this->assertFalse(Storage::disk('local')->exists($chunkDir));
    }

    /** @test */
    public function it_creates_metadata_file()
    {
        // Arrange
        $uuid = Str::uuid()->toString();
        $chunkDir = "livewire-tmp/chunks/{$uuid}";

        Storage::disk('local')->put("{$chunkDir}/chunk_0", "test content");

        // Act
        $result = $this->service->assemble('local', $chunkDir, 'test.txt', 1, $uuid);

        // Assert
        $metaPath = $result['path'] . '.meta';
        $this->assertTrue(Storage::disk('local')->exists($metaPath));

        $metadata = json_decode(Storage::disk('local')->get($metaPath), true);
        $this->assertEquals('test.txt', $metadata['filename']);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime', $metadata);
        $this->assertArrayHasKey('uploaded_at', $metadata);
    }

    /** @test */
    public function it_assembles_chunks_in_correct_order()
    {
        // Arrange
        $uuid = Str::uuid()->toString();
        $chunkDir = "livewire-tmp/chunks/{$uuid}";

        // Create chunks with specific content
        Storage::disk('local')->put("{$chunkDir}/chunk_0", "FIRST");
        Storage::disk('local')->put("{$chunkDir}/chunk_1", "SECOND");
        Storage::disk('local')->put("{$chunkDir}/chunk_2", "THIRD");

        // Act
        $result = $this->service->assemble('local', $chunkDir, 'test.txt', 3, $uuid);

        // Assert
        $content = Storage::disk('local')->get($result['path']);
        $this->assertEquals('FIRSTSECONDTHIRD', $content);
    }
}
