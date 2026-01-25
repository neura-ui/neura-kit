<?php

namespace Neura\Kit\Support;

use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Helper class pour créer des TemporaryUploadedFile depuis des uploads par chunks
 */
class ChunkedTemporaryFile
{
    /**
     * Créer un TemporaryUploadedFile depuis un UUID ou un array de données dropzone
     * 
     * @param string|array $data UUID string ou array avec 'uuid' key
     * @return TemporaryUploadedFile|null
     */
    public static function createFromChunkUpload(string|array $data): ?TemporaryUploadedFile
    {
        // Extraire l'UUID si c'est un array
        $uuid = is_array($data) ? ($data['uuid'] ?? null) : $data;
        
        if (!$uuid) {
            return null;
        }
        
        $disk = config('livewire.temporary_file_upload.disk', 'local');
        $path = "livewire-tmp/{$uuid}";
        $metaPath = "{$path}.meta";

        if (!Storage::disk($disk)->exists($path)) {
            return null;
        }

        $tmpFile = new TemporaryUploadedFile($uuid, $disk);
        
        return $tmpFile;
    }

    /**
     * Créer plusieurs TemporaryUploadedFile depuis des UUIDs ou arrays de données dropzone
     * 
     * Accepte:
     * - Array d'UUIDs: ['uuid1', 'uuid2']
     * - Array d'arrays dropzone: [['uuid' => 'uuid1', 'filename' => '...'], ...]
     * 
     * @param array $items
     * @return TemporaryUploadedFile[]
     */
    public static function createMultipleFromChunkUpload(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            return self::createFromChunkUpload($item);
        }, $items)));
    }
    
    /**
     * Créer un TemporaryUploadedFile depuis les données du dropzone (single file)
     * Alias pour createFromChunkUpload pour plus de clarté
     * 
     * @param array $dropzoneData Array avec 'uuid', 'filename', 'path', 'size', 'mime'
     * @return TemporaryUploadedFile|null
     */
    public static function fromDropzone(array $dropzoneData): ?TemporaryUploadedFile
    {
        return self::createFromChunkUpload($dropzoneData);
    }
    
    /**
     * Créer plusieurs TemporaryUploadedFile depuis les données du dropzone (multiple files)
     * 
     * @param array $dropzoneDataArray Array of dropzone data arrays
     * @return TemporaryUploadedFile[]
     */
    public static function fromDropzoneMultiple(array $dropzoneDataArray): array
    {
        return self::createMultipleFromChunkUpload($dropzoneDataArray);
    }

    /**
     * Nettoyer les fichiers temporaires expirés
     * 
     * @param int $olderThanMinutes
     * @return int Nombre de fichiers nettoyés
     */
    public static function cleanup(int $olderThanMinutes = 1440): int
    {
        $disk = config('livewire.temporary_file_upload.disk', 'local');
        $tmpDir = 'livewire-tmp';
        
        if (!Storage::disk($disk)->exists($tmpDir)) {
            return 0;
        }

        $count = 0;
        $files = Storage::disk($disk)->files($tmpDir);

        foreach ($files as $file) {
            if (str_ends_with($file, '.meta')) {
                continue;
            }

            $metaFile = "{$file}.meta";
            
            if (Storage::disk($disk)->exists($metaFile)) {
                $metadata = json_decode(Storage::disk($disk)->get($metaFile), true);
                $uploadedAt = $metadata['uploaded_at'] ?? 0;
                
                if (now()->timestamp - $uploadedAt > ($olderThanMinutes * 60)) {
                    Storage::disk($disk)->delete($file);
                    Storage::disk($disk)->delete($metaFile);
                    $count++;
                }
            }
        }

        return $count;
    }
}
