<?php

namespace Neura\Kit\Support;

use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Collection de fichiers uploadés via le dropzone
 * Convertit automatiquement les données dropzone en TemporaryUploadedFile
 */
class DropzoneFiles extends Collection
{
    /**
     * Créer une collection depuis les données du dropzone
     * 
     * @param array|null $dropzoneData
     * @return static
     */
    public static function from(?array $dropzoneData): static
    {
        if (empty($dropzoneData)) {
            return new static([]);
        }
        
        // Vérifier si c'est un seul fichier ou plusieurs
        if (isset($dropzoneData['uuid'])) {
            // Single file
            $file = ChunkedTemporaryFile::fromDropzone($dropzoneData);
            return new static($file ? [$file] : []);
        }
        
        // Multiple files
        $files = ChunkedTemporaryFile::fromDropzoneMultiple($dropzoneData);
        return new static($files);
    }
    
    /**
     * Obtenir le premier fichier
     * 
     * @return TemporaryUploadedFile|null
     */
    public function first(?callable $callback = null, $default = null): ?TemporaryUploadedFile
    {
        return parent::first($callback, $default);
    }
    
    /**
     * Stocker tous les fichiers dans un répertoire
     * 
     * @param string $path
     * @param string|null $disk
     * @return array Chemins des fichiers stockés
     */
    public function storeAll(string $path, ?string $disk = null): array
    {
        return $this->map(function (TemporaryUploadedFile $file) use ($path, $disk) {
            return $file->store($path, $disk ?? config('filesystems.default'));
        })->toArray();
    }
    
    /**
     * Stocker tous les fichiers avec leur nom original
     * 
     * @param string $path
     * @param string|null $disk
     * @return array Chemins des fichiers stockés
     */
    public function storeAllAs(string $path, ?string $disk = null): array
    {
        return $this->map(function (TemporaryUploadedFile $file) use ($path, $disk) {
            $name = $file->getClientOriginalName();
            return $file->storeAs($path, $name, $disk ?? config('filesystems.default'));
        })->toArray();
    }
}
