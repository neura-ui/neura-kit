<?php

namespace Neura\Kit\Tests\Unit\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Neura\Kit\Support\ChunkedTemporaryFile;
use Neura\Kit\Tests\TestCase;

class ChunkedTemporaryFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['livewire.temporary_file_upload.disk' => 'local']);
        Storage::disk('local')->deleteDirectory('livewire-tmp');
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('livewire-tmp');
        parent::tearDown();
    }

    /** @test */
    public function it_creates_temporary_file_from_chunk_upload()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();
        $fileName = 'test-document.pdf';

        // Simule un fichier uploadé par chunks
        $this->createFakeChunkUpload($uuid, $fileName);

        // Crée le TemporaryUploadedFile
        $file = ChunkedTemporaryFile::createFromChunkUpload($uuid);

        $this->assertInstanceOf(TemporaryUploadedFile::class, $file);
        // Le nom du fichier dans Livewire est basé sur l'UUID, pas le nom original
        $this->assertNotEmpty($file->getClientOriginalName());
        // Mais on peut vérifier que le fichier existe
        $this->assertTrue(Storage::disk('local')->exists("livewire-tmp/{$uuid}"));
    }

    /** @test */
    public function it_returns_null_for_non_existent_uuid()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        $file = ChunkedTemporaryFile::createFromChunkUpload($uuid);

        $this->assertNull($file);
    }

    /** @test */
    public function it_returns_null_when_metadata_is_missing()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Crée le fichier mais pas les métadonnées
        Storage::disk('local')->put("livewire-tmp/{$uuid}", 'file content');

        $file = ChunkedTemporaryFile::createFromChunkUpload($uuid);

        $this->assertNull($file);
    }

    /** @test */
    public function it_creates_multiple_temporary_files_from_uuids()
    {
        $uuid1 = \Illuminate\Support\Str::uuid()->toString();
        $uuid2 = \Illuminate\Support\Str::uuid()->toString();

        $this->createFakeChunkUpload($uuid1, 'file1.pdf');
        $this->createFakeChunkUpload($uuid2, 'file2.pdf');

        $files = ChunkedTemporaryFile::createMultipleFromChunkUpload([$uuid1, $uuid2]);

        $this->assertCount(2, $files);
        $this->assertContainsOnlyInstancesOf(TemporaryUploadedFile::class, $files);
    }

    /** @test */
    public function it_filters_out_invalid_uuids_when_creating_multiple()
    {
        $validUuid = \Illuminate\Support\Str::uuid()->toString();
        $invalidUuid = \Illuminate\Support\Str::uuid()->toString();

        $this->createFakeChunkUpload($validUuid, 'file.pdf');
        // Ne crée pas de fichier pour invalidUuid

        $files = ChunkedTemporaryFile::createMultipleFromChunkUpload([$validUuid, $invalidUuid]);

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_cleans_up_expired_files()
    {
        $oldUuid = \Illuminate\Support\Str::uuid()->toString();
        $recentUuid = \Illuminate\Support\Str::uuid()->toString();

        // Crée un vieux fichier (plus de 24h)
        $this->createFakeChunkUpload($oldUuid, 'old-file.pdf', now()->subDays(2)->timestamp);

        // Crée un fichier récent
        $this->createFakeChunkUpload($recentUuid, 'recent-file.pdf', now()->timestamp);

        // Nettoie les fichiers de plus de 24h (1440 minutes)
        $cleaned = ChunkedTemporaryFile::cleanup(1440);

        $this->assertEquals(1, $cleaned);
        $this->assertFalse(Storage::disk('local')->exists("livewire-tmp/{$oldUuid}"));
        $this->assertTrue(Storage::disk('local')->exists("livewire-tmp/{$recentUuid}"));
    }

    /** @test */
    public function it_returns_zero_when_no_files_to_cleanup()
    {
        $cleaned = ChunkedTemporaryFile::cleanup(1440);

        $this->assertEquals(0, $cleaned);
    }

    /** @test */
    public function it_cleans_up_files_based_on_custom_age()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Crée un fichier de 2h
        $this->createFakeChunkUpload($uuid, 'file.pdf', now()->subHours(2)->timestamp);

        // Nettoie les fichiers de plus de 1h (60 minutes)
        $cleaned = ChunkedTemporaryFile::cleanup(60);

        $this->assertEquals(1, $cleaned);
        $this->assertFalse(Storage::disk('local')->exists("livewire-tmp/{$uuid}"));
    }

    /**
     * Helper pour créer un faux upload par chunks
     */
    protected function createFakeChunkUpload(string $uuid, string $fileName, ?int $timestamp = null): void
    {
        $disk = 'local';
        $path = "livewire-tmp/{$uuid}";

        // Crée le fichier
        $content = 'Fake file content for testing';
        Storage::disk($disk)->put($path, $content);

        // Crée les métadonnées
        $metadata = [
            'filename' => $fileName,
            'size' => strlen($content),
            'mime' => 'application/pdf',
            'path' => $path,
            'uploaded_at' => $timestamp ?? now()->timestamp,
        ];

        Storage::disk($disk)->put("{$path}.meta", json_encode($metadata));
    }
}
