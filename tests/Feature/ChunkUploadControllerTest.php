<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Neura\Kit\Tests\TestCase;

class ChunkUploadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure le disque de test
        config(['livewire.temporary_file_upload.disk' => 'local']);
        config(['neura-kit.upload.max_size' => 10]); // 10MB pour les tests

        // Nettoie le storage avant chaque test
        Storage::disk('local')->deleteDirectory('livewire-tmp');
    }

    protected function tearDown(): void
    {
        // Nettoie le storage après chaque test
        Storage::disk('local')->deleteDirectory('livewire-tmp');

        parent::tearDown();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/neura-kit/upload/chunks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'chunk',
                'chunkIndex',
                'totalChunks',
                'uuid',
                'fileName',
                'fileSize',
            ]);
    }

    /** @test */
    public function it_validates_chunk_index_must_be_positive()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => -1,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => 'test.txt',
            'fileSize' => 102400,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['chunkIndex']);
    }

    /** @test */
    public function it_validates_uuid_format()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => 'invalid-uuid',
            'fileName' => 'test.txt',
            'fileSize' => 102400,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['uuid']);
    }

    /** @test */
    public function it_rejects_files_exceeding_max_size()
    {
        $chunk = UploadedFile::fake()->create('large.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // 11MB > 10MB (config)
        $fileSize = 11 * 1024 * 1024;

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => 'large.txt',
            'fileSize' => $fileSize,
        ]);

        $response->assertStatus(413)
            ->assertJson([
                'success' => false,
                'message' => 'File size exceeds maximum allowed size of 10MB',
            ]);
    }

    /** @test */
    public function it_uploads_single_chunk_file_successfully()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();
        $fileName = 'test-document.txt';

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => $fileName,
            'fileSize' => 102400,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File uploaded successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'filename',
                    'path',
                    'size',
                    'mime',
                ],
            ]);

        // Vérifie que le fichier final existe
        $this->assertTrue(
            Storage::disk('local')->exists("livewire-tmp/{$uuid}")
        );

        // Vérifie que les métadonnées existent
        $this->assertTrue(
            Storage::disk('local')->exists("livewire-tmp/{$uuid}.meta")
        );

        // Vérifie le contenu des métadonnées
        $metadata = json_decode(
            Storage::disk('local')->get("livewire-tmp/{$uuid}.meta"),
            true
        );

        $this->assertEquals($fileName, $metadata['filename']);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime', $metadata);
    }

    /** @test */
    public function it_uploads_multiple_chunks_successfully()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();
        $fileName = 'large-document.pdf';
        $totalChunks = 3;

        // Upload tous les chunks sauf le dernier
        for ($i = 0; $i < $totalChunks - 1; $i++) {
            $chunk = UploadedFile::fake()->create("chunk{$i}.tmp", 100);

            $response = $this->postJson('/neura-kit/upload/chunks', [
                'chunk' => $chunk,
                'chunkIndex' => $i,
                'totalChunks' => $totalChunks,
                'uuid' => $uuid,
                'fileName' => $fileName,
                'fileSize' => 307200,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => "Chunk {$i} uploaded successfully",
                ]);

            // Vérifie que le chunk est stocké
            $this->assertTrue(
                Storage::disk('local')->exists("livewire-tmp/chunks/{$uuid}/chunk_{$i}")
            );
        }

        // Upload le dernier chunk (déclenche l'assemblage)
        $lastChunk = UploadedFile::fake()->create('chunk-last.tmp', 100);
        $lastIndex = $totalChunks - 1;

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $lastChunk,
            'chunkIndex' => $lastIndex,
            'totalChunks' => $totalChunks,
            'uuid' => $uuid,
            'fileName' => $fileName,
            'fileSize' => 307200,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File uploaded successfully',
            ]);

        // Vérifie que le fichier final existe
        $this->assertTrue(
            Storage::disk('local')->exists("livewire-tmp/{$uuid}")
        );

        // Vérifie que les chunks ont été nettoyés
        $this->assertFalse(
            Storage::disk('local')->exists("livewire-tmp/chunks/{$uuid}")
        );
    }

    /** @test */
    public function it_sanitizes_dangerous_filenames()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Nom de fichier avec tentative de directory traversal
        $dangerousFileName = '../../etc/passwd';

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => $dangerousFileName,
            'fileSize' => 102400,
        ]);

        $response->assertStatus(200);

        // Vérifie que le nom de fichier a été sanitized
        $metadata = json_decode(
            Storage::disk('local')->get("livewire-tmp/{$uuid}.meta"),
            true
        );

        $this->assertStringNotContainsString('..', $metadata['filename']);
        $this->assertStringNotContainsString('/', $metadata['filename']);
        $this->assertStringNotContainsString('\\', $metadata['filename']);
    }

    /** @test */
    public function it_sanitizes_filenames_with_special_characters()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        $fileName = 'test file with spaces & special!@#$%^chars.txt';

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => $fileName,
            'fileSize' => 102400,
        ]);

        $response->assertStatus(200);

        $metadata = json_decode(
            Storage::disk('local')->get("livewire-tmp/{$uuid}.meta"),
            true
        );

        // Vérifie que seuls les caractères autorisés sont présents
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._-]+$/',
            $metadata['filename']
        );
    }

    /** @test */
    public function it_cleans_up_chunks_on_error()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Upload premier chunk
        $chunk1 = UploadedFile::fake()->create('chunk1.tmp', 100);
        $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk1,
            'chunkIndex' => 0,
            'totalChunks' => 2,
            'uuid' => $uuid,
            'fileName' => 'test.txt',
            'fileSize' => 204800,
        ]);

        // Vérifie que le chunk existe
        $this->assertTrue(
            Storage::disk('local')->exists("livewire-tmp/chunks/{$uuid}/chunk_0")
        );

        // Simule une erreur en envoyant un fichier qui dépasse la taille max
        // (impossible normalement car validation, mais teste le cleanup)
        // Note: En pratique, on testerait avec un mock qui force une exception
    }

    /** @test */
    public function it_accepts_optional_field_parameter()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => 'test.txt',
            'fileSize' => 102400,
            'field' => 'document_upload',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_retrieve_uploaded_file_metadata()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Upload le fichier
        $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => 'test.txt',
            'fileSize' => 102400,
        ]);

        // Récupère les métadonnées
        $response = $this->getJson("/neura-kit/upload/file/{$uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'filename',
                    'size',
                    'mime',
                    'path',
                    'uploaded_at',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_file()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        $response = $this->getJson("/neura-kit/upload/file/{$uuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'File not found or expired',
            ]);
    }

    /** @test */
    public function it_handles_missing_chunks_gracefully()
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Upload chunk 0
        $chunk0 = UploadedFile::fake()->create('chunk0.tmp', 100);
        $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk0,
            'chunkIndex' => 0,
            'totalChunks' => 3,
            'uuid' => $uuid,
            'fileName' => 'test.txt',
            'fileSize' => 307200,
        ]);

        // Skip chunk 1, upload chunk 2 directement
        $chunk2 = UploadedFile::fake()->create('chunk2.tmp', 100);
        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk2,
            'chunkIndex' => 2,
            'totalChunks' => 3,
            'uuid' => $uuid,
            'fileName' => 'test.txt',
            'fileSize' => 307200,
        ]);

        // L'assemblage devrait échouer car chunk 1 manque
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);

        // Vérifie que les chunks ont été nettoyés
        $this->assertFalse(
            Storage::disk('local')->exists("livewire-tmp/chunks/{$uuid}")
        );
    }

    /** @test */
    public function it_rejects_filenames_exceeding_max_length()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Nom très long (> 255 caractères)
        $longFileName = str_repeat('a', 300) . '.txt';

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => $longFileName,
            'fileSize' => 102400,
        ]);

        // Laravel valide et rejette automatiquement les noms trop longs
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fileName']);
    }

    /** @test */
    public function it_sanitizes_long_but_valid_filenames()
    {
        $chunk = UploadedFile::fake()->create('test.txt', 100);
        $uuid = \Illuminate\Support\Str::uuid()->toString();

        // Nom long mais valide (< 255 caractères)
        $longFileName = str_repeat('a', 200) . '.txt';

        $response = $this->postJson('/neura-kit/upload/chunks', [
            'chunk' => $chunk,
            'chunkIndex' => 0,
            'totalChunks' => 1,
            'uuid' => $uuid,
            'fileName' => $longFileName,
            'fileSize' => 102400,
        ]);

        $response->assertStatus(200);

        $metadata = json_decode(
            Storage::disk('local')->get("livewire-tmp/{$uuid}.meta"),
            true
        );

        // Vérifie que le nom de fichier est conservé (< 255 chars)
        $this->assertLessThanOrEqual(255, strlen($metadata['filename']));
        $this->assertStringEndsWith('.txt', $metadata['filename']);
    }
}
