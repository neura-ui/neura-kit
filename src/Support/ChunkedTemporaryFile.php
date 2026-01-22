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
     * Créer un TemporaryUploadedFile depuis un UUID de chunk upload
     * 
     * @param string $uuid
     * @return TemporaryUploadedFile|null
     */
    public static function createFromChunkUpload(string $uuid): ?TemporaryUploadedFile
    {
        $disk = config('livewire.temporary_file_upload.disk', 'local');
        $path = "livewire-tmp/{$uuid}";
        $metaPath = "{$path}.meta";

        if (!Storage::disk($disk)->exists($path) || !Storage::disk($disk)->exists($metaPath)) {
            return null;
        }

        $metadata = json_decode(Storage::disk($disk)->get($metaPath), true);

        // Créer un TemporaryUploadedFile compatible Livewire
        // Le constructeur Livewire prend (path_relatif, disk)
        $tmpFile = new TemporaryUploadedFile($uuid, $disk);
        
        return $tmpFile;
    }

    /**
     * Créer plusieurs TemporaryUploadedFile depuis des UUIDs
     * 
     * @param array $uuids
     * @return array
     */
    public static function createMultipleFromChunkUpload(array $uuids): array
    {
        return array_filter(array_map(function ($uuid) {
            return self::createFromChunkUpload($uuid);
        }, $uuids));
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
            // Skip les fichiers de métadonnées
            if (str_ends_with($file, '.meta')) {
                continue;
            }

            $metaFile = "{$file}.meta";
            
            if (Storage::disk($disk)->exists($metaFile)) {
                $metadata = json_decode(Storage::disk($disk)->get($metaFile), true);
                $uploadedAt = $metadata['uploaded_at'] ?? 0;
                
                if (now()->timestamp - $uploadedAt > ($olderThanMinutes * 60)) {
                    // Supprimer le fichier et ses métadonnées
                    Storage::disk($disk)->delete($file);
                    Storage::disk($disk)->delete($metaFile);
                    $count++;
                }
            }
        }

        return $count;
    }
}
